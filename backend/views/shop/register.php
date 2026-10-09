<?php
/**
 * Customer registration.
 * @var string|null $error
 * @var array<string,string> $old previously submitted values
 */

use App\Services\Password;

$old = $old + ['name' => '', 'phone' => '', 'address' => '', 'username' => ''];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="preload" href="/assets/fonts/inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<title>Create Customer Account - MSINDA Food Shop</title>
<link rel="stylesheet" href="<?= asset('css/portal.css') ?>">
<script src="<?= asset('js/portal.js') ?>" defer></script>
</head>
<body class="portal-auth-body customer-login-page">
<div class="customer-auth-shell registration-shell">
  <?php partial('shop/partials/auth-visual', [
      'eyebrow'  => 'CUSTOMER REGISTRATION',
      'heading'  => 'Create your account and make your next order easier.',
      'intro'    => 'Set up your customer profile once. Your delivery details will be ready when you return to shop.',
      'benefits' => [
          ['Simple checkout', 'Keep your delivery information in one customer profile.'],
          ['Order history', 'Access your previous customer orders after signing in.'],
          ['One account', 'Use the same customer credentials each time you order.'],
      ],
  ]); ?>

  <section class="customer-auth-panel">
    <div class="customer-auth-card registration-card-modern">
      <div class="auth-logo"><img src="<?= asset('img/brand/msinda-emblem.png') ?>" alt="MSINDA Food Shop"></div>
      <div class="eyebrow">NEW CUSTOMER</div>
      <h2>Create your account</h2>
      <p class="auth-intro">Enter your details below to start ordering from MSINDA Food Shop.</p>

      <?php if ($error): ?><div class="portal-alert error" role="alert"><?= e($error) ?></div><?php endif; ?>

      <form method="post" action="/shop/register" class="customer-auth-form registration-form" novalidate>
        <?= csrf_field() ?>
        <div class="registration-grid">
          <div class="customer-field">
            <label for="reg-name">Full name</label>
            <div class="customer-input-wrap"><input id="reg-name" name="name" value="<?= e($old['name']) ?>" maxlength="100" autocomplete="name" placeholder="Your full name" required></div>
          </div>
          <div class="customer-field">
            <label for="reg-phone">Phone</label>
            <div class="customer-input-wrap"><input id="reg-phone" name="phone" type="tel" value="<?= e($old['phone']) ?>" maxlength="30" autocomplete="tel" placeholder="Phone number" required></div>
          </div>
          <div class="customer-field registration-wide">
            <label for="reg-address">Delivery address</label>
            <div class="customer-input-wrap"><input id="reg-address" name="address" value="<?= e($old['address']) ?>" maxlength="255" autocomplete="street-address" placeholder="House, street, area" required></div>
          </div>
          <div class="customer-field registration-wide">
            <label for="reg-username">Username</label>
            <div class="customer-input-wrap"><input id="reg-username" name="username" value="<?= e($old['username']) ?>" maxlength="80" autocomplete="username" placeholder="Choose a username" required></div>
          </div>
          <div class="customer-field password-field">
            <label for="reg-password">Password</label>
            <div class="customer-input-wrap has-eye">
              <input id="reg-password" type="password" name="password" autocomplete="new-password" placeholder="Create a password" required>
              <?php partial('shop/partials/password-eye', ['target' => 'reg-password']); ?>
            </div>
          </div>
          <div class="customer-field password-field">
            <label for="reg-confirm">Confirm password</label>
            <div class="customer-input-wrap has-eye">
              <input id="reg-confirm" type="password" name="confirm" autocomplete="new-password" placeholder="Repeat your password" required>
              <?php partial('shop/partials/password-eye', ['target' => 'reg-confirm']); ?>
            </div>
          </div>
        </div>
        <div class="password-help"><?= e(Password::RULES) ?></div>
        <button class="customer-auth-btn" type="submit">Create Customer Account</button>
      </form>

      <div class="auth-switch">Already registered? <a href="/shop">Sign in</a></div>
    </div>
    <div class="customer-auth-footer">© <?= date('Y') ?> MSINDA Food Shop | All Rights Reserved</div>
  </section>
</div>
</body>
</html>
