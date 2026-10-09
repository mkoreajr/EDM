<?php
/**
 * Admin: online orders from the customer portal.
 *
 * @var list<array<string,mixed>> $orders
 * @var array<int,list<array<string,mixed>>> $items  order id => items
 * @var array<string,int> $counts  status => number of orders
 * @var int $allCount
 * @var string $status  active filter
 * @var string $q
 * @var string $from
 * @var string $to
 * @var array $pager
 * @var list<string> $statuses
 * @var array<string,list<string>> $transitions
 * @var list<string> $cancelReasons
 * @var string|null $error
 * @var string|null $success
 */

use App\Services\OrderWorkflow;

$icons = [
    'clock'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'check'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 12.5 4 4 8-9" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'delivery' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h11v10H3zM14 9h4l3 3v4h-7z" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/><circle cx="7" cy="18" r="1.7" fill="currentColor"/><circle cx="18" cy="18" r="1.7" fill="currentColor"/></svg>',
    'orders'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="4" width="14" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="1.9"/><path d="M8 8h8M8 12h8M8 16h5" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>',
    'cancel'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="m9 9 6 6m0-6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
];
$summary = [
    ['Pending', 'pending', 'clock'],
    ['Confirmed', 'confirmed', 'check'],
    ['Out for Delivery', 'delivery', 'delivery'],
    ['Delivered', 'delivered', 'check'],
];
$pillIcons = ['All' => 'orders', 'Pending' => 'clock', 'Confirmed' => 'check', 'Out for Delivery' => 'delivery', 'Delivered' => 'check', 'Cancelled' => 'cancel'];
$filters = ['All' => ['All Orders', $allCount]];
foreach ($statuses as $s) {
    $filters[$s] = [$s, $counts[$s]];
}
$statusClass = static fn(string $s): string => strtolower(str_replace(' ', '-', $s));
$when = static fn($ts, string $format = 'd M Y, h:i A'): string => $ts ? date($format, strtotime((string)$ts)) : '—';
$keep = ['q' => $q, 'from' => $from, 'to' => $to];
?>
<div id="mobileOrderDetailScreen" class="mobile-order-detail-screen" aria-hidden="true"></div>
<div class="online-orders-page" data-transitions="<?= e(json_encode($transitions)) ?>">
  <section class="online-orders-hero">
    <div class="online-orders-heading">
      <div class="online-orders-title-wrap">
        <div class="online-orders-kicker">CUSTOMER ORDERS</div>
        <div class="online-orders-title-row">
          <div class="online-orders-cart-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M3 4h2l2.1 10.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 7H6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="19" r="1.5" fill="currentColor"/><circle cx="18" cy="19" r="1.5" fill="currentColor"/></svg>
          </div>
          <div>
            <h1>Online Orders</h1>
            <p>Receive customer orders from the portal, confirm stock and manage delivery.</p>
          </div>
        </div>
      </div>
      <div class="online-orders-summary">
        <?php foreach ($summary as [$label, $class, $icon]): ?>
          <div class="order-summary-card <?= $class ?>" data-summary="<?= e($label) ?>"><span class="summary-icon"><?= $icons[$icon] ?></span><div><strong><?= number_format($counts[$label]) ?></strong><small><?= e($label) ?></small></div><i></i></div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php partial('partials/alerts', compact('error', 'success')); ?>

  <section class="online-orders-toolbar">
    <div class="order-filter-pills">
      <?php foreach ($filters as $key => [$label, $count]): ?>
        <a class="order-filter-pill <?= $status === $key ? 'active' : '' ?>" data-filter="<?= e($key) ?>" href="<?= e(url('/online-orders', $keep + ['status' => $key === 'All' ? null : $key])) ?>">
          <span class="pill-symbol"><?= $icons[$pillIcons[$key]] ?></span>
          <span class="pill-label"><?= e($label) ?></span><b><?= number_format($count) ?></b>
        </a>
      <?php endforeach; ?>
    </div>
    <form class="orders-search-form" method="get" action="/online-orders">
      <?php if ($status !== 'All'): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
      <label class="orders-search">
        <span aria-hidden="true">⌕</span>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search orders, customer name, phone..." aria-label="Search orders">
      </label>
      <label class="orders-date"><span aria-hidden="true">▣</span><input type="date" name="from" value="<?= e($from) ?>" aria-label="From date"></label>
      <span class="date-dash">–</span>
      <label class="orders-date"><input type="date" name="to" value="<?= e($to) ?>" aria-label="To date"></label>
      <button class="orders-search-btn" type="submit">Search</button>
    </form>
  </section>

  <?php if (!$orders): ?>
    <section class="online-orders-empty">
      <div class="empty-cart-art">
        <div class="empty-cart-glow"></div>
        <div class="empty-cart">
          <svg viewBox="0 0 100 100" aria-hidden="true"><path d="M24 29h8l8 37h35c5 0 9-3 11-8l6-21H38" fill="none" stroke="currentColor" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="48" cy="79" r="6" fill="currentColor"/><circle cx="78" cy="79" r="6" fill="currentColor"/></svg>
        </div>
        <span class="spark spark-a"></span><span class="spark spark-b"></span><span class="spark spark-c"></span>
      </div>
      <h2>No orders found</h2>
      <p>Customer orders from the portal will appear here when they are placed.</p>
      <div class="empty-info"><span>i</span> When customers place orders through the customer portal (<a href="/shop" target="_blank" rel="noopener">/shop</a>), they will be displayed here for you to confirm, manage and deliver.</div>
    </section>
  <?php else: ?>
    <section class="online-orders-table-card">
      <div class="orders-table-scroll">
        <table class="online-orders-table">
          <thead><tr><th>#</th><th>Order Details</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Order Date</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($orders as $o):
              $id = (int)$o['id'];
              $orderItems = $items[$id] ?? [];
              $names = array_map(static fn($it) => $it['name'] . ' × ' . qty($it['quantity']), $orderItems);
              $itemCount = array_sum(array_map(static fn($it) => (float)$it['quantity'], $orderItems));
              $current = (string)$o['status'];
          ?>
            <tr class="order-row" id="order-row-<?= $id ?>" data-order-id="<?= $id ?>" data-status="<?= e($current) ?>">
              <td><span class="order-number-chip"><?= e($o['order_number']) ?></span></td>
              <td><div class="order-detail-name"><?= e($names[0] ?? 'Order items') ?></div><small><?= count($names) > 1 ? '+ ' . (count($names) - 1) . ' more item(s)' : 'Qty: ' . qty($itemCount) ?></small></td>
              <td><div class="customer-cell"><span class="customer-avatar" aria-hidden="true">♙</span><div><strong><?= e($o['customer_name']) ?></strong><small>☎ <?= e($o['customer_phone']) ?></small></div></div></td>
              <td><strong><?= qty($itemCount) ?> item<?= abs($itemCount - 1) < 0.00001 ? '' : 's' ?></strong><small><?= e(implode(', ', $names)) ?></small></td>
              <td><strong class="table-total">TZS <?= money($o['total_amount']) ?></strong></td>
              <td><span class="payment-chip"><?= e($o['payment_method']) ?></span></td>
              <td><span class="table-status <?= $statusClass($current) ?>" data-status-chip><?= e($current) ?></span></td>
              <td><div class="date-cell">▣ <?= e($when($o['created_at'], 'd M Y')) ?><small>◷ <?= e($when($o['created_at'], 'h:i A')) ?></small></div></td>
              <td>
                <div class="table-actions">
                  <button type="button" class="table-action primary" data-view-order="<?= $id ?>">View</button>
                  <?php if (!OrderWorkflow::isFinal($current)): ?>
                    <button type="button" class="table-action secondary" data-confirm-order="<?= $id ?>">Confirm</button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>

            <tr class="order-view-row" id="order-view-<?= $id ?>"><td colspan="9">
              <div class="order-view-panel">
                <button type="button" class="mobile-order-back" data-close-detail aria-label="Back to orders">← <span>Back to Orders</span></button>
                <div class="order-view-head">
                  <div><span class="order-view-kicker">ORDER DETAILS</span><strong><?= e($o['order_number']) ?></strong></div>
                  <span class="order-view-status" data-view-status><?= e($current) ?></span>
                </div>
                <div class="order-view-grid">
                  <section class="order-view-card order-items-card">
                    <h3>Customer Items</h3>
                    <div class="order-items-table-wrap">
                      <table class="order-items-table">
                        <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Item Total</th></tr></thead>
                        <tbody>
                          <?php foreach ($orderItems as $it): ?>
                            <tr><td><strong><?= e($it['name']) ?></strong></td><td><?= qty($it['quantity']) ?></td><td>TZS <?= money($it['unit_price']) ?></td><td><strong>TZS <?= money($it['total']) ?></strong></td></tr>
                          <?php endforeach; ?>
                        </tbody>
                        <tfoot><tr><th colspan="3">Order Total</th><th>TZS <?= money($o['total_amount']) ?></th></tr></tfoot>
                      </table>
                    </div>
                  </section>
                  <section class="order-view-card">
                    <h3>Customer &amp; Delivery</h3>
                    <dl class="order-info-list">
                      <div><dt>Customer</dt><dd><?= e($o['customer_name']) ?></dd></div>
                      <div><dt>Phone</dt><dd><?= e($o['phone'] ?: $o['customer_phone']) ?></dd></div>
                      <div><dt>Delivery Address</dt><dd><?= e($o['delivery_address']) ?></dd></div>
                      <div><dt>Order Date</dt><dd><?= e($when($o['created_at'])) ?></dd></div>
                    </dl>
                  </section>
                  <section class="order-view-card">
                    <h3>Payment &amp; Notes</h3>
                    <dl class="order-info-list" data-notes-list>
                      <div><dt>Payment</dt><dd><?= e($o['payment_method']) ?></dd></div>
                      <div><dt>Order Note</dt><dd><?= e($o['notes'] ?: 'No customer note') ?></dd></div>
                      <div><dt>Admin Note</dt><dd data-view-admin-note><?= e($o['admin_note'] ?: 'No admin note') ?></dd></div>
                      <?php if (!empty($o['cancellation_reason'])): ?><div><dt>Cancellation Reason</dt><dd data-view-cancellation-reason><?= e($o['cancellation_reason']) ?></dd></div><?php endif; ?>
                      <?php if (!empty($o['cancelled_at'])): ?><div><dt>Cancelled At</dt><dd><?= e($when($o['cancelled_at'])) ?></dd></div><?php endif; ?>
                    </dl>
                  </section>
                </div>
                <div class="mobile-detail-extra-grid">
                  <section class="order-view-card">
                    <h3>Delivery Information</h3>
                    <dl class="order-info-list">
                      <div><dt>Status</dt><dd data-view-delivery-status><?= e($current) ?></dd></div>
                      <div><dt>Delivery Person</dt><dd data-view-delivery-person><?= e($o['delivery_person'] ?: '—') ?></dd></div>
                      <div><dt>Delivered At</dt><dd><?= e($when($o['delivered_at'])) ?></dd></div>
                    </dl>
                  </section>
                  <section class="order-view-card">
                    <h3>Admin Information</h3>
                    <dl class="order-info-list">
                      <div><dt>Last Updated</dt><dd><?= e($when($o['updated_at'] ?: $o['created_at'])) ?></dd></div>
                    </dl>
                  </section>
                </div>
              </div>
            </td></tr>

            <tr class="order-confirm-row" id="order-details-<?= $id ?>"><td colspan="9">
              <form method="post" action="/online-orders/status" class="order-update-form" data-order-id="<?= $id ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= $id ?>">
                <input type="hidden" name="cancellation_reason" value="">
                <input type="hidden" name="cancellation_reason_custom" value="">
                <div class="order-editor-heading">
                  <div><span>Prepare &amp; Confirm</span><strong><?= e($o['order_number']) ?></strong><small><?= e($o['customer_name']) ?> · <?= e($o['customer_phone']) ?> · Review items in View before confirming.</small></div>
                  <span class="order-editor-current" data-current-label>Current: <?= e($current) ?></span>
                </div>
                <label>Delivery person<input name="delivery_person" value="<?= e($o['delivery_person'] ?? '') ?>" maxlength="100" placeholder="Driver / rider name"></label>
                <label>Admin note<input name="admin_note" value="<?= e($o['admin_note'] ?? '') ?>" maxlength="1000" placeholder="Message to customer"></label>
                <label>Status<select name="status" data-status-select>
                  <?php foreach ($statuses as $option): ?>
                    <option value="<?= e($option) ?>" <?= $option === $current ? 'selected' : '' ?> <?= in_array($option, $transitions[$current] ?? [$current], true) ? '' : 'disabled' ?>><?= e($option) ?></option>
                  <?php endforeach; ?>
                </select></label>
                <div class="order-editor-actions">
                  <button class="btn primary order-save-btn" type="submit"><span class="save-label">Save changes</span><span class="save-spinner" aria-hidden="true"></span></button>
                  <span class="order-save-message" role="status" aria-live="polite"></span>
                </div>
              </form>
            </td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="orders-table-footer">
        <?php partial('partials/pagination', ['pager' => $pager, 'base' => '/online-orders', 'query' => $keep + ['status' => $status === 'All' ? null : $status], 'noun' => 'orders']); ?>
        <?php if ($pager['total'] <= $pager['perPage']): ?><span>Showing <?= count($orders) ?> of <?= number_format($pager['total']) ?> orders</span><?php endif; ?>
      </div>
    </section>
  <?php endif; ?>
