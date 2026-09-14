<?php
session_start();
require_once __DIR__ . "/config/database.php";

function e($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function money($v){ return number_format((float)$v,2); }
function is_admin(){ return strtolower((string)($_SESSION['role'] ?? '')) === 'admin'; }
function require_admin(){
    if(!is_admin()){
        http_response_code(403);
        exit("Access denied.");
    }
}
function password_change_required(){
    $uid=(int)($_SESSION['user_id']??0);
    if(!$uid) return false;
    try{
        $st=$conn->prepare("SELECT must_change_password FROM users WHERE id=? LIMIT 1");
        $st->bind_param("i",$uid); $st->execute();
        $u=$st->get_result()->fetch_assoc();
        return !empty($u['must_change_password']);
    }catch(Throwable $e){ return false; }
}

if(!isset($_SESSION['user_id'])){ header("Location: index.php"); exit; }

if(password_change_required() && basename($_SERVER['PHP_SELF']) !== 'change_password.php' && basename($_SERVER['PHP_SELF']) !== 'logout.php'){
    header("Location: change_password.php?required=1");
    exit;
}
?>
