<?php

namespace App\Controllers;

use App\Core\DB;
use App\Services\Settings;

final class DashboardController
{
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
                (SELECT COUNT(*) FROM sales WHERE sale_date = CURRENT_DATE)                  AS today_count"
        );

        view('dashboard/index', [
            'pageTitle' => 'Home',
            'active'    => 'dashboard',
            'stats'     => $stats,
            'alerts'    => Settings::get('low_stock_alert') === '1' ? $this->stockAlerts() : [],
        ]);
    }

    /**
     * Out-of-stock notices only: one per product whose stock has run out completely.
     * There are deliberately no "stock low" warnings.
     *
     * @return list<array{empty:bool,title:string,text:string}>
     */
    private function stockAlerts(): array
    {
        $products = DB::all('SELECT name FROM products WHERE stock_quantity <= 0 ORDER BY name');

        return array_map(static fn(array $p): array => [
            'empty' => true,
            'title' => 'Out of Stock:',
            'text'  => "{$p['name']} has no stock left.",
        ], $products);
    }
}
