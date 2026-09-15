<?php $pageTitle=$pageTitle??'Egg Sales System'; ?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pageTitle)?> - EDM Kienyeji Egg Shop</title><link rel="stylesheet" href="assets/style.css">
</head><body class="app-body <?=!empty($required) ? "password-required-mode" : ""?>">
<aside class="sidebar">
  <div class="side-brand">
    <div class="side-egg brand-mark-code"><svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path d="M12 2C8.5 5.2 6 9.1 6 13.1A6 6 0 0 0 18 13c0-4-2.5-7.8-6-11Z" fill="currentColor"/></svg></div>
    <div><strong>EDM <span>KIENYEJI</span></strong><b>— EGG SHOP —</b><small>Fresh Eggs • Healthy Families</small></div>
  </div>
  <nav class="side-nav">
    <a class="<?=($active??'')==='dashboard'?'active':''?>" href="dashboard.php"><span class="nav-ico"><svg viewBox="0 0 24 24"><path d="m4 11 8-7 8 7v8a1 1 0 0 1-1 1h-4v-5H9v5H5a1 1 0 0 1-1-1v-8Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span>Home</a>
    <a class="<?=($active??'')==='sales'?'active':''?>" href="sales.php"><span class="nav-ico"><svg viewBox="0 0 24 24"><path d="M3 4h2l2 11h10l2-8H6" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="9" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/></svg></span>Sales (POS)</a>
    <?php if(is_admin()): ?>
    <a class="<?=($active??'')==='products'?'active':''?>" href="products.php"><span class="nav-ico"><svg viewBox="0 0 24 24"><path d="M12 3c-2.9 3.1-6 6.9-6 11a6 6 0 0 0 12 0c0-4.1-3.1-7.9-6-11Z" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></span>Products</a>
    <a class="<?=($active??'')==='inventory'?'active':''?>" href="inventory.php"><span class="nav-ico"><svg viewBox="0 0 24 24"><path d="M3.5 9.2 12 4l8.5 5.2v10.1H3.5V9.2Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 9.2H20.5M7 12.2h3v3H7v-3Zm7 0h3v3h-3v-3Z" fill="none" stroke="currentColor" stroke-width="1.5"/></svg></span>Stock</a>
    <?php endif; ?>
    <a class="<?=($active??'')==='customers'?'active':''?>" href="customers.php"><span class="nav-ico"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M5.5 19c.7-3.1 2.8-5 6.5-5s5.8 1.9 6.5 5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></span>Customers</a>
    <?php if(is_admin()): ?>
    <a class="<?=($active??'')==='settings'?'active':''?>" href="settings.php"><span class="nav-ico"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3.5" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M19 12a7 7 0 0 0-.2-1.7l1.5-1-1.7-3-1.7.7a7 7 0 0 0-2.3-1.3L14.3 4h-3l-.3 1.7A7 7 0 0 0 8.7 7L7 6.3l-1.7 3 1.5 1A7 7 0 0 0 6.5 12c0 .6.1 1.2.3 1.7l-1.5 1 1.7 3 1.7-.7a7 7 0 0 0 2.3 1.3l.3 1.7h3l.3-1.7a7 7 0 0 0 2.3-1.3l1.7.7 1.7-3-1.5-1c.1-.5.2-1.1.2-1.7Z" fill="none" stroke="currentColor" stroke-width="1.3"/></svg></span>Settings</a>
    <?php endif; ?>
  </nav>
  <div class="side-slogan">Kuku Bora,<br>Mayai Bora,<br>Maisha Bora</div>
  <div class="side-bottom">Fresh Eggs<br>Healthy Families<br>A Better Tomorrow</div>
