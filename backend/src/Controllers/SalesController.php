<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Services\Settings;
use DomainException;
use Throwable;

final class SalesController
{
    private const PER_PAGE = 10;

    public function index(): void
    {
        $products = DB::all('SELECT * FROM products WHERE stock_quantity > 0 ORDER BY category, name, package_size_kg');
        $customers = DB::all('SELECT id, name FROM customers ORDER BY name');

        $pager = paginate((int)DB::value('SELECT COUNT(*) FROM sales'), self::PER_PAGE, (int)input('page', 1));
        $recent = DB::all(
            "SELECT s.id, s.sale_number, s.sale_date, s.payment_method, s.total_amount,
                    COALESCE(c.name, 'Walk-in Customer') AS customer
             FROM sales s LEFT JOIN customers c ON c.id = s.customer_id
             ORDER BY s.id DESC LIMIT ? OFFSET ?",
            [$pager['perPage'], $pager['offset']]
        );

        view('sales/index', [
            'pageTitle'      => 'Sales',
            'active'         => 'sales',
            'products'       => $products,
            'customers'      => $customers,
            'recent'         => $recent,
            'pager'          => $pager,
            'paymentMethods' => Settings::PAYMENT_METHODS,
            'defaultPayment' => Settings::get('default_payment'),
            'error'          => flash('error'),
        ]);
    }

    public function store(): void
    {
        $customerId = (int)input('customer_id', 0);
        $payment = (string)input('payment_method');
        $lines = $this->collectLines($_POST['product_id'] ?? [], $_POST['quantity'] ?? []);

        if (!in_array($payment, Settings::PAYMENT_METHODS, true) || !$lines) {
            flash('error', 'Please add at least one valid product and quantity.');
            redirect('/sales');
        }

        try {
            $saleId = DB::transaction(function () use ($customerId, $payment, $lines): int {
                $checked = [];
                $total = 0.0;
                foreach ($lines as $productId => $quantity) {
                    $product = DB::one(
                        'SELECT name, unit, selling_price, stock_quantity FROM products WHERE id = ? FOR UPDATE',
                        [$productId]
                    );
                    if ($product === null) {
                        throw new DomainException('Product not found.');
                    }
                    if ($quantity > (float)$product['stock_quantity']) {
                        throw new DomainException(sprintf(
                            'Insufficient stock for %s. Available: %s %s.',
                            $product['name'],
                            qty($product['stock_quantity']),
                            strtolower((string)$product['unit'])
                        ));
                    }
                    $price = (float)$product['selling_price'];
                    $lineTotal = $quantity * $price;
                    $total += $lineTotal;
                    $checked[] = [$productId, $quantity, $price, $lineTotal, $product['name']];
                }

                $saleNumber = 'SALE-' . date('YmdHis') . '-' . random_int(100, 999);
                $saleId = (int)DB::value(
                    'INSERT INTO sales (sale_number, customer_id, sale_date, payment_method, total_amount, created_by)
                     VALUES (?, ?, CURRENT_DATE, ?, ?, ?) RETURNING id',
                    [$saleNumber, $customerId > 0 ? $customerId : null, $payment, $total, Auth::id()]
                );

                foreach ($checked as [$productId, $quantity, $price, $lineTotal, $productName]) {
                    // The name is kept on the line so later renames never change this sale.
                    DB::execute(
                        'INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)',
                        [$saleId, $productId, $productName, $quantity, $price, $lineTotal]
                    );
                    DB::execute('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?', [$quantity, $productId]);
                    DB::execute(
                        "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_id) VALUES (?, 'Sale', ?, ?)",
                        [$productId, $quantity, $saleId]
                    );
                }
                return $saleId;
            });
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
            redirect('/sales');
        } catch (Throwable $e) {
            error_log((string)$e);
            flash('error', 'The sale could not be saved. Please try again.');
            redirect('/sales');
        }

        redirect(url('/receipt', ['id' => $saleId]));
    }

    /**
     * Merge the submitted rows into productId => total quantity, so the same
     * product added twice is checked against stock as one amount.
     * @return array<int,int>
     */
    private function collectLines($productIds, $quantities): array
    {
        $productIds = is_array($productIds) ? $productIds : [$productIds];
        $quantities = is_array($quantities) ? $quantities : [$quantities];

        $lines = [];
        foreach ($productIds as $index => $productId) {
            $productId = (int)$productId;
            $quantity = (int)($quantities[$index] ?? 0);
            if ($productId > 0 && $quantity > 0) {
                $lines[$productId] = ($lines[$productId] ?? 0) + $quantity;
            }
        }
        return $lines;
    }
}
