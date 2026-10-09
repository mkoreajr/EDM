<?php
/**
 * @var bool $used
 * @var string|null $message
 * @var string|null $error
 */

use App\Services\Password;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Password Recovery</title>
<link rel="stylesheet" href="<?= asset('css/recovery.css') ?>">
</head>
<body>
<div class="card">
  <h1>Admin Password Recovery</h1>
  <div class="sub">Reset the administrator password without deleting your business data.</div>
  <?php if ($message): ?><div class="alert ok"><?= e($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert bad"><?= e($error) ?></div><?php endif; ?>
  <?php if (!$used): ?>
  <form method="post" action="/admin-recovery">
    <?= csrf_field() ?>
    <label for="recovery_code">Recovery Code</label>
    <input id="recovery_code" name="recovery_code" type="password" required autocomplete="off">
    <label for="temporary_password">New Temporary Password</label>
    <div class="password-rules">Use 8+ characters with uppercase, lowercase, a number, and a special character.</div>
    <input id="temporary_password" name="temporary_password" type="password" minlength="8" pattern="<?= e(Password::HTML_PATTERN) ?>" title="Use at least 8 characters with uppercase, lowercase, a number, and a special character." required autocomplete="new-password">
    <button type="submit">Reset Admin Password</button>
  </form>
  <?php endif; ?>
  <a href="/">← Back to Sign In</a>
  <div class="note">After signing in, change the temporary password immediately. This recovery action does not delete sales, stock, products, customers or other records.</div>
</div>
</body>
</html>
