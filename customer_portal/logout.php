<?php
if(session_status() !== PHP_SESSION_ACTIVE) session_start();
unset($_SESSION['portal_customer_id'],$_SESSION['portal_customer_name'],$_SESSION['portal_last_activity'],$_SESSION['portal_cart']);
header('Location: /shop'); exit;
