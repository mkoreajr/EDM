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
  <div>
    <div class="welcome-kicker">GOOD DAY,</div>
    <h1><?=e($_SESSION['name']??'Administrator')?>!</h1>
    <p>Quality eggs. Healthier families. A brighter tomorrow.</p>
    <a class="hero-btn" href="sales.php"><span>＋</span> New Sale (POS) <b>›</b></a>
  </div>
  <div class="hero-art" aria-hidden="true">
    <div class="code-eggs"><i class="code-egg"></i><i class="code-egg"></i><i class="code-egg"></i></div>
    <div class="hero-leaf leaf-a"></div><div class="hero-leaf leaf-b"></div>
    <div class="hero-script">Fresh Eggs<br><em>Brighter Lives</em><i></i></div>
  </div>
</section>

<section class="stat-grid">
  <a class="stat-card stat-green" href="products.php"><span class="stat-icon">🥚</span><div><small>Total Products</small><strong><?=number_format($products)?></strong><em>Egg products available</em></div><b class="arrow">›</b></a>
  <a class="stat-card stat-yellow" href="inventory.php"><span class="stat-icon">▣</span><div><small>Total Stock</small><strong><?=number_format($stock)?></strong><em>Eggs in stock</em></div><b class="arrow">›</b></a>
  <a class="stat-card stat-blue" href="customers.php"><span class="stat-icon">♟</span><div><small>Total Customers</small><strong><?=number_format($customers)?></strong><em>Registered customers</em></div><b class="arrow">›</b></a>
  <a class="stat-card stat-pink" href="sales.php"><span class="stat-icon">🛒</span><div><small>Today's Sales</small><strong>TZS <?=money($today)?></strong><em>From <?=number_format($todaySales)?> sales</em></div><b class="arrow">›</b></a>
</section>

<section class="quick-section">
  <div class="section-heading"><h2>Quick Actions</h2><p>Common tasks to run your egg business</p></div>
  <div class="quick-grid">
    <a class="quick-card q-green" href="sales.php"><span>🛒</span><div><strong>New Sale (POS)</strong><small>Record a new sale</small></div><b>›</b></a>
    <a class="quick-card q-soft" href="products.php"><span>🥚</span><div><strong>Manage Products</strong><small>Add, edit or view products</small></div><b>›</b></a>
    <a class="quick-card q-gold" href="inventory.php"><span>▣</span><div><strong>Update Stock</strong><small>Manage stock levels</small></div><b>›</b></a>
    <a class="quick-card q-blue" href="customers.php"><span>♟</span><div><strong>View Customers</strong><small>Manage your customers</small></div><b>›</b></a>
  </div>
</section>

<section class="mini-info-grid">
  <div class="info-box"><span>✓</span><div><strong>Payment Methods</strong><small>Cash • Mobile Money • Bank</small></div></div>
  <div class="info-box"><span>▣</span><div><strong>Stock Management</strong><small>Keep your egg inventory organized</small></div></div>
  <div class="info-box"><span>♟</span><div><strong>Customer Management</strong><small>Keep customer records up to date</small></div></div>
</section>
<?php require "partials/footer.php"; ?>