<?php
/**
 * Sales report as an Excel-compatible HTML table (.xls download).
 *
 * @var list<array<string,mixed>> $rows
 * @var array<string,mixed> $summary
 * @var string $from
 * @var string $to
 * @var string $downloadedBy
 */
?>
<html>
<head>
<meta charset="UTF-8">
<style>table{border-collapse:collapse;font-family:Arial}th,td{border:1px solid #bbb;padding:7px}th{background:#eaf5ef}.num{text-align:right}</style>
</head>
<body>
<h2>MSINDA FOOD SHOP</h2>
<h3>Sales Report</h3>
<p>
  <b>Report Period:</b> <?= e($from) ?> to <?= e($to) ?><br>
  <b>Downloaded By:</b> <?= e($downloadedBy) ?><br>
  <b>Total Sales:</b> <?= number_format((int)$summary['sale_count']) ?> &nbsp; <b>Total Amount:</b> TZS <?= money($summary['total']) ?>
</p>
<table>
  <tr><th>SN</th><th>Sale No.</th><th>Date</th><th>Customer</th><th>Cashier</th><th>Product</th><th>Category</th><th>Quantity</th><th>Unit Price</th><th>Line Total</th><th>Payment Method</th></tr>
  <?php foreach ($rows as $i => $x): ?>
  <tr>
    <td><?= $i + 1 ?></td>
    <td><?= e($x['sale_number']) ?></td>
    <td><?= e($x['sale_date']) ?></td>
    <td><?= e($x['customer']) ?></td>
    <td><?= e($x['cashier']) ?></td>
    <td><?= e($x['product']) ?></td>
    <td><?= e($x['category']) ?></td>
    <td class="num"><?= e($x['quantity']) ?></td>
    <td class="num">TZS <?= money($x['unit_price']) ?></td>
    <td class="num">TZS <?= money($x['total']) ?></td>
    <td><?= e($x['payment_method']) ?></td>
  </tr>
  <?php endforeach; ?>
  <tr><td colspan="9"><b>REPORT TOTAL</b></td><td class="num"><b>TZS <?= money($summary['total']) ?></b></td><td></td></tr>
</table>
</body>
</html>
