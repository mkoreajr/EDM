<?php
require "auth.php"; require_admin();
$error='';$success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim($_POST['name']??'');
  if($name==='')$error='Name is required.';
  elseif(mb_strlen($name)>100)$error='Name is too long.';
  else{$st=$conn->prepare("UPDATE users SET name=? WHERE id=?");$st->bind_param("si",$name,$_SESSION['user_id']);$st->execute();$_SESSION['name']=$name;$success='Administrator name updated successfully. The Admin role remains unchanged.';}
}
$pageTitle='Change Name';$active='settings';require 'partials/header.php';
?>
<div class="page-intro"><div><div class="welcome-kicker">ACCOUNT</div><h1>Change Name</h1><p class="muted">Rename the administrator without changing the Admin role.</p></div></div>
<div class="panel password-panel"><?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?><?php if($success):?><div class="alert success"><?=e($success)?></div><?php endif;?>
<form method="post" class="password-form"><div class="field"><label>Administrator Name</label><input name="name" value="<?=e($_SESSION['name']??'Administrator')?>" maxlength="100" required></div><div class="form-actions"><button class="btn primary">Save Name</button><a class="btn secondary" href="settings.php">Back</a></div></form></div>
<?php require 'partials/footer.php'; ?>