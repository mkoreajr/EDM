<?php
require "auth.php";
$id=(int)($_GET['id']??0);

$s=$conn->query("SELECT s.*,COALESCE(c.name,'Walk-in Customer') AS customer,COALESCE(c.phone,'') AS customer_phone,
  COALESCE(u.name,'Administrator') AS cashier
  FROM sales s
  LEFT JOIN customers c ON c.id=s.customer_id
  LEFT JOIN users u ON u.id=s.created_by
  WHERE s.id=$id")->fetch_assoc();

if(!$s) die("Sale not found.");

$itemsRes=$conn->query("SELECT si.*,p.name,p.unit FROM sale_items si JOIN products p ON p.id=si.product_id WHERE si.sale_id=$id ORDER BY si.id");
$items=[]; $subtotal=0;
if($itemsRes){
  while($row=$itemsRes->fetch_assoc()){
    $items[]=$row;
    $subtotal+=(float)$row['total'];
  }
}
$discount=max(0,$subtotal-(float)$s['total_amount']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Receipt <?=e($s['sale_number'])?></title>
<style>
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{
  font-family:Inter, Segoe UI, Arial, Helvetica, sans-serif;
  background:linear-gradient(135deg,#eef8f3,#f9fbea);
  color:#17332a;
  min-height:100vh;
}
.receipt-shell{width:min(760px,100%);margin:0 auto;padding:22px 16px 30px}
.action-bar{
  display:flex;justify-content:center;align-items:center;gap:10px;flex-wrap:wrap;
  margin-bottom:16px;
}
.action-btn{
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  min-width:142px;height:42px;padding:0 17px;border-radius:8px;
  border:1px solid #d2dfda;background:#fff;color:#164333;
  font:700 13px Arial,Helvetica,sans-serif;text-decoration:none;cursor:pointer;
}
.action-btn svg{width:17px;height:17px}
.action-btn.primary{background:#008e5b;border-color:#008e5b;color:#fff}
.action-btn.primary:hover{background:#007249}
.action-btn:hover{background:#f3f8f5}
.receipt-card{
  background:#fff;border:1px solid #dce8e3;border-radius:13px;
  box-shadow:0 14px 40px rgba(0,72,48,.10);overflow:hidden;
}
.receipt-header{text-align:center;padding:25px 25px 14px}
.shop-logo{
  width:54px;height:54px;border-radius:50%;margin:0 auto 8px;
  display:grid;place-items:center;background:#fff0a0;color:#00764d;
}
.shop-logo svg{width:32px;height:32px}
.receipt-header h1{font-size:21px;margin:0;color:#006f49;letter-spacing:.4px}
.receipt-header .shop-sub{font-size:11px;color:#597067;margin:4px 0 0;font-style:italic}
.receipt-header .shop-address{font-size:11px;color:#53676d;margin:8px 0 0;line-height:1.45}
.receipt-title{
  border-top:1px dashed #bfcfca;border-bottom:1px dashed #bfcfca;
  margin:0 16px;padding:9px 0;text-align:center;font-size:18px;font-weight:800;
  color:#172d27;
}
.sale-meta{padding:13px 22px 5px;display:grid;grid-template-columns:1fr 1fr;gap:5px 22px;font-size:11px}
.meta-row{display:flex;justify-content:space-between;gap:10px}
.meta-row b{color:#405b52}.meta-row span{text-align:right;color:#1f302c}
.receipt-table{width:calc(100% - 32px);margin:9px 16px;border-collapse:collapse;font-size:11px}
.receipt-table th{background:#f0f7f3;color:#2b4d41;font-weight:800}
.receipt-table th,.receipt-table td{padding:8px 6px;border-bottom:1px solid #e3ebe7}
.receipt-table th:first-child,.receipt-table td:first-child{text-align:left}
.receipt-table th:not(:first-child),.receipt-table td:not(:first-child){text-align:right}
.totals{margin:6px 16px 0;border-top:1px dashed #bfcfca;padding:9px 6px 0;font-size:12px}
.total-row{display:flex;justify-content:space-between;padding:3px 0}
.grand-total{
  margin-top:5px;padding:9px;border-radius:7px;background:#e3f6ed;
  display:flex;justify-content:space-between;font-size:16px;font-weight:800;color:#10372b;
}
.payment-row{display:flex;justify-content:space-between;padding:8px 6px 0;font-size:12px}
.receipt-thanks{text-align:center;padding:17px 16px 7px;color:#4e665e;font-size:11px;font-weight:700;font-style:italic}
.receipt-tagline{text-align:center;color:#5d6e69;font-size:10px;font-style:italic;padding-bottom:5px}
@media(max-width:560px){
  .receipt-shell{padding:12px 9px 20px}
  .action-bar{gap:7px;margin-bottom:10px}
  .action-btn{min-width:0;flex:1;height:39px;padding:0 9px;font-size:11px}
  .action-btn svg{width:15px;height:15px}
  .receipt-header{padding:20px 15px 12px}
  .receipt-header h1{font-size:18px}
  .receipt-title{font-size:16px;margin:0 11px}
  .sale-meta{padding:11px 14px 4px;grid-template-columns:1fr;gap:4px}
  .receipt-table{width:calc(100% - 20px);margin:8px 10px;font-size:9px}
  .receipt-table th,.receipt-table td{padding:6px 4px}
  .totals{margin:5px 10px 0}
}
@media print{
  @page{size:A4;margin:10mm}
  body{background:#fff}
  .receipt-shell{width:100%;padding:0}
  .action-bar{display:none!important}
  .receipt-card{border:0;box-shadow:none;border-radius:0}
  .receipt-header{padding-top:8px}
}
</style>
</head>
<body>
<div class="receipt-shell">

  <div class="action-bar">
    <a class="action-btn" href="sales.php" title="Back to Sales">
      <svg viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Back to Sales
    </a>
    <button class="action-btn" id="downloadPdf" type="button" title="Download PDF">
      <svg viewBox="0 0 24 24" fill="none"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Download PDF
    </button>
    <button class="action-btn primary" type="button" onclick="window.print()" title="Print Receipt">
      <svg viewBox="0 0 24 24" fill="none"><path d="M7 8V4h10v4M6 17H4a1 1 0 0 1-1-1v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6a1 1 0 0 1-1 1h-2M7 14h10v7H7v-7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M17 11h.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
      Print Receipt
    </button>
  </div>

  <div class="receipt-card" id="receiptDocument">
    <div class="receipt-header">
      <div class="shop-logo" aria-hidden="true">
        <svg viewBox="0 0 32 32" fill="none">
          <path d="M16 4c-4.5 3.2-8 8.5-8 14.1C8 23.1 11.5 27 16 27s8-3.9 8-8.9C24 12.5 20.5 7.2 16 4Z" fill="currentColor"/>
          <path d="M16 11c-2.2 2.3-3.8 4.7-3.8 7.4 0 2.3 1.7 4 3.8 4s3.8-1.7 3.8-4c0-2.7-1.6-5.1-3.8-7.4Z" fill="#ffd72e"/>
        </svg>
      </div>
      <h1>EDM KIENYEJI — EGG SHOP</h1>
      <div class="shop-sub">Fresh Eggs • Healthy Families • A Better Tomorrow</div>
      <div class="shop-address">Mwanza, Tanzania<br>Phone: 0712 345 678</div>
    </div>

    <div class="receipt-title">SALES RECEIPT</div>

    <div class="sale-meta">
      <div class="meta-row"><b>Receipt No:</b><span><?=e($s['sale_number'])?></span></div>
      <div class="meta-row"><b>Date:</b><span><?=e($s['sale_date'])?></span></div>
      <div class="meta-row"><b>Cashier:</b><span><?=e($s['cashier'])?></span></div>
      <div class="meta-row"><b>Customer:</b><span><?=e($s['customer'])?></span></div>
      <?php if($s['customer_phone']!==''): ?><div class="meta-row"><b>Phone:</b><span><?=e($s['customer_phone'])?></span></div><?php endif; ?>
      <div class="meta-row"><b>Payment:</b><span><?=e($s['payment_method'])?></span></div>
    </div>

    <table class="receipt-table">
      <thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
      <tbody>
      <?php $no=1; foreach($items as $i): ?>
        <tr>
          <td><?=$no++?></td>
          <td><?=e($i['name'])?> (<?=e($i['unit'])?>)</td>
          <td><?=money($i['quantity'])?></td>
          <td><?=money($i['unit_price'])?></td>
          <td><?=money($i['total'])?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div class="totals">
      <div class="total-row"><span>Subtotal</span><b>TZS <?=money($subtotal)?></b></div>
      <div class="total-row"><span>Discount</span><b>TZS <?=money($discount)?></b></div>
      <div class="grand-total"><span>TOTAL (TSH)</span><span>TZS <?=money($s['total_amount'])?></span></div>
    </div>

    <div class="payment-row"><span>Payment Method:</span><b><?=e(strtoupper($s['payment_method']))?></b></div>
    <div class="receipt-thanks">Thank you for shopping with us!</div>
    <div class="receipt-tagline">“Fresh Eggs • Healthy Families • A Better Tomorrow”</div>
  </div>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
(function(){
  var receiptCode=<?=json_encode($s['sale_number'])?>;
  var lookupUrl=location.origin + location.pathname.replace(/receipt\\.php$/,'receipt_lookup.php') + '?code=' + encodeURIComponent(receiptCode);
  var pdf=document.getElementById('downloadPdf');
  if(pdf){
    pdf.addEventListener('click',function(){
      var original=pdf.innerHTML;
      pdf.disabled=true;
      pdf.textContent='Preparing PDF...';
      if(window.html2pdf){
        var element=document.getElementById('receiptDocument');
        html2pdf().set({
          margin:[7,7,7,7],
          filename:'Receipt-'+receiptCode+'.pdf',
          image:{type:'jpeg',quality:.98},
          html2canvas:{scale:2,useCORS:true,backgroundColor:'#ffffff'},
          jsPDF:{unit:'mm',format:'a4',orientation:'portrait'}
        }).from(element).save().then(function(){
          pdf.disabled=false; pdf.innerHTML=original;
        }).catch(function(){
          pdf.disabled=false; pdf.innerHTML=original;
          window.print();
        });
      }else{
        pdf.disabled=false; pdf.innerHTML=original;
        window.print();
      }
    });
  }
})();
</script>
</body>
</html>
