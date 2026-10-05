<?php require_once __DIR__ . '/../auth.php'; $pageTitle=$pageTitle??'Customer Portal'; $customer=portal_current_customer(); ?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="/shop/">
<title><?=pe($pageTitle)?> - MSINDA Food Shop</title><link rel="stylesheet" href="assets/portal.css">
</head><body class="portal-body">
<header class="portal-header">
  <a class="portal-brand" href="shop.php"><img src="../assets/branding/msinda-agricultural-emblem.png" alt="MSINDA Food Shop"><span>MSINDA Food Shop</span></a>
  <nav class="portal-nav" aria-label="Customer navigation">
    <a href="shop.php" class="<?=($active??'')==='shop'?'active':''?>">Shop</a>
    <?php if($customer): ?><a href="orders.php" class="<?=($active??'')==='orders'?'active':''?>">My Orders</a><?php endif; ?>
    <a href="cart.php" class="cart-link <?=($active??'')==='cart'?'active':''?>">Cart <b><?=portal_cart_count()?></b></a>
    <?php if($customer): ?><span class="portal-user">Hi, <?=pe($customer['name'])?></span><a class="logout" href="logout.php">Log out</a><?php else: ?><a href="login.php">Sign in</a><?php endif; ?>
  </nav>
</header>
<main class="portal-main">
