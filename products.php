<?php
require "auth.php"; $pageTitle="Products"; $active="products";
$message='';
if(isset($_POST['save'])){
    $id=(int)($_POST['id']??0); $category=$_POST['category']??'Eggs'; $name=trim($_POST['name']??'');
    $price=(float)($_POST['selling_price']??0); $stock=(float)($_POST['stock_quantity']??0);
    $package = ($category==='Eggs') ? null : (float)($_POST['package_size_kg']??0);
    $unit = ($category==='Eggs') ? 'Tray' : 'Bag';
    if(!in_array($category,['Eggs','Rice','Flour'],true) || $name==='' || $price<0 || $stock<0 || ($category!=='Eggs' && ($package<1 || $package>20))){
        $message='Please enter valid product details. Rice and Flour package size must be between 1 and 20 Kg.';
    } else {
        if($id){$st=$conn->prepare("UPDATE products SET category=?,name=?,unit=?,package_size_kg=?,selling_price=?,stock_quantity=? WHERE id=?");$st->bind_param("sssdddi",$category,$name,$unit,$package,$price,$stock,$id);}
        else{$st=$conn->prepare("INSERT INTO products(category,name,unit,package_size_kg,selling_price,stock_quantity) VALUES(?,?,?,?,?,?)");$st->bind_param("sssddd",$category,$name,$unit,$package,$price,$stock);}
        $st->execute(); header("Location: products.php"); exit;
    }
}
if(isset($_GET['delete'])){$id=(int)$_GET['delete'];$conn->query("DELETE FROM products WHERE id=$id");header("Location: products.php");exit;}
$edit=null;if(isset($_GET['edit'])){$id=(int)$_GET['edit'];$edit=$conn->query("SELECT * FROM products WHERE id=$id")->fetch_assoc();}
$perPage=10;
$page=max(1,(int)($_GET['page']??1));
$totalProducts=(int)$conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];
$totalPages=max(1,(int)ceil($totalProducts/$perPage));
if($page>$totalPages)$page=$totalPages;
$offset=($page-1)*$perPage;
require "partials/header.php";
?>
<?php if($message): ?><div class="alert danger"><?=e($message)?></div><?php endif; ?>
<div class="panel">
<h3><?= $edit?'Edit Product':'Add Product' ?></h3>
<form method="post"><input type="hidden" name="id" value="<?=e($edit['id']??0)?>">
<div class="form-grid">
<?php $editCat=$edit['category']??'Eggs'; ?>
<div class="field"><label>Product Type</label><select name="category" id="category" required><option value="Eggs" <?= $editCat==='Eggs'?'selected':'' ?>>Eggs</option><option value="Rice" <?= $editCat==='Rice'?'selected':'' ?>>Rice</option><option value="Flour" <?= $editCat==='Flour'?'selected':'' ?>>Flour</option></select></div>
<div class="field"><label>Product Name</label><select name="name" id="product_name" required><option value="Eggs" <?= (($edit['name']??'Eggs')==='Eggs')?'selected':'' ?>>Eggs</option><option value="Rice" <?= (($edit['name']??'')==='Rice')?'selected':'' ?>>Rice</option><option value="Flour" <?= (($edit['name']??'')==='Flour')?'selected':'' ?>>Flour</option></select></div>
<div class="field package-field"><label>Package Size (Kg)</label><input type="number" step="1" min="1" max="20" name="package_size_kg" id="package_size_kg" value="<?=e($edit['package_size_kg']??'')?>"><small class="field-help">For Rice/Flour: choose 1 Kg up to 20 Kg.</small></div>
<div class="field"><label>Unit</label><input id="unit_display" value="<?=($edit['unit']??'Tray')?>" readonly><small class="field-help" id="unit_help">Egg stock is counted by tray. 1 tray = 30 eggs.</small></div>
<div class="field"><label>Selling Price (TZS)</label><input type="number" step="0.01" min="0" name="selling_price" value="<?=e($edit['selling_price']??0)?>" required><small class="field-help" id="price_help">For Rice/Flour this is the price per bag/package.</small></div>
<div class="field"><label id="stock_label">Stock Quantity (Trays)</label><input type="number" step="1" min="0" name="stock_quantity" value="<?=e($edit['stock_quantity']??0)?>" required><small class="field-help" id="stock_help">Enter whole trays. Example: 1 = 1 tray = 30 eggs.</small></div>
</div>
<div class="form-actions"><button class="btn primary" name="save">Save Product</button><?php if($edit): ?><a class="btn secondary" href="products.php">Cancel</a><?php endif;?></div>
</form></div>
<div class="panel" style="margin-top:20px"><div class="toolbar"><h3>Products</h3></div><div class="table-wrap"><table class="table"><tr><th>Product</th><th>Type</th><th>Package</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
<?php $rows=$conn->query("SELECT * FROM products ORDER BY id DESC LIMIT $perPage OFFSET $offset");while($r=$rows->fetch_assoc()):
  $isEgg=$r['category']==='Eggs'; $package=$isEgg?'Tray':number_format((float)$r['package_size_kg'],0).' Kg';
  $stockText=number_format((float)$r['stock_quantity'],0).' '.($isEgg?'tray':'bag').((float)$r['stock_quantity']===1?'':'s');
  ?><tr><td><?=e($r['name'])?></td><td><?=e($r['category'])?></td><td><?=e($package)?></td><td>TZS <?=money($r['selling_price'])?></td><td><?=e($stockText)?></td><td class="actions"><a class="btn btn-sm secondary" href="?edit=<?=$r['id']?>&page=<?=$page?>">Edit</a><a class="btn btn-sm danger-btn" data-confirm="Delete this product?" href="?delete=<?=$r['id']?>&page=<?=$page?>">Delete</a></td></tr><?php endwhile;?>
