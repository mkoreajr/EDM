<?php
/**
 * @var list<array<string,mixed>> $rows
 */
?>
<div class="panel">
  <div class="toolbar"><h3>Current Inventory</h3><a class="btn primary btn-sm" href="/purchases">+ Add Stock</a></div>
  <div class="table-wrap">
    <table class="table">
      <tr><th>Product</th><th>Type</th><th>Package</th><th>Purchased</th><th>Sold</th><th>Current Stock</th></tr>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['name']) ?></td>
          <td><?= e($r['category']) ?></td>
          <td><?= e(package_label($r, ' Kg')) ?></td>
          <td><?= e(unit_label($r['category'], $r['purchased'])) ?></td>
          <td><?= e(unit_label($r['category'], $r['sold'])) ?></td>
          <td class="<?= (float)$r['stock_quantity'] <= 0 ? 'low' : '' ?>"><?= e(unit_label($r['category'], $r['stock_quantity'])) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6">No products yet.</td></tr><?php endif; ?>
    </table>
  </div>
</div>
