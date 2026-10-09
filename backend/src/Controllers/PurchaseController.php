<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use Throwable;

final class PurchaseController
{
    public function index(): void
    {
        $purchases = DB::all(
            "SELECT pu.id, pu.purchase_date, pu.total_amount, COALESCE(s.name, 'Unknown') AS supplier,
                    item.name AS product, item.category, item.package_size_kg, item.quantity
             FROM purchases pu
             LEFT JOIN suppliers s ON s.id = pu.supplier_id
             LEFT JOIN LATERAL (
                 SELECT p.name, p.category, p.package_size_kg, pi.quantity
                 FROM purchase_items pi JOIN products p ON p.id = pi.product_id
                 WHERE pi.purchase_id = pu.id ORDER BY pi.id LIMIT 1
             ) item ON TRUE
             ORDER BY pu.id DESC LIMIT 50"
        );

        view('purchases/index', [
            'pageTitle' => 'Purchases',
            'active'    => 'purchases',
            'products'  => DB::all('SELECT id, name, category, package_size_kg FROM products ORDER BY category, name, package_size_kg'),
            'suppliers' => DB::all('SELECT id, name FROM suppliers ORDER BY name'),
            'purchases' => $purchases,
            'error'     => flash('error'),
            'success'   => flash('success'),
        ]);
    }

    public function store(): void
    {
        $supplierId = (int)input('supplier_id', 0);
        $productId = (int)input('product_id', 0);
        $quantity = (int)input('quantity', 0);
        $cost = (float)input('unit_cost', -1);

        if ($productId <= 0 || $quantity <= 0 || $cost < 0) {
            flash('error', 'Enter valid purchase details.');
            redirect('/purchases');
        }

        try {
            DB::transaction(static function () use ($supplierId, $productId, $quantity, $cost): void {
                $total = $quantity * $cost;
                $purchaseId = (int)DB::value(
                    'INSERT INTO purchases (supplier_id, purchase_date, total_amount, created_by)
                     VALUES (?, CURRENT_DATE, ?, ?) RETURNING id',
                    [$supplierId > 0 ? $supplierId : null, $total, Auth::id()]
                );
                DB::execute(
                    'INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_cost, total) VALUES (?, ?, ?, ?, ?)',
                    [$purchaseId, $productId, $quantity, $cost, $total]
                );
                DB::execute('UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?', [$quantity, $productId]);
                DB::execute(
                    "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_id) VALUES (?, 'Purchase', ?, ?)",
                    [$productId, $quantity, $purchaseId]
                );
            });
            flash('success', 'Purchase saved and stock updated.');
        } catch (Throwable $e) {
            error_log((string)$e);
            flash('error', 'The purchase could not be saved. Please check the product and supplier.');
        }
        redirect('/purchases');
    }
}
