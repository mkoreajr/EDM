<?php
/**
 * @var list<array<string,mixed>> $items
 * @var float $total
 * @var list<string> $adjusted names of products whose quantity was lowered to the stock available
 * @var string|null $error
 */

use App\Services\Cart;

$icons = ['Eggs' => '🥚', 'Rice' => '🍚', 'Flour' => '🌾'];
?>
<section class="section-head section-head-card">
  <div><div class="eyebrow">YOUR ORDER</div><h1>Shopping Cart</h1><p>Review your products before sending the order to MSINDA Food Shop.</p></div>
</section>

<?php if ($error): ?><div class="portal-alert error"><?= e($error) ?></div>
<?php elseif ($adjusted): ?><div class="portal-alert error" role="alert"><?= e(Cart::adjustmentMessage($adjusted)) ?></div><?php endif; ?>

<?php if (!$items): ?>
  <div class="empty-card"><h3>Your cart is empty</h3><p>Choose products from the shop to get started.</p><a class="portal-btn" href="/shop">Browse Products</a></div>
<?php else: ?>
  <div class="cart-layout">
    <div class="cart-list">
      <?php foreach ($items as $p): ?>
        <div class="cart-row">
          <div class="product-icon small <?= e(strtolower($p['category'])) ?>"><?= $icons[$p['category']] ?? '' ?></div>
          <div class="cart-info"><strong><?= e($p['name']) ?></strong><small><?= e(package_label($p)) ?> • TZS <?= money($p['selling_price']) ?></small></div>
          <form method="post" action="/shop/cart" class="qty-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
            <input type="number" name="quantity" value="<?= (int)$p['cart_qty'] ?>" min="1" max="<?= max(1, (int)$p['stock_quantity']) ?>" aria-label="Quantity">
            <button type="submit">Update</button>
          </form>
          <strong class="line-total">TZS <?= money($p['line_total']) ?></strong>
          <form method="post" action="/shop/cart">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
            <button class="remove-link" type="submit">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
    <aside class="cart-summary">
      <h3>Order Summary</h3>
      <div><span>Items</span><strong><?= Cart::count() ?></strong></div>
      <div class="summary-total"><span>Total</span><strong>TZS <?= money($total) ?></strong></div>
      <a class="portal-btn full" href="/shop/checkout">Proceed to Checkout</a>
      <a class="back-link" href="/shop">← Continue shopping</a>
    </aside>
  </div>
<?php endif; ?>
