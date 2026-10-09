<?php

namespace App\Controllers;

use App\Core\DB;
use App\Services\Settings;

final class DashboardController
{
    /** [label, category key, threshold, unit word] */
    private const STOCK_RULES = [
        ['Egg',   'egg_stock',   50, 'trays'],
        ['Rice',  'rice_stock',  30, 'packages'],
        ['Flour', 'flour_stock', 30, 'packages'],
    ];

    /** /admin — friendly staff entry URL; the router sends signed-out visitors to the login page. */
    public function entry(): void
    {
        redirect('/dashboard');
    }

    public function index(): void
    {
        // One round-trip for every number on the dashboard.
        $stats = DB::one(
            "SELECT
                (SELECT COUNT(*) FROM products)                                              AS products,
                (SELECT COALESCE(SUM(stock_quantity), 0) FROM products)                       AS stock,
                (SELECT COUNT(*) FROM customers)                                             AS customers,
                (SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE sale_date = CURRENT_DATE) AS today_total,
                (SELECT COUNT(*) FROM sales WHERE sale_date = CURRENT_DATE)                  AS today_count,
                (SELECT COALESCE(SUM(stock_quantity), 0) FROM products WHERE LOWER(unit) = 'tray')      AS egg_stock,
                (SELECT COALESCE(SUM(stock_quantity), 0) FROM products WHERE LOWER(category) = 'rice')  AS rice_stock,
                (SELECT COALESCE(SUM(stock_quantity), 0) FROM products WHERE LOWER(category) = 'flour') AS flour_stock"
        );

        view('dashboard/index', [
            'pageTitle' => 'Home',
            'active'    => 'dashboard',
            'stats'     => $stats,
            'alerts'    => Settings::get('low_stock_alert') === '1' ? $this->stockAlerts($stats) : [],
        ]);
    }

    /** @return list<array{empty:bool,title:string,text:string}> */
    private function stockAlerts(array $stats): array
    {
        $alerts = [];
        foreach (self::STOCK_RULES as [$label, $key, $threshold, $unit]) {
            $stock = (float)$stats[$key];
            if ($stock <= 0) {
                $alerts[] = [
                    'empty' => true,
                    'title' => "{$label} Stock Out:",
                    'text'  => 'No ' . strtolower($label) . ' stock is currently available.',
                ];
            } elseif ($stock < $threshold) {
                $what = $label === 'Egg' ? 'Total stock' : 'Total ' . strtolower($label) . ' stock';
                $alerts[] = [
                    'empty' => false,
                    'title' => ($label === 'Egg' ? 'Stock Low:' : "{$label} Stock Low:"),
                    'text'  => "{$what} is below {$threshold} {$unit}. Current stock:",
                    'value' => number_format($stock, 2) . " {$unit}",
                ];
            }
        }
        return $alerts;
    }
}
