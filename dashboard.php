<?php
require "auth.php"; $pageTitle="Dashboard"; $active="dashboard"; require "partials/header.php";
$sales=(float)$conn->query("SELECT COALESCE(SUM(total_amount),0) x FROM sales")->fetch_assoc()['x'];
$expenses=(float)$conn->query("SELECT COALESCE(SUM(amount),0) x FROM expenses")->fetch_assoc()['x'];
$stock=(float)$conn->query("SELECT COALESCE(SUM(stock_quantity),0) x FROM products")->fetch_assoc()['x'];
$profit=$sales-$expenses;
$today=(float)$conn->query("SELECT COALESCE(SUM(total_amount),0) x FROM sales WHERE sale_date=CURDATE()")->fetch_assoc()['x'];
$recent=$conn->query("SELECT s.sale_number,s.sale_date,s.payment_method,s.total_amount,COALESCE(c.name,'Walk-in Customer') customer FROM sales s LEFT JOIN customers c ON c.id=s.customer_id ORDER BY s.id DESC LIMIT 8");
?>
<div class="cards">
<div class="card"><div class="label">Total Sales</div><div class="value">TZS <?=money($sales)?></div></div>
<div class="card"><div class="label">Total Expenses</div><div class="value">TZS <?=money($expenses)?></div></div>
<div class="card"><div class="label">Net Profit</div><div class="value">TZS <?=money($profit)?></div></div>
<div class="card"><div class="label">Eggs In Stock</div><div class="value"><?=money($stock)?></div></div>
</div>
<div class="grid2">
<div class="panel"><h3>Today's Sales</h3><div class="value" style="font-size:32px">TZS <?=money($today)?></div><p class="muted">All payment methods combined</p></div>
<div class="panel"><h3>Quick Actions</h3><div class="actions"><a class="btn primary" href="sales.php">+ New Sale</a><a class="btn secondary" href="products.php">Products</a><a class="btn secondary" href="purchases.php">Purchase Stock</a></div></div>
</div>
<div class="panel" style="margin-top:20px"><div class="toolbar"><h3>Recent Sales</h3><a href="sales.php">View all</a></div>
<div class="table-wrap"><table class="table"><tr><th>Sale No.</th><th>Date</th><th>Customer</th><th>Payment</th><th>Total</th></tr>
<?php while($r=$recent->fetch_assoc()): ?><tr><td><?=e($r['sale_number'])?></td><td><?=e($r['sale_date'])?></td><td><?=e($r['customer'])?></td><td><?=e($r['payment_method'])?></td><td>TZS <?=money($r['total_amount'])?></td></tr><?php endwhile; ?>
</table></div></div>
<?php require "partials/footer.php"; ?>