<?php
// Public customer entry: open the shop directly.
require_once __DIR__ . '/db.php';
if(session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Location: shop.php');
exit;
