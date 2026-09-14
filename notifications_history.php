<?php
require "auth.php";

if(isset($_GET['id'])){
  $id=(int)$_GET['id'];
  $stmt=$conn->prepare("UPDATE notifications SET read_at=CURRENT_TIMESTAMP WHERE id=? AND user_id=?");
  $stmt->bind_param("ii",$id,$_SESSION['user_id']); $stmt->execute();
  header("Location: notifications_history.php"); exit;
}

if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='clear_notifications'){
  $stmt=$conn->prepare("DELETE FROM notifications WHERE user_id=?");
  $stmt->bind_param("i",$_SESSION['user_id']); $stmt->execute();
  header("Location: notifications_history.php?cleared=1"); exit;
}

$pageTitle="Notification History"; $active=""; require "partials/header.php";
$uid=(int)$_SESSION['user_id'];
$res=$conn->query("SELECT id,title,message,read_at,created_at FROM notifications WHERE user_id={$uid} ORDER BY created_at DESC");
$items=[];
if($res){ while($row=$res->fetch_assoc()){ if($row!==null) $items[]=$row; } }
?>
<div class="page-intro notification-page-heading">
  <div style="width:100%;text-align:center">
    <div class="welcome-kicker">ACCOUNT</div>
    <h1>Notification History</h1>
    <p class="muted">View all your system notifications.</p>
  </div>
</div>

<?php if(isset($_GET['cleared'])): ?>
<div class="notification-cleared-message">All notifications have been cleared.</div>
<?php endif; ?>

<div class="notification-page-list notification-history-list">
<?php if(!$items): ?>
  <div class="notification-empty-card">No notifications.</div>
<?php else: ?>
<?php foreach($items as $n): ?>
<a class="notification-page-item <?=empty($n['read_at'])?'unread':''?>" href="notifications_history.php?id=<?=(int)$n['id']?>">
  <span class="notification-dot"></span>
  <div><strong><?=e($n['title'])?></strong><p><?=e($n['message'])?></p><small><?=e($n['created_at'])?></small></div>
  <?php if(empty($n['read_at'])):?><b class="unread-label">Unread</b><?php endif;?>
</a>
<?php endforeach; ?>
<?php endif; ?>

<?php if($items): ?>
<div class="notification-actions history-actions">
  <form method="post" onsubmit="return confirm('Clear all notifications, including unread notifications? This cannot be undone.');">
    <input type="hidden" name="action" value="clear_notifications">
    <button class="clear-notifications-btn" type="submit">Clear Notifications</button>
  </form>
</div>
<?php endif; ?>
</div>
<?php require "partials/footer.php"; ?>
