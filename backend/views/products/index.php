<?php
/**
 * @var array<string,mixed>|null $edit
 * @var list<array<string,mixed>> $products
 * @var array $pager
 * @var list<string> $categories
 * @var string|null $error
 * @var string|null $success
 */

$editCategory = $edit['category'] ?? 'Rice';
$renameIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4l10-10-4-4L4 16zM13 7l4 4"/></svg>';
?>
<div class="page-head">
  <div><h1>Products</h1><p>Add products, set prices and stock, and rename products when the source changes.</p></div>
</div>

<?php partial('partials/alerts', compact('error', 'success')); ?>

<div class="panel">
  <div class="panel-head">
    <div><h3><?= $edit ? 'Edit Product' : 'Add Product' ?></h3><p>The name you enter here is what customers see in the online shop.</p></div>
  </div>
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
        <input type="text" name="name" id="product_name" maxlength="100" placeholder="e.g. Mbeya Rice" value="<?= e($edit['name'] ?? '') ?>" required>
        <small class="field-help">Shown to customers in the shop. You can rename it any time.</small>
      </div>
      <div class="field package-field">
        <label for="package_size_kg">Package Size (Kg)</label>
        <input type="number" step="1" min="1" max="20" name="package_size_kg" id="package_size_kg" value="<?= e($edit['package_size_kg'] ?? '') ?>">
        <small class="field-help">For Rice/Flour: choose 1 Kg up to 20 Kg.</small>
      </div>
      <div class="field">
        <label for="unit_display">Unit</label>
        <input id="unit_display" value="<?= e($edit['unit'] ?? 'Bag') ?>" readonly>
        <small class="field-help" id="unit_help">Rice/Flour stock is counted by bags/packages.</small>
      </div>
      <div class="field">
        <label for="selling_price">Selling Price (TZS)</label>
        <input type="number" step="0.01" min="0" name="selling_price" id="selling_price" value="<?= e($edit['selling_price'] ?? 0) ?>" required>
        <small class="field-help">For Rice/Flour this is the price per bag/package.</small>
      </div>
      <div class="field">
        <label id="stock_label" for="stock_quantity">Stock Quantity (Bags)</label>
        <input type="number" step="1" min="0" name="stock_quantity" id="stock_quantity" value="<?= e($edit['stock_quantity'] ?? 0) ?>" required>
        <small class="field-help" id="stock_help">Enter whole bags/packages. Each bag uses the selected package size (1–20 Kg).</small>
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
      <tr><th>SN</th><th>Product</th><th>Type</th><th>Package</th><th>Price</th><th>Stock</th><th></th></tr>
      <?php foreach ($products as $i => $r): ?>
        <tr>
          <td><?= $pager['offset'] + $i + 1 ?></td>
          <td><strong><?= e($r['name']) ?></strong></td>
          <td><span class="tag <?= e(strtolower($r['category'])) ?>"><?= e($r['category']) ?></span></td>
          <td><?= e(package_label($r, ' Kg')) ?></td>
          <td>TZS <?= money($r['selling_price']) ?></td>
          <td class="<?= (float)$r['stock_quantity'] <= 0 ? 'stock-zero' : '' ?>"><?= e(unit_label($r['category'], $r['stock_quantity'])) ?></td>
          <td class="actions">
            <button class="btn btn-sm gold" type="button" data-rename-id="<?= (int)$r['id'] ?>" data-rename-name="<?= e($r['name']) ?>"><?= $renameIcon ?>Rename</button>
            <a class="btn btn-sm secondary" href="<?= url('/products', ['edit' => (int)$r['id'], 'page' => $pager['page']]) ?>">Edit</a>
            <?php partial('partials/delete-button', ['action' => '/products/delete', 'id' => $r['id'], 'confirm' => 'Delete this product?', 'page' => $pager['page']]); ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$products): ?><tr><td colspan="7" class="table-empty">No products yet.</td></tr><?php endif; ?>
    </table>
  </div>
  <?php partial('partials/pagination', ['pager' => $pager, 'base' => '/products', 'noun' => 'products']); ?>
</div>

<dialog class="app-dialog" id="renameDialog" aria-labelledby="renameTitle">
  <form method="post" action="/products/rename">
    <?= csrf_field() ?>
    <input type="hidden" name="id" id="renameId">
    <input type="hidden" name="page" value="<?= (int)$pager['page'] ?>">
    <div class="dialog-icon"><?= $renameIcon ?></div>
    <h3 id="renameTitle">Rename product</h3>
    <p>Use this when the product's source changes, for example a new rice supplier.</p>
    <div class="dialog-current"><span>Current name</span><strong id="renameCurrent"></strong></div>
    <div class="dialog-field">
      <label for="renameName">New name</label>
      <input type="text" name="name" id="renameName" maxlength="100" required>
    </div>
    <div class="dialog-note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg><span>The new name appears in the customer shop straight away. Past sales, receipts, orders and reports keep the old name.</span></div>
    <div class="dialog-actions">
      <button class="btn secondary" type="button" id="renameCancel">Cancel</button>
      <button class="btn primary" type="submit">Save new name</button>
    </div>
  </form>
</dialog>
<script src="<?= asset('js/products.js') ?>" defer></script>
