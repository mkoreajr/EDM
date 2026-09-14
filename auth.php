<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: index.php"); exit; }
require_once __DIR__ . "/config/database.php";
function e($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function money($v){ return number_format((float)$v,2); }
