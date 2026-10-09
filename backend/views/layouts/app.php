<?php
/**
 * Main application layout: sidebar, top bar, notifications, footer.
 *
 * @var string $content
 * @var string|null $pageTitle
 * @var string|null $active
 * @var string|null $bodyClass
 * @var list<string>|null $styles extra stylesheets for this page, e.g. ['css/orders.css']
 */

use App\Core\Auth;
use App\Core\DB;
use App\Services\Notifications;

$pageTitle = $pageTitle ?? 'Dashboard';
$active = $active ?? '';
$nav = static fn(string $key): string => $active === $key ? 'active' : '';

$userId = Auth::id();
$unread = Notifications::unreadCount($userId);
$notifItems = Notifications::latest($userId, 5);
$userName = Auth::name();
$userRole = Auth::role() ?: 'Admin';
$initials = strtoupper(mb_substr($userName, 0, 2));
$pendingOrders = Auth::isAdmin() ? (int)DB::value("SELECT COUNT(*) FROM orders WHERE status = 'Pending'") : 0;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="preload" href="/assets/fonts/inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<title><?= e($pageTitle) ?> - EDM Kienyeji Food Shop</title>
<link rel="preload" href="<?= asset('img/brand/msinda-emblem.png') ?>" as="image">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<?php foreach ($styles ?? [] as $stylesheet): ?><link rel="stylesheet" href="<?= asset($stylesheet) ?>">
<?php endforeach; ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
</head>
<body class="app-body <?= e($bodyClass ?? '') ?>" data-idle-timeout="<?= Auth::idleTimeout() ?>">
<aside class="sidebar">
  <div class="side-brand side-brand-logo">
    <img src="<?= asset('img/brand/msinda-emblem.png') ?>" alt="" class="sidebar-edm-logo">
    <span class="side-brand-name"><strong>MSINDA</strong><small>Food Shop</small></span>
  </div>
  <nav class="side-nav">
    <div class="side-nav-label">Menu</div>
    <a class="<?= $nav('dashboard') ?>" href="/dashboard"><span class="nav-ico"><svg viewBox="0 0 24 24"><path d="m4 11 8-7 8 7v8a1 1 0 0 1-1 1h-4v-5H9v5H5a1 1 0 0 1-1-1v-8Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span>Home</a>
    <a class="<?= $nav('sales') ?>" href="/sales"><span class="nav-ico"><svg viewBox="0 0 24 24"><path d="M3 4h2l2 11h10l2-8H6" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="9" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/></svg></span>Sales (POS)</a>
    <?php if (Auth::isAdmin()): ?>
    <a class="<?= $nav('online_orders') ?>" href="/online-orders"><span class="nav-ico"><svg viewBox="0 0 24 24"><path d="M4 6h16v12H4z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M7 9h10M7 13h6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>Online Orders<?php if ($pendingOrders > 0): ?><b class="nav-badge" title="<?= $pendingOrders ?> pending"><?= min($pendingOrders, 99) ?></b><?php endif; ?></a>
    <a class="<?= $nav('products') ?>" href="/products"><span class="nav-ico"><svg viewBox="0 0 24 24"><path d="M12 3c-2.9 3.1-6 6.9-6 11a6 6 0 0 0 12 0c0-4.1-3.1-7.9-6-11Z" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></span>Products</a>
    <?php endif; ?>
    <a class="<?= $nav('customers') ?>" href="/customers"><span class="nav-ico"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M5.5 19c.7-3.1 2.8-5 6.5-5s5.8 1.9 6.5 5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></span>Customers</a>
    <a class="<?= $nav('reports') ?>" href="/reports"><span class="nav-ico"><svg viewBox="0 0 24 24"><path d="M5 19V9M12 19V5M19 19v-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3.5 19.5h17" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span>Reports</a>
    <?php if (Auth::isAdmin()): ?>
    <a class="<?= $nav('settings') ?>" href="/settings"><span class="nav-ico"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3.5" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M19 12a7 7 0 0 0-.2-1.7l1.5-1-1.7-3-1.7.7a7 7 0 0 0-2.3-1.3L14.3 4h-3l-.3 1.7A7 7 0 0 0 8.7 7L7 6.3l-1.7 3 1.5 1A7 7 0 0 0 6.5 12c0 .6.1 1.2.3 1.7l-1.5 1 1.7 3 1.7-.7a7 7 0 0 0 2.3 1.3l.3 1.7h3l.3-1.7a7 7 0 0 0 2.3-1.3l1.7.7 1.7-3-1.5-1c.1-.5.2-1.1.2-1.7Z" fill="none" stroke="currentColor" stroke-width="1.3"/></svg></span>Settings</a>
    <?php endif; ?>
  </nav>
  <div class="side-slogan">Mchele Bora, Unga Bora, Mayai Bora</div>
  <div class="side-bottom">Mchele<br>Unga<br>Mayai</div>
