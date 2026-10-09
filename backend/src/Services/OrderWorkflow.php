<?php

namespace App\Services;

use App\Core\DB;
use DomainException;

/**
 * Online-order status changes. Each change runs in one transaction together
 * with its stock and sales effects:
 *
 *   Pending → Confirmed          reserves stock (once)
 *   Confirmed → Out for Delivery
 *   Out for Delivery → Delivered records the sale (stock was already reserved)
 *   any of the above → Cancelled releases reserved stock
 */
final class OrderWorkflow
{
    public const STATUSES = ['Pending', 'Confirmed', 'Out for Delivery', 'Delivered', 'Cancelled'];

    /** Allowed next statuses (including staying on the current one to edit notes). */
    public const TRANSITIONS = [
        'Pending'          => ['Pending', 'Confirmed', 'Cancelled'],
        'Confirmed'        => ['Confirmed', 'Out for Delivery', 'Cancelled'],
        'Out for Delivery' => ['Out for Delivery', 'Delivered', 'Cancelled'],
        'Delivered'        => ['Delivered'],
        'Cancelled'        => ['Cancelled'],
    ];

    public const CANCEL_REASONS = [
        'Customer requested cancellation',
        'Product/item out of stock',
        'Payment issue',
        'Invalid customer/order information',
        'Delivery unavailable',
        'Customer did not respond',
        'Duplicate order',
        'Order placed by mistake',
        'Other',
    ];

    public const PAYMENT_METHODS = ['Cash on Delivery', 'Mobile Money', 'Bank'];

    /** Online payment method => POS sale payment method. */
    private const SALE_PAYMENT = ['Cash on Delivery' => 'Cash', 'Mobile Money' => 'Mobile Money', 'Bank' => 'Bank'];

    public static function isFinal(string $status): bool
    {
        return in_array($status, ['Delivered', 'Cancelled'], true);
    }

