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
$status=$_GET['status']??'All'; 
if(!in_array($status,array_merge(['All'],$allowed),true))$status='All';
$q=trim($_GET['q']??'');
$from=trim($_GET['from']??'');
$to=trim($_GET['to']??'');

$whereParts=[];
if($status!=='All') $whereParts[]="o.status='".$conn->real_escape_string($status)."'";
if($q!==''){
  $qq=$conn->real_escape_string($q);
  $whereParts[]="(o.order_number LIKE '%$qq%' OR c.name LIKE '%$qq%' OR c.phone LIKE '%$qq%' OR o.delivery_address LIKE '%$qq%')";
}
if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)) $whereParts[]="DATE(o.created_at)>='".$conn->real_escape_string($from)."'";
if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)) $whereParts[]="DATE(o.created_at)<='".$conn->real_escape_string($to)."'";
$where=$whereParts?'WHERE '.implode(' AND ',$whereParts):'';

$orders=[];
$rs=$conn->query("SELECT o.*,c.name customer_name,c.phone customer_phone FROM orders o JOIN customers c ON c.id=o.customer_id $where ORDER BY o.id DESC");
while($rs && ($r=$rs->fetch_assoc()))$orders[]=$r;

$statusCounts=[];
foreach($allowed as $sName){
  $r=$conn->query("SELECT COUNT(*) c FROM orders WHERE status='".$conn->real_escape_string($sName)."'")->fetch_assoc();
  $statusCounts[$sName]=(int)($r['c']??0);
}
$allCount=(int)$conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];

