<?php
require "auth.php"; $pageTitle="Inventory"; $active="inventory"; require "partials/header.php";
$rows=$conn->query("SELECT p.*,COALESCE((SELECT SUM(quantity) FROM stock_movements m WHERE m.product_id=p.id AND m.movement_type='Purchase'),0) purchases,COALESCE((SELECT SUM(quantity) FROM stock_movements m WHERE m.product_id=p.id AND m.movement_type='Sale'),0) sales FROM products p ORDER BY p.name");
?>
<div class="panel"><h3>Current Inventory</h3><table class="table"><tr><th>Product</th><th>Unit</th><th>Purchased</th><th>Sold</th><th>Current Stock</th></tr><?php while($r=$rows->fetch_assoc()):?><tr><td><?=e($r['name'])?></td><td><?=e($r['unit'])?></td><td><?=money($r['purchases'])?></td><td><?=money($r['sales'])?></td><td class="<?=($r['stock_quantity']<=5?'low':'')?>"><?=money($r['stock_quantity'])?></td></tr><?php endwhile;?></table></div>
<?php require "partials/footer.php"; ?>