    /**
     * Apply a status change. Throws DomainException with a message for the admin
     * when the change is not allowed.
     *
     * @return array{order_number:string,status:string,previous_status:string,cancellation_reason:?string,message:string}
     */
    public static function update(
        int $orderId,
        string $newStatus,
        string $deliveryPerson,
        string $adminNote,
        string $cancelReason,
        string $cancelOther,
        int $userId
    ): array {
        if ($orderId <= 0 || !in_array($newStatus, self::STATUSES, true)) {
            throw new DomainException('Invalid order update.');
        }
        if (mb_strlen($deliveryPerson) > 100) {
            throw new DomainException('Delivery person name is too long (max 100 characters).');
        }
        if ($newStatus === 'Cancelled') {
            if ($cancelReason === '' || !in_array($cancelReason, self::CANCEL_REASONS, true)) {
                throw new DomainException('Please choose a cancellation reason.');
            }
            if ($cancelReason === 'Other') {
                if ($cancelOther === '') {
                    throw new DomainException('Please specify the cancellation reason.');
                }
                $cancelReason = $cancelOther;
            }
        }

        return DB::transaction(static function () use ($orderId, $newStatus, $deliveryPerson, $adminNote, $cancelReason, $userId): array {
            $order = DB::one('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
            if ($order === null) {
                throw new DomainException('Order not found.');
            }

            $current = (string)$order['status'];
            if (!in_array($newStatus, self::TRANSITIONS[$current] ?? [], true)) {
                throw new DomainException("Cannot change an order from {$current} to {$newStatus}. Follow the order workflow: "
                    . 'Pending → Confirmed → Out for Delivery → Delivered. Orders can be cancelled before delivery.');
            }

            $reserved = (bool)$order['stock_reserved'];

            if ($newStatus === 'Confirmed' && !$reserved) {
                self::reserveStock($orderId);
                $reserved = true;
            }

            if ($newStatus === 'Cancelled' && $current !== 'Cancelled') {
                if ($reserved && empty($order['sale_id'])) {
                    self::releaseStock($orderId);
                }
                $reserved = false;
            }

            if ($newStatus === 'Delivered' && $current !== 'Delivered' && empty($order['sale_id'])) {
                if (!$reserved) {
                    throw new DomainException('Confirm the order before marking it as delivered.');
                }
                self::recordSale($order, $userId);
            }

            $cancelling = $newStatus === 'Cancelled' && $current !== 'Cancelled';
            DB::execute(
                'UPDATE orders SET status = ?, delivery_person = ?, admin_note = ?, stock_reserved = ?,
                        cancellation_reason = ?, cancelled_at = ?, cancelled_by = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?',
                [
                    $newStatus,
                    $deliveryPerson !== '' ? $deliveryPerson : null,
                    $adminNote !== '' ? $adminNote : null,
                    $reserved ? 'true' : 'false',
                    $cancelling ? $cancelReason : $order['cancellation_reason'],
                    $cancelling ? date('Y-m-d H:i:s') : $order['cancelled_at'],
                    $cancelling ? ($userId ?: null) : $order['cancelled_by'],
                    $orderId,
                ]
            );

            return [
                'order_number'        => (string)$order['order_number'],
                'status'              => $newStatus,
                'previous_status'     => $current,
                'cancellation_reason' => $cancelling ? $cancelReason : $order['cancellation_reason'],
                'message'             => "Order {$order['order_number']} updated to {$newStatus}.",
            ];
        });
    }

    private static function reserveStock(int $orderId): void
    {
        $items = DB::all(
            'SELECT oi.product_id, oi.quantity, COALESCE(oi.product_name, p.name) AS name, p.stock_quantity
             FROM order_items oi JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ? ORDER BY oi.id FOR UPDATE OF p',
            [$orderId]
        );
        if (!$items) {
            throw new DomainException('This order has no items to confirm.');
        }

        // The same product can appear on more than one line; check the combined amount.
        $needed = [];
        foreach ($items as $item) {
            $needed[(int)$item['product_id']] = ($needed[(int)$item['product_id']] ?? 0) + (float)$item['quantity'];
        }
        foreach ($items as $item) {
            $productId = (int)$item['product_id'];
            if (!isset($needed[$productId])) {
                continue;
            }
            if ((float)$item['stock_quantity'] < $needed[$productId]) {
                throw new DomainException('Insufficient stock for ' . $item['name'] . '. Available: ' . qty($item['stock_quantity']) . '.');
            }
            unset($needed[$productId]);
        }

        foreach ($items as $item) {
            $quantity = (float)$item['quantity'];
            DB::execute('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?', [$quantity, $item['product_id']]);
            DB::execute(
                "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_id) VALUES (?, 'Adjustment', ?, ?)",
                [$item['product_id'], -$quantity, $orderId]
            );
        }
    }

    private static function releaseStock(int $orderId): void
    {
        foreach (DB::all('SELECT product_id, quantity FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]) as $item) {
            DB::execute('UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?', [$item['quantity'], $item['product_id']]);
            DB::execute(
                "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_id) VALUES (?, 'Adjustment', ?, ?)",
                [$item['product_id'], $item['quantity'], $orderId]
            );
        }
    }

    /** Delivered: create the sale once. Stock was already deducted when the order was confirmed. */
    private static function recordSale(array $order, int $userId): void
    {
        $saleId = (int)DB::value(
            'INSERT INTO sales (sale_number, customer_id, sale_date, payment_method, total_amount, created_by)
             VALUES (?, ?, CURRENT_DATE, ?, ?, ?) RETURNING id',
            [
                'SALE-' . date('YmdHis') . '-' . random_int(100, 999),
                $order['customer_id'],
                self::SALE_PAYMENT[$order['payment_method']] ?? 'Cash',
                $order['total_amount'],
                $userId,
            ]
        );

        $items = DB::all(
            'SELECT oi.product_id, COALESCE(oi.product_name, p.name) AS product_name, oi.quantity, oi.unit_price, oi.total
             FROM order_items oi JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ? ORDER BY oi.id',
            [$order['id']]
        );
        foreach ($items as $item) {
            // The sale keeps the name the customer ordered, even if the product was renamed since.
            DB::execute(
                'INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)',
                [$saleId, $item['product_id'], $item['product_name'], $item['quantity'], $item['unit_price'], $item['total']]
            );
            DB::execute(
                "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_id) VALUES (?, 'Sale', ?, ?)",
                [$item['product_id'], $item['quantity'], $saleId]
            );
        }

        DB::execute('UPDATE orders SET sale_id = ?, delivered_at = CURRENT_TIMESTAMP WHERE id = ?', [$saleId, $order['id']]);
    }
}
