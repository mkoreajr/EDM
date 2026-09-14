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

<?php if($required): ?>
<div class="password-required-page">
  <div class="password-required-card">
    <div class="password-required-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 10V7a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
    </div>
    <div class="welcome-kicker">SECURITY</div>
    <h1>Change Your Password</h1>
    <p class="password-required-subtitle">For security, you must change your temporary password before entering the system.</p>

    <?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?>

    <form method="post" class="password-required-form">
      <div class="field"><label>Current Password</label><input type="password" name="current_password" required autocomplete="current-password"></div>
      <div class="field"><label>New Password</label><input type="password" name="new_password" minlength="6" required autocomplete="new-password"></div>
      <div class="field"><label>Confirm New Password</label><input type="password" name="confirm_password" minlength="6" required autocomplete="new-password"></div>
      <button class="btn primary password-required-submit" type="submit">Set New Password</button>
    </form>
  </div>
</div>
<?php else: ?>
<div class="page-intro centered-page-intro"><div><div class="welcome-kicker">SECURITY</div><h1>Change Password</h1><p class="muted">Update your account password securely.</p></div></div>
<div class="panel password-panel">
<?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?>
<?php if($success):?><div class="alert success"><?=e($success)?></div><div class="form-actions"><a class="btn primary" href="dashboard.php">Continue to Dashboard</a></div><?php endif;?>
<?php if(!$success):?>
<form method="post" class="password-form">
<div class="field"><label>Current Password</label><input type="password" name="current_password" required autocomplete="current-password"></div>
<div class="field"><label>New Password</label><input type="password" name="new_password" minlength="6" required autocomplete="new-password"></div>
<div class="field"><label>Confirm New Password</label><input type="password" name="confirm_password" minlength="6" required autocomplete="new-password"></div>
<div class="form-actions"><button class="btn primary" type="submit">Change Password</button><a class="btn secondary" href="dashboard.php">Cancel</a></div>
</form>
<?php endif;?>
</div>
<?php endif; ?>
<?php require 'partials/footer.php'; ?>
