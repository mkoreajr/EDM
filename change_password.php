<?php
require "auth.php";
$error=''; $success='';
$required=!empty($_GET['required']) || password_change_required();

if($_SERVER['REQUEST_METHOD']==='POST'){
  $current=$_POST['current_password']??'';
  $new=$_POST['new_password']??'';
  $confirm=$_POST['confirm_password']??'';

  $stmt=$conn->prepare("SELECT password FROM users WHERE id=? LIMIT 1");
  $stmt->bind_param("i",$_SESSION['user_id']); $stmt->execute();
  $u=$stmt->get_result()->fetch_assoc();

  if(!$u || !hash_equals((string)$u['password'],hash('sha256',$current))) $error='Current password is incorrect.';
  elseif(strlen($new)<6) $error='New password must be at least 6 characters.';
  elseif($new!==$confirm) $error='New password and confirmation do not match.';
  elseif(hash('sha256',$new)===$u['password']) $error='New password must be different from the current password.';
  else {
    $hash=hash('sha256',$new);
    $up=$conn->prepare("UPDATE users SET password=?,must_change_password=FALSE WHERE id=?");
    $up->bind_param("si",$hash,$_SESSION['user_id']); $up->execute();
    $success='Password changed successfully. You can now enter the system.';
    $required=false;
  }
}
$pageTitle='Change Password'; $active='settings'; require 'partials/header.php';
?>
<div class="page-intro"><div><div class="welcome-kicker">SECURITY</div><h1>Change Password</h1>
<p class="muted"><?= $required ? 'For security, you must change your temporary password before using the system.' : 'Update your account password securely.' ?></p></div></div>
<div class="panel password-panel">
<?php if($required):?><div class="alert warning">Password change required. Please choose a new password before continuing.</div><?php endif;?>
<?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?>
<?php if($success):?><div class="alert success"><?=e($success)?></div><div class="form-actions"><a class="btn primary" href="dashboard.php">Continue to Dashboard</a></div><?php endif;?>
<?php if(!$success):?>
<form method="post" class="password-form">
<div class="field"><label>Current Password</label><input type="password" name="current_password" required autocomplete="current-password"></div>
<div class="field"><label>New Password</label><input type="password" name="new_password" minlength="6" required autocomplete="new-password"></div>
<div class="field"><label>Confirm New Password</label><input type="password" name="confirm_password" minlength="6" required autocomplete="new-password"></div>
<div class="form-actions"><button class="btn primary" type="submit"><?= $required ? 'Set New Password' : 'Change Password' ?></button><?php if(!$required):?><a class="btn secondary" href="dashboard.php">Cancel</a><?php endif;?></div>
</form>
<?php endif;?>
</div>
<?php require 'partials/footer.php'; ?>
