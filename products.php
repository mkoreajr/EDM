<?php
require "auth.php"; $pageTitle="Products"; $active="products";
if(isset($_POST['save'])){
    $id=(int)($_POST['id']??0); $name=trim($_POST['name']); $unit=$_POST['unit']; $price=(float)$_POST['selling_price']; $stock=(float)$_POST['stock_quantity'];
    if($id){$st=$conn->prepare("UPDATE products SET name=?,unit=?,selling_price=?,stock_quantity=? WHERE id=?");$st->bind_param("ssddi",$name,$unit,$price,$stock,$id);}
    else{$st=$conn->prepare("INSERT INTO products(name,unit,selling_price,stock_quantity) VALUES(?,?,?,?)");$st->bind_param("ssdd",$name,$unit,$price,$stock);}
    $st->execute(); header("Location: products.php"); exit;
}
if(isset($_GET['delete'])){$id=(int)$_GET['delete'];$conn->query("DELETE FROM products WHERE id=$id");header("Location: products.php");exit;}
$edit=null;if(isset($_GET['edit'])){$id=(int)$_GET['edit'];$edit=$conn->query("SELECT * FROM products WHERE id=$id")->fetch_assoc();}
$perPage=15;
$page=max(1,(int)($_GET['page']??1));
$totalProducts=(int)$conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];
$totalPages=max(1,(int)ceil($totalProducts/$perPage));
if($page>$totalPages)$page=$totalPages;
$offset=($page-1)*$perPage;
require "partials/header.php";
?>
<div class="panel">
<h3><?= $edit?'Edit Product':'Add Product' ?></h3>
<form method="post"><input type="hidden" name="id" value="<?=e($edit['id']??0)?>">
<div class="form-grid"><div class="field"><label>Product Name</label><input name="name" value="<?=e($edit['name']??'Eggs')?>" required></div>
<div class="field"><label>Unit</label><select name="unit"><?php foreach(['Tray','Half Tray','Piece'] as $u): ?><option <?=$u===($edit['unit']??'Tray')?'selected':''?>><?=$u?></option><?php endforeach;?></select></div>
<div class="field"><label>Selling Price (TZS)</label><input type="number" step="0.01" min="0" name="selling_price" value="<?=e($edit['selling_price']??0)?>" required></div>
<div class="field"><label>Stock Quantity</label><input type="number" step="0.01" min="0" name="stock_quantity" value="<?=e($edit['stock_quantity']??0)?>" required></div></div>
<div class="form-actions"><button class="btn primary" name="save">Save Product</button><?php if($edit): ?><a class="btn secondary" href="products.php">Cancel</a><?php endif;?></div>
</form></div>
<div class="panel" style="margin-top:20px"><div class="toolbar"><h3>Products</h3></div><div class="table-wrap"><table class="table"><tr><th>Product</th><th>Unit</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
<?php $rows=$conn->query("SELECT * FROM products ORDER BY id DESC LIMIT $perPage OFFSET $offset");while($r=$rows->fetch_assoc()): ?><tr><td><?=e($r['name'])?></td><td><?=e($r['unit'])?></td><td>TZS <?=money($r['selling_price'])?></td><td><?=money($r['stock_quantity'])?></td><td class="actions"><a class="btn btn-sm secondary" href="?edit=<?=$r['id']?>&page=<?=$page?>">Edit</a><a class="btn btn-sm danger-btn" data-confirm="Delete this product?" href="?delete=<?=$r['id']?>&page=<?=$page?>">Delete</a></td></tr><?php endwhile;?>
</table></div>
<?php if($totalProducts>$perPage): ?>
<div class="products-pagination" aria-label="Product pages">
  <?php if($page>1): ?><a class="page-arrow" href="products.php?page=<?=$page-1?>" aria-label="Previous page">‹</a><?php endif; ?>
  <?php
    $start=max(1,$page-2); $end=min($totalPages,$page+2);
    if($start>1): ?><a href="products.php?page=1">1</a><?php if($start>2): ?><span class="page-dots">…</span><?php endif; ?><?php endif;
    for($i=$start;$i<=$end;$i++): ?><a class="<?= $i===$page?'active':'' ?>" href="products.php?page=<?=$i?>"><?=$i?></a><?php endfor;
    if($end<$totalPages): ?><?php if($end<$totalPages-1): ?><span class="page-dots">…</span><?php endif; ?><a href="products.php?page=<?=$totalPages?>"><?=$totalPages?></a><?php endif; ?>
  <?php if($page<$totalPages): ?><a class="page-arrow" href="products.php?page=<?=$page+1?>" aria-label="Next page">›</a><?php endif; ?>
</div>
<div class="products-page-info">Showing <?=($offset+1)?>–<?=min($offset+$perPage,$totalProducts)?> of <?=$totalProducts?> products</div>
<?php endif; ?>
</div></div>
<?php require "partials/footer.php"; ?>