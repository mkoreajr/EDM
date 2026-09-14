<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit; }
$error = $_SESSION['login_error'] ?? ''; unset($_SESSION['login_error']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>EDM Kienyeji Egg Shop - Sign In</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
<div class="login-shell">
  <section class="login-visual">
    <div class="visual-overlay">
      <div class="shop-brand"><span class="egg-mark">🥚</span><div><strong>EDM KIENYEJI</strong><b>EGG SHOP</b><small>Fresh Eggs • Healthy Families • A Better Tomorrow</small></div></div>
      <div class="visual-copy">
        <div class="eyebrow">SMART EGG BUSINESS MANAGEMENT</div>
        <h1>Sell smarter.<br>Manage stock.<br><span>Grow your business.</span></h1>
        <p>Manage sales, stock, customers and daily egg-shop operations with ease.</p>
        <div class="feature-list">
          <div><span>🛒</span><div><b>Manage Sales</b><small>Fast, easy and reliable</small></div></div>
          <div><span>📦</span><div><b>Track Stock</b><small>Real-time inventory</small></div></div>
          <div><span>👥</span><div><b>Customers & Suppliers</b><small>Keep your business organized</small></div></div>
          <div><span>📊</span><div><b>Reports & Insights</b><small>Make better decisions</small></div></div>
          <div><span>💳</span><div><b>Multiple Payments</b><small>Cash • Mobile Money • Bank</small></div></div>
        </div>
      </div>
      <div class="visual-tag">Fresh Eggs <i>•</i> Healthy Families <i>•</i> A Better Tomorrow</div>
    </div>
  </section>
  <section class="login-form-side">
    <div class="form-wrap">
      <div class="welcome">WELCOME BACK</div>
      <h2>Sign <span>in</span></h2>
      <p class="subtitle">Access your EDM Kienyeji Egg Shop dashboard.</p>
      <?php if($error): ?><div class="alert danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
      <form method="post" action="login.php">
        <label>Username</label>
        <div class="input-wrap"><span>👤</span><input name="username" value="<?=htmlspecialchars($_POST['username']??'')?>" required autofocus></div>
        <label>Password</label>
        <div class="input-wrap"><span>🔒</span><input id="password" type="password" name="password" required><button type="button" class="eye" onclick="togglePassword()">◉</button></div>
        <div class="form-row"><label class="check"><input type="checkbox"> <span>Remember me</span></label><a href="#" onclick="return false">Forgot password?</a></div>
        <button class="signin-btn" type="submit">↪ &nbsp; Sign In</button>
      </form>
      <div class="or"><span>or continue with</span></div>
      <div class="payment-cards">
        <div><strong>▣</strong><b>Cash</b><small>Simple & Fast</small></div>
        <div><strong>▯</strong><b>Mobile Money</b><small>Secure & Convenient</small></div>
        <div><strong>▥</strong><b>Bank</b><small>Safe & Reliable</small></div>
      </div>
      <div class="secure">🛡 Secure • Reliable • Built for Egg Businesses</div>
      <div class="demo-login">Demo administrator: <b>admin</b> / <b>admin123</b></div>
    </div>
  </section>
</div>
<script>
function togglePassword(){const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';}
</script>
</body>
</html>