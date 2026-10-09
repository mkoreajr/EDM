<?php
/**
 * @var array<string,mixed> $customer
 * @var list<array<string,mixed>> $items
 * @var float $total
 * @var list<string> $methods
 * @var array<string,string> $old previously submitted values
 * @var string|null $error
 */

$address = $old['address'] ?? (string)$customer['address'];
$phone = $old['phone'] ?? (string)$customer['phone'];
$payment = $old['payment'] ?? $methods[0];
$notes = $old['notes'] ?? '';
?>
<section class="section-head section-head-card">
  <div><div class="eyebrow">CHECKOUT</div><h1>Delivery Details</h1><p>Your order will appear immediately in the MSINDA admin portal for processing.</p></div>
</section>

<?php if ($error): ?><div class="portal-alert error" role="alert"><?= e($error) ?></div><?php endif; ?>

<div class="checkout-layout">
  <form method="post" action="/shop/checkout" class="checkout-card">
    <?= csrf_field() ?>
    <label>Delivery address<input name="address" value="<?= e($address) ?>" maxlength="255" required></label>
    <label>Phone number<input name="phone" type="tel" value="<?= e($phone) ?>" maxlength="30" required></label>
    <label>Payment method
      <select name="payment_method">
        <?php foreach ($methods as $method): ?><option <?= $method === $payment ? 'selected' : '' ?>><?= e($method) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Order note (optional)<textarea name="notes" rows="4" maxlength="1000" placeholder="Any delivery instructions..."><?= e($notes) ?></textarea></label>
    <button class="portal-btn full" type="submit">Place Order — TZS <?= money($total) ?></button>
  </form>
  <aside class="cart-summary">
    <h3>Order Items</h3>
    <?php foreach ($items as $it): ?>
      <div class="checkout-item"><span><?= e($it['name']) ?> (<?= e(package_label($it)) ?>) × <?= (int)$it['cart_qty'] ?></span><strong>TZS <?= money($it['line_total']) ?></strong></div>
    <?php endforeach; ?>
    <div class="summary-total"><span>Total</span><strong>TZS <?= money($total) ?></strong></div>
  </aside>
</div>
