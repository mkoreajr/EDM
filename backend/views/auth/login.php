<?php
/**
 * @var list<array{id:int,filename:string}> $slides
 * @var string|null $error
 * @var string $username
 */

$defaultSlides = [
    ['src' => asset('img/login-slides/edm-rice-unga-1.webp'), 'alt' => 'EDM Rice and Unga products'],
    ['src' => asset('img/login-slides/edm-rice-unga-2.webp'), 'alt' => 'EDM Rice and Unga products'],
    ['src' => asset('img/login-slides/edm-juice.webp'),       'alt' => 'EDM Juice products'],
];
$slideList = $slides
    ? array_map(static fn(array $s) => ['src' => url('/slides/image', ['id' => (int)$s['id']]), 'alt' => $s['filename']], $slides)
    : $defaultSlides;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="preload" href="/assets/fonts/inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<title>MSINDA Food Shop — Login</title>
<link rel="preload" href="<?= e($slideList[0]['src']) ?>" as="image" fetchpriority="high">
<link rel="stylesheet" href="<?= asset('css/login.css') ?>">
<script src="<?= asset('js/login.js') ?>" defer></script>
</head>
<body>
<div class="login-page">
  <section class="left" aria-label="EDM products">
    <div class="login-slideshow">
      <div class="left-copy">
        <div class="left-kicker">MSINDA Food Shop</div>
        <h2 class="left-title">Mchele Bora, Unga Bora,<br><span>Mayai Bora.</span></h2>
        <p class="left-sub">Sales, stock, customers and online orders for your shop — all in one place.</p>
      </div>
      <div class="slide-stage">
        <?php foreach ($slideList as $i => $slide): ?>
          <div class="login-slide <?= $i === 0 ? 'active' : '' ?>">
            <img <?= $i === 0 ? 'src' : 'data-src' ?>="<?= e($slide['src']) ?>" alt="<?= e($slide['alt']) ?>" decoding="async" <?= $i === 0 ? 'fetchpriority="high"' : '' ?>>
          </div>
        <?php endforeach; ?>
        <div class="login-slide-overlay"></div>
      </div>
      <div class="left-foot">
        <div class="slide-dots" aria-hidden="true">
          <?php foreach ($slideList as $i => $slide): ?><span class="dot <?= $i === 0 ? 'active' : '' ?>"></span><?php endforeach; ?>
        </div>
        <div class="left-secure"><svg viewBox="0 0 24 24"><path d="M12 3 20 6v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>Secure • Reliable • Built for Food Businesses</div>
      </div>
    </div>
  </section>
  <section class="right-panel">
    <main class="login-card">
      <div class="logo-wrap"><img src="<?= asset('img/brand/msinda-emblem.png') ?>" alt="MSINDA Food Shop"></div>
      <div class="welcome">Welcome back</div>
      <h1 class="login-title">Sign in to your shop</h1>
      <p class="sub">Access your MSINDA Food Shop dashboard.</p>
      <?php if ($error): ?><div class="error" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form method="post" action="/login">
        <?= csrf_field() ?>
        <div class="field">
          <label for="username">Username</label>
          <div class="input">
            <span class="input-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.8-3.2 3.2-5 7-5s6.2 1.8 7 5"/></svg></span>
            <input id="username" type="text" name="username" autocomplete="username" placeholder="Enter your username" value="<?= e($username) ?>" maxlength="50" required autofocus>
          </div>
        </div>
        <div class="field pass-wrap">
          <label for="password">Password</label>
          <div class="input">
            <span class="input-icon"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
            <input id="password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
          </div>
          <button class="eye" type="button" id="togglePassword" aria-label="Show password"><svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg></button>
        </div>
        <div class="forgot-row"><a class="forgot" href="/admin-recovery">Forgot password?</a></div>
        <button class="btn" type="submit">Sign in</button>
      </form>
      <a class="customer-portal-link" href="/shop">Customer Portal → Order Online</a>
    </main>
    <div class="right-footer">
      <div class="support">ICT Support: <a href="tel:+255682657202">+255 682 657 202</a></div>
      <div class="footer">© <?= date('Y') ?> MSINDA Food Shop · v<?= e(APP_VERSION) ?></div>
    </div>
  </section>
</div>
</body>
</html>
