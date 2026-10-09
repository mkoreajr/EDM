<?php
/**
 * Order details with delivery progress, or the cancellation notice.
 * @var array<string,mixed> $order
 * @var list<array<string,mixed>> $items
 * @var bool $placed
 */

$status = (string)$order['status'];
$cancelled = $status === 'Cancelled';
$stageIndex = ['Pending' => 0, 'Confirmed' => 1, 'Out for Delivery' => 2, 'Delivered' => 3];
$currentStage = $stageIndex[$status] ?? 0;
$progress = round($currentStage / 3 * 100) . '%';
$stages = [
    ['Order Received', 'We received your order.'],
    ['Confirmed', 'Stock and order are being prepared.'],
    ['Out for Delivery', 'Your order is on the way.'],
    ['Delivered', 'Order completed successfully.'],
];
$when = static fn($ts): string => date('d M Y, H:i', strtotime((string)$ts));
?>
<?php if ($placed): ?><div class="portal-alert success">Order placed successfully. MSINDA Food Shop has received your order.</div><?php endif; ?>

<?php if ($cancelled): ?>
  <div class="portal-alert error cancellation-alert" role="alert">
    <strong>Order Cancelled</strong>
    <p>This order was cancelled by MSINDA.</p>
    <?php if (!empty($order['cancellation_reason'])): ?>
      <div class="cancellation-reason"><span>Cancellation reason</span><strong><?= nl2br(e($order['cancellation_reason'])) ?></strong></div>
    <?php endif; ?>
    <?php if (!empty($order['cancelled_at'])): ?><small>Cancelled <?= e($when($order['cancelled_at'])) ?></small><?php endif; ?>
  </div>
<?php endif; ?>

<section class="order-detail-head order-detail-head-card">
  <div>
    <div class="eyebrow">ORDER <?= e($order['order_number']) ?></div>
    <h1>Delivery status</h1>
    <p>Placed <?= e($when($order['created_at'])) ?></p>
  </div>
  <b class="status big <?= e(strtolower(str_replace(' ', '-', $status))) ?>"><?= e($status) ?></b>
</section>

<?php if (!$cancelled): ?>
<section class="order-stage-card" aria-label="Order progress">
  <div class="stage-track" aria-hidden="true"><span class="stage-track-fill" style="width:<?= $progress ?>;--stage-progress:<?= $progress ?>"></span></div>
  <div class="stage-list">
    <?php foreach ($stages as $i => [$label, $description]):
        $complete = $i <= $currentStage;
        $class = ($complete ? 'is-complete' : 'is-upcoming') . ($i === $currentStage ? ' is-current' : '');
    ?>
      <div class="stage <?= $class ?>">
        <div class="stage-marker">
          <?php if ($complete): ?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6.5 12.5 3.3 3.3 7.7-8"></path></svg>
          <?php else: ?>
            <span><?= $i + 1 ?></span>
          <?php endif; ?>
        </div>
        <div class="stage-copy"><strong><?= e($label) ?></strong><span><?= e($description) ?></span></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<div class="detail-grid">
  <section class="detail-card">
    <h3>Items</h3>
    <?php foreach ($items as $it): ?>
      <div class="detail-item">
        <div><strong><?= e($it['name']) ?> (<?= e(package_label($it)) ?>)</strong><small><?= qty($it['quantity']) ?> × TZS <?= money($it['unit_price']) ?></small></div>
        <b>TZS <?= money($it['total']) ?></b>
      </div>
    <?php endforeach; ?>
    <div class="detail-total"><span>Total</span><strong>TZS <?= money($order['total_amount']) ?></strong></div>
  </section>

  <section class="detail-card">
    <h3>Delivery</h3>
    <p><strong>Address</strong><br><?= nl2br(e($order['delivery_address'])) ?></p>
    <p><strong>Phone</strong><br><?= e($order['phone']) ?></p>
    <p><strong>Payment</strong><br><?= e($order['payment_method']) ?></p>
    <?php if (!empty($order['delivery_person'])): ?><p><strong>Delivery by</strong><br><?= e($order['delivery_person']) ?></p><?php endif; ?>
    <?php if (!empty($order['admin_note'])): ?><div class="admin-note"><strong>Message from MSINDA</strong><p><?= nl2br(e($order['admin_note'])) ?></p></div><?php endif; ?>
  </section>
</div>
