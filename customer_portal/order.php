<?php
require_once __DIR__ . '/auth.php'; portal_require_login(); $pageTitle='Order Details'; $active='orders'; $id=(int)($_GET['id']??0);$cid=portal_customer_id();
$st=$conn->prepare('SELECT * FROM orders WHERE id=? AND customer_id=? LIMIT 1');$st->bind_param('ii',$id,$cid);$st->execute();$order=$st->get_result()->fetch_assoc();if(!$order){http_response_code(404);exit('Order not found.');}
$items=[];$rs=$conn->query("SELECT oi.*,p.name,p.category,p.unit,p.package_size_kg FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=$id ORDER BY oi.id");while($r=$rs->fetch_assoc())$items[]=$r;
$placed=!empty($_GET['placed']);
$status=$order['status'];
$stageIndex=['Pending'=>0,'Confirmed'=>1,'Out for Delivery'=>2,'Delivered'=>3];
$isCancelled = strcasecmp((string)$status, 'Cancelled') === 0;
$currentStage=$stageIndex[$status]??0;
$stages=[
  ['label'=>'Order Received','description'=>'We received your order.'],
  ['label'=>'Confirmed','description'=>'Stock and order are being prepared.'],
  ['label'=>'Out for Delivery','description'=>'Your order is on the way.'],
  ['label'=>'Delivered','description'=>'Order completed successfully.']
];
?>
<?php require __DIR__.'/partials/header.php'; ?>
<?php if($placed): ?><div class="portal-alert success">Order placed successfully. MSINDA Food Shop has received your order.</div><?php endif; ?>
<?php if($isCancelled): ?>
  <div class="portal-alert error cancellation-alert" role="alert">
    <strong>Order Cancelled</strong>
    <p>This order was cancelled by MSINDA.</p>
    <?php if(!empty($order['cancellation_reason'])): ?>
      <div class="cancellation-reason"><span>Cancellation reason</span><strong><?=nl2br(pe($order['cancellation_reason']))?></strong></div>
    <?php endif; ?>
    <?php if(!empty($order['cancelled_at'])): ?><small>Cancelled <?=pe(date('d M Y, H:i',strtotime($order['cancelled_at'])))?></small><?php endif; ?>
  </div>
<?php endif; ?>

<section class="order-detail-head">
  <div>
    <div class="eyebrow">ORDER <?=pe($order['order_number'])?></div>
    <h1>Delivery status</h1>
    <p>Placed <?=pe(date('d M Y, H:i',strtotime($order['created_at'])))?></p>
  </div>
  <b class="status big <?=pe(portal_status_class($status))?>"><?=pe($status)?></b>
</section>

<?php if(!$isCancelled): ?><section class="order-stage-card" aria-label="Order progress">
  <div class="stage-track" aria-hidden="true"><span class="stage-track-fill" style="width: <?=($currentStage/3)*100?>%"></span></div>
  <div class="stage-list">
    <?php foreach($stages as $i=>$stage):
      $complete=$i<=$currentStage;
      $current=$i===$currentStage;
      $stateClass=$complete?'is-complete':'is-upcoming';
      if($current)$stateClass.=' is-current';
    ?>
      <div class="stage <?= $stateClass ?>">
        <div class="stage-marker">
          <?php if($complete): ?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6.5 12.5 3.3 3.3 7.7-8"></path></svg>
          <?php else: ?>
            <span><?=($i+1)?></span>
          <?php endif; ?>
        </div>
        <div class="stage-copy">
          <strong><?=pe($stage['label'])?></strong>
          <span><?=pe($stage['description'])?></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section><?php endif; ?>

<div class="detail-grid">
  <section class="detail-card">
    <h3>Items</h3>
    <?php foreach($items as $it): ?>
      <div class="detail-item">
        <div><strong><?=pe($it['name'])?></strong><small><?=number_format((float)$it['quantity'],0)?> × TZS <?=pmoney($it['unit_price'])?></small></div>
        <b>TZS <?=pmoney($it['total'])?></b>
      </div>
    <?php endforeach; ?>
    <div class="detail-total"><span>Total</span><strong>TZS <?=pmoney($order['total_amount'])?></strong></div>
  </section>

  <section class="detail-card">
    <h3>Delivery</h3>
    <p><strong>Address</strong><br><?=nl2br(pe($order['delivery_address']))?></p>
    <p><strong>Phone</strong><br><?=pe($order['phone'])?></p>
    <p><strong>Payment</strong><br><?=pe($order['payment_method'])?></p>
    <?php if($order['delivery_person']): ?><p><strong>Delivery by</strong><br><?=pe($order['delivery_person'])?></p><?php endif; ?>
    <?php if($order['admin_note']): ?><div class="admin-note"><strong>Message from MSINDA</strong><p><?=nl2br(pe($order['admin_note']))?></p></div><?php endif; ?>
  </section>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>
