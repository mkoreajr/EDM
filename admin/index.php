<?php
// Friendly admin entry URL: /admin
// Authentication and role enforcement are still handled by the existing admin auth layer.
require_once __DIR__ . '/../auth.php';
header('Location: ../dashboard.php');
exit;
