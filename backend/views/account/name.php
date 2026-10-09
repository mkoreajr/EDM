<?php
/**
 * @var string|null $error
 * @var string|null $success
 */

use App\Core\Auth;
?>
<div class="page-intro"><div><div class="welcome-kicker">ACCOUNT</div><h1>Change Name</h1><p class="muted">Rename the administrator without changing the Admin role.</p></div></div>
<div class="panel password-panel">
  <?php partial('partials/alerts', compact('error', 'success')); ?>
  <form method="post" action="/account/name" class="password-form">
    <?= csrf_field() ?>
    <div class="field"><label for="name">Administrator Name</label><input id="name" name="name" value="<?= e(Auth::name()) ?>" maxlength="100" required></div>
    <div class="form-actions"><button class="btn primary" type="submit">Save Name</button><a class="btn secondary" href="/settings">Back</a></div>
  </form>
</div>