</aside>
<main class="content">
<header class="topbar app-topbar">
  <button class="menu-btn" type="button" aria-label="Menu">☰</button>
  <div class="topbar-title"><small>MSINDA Food Shop</small><strong><?= e($pageTitle) ?></strong></div>
  <div class="top-actions">
    <div class="dropdown-wrap notification-wrap">
      <button class="notify" id="notificationButton" type="button" aria-label="Notifications" aria-expanded="false">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 21h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        <?php if ($unread > 0): ?><i id="notificationCount"><?= min($unread, 99) ?></i><?php endif; ?>
      </button>
      <div class="dropdown notification-dropdown" id="notificationDropdown">
        <div class="dropdown-head"><strong>Notifications</strong><span><?= number_format($unread) ?> unread</span></div>
        <div class="notification-list">
        <?php if (!$notifItems): ?>
          <div class="empty-notifications">No notifications.</div>
        <?php else: foreach ($notifItems as $n): ?>
          <a class="notification-item <?= empty($n['read_at']) ? 'unread' : '' ?>" href="<?= url('/notifications', ['id' => (int)$n['id']]) ?>">
            <span class="n-icon"><svg viewBox="0 0 24 24"><path d="M12 4a7 7 0 0 0-7 7v3l-2 3h18l-2-3v-3a7 7 0 0 0-7-7Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M9.5 20h5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
            <span class="n-copy"><strong><?= e($n['title']) ?></strong><small><?= e($n['message']) ?></small></span>
            <?php if (empty($n['read_at'])): ?><b class="unread-mark">Unread</b><?php endif; ?>
          </a>
        <?php endforeach; endif; ?>
        </div>
        <a class="all-notifications" href="/notifications">View More Notifications <span>→</span></a>
      </div>
    </div>
    <div class="dropdown-wrap profile-wrap">
      <button class="profile profile-button" id="profileButton" type="button" aria-expanded="false">
        <span class="avatar"><?= e($initials) ?></span>
        <span class="profile-text"><strong><?= e($userName) ?></strong><small><?= e($userRole) ?></small></span>
        <span class="chev">⌄</span>
      </button>
      <div class="dropdown profile-dropdown" id="profileDropdown">
        <div class="profile-menu-head"><span class="avatar small-avatar"><?= e($initials) ?></span><div><strong><?= e($userName) ?></strong><small><?= e($userRole) ?></small></div></div>
        <?php if (Auth::isAdmin()): ?><a href="/account/name"><svg viewBox="0 0 24 24"><path d="M4 20h4l10-10-4-4L4 16zM13 7l4 4" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>Change Name</a><?php endif; ?>
        <a href="/account/password"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M8 10V7a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>Change Password</a>
        <a class="logout-link" href="/logout"><svg viewBox="0 0 24 24"><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5M14 8l4 4-4 4M18 12H9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>Log Out</a>
      </div>
    </div>
  </div>
</header>
<?= $content ?>
<footer class="app-footer">
  <div class="footer-content">
    <div>© <?= date('Y') ?> MSINDA Food Shop | All Rights Reserved.</div>
    <div class="footer-tagline">“Mchele • Unga • Mayai”</div>
  </div>
</footer>
</main>

<div class="clear-confirm-overlay" id="clearConfirmOverlay" aria-hidden="true">
  <div class="clear-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="clearConfirmTitle">
    <div class="clear-confirm-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none"><path d="M12 3.5 21 20H3L12 3.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 9v5M12 17.2v.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    </div>
    <h3 id="clearConfirmTitle">Are you sure you want to delete the notifications?</h3>
    <p>This action will remove all notifications, including unread notifications. This cannot be undone.</p>
    <div class="clear-confirm-buttons">
      <button type="button" class="clear-confirm-no" id="clearConfirmNo">No</button>
      <button type="button" class="clear-confirm-yes" id="clearConfirmYes">Yes, Delete</button>
    </div>
  </div>
</div>
</body>
</html>
