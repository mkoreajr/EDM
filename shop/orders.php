<?php
require_once __DIR__ . '/auth.php'; portal_require_login(); $pageTitle='My Orders'; $active='orders'; $cid=portal_customer_id();
$rs=$conn->query("SELECT * FROM orders WHERE customer_id=$cid ORDER BY id DESC");
?>
<?php require __DIR__.'/partials/header.php'; ?>
<section class="section-head"><div><div class="eyebrow">ORDER HISTORY</div><h1>My Orders</h1><p>Follow every order from pending to delivery.</p></div><a class="portal-btn" href="shop.php">+ New Order</a></section>
<?php if(!$rs->fetch_assoc()): ?><div class="empty-card"><h3>No orders yet</h3><p>Your online orders will appear here.</p></div><?php else: $rs=$conn->query("SELECT * FROM orders WHERE customer_id=$cid ORDER BY id DESC"); ?>
<div class="orders-table"><div class="orders-head"><span>Order</span><span>Date</span><span>Total</span><span>Status</span><span></span></div><?php while($o=$rs->fetch_assoc()): ?><a class="order-row" href="order.php?id=<?=$o['id']?>"><strong><?=pe($o['order_number'])?></strong><span><?=pe(date('d M Y, H:i',strtotime($o['created_at'])))?></span><span>TZS <?=pmoney($o['total_amount'])?></span><span><b class="status <?=pe(portal_status_class($o['status']))?>"><?=pe($o['status'])?></b></span><span>View →</span></a><?php endwhile; ?></div><?php endif; ?>
<?php require __DIR__.'/partials/footer.php'; ?>
