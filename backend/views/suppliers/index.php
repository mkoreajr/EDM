<?php
/**
 * @var array<string,mixed>|null $edit
 * @var list<array<string,mixed>> $suppliers
 * @var string|null $error
 * @var string|null $success
 */
?>
<?php partial('partials/alerts', compact('error', 'success')); ?>

<div class="panel">
  <h3><?= $edit ? 'Edit Supplier' : 'Add Supplier' ?></h3>
  <form method="post" action="/suppliers">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="form-grid">
      <div class="field"><label for="s_name">Supplier Name</label><input id="s_name" name="name" value="<?= e($edit['name'] ?? '') ?>" maxlength="100" required></div>
      <div class="field"><label for="s_phone">Phone</label><input id="s_phone" name="phone" type="tel" value="<?= e($edit['phone'] ?? '') ?>" maxlength="30"></div>
      <div class="field"><label for="s_address">Address</label><input id="s_address" name="address" value="<?= e($edit['address'] ?? '') ?>" maxlength="255"></div>
    </div>
    <div class="form-actions">
      <button class="btn primary" type="submit">Save Supplier</button>
      <?php if ($edit): ?><a class="btn secondary" href="/suppliers">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="panel panel-spaced">
  <h3>Supplier List</h3>
  <div class="table-wrap">
    <table class="table">
      <tr><th>Name</th><th>Phone</th><th>Address</th><th>Actions</th></tr>
      <?php foreach ($suppliers as $r): ?>
        <tr>
          <td><?= e($r['name']) ?></td>
          <td><?= e($r['phone']) ?></td>
          <td><?= e($r['address']) ?></td>
          <td class="actions">
            <a class="btn btn-sm secondary" href="<?= url('/suppliers', ['edit' => (int)$r['id']]) ?>">Edit</a>
            <?php partial('partials/delete-button', ['action' => '/suppliers/delete', 'id' => $r['id'], 'confirm' => 'Delete supplier?']); ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$suppliers): ?><tr><td colspan="4">No suppliers yet.</td></tr><?php endif; ?>
    </table>
  </div>
</div>
