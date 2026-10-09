<?php
/**
 * Printable sales receipt (standalone page).
 *
 * @var array<string,mixed> $sale
 * @var list<array<string,mixed>> $items
 * @var float $subtotal
 * @var float $discount
 * @var string $footer
 */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Receipt <?= e($sale['sale_number']) ?></title>
<link rel="stylesheet" href="<?= asset('css/receipt.css') ?>">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" defer></script>
<script src="<?= asset('js/receipt.js') ?>" defer></script>
</head>
<body>
<div class="receipt-shell">
  <div class="action-bar">
    <a class="action-btn" href="/sales" title="Back to Sales">
      <svg viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Back to Sales
    </a>
    <button class="action-btn" id="downloadPdf" type="button" title="Download PDF" data-filename="Receipt-<?= e($sale['sale_number']) ?>.pdf">
      <svg viewBox="0 0 24 24" fill="none"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Download PDF
    </button>
    <button class="action-btn primary" type="button" id="printReceipt" title="Print Receipt">
      <svg viewBox="0 0 24 24" fill="none"><path d="M7 8V4h10v4M6 17H4a1 1 0 0 1-1-1v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6a1 1 0 0 1-1 1h-2M7 14h10v7H7v-7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M17 11h.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
      Print Receipt
    </button>
  </div>

  <div class="receipt-card" id="receiptDocument">
    <div class="receipt-header">
      <img class="shop-logo" src="<?= asset('img/brand/msinda-emblem.png') ?>" alt="MSINDA Food Shop logo">
    </div>

    <div class="receipt-title">SALES RECEIPT</div>

    <div class="sale-meta">
      <div class="meta-row"><b>Receipt No:</b><span><?= e($sale['sale_number']) ?></span></div>
      <div class="meta-row"><b>Date:</b><span><?= e($sale['sale_date']) ?></span></div>
      <div class="meta-row"><b>Cashier:</b><span><?= e($sale['cashier']) ?></span></div>
      <div class="meta-row"><b>Customer:</b><span><?= e($sale['customer']) ?></span></div>
      <?php if ($sale['customer_phone'] !== ''): ?><div class="meta-row"><b>Phone:</b><span><?= e($sale['customer_phone']) ?></span></div><?php endif; ?>
      <div class="meta-row"><b>Payment:</b><span><?= e($sale['payment_method']) ?></span></div>
    </div>

    <table class="receipt-table">
      <thead><tr><th>SN</th><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
      <tbody>
      <?php foreach ($items as $n => $item): ?>
        <tr>
          <td><?= $n + 1 ?></td>
          <td><?= e($item['name']) ?> (<?= e(package_label($item)) ?>)</td>
          <td><?= money($item['quantity']) ?></td>
          <td><?= money($item['unit_price']) ?></td>
          <td><?= money($item['total']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div class="totals">
      <div class="total-row"><span>Subtotal</span><b>TZS <?= money($subtotal) ?></b></div>
      <div class="total-row"><span>Discount</span><b>TZS <?= money($discount) ?></b></div>
      <div class="grand-total"><span>TOTAL (TSH)</span><span>TZS <?= money($sale['total_amount']) ?></span></div>
    </div>

    <div class="payment-row">Payment Method: <b><?= e(ucwords(strtolower((string)$sale['payment_method']))) ?></b></div>
    <div class="receipt-thanks"><?= e($footer) ?></div>
    <div class="receipt-tagline">“Mchele • Unga • Mayai”</div>
  </div>
</div>
</body>
</html>
