<?php
require_once __DIR__ . '/db.php'; if(session_status() !== PHP_SESSION_ACTIVE) session_start();
if(portal_logged_in()){ header('Location: shop.php'); exit; }
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim($_POST['name']??''); $phone=trim($_POST['phone']??''); $address=trim($_POST['address']??''); $username=trim($_POST['username']??''); $password=$_POST['password']??''; $confirm=$_POST['confirm']??'';
  if($name===''||$phone===''||$address===''||$username===''||$password==='') $error='Please fill in all required fields.';
  elseif(!preg_match('/^[A-Za-z0-9._-]{4,80}$/',$username)) $error='Username must be 4-80 characters and use letters, numbers, dot, underscore or hyphen.';
  elseif(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/',$password)) $error='Password must be at least 8 characters and include uppercase, lowercase, a number and a special character.';
  elseif($password!==$confirm) $error='Password confirmation does not match.';
  else {
    try{
      $chk=$conn->prepare('SELECT id FROM customer_accounts WHERE username=? LIMIT 1'); $chk->bind_param('s',$username); $chk->execute();
      if($chk->get_result()->fetch_assoc()) $error='That username is already in use.';
      else{
        $conn->begin_transaction();
        $st=$conn->prepare('INSERT INTO customers(name,phone,address) VALUES(?,?,?) RETURNING id'); $st->bind_param('sss',$name,$phone,$address); $st->execute(); $row=$st->get_result()->fetch_assoc(); $cid=(int)$row['id'];
        $hash=hash('sha256',$password); $ac=$conn->prepare('INSERT INTO customer_accounts(customer_id,username,password) VALUES(?,?,?)'); $ac->bind_param('iss',$cid,$username,$hash); $ac->execute();
        $conn->commit(); $_SESSION['portal_customer_id']=$cid; $_SESSION['portal_customer_name']=$name; $_SESSION['portal_last_activity']=time(); header('Location: shop.php'); exit;
      }
    }catch(Throwable $e){ if($conn) { try{$conn->rollback();}catch(Throwable $ignore){} } $error='Could not create your account. Please try again.'; }
  }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="/shop/"><title>Create Customer Account - MSINDA Food Shop</title><link rel="stylesheet" href="assets/portal.css"></head><body class="portal-auth-body">
<div class="auth-card register-card"><div class="auth-logo"><img src="../assets/branding/msinda-agricultural-emblem.png" alt="MSINDA Food Shop"></div><div class="eyebrow">CUSTOMER REGISTRATION</div><h1>Create your account</h1><p>Save your delivery details and order food products online.</p>
<?php if($error): ?><div class="portal-alert error"><?=pe($error)?></div><?php endif; ?>
<form method="post" class="auth-form two-col">
<label>Full name<input name="name" value="<?=pe($_POST['name']??'')?>" required></label><label>Phone<input name="phone" value="<?=pe($_POST['phone']??'')?>" required></label>
<label class="wide">Delivery address<input name="address" value="<?=pe($_POST['address']??'')?>" placeholder="House, street, area" required></label>
<label>Username<input name="username" value="<?=pe($_POST['username']??'')?>" autocomplete="username" required></label><label>Password<input type="password" name="password" required></label>
<label class="wide">Confirm password<input type="password" name="confirm" required></label>
<div class="wide password-help">Password: 8+ characters with uppercase, lowercase, number and special character.</div>
<button class="portal-btn wide" type="submit">Create Account</button>
</form><div class="auth-switch">Already registered? <a href="index.php">Sign in</a></div></div></body></html>
