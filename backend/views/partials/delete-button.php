<?php
/**
 * Small POST form that deletes a record after a confirmation prompt.
 *
 * @var string   $action  e.g. "/products/delete"
 * @var int      $id
 * @var string   $confirm confirmation question
 * @var int|null $page    current page to return to
 */
?>
<form method="post" action="<?= e($action) ?>" class="inline-form" data-confirm="<?= e($confirm) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$id ?>">
  <?php if (!empty($page)): ?><input type="hidden" name="page" value="<?= (int)$page ?>"><?php endif; ?>
  <button class="btn btn-sm danger-btn" type="submit">Delete</button>
</form>
