<?php
require "auth.php"; $pageTitle="Settings"; $active="settings"; require "partials/header.php";
?>
<div class="page-intro"><div><div class="welcome-kicker">SYSTEM</div><h1>Settings</h1><p class="muted">Manage your account and shop preferences.</p></div></div>
<div class="settings-grid">
  <div class="panel setting-card"><div class="setting-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M5 20c.6-3.3 3-5 7-5s6.4 1.7 7 5" fill="none" stroke="currentColor" stroke-width="1.7"/></svg></div><h3>Account</h3><p>Signed in as <strong><?=e($_SESSION['name']??'Administrator')?></strong>.</p><p class="muted">Role: <?=e($_SESSION['role']??'Admin')?></p></div>
  <div class="panel setting-card"><div class="setting-icon"><svg viewBox="0 0 24 24"><rect x="2.5" y="6" width="19" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="2.2" fill="none" stroke="currentColor" stroke-width="1.7"/></svg></div><h3>Payment Methods</h3><p>Available payment methods:</p><div class="chips"><span>Cash</span><span>Mobile Money</span><span>Bank</span></div></div>
  <div class="panel setting-card"><div class="setting-icon"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M8 10V7a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="1.7"/></svg></div><h3>Security</h3><p class="muted">Protect your account by changing your password and signing out when you finish using the system.</p><div class="form-actions"><a class="btn primary" href="change_password.php">Change Password</a><a class="btn danger-btn" href="logout.php">Sign Out</a></div></div>
</div>
<?php require "partials/footer.php"; ?>