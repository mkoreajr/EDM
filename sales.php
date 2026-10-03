<?php
require "auth.php"; $pageTitle="Sales"; $active="sales"; $message='';

if(isset($_POST['save_sale'])){
    $customer_id=(int)($_POST['customer_id']??0);
    $payment=$_POST['payment_method']??'';
    $product_ids=$_POST['product_id']??[];
    $quantities=$_POST['quantity']??[];

    if(!is_array($product_ids)) $product_ids=[$product_ids];
    if(!is_array($quantities)) $quantities=[$quantities];

    $lines=[];
    foreach($product_ids as $idx=>$pid){
        $pid=(int)$pid; $qty=(int)($quantities[$idx]??0);
        if($pid>0 && $qty>0) $lines[]=['product_id'=>$pid,'quantity'=>$qty];
    }

    if(!in_array($payment,['Cash','Mobile Money','Bank'],true) || !$lines){
        $message="Please add at least one valid product and quantity.";
    } else {
        $conn->begin_transaction();
        try{
            $total=0; $checked=[];
            foreach($lines as $line){
                $pid=$line['product_id']; $qty=$line['quantity'];
                $st=$conn->prepare("SELECT selling_price,stock_quantity,name,unit,category,package_size_kg FROM products WHERE id=? FOR UPDATE");
                $st->bind_param("i",$pid); $st->execute(); $p=$st->get_result()->fetch_assoc();
                if(!$p) throw new Exception("Product not found.");
                if($qty>$p['stock_quantity']) throw new Exception("Insufficient stock for ".$p['name'].". Available: ".$p['stock_quantity']." ".strtolower($p['unit']).".");
                $lineTotal=$qty*(float)$p['selling_price'];
                $total += $lineTotal;
                $checked[]=['product_id'=>$pid,'quantity'=>$qty,'price'=>(float)$p['selling_price'],'total'=>$lineTotal];
            }

            $number='SALE-'.date('YmdHis').'-'.random_int(100,999);
            if($customer_id > 0){
                $st=$conn->prepare("INSERT INTO sales(sale_number,customer_id,sale_date,payment_method,total_amount,created_by) VALUES(?,?,CURDATE(),?,?,?)");
                $st->bind_param("sisdi",$number,$customer_id,$payment,$total,$_SESSION['user_id']);
            } else {
                $st=$conn->prepare("INSERT INTO sales(sale_number,customer_id,sale_date,payment_method,total_amount,created_by) VALUES(?,NULL,CURDATE(),?,?,?)");
                $st->bind_param("sdi",$number,$payment,$total,$_SESSION['user_id']);
            }
            $st->execute(); $sale_id=$conn->insert_id;

            $item=$conn->prepare("INSERT INTO sale_items(sale_id,product_id,quantity,unit_price,total) VALUES(?,?,?,?,?)");
            $up=$conn->prepare("UPDATE products SET stock_quantity=stock_quantity-? WHERE id=?");
            $mv=$conn->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,reference_id) VALUES(?,'Sale',?,?)");

            foreach($checked as $line){
                $item->bind_param("iiddd",$sale_id,$line['product_id'],$line['quantity'],$line['price'],$line['total']); $item->execute();
                $up->bind_param("di",$line['quantity'],$line['product_id']); $up->execute();
                $mv->bind_param("idi",$line['product_id'],$line['quantity'],$sale_id); $mv->execute();
            }

            $conn->commit(); header("Location: receipt.php?id=".$sale_id); exit;
        }catch(Exception $e){$conn->rollback();$message=$e->getMessage();}
    }
}

