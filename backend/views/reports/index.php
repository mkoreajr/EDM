<?php
/**
 * @var string $from
 * @var string $to
 * @var array<string,mixed> $totals
 * @var list<array<string,mixed>> $items
 * @var array $pager
 */

use App\Core\Auth;

$period = ['from' => $from, 'to' => $to];
?>
<div class="panel">
  <form class="report-filter" method="get" action="/reports">
    <div class="field"><label for="from">From</label><input type="date" id="from" name="from" value="<?= e($from) ?>"></div>
    <div class="field"><label for="to">To</label><input type="date" id="to" name="to" value="<?= e($to) ?>"></div>
    <button class="btn primary" type="submit">Filter Report</button>
  </form>
  <?php if (Auth::isAdmin()): ?>
  <div class="report-downloads">
    <a class="btn" href="<?= e(url('/reports/export', ['format' => 'excel'] + $period)) ?>">Download Excel</a>
    <a class="btn" href="<?= e(url('/reports/export', ['format' => 'pdf'] + $period)) ?>">Download PDF</a>
  </div>
  <?php endif; ?>
</div>

<div class="cards panel-spaced">
  <div class="card"><div class="label">Sales Transactions</div><div class="value"><?= number_format((int)$totals['sale_count']) ?></div></div>
  <div class="card"><div class="label">Sales Total</div><div class="value">TZS <?= money($totals['sales']) ?></div></div>
  <div class="card"><div class="label">Purchases</div><div class="value">TZS <?= money($totals['purchases']) ?></div></div>
  <div class="card"><div class="label">Expenses</div><div class="value">TZS <?= money($totals['expenses']) ?></div></div>
</div>

<div class="panel">
  <div class="report-heading">
    <h3>Sales List</h3>
    <p><?= e($from) ?> to <?= e($to) ?> · <?= number_format((int)$totals['sale_count']) ?> sales · Total TZS <?= money($totals['sales']) ?></p>
  </div>
  <div class="table-wrap report-table">
    <table class="table">
      <tr><th>SN</th><th>Sale No.</th><th>Date</th><th>Customer</th><th>Cashier</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Payment</th></tr>
      <?php if (!$items): ?>
        <tr><td colspan="10">No sales found for the selected date range.</td></tr>
      <?php else: ?>
        <?php foreach ($items as $i => $x): ?>
          <tr>
            <td><?= $pager['offset'] + $i + 1 ?></td>
            <td><?= e($x['sale_number']) ?></td>
            <td><?= e($x['sale_date']) ?></td>
            <td><?= e($x['customer']) ?></td>
            <td><?= e($x['cashier']) ?></td>
            <td><?= e($x['product']) ?></td>
            <td><?= qty($x['quantity']) ?></td>
            <td>TZS <?= money($x['unit_price']) ?></td>
            <td>TZS <?= money($x['total']) ?></td>
            <td><?= e($x['payment_method']) ?></td>
          </tr>
        <?php endforeach; ?>
        <tr><td colspan="8"><b>REPORT TOTAL</b></td><td><b>TZS <?= money($totals['sales']) ?></b></td><td></td></tr>
      <?php endif; ?>
    </table>
  </div>
  <?php partial('partials/pagination', ['pager' => $pager, 'base' => '/reports', 'query' => $period, 'noun' => 'sale items']); ?>
</div>
