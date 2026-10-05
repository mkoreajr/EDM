<?php
require_once __DIR__ . '/db.php';
if(session_status() !== PHP_SESSION_ACTIVE) session_start();
if(portal_logged_in()){ header('Location: shop.php'); exit; }
$error=$_SESSION['portal_error']??''; unset($_SESSION['portal_error']);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="/shop/"><title>Customer Portal - MSINDA Food Shop</title><link rel="stylesheet" href="assets/portal.css"></head><body class="portal-auth-body">
<div class="auth-card">
  <div class="auth-logo"><img src="../assets/branding/msinda-agricultural-emblem.png" alt="MSINDA Food Shop"></div>
  <div class="eyebrow">CUSTOMER PORTAL</div><h1>Welcome back</h1><p>Sign in to shop, manage your cart, and follow every delivery from one simple dashboard.</p>
  <?php if($error): ?><div class="portal-alert error"><?=pe($error)?></div><?php endif; ?>
  <form method="post" action="login.php" class="auth-form">
    <label>Username<input name="username" autocomplete="username" required></label>
    <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
    <button class="portal-btn" type="submit">Sign in</button>
  </form>
  <div class="auth-switch">New to MSINDA? <a href="register.php">Create your account</a></div>
  <a class="admin-link" href="/admin">Admin / Staff Login</a>
</div>
</body></html>
