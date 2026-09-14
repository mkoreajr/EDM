<?php
session_start();
if(isset($_SESSION['user_id'])){header("Location: dashboard.php");exit;}
$error=$_SESSION['login_error']??'';unset($_SESSION['login_error']);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>EDM Kienyeji Egg Shop — Sign In</title>
<style>
*{box-sizing:border-box}html,body{margin:0;width:100%;height:100%;overflow:hidden;font-family:Arial,Helvetica,sans-serif}
.login{position:relative;width:100vw;height:100vh;background:url('assets/login-approved.png') center/100% 100% no-repeat}
form{position:absolute;left:62.75%;top:33.65%;width:32.3%;height:32%;margin:0}
.username,.password{position:absolute;left:0;width:100%;height:54px;padding:0 50px;border:1px solid #d3ddeb;border-radius:8px;background:#fff;font:16px Arial;color:#13213e;outline:none}
.username{top:0}.password{top:104px}.username:focus,.password:focus{border-color:#008f5b;box-shadow:0 0 0 2px #008f5b18}
.toggle{position:absolute;right:6px;top:108px;width:40px;height:42px;border:0;background:transparent;color:#71809a;cursor:pointer}
.remember{position:absolute;left:0;top:181px;display:flex;align-items:center;gap:7px;font-size:14px;color:#263650;white-space:nowrap}
.remember input{width:20px;height:20px;margin:0;accent-color:#008f5b}
.forgot{position:absolute;right:0;top:184px;color:#078b58;font-size:14px;font-weight:700;text-decoration:none}
.submit{position:absolute;left:0;top:236px;width:100%;height:58px;border:0;border-radius:9px;background:#00945d;color:#fff;font-size:17px;font-weight:800;cursor:pointer;box-shadow:0 7px 18px #00945d20}
.error{position:absolute;left:62.75%;top:27%;width:32.3%;padding:9px 14px;border-radius:7px;background:#fff0f0;color:#a22;border:1px solid #eccaca;font-size:13px;z-index:5}
@media(max-width:800px){
body{overflow:auto;background:#006040}.login{height:auto;min-height:100vh;background:#006040}
.login:before{content:"";display:block;height:260px;background:url('assets/login-approved.png') left top/auto 100% no-repeat}
form{position:relative;left:auto;top:auto;width:88%;height:365px;margin:25px auto;background:#fff;border-radius:16px;padding:28px}
form:after{content:"EDM Kienyeji Egg Shop";display:block;position:absolute;top:20px;left:28px;font-size:25px;font-weight:800;color:#008b59}
.username,.password{position:absolute;left:28px;width:calc(100% - 56px);height:52px}.username{top:75px}.password{top:148px}.toggle{top:153px;right:28px}.remember{left:28px;top:220px}.forgot{right:28px;top:223px}.submit{left:28px;width:calc(100% - 56px);top:275px;height:52px}.error{position:relative;left:auto;top:auto;width:88%;margin:15px auto}
}
</style></head><body><div class="login">
<?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post" action="login.php">
<input class="username" name="username" aria-label="Username" value="<?=htmlspecialchars($_POST['username']??'')?>" required autofocus>
<input class="password" id="password" type="password" name="password" aria-label="Password" required>
<button type="button" class="toggle" onclick="togglePassword()" aria-label="Show password">◉</button>
<label class="remember"><input type="checkbox" name="remember"><span>Remember me</span></label>
<a class="forgot" href="#" onclick="return false">Forgot password?</a>
<button class="submit" type="submit">↪ &nbsp; Sign In</button>
</form></div>
<script>function togglePassword(){const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';}</script>
</body></html>