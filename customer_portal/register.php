<?php
require_once __DIR__ . '/db.php';
if(session_status() !== PHP_SESSION_ACTIVE) session_start();
if(portal_logged_in()){ header('Location: shop.php'); exit; }
$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim((string)($_POST['name']??''));
  $phone=trim((string)($_POST['phone']??''));
  $address=trim((string)($_POST['address']??''));
  $username=trim((string)($_POST['username']??''));
  $password=(string)($_POST['password']??'');
  $confirm=(string)($_POST['confirm']??'');

  if($name===''||$phone===''||$address===''||$username===''||$password===''||$confirm==='') $error='Please fill in all required fields.';
  elseif(!preg_match('/^[A-Za-z0-9._-]{4,80}$/',$username)) $error='Username must be 4-80 characters and use letters, numbers, dot, underscore or hyphen.';
  elseif(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/',$password)) $error='Password must be at least 8 characters and include uppercase, lowercase, a number and a special character.';
  elseif($password!==$confirm) $error='Password and Confirm Password do not match.';
  else {
    try{
      $chk=$conn->prepare('SELECT id FROM customer_accounts WHERE username=? LIMIT 1');
      $chk->bind_param('s',$username);
      $chk->execute();
      if($chk->get_result()->fetch_assoc()) $error='That username is already in use. Please choose another one.';
      else{
        $conn->begin_transaction();
        $st=$conn->prepare('INSERT INTO customers(name,phone,address) VALUES(?,?,?) RETURNING id');
        $st->bind_param('sss',$name,$phone,$address);
        $st->execute();
        $row=$st->get_result()->fetch_assoc();
        $cid=(int)$row['id'];
        $hash=hash('sha256',$password);
        $ac=$conn->prepare('INSERT INTO customer_accounts(customer_id,username,password) VALUES(?,?,?)');
        $ac->bind_param('iss',$cid,$username,$hash);
        $ac->execute();
        $conn->commit();
        session_regenerate_id(true);
        $_SESSION['portal_customer_id']=$cid;
        $_SESSION['portal_customer_name']=$name;
        $_SESSION['portal_last_activity']=time();
        header('Location: shop.php');
        exit;
      }
    }catch(Throwable $e){
      if($conn) { try{$conn->rollback();}catch(Throwable $ignore){} }
      $error='Could not create your account. Please try again.';
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Customer Account - MSINDA Food Shop</title>
<link rel="stylesheet" href="/shop/assets/portal.css">
</head>
<body class="portal-auth-body customer-login-page">
<div class="customer-auth-shell registration-shell">
  <section class="customer-auth-visual" aria-label="MSINDA Food Shop customer registration">
    <div class="customer-auth-brand">
      <img src="../assets/branding/msinda-agricultural-emblem.png" alt="MSINDA Food Shop">
      <span>MSINDA Food Shop</span>
    </div>
    <div class="customer-auth-copy">
      <div class="eyebrow light">CUSTOMER REGISTRATION</div>
      <h1>Create your account and make your next order easier.</h1>
      <p>Set up your customer profile once. Your delivery details will be ready when you return to shop.</p>
      <div class="customer-auth-benefits">
        <div><span>✓</span><strong>Simple checkout</strong><small>Keep your delivery information in one customer profile.</small></div>
        <div><span>✓</span><strong>Order history</strong><small>Access your previous customer orders after signing in.</small></div>
        <div><span>✓</span><strong>One account</strong><small>Use the same customer credentials each time you order.</small></div>
      </div>
    </div>
    <div class="customer-auth-visual-footer">Secure • Reliable • Built for convenient food ordering</div>
  </section>

  <section class="customer-auth-panel">
    <div class="customer-auth-card registration-card-modern">
      <div class="auth-logo"><img src="../assets/branding/msinda-agricultural-emblem.png" alt="MSINDA Food Shop"></div>
      <div class="eyebrow">NEW CUSTOMER</div>
      <h2>Create your account</h2>
      <p class="auth-intro">Enter your details below to start ordering from MSINDA Food Shop.</p>

      <?php if($error): ?><div class="portal-alert error"><?=pe($error)?></div><?php endif; ?>

      <form method="post" class="customer-auth-form registration-form" novalidate>
        <div class="registration-grid">
          <div class="customer-field">
            <label for="reg-name">Full name</label>
            <div class="customer-input-wrap"><input id="reg-name" name="name" value="<?=pe($_POST['name']??'')?>" autocomplete="name" placeholder="Your full name" required></div>
          </div>
          <div class="customer-field">
            <label for="reg-phone">Phone</label>
            <div class="customer-input-wrap"><input id="reg-phone" name="phone" value="<?=pe($_POST['phone']??'')?>" autocomplete="tel" placeholder="Phone number" required></div>
          </div>
          <div class="customer-field registration-wide">
            <label for="reg-address">Delivery address</label>
            <div class="customer-input-wrap"><input id="reg-address" name="address" value="<?=pe($_POST['address']??'')?>" autocomplete="street-address" placeholder="House, street, area" required></div>
          </div>
          <div class="customer-field registration-wide">
            <label for="reg-username">Username</label>
            <div class="customer-input-wrap"><input id="reg-username" name="username" value="<?=pe($_POST['username']??'')?>" autocomplete="username" placeholder="Choose a username" required></div>
          </div>
          <div class="customer-field password-field">
            <label for="reg-password">Password</label>
            <div class="customer-input-wrap has-eye">
              <input id="reg-password" type="password" name="password" autocomplete="new-password" placeholder="Create a password" required>
              <button class="password-eye" type="button" data-password-toggle="reg-password" aria-label="Show password" aria-pressed="false">
                <svg class="eye-open" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                <svg class="eye-closed" viewBox="0 0 24 24"><path d="m3 3 18 18"></path><path d="M10.6 6.2A10.6 10.6 0 0 1 12 6c6.5 0 10 6 10 6a17.4 17.4 0 0 1-3.2 3.8"></path><path d="M6.2 6.9C3.7 8.5 2 12 2 12s3.5 6 10 6c1.4 0 2.7-.3 3.8-.8"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg>
              </button>
            </div>
          </div>
          <div class="customer-field password-field">
            <label for="reg-confirm">Confirm password</label>
            <div class="customer-input-wrap has-eye">
              <input id="reg-confirm" type="password" name="confirm" autocomplete="new-password" placeholder="Repeat your password" required>
              <button class="password-eye" type="button" data-password-toggle="reg-confirm" aria-label="Show password" aria-pressed="false">
                <svg class="eye-open" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                <svg class="eye-closed" viewBox="0 0 24 24"><path d="m3 3 18 18"></path><path d="M10.6 6.2A10.6 10.6 0 0 1 12 6c6.5 0 10 6 10 6a17.4 17.4 0 0 1-3.2 3.8"></path><path d="M6.2 6.9C3.7 8.5 2 12 2 12s3.5 6 10 6c1.4 0 2.7-.3 3.8-.8"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg>
              </button>
            </div>
          </div>
        </div>
        <div class="password-help">Password must be 8+ characters with uppercase, lowercase, number and special character.</div>
        <button class="customer-auth-btn" type="submit">Create Customer Account</button>
      </form>

      <div class="auth-switch">Already registered? <a href="index.php">Sign in</a></div>
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
