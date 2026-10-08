<?php
require 'auth.php'; require_admin(); $pageTitle='Online Orders'; $active='online_orders'; $message=''; $error='';
$allowed=['Pending','Confirmed','Out for Delivery','Delivered','Cancelled'];
$transitionMap=[
  'Pending'=>['Pending','Confirmed','Cancelled'],
  'Confirmed'=>['Confirmed','Out for Delivery','Cancelled'],
  'Out for Delivery'=>['Out for Delivery','Delivered','Cancelled'],
  'Delivered'=>['Delivered'],
  'Cancelled'=>['Cancelled']
];

/**
 * Apply an online-order status update atomically.
 * Status transitions are enforced server-side; inventory and sales changes
 * happen in the same transaction as the order update.
 */
function updateOnlineOrder($conn,$id,$new,$driver,$note,$allowed,$transitionMap){
  // Use the native PDO connection for this transaction. The page still uses the
  // compatibility layer for legacy reads, but status transitions are critical
  // writes and must use PostgreSQL/PDO directly so errors are deterministic.
  global $pdo;
  if(!$pdo instanceof PDO) throw new Exception('Database connection is unavailable.');
  if($id<=0 || !in_array($new,$allowed,true)) throw new Exception('Invalid order update.');

  $pdo->beginTransaction();
  try{
    $st=$pdo->prepare('SELECT * FROM orders WHERE id=:id FOR UPDATE');
    $st->execute([':id'=>$id]);
    $order=$st->fetch(PDO::FETCH_ASSOC);
    if(!$order) throw new Exception('Order not found.');

    $current=(string)$order['status'];
    if(!isset($transitionMap[$current]) || !in_array($new,$transitionMap[$current],true)){
      throw new Exception("Cannot change an order from $current to $new. Follow the order workflow: Pending → Confirmed → Out for Delivery → Delivered. Orders can be cancelled before delivery.");
    }

    // Reserve stock exactly once when the order becomes Confirmed.
    if($new==='Confirmed' && !(bool)$order['stock_reserved']){
      $items=$pdo->prepare('SELECT oi.product_id, oi.quantity, p.name, p.stock_quantity FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=:order_id ORDER BY oi.id FOR UPDATE OF p');
      $items->execute([':order_id'=>$id]);
      $rows=$items->fetchAll(PDO::FETCH_ASSOC);
      if(!$rows) throw new Exception('This order has no items to confirm.');

      $stock=$pdo->prepare('UPDATE products SET stock_quantity=stock_quantity-:qty_sub WHERE id=:product_id AND stock_quantity>=:qty_check');
      $movement=$pdo->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,reference_id) VALUES(:product_id,'Adjustment',:quantity,:reference_id)");
      foreach($rows as $it){
        $qty=(float)$it['quantity'];
        if($qty<=0) throw new Exception('Invalid quantity for '.$it['name'].'.');
        if((float)$it['stock_quantity'] < $qty){
          throw new Exception('Insufficient stock for '.$it['name'].'. Available: '.number_format((float)$it['stock_quantity'],0).'.');
        }
        $stock->execute([':qty_sub'=>$qty,':qty_check'=>$qty,':product_id'=>(int)$it['product_id']]);
        if($stock->rowCount()!==1) throw new Exception('Stock could not be reserved for '.$it['name'].'.');
        $movement->execute([':product_id'=>(int)$it['product_id'],':quantity'=>-$qty,':reference_id'=>$id]);
      }
    }

    // Cancel releases previously reserved stock, provided no sale has been created.
    if($new==='Cancelled' && $current!=='Cancelled' && (bool)$order['stock_reserved'] && empty($order['sale_id'])){
      $items=$pdo->prepare('SELECT product_id,quantity FROM order_items WHERE order_id=:order_id ORDER BY id');
      $items->execute([':order_id'=>$id]);
      $stock=$pdo->prepare('UPDATE products SET stock_quantity=stock_quantity+:qty WHERE id=:product_id');
      $movement=$pdo->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,reference_id) VALUES(:product_id,'Adjustment',:quantity,:reference_id)");
      while($it=$items->fetch(PDO::FETCH_ASSOC)){
        $qty=(float)$it['quantity'];
        $stock->execute([':qty'=>$qty,':product_id'=>(int)$it['product_id']]);
        $movement->execute([':product_id'=>(int)$it['product_id'],':quantity'=>$qty,':reference_id'=>$id]);
      }
    }

    // Delivered creates the sale once. Stock was already reserved at Confirmed,
    // so this stage records the sale without deducting inventory a second time.
    if($new==='Delivered' && $current!=='Delivered' && empty($order['sale_id'])){
      if(!(bool)$order['stock_reserved']) throw new Exception('Confirm the order before marking it as delivered.');
      $paymentMap=['Cash on Delivery'=>'Cash','Mobile Money'=>'Mobile Money','Bank'=>'Bank'];
      $payment=$paymentMap[$order['payment_method']]??'Cash';
      $saleNo='SALE-'.date('YmdHis').'-'.random_int(100,999);
      $sale=$pdo->prepare('INSERT INTO sales(sale_number,customer_id,sale_date,payment_method,total_amount,created_by) VALUES(:sale_number,:customer_id,CURRENT_DATE,:payment_method,:total_amount,:created_by) RETURNING id');
      $sale->execute([
        ':sale_number'=>$saleNo,
        ':customer_id'=>(int)$order['customer_id'],
        ':payment_method'=>$payment,
        ':total_amount'=>(float)$order['total_amount'],
        ':created_by'=>(int)($_SESSION['user_id']??0)
      ]);
      $saleId=(int)$sale->fetchColumn();
      if($saleId<=0) throw new Exception('Sale record could not be created.');

      $items=$pdo->prepare('SELECT product_id,quantity,unit_price,total FROM order_items WHERE order_id=:order_id ORDER BY id');
      $items->execute([':order_id'=>$id]);
      $saleItem=$pdo->prepare('INSERT INTO sale_items(sale_id,product_id,quantity,unit_price,total) VALUES(:sale_id,:product_id,:quantity,:unit_price,:total)');
      $movement=$pdo->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,reference_id) VALUES(:product_id,'Sale',:quantity,:reference_id)");
      while($it=$items->fetch(PDO::FETCH_ASSOC)){
        $saleItem->execute([
          ':sale_id'=>$saleId,
          ':product_id'=>(int)$it['product_id'],
          ':quantity'=>(float)$it['quantity'],
          ':unit_price'=>(float)$it['unit_price'],
          ':total'=>(float)$it['total']
        ]);
        $movement->execute([':product_id'=>(int)$it['product_id'],':quantity'=>(float)$it['quantity'],':reference_id'=>$saleId]);
      }
      $upSale=$pdo->prepare('UPDATE orders SET sale_id=:sale_id,delivered_at=CURRENT_TIMESTAMP WHERE id=:id');
      $upSale->execute([':sale_id'=>$saleId,':id'=>$id]);
    }

    $reserved=($new==='Confirmed' || ($new!=='Cancelled' && (bool)$order['stock_reserved'])) ? true : false;
    if($new==='Cancelled') $reserved=false;
    $up=$pdo->prepare('UPDATE orders SET status=:status,delivery_person=:delivery_person,admin_note=:admin_note,stock_reserved=CAST(:stock_reserved AS BOOLEAN),updated_at=CURRENT_TIMESTAMP WHERE id=:id');
    $up->execute([
      ':status'=>$new,
      ':delivery_person'=>$driver!==''?$driver:null,
      ':admin_note'=>$note!==''?$note:null,
      ':stock_reserved'=>$reserved ? 'true' : 'false',
      ':id'=>$id
    ]);
    if($up->rowCount()!==1) throw new Exception('The order status could not be saved.');

    $pdo->commit();
    return ['order_number'=>$order['order_number'],'status'=>$new,'previous_status'=>$current,'message'=>"Order {$order['order_number']} updated to $new."];
  }catch(Throwable $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
  }
}

