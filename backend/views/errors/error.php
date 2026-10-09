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
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;font-family:Inter,Arial,Helvetica,sans-serif;background:linear-gradient(135deg,#f4f7f5,#e8efec);color:#17352b}
.card{width:min(520px,100%);background:#fff;border-radius:16px;padding:34px 30px;text-align:center;box-shadow:0 20px 50px rgba(18,58,45,.14)}
.code{font-size:56px;font-weight:800;color:#159b62;line-height:1}
h1{font-size:22px;margin:12px 0 8px}
p{color:#60746c;font-size:14px;line-height:1.55;margin:0 0 22px;white-space:pre-wrap;word-break:break-word;text-align:<?= app_debug() ? 'left' : 'center' ?>}
a{display:inline-block;padding:11px 20px;border-radius:8px;background:#159b62;color:#fff;text-decoration:none;font-weight:700;font-size:14px}
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
