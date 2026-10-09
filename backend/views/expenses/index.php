<?php
/**
 * @var list<array<string,mixed>> $expenses
 * @var string|null $error
 * @var string|null $success
 */
?>
<?php partial('partials/alerts', compact('error', 'success')); ?>

<div class="panel">
  <h3>Add Expense</h3>
  <form method="post" action="/expenses">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="field"><label for="x_name">Expense Name</label><input id="x_name" name="expense_name" maxlength="100" required></div>
      <div class="field"><label for="x_amount">Amount (TZS)</label><input id="x_amount" type="number" step="0.01" min="0" name="amount" required></div>
      <div class="field"><label for="x_date">Date</label><input id="x_date" type="date" name="expense_date" value="<?= date('Y-m-d') ?>" required></div>
      <div class="field"><label for="x_desc">Description</label><textarea id="x_desc" name="description"></textarea></div>
    </div>
    <div class="form-actions"><button class="btn primary" type="submit">Save Expense</button></div>
  </form>
</div>

<div class="panel panel-spaced">
  <h3>Expense History</h3>
  <div class="table-wrap">
    <table class="table">
      <tr><th>Date</th><th>Expense</th><th>Description</th><th>Amount</th><th></th></tr>
      <?php foreach ($expenses as $r): ?>
        <tr>
          <td><?= e($r['expense_date']) ?></td>
          <td><?= e($r['expense_name']) ?></td>
          <td><?= e($r['description']) ?></td>
          <td>TZS <?= money($r['amount']) ?></td>
          <td><?php partial('partials/delete-button', ['action' => '/expenses/delete', 'id' => $r['id'], 'confirm' => 'Delete expense?']); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$expenses): ?><tr><td colspan="5">No expenses recorded yet.</td></tr><?php endif; ?>
    </table>
  </div>
</div>
