<?php
/**
 * @var array<string,mixed>|null $edit
 * @var list<array<string,mixed>> $products
 * @var array $pager
 * @var list<string> $categories
 * @var string|null $error
 * @var string|null $success
 */

$editCategory = $edit['category'] ?? 'Eggs';
$editName = $edit['name'] ?? $editCategory;
?>
<?php partial('partials/alerts', compact('error', 'success')); ?>

<div class="panel">
  <h3><?= $edit ? 'Edit Product' : 'Add Product' ?></h3>
  <form method="post" action="/products" id="productForm">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="form-grid">
      <div class="field">
        <label for="category">Product Type</label>
        <select name="category" id="category" required>
          <?php foreach ($categories as $c): ?><option value="<?= e($c) ?>" <?= $editCategory === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="product_name">Product Name</label>
        <select name="name" id="product_name" required>
          <?php foreach ($categories as $c): ?><option value="<?= e($c) ?>" <?= $editName === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field package-field">
        <label for="package_size_kg">Package Size (Kg)</label>
        <input type="number" step="1" min="1" max="20" name="package_size_kg" id="package_size_kg" value="<?= e($edit['package_size_kg'] ?? '') ?>">
        <small class="field-help">For Rice/Flour: choose 1 Kg up to 20 Kg.</small>
      </div>
      <div class="field">
        <label for="unit_display">Unit</label>
        <input id="unit_display" value="<?= e($edit['unit'] ?? 'Tray') ?>" readonly>
        <small class="field-help" id="unit_help">Egg stock is counted by tray. 1 tray = 30 eggs.</small>
      </div>
      <div class="field">
        <label for="selling_price">Selling Price (TZS)</label>
        <input type="number" step="0.01" min="0" name="selling_price" id="selling_price" value="<?= e($edit['selling_price'] ?? 0) ?>" required>
        <small class="field-help">For Rice/Flour this is the price per bag/package.</small>
      </div>
      <div class="field">
        <label id="stock_label" for="stock_quantity">Stock Quantity (Trays)</label>
        <input type="number" step="1" min="0" name="stock_quantity" id="stock_quantity" value="<?= e($edit['stock_quantity'] ?? 0) ?>" required>
        <small class="field-help" id="stock_help">Enter whole trays. Example: 1 = 1 tray = 30 eggs.</small>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn primary" type="submit">Save Product</button>
      <?php if ($edit): ?><a class="btn secondary" href="/products">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="panel panel-spaced">
  <div class="toolbar"><h3>Product List</h3></div>
  <div class="table-wrap">
    <table class="table">
      <tr><th>SN</th><th>Product</th><th>Type</th><th>Package</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
      <?php foreach ($products as $i => $r): ?>
        <tr>
          <td><?= $pager['offset'] + $i + 1 ?></td>
          <td><?= e($r['name']) ?></td>
          <td><?= e($r['category']) ?></td>
          <td><?= e(package_label($r, ' Kg')) ?></td>
          <td>TZS <?= money($r['selling_price']) ?></td>
          <td><?= e(unit_label($r['category'], $r['stock_quantity'])) ?></td>
          <td class="actions">
            <a class="btn btn-sm secondary" href="<?= url('/products', ['edit' => (int)$r['id'], 'page' => $pager['page']]) ?>">Edit</a>
            <?php partial('partials/delete-button', ['action' => '/products/delete', 'id' => $r['id'], 'confirm' => 'Delete this product?', 'page' => $pager['page']]); ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$products): ?><tr><td colspan="7">No products yet.</td></tr><?php endif; ?>
    </table>
  </div>
  <?php partial('partials/pagination', ['pager' => $pager, 'base' => '/products', 'noun' => 'products']); ?>
</div>
<script src="<?= asset('js/products.js') ?>" defer></script>
