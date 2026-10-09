<?php

namespace App\Services;

use App\Core\DB;

/**
 * Out-of-stock alerts shown in the notification bell.
 *
 * Only products whose stock has run out completely are listed; there are
 * deliberately no "stock low" warnings. Rows with the same name, type and
 * package size count as one product (older databases hold several such rows),
 * and that product is only out of stock when all of them are at zero.
 * Alerts are worked out live, so they disappear as soon as stock is added.
 */
final class StockAlerts
{
    /** @return list<array{name:string,package:string}> one entry per out-of-stock product */
    public static function outOfStock(): array
    {
        if (Settings::get('low_stock_alert') !== '1') {
            return [];
        }

        $rows = DB::all(
            'SELECT MIN(name) AS name, category, package_size_kg
             FROM products
             GROUP BY LOWER(TRIM(name)), category, package_size_kg
             HAVING SUM(stock_quantity) <= 0
             ORDER BY MIN(name), category, package_size_kg NULLS FIRST'
        );

        return array_map(static fn(array $p): array => [
            'name'    => (string)$p['name'],
            'package' => package_label($p),
        ], $rows);
    }
}