function onlineOrderUrl($params=[]){
  $base='online_orders.php';
  $clean=[];
  foreach($params as $k=>$v) if($v!=='' && $v!==null) $clean[$k]=$v;
  return $base.($clean?'?'.http_build_query($clean):'');
}
require 'partials/header.php';
?>
<div class="online-orders-page">
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
        <div class="order-summary-card pending"><span class="summary-icon">◷</span><div><strong><?=number_format($statusCounts['Pending'])?></strong><small>Pending</small></div><i></i></div>
        <div class="order-summary-card confirmed"><span class="summary-icon">✓</span><div><strong><?=number_format($statusCounts['Confirmed'])?></strong><small>Confirmed</small></div><i></i></div>
        <div class="order-summary-card delivery"><span class="summary-icon">▣</span><div><strong><?=number_format($statusCounts['Out for Delivery'])?></strong><small>Out for Delivery</small></div><i></i></div>
        <div class="order-summary-card delivered"><span class="summary-icon">✓</span><div><strong><?=number_format($statusCounts['Delivered'])?></strong><small>Delivered</small></div><i></i></div>
      </div>
    </div>
  </section>

  <?php if($message): ?><div class="alert success"><?=e($message)?></div><?php endif;?>
  <?php if($error): ?><div class="alert danger"><?=e($error)?></div><?php endif;?>

  <section class="online-orders-toolbar">
    <div class="order-filter-pills">
      <?php
        $filters=[
          'All'=>['All Orders',$allCount],
          'Pending'=>['Pending',$statusCounts['Pending']],
          'Confirmed'=>['Confirmed',$statusCounts['Confirmed']],
          'Out for Delivery'=>['Out for Delivery',$statusCounts['Out for Delivery']],
          'Delivered'=>['Delivered',$statusCounts['Delivered']],
          'Cancelled'=>['Cancelled',$statusCounts['Cancelled']]
        ];
        foreach($filters as $key=>$meta):
          $params=['status'=>$key==='All'?'':$key,'q'=>$q,'from'=>$from,'to'=>$to];
      ?>
        <?php $pillIcons=['All'=>'▦','Pending'=>'◷','Confirmed'=>'✓','Out for Delivery'=>'▣','Delivered'=>'✓','Cancelled'=>'×']; ?>
        <a class="order-filter-pill <?=$status===$key?'active':''?>" href="<?=e(onlineOrderUrl($params))?>">
          <span class="pill-symbol"><?=e($pillIcons[$key]??'•')?></span>
          <?=e($meta[0])?><b><?=number_format($meta[1])?></b>
        </a>
      <?php endforeach;?>
    </div>
    <form class="orders-search-form" method="get">
      <?php if($status!=='All'): ?><input type="hidden" name="status" value="<?=e($status)?>"><?php endif;?>
      <label class="orders-search">
        <span>⌕</span>
        <input type="search" name="q" value="<?=e($q)?>" placeholder="Search orders, customer name, phone..." aria-label="Search orders">
      </label>
      <label class="orders-date">
        <span>▣</span>
        <input type="date" name="from" value="<?=e($from)?>" aria-label="From date">
      </label>
      <span class="date-dash">–</span>
      <label class="orders-date">
        <input type="date" name="to" value="<?=e($to)?>" aria-label="To date">
      </label>
      <button class="orders-search-btn" type="submit">Search</button>
    </form>
  </section>

  <?php if(!$orders): ?>
    <section class="online-orders-empty panel">
      <div class="empty-cart-art">
        <div class="empty-cart-glow"></div>
        <div class="empty-cart">
          <svg viewBox="0 0 100 100" aria-hidden="true">
            <path d="M24 29h8l8 37h35c5 0 9-3 11-8l6-21H38" fill="none" stroke="currentColor" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="48" cy="79" r="6" fill="currentColor"/><circle cx="78" cy="79" r="6" fill="currentColor"/>
          </svg>
        </div>
        <span class="spark spark-a"></span><span class="spark spark-b"></span><span class="spark spark-c"></span>
      </div>
      <h2>No orders found</h2>
      <p>Customer orders from the portal will appear here when they are placed.</p>
      <div class="empty-info"><span>i</span> When customers place orders through the customer portal, they will be displayed here for you to confirm, manage and deliver.</div>
    </section>
  <?php else: ?>
    <section class="online-orders-table-card">
      <div class="orders-table-scroll">
        <table class="online-orders-table">
          <thead><tr><th>#</th><th>Order Details</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Order Date</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($orders as $o):
            $items=$conn->query("SELECT oi.*,p.name FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=".(int)$o['id']." ORDER BY oi.id");
            $itemNames=[];$itemCount=0;
            while($it=$items->fetch_assoc()){ $itemNames[]=$it['name'].' × '.number_format((float)$it['quantity'],0); $itemCount+=(float)$it['quantity']; }
            $statusClass=strtolower(str_replace(' ','-', $o['status']));
          ?>
            <tr>
              <td><span class="order-number-chip"><?=e($o['order_number'])?></span></td>
              <td><div class="order-detail-name"><?=e($itemNames[0]??'Order items')?></div><small><?=count($itemNames)>1?'+ '.(count($itemNames)-1).' more item(s)':'Qty: '.number_format($itemCount,0)?></small></td>
              <td><div class="customer-cell"><span class="customer-avatar">♙</span><div><strong><?=e($o['customer_name'])?></strong><small>☎ <?=e($o['customer_phone'])?></small></div></div></td>
              <td><strong><?=number_format($itemCount,0)?> item<?=($itemCount==1?'':'s')?></strong><small><?=e(implode(', ',$itemNames))?></small></td>
              <td><strong class="table-total">TZS <?=money($o['total_amount'])?></strong></td>
              <td><span class="payment-chip"><?=e($o['payment_method'])?></span></td>
              <td><span class="table-status <?=$statusClass?>"><?=e($o['status'])?></span></td>
              <td><div class="date-cell">▣ <?=e(date('d M Y',strtotime($o['created_at'])))?><small>◷ <?=e(date('h:i A',strtotime($o['created_at'])))?></small></div></td>
              <td>
                <div class="table-actions">
                  <button type="button" class="table-action primary" onclick="document.getElementById('order-update-<?=$o['id']?>').scrollIntoView({behavior:'smooth',block:'center'});">View</button>
                  <?php if($o['status']!=='Delivered' && $o['status']!=='Cancelled'): ?>
                    <button type="button" class="table-action secondary" onclick="document.getElementById('order-update-<?=$o['id']?>').scrollIntoView({behavior:'smooth',block:'center'});">Update</button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <tr class="order-update-row"><td colspan="9">
              <form method="post" class="order-update-form" id="order-update-<?=$o['id']?>">
                <input type="hidden" name="order_id" value="<?=$o['id']?>">
                <label>Delivery person<input name="delivery_person" value="<?=e($o['delivery_person']??'')?>" placeholder="Driver / rider name"></label>
                <label>Admin note<input name="admin_note" value="<?=e($o['admin_note']??'')?>" placeholder="Message to customer"></label>
                <label>Status<select name="status">
                  <?php if($o['status']==='Pending'): ?><option>Confirmed</option><option>Cancelled</option>
                  <?php elseif($o['status']==='Confirmed'): ?><option>Out for Delivery</option><option>Cancelled</option>
                  <?php elseif($o['status']==='Out for Delivery'): ?><option>Delivered</option><?php endif; ?>
                </select></label>
                <?php if($o['status']!=='Delivered' && $o['status']!=='Cancelled'): ?><button class="btn primary" name="update_order">Update Order</button><?php else: ?><div class="order-final-note"><?= $o['status']==='Delivered' ? '✓ Delivered and recorded as a sale.' : 'Order cancelled.' ?></div><?php endif; ?>
              </form>
            </td></tr>
          <?php endforeach;?>
          </tbody>
        </table>
      </div>
      <div class="orders-table-footer"><span>Showing 1 to <?=count($orders)?> of <?=count($orders)?> orders</span><div><button disabled>‹ Previous</button><b>1</b><button disabled>Next ›</button></div></div>
    </section>
  <?php endif; ?>
