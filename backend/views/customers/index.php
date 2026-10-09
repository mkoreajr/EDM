<?php
/**
 * @var array<string,mixed>|null $edit
 * @var list<array<string,mixed>> $customers
 * @var array $pager
 * @var string|null $error
 * @var string|null $success
 */
?>
<?php partial('partials/alerts', compact('error', 'success')); ?>

<div class="panel">
  <h3><?= $edit ? 'Edit Customer' : 'Add Customer' ?></h3>
  <form method="post" action="/customers">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="form-grid">
      <div class="field"><label for="c_name">Customer Name</label><input id="c_name" name="name" value="<?= e($edit['name'] ?? '') ?>" maxlength="100" required></div>
      <div class="field"><label for="c_phone">Phone</label><input id="c_phone" name="phone" type="tel" value="<?= e($edit['phone'] ?? '') ?>" maxlength="30"></div>
      <div class="field"><label for="c_address">Address</label><input id="c_address" name="address" value="<?= e($edit['address'] ?? '') ?>" maxlength="255"></div>
    </div>
    <div class="form-actions">
      <button class="btn primary" type="submit">Save Customer</button>
      <?php if ($edit): ?><a class="btn secondary" href="/customers">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="panel panel-spaced">
  <h3>Customer List</h3>
  <div class="table-wrap">
    <table class="table">
      <tr><th>SN</th><th>Name</th><th>Phone</th><th>Address</th><th>Actions</th></tr>
      <?php foreach ($customers as $i => $r): ?>
        <tr>
          <td><?= $pager['offset'] + $i + 1 ?></td>
          <td><?= e($r['name']) ?></td>
          <td><?= e($r['phone']) ?></td>
          <td><?= e($r['address']) ?></td>
          <td class="actions">
            <a class="btn btn-sm secondary" href="<?= url('/customers', ['edit' => (int)$r['id'], 'page' => $pager['page']]) ?>">Edit</a>
            <?php partial('partials/delete-button', ['action' => '/customers/delete', 'id' => $r['id'], 'confirm' => 'Delete customer?', 'page' => $pager['page']]); ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$customers): ?><tr><td colspan="5">No customers yet.</td></tr><?php endif; ?>
    </table>
  </div>
  <?php partial('partials/pagination', ['pager' => $pager, 'base' => '/customers', 'noun' => 'customers']); ?>
</div>
