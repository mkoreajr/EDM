<?php

namespace App\Controllers;

use App\Core\CustomerAuth;
use App\Core\DB;
use App\Services\Cart;
use App\Services\Notifications;
use App\Services\OrderWorkflow;
use DomainException;
use Throwable;

/**
 * Customer portal (/shop): products, cart, checkout and order tracking.
 */
final class ShopController
{
    private const CATEGORIES = ['Eggs', 'Flour', 'Rice'];

    /** /shop shows the sign-in page to visitors and the shop to signed-in customers. */
    public function home(): void
    {
        if (CustomerAuth::check() && CustomerAuth::expired()) {
            CustomerAuth::logout();
            redirect('/shop?timeout=1');
        }
        if (!CustomerAuth::check()) {
            view('shop/login', [
                'error'    => flash('error'),
                'username' => flash('username') ?? '',
                'timedOut' => input('timeout', '') !== '',
            ], null);
            return;
        }

        // Only products the shop has in stock are offered; they disappear at 0 and return when restocked.
        $products = DB::all(
            "SELECT * FROM products WHERE category IN ('Eggs', 'Flour', 'Rice') AND stock_quantity >= 1
             ORDER BY CASE category WHEN 'Eggs' THEN 1 WHEN 'Flour' THEN 2 ELSE 3 END, package_size_kg NULLS FIRST, name, id"
        );
        $categories = array_values(array_intersect(self::CATEGORIES, array_unique(array_column($products, 'category'))));

        view('shop/home', [
            'pageTitle'  => 'Shop',
            'active'     => 'shop',
            'products'   => $products,
            'categories' => $categories,
            'message'    => flash('success'),
        ], 'layouts/shop');
    }

    public function cart(): void
    {
        $cart = Cart::lines();
        view('shop/cart', [
            'pageTitle' => 'Cart',
            'active'    => 'cart',
            'items'     => $cart['items'],
            'total'     => $cart['total'],
            'adjusted'  => $cart['adjusted'],
            'error'     => flash('error'),
        ], 'layouts/shop');
    }

    public function updateCart(): void
    {
        $productId = (int)input('product_id', 0);
        $quantity = max(0, (int)input('quantity', 0));
        if ($productId > 0) {
            match ((string)input('action')) {
                'add'    => Cart::put($productId, $quantity),
                'update' => Cart::put($productId, $quantity, true),
                'remove' => Cart::remove($productId),
                default  => null,
            };
        }
        redirect('/shop/cart');
    }

    public function checkout(): void
    {
        $cart = Cart::lines();
        if (!$cart['items'] || $cart['adjusted']) {
            // Stock changed since items were added: show the corrected cart before checkout.
            if ($cart['adjusted']) {
                flash('error', Cart::adjustmentMessage($cart['adjusted']));
            }
            redirect('/shop/cart');
        }
        view('shop/checkout', [
            'pageTitle' => 'Checkout',
            'active'    => 'cart',
            'customer'  => CustomerAuth::customer(),
            'items'     => $cart['items'],
            'total'     => $cart['total'],
            'methods'   => OrderWorkflow::PAYMENT_METHODS,
            'old'       => $_SESSION['checkout_old'] ?? [],
            'error'     => flash('error'),
        ], 'layouts/shop');
        unset($_SESSION['checkout_old']);
    }

