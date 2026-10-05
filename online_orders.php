<?php
require 'auth.php'; require_admin(); $pageTitle='Online Orders'; $active='online_orders'; $message=''; $error='';
$allowed=['Pending','Confirmed','Out for Delivery','Delivered','Cancelled'];
if(isset($_POST['update_order'])){
  $id=(int)($_POST['order_id']??0); $new=$_POST['status']??''; $driver=trim($_POST['delivery_person']??''); $note=trim($_POST['admin_note']??'');
  if($id<=0 || !in_array($new,$allowed,true)) $error='Invalid order update.';
  else{
    $conn->begin_transaction();
    try{
      $st=$conn->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');$st->bind_param('i',$id);$st->execute();$order=$st->get_result()->fetch_assoc();if(!$order)throw new Exception('Order not found.');
      $current=$order['status'];
      $valid=(($current==='Pending' && in_array($new,['Confirmed','Cancelled'],true)) || ($current==='Confirmed' && in_array($new,['Out for Delivery','Cancelled'],true)) || ($current==='Out for Delivery' && $new==='Delivered') || ($current===$new));
      if(!$valid)throw new Exception("Cannot change an order from $current to $new.");

      if($new==='Confirmed' && !$order['stock_reserved']){
        $items=$conn->query("SELECT * FROM order_items WHERE order_id=$id ORDER BY id");
        while($it=$items->fetch_assoc()){
          $ps=$conn->prepare('SELECT id,name,stock_quantity FROM products WHERE id=? FOR UPDATE');$ps->bind_param('i',$it['product_id']);$ps->execute();$p=$ps->get_result()->fetch_assoc();
          if(!$p)throw new Exception('A product in this order no longer exists.');
          if((float)$p['stock_quantity'] < (float)$it['quantity'])throw new Exception('Insufficient stock for '.$p['name'].'. Available: '.number_format((float)$p['stock_quantity'],0).'.');
          $up=$conn->prepare('UPDATE products SET stock_quantity=stock_quantity-? WHERE id=?');$up->bind_param('di',$it['quantity'],$it['product_id']);$up->execute();
          $mv=$conn->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,reference_id) VALUES(?,'Adjustment',?,?)");$qty=-1*(float)$it['quantity'];$mv->bind_param('idi',$it['product_id'],$qty,$id);$mv->execute();
        }
      }

      if($new==='Cancelled' && $order['stock_reserved'] && empty($order['sale_id'])){
        $items=$conn->query("SELECT * FROM order_items WHERE order_id=$id ORDER BY id");
        while($it=$items->fetch_assoc()){
          $up=$conn->prepare('UPDATE products SET stock_quantity=stock_quantity+? WHERE id=?');$up->bind_param('di',$it['quantity'],$it['product_id']);$up->execute();
          $mv=$conn->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,reference_id) VALUES(?,'Adjustment',?,?)");$qty=(float)$it['quantity'];$mv->bind_param('idi',$it['product_id'],$qty,$id);$mv->execute();
        }
      }

      if($new==='Delivered' && empty($order['sale_id'])){
        if(!$order['stock_reserved'])throw new Exception('Confirm the order before marking it as delivered.');
        $paymentMap=['Cash on Delivery'=>'Cash','Mobile Money'=>'Mobile Money','Bank'=>'Bank'];$payment=$paymentMap[$order['payment_method']]??'Cash';
        $saleNo='SALE-'.date('YmdHis').'-'.random_int(100,999);
        $sale=$conn->prepare('INSERT INTO sales(sale_number,customer_id,sale_date,payment_method,total_amount,created_by) VALUES(?,?,CURRENT_DATE,?,?,?) RETURNING id');$sale->bind_param('sisdi',$saleNo,$order['customer_id'],$payment,$order['total_amount'],$_SESSION['user_id']);$sale->execute();$saleRow=$sale->get_result()->fetch_assoc();$saleId=(int)$saleRow['id'];
        $items=$conn->query("SELECT * FROM order_items WHERE order_id=$id ORDER BY id");$si=$conn->prepare('INSERT INTO sale_items(sale_id,product_id,quantity,unit_price,total) VALUES(?,?,?,?,?)');$mv=$conn->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,reference_id) VALUES(?,'Sale',?,?)");
        while($it=$items->fetch_assoc()){$si->bind_param('iiddd',$saleId,$it['product_id'],$it['quantity'],$it['unit_price'],$it['total']);$si->execute();$mv->bind_param('idi',$it['product_id'],$it['quantity'],$saleId);$mv->execute();}
        $up=$conn->prepare('UPDATE orders SET sale_id=?,delivered_at=CURRENT_TIMESTAMP WHERE id=?');$up->bind_param('ii',$saleId,$id);$up->execute();
      }
      $reserved=($new==='Confirmed' || ($new!=='Cancelled' && !empty($order['stock_reserved']))) ? 1 : 0;
      if($new==='Cancelled')$reserved=0;
      $up=$conn->prepare('UPDATE orders SET status=?,delivery_person=?,admin_note=?,stock_reserved=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');$up->bind_param('sssii',$new,$driver,$note,$reserved,$id);$up->execute();
      $conn->commit();$message="Order {$order['order_number']} updated to $new.";
    }catch(Throwable $e){try{$conn->rollback();}catch(Throwable $ignore){}$error=$e->getMessage();}
  }
}
$status=$_GET['status']??'All'; if(!in_array($status,array_merge(['All'],$allowed),true))$status='All';
$where=$status==='All'?'':'WHERE o.status='.$pdo->quote($status);
$orders=[];$rs=$conn->query("SELECT o.*,c.name customer_name,c.phone customer_phone FROM orders o JOIN customers c ON c.id=o.customer_id $where ORDER BY o.id DESC");while($r=$rs->fetch_assoc())$orders[]=$r;
$pending=(int)$conn->query("SELECT COUNT(*) c FROM orders WHERE status='Pending'")->fetch_assoc()['c'];
require 'partials/header.php';
?>
<div class="orders-admin-page"><div class="settings-heading"><div><div class="welcome-kicker">CUSTOMER ORDERS</div><h1>Online Orders</h1><p>Receive customer orders from the portal, confirm stock and manage delivery.</p></div><div class="order-count-card"><strong><?=$pending?></strong><small>Pending</small></div></div>
<?php if($message): ?><div class="alert success"><?=e($message)?></div><?php endif;?><?php if($error): ?><div class="alert danger"><?=e($error)?></div><?php endif;?>
<div class="order-filter"><a class="<?= $status==='All'?'active':''?>" href="online_orders.php">All</a><?php foreach($allowed as $s): ?><a class="<?= $status===$s?'active':''?>" href="?status=<?=urlencode($s)?>"><?=e($s)?></a><?php endforeach;?></div>
<?php if(!$orders): ?><div class="panel empty-order-admin"><h3>No orders found</h3><p>Customer orders will appear here when they are placed.</p></div><?php else: ?>
<div class="admin-order-list"><?php foreach($orders as $o): $items=$conn->query("SELECT oi.*,p.name FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=".(int)$o['id']); $itemNames=[];while($it=$items->fetch_assoc())$itemNames[]=$it['name'].' × '.number_format((float)$it['quantity'],0); ?>
<article class="admin-order-card"><div class="admin-order-top"><div><span class="order-number"><?=e($o['order_number'])?></span><h3><?=e($o['customer_name'])?></h3><p><?=e($o['customer_phone'])?> • <?=e($o['delivery_address'])?></p></div><div><b class="status <?=e(strtolower(str_replace(' ','-', $o['status'])))?>"><?=e($o['status'])?></b><small><?=e(date('d M Y, H:i',strtotime($o['created_at'])))?></small></div></div><div class="admin-order-body"><div><strong>Items</strong><p><?=e(implode(', ',$itemNames))?></p></div><div><strong>Total</strong><p class="admin-total">TZS <?=money($o['total_amount'])?></p></div><div><strong>Payment</strong><p><?=e($o['payment_method'])?></p></div></div>
<?php if($o['notes']): ?><div class="customer-note"><strong>Customer note:</strong> <?=e($o['notes'])?></div><?php endif; ?>
<?php if($o['status']!=='Delivered' && $o['status']!=='Cancelled'): ?><form method="post" class="order-update-form"><input type="hidden" name="order_id" value="<?=$o['id']?>"><label>Delivery person<input name="delivery_person" value="<?=e($o['delivery_person']??'')?>" placeholder="Driver / rider name"></label><label>Admin note<input name="admin_note" value="<?=e($o['admin_note']??'')?>" placeholder="Message to customer"></label><label>Status<select name="status"><?php if($o['status']==='Pending'): ?><option>Confirmed</option><option>Cancelled</option><?php elseif($o['status']==='Confirmed'): ?><option>Out for Delivery</option><option>Cancelled</option><?php elseif($o['status']==='Out for Delivery'): ?><option>Delivered</option><?php endif; ?></select></label><button class="btn primary" name="update_order">Update Order</button></form><?php else: ?><div class="order-final-note"><?= $o['status']==='Delivered' ? '✓ Delivered and recorded as a sale.' : 'Order cancelled.' ?><?php if($o['admin_note']): ?><span><?=e($o['admin_note'])?></span><?php endif; ?></div><?php endif; ?>
</article><?php endforeach; ?></div><?php endif; ?></div>
<style>
.orders-admin-page{max-width:1180px}.settings-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}.settings-heading h1{margin:4px 0 6px}.settings-heading p{margin:0;color:#72847d}.order-count-card{min-width:100px;padding:14px 18px;border-radius:14px;background:#e9f7ef;text-align:center;color:#146d4c}.order-count-card strong{display:block;font-size:25px}.order-count-card small{font-size:11px;font-weight:800}.order-filter{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}.order-filter a{padding:8px 12px;border-radius:8px;background:#fff;border:1px solid #dce7e2;color:#60736b;font-size:12px;font-weight:800}.order-filter a.active{background:#159b62;color:#fff;border-color:#159b62}.admin-order-list{display:grid;gap:16px}.admin-order-card{background:#fff;border:1px solid #dce7e2;border-radius:15px;padding:20px;box-shadow:0 8px 22px rgba(31,74,58,.04)}.admin-order-top{display:flex;justify-content:space-between;gap:18px}.admin-order-top h3{margin:5px 0}.admin-order-top p{margin:0;color:#73847d;font-size:12px}.admin-order-top>div:last-child{display:flex;flex-direction:column;align-items:flex-end;gap:7px}.admin-order-top small{color:#82918b}.order-number{font-size:11px;font-weight:900;letter-spacing:.5px;color:#148155}.admin-order-body{display:grid;grid-template-columns:2fr 1fr 1fr;gap:15px;border-top:1px solid #edf1ef;border-bottom:1px solid #edf1ef;margin:16px 0;padding:15px 0}.admin-order-body strong{font-size:11px;text-transform:uppercase;color:#7a8b84}.admin-order-body p{margin:5px 0 0;font-size:13px;color:#445f54}.admin-total{font-weight:900;color:#16714f!important}.customer-note{background:#fff9df;border:1px solid #f0e4ac;border-radius:9px;padding:10px 12px;font-size:12px;color:#695c2e;margin-bottom:14px}.order-update-form{display:grid;grid-template-columns:1fr 1fr 180px auto;gap:10px;align-items:end}.order-update-form label{font-size:11px;font-weight:800;color:#5b7067}.order-update-form input,.order-update-form select{display:block;width:100%;margin-top:6px;height:38px;border:1px solid #d6e1dd;border-radius:7px;padding:0 10px;background:#fff}.order-update-form .btn{height:38px;white-space:nowrap}.order-final-note{background:#f5f9f7;border-radius:9px;padding:11px 13px;color:#4e6a5e;font-size:12px;font-weight:800}.order-final-note span{display:block;margin-top:4px;font-weight:500}.empty-order-admin{text-align:center;padding:50px}.empty-order-admin p{color:#75867f}
@media(max-width:900px){.order-update-form{grid-template-columns:1fr 1fr}.order-update-form .btn{grid-column:1/-1}.admin-order-body{grid-template-columns:1fr 1fr}.admin-order-body>div:first-child{grid-column:1/-1}}
@media(max-width:620px){.settings-heading,.admin-order-top{align-items:flex-start;flex-direction:column}.admin-order-top>div:last-child{align-items:flex-start}.admin-order-body{grid-template-columns:1fr}.admin-order-body>div:first-child{grid-column:auto}.order-update-form{grid-template-columns:1fr}}
</style>
<?php require 'partials/footer.php'; ?>
