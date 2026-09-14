<?php
require "auth.php"; $pageTitle="Settings"; $active="settings"; require "partials/header.php";
?>
<div class="page-intro"><div><div class="welcome-kicker">SYSTEM</div><h1>Settings</h1><p class="muted">Manage your account and shop preferences.</p></div></div>
<div class="settings-grid">
  <div class="panel setting-card"><div class="setting-icon">👤</div><h3>Account</h3><p>Signed in as <strong><?=e($_SESSION['name']??'Administrator')?></strong>.</p><p class="muted">Role: <?=e($_SESSION['role']??'Admin')?></p></div>
  <div class="panel setting-card"><div class="setting-icon">💳</div><h3>Payment Methods</h3><p>Available payment methods:</p><div class="chips"><span>Cash</span><span>Mobile Money</span><span>Bank</span></div></div>
  <div class="panel setting-card"><div class="setting-icon">🔐</div><h3>Security</h3><p class="muted">Protect your account by signing out when you finish using the system.</p><a class="btn danger-btn" href="logout.php">Sign Out</a></div>
</div>
<?php require "partials/footer.php"; ?>