<?php
require "auth.php"; $pageTitle="Reports"; $active="reports";
$from=$_GET['from']??date('Y-m-01');$to=$_GET['to']??date('Y-m-d');
$st=$conn->prepare("SELECT COALESCE(SUM(total_amount),0) x FROM sales WHERE sale_date BETWEEN ? AND ?");$st->bind_param("ss",$from,$to);$st->execute();$sales=(float)$st->get_result()->fetch_assoc()['x'];
$st=$conn->prepare("SELECT COALESCE(SUM(amount),0) x FROM expenses WHERE expense_date BETWEEN ? AND ?");$st->bind_param("ss",$from,$to);$st->execute();$expenses=(float)$st->get_result()->fetch_assoc()['x'];
$st=$conn->prepare("SELECT COALESCE(SUM(total_amount),0) x FROM purchases WHERE purchase_date BETWEEN ? AND ?");$st->bind_param("ss",$from,$to);$st->execute();$purchases=(float)$st->get_result()->fetch_assoc()['x'];
$cash=$conn->query("SELECT COALESCE(SUM(total_amount),0)x FROM sales WHERE sale_date BETWEEN '".$conn->real_escape_string($from)."' AND '".$conn->real_escape_string($to)."' AND payment_method='Cash'")->fetch_assoc()['x'];
$mobile=$conn->query("SELECT COALESCE(SUM(total_amount),0)x FROM sales WHERE sale_date BETWEEN '".$conn->real_escape_string($from)."' AND '".$conn->real_escape_string($to)."' AND payment_method='Mobile Money'")->fetch_assoc()['x'];
$bank=$conn->query("SELECT COALESCE(SUM(total_amount),0)x FROM sales WHERE sale_date BETWEEN '".$conn->real_escape_string($from)."' AND '".$conn->real_escape_string($to)."' AND payment_method='Bank'")->fetch_assoc()['x'];
require "partials/header.php"; ?>
<div class="panel"><form class="report-filter"><div class="field"><label>From</label><input type="date" name="from" value="<?=e($from)?>"></div><div class="field"><label>To</label><input type="date" name="to" value="<?=e($to)?>"></div><button class="btn primary">Filter Report</button></form></div>
<div class="cards" style="margin-top:20px"><div class="card"><div class="label">Sales</div><div class="value">TZS <?=money($sales)?></div></div><div class="card"><div class="label">Purchases</div><div class="value">TZS <?=money($purchases)?></div></div><div class="card"><div class="label">Expenses</div><div class="value">TZS <?=money($expenses)?></div></div><div class="card"><div class="label">Net Result</div><div class="value">TZS <?=money($sales-$expenses)?></div></div></div>
<div class="panel"><h3>Sales by Payment Method</h3><table class="table"><tr><th>Payment Method</th><th>Amount</th></tr><tr><td>Cash</td><td>TZS <?=money($cash)?></td></tr><tr><td>Mobile Money</td><td>TZS <?=money($mobile)?></td></tr><tr><td>Bank</td><td>TZS <?=money($bank)?></td></tr></table></div>
<?php require "partials/footer.php"; ?>