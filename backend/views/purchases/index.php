<?php
/**
 * @var list<array<string,mixed>> $products
 * @var list<array<string,mixed>> $suppliers
 * @var list<array<string,mixed>> $purchases
 * @var string|null $error
 * @var string|null $success
 */
?>
<?php partial('partials/alerts', compact('error', 'success')); ?>

<div class="panel">
  <h3>New Purchase</h3>
  <form method="post" action="/purchases">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="field">
        <label for="supplier_id">Supplier</label>
        <select name="supplier_id" id="supplier_id">
          <option value="0">Select Supplier</option>
          <?php foreach ($suppliers as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="product_id">Product</label>
        <select name="product_id" id="product_id" required>
          <?php foreach ($products as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?> - <?= e(package_label($p)) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="quantity">Quantity</label>
        <input type="number" step="1" min="1" name="quantity" id="quantity" required>
        <small class="field-help">Eggs: trays. Rice/Flour: bags/packages.</small>
      </div>
      <div class="field"><label for="unit_cost">Unit Cost (TZS)</label><input type="number" step="0.01" min="0" name="unit_cost" id="unit_cost" required></div>
    </div>
    <div class="form-actions"><button class="btn primary" type="submit" <?= $products ? '' : 'disabled' ?>>Save Purchase</button></div>
  </form>
</div>

<div class="panel panel-spaced">
  <h3>Purchase History</h3>
  <div class="table-wrap">
    <table class="table">
      <tr><th>Date</th><th>Supplier</th><th>Product</th><th>Qty</th><th>Total</th></tr>
      <?php foreach ($purchases as $r): ?>
        <tr>
          <td><?= e($r['purchase_date']) ?></td>
          <td><?= e($r['supplier']) ?></td>
          <td><?= e($r['product']) ?> (<?= e(package_label($r, ' Kg')) ?>)</td>
          <td><?= e(unit_label($r['category'], $r['quantity'])) ?></td>
          <td>TZS <?= money($r['total_amount']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$purchases): ?><tr><td colspan="5">No purchases recorded yet.</td></tr><?php endif; ?>
    </table>
  </div>
</div>
