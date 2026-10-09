<?php
/**
 * @var list<array<string,mixed>> $products
 * @var list<array<string,mixed>> $customers
 * @var list<array<string,mixed>> $recent
 * @var array $pager
 * @var list<string> $paymentMethods
 * @var string $defaultPayment
 * @var string|null $error
 */

$noStock = !$products;
$disabled = $noStock ? 'disabled' : '';
?>
<?php if ($error): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($noStock): ?><div class="alert danger stock-no-sale">No stock available. Please contact the administrator to update stock before making a sale.</div><?php endif; ?>

<div class="panel">
  <h3>New Sale</h3>
  <form method="post" action="/sales" id="saleForm">
    <?= csrf_field() ?>
    <div class="form-grid sale-top-grid">
      <div class="field">
        <label for="customer_id">Customer</label>
        <select name="customer_id" id="customer_id">
          <option value="0">Walk-in Customer</option>
          <?php foreach ($customers as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="payment_method">Payment Method</label>
        <select name="payment_method" id="payment_method" required>
          <?php foreach ($paymentMethods as $method): ?>
            <option <?= $method === $defaultPayment ? 'selected' : '' ?>><?= e($method) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="sale-items" id="saleItems">
      <div class="sale-item-row">
        <div class="field">
          <label>Product</label>
          <select name="product_id[]" class="sale-product" required <?= $disabled ?>>
            <?php foreach ($products as $p): ?>
              <option value="<?= (int)$p['id'] ?>" data-price="<?= e($p['selling_price']) ?>" data-stock="<?= (int)$p['stock_quantity'] ?>"><?= e($p['name']) ?> - <?= e(package_label($p)) ?> (Stock: <?= e(unit_label($p['category'], $p['stock_quantity'])) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Quantity</label>
          <input type="number" step="1" min="1" name="quantity[]" class="sale-qty" value="1" required <?= $disabled ?>>
          <small class="field-help">Eggs: whole trays (1 tray = 30 eggs). Rice/Flour: whole bags/packages.</small>
        </div>
        <div class="field"><label>Unit Price (TZS)</label><input class="sale-price" readonly tabindex="-1"></div>
        <div class="field"><label>Line Total (TZS)</label><input class="sale-total" readonly tabindex="-1"></div>
        <button type="button" class="remove-sale-item" title="Remove product" aria-label="Remove product" hidden>×</button>
      </div>
    </div>

    <div class="sale-controls"><button type="button" class="btn secondary" id="addProductBtn" <?= $disabled ?>>+ Add Another Product</button></div>
    <div class="sale-summary"><span>Total Amount (TZS)</span><strong id="grandTotal">0</strong></div>
    <div class="form-actions"><button class="btn primary" type="submit" <?= $disabled ?>>Save Sale &amp; Print Receipt</button></div>
  </form>
</div>

<div class="panel panel-spaced">
  <h3>Recent Sales</h3>
  <div class="table-wrap">
    <table class="table">
      <tr><th>SN</th><th>Sale No.</th><th>Date</th><th>Customer</th><th>Payment</th><th>Total</th><th></th></tr>
      <?php foreach ($recent as $i => $r): ?>
        <tr>
          <td><?= $pager['offset'] + $i + 1 ?></td>
          <td><?= e($r['sale_number']) ?></td>
          <td><?= e($r['sale_date']) ?></td>
          <td><?= e($r['customer']) ?></td>
          <td><?= e($r['payment_method']) ?></td>
          <td>TZS <?= money($r['total_amount']) ?></td>
          <td><a class="btn btn-sm secondary" href="<?= url('/receipt', ['id' => (int)$r['id']]) ?>">Receipt</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recent): ?><tr><td colspan="7">No sales recorded yet.</td></tr><?php endif; ?>
    </table>
  </div>
  <?php partial('partials/pagination', ['pager' => $pager, 'base' => '/sales', 'noun' => 'sales']); ?>
</div>
<script src="<?= asset('js/pos.js') ?>" defer></script>