$products=$conn->query("SELECT id,name,category,unit,selling_price,stock_quantity,package_size_kg FROM products WHERE stock_quantity>0 ORDER BY category,name,package_size_kg");
$productOptions=[];
while($p=$products->fetch_assoc()) $productOptions[]=$p;
$productCount=count($productOptions);
$customers=$conn->query("SELECT id,name FROM customers ORDER BY name");
$perPage = 10; $page = max(1, (int)($_GET['page'] ?? 1)); $totalSales = (int)$conn->query("SELECT COUNT(*) AS c FROM sales")->fetch_assoc()['c']; $totalPages = max(1, (int)ceil($totalSales / $perPage)); if($page > $totalPages) $page = $totalPages; $offset = ($page - 1) * $perPage;
$recent=$conn->query("SELECT s.*,COALESCE(c.name,'Walk-in Customer') customer FROM sales s LEFT JOIN customers c ON c.id=s.customer_id ORDER BY s.id DESC LIMIT $perPage OFFSET $offset");
require "partials/header.php";
?>
<?php if($message): ?><div class="alert danger"><?=e($message)?></div><?php endif;?>
<?php if($productCount===0): ?><div class="alert danger stock-no-sale">No stock available. Please contact the administrator to update stock before making a sale.</div><?php endif; ?>
<div class="panel"><h3>New Sale</h3><form method="post" id="saleForm">
<div class="form-grid sale-top-grid">
<div class="field"><label>Customer</label><select name="customer_id"><option value="0">Walk-in Customer</option><?php while($c=$customers->fetch_assoc()): ?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endwhile;?></select></div>
<div class="field"><label>Payment Method</label><select name="payment_method" required><option>Cash</option><option>Mobile Money</option><option>Bank</option></select></div>
</div>

