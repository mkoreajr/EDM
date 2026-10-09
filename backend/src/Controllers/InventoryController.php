<?php

namespace App\Controllers;

use App\Core\DB;

final class InventoryController
{
    public function index(): void
    {
        // One aggregate pass over stock_movements instead of two sub-queries per product.
        $rows = DB::all(
            "SELECT p.*, COALESCE(m.purchased, 0) AS purchased, COALESCE(m.sold, 0) AS sold
             FROM products p
             LEFT JOIN (
                 SELECT product_id,
                        SUM(quantity) FILTER (WHERE movement_type = 'Purchase') AS purchased,
                        SUM(quantity) FILTER (WHERE movement_type = 'Sale')     AS sold
                 FROM stock_movements GROUP BY product_id
             ) m ON m.product_id = p.id
             ORDER BY p.category, p.name, p.package_size_kg"
        );

        view('inventory/index', [
            'pageTitle' => 'Inventory',
            'active'    => 'inventory',
            'rows'      => $rows,
        ]);
    }
}
