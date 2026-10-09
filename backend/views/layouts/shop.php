<?php
/**
 * Customer portal layout: header navigation, content, footer.
 *
 * @var string $content
 * @var string|null $pageTitle
 * @var string|null $active
 */

use App\Core\CustomerAuth;
use App\Services\Cart;

$pageTitle = $pageTitle ?? 'Customer Portal';
$active = $active ?? '';
$customer = CustomerAuth::customer();
$nav = static fn(string $key): string => $active === $key ? 'active' : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="preload" href="/assets/fonts/inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<title><?= e($pageTitle) ?> - MSINDA Food Shop</title>
<link rel="stylesheet" href="<?= asset('css/portal.css') ?>">
<script src="<?= asset('js/portal.js') ?>" defer></script>
</head>
<body class="portal-body">
<header class="portal-header">
  <a class="portal-brand" href="/shop"><img src="<?= asset('img/brand/msinda-emblem.png') ?>" alt="MSINDA Food Shop" width="46" height="46"><span>MSINDA Food Shop</span></a>
  <nav class="portal-nav" aria-label="Customer navigation">
    <a href="/shop" class="<?= $nav('shop') ?>">Shop</a>
    <?php if ($customer): ?><a href="/shop/orders" class="<?= $nav('orders') ?>">My Orders</a><?php endif; ?>
    <a href="/shop/cart" class="cart-link <?= $nav('cart') ?>">Cart <b><?= Cart::count() ?></b></a>
    <?php if ($customer): ?>
      <span class="portal-user">Hi, <?= e($customer['name']) ?></span><a class="logout" href="/shop/logout">Log out</a>
    <?php else: ?>
      <a href="/shop">Sign in</a>
    <?php endif; ?>
  </nav>
</header>
<main class="portal-main">
<?= $content ?>
</main>
<footer class="portal-footer">© <?= date('Y') ?> MSINDA Food Shop | All Rights Reserved</footer>
</body>
</html>
