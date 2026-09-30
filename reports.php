<?php
require "auth.php"; $pageTitle="Reports"; $active="reports";
$from=$_GET['from']??date('Y-m-01'); $to=$_GET['to']??date('Y-m-d');
if($from>$to){$tmp=$from;$from=$to;$to=$tmp;}
$st=$conn->prepare("SELECT COALESCE(SUM(total_amount),0) x FROM sales WHERE sale_date BETWEEN ? AND ?");$st->bind_param("ss",$from,$to);$st->execute();$sales=(float)$st->get_result()->fetch_assoc()['x'];
$st=$conn->prepare("SELECT COUNT(*) x FROM sales WHERE sale_date BETWEEN ? AND ?");$st->bind_param("ss",$from,$to);$st->execute();$saleCount=(int)$st->get_result()->fetch_assoc()['x'];
$st=$conn->prepare("SELECT COALESCE(SUM(amount),0) x FROM expenses WHERE expense_date BETWEEN ? AND ?");$st->bind_param("ss",$from,$to);$st->execute();$expenses=(float)$st->get_result()->fetch_assoc()['x'];
$st=$conn->prepare("SELECT COALESCE(SUM(total_amount),0) x FROM purchases WHERE purchase_date BETWEEN ? AND ?");$st->bind_param("ss",$from,$to);$st->execute();$purchases=(float)$st->get_result()->fetch_assoc()['x'];
$perPage=15; $currentPage=max(1,(int)($_GET['page']??1));
$st=$conn->prepare("SELECT COUNT(*) x FROM sale_items si JOIN sales s ON s.id=si.sale_id WHERE s.sale_date BETWEEN ? AND ?");$st->bind_param('ss',$from,$to);$st->execute();$itemCount=(int)$st->get_result()->fetch_assoc()['x']; $totalPages=max(1,(int)ceil($itemCount/$perPage)); if($currentPage>$totalPages)$currentPage=$totalPages; $offset=($currentPage-1)*$perPage;
$st=$conn->prepare("SELECT s.sale_number,s.sale_date,COALESCE(c.name,'Walk-in Customer') customer,u.name cashier,p.name product,si.quantity,si.unit_price,si.total,s.payment_method FROM sale_items si JOIN sales s ON s.id=si.sale_id JOIN products p ON p.id=si.product_id LEFT JOIN customers c ON c.id=s.customer_id JOIN users u ON u.id=s.created_by WHERE s.sale_date BETWEEN ? AND ? ORDER BY s.sale_date ASC,s.id ASC,si.id ASC LIMIT ? OFFSET ?");$st->bind_param('ssii',$from,$to,$perPage,$offset);$st->execute();$items=[];$rr=$st->get_result();while($x=$rr->fetch_assoc())$items[]=$x;
require "partials/header.php"; ?>
<div class="panel">
<form class="report-filter" method="get">
 <div class="field"><label>From</label><input type="date" name="from" value="<?=e($from)?>"></div>
 <div class="field"><label>To</label><input type="date" name="to" value="<?=e($to)?>"></div>
 <button class="btn primary" type="submit">Filter Report</button>
</form>
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
 <a class="btn" href="report_export.php?format=excel&amp;from=<?=e($from)?>&amp;to=<?=e($to)?>">Download Excel</a>
 <a class="btn" href="report_export.php?format=pdf&amp;from=<?=e($from)?>&amp;to=<?=e($to)?>">Download PDF</a>
</div>
</div>
<div class="cards" style="margin-top:20px"><div class="card"><div class="label">Sales Transactions</div><div class="value"><?=number_format($saleCount)?></div></div><div class="card"><div class="label">Sales Total</div><div class="value">TZS <?=money($sales)?></div></div><div class="card"><div class="label">Purchases</div><div class="value">TZS <?=money($purchases)?></div></div><div class="card"><div class="label">Expenses</div><div class="value">TZS <?=money($expenses)?></div></div></div>
<div class="panel"><div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap"><div><h3 style="margin:0">Sales List</h3><p style="margin:5px 0 0;color:#60736d"><?=e($from)?> to <?=e($to)?> · <?=number_format($saleCount)?> sales · Total TZS <?=money($sales)?></p></div></div>
<div style="overflow:auto;margin-top:14px"><table class="table"><tr><th>SN</th><th>Sale No.</th><th>Date</th><th>Customer</th><th>Cashier</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Payment</th></tr>
<?php if(!$items): ?><tr><td colspan="10">No sales found for the selected date range.</td></tr><?php else:$sn=$offset+1;foreach($items as $x): ?><tr><td><?=$sn++?></td><td><?=e($x['sale_number'])?></td><td><?=e($x['sale_date'])?></td><td><?=e($x['customer'])?></td><td><?=e($x['cashier'])?></td><td><?=e($x['product'])?></td><td><?=e($x['quantity'])?></td><td>TZS <?=money($x['unit_price'])?></td><td>TZS <?=money($x['total'])?></td><td><?=e($x['payment_method'])?></td></tr><?php endforeach; ?><tr><td colspan="8"><b>REPORT TOTAL</b></td><td><b>TZS <?=money($sales)?></b></td><td></td></tr><?php endif; ?></table></div></div>
<?php if($totalPages>1): ?>
<div style="display:flex;justify-content:center;align-items:center;gap:6px;flex-wrap:wrap;margin-top:18px">
 <?php if($currentPage>1): ?><a class="btn" href="reports.php?from=<?=e($from)?>&to=<?=e($to)?>&page=<?=$currentPage-1?>">Previous</a><?php endif; ?>
 <?php for($pg=1;$pg<=$totalPages;$pg++): ?>
  <a class="btn <?= $pg===$currentPage?'primary':'' ?>" href="reports.php?from=<?=e($from)?>&to=<?=e($to)?>&page=<?=$pg?>"><?=$pg?></a>
 <?php endfor; ?>
 <?php if($currentPage<$totalPages): ?><a class="btn" href="reports.php?from=<?=e($from)?>&to=<?=e($to)?>&page=<?=$currentPage+1?>">Next</a><?php endif; ?>
</div>
<?php endif; ?>
<?php require "partials/footer.php"; ?>
