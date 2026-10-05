<?php
require_once __DIR__ . '/auth.php'; $pageTitle='Cart'; $active='cart';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $action=$_POST['action']??''; $pid=(int)($_POST['product_id']??0); $qty=max(0,(int)($_POST['quantity']??0));
  $cart=portal_cart();
  if($action==='add' && $pid>0){ $st=$conn->prepare('SELECT stock_quantity FROM products WHERE id=?'); $st->bind_param('i',$pid); $st->execute(); $p=$st->get_result()->fetch_assoc(); if($p){$cart[$pid]=min((int)$p['stock_quantity'],(int)($cart[$pid]??0)+max(1,$qty));} }
  elseif($action==='update' && $pid>0){$st=$conn->prepare('SELECT stock_quantity FROM products WHERE id=?');$st->bind_param('i',$pid);$st->execute();$p=$st->get_result()->fetch_assoc(); if($p && $qty>0)$cart[$pid]=min((int)$p['stock_quantity'],$qty); else unset($cart[$pid]);}
  elseif($action==='remove' && $pid>0) unset($cart[$pid]);
  $_SESSION['portal_cart']=$cart; header('Location: cart.php'); exit;
}
$cart=portal_cart(); $items=[]; $total=0;
if($cart){$ids=array_values(array_filter(array_map('intval',array_keys($cart)))); if($ids){$rs=$conn->query('SELECT * FROM products WHERE id IN ('.implode(',',$ids).')'); while($p=$rs->fetch_assoc()){$q=(int)($cart[$p['id']]??0); if($q<=0)continue; $line=$q*(float)$p['selling_price'];$p['cart_qty']=$q;$p['line_total']=$line;$items[]=$p;$total+=$line;}}}
?>
<?php require __DIR__.'/partials/header.php'; ?>
<section class="section-head section-head-card"><div><div class="eyebrow">YOUR ORDER</div><h1>Shopping Cart</h1><p>Review your products before sending the order to MSINDA Food Shop.</p></div></section>
<?php if(!$items): ?><div class="empty-card"><h3>Your cart is empty</h3><p>Choose products from the shop to get started.</p><a class="portal-btn" href="shop.php">Browse Products</a></div><?php else: ?>
<div class="cart-layout"><div class="cart-list">
<?php foreach($items as $p): $egg=$p['category']==='Eggs'; ?><div class="cart-row"><div class="product-icon small <?=strtolower($p['category'])?>"><?= $egg?'🥚':($p['category']==='Rice'?'🍚':'🌾') ?></div><div class="cart-info"><strong><?=pe($p['name'])?></strong><small><?=pe($egg?'Tray':number_format((float)$p['package_size_kg'],0).' Kg Bag')?> • TZS <?=pmoney($p['selling_price'])?></small></div><form method="post" class="qty-form"><input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?=$p['id']?>"><input type="number" name="quantity" value="<?=$p['cart_qty']?>" min="1" max="<?=max(1,(int)$p['stock_quantity'])?>"><button>Manage</button></form><strong class="line-total">TZS <?=pmoney($p['line_total'])?></strong><form method="post"><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?=$p['id']?>"><button class="remove-link">Remove</button></form></div><?php endforeach; ?>
</div><aside class="cart-summary"><h3>Order Summary</h3><div><span>Items</span><strong><?=portal_cart_count()?></strong></div><div class="summary-total"><span>Total</span><strong>TZS <?=pmoney($total)?></strong></div><a class="portal-btn full" href="checkout.php">Proceed to Checkout</a><a class="back-link" href="shop.php">← Continue shopping</a></aside></div>
<?php endif; ?><?php require __DIR__.'/partials/footer.php'; ?>
