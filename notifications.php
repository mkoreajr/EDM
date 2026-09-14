<?php
require "auth.php";
if(isset($_GET['id'])){
  $id=(int)$_GET['id'];
  $stmt=$conn->prepare("UPDATE notifications SET read_at=CURRENT_TIMESTAMP WHERE id=? AND user_id=?");
  $stmt->bind_param("ii",$id,$_SESSION['user_id']); $stmt->execute();
  header("Location: notifications.php"); exit;
}
$pageTitle="Notifications"; $active=""; require "partials/header.php";
$res=$conn->query("SELECT id,title,message,read_at,created_at FROM notifications WHERE user_id=".(int)$_SESSION['user_id']." ORDER BY created_at DESC");
?>
<div class="page-intro notification-page-heading"><div style="width:100%;text-align:center"><div class="welcome-kicker">ACCOUNT</div><h1>Notifications</h1><p class="muted">Read your latest system notifications.</p></div></div>
<div class="notification-page-list">
<?php while($n=$res->fetch_assoc()): ?>
<a class="notification-page-item <?=empty($n['read_at'])?'unread':''?>" href="notifications.php?id=<?=(int)$n['id']?>">
  <span class="notification-dot"></span><div><strong><?=e($n['title'])?></strong><p><?=e($n['message'])?></p><small><?=e($n['created_at'])?></small></div>
  <?php if(empty($n['read_at'])):?><b class="unread-label">Unread</b><?php endif;?>
</a>
<?php endwhile; ?>
</div>
<?php require "partials/footer.php"; ?>
