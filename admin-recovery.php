<?php
session_start();
require __DIR__ . '/config/database.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$code = (string)($_ENV['ADMIN_RECOVERY_CODE'] ?? getenv('ADMIN_RECOVERY_CODE') ?? '');
$message = '';
$error = '';

if ($code === '') {
    http_response_code(404);
    exit('Admin recovery is not enabled.');
}

try {
    $conn->query("CREATE TABLE IF NOT EXISTS system_recovery (id SMALLINT PRIMARY KEY, admin_recovery_used BOOLEAN NOT NULL DEFAULT FALSE)");
    $conn->query("INSERT INTO system_recovery(id, admin_recovery_used) VALUES (1, FALSE) ON CONFLICT (id) DO NOTHING");
    $row = $conn->query("SELECT admin_recovery_used FROM system_recovery WHERE id=1")->fetch_assoc();
    $used = !empty($row['admin_recovery_used']);
} catch(Throwable $e) {
    http_response_code(500);
    exit('Recovery service is unavailable.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered = trim($_POST['recovery_code'] ?? '');
    $newTemp = (string)($_POST['temporary_password'] ?? '');

    if ($used) {
        $error = 'Admin recovery has already been used. Generate a new recovery code in Render before using this page again.';
    } elseif (!hash_equals($code, $entered)) {
        $error = 'Invalid recovery code.';
    } elseif (strlen($newTemp) < 8) {
        $error = 'Temporary password must be at least 8 characters.';
    } else {
        try {
            $hash = hash('sha256', $newTemp);
            $st = $conn->prepare("UPDATE users SET password=?, must_change_password=TRUE WHERE username='admin'");
            $st->bind_param("s", $hash);
            $st->execute();
            $conn->query("UPDATE system_recovery SET admin_recovery_used=TRUE WHERE id=1");
            $message = 'Admin password reset successfully. Sign in with username "admin" and the temporary password you just created. The system will require you to change it.';
            $used = true;
        } catch(Throwable $e) {
            $error = 'Could not reset the admin password.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Password Recovery</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Arial,Helvetica,sans-serif;background:linear-gradient(135deg,#006b4f,#79c879 48%,#fff0a8 100%);display:flex;align-items:center;justify-content:center;padding:24px;color:#073b2c}
.card{width:min(460px,100%);background:#fff;border-radius:18px;padding:30px;box-shadow:0 20px 60px rgba(0,60,40,.18)}
h1{margin:0 0 8px;font-size:27px}.sub{color:#617586;margin-bottom:24px}
label{display:block;font-weight:500;font-size:14px;margin:14px 0 7px}input{width:100%;height:46px;border:1px solid #d6e1dc;border-radius:9px;padding:0 13px;font:400 14px Arial,Helvetica,sans-serif}
button{width:100%;height:46px;border:0;border-radius:9px;background:#008b5a;color:#fff;font-weight:700;font-size:14px;margin-top:18px;cursor:pointer}
.alert{padding:12px;border-radius:9px;margin-bottom:16px;font-size:14px}.ok{background:#e2f6eb;color:#08623f}.bad{background:#fde7e7;color:#9f1e28}
a{display:block;text-align:center;margin-top:18px;color:#007b52;text-decoration:none;font-size:14px}
.note{font-size:12px;color:#758794;margin-top:15px;line-height:1.5}
</style>
</head>
<body>
<div class="card">
<h1>Admin Password Recovery</h1>
<div class="sub">Reset the administrator password without deleting your business data.</div>
<?php if($message): ?><div class="alert ok"><?=h($message)?></div><?php endif; ?>
<?php if($error): ?><div class="alert bad"><?=h($error)?></div><?php endif; ?>
<?php if(!$used): ?>
<form method="post">
<label>Recovery Code</label>
<input name="recovery_code" type="password" required autocomplete="off">
<label>New Temporary Password</label>
<input name="temporary_password" type="password" minlength="8" required autocomplete="new-password">
<button type="submit">Reset Admin Password</button>
</form>
<?php endif; ?>
<a href="index.php">← Back to Sign In</a>
<div class="note">After signing in, change the temporary password immediately. This recovery action does not delete sales, stock, products, customers or other records.</div>
</div>
</body>
</html>
