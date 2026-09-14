<?php
require "auth.php"; $pageTitle="Sales"; $active="sales"; $message='';
if(isset($_POST['save_sale'])){
    $customer_id=(int)($_POST['customer_id']??0); $product_id=(int)$_POST['product_id']; $qty=(float)$_POST['quantity']; $payment=$_POST['payment_method'];
    if(!in_array($payment,['Cash','Mobile Money','Bank'],true) || $qty<=0){$message="Please enter valid sale details.";}
    else{
        $conn->begin_transaction();
        try{
            $st=$conn->prepare("SELECT selling_price,stock_quantity,name,unit FROM products WHERE id=? FOR UPDATE");$st->bind_param("i",$product_id);$st->execute();$p=$st->get_result()->fetch_assoc();
            if(!$p) throw new Exception("Product not found.");
            if($qty>$p['stock_quantity']) throw new Exception("Insufficient stock. Available: ".$p['stock_quantity']." ".$p['unit'].".");
            $total=$qty*(float)$p['selling_price'];
            $number='SALE-'.date('YmdHis').'-'.random_int(100,999);
            $st=$conn->prepare("INSERT INTO sales(sale_number,customer_id,sale_date,payment_method,total_amount,created_by) VALUES(?,?,CURDATE(),?,?,?)");
            $st->bind_param("sisdi",$number,$customer_id,$payment,$total,$_SESSION['user_id']); $st->execute(); $sale_id=$conn->insert_id;
            $item=$conn->prepare("INSERT INTO sale_items(sale_id,product_id,quantity,unit_price,total) VALUES(?,?,?,?,?)");$item->bind_param("iiddd",$sale_id,$product_id,$qty,$p['selling_price'],$total);$item->execute();
            $up=$conn->prepare("UPDATE products SET stock_quantity=stock_quantity-? WHERE id=?");$up->bind_param("di",$qty,$product_id);$up->execute();
            $mv=$conn->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,reference_id) VALUES(?,'Sale',?,?)");$mv->bind_param("idi",$product_id,$qty,$sale_id);$mv->execute();
            $conn->commit(); header("Location: receipt.php?id=".$sale_id); exit;
        }catch(Exception $e){$conn->rollback();$message=$e->getMessage();}
    }
}
$products=$conn->query("SELECT * FROM products WHERE stock_quantity>0 ORDER BY name");
$customers=$conn->query("SELECT * FROM customers ORDER BY name");
$recent=$conn->query("SELECT s.*,COALESCE(c.name,'Walk-in Customer') customer FROM sales s LEFT JOIN customers c ON c.id=s.customer_id ORDER BY s.id DESC LIMIT 30");
require "partials/header.php";
?>
<?php if($message): ?><div class="alert danger"><?=e($message)?></div><?php endif;?>
<div class="panel"><h3>New Sale</h3><form method="post">
<div class="form-grid"><div class="field"><label>Customer</label><select name="customer_id"><option value="0">Walk-in Customer</option><?php while($c=$customers->fetch_assoc()): ?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endwhile;?></select></div>
<div class="field"><label>Product</label><select name="product_id" id="product" required><?php while($p=$products->fetch_assoc()): ?><option value="<?=$p['id']?>" data-price="<?=$p['selling_price']?>" data-stock="<?=$p['stock_quantity']?>"><?=e($p['name'])?> - <?=e($p['unit'])?> (Stock: <?=money($p['stock_quantity'])?>)</option><?php endwhile;?></select></div>
<div class="field"><label>Quantity</label><input type="number" step="0.01" min="0.01" name="quantity" id="qty" required></div>
<div class="field"><label>Unit Price (TZS)</label><input id="price" readonly></div>
<div class="field"><label>Total Amount (TZS)</label><input id="total" readonly></div>
<div class="field"><label>Payment Method</label><select name="payment_method" required><option>Cash</option><option>Mobile Money</option><option>Bank</option></select></div></div>
<div class="form-actions"><button class="btn primary" name="save_sale">Save Sale & Print Receipt</button></div></form></div>
<div class="panel" style="margin-top:20px"><h3>Recent Sales</h3><div class="table-wrap"><table class="table"><tr><th>Sale No.</th><th>Date</th><th>Customer</th><th>Payment</th><th>Total</th><th></th></tr>
<?php while($r=$recent->fetch_assoc()): ?><tr><td><?=e($r['sale_number'])?></td><td><?=e($r['sale_date'])?></td><td><?=e($r['customer'])?></td><td><?=e($r['payment_method'])?></td><td>TZS <?=money($r['total_amount'])?></td><td><a class="btn btn-sm secondary" href="receipt.php?id=<?=$r['id']?>">Receipt</a></td></tr><?php endwhile;?></table></div></div>
<script>const p=document.getElementById('product'),q=document.getElementById('qty'),pr=document.getElementById('price'),t=document.getElementById('total');function calc(){let o=p.options[p.selectedIndex],price=+o.dataset.price||0;pr.value=price.toLocaleString();t.value=((+q.value||0)*price).toLocaleString()}p.addEventListener('change',calc);q.addEventListener('input',calc);calc();</script>
<?php require "partials/footer.php"; ?>