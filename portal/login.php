<?php
require_once __DIR__ . '/db.php';
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

if(portal_logged_in()){
    header('Location: shop.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: index.php');
    exit;
}

$username=trim((string)($_POST['username']??''));
$password=(string)($_POST['password']??'');

if($username==='' || $password===''){
    $_SESSION['portal_error']='Please enter your username and password.';
    $_SESSION['portal_username']=$username;
    header('Location: index.php');
    exit;
}

$st=$conn->prepare("SELECT ca.*,c.name FROM customer_accounts ca JOIN customers c ON c.id=ca.customer_id WHERE ca.username=? LIMIT 1");
$st->bind_param('s',$username);
$st->execute();
$u=$st->get_result()->fetch_assoc();

if($u && hash('sha256',$password)===$u['password']){
    session_regenerate_id(true);
    $_SESSION['portal_customer_id']=(int)$u['customer_id'];
    $_SESSION['portal_customer_name']=$u['name'];
    $_SESSION['portal_last_activity']=time();
    unset($_SESSION['portal_error'], $_SESSION['portal_username']);

    $up=$conn->prepare("UPDATE customer_accounts SET last_login_at=CURRENT_TIMESTAMP WHERE id=?");
    $up->bind_param('i',$u['id']);
    $up->execute();

    header('Location: shop.php');
    exit;
}

$_SESSION['portal_error']='Invalid username or password. Please check your details and try again.';
$_SESSION['portal_username']=$username;
header('Location: index.php');
exit;
