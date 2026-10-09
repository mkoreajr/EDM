<?php
/**
 * Password field with a show/hide button.
 *
 * @var string $id
 * @var string $name
 * @var string $label
 * @var string $autocomplete
 * @var bool   $strong  add the strength pattern (new passwords)
 */

use App\Services\Password;

$strong = $strong ?? false;
?>
<div class="field">
  <label for="<?= e($id) ?>"><?= e($label) ?></label>
  <div class="password-field-wrap">
    <input type="password" id="<?= e($id) ?>" name="<?= e($name) ?>" required autocomplete="<?= e($autocomplete) ?>"
      <?php if ($strong): ?>minlength="8" pattern="<?= e(Password::HTML_PATTERN) ?>" title="Use at least 8 characters with uppercase, lowercase, a number, and a special character."<?php endif; ?>>
    <button type="button" class="password-toggle" data-password-toggle="<?= e($id) ?>" aria-label="Show password" aria-pressed="false">
      <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
      <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3l18 18"/><path d="M10.6 6.2A10.4 10.4 0 0 1 12 6c6 0 9.5 6 9.5 6a16.8 16.8 0 0 1-3 3.6M6.4 6.8C4 8.2 2.5 12 2.5 12s3.5 6 9.5 6c1.1 0 2.1-.2 3-.5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
    </button>
  </div>
</div>