</aside>
<main class="content">
<header class="topbar app-topbar">
  <button class="menu-btn" type="button" aria-label="Menu">☰</button>
  <div class="searchbox"><span>⌕</span><input type="search" placeholder="Search products, customers, sales..."></div>
  <?php
    $uid=(int)($_SESSION['user_id']??0);
    $unread=0; $notifItems=[];
    if($uid){
      $ur=$conn->query("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id={$uid} AND read_at IS NULL");
      if($ur){ $unread=(int)($ur->fetch_assoc()['unread_count']??0); }
      $nr=$conn->query("SELECT id,title,message,read_at,created_at FROM notifications WHERE user_id={$uid} ORDER BY created_at DESC LIMIT 5");
      if($nr){ while($row=$nr->fetch_assoc()){ $notifItems[]=$row; } }
    }
  ?>
  <div class="top-actions">
    <div class="dropdown-wrap notification-wrap">
      <button class="notify" id="notificationButton" type="button" aria-label="Notifications" aria-expanded="false">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 21h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        <?php if($unread>0):?><i id="notificationCount"><?=min($unread,99)?></i><?php endif;?>
      </button>
      <div class="dropdown notification-dropdown" id="notificationDropdown">
        <div class="dropdown-head"><strong>Notifications</strong><span><?=number_format($unread)?> unread</span></div>
        <div class="notification-list">
        <?php if(!$notifItems):?><div class="empty-notifications">No notifications.</div><?php else: foreach($notifItems as $n): ?>
          <a class="notification-item <?=empty($n['read_at'])?'unread':''?>" href="notifications.php?id=<?=(int)$n['id']?>">
            <span class="n-icon"><svg viewBox="0 0 24 24"><path d="M12 4a7 7 0 0 0-7 7v3l-2 3h18l-2-3v-3a7 7 0 0 0-7-7Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M9.5 20h5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
            <span class="n-copy"><strong><?=e($n['title'])?></strong><small><?=e($n['message'])?></small></span>
            <?php if(empty($n['read_at'])):?><b class="unread-mark">Unread</b><?php endif;?>
          </a>
        <?php endforeach; endif;?>
        </div>
        <a class="all-notifications" href="notifications.php">View More Notifications <span>→</span></a>
      </div>
    </div>
    <div class="dropdown-wrap profile-wrap">
      <button class="profile profile-button" id="profileButton" type="button" aria-expanded="false">
        <span class="avatar"><?=strtoupper(substr((string)($_SESSION['name']??'Admin'),0,2))?></span>
        <span class="profile-text"><strong><?=e($_SESSION['name']??'Administrator')?></strong><small><?=e($_SESSION['role']??'Admin')?></small></span>
        <span class="chev">⌄</span>
      </button>
      <div class="dropdown profile-dropdown" id="profileDropdown">
        <div class="profile-menu-head"><span class="avatar small-avatar"><?=strtoupper(substr((string)($_SESSION['name']??'Admin'),0,2))?></span><div><strong><?=e($_SESSION['name']??'Administrator')?></strong><small><?=e($_SESSION['role']??'Admin')?></small></div></div>
        <?php if(is_admin()): ?><a href="change_name.php"><svg viewBox="0 0 24 24"><path d="M4 20h4l10-10-4-4L4 16zM13 7l4 4" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>Change Name</a><?php endif; ?>
        <a href="change_password.php"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M8 10V7a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>Change Password</a>
        <a class="logout-link" href="logout.php"><svg viewBox="0 0 24 24"><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5M14 8l4 4-4 4M18 12H9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>Log Out</a>
      </div>
    </div>
  </div>
</header>
<?php
<?php
/* Low-stock warning is based on TOTAL available tray stock.
   Adding stock above 50 trays removes the warning automatically.
   Selling stock below 50 trays shows it again. */
$totalTrayStock = 0;
$lsr = $conn->query("SELECT COALESCE(SUM(stock_quantity),0) AS total_trays FROM products WHERE LOWER(unit) = 'tray'");
if ($lsr && ($lsrow = $lsr->fetch_assoc())) {
    $totalTrayStock = (float)$lsrow['total_trays'];
}
?>
<?php if ($totalTrayStock > 0 && $totalTrayStock < 50): ?>
<div class="low-stock-alert" role="alert">
  <span class="low-stock-alert-icon">
    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path d="M12 3 21 20H3L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
      <path d="M12 9v5M12 17.2v.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
    </svg>
  </span>
  <span>
    <strong>Stock Low:</strong>
    Total stock is below 50 trays. Current stock:
    <b><?=number_format($totalTrayStock,2)?> trays</b>
  </span>
  <a href="inventory.php">View Stock</a>
</div>
<?php endif; ?>
