<?php
/**
 * @var array<string,mixed> $stats
 * @var list<array{empty:bool,title:string,text:string,value?:string}> $alerts
 */

use App\Core\Auth;

$warningIcon = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3 21 20H3L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 9v5M12 17.2v.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
?>
<?php foreach ($alerts as $alert): ?>
<div class="low-stock-alert <?= $alert['empty'] ? 'stock-alert-empty' : '' ?>" role="alert">
  <span class="low-stock-alert-icon"><?= $warningIcon ?></span>
  <span><strong><?= e($alert['title']) ?></strong> <?= e($alert['text']) ?><?php if (!empty($alert['value'])): ?> <b><?= e($alert['value']) ?></b><?php endif; ?></span>
  <?php if (Auth::isAdmin()): ?><a href="/inventory">View Stock</a><?php endif; ?>
</div>
<?php endforeach; ?>

<section class="welcome-banner">
  <div class="welcome-copy">
    <div class="welcome-kicker">GOOD DAY,</div>
    <h1><?= e(Auth::name()) ?>!</h1>
    <p>Welcome to MSINDA Food Shop.</p>
    <a class="hero-btn" href="/sales"><span>＋</span> New Sale (POS) <b>›</b></a>
  </div>
  <div class="welcome-tagline" aria-label="Mchele, Unga, Mayai">
    <span>Mchele • Unga • Mayai</span>
    <strong>Healthy Families</strong>
    <em>A Better Tomorrow</em>
    <i></i>
  </div>
</section>

<section class="stat-grid">
  <a class="stat-card stat-green" href="<?= Auth::isAdmin() ? '/products' : '/sales' ?>"><span class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c-2.9 3.1-6 6.9-6 11a6 6 0 0 0 12 0c0-4.1-3.1-7.9-6-11Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M9.2 16.3c.7 1 1.6 1.5 2.8 1.7" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span><div><small>Total Products</small><strong><?= number_format((int)$stats['products']) ?></strong><em>Rice • Flour • Eggs</em></div><b class="arrow">›</b></a>
  <a class="stat-card stat-yellow" href="<?= Auth::isAdmin() ? '/inventory' : '/sales' ?>"><span class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 9.2 12 4l8.5 5.2v10.1H3.5V9.2Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M3.5 9.2H20.5M7 12.2h3v3H7v-3Zm7 0h3v3h-3v-3ZM7 17.2h3v2.1H7v-2.1Zm7 0h3v2.1h-3v-2.1Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></span><div><small>Total Stock</small><strong><?= qty($stats['stock']) ?></strong><em>Stock across all products</em></div><b class="arrow">›</b></a>
  <a class="stat-card stat-blue" href="/customers"><span class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M5.5 19c.7-3.1 2.8-5 6.5-5s5.8 1.9 6.5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><div><small>Total Customers</small><strong><?= number_format((int)$stats['customers']) ?></strong><em>Registered customers</em></div><b class="arrow">›</b></a>
  <a class="stat-card stat-pink" href="/sales"><span class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2 11h10l2-8H6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="19" r="1.4" fill="currentColor"/><circle cx="17" cy="19" r="1.4" fill="currentColor"/></svg></span><div><small>Today's Sales</small><strong>TZS <?= money($stats['today_total']) ?></strong><em>From <?= number_format((int)$stats['today_count']) ?> sales</em></div><b class="arrow">›</b></a>
</section>

<section class="quick-section">
  <div class="section-heading"><h2>Quick Actions</h2><p>Common tasks to run your food shop</p></div>
  <div class="quick-grid">
    <a class="quick-card q-green" href="/sales"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2 11h10l2-8H6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="19" r="1.4" fill="currentColor"/><circle cx="17" cy="19" r="1.4" fill="currentColor"/></svg></span><div><strong>Sales (POS)</strong><small>Record a new sale</small></div><b>›</b></a>
    <a class="quick-card q-blue" href="/customers"><span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M5.5 19c.7-3.1 2.8-5 6.5-5s5.8 1.9 6.5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><div><strong>View Customer</strong><small>View customer records</small></div><b>›</b></a>
    <?php if (Auth::isAdmin()): ?>
    <a class="quick-card q-soft" href="/products"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c-2.9 3.1-6 6.9-6 11a6 6 0 0 0 12 0c0-4.1-3.1-7.9-6-11Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M9.2 16.3c.7 1 1.6 1.5 2.8 1.7" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span><div><strong>Manage Products</strong><small>Add, edit or view products</small></div><b>›</b></a>
    <a class="quick-card q-gold" href="/purchases"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5 12 4l8 3.5-8 3-8-3Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M4 7.5V16l8 4 8-4V7.5M12 10.5V20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m7 9 5 2 5-2" fill="none" stroke="currentColor" stroke-width="1.5"/></svg></span><div><strong>Update Stock</strong><small>Record a stock purchase</small></div><b>›</b></a>
    <?php endif; ?>
  </div>
</section>

<section class="mini-info-grid">
  <div class="info-box"><span>✓</span><div><strong>Payment Methods</strong><small>Cash • Mobile Money • Bank</small></div></div>
  <div class="info-box"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5 12 4l8 3.5-8 3-8-3Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M4 7.5V16l8 4 8-4V7.5M12 10.5V20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m7 9 5 2 5-2" fill="none" stroke="currentColor" stroke-width="1.5"/></svg></span><div><strong>Stock Management</strong><small>Keep your food inventory organized</small></div></div>
  <div class="info-box"><span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M5.5 19c.7-3.1 2.8-5 6.5-5s5.8 1.9 6.5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><div><strong>Customer Management</strong><small>Keep customer records up to date</small></div></div>
</section>
