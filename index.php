<?php
session_start();
if(isset($_SESSION['user_id'])){header("Location: dashboard.php");exit;}
$error=$_SESSION['login_error']??'';unset($_SESSION['login_error']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>EDM Kienyeji Egg Shop — Sign In</title>
<style>
*{box-sizing:border-box}
html,body{margin:0;width:100%;height:100%;overflow:hidden;font-family:Arial,Helvetica,sans-serif}
.login{position:relative;width:100vw;height:100vh;background:#fff url('assets/login-approved.png') center/100% 100% no-repeat}
.login-form{position:absolute;left:62.8%;top:33.3%;width:32.3%;height:34%;margin:0}
.username,.password{
 position:absolute;left:0;width:100%;height:54px;
 border:0!important;outline:0!important;background:transparent!important;
 box-shadow:none!important;border-radius:8px;padding:0 52px;
 font:16px Arial;color:#14213d;
}
.username{top:0}.password{top:104px}
.username:focus,.password:focus{outline:2px solid rgba(0,143,93,.18)!important}
.toggle{
 position:absolute;right:5px;top:106px;width:42px;height:48px;
 border:0;background:transparent;color:transparent;cursor:pointer;
}
.remember-hit{
 position:absolute;left:0;top:177px;width:24px;height:24px;
 opacity:0;cursor:pointer;margin:0;
}
.forgot-hit{
 position:absolute;right:0;top:178px;width:150px;height:25px;
 border:0;background:transparent;cursor:pointer;
}
.submit{
 position:absolute;left:0;top:233px;width:100%;height:58px;
 border:0;background:transparent;color:transparent;cursor:pointer;
 border-radius:9px;
}
.error{position:absolute;left:62.8%;top:28%;width:32.3%;padding:9px 14px;border-radius:7px;background:#fff0f0;color:#a22;border:1px solid #eccaca;font-size:13px;z-index:5}
.error+ .login-form{top:38%}
@media(max-width:800px){
 body{overflow:auto;background:#006040}
 .login{width:100%;height:100vh;background:#006040}
 .login-form{position:absolute;left:6%;top:52%;width:88%;height:360px;background:#fff;border-radius:16px}
 .username,.password{left:28px;width:calc(100% - 56px);height:52px;border:1px solid #d3ddeb!important;background:#fff!important;padding:0 16px}
 .username{top:74px}.password{top:147px}
 .toggle{top:151px;right:28px;color:#71809a}
 .remember-hit{left:28px;top:219px}
 .forgot-hit{right:28px;top:219px}
 .submit{left:28px;top:273px;width:calc(100% - 56px);height:54px;background:#00945d;color:#fff;font-weight:800;font-size:16px}
}
</style>
</head>
<body>
<div class="login">
<?php if($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form class="login-form" method="post" action="login.php">
<input class="username" name="username" aria-label="Username" value="<?=htmlspecialchars($_POST['username']??'')?>" required autofocus>
<input class="password" id="password" type="password" name="password" aria-label="Password" required>
<button type="button" class="toggle" onclick="togglePassword()" aria-label="Show password"></button>
<input class="remember-hit" type="checkbox" name="remember" aria-label="Remember me">
<button class="forgot-hit" type="button" aria-label="Forgot password"></button>
<button class="submit" type="submit" aria-label="Sign In"></button>
</form>
</div>
<script>
function togglePassword(){const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';}
</script>
</body>
</html>