    public function placeOrder(): void
    {
        $address = (string)input('address');
        $phone = (string)input('phone');
        $payment = (string)input('payment_method');
        $notes = (string)input('notes');
        $_SESSION['checkout_old'] = compact('address', 'phone', 'payment', 'notes');

        $error = null;
        if ($address === '' || $phone === '') {
            $error = 'Delivery address and phone are required.';
        } elseif (mb_strlen($address) > 255 || mb_strlen($phone) > 30 || mb_strlen($notes) > 1000) {
            $error = 'Address (max 255), phone (max 30) or note (max 1000 characters) is too long.';
        } elseif (!in_array($payment, OrderWorkflow::PAYMENT_METHODS, true)) {
            $error = 'Invalid payment method.';
        } elseif (!Cart::raw()) {
            redirect('/shop/cart');
        }
        if ($error !== null) {
            flash('error', $error);
            redirect('/shop/checkout');
        }

        $customer = CustomerAuth::customer();
        try {
            [$orderId, $orderNumber, $total] = DB::transaction(static function () use ($customer, $address, $phone, $payment, $notes): array {
                $lines = [];
                $total = 0.0;
                foreach (Cart::raw() as $productId => $quantity) {
                    $quantity = (int)$quantity;
                    if ($quantity <= 0) {
                        continue;
                    }
                    $product = DB::one('SELECT name, selling_price, stock_quantity FROM products WHERE id = ? FOR UPDATE', [(int)$productId]);
                    if ($product === null || $quantity > (float)$product['stock_quantity']) {
                        throw new DomainException('Stock changed for ' . ($product['name'] ?? 'a product') . '. Please update your cart.');
                    }
                    $price = (float)$product['selling_price'];
                    $lines[] = [(int)$productId, $quantity, $price, $quantity * $price, $product['name']];
                    $total += $quantity * $price;
                }
                if (!$lines) {
                    throw new DomainException('Your cart is empty.');
                }

                $number = 'ORD-' . date('YmdHis') . '-' . random_int(100, 999);
                $orderId = (int)DB::value(
                    "INSERT INTO orders (order_number, customer_id, status, delivery_address, phone, payment_method, notes, total_amount)
                     VALUES (?, ?, 'Pending', ?, ?, ?, ?, ?) RETURNING id",
                    [$number, $customer['id'], $address, $phone, $payment, $notes !== '' ? $notes : null, $total]
                );
                foreach ($lines as [$productId, $quantity, $price, $lineTotal, $productName]) {
                    // The name is kept on the line so later renames never change this order.
                    DB::execute(
                        'INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)',
                        [$orderId, $productId, $productName, $quantity, $price, $lineTotal]
                    );
                }
                return [$orderId, $number, $total];
            });
        } catch (DomainException $e) {
            flash('error', $e->getMessage());
            redirect('/shop/checkout');
        } catch (Throwable $e) {
            error_log((string)$e);
            flash('error', 'Could not place the order. Please try again.');
            redirect('/shop/checkout');
        }

        // Let every administrator know a new order is waiting.
        foreach (DB::all("SELECT id FROM users WHERE LOWER(role) = 'admin'") as $admin) {
            Notifications::send(
                (int)$admin['id'],
                'New online order',
                "Order {$orderNumber} from {$customer['name']} has been placed. Total TZS " . money($total) . '.'
            );
        }

        Cart::clear();
        unset($_SESSION['checkout_old']);
        redirect(url('/shop/order', ['id' => $orderId, 'placed' => 1]));
    }

    public function orders(): void
    {
        view('shop/orders', [
            'pageTitle' => 'My Orders',
            'active'    => 'orders',
            'orders'    => DB::all('SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC', [CustomerAuth::id()]),
        ], 'layouts/shop');
    }

    public function order(): void
    {
        $order = DB::one('SELECT * FROM orders WHERE id = ? AND customer_id = ?', [(int)input('id', 0), CustomerAuth::id()]);
        if ($order === null) {
            abort(404, 'Order not found.');
        }
        $items = DB::all(
            'SELECT oi.*, COALESCE(oi.product_name, p.name) AS name, p.category, p.unit, p.package_size_kg
             FROM order_items oi JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ? ORDER BY oi.id',
            [$order['id']]
        );

        view('shop/order', [
            'pageTitle' => 'Order Details',
            'active'    => 'orders',
            'order'     => $order,
            'items'     => $items,
            'placed'    => input('placed', '') !== '',
        ], 'layouts/shop');
    }
}
