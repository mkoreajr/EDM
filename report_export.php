<?php
require "auth.php";
$from=$_GET['from']??date('Y-m-01');
$to=$_GET['to']??date('Y-m-d');
$format=$_GET['format']??'';
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)) $from=date('Y-m-01');
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)) $to=date('Y-m-d');
if($from>$to){$tmp=$from;$from=$to;$to=$tmp;}

$sql="SELECT s.sale_number,s.sale_date,COALESCE(c.name,'Walk-in Customer') AS customer,
 u.name AS cashier,s.payment_method,p.name AS product,p.category,p.package_size_kg,p.unit,
 si.quantity,si.unit_price,si.total,s.total_amount
 FROM sale_items si JOIN sales s ON s.id=si.sale_id
 JOIN products p ON p.id=si.product_id LEFT JOIN customers c ON c.id=s.customer_id
 JOIN users u ON u.id=s.created_by WHERE s.sale_date BETWEEN ? AND ?
 ORDER BY s.sale_date ASC,s.id ASC,si.id ASC";
$st=$conn->prepare($sql);$st->bind_param('ss',$from,$to);$st->execute();$rows=[];$r=$st->get_result();while($x=$r->fetch_assoc())$rows[]=$x;
$st=$conn->prepare("SELECT COUNT(*) sale_count,COALESCE(SUM(total_amount),0) total FROM sales WHERE sale_date BETWEEN ? AND ?");$st->bind_param('ss',$from,$to);$st->execute();$summary=$st->get_result()->fetch_assoc();
$downloadedBy=$_SESSION['name']??$_SESSION['username']??'Administrator';

if($format==='excel'){
 header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
 header('Content-Disposition: attachment; filename="MSINDA-Food-Shop-Sales-Report-'.$from.'-to-'.$to.'.xls"');
 echo "\xEF\xBB\xBF";
 echo '<html><head><meta charset="UTF-8"><style>table{border-collapse:collapse;font-family:Arial}th,td{border:1px solid #bbb;padding:7px}th{background:#eaf5ef} .num{text-align:right}</style></head><body>';
 echo '<h2>MSINDA FOOD SHOP</h2><h3>Sales Report</h3>';
 echo '<p><b>Report Period:</b> '.e($from).' to '.e($to).'<br><b>Downloaded By:</b> '.e($downloadedBy).'<br><b>Total Sales:</b> '.number_format((float)$summary['sale_count']).' &nbsp; <b>Total Amount:</b> TZS '.money($summary['total']).'</p>';
 echo '<table><tr><th>SN</th><th>Sale No.</th><th>Date</th><th>Customer</th><th>Cashier</th><th>Product</th><th>Category</th><th>Quantity</th><th>Unit Price</th><th>Line Total</th><th>Payment Method</th></tr>';
 $sn=1;foreach($rows as $x){echo '<tr><td>'.($sn++).'</td><td>'.e($x['sale_number']).'</td><td>'.e($x['sale_date']).'</td><td>'.e($x['customer']).'</td><td>'.e($x['cashier']).'</td><td>'.e($x['product']).'</td><td>'.e($x['category']).'</td><td class="num">'.e($x['quantity']).'</td><td class="num">TZS '.money($x['unit_price']).'</td><td class="num">TZS '.money($x['total']).'</td><td>'.e($x['payment_method']).'</td></tr>';}
 echo '<tr><td colspan="9"><b>REPORT TOTAL</b></td><td class="num"><b>TZS '.money($summary['total']).'</b></td><td></td></tr></table></body></html>';exit;
}

