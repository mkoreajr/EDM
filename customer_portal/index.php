<?php
// Public customer entry: /shop opens the shopping page directly.
require_once __DIR__ . '/db.php';
if(session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Location: shop.php');
exit;
