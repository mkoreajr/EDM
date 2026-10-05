<?php
require_once __DIR__ . '/db.php';
if(session_status() !== PHP_SESSION_ACTIVE){ session_start(); }
if(portal_logged_in()){
    $now=time(); $last=(int)($_SESSION['portal_last_activity']??0);
    if($last && ($now-$last)>=1800){ unset($_SESSION['portal_customer_id'],$_SESSION['portal_customer_name'],$_SESSION['portal_last_activity'],$_SESSION['portal_cart']); }
    else $_SESSION['portal_last_activity']=$now;
}
function portal_touch(){ $_SESSION['portal_last_activity']=time(); }