</div>

<div class="cancel-modal" id="cancel-modal" aria-hidden="true">
  <div class="cancel-modal-backdrop" data-cancel-close></div>
  <div class="cancel-modal-card" role="dialog" aria-modal="true" aria-labelledby="cancel-modal-title">
    <button type="button" class="cancel-modal-close" data-cancel-close aria-label="Close">&times;</button>
    <div class="cancel-modal-icon">!</div>
    <h3 id="cancel-modal-title">Why are you cancelling this order?</h3>
    <p>Select a reason before cancelling the order. The customer will see it on their order.</p>
    <label class="cancel-reason-label">Cancellation reason
      <select id="cancel-reason-select">
        <option value="">Choose a reason</option>
        <?php foreach ($cancelReasons as $reason): ?><option><?= e($reason) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label class="cancel-other-wrap" id="cancel-other-wrap">Please specify the reason
      <textarea id="cancel-reason-custom" rows="3" maxlength="500" placeholder="Please specify the reason"></textarea>
    </label>
    <div class="cancel-modal-actions">
      <button type="button" class="cancel-back-btn" data-cancel-close>Back</button>
      <button type="button" class="cancel-confirm-btn" id="cancel-confirm-btn">Confirm Cancellation</button>
    </div>
    <div class="cancel-modal-error" id="cancel-modal-error" role="alert"></div>
  </div>
</div>
<script src="<?= asset('js/orders.js') ?>" defer></script>
