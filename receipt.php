<?php
require "auth.php"; $id=(int)($_GET['id']??0);
$s=$conn->query("SELECT s.*,COALESCE(c.name,'Walk-in Customer') customer FROM sales s LEFT JOIN customers c ON c.id=s.customer_id WHERE s.id=$id")->fetch_assoc();
if(!$s) die("Sale not found."); $items=$conn->query("SELECT si.*,p.name,p.unit FROM sale_items si JOIN products p ON p.id=si.product_id WHERE si.sale_id=$id");
?>
<!doctype html><html><head><meta charset="utf-8"><title>Receipt <?=$s['sale_number']?></title><style>
body{font-family:Arial;max-width:700px;margin:30px auto;padding:20px}.head{text-align:center}.line{border-top:1px solid #999;margin:15px 0}table{width:100%;border-collapse:collapse}th,td{padding:9px;border-bottom:1px solid #ddd;text-align:left}.right{text-align:right}.buttons{margin:20px 0}@media print{.buttons{display:none}body{margin:0}}
</style></head><body><div class="head"><h1>EGG SALES SYSTEM</h1><p>SALE RECEIPT</p></div>
<div class="line"></div><p><b>Sale No:</b> <?=e($s['sale_number'])?><br><b>Date:</b> <?=e($s['sale_date'])?><br><b>Customer:</b> <?=e($s['customer'])?></p>
<table><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr><?php while($i=$items->fetch_assoc()): ?><tr><td><?=e($i['name'])?> (<?=e($i['unit'])?>)</td><td><?=money($i['quantity'])?></td><td>TZS <?=money($i['unit_price'])?></td><td>TZS <?=money($i['total'])?></td></tr><?php endwhile;?></table>
<h2 class="right">TOTAL: TZS <?=money($s['total_amount'])?></h2><p class="right"><b>Payment:</b> <?=e($s['payment_method'])?></p>
<p style="text-align:center;margin-top:40px">Thank you for your business!</p><div class="buttons"><button onclick="window.print()">Print</button> <button onclick="location.href='sales.php'">Back to Sales</button></div></body></html>