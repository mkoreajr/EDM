<?php

namespace App\Controllers;

use App\Core\DB;
use App\Services\Settings;

final class ReceiptController
{
    public function show(): void
    {
        $id = (int)input('id', 0);

        $sale = DB::one(
            "SELECT s.*, COALESCE(c.name, 'Walk-in Customer') AS customer, COALESCE(c.phone, '') AS customer_phone,
                    COALESCE(u.name, 'Administrator') AS cashier
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN users u ON u.id = s.created_by
             WHERE s.id = ?",
            [$id]
        );
        if ($sale === null) {
            abort(404, 'Sale not found.');
        }

        $items = DB::all(
            'SELECT si.*, COALESCE(si.product_name, p.name) AS name, p.unit, p.category, p.package_size_kg
             FROM sale_items si JOIN products p ON p.id = si.product_id
             WHERE si.sale_id = ? ORDER BY si.id',
            [$id]
        );
        $subtotal = array_sum(array_map(static fn(array $item) => (float)$item['total'], $items));

        view('sales/receipt', [
            'sale'     => $sale,
            'items'    => $items,
            'subtotal' => $subtotal,
            'discount' => max(0, $subtotal - (float)$sale['total_amount']),
            'footer'   => Settings::get('receipt_footer') ?: 'Thank you for shopping with us!',
        ], null);
    }
}
