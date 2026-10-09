<?php
/**
 * Green left-hand panel shared by the customer sign-in and registration pages.
 * @var string $eyebrow
 * @var string $heading
 * @var string $intro
 * @var list<array{0:string,1:string}> $benefits [title, text]
 */
?>
<section class="customer-auth-visual" aria-hidden="true">
  <div class="customer-auth-brand">
    <img src="<?= asset('img/brand/msinda-emblem.png') ?>" alt="" width="50" height="50">
    <span>MSINDA Food Shop</span>
  </div>
  <div class="customer-auth-copy">
    <div class="eyebrow light"><?= e($eyebrow) ?></div>
    <h1><?= e($heading) ?></h1>
    <p><?= e($intro) ?></p>
    <div class="customer-auth-benefits">
      <?php foreach ($benefits as [$title, $text]): ?>
        <div><span>✓</span><strong><?= e($title) ?></strong><small><?= e($text) ?></small></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="customer-auth-visual-footer">Secure • Reliable • Built for convenient food ordering</div>
</section>
