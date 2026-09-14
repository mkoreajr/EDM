<?php
require "auth.php";
$error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $current=$_POST['current_password']??''; $new=$_POST['new_password']??''; $confirm=$_POST['confirm_password']??'';
  $stmt=$conn->prepare("SELECT password FROM users WHERE id=? LIMIT 1"); $stmt->bind_param("i",$_SESSION['user_id']); $stmt->execute(); $u=$stmt->get_result()->fetch_assoc();
  if(!$u || hash('sha256',$current)!==$u['password']) $error='Current password is incorrect.';
  elseif(strlen($new)<6) $error='New password must be at least 6 characters.';
  elseif($new!==$confirm) $error='New password and confirmation do not match.';
  else { $hash=hash('sha256',$new); $up=$conn->prepare("UPDATE users SET password=? WHERE id=?"); $up->bind_param("si",$hash,$_SESSION['user_id']); $up->execute(); $success='Password changed successfully.'; }
}
$pageTitle='Change Password'; $active='settings'; require 'partials/header.php';
?>
<div class="page-intro"><div><div class="welcome-kicker">SECURITY</div><h1>Change Password</h1><p class="muted">Update the administrator password securely.</p></div></div>
<div class="panel password-panel">
<?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?>
<?php if($success):?><div class="alert success"><?=e($success)?></div><?php endif;?>
<form method="post" class="password-form">
<div class="field"><label>Current Password</label><input type="password" name="current_password" required></div>
<div class="field"><label>New Password</label><input type="password" name="new_password" minlength="6" required></div>
<div class="field"><label>Confirm New Password</label><input type="password" name="confirm_password" minlength="6" required></div>
<div class="form-actions"><button class="btn primary" type="submit">Change Password</button><a class="btn secondary" href="dashboard.php">Cancel</a></div>
</form></div>
<?php require 'partials/footer.php'; ?>