</table></div>
<?php if($totalProducts>$perPage): ?>
<div class="products-pagination" aria-label="Product pages">
  <?php if($page>1): ?><a class="page-arrow" href="products.php?page=<?=$page-1?>" aria-label="Previous page">‹</a><?php endif; ?>
  <?php $start=max(1,$page-2); $end=min($totalPages,$page+2); if($start>1): ?><a href="products.php?page=1">1</a><?php if($start>2): ?><span class="page-dots">…</span><?php endif; ?><?php endif; for($i=$start;$i<=$end;$i++): ?><a class="<?= $i===$page?'active':'' ?>" href="products.php?page=<?=$i?>"><?=$i?></a><?php endfor; if($end<$totalPages): ?><?php if($end<$totalPages-1): ?><span class="page-dots">…</span><?php endif; ?><a href="products.php?page=<?=$totalPages?>"><?=$totalPages?></a><?php endif; ?>
  <?php if($page<$totalPages): ?><a class="page-arrow" href="products.php?page=<?=$page+1?>" aria-label="Next page">›</a><?php endif; ?>
</div><div class="products-page-info">Showing <?=($offset+1)?>–<?=min($offset+$perPage,$totalProducts)?> of <?=$totalProducts?> products</div>
<?php endif; ?></div></div>
<style>.field-help{display:block;margin-top:5px;font-size:12px;color:#60736d}.field input[readonly]{background:#f7faf9;color:#1d2b27}.package-field.hidden{display:none}</style>
<script>
const cat=document.getElementById('category'), productName=document.getElementById('product_name'), pkg=document.getElementById('package_size_kg'), unit=document.getElementById('unit_display'), stockLabel=document.getElementById('stock_label'), stockHelp=document.getElementById('stock_help'), unitHelp=document.getElementById('unit_help');
function syncProductFields(){
  const type=cat.value;
  const egg=type==='Eggs';
  const current=productName.value;
  const names={Eggs:[['Eggs','Eggs']],Rice:[['Rice','Rice']],Flour:[['Flour','Flour']]};
  productName.innerHTML=(names[type]||[]).map(([value,label])=>`<option value="${value}">${label}</option>`).join('');
  productName.value=(names[type]||[]).some(([value])=>value===current)?current:type;
  pkg.disabled=egg; pkg.required=!egg; pkg.closest('.package-field').classList.toggle('hidden',egg);
  unit.value=egg?'Tray':'Bag';
  stockLabel.textContent=egg?'Stock Quantity (Trays)':'Stock Quantity (Bags)';
  stockHelp.textContent=egg?'Enter whole trays. Example: 1 = 1 tray = 30 eggs.':'Enter whole bags/packages. Each bag uses the selected package size (1–20 Kg).';
  unitHelp.textContent=egg?'Egg stock is counted by tray. 1 tray = 30 eggs.':'Rice/Flour stock is counted by bags/packages.';
}
cat.addEventListener('change',syncProductFields); syncProductFields();
</script>
<?php require "partials/footer.php"; ?>
