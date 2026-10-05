<?php
require __DIR__ . '/auth.php';
require_admin();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$allowed=['Pending','Confirmed','Out for Delivery','Delivered','Cancelled'];
$transitionMap=[
 'Pending'=>['Pending','Confirmed','Cancelled'],
 'Confirmed'=>['Confirmed','Out for Delivery','Cancelled'],
 'Out for Delivery'=>['Out for Delivery','Delivered','Cancelled'],
 'Delivered'=>['Delivered'],
 'Cancelled'=>['Cancelled']
];
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

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('POST request required.');
  $id=(int)($_POST['order_id']??0);
  $new=trim((string)($_POST['status']??''));
  $driver=trim((string)($_POST['delivery_person']??''));
  $note=trim((string)($_POST['admin_note']??''));
  $result=updateOnlineOrder($conn,$id,$new,$driver,$note,$allowed,$transitionMap);
  echo json_encode(['ok'=>true]+$result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
exit;