if(isset($_POST['update_order'])){
  $id=(int)($_POST['order_id']??0);
  $new=trim((string)($_POST['status']??''));
  $driver=trim((string)($_POST['delivery_person']??''));
  $note=trim((string)($_POST['admin_note']??''));
  $isAjax=isset($_POST['ajax']) && $_POST['ajax']==='1';
  try{
    if($isAjax) ob_start();
    $result=updateOnlineOrder($conn,$id,$new,$driver,$note,$allowed,$transitionMap);
    if($isAjax){
      $noise=ob_get_clean();
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok'=>true]+$result); exit;
    }
    $message=$result['message'];
  }catch(Throwable $e){
    if($isAjax){
      if(ob_get_level()) ob_end_clean();
      http_response_code(422);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok'=>false,'message'=>$e->getMessage()]); exit;
    }
    $error=$e->getMessage();
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

// Use inline SVG icons so they render consistently even when icon fonts are unavailable.
$orderIcons=[
  'clock'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
  'check'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 12.5 4 4 8-9" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
  'delivery'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h11v10H3zM14 9h4l3 3v4h-7z" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/><circle cx="7" cy="18" r="1.7" fill="currentColor"/><circle cx="18" cy="18" r="1.7" fill="currentColor"/></svg>',
  'orders'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="4" width="14" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="1.9"/><path d="M8 8h8M8 12h8M8 16h5" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>',
  'cancel'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="m9 9 6 6m0-6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
];
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
        <div class="order-summary-card pending"><span class="summary-icon"><?=$orderIcons['clock']?></span><div><strong><?=number_format($statusCounts['Pending'])?></strong><small>Pending</small></div><i></i></div>
        <div class="order-summary-card confirmed"><span class="summary-icon"><?=$orderIcons['check']?></span><div><strong><?=number_format($statusCounts['Confirmed'])?></strong><small>Confirmed</small></div><i></i></div>
        <div class="order-summary-card delivery"><span class="summary-icon"><?=$orderIcons['delivery']?></span><div><strong><?=number_format($statusCounts['Out for Delivery'])?></strong><small>Out for Delivery</small></div><i></i></div>
        <div class="order-summary-card delivered"><span class="summary-icon"><?=$orderIcons['check']?></span><div><strong><?=number_format($statusCounts['Delivered'])?></strong><small>Delivered</small></div><i></i></div>
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
        <?php $pillIcons=['All'=>'orders','Pending'=>'clock','Confirmed'=>'check','Out for Delivery'=>'delivery','Delivered'=>'check','Cancelled'=>'cancel']; ?>
        <a class="order-filter-pill <?=$status===$key?'active':''?>" href="<?=e(onlineOrderUrl($params))?>">
          <span class="pill-symbol"><?=$orderIcons[$pillIcons[$key]]??''?></span>
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
            <tr class="order-row" id="order-row-<?=$o['id']?>" data-order-id="<?=$o['id']?>" data-status="<?=e($o['status'])?>">
              <td><span class="order-number-chip"><?=e($o['order_number'])?></span></td>
              <td><div class="order-detail-name"><?=e($itemNames[0]??'Order items')?></div><small><?=count($itemNames)>1?'+ '.(count($itemNames)-1).' more item(s)':'Qty: '.number_format($itemCount,0)?></small></td>
              <td><div class="customer-cell"><span class="customer-avatar">♙</span><div><strong><?=e($o['customer_name'])?></strong><small>☎ <?=e($o['customer_phone'])?></small></div></div></td>
              <td><strong><?=number_format($itemCount,0)?> item<?=($itemCount==1?'':'s')?></strong><small><?=e(implode(', ',$itemNames))?></small></td>
              <td><strong class="table-total">TZS <?=money($o['total_amount'])?></strong></td>
              <td><span class="payment-chip"><?=e($o['payment_method'])?></span></td>
              <td><span class="table-status <?=$statusClass?>" data-status-chip><?=e($o['status'])?></span></td>
              <td><div class="date-cell">▣ <?=e(date('d M Y',strtotime($o['created_at'])))?><small>◷ <?=e(date('h:i A',strtotime($o['created_at'])))?></small></div></td>
              <td>
                <div class="table-actions">
                  <button type="button" class="table-action primary" data-view-order="<?=$o['id']?>" onclick="toggleOrderDetails(<?=$o['id']?>)">View</button>
                  <?php if(!in_array($o['status'],['Delivered','Cancelled'],true)): ?>
                  <button type="button" class="table-action secondary" data-confirm-order="<?=$o['id']?>" onclick="toggleOrderConfirm(<?=$o['id']?>)">Confirm</button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php
              $detailItems=$conn->query("SELECT oi.*,p.name FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=".(int)$o['id']." ORDER BY oi.id");
            ?>
            <tr class="order-view-row" id="order-view-<?=$o['id']?>"><td colspan="9">
              <div class="order-view-panel">
                <div class="order-view-head">
                  <div><span class="order-view-kicker">ORDER DETAILS</span><strong><?=e($o['order_number'])?></strong></div>
                  <span class="order-view-status" data-view-status><?=e($o['status'])?></span>
                </div>
                <div class="order-view-grid">
                  <section class="order-view-card order-items-card">
                    <h3>Customer Items</h3>
                    <div class="order-items-table-wrap">
                      <table class="order-items-table">
                        <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Item Total</th></tr></thead>
                        <tbody>
                        <?php while($dit=$detailItems->fetch_assoc()): ?>
                          <tr><td><strong><?=e($dit['name'])?></strong></td><td><?=number_format((float)$dit['quantity'],0)?></td><td>TZS <?=money($dit['unit_price'])?></td><td><strong>TZS <?=money($dit['total'])?></strong></td></tr>
                        <?php endwhile; ?>
                        </tbody>
                        <tfoot><tr><th colspan="3">Order Total</th><th>TZS <?=money($o['total_amount'])?></th></tr></tfoot>
                      </table>
                    </div>
                  </section>
                  <section class="order-view-card">
                    <h3>Customer &amp; Delivery</h3>
                    <dl class="order-info-list">
                      <div><dt>Customer</dt><dd><?=e($o['customer_name'])?></dd></div>
                      <div><dt>Phone</dt><dd><?=e($o['customer_phone'])?></dd></div>
                      <div><dt>Delivery Address</dt><dd><?=e($o['delivery_address'])?></dd></div>
                      <div><dt>Order Date</dt><dd><?=e(date('d M Y, h:i A',strtotime($o['created_at'])))?></dd></div>
                    </dl>
                  </section>
                  <section class="order-view-card">
                    <h3>Payment &amp; Notes</h3>
                    <dl class="order-info-list">
                      <div><dt>Payment</dt><dd><?=e($o['payment_method'])?></dd></div>
                      <div><dt>Order Note</dt><dd><?=e($o['notes']?:'No customer note')?></dd></div>
                      <div><dt>Admin Note</dt><dd data-view-admin-note><?=e($o['admin_note']?:'No admin note')?></dd></div>
                      <?php if(!empty($o['cancellation_reason'])): ?><div><dt>Cancellation Reason</dt><dd data-view-cancellation-reason><?=e($o['cancellation_reason'])?></dd></div><?php endif; ?>
                      <?php if(!empty($o['cancelled_at'])): ?><div><dt>Cancelled At</dt><dd><?=e(date('d M Y, h:i A',strtotime($o['cancelled_at'])))?></dd></div><?php endif; ?>
                    </dl>
                  </section>
                </div>
              </div>
            </td></tr>
            <tr class="order-confirm-row" id="order-details-<?=$o['id']?>"><td colspan="9">
              <form method="post" class="order-update-form" id="order-update-<?=$o['id']?>" data-order-id="<?=$o['id']?>">
                <input type="hidden" name="order_id" value="<?=$o['id']?>">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="cancellation_reason" value="">
                <input type="hidden" name="cancellation_reason_custom" value="">
                <div class="order-editor-heading">
                  <div><span>Prepare &amp; Confirm</span><strong><?=e($o['order_number'])?></strong><small><?=e($o['customer_name'])?> · <?=e($o['customer_phone'])?> · Review items in View before confirming.</small></div>
                  <span class="order-editor-current" data-current-label>Current: <?=e($o['status'])?></span>
                </div>
                <label>Delivery person<input name="delivery_person" value="<?=e($o['delivery_person']??'')?>" placeholder="Driver / rider name"></label>
                <label>Admin note<input name="admin_note" value="<?=e($o['admin_note']??'')?>" placeholder="Message to customer"></label>
                <label>Status<select name="status" data-status-select onchange="syncStatusHint(<?=$o['id']?>)">
                  <?php foreach($allowed as $option): ?>
                    <option value="<?=e($option)?>" <?= $option===$o['status']?'selected':'' ?> <?= in_array($option,$transitionMap[$o['status']]??[$o['status']],true)?'':'disabled' ?>><?=e($option)?></option>
                  <?php endforeach; ?>
                </select></label>
                <div class="order-editor-actions"><button class="btn primary order-save-btn" type="submit" name="update_order"><span class="save-label">Confirm</span><span class="save-spinner" aria-hidden="true"></span></button><span class="order-save-message" role="status" aria-live="polite"></span></div>
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
<?php require "partials/footer.php"; ?>
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
.summary-icon{width:48px;height:48px;border-radius:50%;background:#fff;display:grid;place-items:center;font-size:25px;font-weight:900;box-shadow:0 4px 12px rgba(26,60,46,.06)}.summary-icon svg{width:25px;height:25px;display:block}.pill-symbol{width:18px;height:18px;display:inline-grid;place-items:center;flex:0 0 18px}.pill-symbol svg{width:17px;height:17px;display:block}
.pending .summary-icon{color:#079b65}.confirmed .summary-icon{color:#1686e5}.delivery .summary-icon{color:#f3a11d}.delivered .summary-icon{color:#e52f35}
.order-summary-card strong{display:block;font-size:25px;line-height:1;color:#102b55}.order-summary-card small{display:block;margin-top:6px;color:#607386;font-size:12px;font-weight:800;white-space:nowrap}
.order-summary-card i{position:absolute;left:17px;bottom:10px;width:60px;height:3px;border-radius:5px}.pending i{background:#0ba76b}.confirmed i{background:#168cf0}.delivery i{background:#f5a21a}.delivered i{background:#e53239}
.online-orders-page .alert{margin:0 0 16px}
.online-orders-toolbar{background:#fff;border:1px solid #e4ece8;border-radius:15px;padding:14px 15px;box-shadow:0 5px 18px rgba(34,74,57,.035);display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:17px}
.order-filter-pills{display:flex;gap:8px;flex-wrap:wrap}
.order-filter-pill{height:48px;padding:0 12px;border:1px solid #dfe7e4;border-radius:10px;background:#fff;color:#42576c;text-decoration:none;display:flex;align-items:center;gap:8px;font-size:13px;font-weight:800;white-space:nowrap}
.order-filter-pill:hover{border-color:#b8d9ca;background:#f8fcfa}.order-filter-pill.active{background:#12a66a;color:#fff;border-color:#12a66a;box-shadow:0 6px 14px rgba(18,166,106,.15)}
.order-filter-pill b{min-width:24px;height:24px;padding:0 7px;border-radius:50%;display:grid;place-items:center;background:#eaf3ef;color:#167c59;font-size:12px}.order-filter-pill.active b{background:#dff5eb;color:#0a875a}

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
.order-number-chip{display:inline-flex;padding:7px 9px;border-radius:8px;background:#eaf9f2;color:#079761;font-size:11px;font-weight:900}.order-detail-name{font-weight:800;color:#1b3551;font-size:13px}.online-orders-table td small{display:block;color:#7b8b98;margin-top:4px;font-size:11px}.customer-cell{display:flex;align-items:center;gap:8px}.customer-avatar{width:30px;height:30px;border:1px solid #d8e2de;border-radius:50%;display:grid;place-items:center;color:#5f7385;font-size:18px}.customer-cell strong{color:#203950}.table-total{color:#142f4e;font-size:13px;white-space:nowrap}.payment-chip,.table-status{display:inline-flex;align-items:center;padding:7px 9px;border-radius:8px;font-size:11px;font-weight:800;white-space:nowrap}.payment-chip{background:#eef8f4;color:#16835d;border:1px solid #cdeedf}.table-status.pending{background:#fff5df;color:#cf8610}.table-status.confirmed{background:#eaf5ff;color:#1779d4}.table-status.out-for-delivery{background:#fff0dc;color:#e79418}.table-status.delivered{background:#e5f8ef;color:#12905e}.table-status.cancelled{background:#fff0f0;color:#d3363d}.date-cell{color:#506477;white-space:nowrap}.date-cell small{margin-top:5px}.table-actions{display:flex;gap:7px}.table-action{height:36px;padding:0 12px;border-radius:8px;border:1px solid #dce6e2;font-weight:800;font-size:11px;cursor:pointer}.table-action.primary{background:#0da66b;border-color:#0da66b;color:#fff}.table-action.secondary{background:#fff;color:#405669}.order-view-row,.order-confirm-row{background:#f8fbfa}.order-view-row td,.order-confirm-row td{padding:0 16px;border-bottom:1px solid #e4ece8}.order-update-form{display:grid;grid-template-columns:1fr 1fr 190px auto;gap:10px;align-items:end;padding:15px 0}.order-update-form label{font-size:11px;font-weight:800;color:#5b7067}.order-update-form input,.order-update-form select{display:block;width:100%;margin-top:6px;height:38px;border:1px solid #d6e1dd;border-radius:7px;padding:0 10px;background:#fff}.order-update-form .btn{height:38px;white-space:nowrap}.order-final-note{background:#eaf8f1;border-radius:8px;padding:10px 13px;color:#277457;font-size:12px;font-weight:800}.orders-table-footer{display:flex;justify-content:space-between;align-items:center;padding:15px 18px;color:#6e7f8e;font-size:12px}.orders-table-footer>div{display:flex;align-items:center;gap:6px}.orders-table-footer button,.orders-table-footer b{height:36px;min-width:36px;padding:0 10px;border:1px solid #e1e8e5;border-radius:8px;background:#fff;color:#8795a0}.orders-table-footer b{display:grid;place-items:center;background:#10a66b;color:#fff;border-color:#10a66b}
@media(max-width:1200px){.online-orders-heading{flex-direction:column}.online-orders-summary{width:100%;min-width:0}.online-orders-toolbar{flex-direction:column;align-items:stretch}.orders-search-form{width:100%}.orders-search{flex:1;width:auto}.orders-date{width:150px}}
@media(max-width:800px){.online-orders-summary{grid-template-columns:repeat(2,1fr)}.online-orders-title-wrap{min-width:0}.orders-search-form{flex-wrap:wrap}.orders-search{min-width:220px}.orders-date{flex:1}.empty-info{text-align:left;justify-content:flex-start}}
@media(max-width:560px){.online-orders-page{padding-top:0}.online-orders-title-row{align-items:flex-start}.online-orders-title-row h1{font-size:29px}.online-orders-cart-icon{width:50px;height:50px;flex-basis:50px}.online-orders-summary{grid-template-columns:1fr 1fr}.order-filter-pills{display:grid;grid-template-columns:1fr 1fr}.order-filter-pill{justify-content:center}.orders-search-form{display:grid;grid-template-columns:1fr 1fr}.orders-search{grid-column:1/-1;width:100%}.orders-date{width:100%}.date-dash{display:none}.orders-search-btn{grid-column:1/-1}.online-orders-empty{min-height:420px}.empty-info{font-size:12px}.online-orders-empty h2{font-size:24px}}
</style>
<style>
/* Reference layout: everything stays inside ONE card, with a clean two-row layout when needed. */
.online-orders-toolbar{
  display:flex;
  flex-direction:column;
  align-items:stretch;
  gap:12px;
  width:100%;
  box-sizing:border-box;
  padding:16px 17px;
  border-radius:16px;
  background:#fff;
  border:1px solid #e1eae6;
  box-shadow:0 6px 20px rgba(34,74,57,.04);
}
.order-filter-pills{
  display:grid;
  grid-template-columns:repeat(6,minmax(0,1fr));
  align-items:stretch;
  gap:8px;
  min-width:0;
  width:100%;
  padding:1px 0;
}
.order-filter-pill{
  width:100%;
  min-width:0;
  height:48px;
  padding:0 10px;
  border:1px solid #dfe7e4;
  border-radius:10px;
  background:#fff;
  justify-content:center;
  box-sizing:border-box;
}
.order-filter-pill .pill-label{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.orders-search-form{
  display:grid;
  grid-template-columns:minmax(260px,1fr) 150px 18px 150px auto;
  align-items:center;
  gap:8px;
  width:100%;
  margin:0;
}
.orders-search{width:auto;min-width:0;height:48px}
.orders-date{width:auto;height:48px;min-width:0}
.orders-date input{min-width:0}
.orders-search-btn{height:48px;padding:0 20px;white-space:nowrap}

@media (max-width:1250px){
  .order-filter-pills{grid-template-columns:repeat(3,minmax(0,1fr));}
}
@media (max-width:760px){
  .online-orders-toolbar{padding:12px;gap:10px}
  .order-filter-pills{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
  .orders-search-form{grid-template-columns:1fr 1fr;gap:8px}
  .orders-search{grid-column:1 / -1}
  .orders-date{width:100%}
  .date-dash{display:none}
  .orders-search-form .orders-date:nth-of-type(2){grid-column:1}
  .orders-search-form .orders-date:nth-of-type(3){grid-column:2}
  .orders-search-btn{grid-column:1 / -1}
}
@media (max-width:430px){
  .order-filter-pills{grid-template-columns:1fr}
}
</style>


<style>
.online-orders-page,.online-orders-page button,.online-orders-page input,.online-orders-page select{font-family:Inter}
.order-row{transition:background .18s ease,box-shadow .18s ease}
.order-row.is-selected{background:#f5fbf8!important;box-shadow:inset 4px 0 0 #0fa46a}
.order-row.just-updated{animation:orderUpdated 1.15s ease}
@keyframes orderUpdated{0%{background:#e8fbf1}100%{background:transparent}}
.order-view-row,.order-confirm-row{display:none!important}
.order-view-row.is-open,.order-confirm-row.is-open{display:table-row!important}
.order-view-row>td,.order-confirm-row>td{background:#f7fbf9!important;padding:0 18px!important}
.order-update-form{display:grid;grid-template-columns:minmax(170px,1fr) minmax(200px,1.2fr) minmax(210px,250px) auto;gap:12px;align-items:end;padding:18px 0}
.order-editor-heading{grid-column:1/-1;display:flex;justify-content:space-between;align-items:center;gap:15px;padding:2px 0 4px}
.order-editor-heading>div{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap}
.order-editor-heading span{font-size:11px;text-transform:uppercase;letter-spacing:.08em;font-weight:900;color:#0c9b66}
.order-editor-heading strong{font-size:14px;color:#173654}
.order-editor-heading small{font-size:12px;color:#728596}
.order-editor-current{background:#e9f8f1;border:1px solid #cbeede;border-radius:999px;padding:7px 10px;color:#16845e!important;text-transform:none!important;letter-spacing:0!important}
.order-update-form label{font-size:11px;font-weight:800;color:#536a62}
.order-update-form input,.order-update-form select{display:block;width:100%;margin-top:6px;height:42px;border:1px solid #d3e0db;border-radius:9px;padding:0 11px;background:#fff;color:#263f56;outline:none;box-sizing:border-box}
.order-update-form input:focus,.order-update-form select:focus{border-color:#10a66b;box-shadow:0 0 0 3px rgba(16,166,107,.1)}

.order-editor-actions{display:flex;align-items:center;gap:10px;height:42px}
.order-save-btn{height:42px;min-width:130px;border:0;border-radius:9px;cursor:pointer}
.order-save-btn:disabled{opacity:.7;cursor:wait}
.save-spinner{display:none;width:14px;height:14px;border:2px solid rgba(255,255,255,.45);border-top-color:#fff;border-radius:50%;margin-left:7px;vertical-align:-2px;animation:spin .7s linear infinite}
.order-save-btn.is-loading .save-spinner{display:inline-block}
@keyframes spin{to{transform:rotate(360deg)}}
.order-save-message{min-height:18px;font-size:11px;font-weight:800}
.order-save-message.success{color:#16875f}.order-save-message.error{color:#d33d46}

.cancel-modal{display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:20px}.cancel-modal.is-open{display:flex}.cancel-modal-backdrop{position:absolute;inset:0;background:rgba(16,37,31,.48);backdrop-filter:blur(2px)}.cancel-modal-card{position:relative;width:min(480px,100%);background:#fff;border:1px solid #e1ebe6;border-radius:16px;box-shadow:0 24px 70px rgba(22,55,44,.2);padding:24px;z-index:1}.cancel-modal-icon{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:#fff0f0;color:#d3363d;font-weight:900;font-size:20px;margin-bottom:12px}.cancel-modal-card h3{margin:0;color:#173b5e;font-size:18px}.cancel-modal-card p{margin:7px 0 18px;color:#70818d;font-size:12px}.cancel-modal-close{position:absolute;right:14px;top:12px;border:0;background:transparent;color:#80908b;font-size:25px;cursor:pointer}.cancel-reason-label,.cancel-other-wrap{display:block;color:#526a62;font-size:11px;font-weight:800;margin-top:10px}.cancel-reason-label select,.cancel-other-wrap textarea{display:block;width:100%;box-sizing:border-box;margin-top:6px;border:1px solid #d3e0db;border-radius:9px;background:#fff;color:#263f56;padding:10px;font:inherit;outline:none}.cancel-other-wrap{display:none}.cancel-other-wrap.is-visible{display:block}.cancel-modal-actions{display:flex;justify-content:flex-end;gap:9px;margin-top:18px}.cancel-back-btn,.cancel-confirm-btn{height:40px;border-radius:9px;padding:0 14px;font-weight:800;font-size:11px;cursor:pointer}.cancel-back-btn{border:1px solid #dce6e2;background:#fff;color:#526879}.cancel-confirm-btn{border:1px solid #d3363d;background:#d3363d;color:#fff}.cancel-modal-error{min-height:16px;margin-top:9px;color:#d3363d;font-size:11px;font-weight:800}

.order-view-panel{padding:18px 0 20px}
.order-view-head{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:14px}
.order-view-head>div{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap}
.order-view-kicker{font-size:11px;font-weight:900;letter-spacing:.08em;color:#079761}
.order-view-head strong{font-size:14px;color:#173b5e}
.order-view-status{display:inline-flex;align-items:center;padding:7px 11px;border-radius:999px;background:#eaf8f1;border:1px solid #c7ebdb;color:#087d56;font-size:11px;font-weight:900}
.order-view-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(220px,1fr) minmax(220px,1fr);gap:12px}
.order-view-card{background:#fff;border:1px solid #e1ebe6;border-radius:11px;padding:14px;box-shadow:0 4px 14px rgba(30,70,54,.035)}
.order-view-card h3{margin:0 0 11px;color:#274963;font-size:12px;font-weight:900}
.order-items-table-wrap{overflow:auto}.order-items-table{width:100%;border-collapse:collapse;min-width:470px}.order-items-table th,.order-items-table td{padding:9px 8px;border-bottom:1px solid #edf2ef;text-align:left;font-size:11px;color:#5b6e7e;white-space:nowrap}.order-items-table th{font-weight:900;color:#627687;background:#f8faf9}.order-items-table td strong{color:#1d3a55}.order-items-table tfoot th{border-bottom:0;padding-top:11px;color:#173b5e;background:#fff;font-size:12px}.order-items-table tfoot th:last-child{text-align:right}.order-info-list{margin:0;display:grid;gap:9px}.order-info-list>div{display:grid;grid-template-columns:105px 1fr;gap:8px}.order-info-list dt{font-size:10px;font-weight:900;color:#83918d}.order-info-list dd{margin:0;font-size:11px;font-weight:700;color:#304b61;line-height:1.45;overflow-wrap:anywhere}
.order-row.is-selected{background:#f3fbf7!important}.order-row.is-selected td:first-child{box-shadow:inset 4px 0 #10a66b}
@media(max-width:1050px){.order-view-grid{grid-template-columns:1fr 1fr}.order-items-card{grid-column:1/-1}.order-update-form{grid-template-columns:1fr 1fr}.order-editor-heading,.order-editor-actions{grid-column:1/-1}}
@media(max-width:620px){.order-view-grid{grid-template-columns:1fr}.order-items-card{grid-column:auto}.order-update-form{grid-template-columns:1fr}.order-editor-heading,.order-editor-actions{grid-column:1}.order-editor-heading{align-items:flex-start;flex-direction:column}.order-editor-current{align-self:flex-start}}
</style>
<div class="cancel-modal" id="cancel-modal" aria-hidden="true">
  <div class="cancel-modal-backdrop" data-cancel-close></div>
  <div class="cancel-modal-card" role="dialog" aria-modal="true" aria-labelledby="cancel-modal-title">
    <button type="button" class="cancel-modal-close" data-cancel-close aria-label="Close">&times;</button>
    <div class="cancel-modal-icon">!</div>
    <h3 id="cancel-modal-title">Why are you cancelling this order?</h3>
    <p>Select a reason before cancelling the order.</p>
    <label class="cancel-reason-label">Cancellation reason
      <select id="cancel-reason-select">
        <option value="">Choose a reason</option>
        <option>Customer requested cancellation</option>
        <option>Product/item out of stock</option>
        <option>Payment issue</option>
        <option>Invalid customer/order information</option>
        <option>Delivery unavailable</option>
        <option>Customer did not respond</option>
        <option>Duplicate order</option>
        <option>Order placed by mistake</option>
        <option>Other</option>
      </select>
    </label>
    <label class="cancel-other-wrap" id="cancel-other-wrap">Please specify the reason
      <textarea id="cancel-reason-custom" rows="3" placeholder="Please specify the reason"></textarea>
    </label>
    <div class="cancel-modal-actions">
      <button type="button" class="cancel-back-btn" data-cancel-close>Back</button>
      <button type="button" class="cancel-confirm-btn" id="cancel-confirm-btn">Confirm Cancellation</button>
    </div>
    <div class="cancel-modal-error" id="cancel-modal-error" role="alert"></div>
  </div>
</div>

<script>
(function(){
  const transitionMap = {
    'Pending':['Pending','Confirmed','Cancelled'],
    'Confirmed':['Confirmed','Out for Delivery','Cancelled'],
    'Out for Delivery':['Out for Delivery','Delivered','Cancelled'],
    'Delivered':['Delivered'],
    'Cancelled':['Cancelled']
  };
  const statusClass = s => s.toLowerCase().replace(/\s+/g,'-');
  const esc = s => String(s ?? '');
  let cancelTargetForm = null;
  const cancelModal=document.getElementById('cancel-modal');
  const cancelSelect=document.getElementById('cancel-reason-select');
  const cancelOther=document.getElementById('cancel-reason-custom');
  const cancelOtherWrap=document.getElementById('cancel-other-wrap');
  const cancelError=document.getElementById('cancel-modal-error');
  function openCancelModal(form){
    cancelTargetForm=form;
    if(cancelModal){ cancelModal.classList.add('is-open'); cancelModal.setAttribute('aria-hidden','false'); }
    if(cancelSelect) cancelSelect.value=form.querySelector('[name=\"cancellation_reason\"]')?.value || '';
    if(cancelOther) cancelOther.value=form.querySelector('[name=\"cancellation_reason_custom\"]')?.value || '';
    if(cancelOtherWrap) cancelOtherWrap.classList.toggle('is-visible',cancelSelect?.value==='Other');
    if(cancelError) cancelError.textContent='';
    setTimeout(()=>cancelSelect?.focus(),50);
  }
  function closeCancelModal(resetStatus=true){
    const form=cancelTargetForm;
    if(cancelModal){ cancelModal.classList.remove('is-open'); cancelModal.setAttribute('aria-hidden','true'); }
    if(resetStatus && form){
      const row=document.getElementById('order-row-'+form.dataset.orderId);
      const current=row?.dataset.status || '';
      const select=form.querySelector('[data-status-select]');
      if(select && current && current!=='Cancelled'){ select.value=current; }
      form.querySelector('[name=\"cancellation_reason\"]')?.setAttribute('value','');
      form.querySelector('[name=\"cancellation_reason_custom\"]')?.setAttribute('value','');
      if(cancelSelect) cancelSelect.value=''; if(cancelOther) cancelOther.value=''; if(cancelOtherWrap) cancelOtherWrap.classList.remove('is-visible');
      syncStatusHint(form.dataset.orderId);
    }
    cancelTargetForm=null;
  }
  document.querySelectorAll('[data-cancel-close]').forEach(el=>el.addEventListener('click',()=>closeCancelModal(true)));
  cancelSelect?.addEventListener('change',()=>{ cancelOtherWrap?.classList.toggle('is-visible',cancelSelect.value==='Other'); if(cancelError) cancelError.textContent=''; });
  document.getElementById('cancel-confirm-btn')?.addEventListener('click',()=>{
    if(!cancelTargetForm) return;
    const reason=(cancelSelect?.value||'').trim(); const custom=(cancelOther?.value||'').trim();
    if(!reason){ if(cancelError) cancelError.textContent='Please choose a cancellation reason.'; return; }
    if(reason==='Other' && !custom){ if(cancelError) cancelError.textContent='Please specify the reason.'; cancelOther?.focus(); return; }
    const form=cancelTargetForm;
    form.querySelector('[name=\"cancellation_reason\"]').value=reason;
    form.querySelector('[name=\"cancellation_reason_custom\"]').value=custom;
    if(cancelModal){ cancelModal.classList.remove('is-open'); cancelModal.setAttribute('aria-hidden','true'); }
    cancelTargetForm=null;
    form.requestSubmit();
  });

  function closeOrderPanels(){
    document.querySelectorAll('.order-view-row.is-open,.order-confirm-row.is-open').forEach(el=>el.classList.remove('is-open'));
    document.querySelectorAll('.order-row.is-selected').forEach(el=>el.classList.remove('is-selected'));
  }

  window.toggleOrderDetails = function(id){
    const row=document.getElementById('order-view-'+id);
    const main=document.getElementById('order-row-'+id);
    if(!row || !main) return;
    const isOpen=row.classList.contains('is-open');
    closeOrderPanels();
    if(!isOpen){
      row.classList.add('is-open');
      main.classList.add('is-selected');
      setTimeout(()=>row.scrollIntoView({behavior:'smooth',block:'center'}),30);
    }
  };

  window.toggleOrderConfirm = function(id){
    const row=document.getElementById('order-details-'+id);
    const main=document.getElementById('order-row-'+id);
    if(!row || !main) return;
    const isOpen=row.classList.contains('is-open');
    closeOrderPanels();
    if(!isOpen){
      row.classList.add('is-open');
      main.classList.add('is-selected');
      setTimeout(()=>row.scrollIntoView({behavior:'smooth',block:'center'}),30);
      const input=row.querySelector('input[name="delivery_person"]');
      if(input) setTimeout(()=>input.focus({preventScroll:true}),220);
    }
  };

  window.syncStatusHint = function(id){
    const row=document.getElementById('order-details-'+id); if(!row) return;
    const form=row.querySelector('form');
    const select=form.querySelector('[data-status-select]');
    const current=row.closest('tbody').querySelector('#order-row-'+id)?.dataset.status || select.value;
    const next=select.value;
    if(next==='Cancelled' && next!==current){
      openCancelModal(form);
    }
  };

  function setLoading(form,on){
    const btn=form.querySelector('.order-save-btn');
    if(!btn) return;
    btn.disabled=on;
    btn.classList.toggle('is-loading',on);
    const label=btn.querySelector('.save-label');
    if(label) label.textContent=on?'Saving…':'Save changes';
  }

  function showMessage(form,text,ok){
    const box=form.querySelector('.order-save-message');
    if(!box) return;
    box.textContent=text;
    box.className='order-save-message '+(ok?'success':'error');
    clearTimeout(box._timer);
    box._timer=setTimeout(()=>{box.textContent='';box.className='order-save-message';},5000);
  }

  function updateCounts(oldStatus,newStatus){
    const labels={'Pending':'Pending','Confirmed':'Confirmed','Out for Delivery':'Out for Delivery','Delivered':'Delivered','Cancelled':'Cancelled'};
    if(oldStatus!==newStatus){
      [oldStatus,newStatus].forEach((st,i)=>{
        const pill=[...document.querySelectorAll('.order-filter-pill')].find(a=>a.textContent.includes(labels[st]));
        if(!pill) return;
        const badge=pill.querySelector('b');
        if(!badge) return;
        const n=Math.max(0,parseInt(badge.textContent.replace(/[^0-9]/g,''),10)||0)+(i===0?-1:1);
        badge.textContent=n;
      });
      const all=document.querySelector('.order-filter-pill');
      if(all){
        const b=all.querySelector('b');
        if(b){ const n=parseInt(b.textContent.replace(/[^0-9]/g,''),10)||0; b.textContent=n; }
      }
      const cards={'Pending':'pending','Confirmed':'confirmed','Out for Delivery':'delivery','Delivered':'delivered'};
      [oldStatus,newStatus].forEach(st=>{
        const card=document.querySelector('.order-summary-card.'+cards[st]);
        if(card){
          const strong=card.querySelector('strong');
          if(strong){let n=parseInt(strong.textContent.replace(/[^0-9]/g,''),10)||0;n=Math.max(0,n+(st===oldStatus?-1:1));strong.textContent=n.toLocaleString();}
        }
      });
    }
  }

  document.querySelectorAll('.order-update-form').forEach(form=>{
    form.addEventListener('submit',async function(ev){
      ev.preventDefault();
      const id=form.dataset.orderId;
      const row=document.getElementById('order-row-'+id);
      const details=document.getElementById('order-details-'+id);
      const oldStatus=row?.dataset.status || form.querySelector('[data-status-select]')?.value || '';
      const newStatus=form.querySelector('[data-status-select]')?.value || oldStatus;
      const cancellationReason=(form.querySelector('[name=\"cancellation_reason\"]')?.value||'').trim();
      const cancellationCustom=(form.querySelector('[name=\"cancellation_reason_custom\"]')?.value||'').trim();
      if(newStatus==='Cancelled' && (!cancellationReason || (cancellationReason==='Other' && !cancellationCustom))){
        openCancelModal(form);
        setLoading(form,false);
        return;
      }
      setLoading(form,true); showMessage(form,'',true);
      try{
        const response=await fetch('order_status_api.php',{method:'POST',body:new FormData(form),headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},credentials:'same-origin',cache:'no-store'});
        const raw=await response.text().catch(()=> '');
        let data=null;
        try { data=raw ? JSON.parse(raw) : null; } catch(e) { data=null; }
        if(!data){
          if(response.status===401) throw new Error('Your admin session has expired. Please log in again.');
          throw new Error('The order update endpoint did not return valid JSON.');
        }
        if(!response.ok || !data.ok) throw new Error(data.message || 'Unable to update the order.');

        if(row){
          row.dataset.status=data.status;
          const chip=row.querySelector('[data-status-chip]');
          if(chip){chip.className='table-status '+statusClass(data.status);chip.textContent=data.status;}
          const actions=row.querySelector('.table-actions');
          const confirmBtn=actions?.querySelector('[data-confirm-order]');
          if(actions){
            if(data.status==='Delivered' || data.status==='Cancelled'){
              confirmBtn?.remove();
            } else if(!confirmBtn){
              const btn=document.createElement('button');
              btn.type='button';
              btn.className='table-action secondary';
              btn.dataset.confirmOrder=id;
              btn.textContent='Confirm';
              btn.addEventListener('click',()=>toggleOrderConfirm(id));
              actions.appendChild(btn);
            }
          }
          row.classList.add('just-updated');
          setTimeout(()=>row.classList.remove('just-updated'),1300);
        }
        const label=details?.querySelector('[data-current-label]');
        if(label) label.textContent='Current: '+data.status;
        const view=document.getElementById('order-view-'+id);
        const viewStatus=view?.querySelector('[data-view-status]');
        if(viewStatus) viewStatus.textContent=data.status;
        if(view && data.status==='Cancelled' && data.cancellation_reason){
          let reason=view.querySelector('[data-view-cancellation-reason]');
          if(!reason){
            const list=view.querySelector('.order-view-card:last-child .order-info-list');
            if(list){ const wrap=document.createElement('div'); wrap.innerHTML='<dt>Cancellation Reason</dt><dd data-view-cancellation-reason></dd>'; list.appendChild(wrap); reason=wrap.querySelector('[data-view-cancellation-reason]'); }
          }
          if(reason) reason.textContent=data.cancellation_reason;
        }
        const select=form.querySelector('[data-status-select]');
        if(select){
          select.value=data.status;
          [...select.options].forEach(opt=>{ opt.disabled=!(transitionMap[data.status]||[data.status]).includes(opt.value); });
        }
        updateCounts(oldStatus,data.status);
        showMessage(form,'Order status updated successfully.',true);
        syncStatusHint(id);

        // If a filtered list no longer matches, hide the row immediately rather than forcing a refresh.
        const params=new URLSearchParams(window.location.search);
        const activeFilter=params.get('status') || 'All';
        if(activeFilter!=='All' && activeFilter!==data.status){
          setTimeout(()=>{
            row?.remove(); details?.remove();
            const remaining=document.querySelectorAll('.online-orders-table tbody .order-row').length;
            const footer=document.querySelector('.orders-table-footer span');
            if(footer) footer.textContent='Showing '+remaining+' order'+(remaining===1?'':'s');
          },700);
        }
      }catch(err){
        showMessage(form,err.message || 'Unable to update the order.',false);
      }finally{setLoading(form,false);}
    });
  });
})();
</script>
<style>
/* Mobile + tablet responsive layout for Online Orders */
@media (max-width: 1024px){
  .online-orders-page{padding:8px 14px 28px;max-width:none}
  .online-orders-heading{flex-direction:column;gap:16px}
  .online-orders-title-wrap{min-width:0;width:100%}
  .online-orders-summary{width:100%;min-width:0;grid-template-columns:repeat(2,minmax(0,1fr))}
  .online-orders-toolbar{flex-direction:column;align-items:stretch}
  .order-filter-pills{overflow-x:auto;flex-wrap:nowrap;padding-bottom:3px;scrollbar-width:none}
  .order-filter-pills::-webkit-scrollbar{display:none}
  .order-filter-pill{flex:0 0 auto}
  .orders-search-form{width:100%;flex-wrap:wrap}
  .orders-search{flex:1 1 100%;width:auto}
  .orders-date{flex:1 1 0;width:auto;min-width:0}
  .orders-search-btn{flex:0 0 auto}
}

@media (max-width: 700px){
  .online-orders-page{padding:6px 10px 24px}
  .online-orders-kicker{font-size:11px;letter-spacing:2.1px}
  .online-orders-title-row{gap:11px;align-items:flex-start}
  .online-orders-title-row h1{font-size:28px;letter-spacing:-.7px}
  .online-orders-title-row p{font-size:13px}
  .online-orders-cart-icon{width:46px;height:46px;flex-basis:46px}
  .online-orders-cart-icon svg{width:25px;height:25px}
  .online-orders-summary{grid-template-columns:1fr 1fr;gap:8px}
  .order-summary-card{min-height:76px;padding:12px 10px;gap:8px;border-radius:12px}
  .summary-icon{width:38px;height:38px}
  .summary-icon svg{width:20px;height:20px}
  .order-summary-card strong{font-size:20px}
  .order-summary-card small{font-size:10px;margin-top:4px}
  .order-summary-card i{left:10px;bottom:7px;width:42px}

  .online-orders-toolbar{padding:10px;border-radius:12px;gap:10px;margin-bottom:12px}
  .order-filter-pill{height:42px;padding:0 10px;font-size:11px;gap:6px}
  .pill-symbol{width:16px;height:16px;flex-basis:16px}
  .pill-symbol svg{width:15px;height:15px}
  .order-filter-pill b{min-width:20px;height:20px;font-size:10px;padding:0 5px}
  .orders-search-form{gap:7px}
  .orders-search{height:42px;padding:0 10px}
  .orders-date{height:42px;padding:0 8px}
  .orders-search-btn{height:42px;padding:0 13px}

  .online-orders-table-card{border-radius:13px;overflow:visible;background:transparent;border:0;box-shadow:none}
  .orders-table-scroll{overflow:visible}
  .online-orders-table{display:block;min-width:0;width:100%}
  .online-orders-table thead{display:none}
  .online-orders-table tbody{display:block;width:100%}
  .online-orders-table tr.order-row{display:grid;grid-template-columns:1fr auto;gap:0 10px;background:#fff;border:1px solid #e1e9e5;border-radius:13px;margin:0 0 10px;padding:12px 12px 10px;box-shadow:0 5px 16px rgba(32,76,58,.045)}
  .online-orders-table tr.order-row.is-selected{border-color:#b8dfcf;box-shadow:0 7px 20px rgba(18,166,106,.10)}
  .online-orders-table tr.order-row td{display:flex;align-items:center;min-width:0;padding:7px 0;border:0;font-size:12px;gap:8px}
  .online-orders-table tr.order-row td::before{content:'';color:#80909d;font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:.45px;flex:0 0 auto;width:70px}
  .online-orders-table tr.order-row td:nth-child(1){grid-column:1 / -1;padding-top:0}
  .online-orders-table tr.order-row td:nth-child(1)::before{content:'Order'}
  .online-orders-table tr.order-row td:nth-child(2)::before{content:'Details'}
  .online-orders-table tr.order-row td:nth-child(3)::before{content:'Customer'}
  .online-orders-table tr.order-row td:nth-child(4)::before{content:'Items'}
  .online-orders-table tr.order-row td:nth-child(5)::before{content:'Total'}
  .online-orders-table tr.order-row td:nth-child(6)::before{content:'Payment'}
  .online-orders-table tr.order-row td:nth-child(7)::before{content:'Status'}
  .online-orders-table tr.order-row td:nth-child(8)::before{content:'Date'}
  .online-orders-table tr.order-row td:nth-child(9){grid-column:1 / -1;justify-content:flex-end;padding-top:9px;margin-top:4px;border-top:1px solid #edf1ef}
  .online-orders-table tr.order-row td:nth-child(9)::before{display:none}
  .online-orders-table tr.order-row td:nth-child(2),
  .online-orders-table tr.order-row td:nth-child(3),
  .online-orders-table tr.order-row td:nth-child(4){align-items:flex-start}
  .order-number-chip{font-size:10px;padding:6px 8px}
  .order-detail-name{font-size:12px}
  .customer-cell{flex:1;min-width:0}
  .customer-cell strong,.customer-cell small{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:190px}
  .table-actions{width:100%;justify-content:flex-end}
  .table-action{height:38px;padding:0 14px;font-size:11px}

  .online-orders-table tr.order-view-row,
  .online-orders-table tr.order-confirm-row{display:none}
  .online-orders-table tr.order-view-row.is-open,
  .online-orders-table tr.order-confirm-row.is-open{display:block;background:#fff;border:1px solid #e1e9e5;border-radius:13px;margin:-2px 0 10px;box-shadow:0 5px 16px rgba(32,76,58,.045)}
  .online-orders-table tr.order-view-row > td,
  .online-orders-table tr.order-confirm-row > td{display:block;padding:0;border:0}
  .order-view-panel{padding:14px!important}
  .order-view-grid{grid-template-columns:1fr!important;gap:10px!important}
  .order-view-card{padding:13px!important}
  .order-items-table-wrap{overflow-x:auto}
  .order-items-table{min-width:520px}
  .order-editor-form{padding:14px!important}
  .order-editor-heading{display:flex!important;flex-direction:column!important;gap:8px!important}
  .order-editor-current{align-self:flex-start}
  .order-editor-form>label{width:100%!important}
  .order-editor-actions{width:100%!important;display:flex!important;align-items:center!important;gap:8px!important;flex-wrap:wrap!important}
  .order-editor-actions .order-save-btn{width:100%!important}

  .orders-table-footer{display:flex;flex-direction:column;align-items:flex-start;gap:10px;padding:12px 4px!important}
}

@media (min-width:701px) and (max-width:1024px){
  .online-orders-table-card{overflow:hidden}
  .orders-table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch}
  .online-orders-table{min-width:1050px}
  .order-view-grid{grid-template-columns:1.2fr 1fr!important}
}
</style>
