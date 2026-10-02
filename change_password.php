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
/* Change Password: same visual language as the Login page, centered and viewport-safe. */
body.change-password-page *{font-family:Arial,Helvetica,sans-serif!important}
html:has(body.change-password-page),body.change-password-page{width:100%!important;height:100%!important;min-height:100%!important;margin:0!important;overflow:hidden!important}
body.change-password-page{background:#005f3f!important;color:#17342a!important;font-family:Arial,Helvetica,sans-serif!important}
body.change-password-page .sidebar,body.change-password-page .app-topbar{display:none!important}
body.change-password-page .content{margin:0!important;padding:0!important;width:100%!important;height:100vh!important;min-height:100vh!important;position:relative!important;left:0!important;overflow:hidden!important;display:flex!important;align-items:center!important;justify-content:center!important;background:#005f3f url('login-hero.png') center/cover no-repeat!important}
body.change-password-page .content:before{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(0,76,49,.97) 0%,rgba(0,95,63,.82) 42%,rgba(0,76,49,.25) 100%);pointer-events:none}
body.change-password-page .content:after{content:"";position:absolute;left:-10%;bottom:-28%;width:58vw;height:48vh;border-radius:50%;background:rgba(255,255,255,.08);transform:rotate(-8deg);pointer-events:none}
body.change-password-page .password-required-page{position:absolute!important;inset:0!important;z-index:2!important;width:100%!important;height:100%!important;min-height:100%!important;padding:0 18px 48px!important;display:flex!important;align-items:center!important;justify-content:center!important;box-sizing:border-box!important}
body.change-password-page .password-required-card,body.change-password-page .password-panel{position:relative!important;left:auto!important;top:auto!important;transform:none!important;z-index:2!important;width:min(390px,calc(100vw - 32px))!important;max-width:390px!important;margin:0!important;padding:28px 30px!important;border-radius:16px!important;background:#fff!important;border:1px solid rgba(255,255,255,.55)!important;box-shadow:0 20px 55px rgba(0,30,20,.24)!important;box-sizing:border-box!important}
body.change-password-page .password-required-icon{width:44px!important;height:44px!important;margin:0 auto 10px!important;color:#008b59!important;background:#e8f7f0!important;border-radius:50%!important;display:flex!important;align-items:center!important;justify-content:center!important}
body.change-password-page .password-required-icon svg{width:22px!important;height:22px!important}
body.change-password-page .welcome-kicker{font-size:11px!important;letter-spacing:3px!important;font-weight:800!important;text-align:center!important;color:#008b59!important}
body.change-password-page .password-required-card h1,body.change-password-page .page-intro h1{font-size:28px!important;line-height:1.1!important;margin:6px 0 7px!important;text-align:center!important;color:#073f2e!important}
body.change-password-page .password-required-subtitle,body.change-password-page .page-intro .muted{font-size:12px!important;line-height:1.45!important;margin:0 auto 16px!important;text-align:center!important;color:#6d7c98!important}
body.change-password-page .password-rules{font-size:10.5px!important;line-height:1.45!important;margin:0 auto 16px!important;padding:0!important;text-align:center!important;color:#60736d!important;background:transparent!important}
body.change-password-page .password-required-form .field,body.change-password-page .password-form .field{margin-bottom:12px!important}
body.change-password-page .password-required-form .field label,body.change-password-page .password-form .field label{font-size:11px!important;margin-bottom:5px!important;color:#17342a!important}
body.change-password-page .password-required-form .change-password-field,body.change-password-page .password-form .change-password-field{position:relative!important;display:block!important;width:100%!important}
body.change-password-page .password-required-form .change-password-field input,body.change-password-page .password-form .change-password-field input{display:block!important;width:100%!important;height:43px!important;padding:0 42px 0 12px!important;border:1px solid #d5dfdb!important;border-radius:8px!important;background:#fff!important;font-size:13px!important;box-sizing:border-box!important;outline:none!important}
body.change-password-page .password-required-form .change-password-field input:focus,body.change-password-page .password-form .change-password-field input:focus{border-color:#008f5b!important;box-shadow:0 0 0 3px rgba(0,143,91,.10)!important}
body.change-password-page .change-password-field .password-toggle{position:absolute!important;right:5px!important;top:50%!important;transform:translateY(-50%)!important;width:34px!important;height:34px!important;margin:0!important;padding:0!important;border:0!important;border-radius:6px!important;background:transparent!important;display:flex!important;align-items:center!important;justify-content:center!important;color:#7d8ba3!important;z-index:5!important;cursor:pointer!important;appearance:none!important;-webkit-appearance:none!important}
body.change-password-page .change-password-field .password-toggle svg{display:block!important;width:17px!important;height:17px!important}
body.change-password-page .change-password-field .password-toggle:hover{background:#eaf7f0!important;color:#008653!important}
body.change-password-page .password-required-submit,body.change-password-page .password-form .btn.primary{width:100%!important;height:43px!important;margin-top:4px!important;border-radius:8px!important;font-size:13px!important;background:#008f5b!important;color:#fff!important;border:0!important;box-shadow:0 7px 18px rgba(0,143,91,.18)!important}
body.change-password-page .password-form .form-actions{display:grid!important;grid-template-columns:1fr auto!important;gap:10px!important;align-items:center!important}
body.change-password-page .password-form .form-actions .btn.secondary{height:43px!important;display:flex!important;align-items:center!important;justify-content:center!important;border-radius:8px!important}
body.change-password-page .app-footer{display:block!important;position:fixed!important;left:0!important;right:0!important;bottom:0!important;height:48px!important;padding:0 28px!important;z-index:10!important;background:rgba(0,60,39,.28)!important;border-top:1px solid rgba(255,255,255,.18)!important;color:rgba(255,255,255,.92)!important}
body.change-password-page .app-footer .footer-content{height:100%!important;display:flex!important;align-items:center!important;justify-content:space-between!important;font-size:11px!important}
body.change-password-page .app-footer .footer-tagline{color:rgba(255,255,255,.78)!important;font-size:10px!important}
body.change-password-page .password-required-mode-footer{display:none!important}
body.change-password-page .alert{font-size:11px!important;padding:9px!important;margin-bottom:12px!important}
@media(max-width:620px){
  html:has(body.change-password-page),body.change-password-page{overflow:auto!important}
  body.change-password-page .content{min-height:100svh!important;height:auto!important;overflow:auto!important;padding:18px 0 58px!important}
  body.change-password-page .password-required-page{height:auto!important;min-height:calc(100svh - 58px)!important;padding:18px 12px 20px!important}
  body.change-password-page .password-required-card,body.change-password-page .password-panel{width:min(390px,calc(100vw - 24px))!important;padding:24px 22px!important}
  body.change-password-page .app-footer{height:52px!important;padding:0 14px!important}
  body.change-password-page .app-footer .footer-content{font-size:9px!important}
  body.change-password-page .app-footer .footer-tagline{font-size:8px!important}
}
</style>

<?php if($required): ?>
<div class="password-required-page">
  <div class="password-required-card">
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
<div class="password-required-mode-footer">© 2026 MSINDA Food Shop | All Rights Reserved.<div class="footer-tagline">“Mchele • Unga • Mayai”</div></div>
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
