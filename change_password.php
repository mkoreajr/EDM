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
  elseif(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $new)) $error='New password must be at least 8 characters and include uppercase, lowercase, a number, and a special character.';
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
function eyeIcon(){ return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3l18 18"/><path d="M10.6 6.2A10.4 10.4 0 0 1 12 6c6 0 9.5 6 9.5 6a16.8 16.8 0 0 1-3 3.6M6.4 6.8C4 8.2 2.5 12 2.5 12s3.5 6 9.5 6c1.1 0 2.1-.2 3-.5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>'; }
$pageTitle='Change Password'; $active='settings'; require 'partials/header.php';
?>
<style id="change-password-final-fix">
.password-required-mode .password-required-page{min-height:calc(100vh - 54px)!important;min-height:calc(100dvh - 54px)!important;padding:30px 18px 24px!important;display:flex!important;align-items:center!important;justify-content:center!important}
.password-required-mode .password-required-card{width:min(390px,100%)!important;max-width:390px!important;padding:26px 30px 28px!important;border-radius:16px!important;background:#fff!important;border:0!important;box-shadow:0 24px 60px rgba(18,58,45,.18)!important}
.password-required-mode .password-brand{margin:0 auto 10px!important;height:58px!important;width:160px!important;display:flex!important;align-items:center!important;justify-content:center!important}
.password-required-mode .password-brand img{width:160px!important;max-width:160px!important;height:58px!important;max-height:58px!important;object-fit:contain!important;display:block!important}
.password-required-mode .password-required-icon{width:44px!important;height:44px!important;margin:0 auto 10px!important}
.password-required-mode .password-required-card .welcome-kicker{font-size:11px!important;letter-spacing:3px!important}
.password-required-mode .password-required-card h1{font-size:26px!important;line-height:1.1!important;margin:6px 0 7px!important}
.password-required-mode .password-required-subtitle{font-size:12px!important;line-height:1.45!important;margin:0 auto 16px!important;max-width:330px!important}
.password-required-mode .password-required-card .password-rules{font-size:10.5px!important;line-height:1.45!important;margin:0 auto 16px!important;padding:8px 10px!important}
.password-required-mode .password-required-form .field{margin-bottom:12px!important}
.password-required-mode .password-required-form .field label{font-size:11px!important;margin-bottom:5px!important}
.password-required-mode .password-required-form .change-password-field{position:relative!important;display:block!important;width:100%!important}
.password-required-mode .password-required-form .change-password-field input{display:block!important;width:100%!important;height:43px!important;padding:0 42px 0 12px!important;border:1px solid #d5dfdb!important;border-radius:8px!important;background:#fff!important;font-size:13px!important;box-sizing:border-box!important}
.password-required-mode .password-required-form .change-password-field .password-toggle{position:absolute!important;right:5px!important;top:50%!important;transform:translateY(-50%)!important;width:34px!important;height:34px!important;margin:0!important;padding:0!important;border:0!important;border-radius:6px!important;background:transparent!important;display:flex!important;align-items:center!important;justify-content:center!important;color:#8b9b95!important;z-index:3!important;cursor:pointer!important;appearance:none!important;-webkit-appearance:none!important}
.password-required-mode .password-required-form .change-password-field .password-toggle svg{display:block!important;width:17px!important;height:17px!important}
.password-required-mode .password-required-form .change-password-field .password-toggle:hover{background:#eaf7f0!important;color:#008653!important}
.password-required-mode .password-required-submit{height:43px!important;margin-top:4px!important;border-radius:8px!important;font-size:13px!important;box-shadow:0 8px 18px rgba(21,155,98,.18)!important}
@media(max-width:620px){.password-required-mode .password-required-page{padding:20px 14px!important}.password-required-mode .password-required-card{width:min(390px,100%)!important;padding:24px 22px 25px!important;border-radius:16px!important}.password-required-mode .password-brand{width:145px!important;height:52px!important}.password-required-mode .password-brand img{width:145px!important;height:52px!important;max-width:145px!important;max-height:52px!important}}
</style>

<?php if($required): ?>
<div class="password-required-page">
  <div class="password-required-card">
    <div class="password-brand">
      <img src="assets/branding/msinda-food-shop.jpg" alt="MSINDA Food Shop">
    </div>
    <div class="password-required-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 10V7a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
    </div>
    <div class="welcome-kicker">SECURITY</div>
    <h1>Change Your Password</h1>
    <p class="password-required-subtitle">For security, you must change your temporary password before entering the system.</p><p class="password-rules">Password must contain: <b>8+ characters</b>, uppercase, lowercase, number, and special character.</p>

    <?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?>

    <form method="post" class="password-required-form">
      <div class="field"><label>Current Password</label><div class="password-field-wrap change-password-field"><input type="password" name="current_password" required autocomplete="current-password"><button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePassword(this)"><?php echo eyeIcon(); ?></button></div></div>
      <div class="field"><label>New Password</label><div class="password-field-wrap change-password-field"><input type="password" name="new_password" minlength="8" required autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" title="Use at least 8 characters with uppercase, lowercase, a number, and a special character."><button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePassword(this)"><?php echo eyeIcon(); ?></button></div></div>
      <div class="field"><label>Confirm New Password</label><div class="password-field-wrap change-password-field"><input type="password" name="confirm_password" minlength="8" required autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" title="Use at least 8 characters with uppercase, lowercase, a number, and a special character."><button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePassword(this)"><?php echo eyeIcon(); ?></button></div></div>
      <button class="btn primary password-required-submit" type="submit">Set New Password</button>
    </form>
  </div>
</div>
<?php else: ?>
<div class="page-intro centered-page-intro"><div><div class="welcome-kicker">SECURITY</div><h1>Change Password</h1><p class="muted">Update your account password securely.</p></div></div><div class="password-rules">Password must contain: <b>8+ characters</b>, uppercase, lowercase, number, and special character.</div>
<div class="panel password-panel">
<?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?>
<?php if($success):?><div class="alert success"><?=e($success)?></div><div class="form-actions"><a class="btn primary" href="dashboard.php">Continue to Dashboard</a></div><?php endif;?>
<?php if(!$success):?>
<form method="post" class="password-form">
<div class="field"><label>Current Password</label><div class="password-field-wrap change-password-field"><input type="password" name="current_password" required autocomplete="current-password"><button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePassword(this)"><?php echo eyeIcon(); ?></button></div></div>
<div class="field"><label>New Password</label><div class="password-field-wrap change-password-field"><input type="password" name="new_password" minlength="8" required autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" title="Use at least 8 characters with uppercase, lowercase, a number, and a special character."><button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePassword(this)"><?php echo eyeIcon(); ?></button></div></div>
<div class="field"><label>Confirm New Password</label><div class="password-field-wrap change-password-field"><input type="password" name="confirm_password" minlength="8" required autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" title="Use at least 8 characters with uppercase, lowercase, a number, and a special character."><button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePassword(this)"><?php echo eyeIcon(); ?></button></div></div>
<div class="form-actions"><button class="btn primary" type="submit">Change Password</button><a class="btn secondary" href="dashboard.php">Cancel</a></div>
</form>
<?php endif;?>
</div>
<?php endif; ?>

<script>
function togglePassword(button){
  const input=button.parentElement.querySelector('input');
  const visible=input.type==='text';
  input.type=visible?'password':'text';
  button.setAttribute('aria-pressed',String(!visible));
  button.setAttribute('aria-label',visible?'Show password':'Hide password');
  button.innerHTML=visible?eyeIconSvg(false):eyeIconSvg(true);
}
function eyeIconSvg(visible){
  return visible ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>' : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3l18 18"/><path d="M10.6 6.2A10.4 10.4 0 0 1 12 6c6 0 9.5 6 9.5 6a16.8 16.8 0 0 1-3 3.6M6.4 6.8C4 8.2 2.5 12 2.5 12s3.5 6 9.5 6c1.1 0 2.1-.2 3-.5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';
}
function eyeIcon(){return eyeIconSvg(false);}
</script>
<?php require 'partials/footer.php'; ?>
