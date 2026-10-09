<?php
/**
 * @var bool $required
 * @var string|null $error
 * @var string|null $success
 */

$fields = static function (): void {
    partial('partials/password-input', ['id' => 'current_password', 'name' => 'current_password', 'label' => 'Current Password', 'autocomplete' => 'current-password']);
    partial('partials/password-input', ['id' => 'new_password', 'name' => 'new_password', 'label' => 'New Password', 'autocomplete' => 'new-password', 'strong' => true]);
    partial('partials/password-input', ['id' => 'confirm_password', 'name' => 'confirm_password', 'label' => 'Confirm New Password', 'autocomplete' => 'new-password', 'strong' => true]);
};
?>
<?php if ($required): ?>
<div class="password-required-page">
  <div class="password-required-card">
    <div class="password-required-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 10V7a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
    </div>
    <div class="welcome-kicker">SECURITY</div>
    <h1>Change Your Password</h1>
    <p class="password-required-subtitle">For security, you must change your temporary password before entering the system.</p>
    <p class="password-rules">Password must contain: <b>8+ characters</b>, uppercase, lowercase, number, and special character.</p>
    <?php if ($error): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/account/password" class="password-required-form">
      <?= csrf_field() ?>
      <?php $fields(); ?>
      <button class="btn primary password-required-submit" type="submit">Set New Password</button>
    </form>
  </div>
</div>
<?php else: ?>
<div class="page-intro centered-page-intro"><div><div class="welcome-kicker">SECURITY</div><h1>Change Password</h1><p class="muted">Update your account password securely.</p></div></div>
<div class="password-rules">Password must contain: <b>8+ characters</b>, uppercase, lowercase, number, and special character.</div>
<div class="panel password-panel">
  <?php if ($error): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?>
    <div class="alert success"><?= e($success) ?></div>
    <div class="form-actions"><a class="btn primary" href="/dashboard">Continue to Dashboard</a></div>
  <?php else: ?>
  <form method="post" action="/account/password" class="password-form">
    <?= csrf_field() ?>
    <?php $fields(); ?>
    <div class="form-actions"><button class="btn primary" type="submit">Change Password</button><a class="btn secondary" href="/dashboard">Cancel</a></div>
  </form>
  <?php endif; ?>
</div>
<?php endif; ?>