</div>
<style>
.online-orders-page{max-width:1440px;margin:0 auto;padding:4px 0 28px}
.online-orders-hero{padding:4px 0 18px}
.online-orders-heading{display:flex;justify-content:space-between;gap:28px;align-items:flex-start}
.online-orders-title-wrap{min-width:360px}
.online-orders-kicker{font-size:14px;font-weight:900;letter-spacing:2.8px;color:#0b9a67;margin:3px 0 7px}
.online-orders-title-row{display:flex;align-items:center;gap:17px}
.online-orders-title-row h1{font-size:35px;line-height:1.05;letter-spacing:-1.2px;color:#102b55;margin:0 0 8px;font-weight:900}
.online-orders-title-row p{margin:0;color:#687d91;font-size:14px;line-height:1.5}
.online-orders-cart-icon{width:58px;height:58px;flex:0 0 58px;border-radius:50%;background:linear-gradient(145deg,#12b878,#07915f);color:#fff;display:grid;place-items:center;box-shadow:0 10px 24px rgba(4,145,94,.18)}
.online-orders-cart-icon svg{width:31px;height:31px}
.online-orders-summary{display:grid;grid-template-columns:repeat(4,minmax(125px,1fr));gap:13px;min-width:600px}
.order-summary-card{position:relative;min-height:92px;border-radius:15px;padding:17px 18px;display:flex;align-items:center;gap:12px;overflow:hidden;border:1px solid transparent}
.order-summary-card.pending{background:linear-gradient(135deg,#effbf5,#e9f7ef);border-color:#e3f1e9}
.order-summary-card.confirmed{background:linear-gradient(135deg,#eef7ff,#e8f3ff);border-color:#e2edf8}
.order-summary-card.delivery{background:linear-gradient(135deg,#fff9ed,#fff2dc);border-color:#faecd4}
.order-summary-card.delivered{background:linear-gradient(135deg,#fff3f3,#ffebeb);border-color:#f8dddd}
.summary-icon{width:48px;height:48px;border-radius:50%;background:#fff;display:grid;place-items:center;font-size:25px;font-weight:900;box-shadow:0 4px 12px rgba(26,60,46,.06)}
.pending .summary-icon{color:#079b65}.confirmed .summary-icon{color:#1686e5}.delivery .summary-icon{color:#f3a11d}.delivered .summary-icon{color:#e52f35}
.order-summary-card strong{display:block;font-size:25px;line-height:1;color:#102b55}.order-summary-card small{display:block;margin-top:6px;color:#607386;font-size:12px;font-weight:800;white-space:nowrap}
.order-summary-card i{position:absolute;left:17px;bottom:10px;width:60px;height:3px;border-radius:5px}.pending i{background:#0ba76b}.confirmed i{background:#168cf0}.delivery i{background:#f5a21a}.delivered i{background:#e53239}
.online-orders-page .alert{margin:0 0 16px}
.online-orders-toolbar{background:#fff;border:1px solid #e4ece8;border-radius:15px;padding:14px 15px;box-shadow:0 5px 18px rgba(34,74,57,.035);display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:17px}
.order-filter-pills{display:flex;gap:8px;flex-wrap:wrap}
.order-filter-pill{height:48px;padding:0 12px;border:1px solid #dfe7e4;border-radius:10px;background:#fff;color:#42576c;text-decoration:none;display:flex;align-items:center;gap:8px;font-size:13px;font-weight:800;white-space:nowrap}
.order-filter-pill:hover{border-color:#b8d9ca;background:#f8fcfa}.order-filter-pill.active{background:#12a66a;color:#fff;border-color:#12a66a;box-shadow:0 6px 14px rgba(18,166,106,.15)}
.order-filter-pill b{min-width:24px;height:24px;padding:0 7px;border-radius:50%;display:grid;place-items:center;background:#eaf3ef;color:#167c59;font-size:12px}.order-filter-pill.active b{background:#dff5eb;color:#0a875a}
.pill-symbol{font-size:17px;line-height:1}
.orders-search-form{display:flex;align-items:center;gap:8px;flex:0 0 auto}
.orders-search,.orders-date{height:44px;border:1px solid #dfe7e4;border-radius:10px;background:#fff;display:flex;align-items:center}
.orders-search{width:310px;padding:0 13px;gap:8px}.orders-search span{font-size:23px;color:#82919c}.orders-search input{border:0;outline:0;width:100%;font-size:12px;color:#31475d;background:transparent}
.orders-date{width:145px;padding:0 10px;gap:7px}.orders-date span{color:#718394}.orders-date input{border:0;outline:0;width:100%;font-size:12px;color:#4d6275;background:transparent}
.date-dash{color:#9aa7b0}.orders-search-btn{height:44px;border:0;border-radius:10px;background:#0fa46a;color:#fff;padding:0 15px;font-weight:800;cursor:pointer}
.online-orders-empty{min-height:500px;background:#fff;border:1px solid #e0e9e5;border-radius:17px;box-shadow:0 7px 24px rgba(32,76,58,.045);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:44px 28px}
.empty-cart-art{position:relative;width:190px;height:180px;margin-bottom:3px}
.empty-cart-glow{position:absolute;left:50%;top:17px;transform:translateX(-50%);width:132px;height:132px;border-radius:50%;background:radial-gradient(circle,#d9f6e9 0,#eafaf4 54%,transparent 55%)}
.empty-cart{position:absolute;left:50%;top:37px;transform:translateX(-50%);width:90px;height:90px;border-radius:50%;background:linear-gradient(145deg,#c9f2e3,#a7e7d0);display:grid;place-items:center;color:#10a66b;box-shadow:0 10px 30px rgba(14,154,103,.08)}
.empty-cart svg{width:68px;height:68px}.spark{position:absolute;height:5px;border-radius:6px;background:#18b579}.spark-a{width:24px;left:23px;top:52px;transform:rotate(-45deg)}.spark-b{width:17px;right:28px;top:45px;transform:rotate(45deg)}.spark-c{width:11px;left:17px;top:77px;transform:rotate(0)}
.online-orders-empty h2{margin:0 0 8px;color:#102b55;font-size:27px;letter-spacing:-.4px}.online-orders-empty>p{margin:0;color:#6c7d8e;font-size:15px}
.empty-info{margin-top:28px;max-width:970px;width:100%;padding:13px 18px;border:1px solid #bfe9d8;border-radius:10px;background:#f3fcf8;color:#526d62;font-size:13px;display:flex;align-items:center;justify-content:center;gap:10px}
.empty-info span{width:23px;height:23px;border-radius:50%;background:#0eaa6c;color:#fff;display:grid;place-items:center;font-weight:900;font-size:13px;flex:0 0 23px}
.online-orders-table-card{background:#fff;border:1px solid #e1e9e5;border-radius:17px;box-shadow:0 7px 24px rgba(32,76,58,.045);overflow:hidden}
.orders-table-scroll{overflow:auto}.online-orders-table{width:100%;min-width:1180px;border-collapse:collapse}.online-orders-table th{padding:15px 13px;background:#f7f9f9;color:#5b6d7d;text-align:left;font-size:11px;text-transform:none;font-weight:900;white-space:nowrap}.online-orders-table td{padding:14px 13px;border-bottom:1px solid #edf1ef;color:#334b5f;font-size:12px;vertical-align:middle}.online-orders-table tbody tr:not(.order-update-row):hover{background:#fbfdfc}
.order-number-chip{display:inline-flex;padding:7px 9px;border-radius:8px;background:#eaf9f2;color:#079761;font-size:11px;font-weight:900}.order-detail-name{font-weight:800;color:#1b3551;font-size:13px}.online-orders-table td small{display:block;color:#7b8b98;margin-top:4px;font-size:11px}.customer-cell{display:flex;align-items:center;gap:8px}.customer-avatar{width:30px;height:30px;border:1px solid #d8e2de;border-radius:50%;display:grid;place-items:center;color:#5f7385;font-size:18px}.customer-cell strong{color:#203950}.table-total{color:#142f4e;font-size:13px;white-space:nowrap}.payment-chip,.table-status{display:inline-flex;align-items:center;padding:7px 9px;border-radius:8px;font-size:11px;font-weight:800;white-space:nowrap}.payment-chip{background:#eef8f4;color:#16835d;border:1px solid #cdeedf}.table-status.pending{background:#fff5df;color:#cf8610}.table-status.confirmed{background:#eaf5ff;color:#1779d4}.table-status.out-for-delivery{background:#fff0dc;color:#e79418}.table-status.delivered{background:#e5f8ef;color:#12905e}.table-status.cancelled{background:#fff0f0;color:#d3363d}.date-cell{color:#506477;white-space:nowrap}.date-cell small{margin-top:5px}.table-actions{display:flex;gap:7px}.table-action{height:36px;padding:0 12px;border-radius:8px;border:1px solid #dce6e2;font-weight:800;font-size:11px;cursor:pointer}.table-action.primary{background:#0da66b;border-color:#0da66b;color:#fff}.table-action.secondary{background:#fff;color:#405669}.order-update-row{background:#f8fbfa}.order-update-row td{padding:0 16px;border-bottom:1px solid #e4ece8}.order-update-form{display:grid;grid-template-columns:1fr 1fr 190px auto;gap:10px;align-items:end;padding:15px 0}.order-update-form label{font-size:11px;font-weight:800;color:#5b7067}.order-update-form input,.order-update-form select{display:block;width:100%;margin-top:6px;height:38px;border:1px solid #d6e1dd;border-radius:7px;padding:0 10px;background:#fff}.order-update-form .btn{height:38px;white-space:nowrap}.order-final-note{background:#eaf8f1;border-radius:8px;padding:10px 13px;color:#277457;font-size:12px;font-weight:800}.orders-table-footer{display:flex;justify-content:space-between;align-items:center;padding:15px 18px;color:#6e7f8e;font-size:12px}.orders-table-footer>div{display:flex;align-items:center;gap:6px}.orders-table-footer button,.orders-table-footer b{height:36px;min-width:36px;padding:0 10px;border:1px solid #e1e8e5;border-radius:8px;background:#fff;color:#8795a0}.orders-table-footer b{display:grid;place-items:center;background:#10a66b;color:#fff;border-color:#10a66b}
@media(max-width:1200px){.online-orders-heading{flex-direction:column}.online-orders-summary{width:100%;min-width:0}.online-orders-toolbar{flex-direction:column;align-items:stretch}.orders-search-form{width:100%}.orders-search{flex:1;width:auto}.orders-date{width:150px}}
@media(max-width:800px){.online-orders-summary{grid-template-columns:repeat(2,1fr)}.online-orders-title-wrap{min-width:0}.orders-search-form{flex-wrap:wrap}.orders-search{min-width:220px}.orders-date{flex:1}.empty-info{text-align:left;justify-content:flex-start}}
@media(max-width:560px){.online-orders-page{padding-top:0}.online-orders-title-row{align-items:flex-start}.online-orders-title-row h1{font-size:29px}.online-orders-cart-icon{width:50px;height:50px;flex-basis:50px}.online-orders-summary{grid-template-columns:1fr 1fr}.order-filter-pills{display:grid;grid-template-columns:1fr 1fr}.order-filter-pill{justify-content:center}.orders-search-form{display:grid;grid-template-columns:1fr 1fr}.orders-search{grid-column:1/-1;width:100%}.orders-date{width:100%}.date-dash{display:none}.orders-search-btn{grid-column:1/-1}.online-orders-empty{min-height:420px}.empty-info{font-size:12px}.online-orders-empty h2{font-size:24px}}
</style>
