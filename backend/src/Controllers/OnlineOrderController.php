<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Services\OrderWorkflow;
use DomainException;
use Throwable;

/**
 * Admin: online orders placed through the customer portal.
 */
final class OnlineOrderController
{
    private const PER_PAGE = 20;

    public function index(): void
    {
        $status = (string)input('status', 'All');
        if (!in_array($status, OrderWorkflow::STATUSES, true)) {
            $status = 'All';
        }
        $q = mb_substr((string)input('q'), 0, 100);
        $from = (string)input('from');
        $to = (string)input('to');
        $from = valid_date($from) ? $from : '';
        $to = valid_date($to) ? $to : '';

        $where = [];
        $params = [];
        if ($status !== 'All') {
            $where[] = 'o.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(o.order_number ILIKE ? OR c.name ILIKE ? OR c.phone ILIKE ? OR o.delivery_address ILIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }
        if ($from !== '') {
            $where[] = 'o.created_at >= ?::date';
            $params[] = $from;
        }
        if ($to !== '') {
            $where[] = "o.created_at < ?::date + INTERVAL '1 day'";
            $params[] = $to;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int)DB::value("SELECT COUNT(*) FROM orders o JOIN customers c ON c.id = o.customer_id {$whereSql}", $params);
        $pager = paginate($total, self::PER_PAGE, (int)input('page', 1));
        $orders = DB::all(
            "SELECT o.*, c.name AS customer_name, c.phone AS customer_phone
             FROM orders o JOIN customers c ON c.id = o.customer_id
             {$whereSql} ORDER BY o.id DESC LIMIT ? OFFSET ?",
            array_merge($params, [$pager['perPage'], $pager['offset']])
        );

        // All items for the listed orders in one query.
        $items = [];
        if ($orders) {
            $ids = array_map(static fn($o) => (int)$o['id'], $orders);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            foreach (DB::all(
                "SELECT oi.order_id, oi.quantity, oi.unit_price, oi.total, COALESCE(oi.product_name, p.name) AS name
                 FROM order_items oi JOIN products p ON p.id = oi.product_id
                 WHERE oi.order_id IN ($placeholders) ORDER BY oi.id",
                $ids
            ) as $item) {
                $items[(int)$item['order_id']][] = $item;
            }
        }

        $counts = array_fill_keys(OrderWorkflow::STATUSES, 0);
        foreach (DB::all('SELECT status, COUNT(*) AS n FROM orders GROUP BY status') as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int)$row['n'];
            }
        }

        view('orders/index', [
            'pageTitle'     => 'Online Orders',
            'active'        => 'online_orders',
            'styles'        => ['css/orders.css'],
            'orders'        => $orders,
            'items'         => $items,
            'counts'        => $counts,
            'allCount'      => array_sum($counts),
            'status'        => $status,
            'q'             => $q,
            'from'          => $from,
            'to'            => $to,
            'pager'         => $pager,
            'statuses'      => OrderWorkflow::STATUSES,
            'transitions'   => OrderWorkflow::TRANSITIONS,
            'cancelReasons' => OrderWorkflow::CANCEL_REASONS,
            'error'         => flash('error'),
            'success'       => flash('success'),
        ]);
    }

    /** Status change. Answers JSON to the page script, or redirects when JavaScript is off. */
    public function updateStatus(): void
    {
        try {
            $result = OrderWorkflow::update(
                (int)input('order_id', 0),
                (string)input('status'),
                (string)input('delivery_person'),
                (string)input('admin_note'),
                (string)input('cancellation_reason'),
                (string)input('cancellation_reason_custom'),
                Auth::id()
            );
        } catch (DomainException $e) {
            $this->fail($e->getMessage());
        } catch (Throwable $e) {
            error_log((string)$e);
            $this->fail('The order could not be updated. Please try again.');
        }

        if (wants_json()) {
            json_response(['ok' => true] + $result);
        }
        flash('success', $result['message']);
        redirect($this->back());
    }

    private function fail(string $message): void
    {
        if (wants_json()) {
            json_response(['ok' => false, 'message' => $message], 422);
        }
        flash('error', $message);
        redirect($this->back());
    }

    /** Return to the same filtered list after a non-JavaScript update. */
    private function back(): string
    {
        $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
        $path = (string)parse_url($referer, PHP_URL_PATH);
        $query = (string)parse_url($referer, PHP_URL_QUERY);
        return $path === '/online-orders' ? $path . ($query !== '' ? '?' . $query : '') : '/online-orders';
    }
}
