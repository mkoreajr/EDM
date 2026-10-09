<?php
/**
 * Standalone error page (no database access, safe to render on failures).
 * @var int $code
 * @var string $title
 * @var string $message
 */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= (int)$code ?> — <?= e($title) ?></title>
<style>
@font-face{font-family:Inter;font-weight:100 900;font-display:swap;src:url("/assets/fonts/inter-latin-wght-normal.woff2") format("woff2")}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px 16px;font-family:Inter,system-ui,-apple-system,"Segoe UI",Arial,sans-serif;color:#14231d;-webkit-font-smoothing:antialiased;
  background:radial-gradient(600px 300px at 100% 0,rgba(242,201,76,.18),transparent 70%),radial-gradient(600px 320px at 0 100%,rgba(34,147,95,.14),transparent 70%),#f5f6f2}
.card{width:min(520px,100%);background:#fff;border:1px solid #e3e8e5;border-radius:22px;padding:36px 30px;text-align:center;box-shadow:0 24px 48px -16px rgba(20,35,29,.28)}
.code{display:inline-grid;place-items:center;min-width:96px;height:96px;padding:0 18px;border-radius:24px;background:#e3f1ea;font-size:44px;font-weight:800;letter-spacing:-.03em;color:#17634a;line-height:1}
h1{font-size:22px;font-weight:800;letter-spacing:-.02em;margin:18px 0 8px}
p{color:#64756d;font-size:14.5px;line-height:1.55;margin:0 0 24px;white-space:pre-wrap;word-break:break-word;text-align:<?= app_debug() ? 'left' : 'center' ?>}
a{display:inline-flex;align-items:center;height:46px;padding:0 22px;border-radius:10px;background:linear-gradient(180deg,#22935f,#1b7a57);color:#fff;text-decoration:none;font-weight:700;font-size:15px}
</style>
</head>
<body>
<main class="card">
  <div class="code"><?= (int)$code ?></div>
  <h1><?= e($title) ?></h1>
  <p><?= e($message) ?></p>
  <a href="/">Go to Home</a>
</main>
</body>
</html>
