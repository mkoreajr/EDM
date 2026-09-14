<?php
require_once __DIR__ . "/config/database.php";
$code=trim($_GET['code']??'');
if($code===''){ http_response_code(400); exit('Receipt code is required.'); }

$esc=$conn->real_escape_string($code);
$s=$conn->query("SELECT s.*,COALESCE(c.name,'Walk-in Customer') AS customer,COALESCE(c.phone,'') AS customer_phone,
COALESCE(u.name,'Administrator') AS cashier
FROM sales s LEFT JOIN customers c ON c.id=s.customer_id LEFT JOIN users u ON u.id=s.created_by
WHERE s.sale_number='$esc' LIMIT 1")->fetch_assoc();
if(!$s){ http_response_code(404); exit('Receipt not found.'); }

$itemsRes=$conn->query("SELECT si.*,p.name,p.unit FROM sale_items si JOIN products p ON p.id=si.product_id WHERE si.sale_id=".(int)$s['id']." ORDER BY si.id");
$items=[];$subtotal=0;
if($itemsRes){while($row=$itemsRes->fetch_assoc()){$items[]=$row;$subtotal+=(float)$row['total'];}}
$discount=max(0,$subtotal-(float)$s['total_amount']);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Purchase Details — <?=e($s['sale_number'])?></title>
<style>
*{box-sizing:border-box}body{margin:0;background:linear-gradient(135deg,#eef8f3,#fff5ad);font-family:Arial,Helvetica,sans-serif;color:#18352b}
.wrap{width:min(720px,100%);margin:auto;padding:18px 12px 28px}.card{background:#fff;border:1px solid #dbe9e2;border-radius:14px;box-shadow:0 15px 40px rgba(0,70,48,.12);overflow:hidden}
.head{text-align:center;padding:24px 18px 15px}.head h1{margin:0;color:#00764d;font-size:21px}.sub{font-size:11px;color:#667a72;font-style:italic;margin-top:5px}
.title{border-top:1px dashed #bfcfca;border-bottom:1px dashed #bfcfca;margin:0 15px;padding:9px;text-align:center;font-weight:800;font-size:17px}
.meta{padding:14px 20px 5px;font-size:12px}.row{display:flex;justify-content:space-between;gap:12px;padding:3px 0}.row b{color:#4b6259}.row span{text-align:right}
table{width:calc(100% - 30px);margin:10px 15px;border-collapse:collapse;font-size:11px}th,td{padding:8px 5px;border-bottom:1px solid #e2ebe7}th{background:#eff7f3;text-align:left}td:not(:first-child),th:not(:first-child){text-align:right}
.total{margin:5px 15px;padding:10px;background:#e4f6ed;border-radius:7px;display:flex;justify-content:space-between;font-weight:800}.thanks{text-align:center;padding:17px;color:#526860;font-style:italic;font-size:11px}
</style></head><body><div class="wrap"><div class="card">
<div class="head"><h1>EDM KIENYEJI — EGG SHOP</h1><div class="sub">Fresh Eggs • Healthy Families • A Better Tomorrow</div></div>
<div class="title">PURCHASE DETAILS</div>
<div class="meta">
<div class="row"><b>Receipt No:</b><span><?=e($s['sale_number'])?></span></div>
<div class="row"><b>Date:</b><span><?=e($s['sale_date'])?></span></div>
<div class="row"><b>Cashier:</b><span><?=e($s['cashier'])?></span></div>
<div class="row"><b>Customer:</b><span><?=e($s['customer'])?></span></div>
<?php if($s['customer_phone']!==''):?><div class="row"><b>Phone:</b><span><?=e($s['customer_phone'])?></span></div><?php endif;?>
<div class="row"><b>Payment Method:</b><span><?=e($s['payment_method'])?></span></div>
</div>
<table><thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>
<?php $no=1;foreach($items as $i):?><tr><td><?=$no++?></td><td><?=e($i['name'])?> (<?=e($i['unit'])?>)</td><td><?=money($i['quantity'])?></td><td>TZS <?=money($i['unit_price'])?></td><td>TZS <?=money($i['total'])?></td></tr><?php endforeach;?>
</tbody></table>
<div class="total"><span>TOTAL (TSH)</span><span>TZS <?=money($s['total_amount'])?></span></div>
<div class="thanks">Thank you for shopping with us!<br>“Fresh Eggs • Healthy Families • A Better Tomorrow”</div>
</div></div></body></html>
