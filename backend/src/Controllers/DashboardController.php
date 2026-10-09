<?php

namespace App\Controllers;

use App\Core\DB;

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

        // Out-of-stock alerts are shown in the notification bell (layouts/app.php), not here.
        view('dashboard/index', [
            'pageTitle' => 'Home',
            'active'    => 'dashboard',
            'stats'     => $stats,
        ]);
    }
}