<div class="sale-items" id="saleItems">
  <div class="sale-item-row" data-row="1">
    <div class="field"><label>Product</label>
      <select name="product_id[]" class="sale-product" required <?= $productCount===0 ? "disabled" : "" ?>>
        <?php foreach($productOptions as $p): $egg=$p['category']==='Eggs'; $package=$egg?'Tray':number_format((float)$p['package_size_kg'],0).' Kg Bag'; $stock=(int)$p['stock_quantity']; ?>
          <option value="<?=$p['id']?>" data-price="<?=$p['selling_price']?>" data-stock="<?=$stock?>"><?=e($p['name'])?> - <?=e($package)?> (Stock: <?=number_format($stock)?> <?= $egg?'tray':'bag'?><?=($stock===1?'':'s')?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field"><label>Quantity</label><input type="number" step="1" min="1" name="quantity[]" class="sale-qty" value="1" required <?= $productCount===0 ? "disabled" : "" ?>><small class="field-help">Eggs: whole trays (1 tray = 30 eggs). Rice/Flour: whole bags/packages.</small></div>
    <div class="field"><label>Unit Price (TZS)</label><input class="sale-price" readonly></div>
    <div class="field"><label>Line Total (TZS)</label><input class="sale-total" readonly></div>
    <button type="button" class="remove-sale-item" title="Remove product" aria-label="Remove product" style="display:none">×</button>
  </div>
</div>

<div class="sale-controls"><button type="button" class="btn secondary" id="addProductBtn" <?= $productCount===0 ? "disabled" : "" ?>>+ Add Another Product</button></div>
<div class="sale-summary"><span>Total Amount (TZS)</span><strong id="grandTotal">0</strong></div>
<div class="form-actions"><button class="btn primary" name="save_sale" <?= $productCount===0 ? "disabled" : "" ?>>Save Sale & Print Receipt</button></div>
</form></div>
<div class="panel" style="margin-top:20px"><h3>Recent Sales</h3><div class="recent-sales-table-scroll"><table class="table recent-sales-table"><tr><th>Sale No.</th><th>Date</th><th>Customer</th><th>Payment</th><th>Total</th><th></th></tr><?php while($r=$recent->fetch_assoc()): ?><tr><td><?=e($r['sale_number'])?></td><td><?=e($r['sale_date'])?></td><td><?=e($r['customer'])?></td><td><?=e($r['payment_method'])?></td><td>TZS <?=money($r['total_amount'])?></td><td><a class="btn btn-sm secondary" href="receipt.php?id=<?=$r['id']?>">Receipt</a></td></tr><?php endwhile;?></table></div>
<?php if($totalSales > $perPage): ?><div class="sales-pagination" aria-label="Sales pages"><?php if($page > 1): ?><a class="page-arrow" href="sales.php?page=<?=$page-1?>">‹</a><?php endif; ?><?php $start=max(1,$page-2);$end=min($totalPages,$page+2);if($start>1):?><a href="sales.php?page=1">1</a><?php if($start>2):?><span class="page-dots">…</span><?php endif;?><?php endif;for($i=$start;$i<=$end;$i++):?><a class="<?= $i===$page?'active':'' ?>" href="sales.php?page=<?=$i?>"><?=$i?></a><?php endfor;if($end<$totalPages):?><?php if($end<$totalPages-1):?><span class="page-dots">…</span><?php endif;?><a href="sales.php?page=<?=$totalPages?>"><?=$totalPages?></a><?php endif;if($page<$totalPages):?><a class="page-arrow" href="sales.php?page=<?=$page+1?>">›</a><?php endif;?></div><div class="sales-page-info">Showing <?=($offset+1)?>–<?=min($offset+$perPage,$totalSales)?> of <?=$totalSales?> sales</div><?php endif; ?></div></div>

<script>
(function(){
  const container=document.getElementById('saleItems');
  const addBtn=document.getElementById('addProductBtn');
  const grand=document.getElementById('grandTotal');
  if(!container) return;

  function format(n){return Number(n||0).toLocaleString('en-US',{minimumFractionDigits:0,maximumFractionDigits:2});}
  function refreshRow(row){
    const product=row.querySelector('.sale-product');
    const qty=row.querySelector('.sale-qty');
    const price=row.querySelector('.sale-price');
    const total=row.querySelector('.sale-total');
    if(!product || !product.options.length) return 0;
    const option=product.options[product.selectedIndex];
    const unitPrice=Number(option?.dataset.price||0);
    const lineTotal=(Number(qty?.value||0)*unitPrice);
    if(price) price.value=format(unitPrice);
    if(total) total.value=format(lineTotal);
    return lineTotal;
  }
  function refreshAll(){
    let total=0;
    container.querySelectorAll('.sale-item-row').forEach(row=>{total+=refreshRow(row);});
    if(grand) grand.textContent=format(total);
    container.querySelectorAll('.remove-sale-item').forEach(btn=>btn.style.display=container.querySelectorAll('.sale-item-row').length>1?'inline-flex':'none');
  }
  function bindRow(row){
    row.querySelector('.sale-product')?.addEventListener('change',()=>refreshRow(row));
    row.querySelector('.sale-qty')?.addEventListener('input',refreshAll);
    row.querySelector('.remove-sale-item')?.addEventListener('click',()=>{row.remove();refreshAll();});
    refreshRow(row);
  }
  const first=container.querySelector('.sale-item-row');
  if(first) bindRow(first);

  addBtn?.addEventListener('click',()=>{
    const source=container.querySelector('.sale-item-row');
    if(!source) return;
    const row=source.cloneNode(true);
    row.querySelectorAll('input').forEach(input=>{if(input.classList.contains('sale-qty')) input.value='1'; else input.value='';});
    row.querySelectorAll('select').forEach(select=>select.selectedIndex=0);
    row.querySelector('.remove-sale-item').style.display='inline-flex';
    container.appendChild(row);
    bindRow(row); refreshAll();
  });
  refreshAll();
})();
</script>
<style>
.sale-top-grid{grid-template-columns:1fr 1fr}
.sale-items{margin-top:4px}
.sale-item-row{position:relative;display:grid;grid-template-columns:2fr 1fr 1fr 1fr 36px;gap:12px;align-items:start;padding:16px 0;border-top:1px solid #e5ece9}
.sale-item-row:first-child{border-top:0}
.remove-sale-item{margin-top:29px;width:34px;height:34px;border:0;border-radius:8px;background:#fee2e2;color:#b91c1c;font-size:22px;line-height:1;align-items:center;justify-content:center;cursor:pointer}
.remove-sale-item:hover{background:#fecaca}
.sale-controls{display:flex;align-items:center;margin-top:3px}
.sale-summary{margin-top:14px;padding:12px 14px;border-radius:8px;background:#e8f6ef;display:flex;justify-content:space-between;align-items:center;color:#143d2f;font-size:15px}
.sale-summary strong{font-size:19px}
@media(max-width:900px){.sale-item-row{grid-template-columns:1fr 1fr}.remove-sale-item{position:absolute;right:0;top:8px;margin-top:0}.sale-item-row{padding-right:44px}}
@media(max-width:650px){.sale-top-grid{grid-template-columns:1fr}.sale-item-row{grid-template-columns:1fr}.remove-sale-item{top:8px}}
</style>
<?php require "partials/footer.php"; ?>
