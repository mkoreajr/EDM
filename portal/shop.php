<?php
require_once __DIR__ . '/auth.php'; $pageTitle='Shop'; $active='shop';
$products=[]; $rs=$conn->query("SELECT * FROM products WHERE stock_quantity>0 ORDER BY category,name,package_size_kg"); while($r=$rs->fetch_assoc()) $products[]=$r;
$message=$_SESSION['portal_message']??''; unset($_SESSION['portal_message']);
$customer=portal_current_customer();
$categories=[]; foreach($products as $p){$categories[$p['category']]=true;} ksort($categories);
?>
<?php require __DIR__.'/partials/header.php'; ?>
<section class="shop-hero">
  <div class="hero-copy">
    <div class="eyebrow">MSINDA CUSTOMER SHOP</div>
    <h1>Good food, simple ordering, delivered to you.</h1>
    <p>Browse our everyday food essentials, add what you need to your cart, and track your order from confirmation to delivery.</p>
    <div class="hero-actions">
      <a class="portal-btn" href="#products">Browse products ↓</a>
      <a class="portal-btn secondary" href="cart.php">View cart · <?=portal_cart_count()?></a>
    </div>
  </div>
  <div class="hero-panel">
    <div class="hero-panel-title">Why order online?</div>
    <div class="hero-stat"><div class="hero-stat-icon">✓</div><div><strong>Easy checkout</strong><span>Save your delivery details</span></div></div>
    <div class="hero-stat"><div class="hero-stat-icon">↗</div><div><strong>Live order progress</strong><span>Follow every order status</span></div></div>
    <div class="hero-stat"><div class="hero-stat-icon">⌁</div><div><strong>Convenient delivery</strong><span>Tell us where to deliver</span></div></div>
  </div>
</section>
<?php if($message): ?><div class="portal-alert success"><?=pe($message)?></div><?php endif; ?>
<section class="section-head" id="products"><div><div class="eyebrow">SHOP WITH CONFIDENCE</div><h2>Available products</h2><p>Choose a product, set the quantity, and add it to your cart.</p></div></section>
<div class="shop-toolbar">
  <label class="search-box" aria-label="Search products">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
    <input id="productSearch" type="search" placeholder="Search eggs, rice, flour..." autocomplete="off">
  </label>
  <div class="category-pills" id="categoryPills"><button class="category-pill active" type="button" data-category="all">All</button><?php foreach(array_keys($categories) as $category): ?><button class="category-pill" type="button" data-category="<?=pe(strtolower($category))?>"><?=pe($category)?></button><?php endforeach; ?></div>
</div>
<?php if(!$products): ?><div class="empty-card"><div class="empty-illustration">🛒</div><h3>No products available right now</h3><p>Please check again later. We will keep the shop updated.</p></div><?php else: ?><div class="product-grid" id="productGrid">
<?php foreach($products as $p): $egg=$p['category']==='Eggs'; $package=$egg?'Tray':number_format((float)$p['package_size_kg'],0).' Kg Bag'; ?>
<article class="product-card" data-category="<?=pe(strtolower($p['category']))?>" data-name="<?=pe(strtolower($p['name'].' '.$p['category'].' '.$package))?>">
  <div class="product-top"><div class="product-icon <?=strtolower($p['category'])?>"><?= $egg?'🥚':($p['category']==='Rice'?'🍚':'🌾') ?></div><span class="stock-badge">In stock</span></div>
  <div class="product-meta"><span><?=pe($p['category'])?></span><h3><?=pe($p['name'])?></h3><p><?=pe($package)?> · <?=number_format((float)$p['stock_quantity'],0)?> available</p></div>
  <strong class="product-price">TZS <?=pmoney($p['selling_price'])?></strong>
  <form method="post" action="cart.php" class="add-form"><input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?=$p['id']?>"><input aria-label="Quantity" type="number" name="quantity" value="1" min="1" max="<?=max(1,(int)$p['stock_quantity'])?>"><button class="portal-btn small" type="submit">Add to cart</button></form>
</article>
<?php endforeach; ?></div><div id="noResults" class="empty-card" style="display:none"><div class="empty-illustration">⌕</div><h3>No matching products</h3><p>Try a different product name or category.</p><button class="portal-btn secondary" type="button" onclick="document.getElementById('productSearch').value='';document.querySelector('[data-category=all]').click();filterProducts();">Show all products</button></div><?php endif; ?>
<script>
const search=document.getElementById('productSearch'), cards=[...document.querySelectorAll('.product-card')], pills=[...document.querySelectorAll('.category-pill')], noResults=document.getElementById('noResults'); let selected='all';
function filterProducts(){const q=(search?.value||'').trim().toLowerCase();let shown=0;cards.forEach(c=>{const okCat=selected==='all'||c.dataset.category===selected;const okText=!q||c.dataset.name.includes(q);const show=okCat&&okText;c.style.display=show?'flex':'none';if(show)shown++;});if(noResults)noResults.style.display=shown?'none':'block';}
pills.forEach(p=>p.addEventListener('click',()=>{pills.forEach(x=>x.classList.remove('active'));p.classList.add('active');selected=p.dataset.category;filterProducts();})); search?.addEventListener('input',filterProducts);
</script>
<?php require __DIR__.'/partials/footer.php'; ?>
