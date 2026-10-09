<?php
/**
 * Flash message boxes.
 * @var string|null $error
 * @var string|null $success
 */
?>
<?php if (!empty($error)): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
