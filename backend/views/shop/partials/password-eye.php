<?php
/**
 * Show/hide toggle for a password input.
 * @var string $target id of the password input
 */
?>
<button class="password-eye" type="button" data-password-toggle="<?= e($target) ?>" aria-label="Show password" aria-pressed="false">
  <svg class="eye-open" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
  <svg class="eye-closed" viewBox="0 0 24 24"><path d="m3 3 18 18"></path><path d="M10.6 6.2A10.6 10.6 0 0 1 12 6c6.5 0 10 6 10 6a17.4 17.4 0 0 1-3.2 3.8"></path><path d="M6.2 6.9C3.7 8.5 2 12 2 12s3.5 6 10 6c1.4 0 2.7-.3 3.8-.8"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg>
</button>
