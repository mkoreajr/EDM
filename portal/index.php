<?php
// Customer Portal entry point. Unauthenticated visitors see the customer login;
// authenticated customers are taken to the shop/home page.
require_once __DIR__ . '/db.php';
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

if(portal_logged_in()){
    header('Location: shop.php');
    exit;
}

$error=$_SESSION['portal_error']??'';
$username=$_SESSION['portal_username']??'';
unset($_SESSION['portal_error'], $_SESSION['portal_username']);
$timedOut=isset($_GET['timeout']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Customer Login - MSINDA Food Shop</title>
<link rel="stylesheet" href="assets/portal.css">
</head>
<body class="portal-auth-body customer-login-page">
<div class="customer-auth-shell">
  <section class="customer-auth-visual" aria-label="MSINDA Food Shop customer portal">
    <div class="customer-auth-brand">
      <img src="../assets/branding/msinda-agricultural-emblem.png" alt="MSINDA Food Shop">
      <span>MSINDA Food Shop</span>
    </div>
    <div class="customer-auth-copy">
      <div class="eyebrow light">CUSTOMER PORTAL</div>
      <h1>Order your everyday food essentials with ease.</h1>
      <p>Sign in to shop, manage your cart, place orders, and follow your delivery progress from one simple customer dashboard.</p>
      <div class="customer-auth-benefits">
        <div><span>✓</span><strong>Quick ordering</strong><small>Save your delivery details for faster checkout.</small></div>
        <div><span>✓</span><strong>Order tracking</strong><small>Keep an eye on every order from confirmation to delivery.</small></div>
        <div><span>✓</span><strong>Secure account</strong><small>Your customer information stays connected to your account.</small></div>
      </div>
    </div>
    <div class="customer-auth-visual-footer">Secure • Reliable • Built for convenient food ordering</div>
  </section>

  <section class="customer-auth-panel">
    <div class="customer-auth-card">
      <div class="auth-logo"><img src="../assets/branding/msinda-agricultural-emblem.png" alt="MSINDA Food Shop"></div>
      <div class="eyebrow">WELCOME BACK</div>
      <h2>Customer Login</h2>
      <p class="auth-intro">Sign in to continue to your MSINDA Food Shop dashboard.</p>

      <?php if($timedOut): ?><div class="portal-alert error">Your session expired. Please sign in again.</div><?php endif; ?>
      <?php if($error): ?><div class="portal-alert error"><?=pe($error)?></div><?php endif; ?>

      <form method="post" action="login.php" class="customer-auth-form" novalidate>
        <div class="customer-field">
          <label for="customer-username">Username</label>
          <div class="customer-input-wrap">
            <span class="customer-input-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"></circle><path d="M5 20c.8-3.2 3.2-5 7-5s6.2 1.8 7 5"></path></svg>
            </span>
            <input id="customer-username" type="text" name="username" value="<?=pe($username)?>" autocomplete="username" placeholder="Enter your username" required autofocus>
          </div>
        </div>

        <div class="customer-field password-field">
          <label for="customer-password">Password</label>
          <div class="customer-input-wrap has-eye">
            <span class="customer-input-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
            </span>
            <input id="customer-password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
            <button class="password-eye" type="button" data-password-toggle="customer-password" aria-label="Show password" aria-pressed="false">
              <svg class="eye-open" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
              <svg class="eye-closed" viewBox="0 0 24 24"><path d="m3 3 18 18"></path><path d="M10.6 6.2A10.6 10.6 0 0 1 12 6c6.5 0 10 6 10 6a17.4 17.4 0 0 1-3.2 3.8"></path><path d="M6.2 6.9C3.7 8.5 2 12 2 12s3.5 6 10 6c1.4 0 2.7-.3 3.8-.8"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg>
            </button>
          </div>
        </div>

        <button class="customer-auth-btn" type="submit">Sign in to Customer Portal</button>
      </form>

      <div class="auth-switch">New to MSINDA Food Shop? <a href="register.php">Create an account</a></div>
      <a class="customer-back-link" href="/shop">Customer Portal Home</a>
    </div>
    <div class="customer-auth-footer">© 2026 MSINDA Food Shop | All Rights Reserved</div>
  </section>
</div>
<script>
(function(){
  document.querySelectorAll('[data-password-toggle]').forEach(function(button){
    button.addEventListener('click',function(){
      var input=document.getElementById(button.getAttribute('data-password-toggle'));
      if(!input) return;
      var show=input.type==='password';
      input.type=show?'text':'password';
      button.setAttribute('aria-label',show?'Hide password':'Show password');
      button.setAttribute('aria-pressed',show?'true':'false');
      button.classList.toggle('is-visible',show);
    });
  });
})();
</script>
</body>
</html>
