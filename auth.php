<?php
if(session_status() !== PHP_SESSION_ACTIVE){ session_start(); }
require_once __DIR__ . "/config/database.php";

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($v){ return number_format((float)$v,2); }
function is_admin(){ return strtolower((string)($_SESSION['role'] ?? '')) === 'admin'; }
function require_admin(){ if(!is_admin()){ http_response_code(403); exit("Access denied."); } }

function password_change_required(){
    $uid=(int)($_SESSION['user_id'] ?? 0);
    if($uid <= 0 || !isset($GLOBALS['conn'])) return false;
    try{
        $st=$GLOBALS['conn']->prepare("SELECT must_change_password FROM users WHERE id=? LIMIT 1");
        $st->bind_param("i",$uid);
        $st->execute();
        $u=$st->get_result()->fetch_assoc();
        return is_array($u) && !empty($u['must_change_password']);
    }catch(Throwable $e){
        return false;
    }
}

if(empty($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}

$currentPage=basename((string)($_SERVER['PHP_SELF'] ?? ''));
if(password_change_required() && !in_array($currentPage,['change_password.php','logout.php'],true)){
    header("Location: change_password.php?required=1");
    exit;
}

$cashierPages=['dashboard.php','sales.php','customers.php','receipt.php','notifications.php','change_password.php','logout.php'];
if(!is_admin() && !in_array($currentPage,$cashierPages,true)){
    header("Location: dashboard.php");
    exit;
}
?>