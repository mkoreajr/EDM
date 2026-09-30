<?php
require "auth.php"; $pageTitle="Inventory"; $active="inventory"; require "partials/header.php";
$rows=$conn->query("SELECT p.*,COALESCE((SELECT SUM(quantity) FROM stock_movements m WHERE m.product_id=p.id AND m.movement_type='Purchase'),0) purchases,COALESCE((SELECT SUM(quantity) FROM stock_movements m WHERE m.product_id=p.id AND m.movement_type='Sale'),0) sales FROM products p ORDER BY p.category,p.name,p.package_size_kg");
?>
<div class="panel"><h3>Current Inventory</h3><div class="table-wrap"><table class="table"><tr><th>Product</th><th>Type</th><th>Package</th><th>Purchased</th><th>Sold</th><th>Current Stock</th></tr>
<?php while($r=$rows->fetch_assoc()): $egg=$r['category']==='Eggs'; $unit=$egg?'tray':'bag'; $package=$egg?'Tray':number_format((float)$r['package_size_kg'],0).' Kg'; ?><tr><td><?=e($r['name'])?></td><td><?=e($r['category'])?></td><td><?=e($package)?></td><td><?=number_format((float)$r['purchases'],0)?> <?=$unit?><?=((float)$r['purchases']===1?'':'s')?></td><td><?=number_format((float)$r['sales'],0)?> <?=$unit?><?=((float)$r['sales']===1?'':'s')?></td><td class="<?=($r['stock_quantity']<=5?'low':'')?>"><?=number_format((float)$r['stock_quantity'],0)?> <?=$unit?><?=((float)$r['stock_quantity']===1?'':'s')?></td></tr><?php endwhile; ?></table></div></div>
<?php require "partials/footer.php"; ?>
