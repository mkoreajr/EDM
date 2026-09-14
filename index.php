<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit; }
$error = $_SESSION['login_error'] ?? ''; unset($_SESSION['login_error']);
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Egg Sales System - Login</title><link rel="stylesheet" href="assets/style.css">
</head><body class="login-page">
<div class="login-card">
<div class="brand">🥚</div><h1>Egg Sales System</h1><p class="muted">Business Management System</p>
<?php if($error): ?><div class="alert danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form method="post" action="login.php">
<label>Username</label><input name="username" required autofocus>
<label>Password</label><input type="password" name="password" required>
<button class="btn primary full">LOGIN</button>
</form>
<div class="demo">Default: <b>admin</b> / <b>admin123</b></div>
</div></body></html>