if($format==='pdf'){
 $logo=__DIR__.'/assets/branding/msinda-agricultural-emblem.png';
 if(!is_file($logo) || !is_readable($logo)){ http_response_code(500); echo 'Report logo asset is missing.'; exit; }

 // Dependency-free, landscape A4 PDF. Designed so report columns and names remain readable.
 class SimplePDF{
  public array $pages=[]; private array $objs=[]; private string $pageContent='';
  private int $pageW=842,$pageH=595;
  function esc($s){return str_replace(['\\','(',')'],['\\\\','\\(','\\)'],(string)$s);}
  function text($x,$y,$txt,$size=8,$bold=false){
   $font=$bold?'F2':'F1';
   // Explicit black text so all names/details remain visible in every PDF viewer.
   $this->pageContent.="0 0 0 rg BT /$font $size Tf 1 0 0 1 $x ".($this->pageH-$y)." Tm (".$this->esc($txt).") Tj ET\n";
  }
  function line($x1,$y1,$x2,$y2){$this->pageContent.="0.75 w 0.45 0.45 0.45 RG $x1 ".($this->pageH-$y1)." m $x2 ".($this->pageH-$y2)." l S\n";}
  function rect($x,$y,$w,$h,$fill=true){
   if($fill)$this->pageContent.="0.91 0.96 0.93 rg $x ".($this->pageH-$y-$h)." $w $h re f\n";
   else $this->pageContent.="0.45 0.45 0.45 RG $x ".($this->pageH-$y-$h)." $w $h re S\n";
  }
  function addPage(){if($this->pageContent!=='')$this->pages[]=$this->pageContent;$this->pageContent='';}
  function centerText($y,$txt,$size=8,$bold=false){
   // Approximate Helvetica text width and center it on the landscape A4 page.
   $factor=$bold?0.56:0.52;
   $width=strlen((string)$txt)*$size*$factor;
   $x=max(25,($this->pageW-$width)/2);
   $this->text($x,$y,$txt,$size,$bold);
  }
  function image($path,$x,$y,$w,$h){$this->pageContent.="q $w 0 0 $h $x ".($this->pageH-$y-$h)." cm /Im1 Do Q\n";}
  function output($imagePath){
   $this->addPage();
   $catalog=1;$pages=2;$font1=3;$font2=4;$img=5;$next=6;$pageObjs=[];
   $this->objs[$catalog]='';$this->objs[$pages]='';
   $this->objs[$font1]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
   $this->objs[$font2]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
   $jpg=file_get_contents($imagePath);$im=getimagesize($imagePath);
   $this->objs[$img]="<< /Type /XObject /Subtype /Image /Width {$im[0]} /Height {$im[1]} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($jpg)." >>\nstream\n".$jpg."\nendstream";
   foreach($this->pages as $c){
    $co=$next++;$po=$next++;
    $this->objs[$co]="<< /Length ".strlen($c)." >>\nstream\n".$c."endstream";
    $this->objs[$po]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> /XObject << /Im1 5 0 R >> >> /Contents '.$co.' 0 R >>';
    $pageObjs[]=$po;
   }
   $kids=implode(' ',array_map(fn($v)=>$v.' 0 R',$pageObjs));
   $this->objs[$pages]='<< /Type /Pages /Kids ['.$kids.'] /Count '.count($pageObjs).' >>';
   $this->objs[$catalog]='<< /Type /Catalog /Pages 2 0 R >>';
   ksort($this->objs);
   $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";$offs=[0];
   foreach($this->objs as $id=>$body){$offs[$id]=strlen($pdf);$pdf.=$id." 0 obj\n".$body."\nendobj\n";}
   $xref=strlen($pdf);$max=max(array_keys($this->objs));
   $pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";
   for($i=1;$i<=$max;$i++)$pdf.=sprintf("%010d 00000 n \n",$offs[$i]??0);
   $pdf.="trailer << /Size ".($max+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
   return $pdf;
  }
 }

 $pdf=new SimplePDF();
 $pageHeader=function()use($pdf,$logo,$from,$to,$downloadedBy,$summary){
  $pdf->image($logo,25,20,150,50);
  $pdf->centerText(37,'MSINDA FOOD SHOP',20,true);
  $pdf->centerText(58,'SALES REPORT',12,true);
  $pdf->text(25,88,'Report Period: '.$from.' to '.$to,9,true);
  $pdf->text(25,104,'Downloaded By: '.$downloadedBy,9);
  $pdf->text(25,120,'Generated On: '.date('Y-m-d H:i'),9);
  $pdf->text(25,136,'Total Sales: '.number_format((float)$summary['sale_count']).'    Total Amount: TZS '.money($summary['total']),9,true);
 };
 $pageHeader();
 $y=165;
 $headers=['SN','Sale No.','Date','Customer','Cashier','Product','Qty','Unit Price','Line Total','Payment'];
 $xs=[25,50,160,225,325,405,505,550,635,730];
 $widths=[25,110,65,100,80,100,45,85,95,87];
 $pdf->rect(25,$y-14,782,24,true);
 foreach($headers as $i=>$h)$pdf->text($xs[$i]+2,$y,$h,8,true);
 $y+=21;$sn=1;$rowNo=0;
 foreach($rows as $x){
  if($y>555){
   $pdf->addPage();
   $pageHeader();
   $pdf->text(25,155,'Sales Details (continued)',10,true);
   $y=177;
   $pdf->rect(25,$y-14,782,24,true);
   foreach($headers as $i=>$h)$pdf->text($xs[$i]+2,$y,$h,8,true);
   $y+=21;
  }
  if($rowNo%2===0)$pdf->rect(25,$y-11,782,18,true);
  $vals=[
   $sn++,
   $x['sale_number'],
   $x['sale_date'],
   $x['customer'],
   $x['cashier'],
   $x['product'],
   number_format((float)$x['quantity'],2),
   'TZS '.number_format((float)$x['unit_price'],2),
   'TZS '.number_format((float)$x['total'],2),
   $x['payment_method']
  ];
  foreach($vals as $i=>$v){
   $s=(string)$v;
   $max=($i===1?18:($i===3?17:($i===4?14:($i===5?17:($i===9?14:18)))));
   if(strlen($s)>$max)$s=substr($s,0,$max-1).'…';
   $pdf->text($xs[$i]+2,$y,$s,7.2,false);
  }
  $pdf->line(25,$y+6,807,$y+6);$y+=18;$rowNo++;
 }
 if($y>545){$pdf->addPage();$y=45;}
 $pdf->text(25,$y+14,'REPORT TOTAL: TZS '.money($summary['total']),11,true);
 $data=$pdf->output($logo);
 header('Content-Type: application/pdf');
 header('Content-Disposition: attachment; filename="MSINDA-Food-Shop-Sales-Report-'.$from.'-to-'.$to.'.pdf"');
 header('Content-Length: '.strlen($data));
 echo $data;exit;
}
http_response_code(400);echo 'Invalid report format.';
