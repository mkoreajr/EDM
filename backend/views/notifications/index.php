<?php
/**
 * Shared by the latest-notifications page and the full history page.
 *
 * @var string $heading
 * @var string $subtitle
 * @var list<array<string,mixed>> $items
 * @var int $total
 * @var bool $cleared
 * @var string $base
 * @var array|null $pager
 */

$isHistory = $base === '/notifications/history';
?>
<div class="page-intro notification-page-heading">
  <div class="centered-heading">
    <div class="welcome-kicker">ACCOUNT</div>
    <h1><?= e($heading) ?></h1>
    <p class="muted"><?= e($subtitle) ?></p>
  </div>
</div>

<?php if ($cleared): ?><div class="notification-cleared-message">All notifications have been cleared.</div><?php endif; ?>

<div class="notification-top-actions">
  <?php if ($total > 0): ?>
    <form method="post" action="/notifications/clear" class="clear-notification-form">
      <?= csrf_field() ?>
      <input type="hidden" name="back" value="<?= $isHistory ? 'history' : 'latest' ?>">
      <button class="clear-notifications-btn" type="submit">Clear Notifications</button>
    </form>
  <?php endif; ?>
  <?php if (!$isHistory && $total > 5): ?>
    <a class="view-more-notifications" href="/notifications/history">View More Notifications <span>→</span></a>
  <?php endif; ?>
</div>

<div class="notification-page-list <?= $isHistory ? 'notification-history-list' : '' ?>">
  <?php if (!$items): ?>
    <div class="notification-empty-card">No notifications.</div>
  <?php else: foreach ($items as $n): ?>
    <a class="notification-page-item <?= empty($n['read_at']) ? 'unread' : '' ?>" href="<?= url($base, ['id' => (int)$n['id']]) ?>">
      <span class="notification-dot"></span>
      <div><strong><?= e($n['title']) ?></strong><p><?= e($n['message']) ?></p><small><?= e(date('d M Y, H:i', strtotime((string)$n['created_at']))) ?></small></div>
      <?php if (empty($n['read_at'])): ?><b class="unread-label">Unread</b><?php endif; ?>
    </a>
  <?php endforeach; endif; ?>
</div>
<?php if ($pager) { partial('partials/pagination', ['pager' => $pager, 'base' => $base, 'noun' => 'notifications']); } ?>
