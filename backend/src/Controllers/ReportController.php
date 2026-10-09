<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Services\SalesReportPdf;

final class ReportController
{
    private const PER_PAGE = 15;

    private const ITEMS_SQL = "
        SELECT s.sale_number, s.sale_date, COALESCE(c.name, 'Walk-in Customer') AS customer, u.name AS cashier,
               s.payment_method, COALESCE(si.product_name, p.name) AS product, p.category, p.package_size_kg, p.unit,
               si.quantity, si.unit_price, si.total
        FROM sale_items si
        JOIN sales s ON s.id = si.sale_id
        JOIN products p ON p.id = si.product_id
        LEFT JOIN customers c ON c.id = s.customer_id
        JOIN users u ON u.id = s.created_by
        WHERE s.sale_date BETWEEN ? AND ?
        ORDER BY s.sale_date ASC, s.id ASC, si.id ASC";

    public function index(): void
    {
        [$from, $to] = $this->period();

        $totals = DB::one(
            'WITH p AS (SELECT CAST(? AS date) AS f, CAST(? AS date) AS t)
             SELECT
                (SELECT COUNT(*) FROM sales, p WHERE sale_date BETWEEN p.f AND p.t)                            AS sale_count,
                (SELECT COALESCE(SUM(total_amount), 0) FROM sales, p WHERE sale_date BETWEEN p.f AND p.t)      AS sales,
                (SELECT COALESCE(SUM(amount), 0) FROM expenses, p WHERE expense_date BETWEEN p.f AND p.t)      AS expenses,
                (SELECT COALESCE(SUM(total_amount), 0) FROM purchases, p WHERE purchase_date BETWEEN p.f AND p.t) AS purchases,
                (SELECT COUNT(*) FROM sale_items si JOIN sales s ON s.id = si.sale_id, p
                  WHERE s.sale_date BETWEEN p.f AND p.t)                                                        AS item_count',
            [$from, $to]
        );

        $pager = paginate((int)$totals['item_count'], self::PER_PAGE, (int)input('page', 1));
        $items = DB::all(self::ITEMS_SQL . ' LIMIT ? OFFSET ?', [$from, $to, $pager['perPage'], $pager['offset']]);

        view('reports/index', [
            'pageTitle' => 'Reports',
            'active'    => 'reports',
            'from'      => $from,
            'to'        => $to,
            'totals'    => $totals,
            'items'     => $items,
            'pager'     => $pager,
        ]);
    }

    public function export(): void
    {
        [$from, $to] = $this->period();
        $format = (string)input('format');

        $rows = DB::all(self::ITEMS_SQL, [$from, $to]);
        $summary = DB::one(
            'SELECT COUNT(*) AS sale_count, COALESCE(SUM(total_amount), 0) AS total FROM sales WHERE sale_date BETWEEN ? AND ?',
            [$from, $to]
        );
        $downloadedBy = Auth::name();
        $filename = 'MSINDA-Food-Shop-Sales-Report-' . $from . '-to-' . $to;

        if ($format === 'excel') {
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
            echo "\xEF\xBB\xBF";
            view('reports/excel', compact('rows', 'summary', 'from', 'to', 'downloadedBy'), null);
            return;
        }

        if ($format === 'pdf') {
            $pdf = (new SalesReportPdf(BASE_PATH . '/resources/report-logo.jpg'))
                ->render($rows, $summary, $from, $to, $downloadedBy);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            return;
        }

        abort(400, 'Invalid report format.');
    }

    /** @return array{0:string,1:string} validated [from, to] dates, defaulting to this month */
    private function period(): array
    {
        $from = (string)input('from', '');
        $to = (string)input('to', '');
        $from = valid_date($from) ? $from : date('Y-m-01');
        $to = valid_date($to) ? $to : date('Y-m-d');
        return $from > $to ? [$to, $from] : [$from, $to];
    }
}
