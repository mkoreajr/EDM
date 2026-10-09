<?php
/**
 * @var list<array<string,mixed>> $orders
 */

$statusClass = static fn(string $s): string => strtolower(str_replace(' ', '-', $s));
?>
<section class="section-head section-head-card">
  <div><div class="eyebrow">ORDER HISTORY</div><h1>My Orders</h1><p>Follow every order from pending to delivery.</p></div>
  <a class="portal-btn" href="/shop">+ New Order</a>
</section>

<?php if (!$orders): ?>
  <div class="empty-card"><h3>No orders yet</h3><p>Your online orders will appear here.</p></div>
<?php else: ?>
  <div class="orders-table">
    <div class="orders-head"><span>Order</span><span>Date</span><span>Total</span><span>Status</span><span></span></div>
    <?php foreach ($orders as $o): ?>
      <a class="order-row" href="<?= url('/shop/order', ['id' => (int)$o['id']]) ?>">
        <strong><?= e($o['order_number']) ?></strong>
        <span><?= e(date('d M Y, H:i', strtotime((string)$o['created_at']))) ?></span>
        <span>TZS <?= money($o['total_amount']) ?></span>
        <span>
          <b class="status <?= e($statusClass((string)$o['status'])) ?>"><?= e($o['status']) ?></b>
          <?php if ($o['status'] === 'Cancelled' && !empty($o['cancellation_reason'])): ?>
            <small class="order-cancel-reason">Reason: <?= e($o['cancellation_reason']) ?></small>
          <?php endif; ?>
        </span>
        <span>View →</span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
