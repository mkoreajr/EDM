<?php
require "auth.php";

if(isset($_GET['id'])){
  $id=(int)$_GET['id'];
  $stmt=$conn->prepare("UPDATE notifications SET read_at=CURRENT_TIMESTAMP WHERE id=? AND user_id=?");
  $stmt->bind_param("ii",$id,$_SESSION['user_id']); $stmt->execute();
  header("Location: notifications.php"); exit;
}

if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='clear_notifications'){
  $stmt=$conn->prepare("DELETE FROM notifications WHERE user_id=?");
  $stmt->bind_param("i",$_SESSION['user_id']); $stmt->execute();
  header("Location: notifications.php?cleared=1"); exit;
}

$pageTitle="Notifications"; $active=""; require "partials/header.php";
$uid=(int)$_SESSION['user_id'];
$totalRes=$conn->query("SELECT COUNT(*) AS total FROM notifications WHERE user_id={$uid}");
$totalRow=$totalRes ? $totalRes->fetch_assoc() : ['total'=>0];
$totalNotifications=(int)($totalRow['total']??0);

$res=$conn->query("SELECT id,title,message,read_at,created_at FROM notifications WHERE user_id={$uid} ORDER BY created_at DESC LIMIT 5");
$items=[];
if($res){ while($row=$res->fetch_assoc()){ if($row!==null) $items[]=$row; } }
?>
<div class="page-intro notification-page-heading">
  <div style="width:100%;text-align:center">
    <div class="welcome-kicker">ACCOUNT</div>
    <h1>Notifications</h1>
    <p class="muted">Read your latest system notifications.</p>
  </div>
</div>

<?php if(isset($_GET['cleared'])): ?>
<div class="notification-cleared-message">All notifications have been cleared.</div>
<?php endif; ?>

<div class="notification-top-actions">
<?php if($totalNotifications>0): ?>
  <form method="post" class="clear-notification-form">
    <input type="hidden" name="action" value="clear_notifications">
    <button class="clear-notifications-btn" type="submit">Clear Notifications</button>
  </form>
<?php endif; ?>
<?php if($totalNotifications>5): ?>
  <a class="view-more-notifications" href="notifications_history.php">View More Notifications <span>→</span></a>
<?php endif; ?>
</div>

<div class="notification-page-list">
<?php if(!$items): ?>
  <div class="notification-empty-card">No notifications.</div>
<?php else: ?>
<?php foreach($items as $n): ?>
<a class="notification-page-item <?=empty($n['read_at'])?'unread':''?>" href="notifications.php?id=<?=(int)$n['id']?>">
  <span class="notification-dot"></span>
  <div><strong><?=e($n['title'])?></strong><p><?=e($n['message'])?></p><small><?=e($n['created_at'])?></small></div>
  <?php if(empty($n['read_at'])):?><b class="unread-label">Unread</b><?php endif;?>
</a>
<?php endforeach; ?>
<?php endif; ?>


</div>

<?php require "partials/footer.php"; ?>
