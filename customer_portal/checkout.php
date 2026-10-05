<?php
require_once __DIR__ . '/auth.php'; portal_require_login(); $pageTitle='Checkout'; $active='cart'; $customer=portal_current_customer(); $cart=portal_cart();
$items=[];$total=0;
if($cart){$ids=array_values(array_filter(array_map('intval',array_keys($cart))));if($ids){$rs=$conn->query('SELECT * FROM products WHERE id IN ('.implode(',',$ids).')');while($p=$rs->fetch_assoc()){$q=(int)($cart[$p['id']]??0);if($q>0){$p['cart_qty']=$q;$p['line_total']=$q*(float)$p['selling_price'];$items[]=$p;$total+=$p['line_total'];}}}}
if(!$items){header('Location: cart.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $address=trim($_POST['address']??'');$phone=trim($_POST['phone']??'');$payment=$_POST['payment_method']??'Cash on Delivery';$notes=trim($_POST['notes']??'');
  if($address===''||$phone==='')$error='Delivery address and phone are required.';elseif(!in_array($payment,['Cash on Delivery','Mobile Money','Bank'],true))$error='Invalid payment method.';else{
    try{
      $conn->begin_transaction();$checked=[];$orderTotal=0;
      foreach($items as $it){$st=$conn->prepare('SELECT * FROM products WHERE id=? FOR UPDATE');$st->bind_param('i',$it['id']);$st->execute();$p=$st->get_result()->fetch_assoc();if(!$p||$it['cart_qty']>$p['stock_quantity'])throw new Exception('Stock changed for '.$it['name'].'. Please update your cart.');$line=$it['cart_qty']*(float)$p['selling_price'];$orderTotal+=$line;$checked[]=['id'=>(int)$it['id'],'qty'=>(int)$it['cart_qty'],'price'=>(float)$p['selling_price'],'total'=>$line];}
      $number='ORD-'.date('YmdHis').'-'.random_int(100,999);
      $st=$conn->prepare('INSERT INTO orders(order_number,customer_id,status,delivery_address,phone,payment_method,notes,total_amount) VALUES(?,?,\'Pending\',?,?,?,?,?) RETURNING id');$cid=(int)$customer['id'];$st->bind_param('sissssd',$number,$cid,$address,$phone,$payment,$notes,$orderTotal);$st->execute();$row=$st->get_result()->fetch_assoc();$orderId=(int)$row['id'];
      $oi=$conn->prepare('INSERT INTO order_items(order_id,product_id,quantity,unit_price,total) VALUES(?,?,?,?,?)');foreach($checked as $it){$oi->bind_param('iiddd',$orderId,$it['id'],$it['qty'],$it['price'],$it['total']);$oi->execute();}
      $admins=$conn->query("SELECT id FROM users WHERE LOWER(role)='admin'");$nt=$conn->prepare('INSERT INTO notifications(user_id,title,message) VALUES(?,?,?)');$title='New online order';$msg="Order $number from ".$customer['name']." has been placed. Total TZS ".pmoney($orderTotal).".";while($a=$admins->fetch_assoc()){$nt->bind_param('iss',$a['id'],$title,$msg);$nt->execute();}
      $conn->commit();$_SESSION['portal_cart']=[];header('Location: order.php?id='.$orderId.'&placed=1');exit;
    }catch(Throwable $e){try{$conn->rollback();}catch(Throwable $ignore){}$error=$e instanceof Exception?$e->getMessage():'Could not place the order. Please try again.';}
  }
}
?>
<?php require __DIR__.'/partials/header.php'; ?>
<section class="section-head"><div><div class="eyebrow">CHECKOUT</div><h1>Delivery Details</h1><p>Your order will appear immediately in the MSINDA admin portal for processing.</p></div></section>
<?php if($error): ?><div class="portal-alert error"><?=pe($error)?></div><?php endif; ?>
<div class="checkout-layout"><form method="post" class="checkout-card"><label>Delivery address<input name="address" value="<?=pe($_POST['address']??$customer['address'])?>" required></label><label>Phone number<input name="phone" value="<?=pe($_POST['phone']??$customer['phone'])?>" required></label><label>Payment method<select name="payment_method"><option <?=($_POST['payment_method']??'')==='Cash on Delivery'?'selected':''?>>Cash on Delivery</option><option <?=($_POST['payment_method']??'')==='Mobile Money'?'selected':''?>>Mobile Money</option><option <?=($_POST['payment_method']??'')==='Bank'?'selected':''?>>Bank</option></select></label><label>Order note (optional)<textarea name="notes" rows="4" placeholder="Any delivery instructions..."><?=pe($_POST['notes']??'')?></textarea></label><button class="portal-btn full" type="submit">Place Order — TZS <?=pmoney($total)?></button></form><aside class="cart-summary"><h3>Order Items</h3><?php foreach($items as $it): ?><div class="checkout-item"><span><?=pe($it['name'])?> × <?=$it['cart_qty']?></span><strong>TZS <?=pmoney($it['line_total'])?></strong></div><?php endforeach; ?><div class="summary-total"><span>Total</span><strong>TZS <?=pmoney($total)?></strong></div></aside></div>
<?php require __DIR__.'/partials/footer.php'; ?>
