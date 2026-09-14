<?php $pageTitle=$pageTitle??'Egg Sales System'; ?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pageTitle)?> - Egg Sales System</title><link rel="stylesheet" href="assets/style.css">
</head><body><aside class="sidebar"><div class="logo">🥚 <span>EGG SALES</span></div>
<nav>
<a class="<?=($active??'')==='dashboard'?'active':''?>" href="dashboard.php">Dashboard</a>
<a class="<?=($active??'')==='sales'?'active':''?>" href="sales.php">Sales</a>
<a class="<?=($active??'')==='products'?'active':''?>" href="products.php">Products</a>
<a class="<?=($active??'')==='inventory'?'active':''?>" href="inventory.php">Inventory</a>
<a class="<?=($active??'')==='customers'?'active':''?>" href="customers.php">Customers</a>
<a class="<?=($active??'')==='suppliers'?'active':''?>" href="suppliers.php">Suppliers</a>
<a class="<?=($active??'')==='purchases'?'active':''?>" href="purchases.php">Purchases</a>
<a class="<?=($active??'')==='expenses'?'active':''?>" href="expenses.php">Expenses</a>
<a class="<?=($active??'')==='reports'?'active':''?>" href="reports.php">Reports</a>
</nav><div class="sidebar-bottom"><a href="logout.php">Logout</a></div></aside>
<main class="content"><header class="topbar"><div><h2><?=e($pageTitle)?></h2><span class="muted">Manage your egg business</span></div>
<div class="user"><?=e($_SESSION['name'])?> · <?=e($_SESSION['role'])?></div></header>