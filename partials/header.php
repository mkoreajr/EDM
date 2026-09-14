<?php $pageTitle=$pageTitle??'Egg Sales System'; ?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pageTitle)?> - EDM Kienyeji Egg Shop</title><link rel="stylesheet" href="assets/style.css">
</head><body class="app-body">
<aside class="sidebar">
  <div class="side-brand">
    <div class="side-egg brand-mark-code"><svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path d="M12 2C8.5 5.2 6 9.1 6 13.1A6 6 0 0 0 18 13c0-4-2.5-7.8-6-11Z" fill="currentColor"/></svg></div>
    <div><strong>EDM <span>KIENYEJI</span></strong><b>— EGG SHOP —</b><small>Fresh Eggs • Healthy Families</small></div>
  </div>
  <nav class="side-nav">
    <a class="<?=($active??'')==='dashboard'?'active':''?>" href="dashboard.php"><span class="nav-ico">⌂</span>Home</a>
    <a class="<?=($active??'')==='sales'?'active':''?>" href="sales.php"><span class="nav-ico">🛒</span>Sales (POS)</a>
    <a class="<?=($active??'')==='products'?'active':''?>" href="products.php"><span class="nav-ico">🥚</span>Products</a>
    <a class="<?=($active??'')==='inventory'?'active':''?>" href="inventory.php"><span class="nav-ico">▣</span>Stock</a>
    <a class="<?=($active??'')==='customers'?'active':''?>" href="customers.php"><span class="nav-ico">♟</span>Customers</a>
    <a class="<?=($active??'')==='settings'?'active':''?>" href="settings.php"><span class="nav-ico">⚙</span>Settings</a>
  </nav>
  <div class="side-slogan">Kuku Bora,<br>Mayai Bora,<br>Maisha Bora</div>
  <div class="side-bottom">Fresh Eggs<br>Healthy Families<br>A Better Tomorrow</div>
</aside>
<main class="content">
<header class="topbar app-topbar">
  <button class="menu-btn" type="button" aria-label="Menu">☰</button>
  <div class="searchbox"><span>⌕</span><input type="search" placeholder="Search products, customers, sales..."></div>
  <div class="top-actions">
    <button class="notify" type="button" aria-label="Notifications">♧<i>3</i></button>
    <div class="profile"><span class="avatar"><?=strtoupper(substr((string)($_SESSION['name']??'Admin'),0,2))?></span><div><strong><?=e($_SESSION['name']??'Administrator')?></strong><small><?=e($_SESSION['role']??'Admin')?></small></div><span class="chev">⌄</span></div>
  </div>
</header>