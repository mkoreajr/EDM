<?php
session_start();
require "config/database.php";
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$stmt = $conn->prepare("SELECT id,name,username,password,role FROM users WHERE username=? LIMIT 1");
$stmt->bind_param("s",$username); $stmt->execute(); $result=$stmt->get_result();
if($user=$result->fetch_assoc()){
    if(hash('sha256',$password)===$user['password']){
        $_SESSION['user_id']=$user['id']; $_SESSION['name']=$user['name']; $_SESSION['role']=$user['role'];
        header("Location: dashboard.php"); exit;
    }
}
$_SESSION['login_error']="Invalid username or password.";
header("Location: index.php"); exit;