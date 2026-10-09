<?php
/**
 * @var list<array<string,mixed>> $products
 * @var list<string> $categories
 * @var string|null $message
 */

use App\Services\Cart;
?>
<section class="shop-hero">
  <div class="hero-copy">
    <div class="eyebrow">MSINDA CUSTOMER SHOP</div>
    <h1>Good food, simple ordering, delivered to you.</h1>
    <p>Browse our everyday food essentials, add what you need to your cart, and track your order from confirmation to delivery.</p>
    <div class="hero-actions">
      <a class="portal-btn" href="#products">Browse products ↓</a>
      <a class="portal-btn secondary" href="/shop/cart">View cart · <?= Cart::count() ?></a>
    </div>
  </div>
  <div class="hero-panel">
    <div class="hero-panel-title">Why order online?</div>
    <div class="hero-stat"><div class="hero-stat-icon">✓</div><div><strong>Easy checkout</strong><span>Save your delivery details</span></div></div>
    <div class="hero-stat"><div class="hero-stat-icon">↗</div><div><strong>Live order progress</strong><span>Follow every order status</span></div></div>
    <div class="hero-stat"><div class="hero-stat-icon">⌂</div><div><strong>Convenient delivery</strong><span>Tell us where to deliver</span></div></div>
  </div>
</section>

<?php if ($message): ?><div class="portal-alert success"><?= e($message) ?></div><?php endif; ?>

<section class="section-head section-head-card" id="products">
  <div><div class="eyebrow">SHOP WITH CONFIDENCE</div><h2>Available products</h2><p>Choose a product, set the quantity, and add it to your cart.</p></div>
</section>

<?php if ($products): ?>
<div class="shop-toolbar">
  <label class="search-box" aria-label="Search products">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
    <input id="productSearch" type="search" placeholder="Search eggs, rice, flour..." autocomplete="off">
  </label>
  <div class="category-pills">
    <button class="category-pill active" type="button" data-category="all">All</button>
    <?php foreach ($categories as $category): ?>
      <button class="category-pill" type="button" data-category="<?= e(strtolower($category)) ?>"><?= e($category) ?></button>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if (!$products): ?>
  <div class="empty-card"><div class="empty-illustration">🛒</div><h3>Bidhaa zitapatikana hivi karibuni</h3><p>Products will be available again soon. Please check back later.</p></div>
<?php else: ?>
  <div class="product-grid" id="productGrid">
    <?php foreach ($products as $p):
        $package = package_label($p);
    ?>
      <article class="product-card" data-category="<?= e(strtolower($p['category'])) ?>" data-name="<?= e(strtolower($p['name'] . ' ' . $p['category'] . ' ' . $package)) ?>">
        <div class="product-top">
          <div class="product-icon <?= e(strtolower($p['category'])) ?><?= $p['category'] === 'Eggs' ? ' plain-product-icon' : '' ?>"></div>
          <span class="stock-badge">In stock</span>
        </div>
        <div class="product-meta">
          <span><?= e($p['category']) ?></span>
          <h3><?= e($p['name']) ?></h3>
          <p><?= e($package) ?> · <?= qty($p['stock_quantity']) ?> available</p>
        </div>
        <strong class="product-price">TZS <?= money($p['selling_price']) ?></strong>
        <form method="post" action="/shop/cart" class="add-form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
          <input aria-label="Quantity" type="number" name="quantity" value="1" min="1" max="<?= (int)$p['stock_quantity'] ?>">
          <button class="portal-btn small" type="submit">Add to cart</button>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
  <div id="noResults" class="empty-card" hidden>
    <div class="empty-illustration">⌕</div>
    <h3>No matching products</h3>
    <p>Try a different product name or category.</p>
    <button class="portal-btn secondary" type="button" id="showAllProducts">Show all products</button>
  </div>
<?php endif; ?>
