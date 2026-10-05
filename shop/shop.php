<?php
require_once __DIR__ . '/auth.php'; $pageTitle='Shop'; $active='shop';
$products=[]; $rs=$conn->query("SELECT * FROM products WHERE stock_quantity>0 ORDER BY category,name,package_size_kg"); while($r=$rs->fetch_assoc()) $products[]=$r;
$message=$_SESSION['portal_message']??''; unset($_SESSION['portal_message']);
?>
<?php require __DIR__.'/partials/header.php'; ?>
<section class="shop-hero"><div><div class="eyebrow">MSINDA CUSTOMER PORTAL</div><h1>Fresh food, ordered your way.</h1><p>Order eggs, rice and flour online. We receive your order on the admin side and arrange delivery to your address.</p></div><a class="portal-btn hero-cart" href="cart.php">View Cart (<?=portal_cart_count()?>)</a></section>
<?php if($message): ?><div class="portal-alert success"><?=pe($message)?></div><?php endif; ?>
<section class="section-head"><div><h2>Available Products</h2><p>Select the quantity you need and add it to your cart.</p></div></section>
<?php if(!$products): ?><div class="empty-card"><h3>No products available right now</h3><p>Please check again later.</p></div><?php else: ?><div class="product-grid">
<?php foreach($products as $p): $egg=$p['category']==='Eggs'; $package=$egg?'Tray':number_format((float)$p['package_size_kg'],0).' Kg Bag'; ?>
<article class="product-card"><div class="product-icon <?=strtolower($p['category'])?>"><?= $egg?'🥚':($p['category']==='Rice'?'🍚':'🌾') ?></div><div class="product-meta"><span><?=pe($p['category'])?></span><h3><?=pe($p['name'])?></h3><p><?=pe($package)?> • Stock <?=number_format((float)$p['stock_quantity'],0)?></p></div><strong class="product-price">TZS <?=pmoney($p['selling_price'])?></strong>
<form method="post" action="cart.php" class="add-form"><input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?=$p['id']?>"><input type="number" name="quantity" value="1" min="1" max="<?=max(1,(int)$p['stock_quantity'])?>"><button class="portal-btn small" type="submit">Add to Cart</button></form></article>
<?php endforeach; ?></div><?php endif; ?>
<?php require __DIR__.'/partials/footer.php'; ?>
