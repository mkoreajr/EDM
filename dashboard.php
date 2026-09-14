<?php
require "auth.php"; $pageTitle="Home"; $active="dashboard"; require "partials/header.php";
$sales=(float)$conn->query("SELECT COALESCE(SUM(total_amount),0) x FROM sales")->fetch_assoc()['x'];
$stock=(float)$conn->query("SELECT COALESCE(SUM(stock_quantity),0) x FROM products")->fetch_assoc()['x'];
$products=(int)$conn->query("SELECT COUNT(*) x FROM products")->fetch_assoc()['x'];
$customers=(int)$conn->query("SELECT COUNT(*) x FROM customers")->fetch_assoc()['x'];
$today=(float)$conn->query("SELECT COALESCE(SUM(total_amount),0) x FROM sales WHERE sale_date=CURRENT_DATE")->fetch_assoc()['x'];
$todaySales=(int)$conn->query("SELECT COUNT(*) x FROM sales WHERE sale_date=CURRENT_DATE")->fetch_assoc()['x'];
?>
<section class="welcome-banner">
  <div class="welcome-copy">
    <div class="welcome-kicker">GOOD DAY,</div>
    <h1><?=e($_SESSION['name']??'Administrator')?>!</h1>
    <p>Welcome to EDM Kienyeji Egg Shop.</p>
    <a class="hero-btn" href="sales.php"><span>＋</span> New Sale (POS) <b>›</b></a>
  </div>
</section>

<section class="stat-grid">
  <a class="stat-card stat-green" href="products.php"><span class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c-2.9 3.1-6 6.9-6 11a6 6 0 0 0 12 0c0-4.1-3.1-7.9-6-11Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M9.2 16.3c.7 1 1.6 1.5 2.8 1.7" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span><div><small>Total Products</small><strong><?=number_format($products)?></strong><em>Egg products available</em></div><b class="arrow">›</b></a>
  <a class="stat-card stat-yellow" href="inventory.php"><span class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5 12 4l8 3.5-8 3-8-3Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M4 7.5V16l8 4 8-4V7.5M12 10.5V20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m7 9 5 2 5-2" fill="none" stroke="currentColor" stroke-width="1.5"/></svg></span><div><small>Total Stock</small><strong><?=number_format($stock)?></strong><em>Eggs in stock</em></div><b class="arrow">›</b></a>
  <a class="stat-card stat-blue" href="customers.php"><span class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M5.5 19c.7-3.1 2.8-5 6.5-5s5.8 1.9 6.5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><div><small>Total Customers</small><strong><?=number_format($customers)?></strong><em>Registered customers</em></div><b class="arrow">›</b></a>
  <a class="stat-card stat-pink" href="sales.php"><span class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2 11h10l2-8H6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="19" r="1.4" fill="currentColor"/><circle cx="17" cy="19" r="1.4" fill="currentColor"/></svg></span><div><small>Today's Sales</small><strong>TZS <?=money($today)?></strong><em>From <?=number_format($todaySales)?> sales</em></div><b class="arrow">›</b></a>
</section>

<section class="quick-section">
  <div class="section-heading"><h2>Quick Actions</h2><p>Common tasks to run your egg business</p></div>
  <div class="quick-grid">
    <a class="quick-card q-green" href="sales.php"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2 11h10l2-8H6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="19" r="1.4" fill="currentColor"/><circle cx="17" cy="19" r="1.4" fill="currentColor"/></svg></span><div><strong>New Sale (POS)</strong><small>Record a new sale</small></div><b>›</b></a>
    <a class="quick-card q-soft" href="products.php"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c-2.9 3.1-6 6.9-6 11a6 6 0 0 0 12 0c0-4.1-3.1-7.9-6-11Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M9.2 16.3c.7 1 1.6 1.5 2.8 1.7" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span><div><strong>Manage Products</strong><small>Add, edit or view products</small></div><b>›</b></a>
    <a class="quick-card q-gold" href="inventory.php"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5 12 4l8 3.5-8 3-8-3Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M4 7.5V16l8 4 8-4V7.5M12 10.5V20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m7 9 5 2 5-2" fill="none" stroke="currentColor" stroke-width="1.5"/></svg></span><div><strong>Update Stock</strong><small>Manage stock levels</small></div><b>›</b></a>
    <a class="quick-card q-blue" href="customers.php"><span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M5.5 19c.7-3.1 2.8-5 6.5-5s5.8 1.9 6.5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><div><strong>View Customers</strong><small>Manage your customers</small></div><b>›</b></a>
  </div>
</section>

<section class="mini-info-grid">
  <div class="info-box"><span>✓</span><div><strong>Payment Methods</strong><small>Cash • Mobile Money • Bank</small></div></div>
  <div class="info-box"><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5 12 4l8 3.5-8 3-8-3Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M4 7.5V16l8 4 8-4V7.5M12 10.5V20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m7 9 5 2 5-2" fill="none" stroke="currentColor" stroke-width="1.5"/></svg></span><div><strong>Stock Management</strong><small>Keep your egg inventory organized</small></div></div>
  <div class="info-box"><span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M5.5 19c.7-3.1 2.8-5 6.5-5s5.8 1.9 6.5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><div><strong>Customer Management</strong><small>Keep customer records up to date</small></div></div>
</section>
<?php require "partials/footer.php"; ?>