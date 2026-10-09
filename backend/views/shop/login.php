<?php
/**
 * Customer sign-in (shown at /shop to signed-out visitors).
 * @var string|null $error
 * @var string $username
 * @var bool $timedOut
 */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="preload" href="/assets/fonts/inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<title>Customer Login - MSINDA Food Shop</title>
<link rel="stylesheet" href="<?= asset('css/portal.css') ?>">
<script src="<?= asset('js/portal.js') ?>" defer></script>
</head>
<body class="portal-auth-body customer-login-page">
<div class="customer-auth-shell">
  <?php partial('shop/partials/auth-visual', [
      'eyebrow'  => 'CUSTOMER PORTAL',
      'heading'  => 'Order your everyday food essentials with ease.',
      'intro'    => 'Sign in to shop, manage your cart, place orders, and follow your delivery progress from one simple customer dashboard.',
      'benefits' => [
          ['Quick ordering', 'Save your delivery details for faster checkout.'],
          ['Order tracking', 'Keep an eye on every order from confirmation to delivery.'],
          ['Secure account', 'Your customer information stays connected to your account.'],
      ],
  ]); ?>

  <section class="customer-auth-panel">
    <div class="customer-auth-card">
      <div class="auth-logo"><img src="<?= asset('img/brand/msinda-emblem.png') ?>" alt="MSINDA Food Shop"></div>
      <div class="eyebrow">WELCOME BACK</div>
      <h2>Customer Login</h2>
      <p class="auth-intro">Sign in to continue to your MSINDA Food Shop dashboard.</p>

      <?php if ($timedOut): ?><div class="portal-alert error">Your session expired. Please sign in again.</div><?php endif; ?>
      <?php if ($error): ?><div class="portal-alert error" role="alert"><?= e($error) ?></div><?php endif; ?>

      <form method="post" action="/shop/login" class="customer-auth-form" novalidate>
        <?= csrf_field() ?>
        <div class="customer-field">
          <label for="customer-username">Username</label>
          <div class="customer-input-wrap">
            <span class="customer-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"></circle><path d="M5 20c.8-3.2 3.2-5 7-5s6.2 1.8 7 5"></path></svg></span>
            <input id="customer-username" type="text" name="username" value="<?= e($username) ?>" maxlength="80" autocomplete="username" placeholder="Enter your username" required autofocus>
          </div>
        </div>
        <div class="customer-field password-field">
          <label for="customer-password">Password</label>
          <div class="customer-input-wrap has-eye">
            <span class="customer-input-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg></span>
            <input id="customer-password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
            <?php partial('shop/partials/password-eye', ['target' => 'customer-password']); ?>
          </div>
        </div>
        <button class="customer-auth-btn" type="submit">Sign in to Customer Portal</button>
      </form>

      <div class="auth-switch">Don't have an account? <a href="/shop/register">Register Now</a></div>
      <a class="customer-back-link" href="/">Staff login →</a>
    </div>
    <div class="customer-auth-footer">© <?= date('Y') ?> MSINDA Food Shop | All Rights Reserved</div>
  </section>
</div>
</body>
</html>
