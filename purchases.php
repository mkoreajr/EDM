<?php
require "auth.php"; $pageTitle="Purchases"; $active="purchases"; $message='';
if(isset($_POST['save_purchase'])){
    $supplier=(int)($_POST['supplier_id']??0);$product=(int)$_POST['product_id'];$qty=(float)$_POST['quantity'];$cost=(float)$_POST['unit_cost'];
    if($qty<=0||$cost<0)$message="Enter valid purchase details.";else{$conn->begin_transaction();try{
        $total=$qty*$cost;$st=$conn->prepare("INSERT INTO purchases(supplier_id,purchase_date,total_amount,created_by) VALUES(?,CURDATE(),?,?)");$st->bind_param("idi",$supplier,$total,$_SESSION['user_id']);$st->execute();$pid=$conn->insert_id;
        $it=$conn->prepare("INSERT INTO purchase_items(purchase_id,product_id,quantity,unit_cost,total) VALUES(?,?,?,?,?)");$it->bind_param("iiddd",$pid,$product,$qty,$cost,$total);$it->execute();
        $up=$conn->prepare("UPDATE products SET stock_quantity=stock_quantity+? WHERE id=?");$up->bind_param("di",$qty,$product);$up->execute();
        $mv=$conn->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,reference_id) VALUES(?,'Purchase',?,?)");$mv->bind_param("idi",$product,$qty,$pid);$mv->execute();
        $conn->commit();header("Location: purchases.php");exit;
    }catch(Exception $e){$conn->rollback();$message=$e->getMessage();}}
}
$products=$conn->query("SELECT * FROM products ORDER BY name");$suppliers=$conn->query("SELECT * FROM suppliers ORDER BY name");$rows=$conn->query("SELECT pu.*,COALESCE(s.name,'Unknown') supplier,(SELECT p.name FROM purchase_items pi JOIN products p ON p.id=pi.product_id WHERE pi.purchase_id=pu.id LIMIT 1) product,(SELECT quantity FROM purchase_items WHERE purchase_id=pu.id LIMIT 1) qty FROM purchases pu LEFT JOIN suppliers s ON s.id=pu.supplier_id ORDER BY pu.id DESC LIMIT 50");
require "partials/header.php"; ?>
<?php if($message):?><div class="alert danger"><?=e($message)?></div><?php endif;?>
<div class="panel"><h3>New Purchase</h3><form method="post"><div class="form-grid"><div class="field"><label>Supplier</label><select name="supplier_id"><option value="0">Select Supplier</option><?php while($s=$suppliers->fetch_assoc()):?><option value="<?=$s['id']?>"><?=e($s['name'])?></option><?php endwhile;?></select></div><div class="field"><label>Product</label><select name="product_id" required><?php while($p=$products->fetch_assoc()):?><option value="<?=$p['id']?>"><?=e($p['name'])?> - <?=e($p['unit'])?></option><?php endwhile;?></select></div><div class="field"><label>Quantity</label><input type="number" step="0.01" min="0.01" name="quantity" required></div><div class="field"><label>Unit Cost (TZS)</label><input type="number" step="0.01" min="0" name="unit_cost" required></div></div><div class="form-actions"><button class="btn primary" name="save_purchase">Save Purchase</button></div></form></div>
<div class="panel" style="margin-top:20px"><h3>Purchase History</h3><table class="table"><tr><th>Date</th><th>Supplier</th><th>Product</th><th>Qty</th><th>Total</th></tr><?php while($r=$rows->fetch_assoc()):?><tr><td><?=e($r['purchase_date'])?></td><td><?=e($r['supplier'])?></td><td><?=e($r['product'])?></td><td><?=money($r['qty'])?></td><td>TZS <?=money($r['total_amount'])?></td></tr><?php endwhile;?></table></div>
<?php require "partials/footer.php"; ?>