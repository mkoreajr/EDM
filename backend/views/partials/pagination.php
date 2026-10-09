<?php
/**
 * Windowed pagination: ‹ 1 … 4 5 [6] 7 8 … 20 ›
 *
 * @var array{page:int,perPage:int,total:int,pages:int,offset:int} $pager
 * @var string $base   path, e.g. "/sales"
 * @var array  $query  extra query parameters to keep (filters)
 * @var string $noun   e.g. "sales"
 */

$query = $query ?? [];
if ($pager['total'] <= $pager['perPage']) {
    return;
}
$page = $pager['page'];
$pages = $pager['pages'];
$link = static fn(int $p): string => url($base, $query + ['page' => $p]);
$start = max(1, $page - 2);
$end = min($pages, $page + 2);
?>
<nav class="pagination" aria-label="<?= e(ucfirst($noun)) ?> pages">
  <?php if ($page > 1): ?><a class="page-arrow" href="<?= e($link($page - 1)) ?>" aria-label="Previous page">‹</a><?php endif; ?>
  <?php if ($start > 1): ?>
    <a href="<?= e($link(1)) ?>">1</a>
    <?php if ($start > 2): ?><span class="page-dots">…</span><?php endif; ?>
  <?php endif; ?>
  <?php for ($i = $start; $i <= $end; $i++): ?>
    <a class="<?= $i === $page ? 'active' : '' ?>" href="<?= e($link($i)) ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
  <?php endfor; ?>
  <?php if ($end < $pages): ?>
    <?php if ($end < $pages - 1): ?><span class="page-dots">…</span><?php endif; ?>
    <a href="<?= e($link($pages)) ?>"><?= $pages ?></a>
  <?php endif; ?>
  <?php if ($page < $pages): ?><a class="page-arrow" href="<?= e($link($page + 1)) ?>" aria-label="Next page">›</a><?php endif; ?>
</nav>
<div class="page-info">Showing <?= $pager['offset'] + 1 ?>–<?= min($pager['offset'] + $pager['perPage'], $pager['total']) ?> of <?= number_format($pager['total']) ?> <?= e($noun) ?></div>
