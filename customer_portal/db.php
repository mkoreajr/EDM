<?php
require_once __DIR__ . '/../config/database.php';
function pe($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function pmoney($v){ return number_format((float)$v, 2); }
function portal_customer_id(){ return (int)($_SESSION['portal_customer_id'] ?? 0); }
function portal_logged_in(){ return portal_customer_id() > 0; }
function portal_require_login(){ if(!portal_logged_in()){ header('Location: /shop'); exit; } }
function portal_current_customer(){
    global $conn;
    $id=portal_customer_id(); if(!$id) return null;
    $st=$conn->prepare("SELECT c.*, ca.username FROM customer_accounts ca JOIN customers c ON c.id=ca.customer_id WHERE ca.customer_id=? LIMIT 1");
    $st->bind_param('i',$id); $st->execute(); return $st->get_result()->fetch_assoc();
}
function portal_cart(){ return $_SESSION['portal_cart'] ?? []; }
function portal_cart_count(){ $n=0; foreach(portal_cart() as $q) $n+=(int)$q; return $n; }
function portal_cart_total(){
    global $conn; $cart=portal_cart(); if(!$cart) return 0;
    $ids=array_map('intval',array_keys($cart)); $ids=array_values(array_filter($ids)); if(!$ids) return 0;
    $in=implode(',', $ids); $rs=$conn->query("SELECT id,selling_price FROM products WHERE id IN ($in)"); $total=0;
    while($p=$rs->fetch_assoc()) $total+=(float)$p['selling_price']*(int)($cart[$p['id']]??0);
    return $total;
}
function portal_status_class($status){ return strtolower(str_replace(' ','-',(string)$status)); }
