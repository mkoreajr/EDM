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
html,body{margin:0;width:100%;height:100%;font-family:Arial,Helvetica,sans-serif;background:#fff;color:#0b1732}
.page{width:100vw;height:100vh;display:grid;grid-template-columns:55.4% 44.6%;overflow:hidden}
.visual{height:100vh;background:#005e3e url('assets/approved-left.png') center/cover no-repeat}
.login{height:100vh;display:flex;align-items:center;justify-content:center;padding:35px 7%;background:#fff}
.card{width:min(570px,100%)}
.badge{display:flex;justify-content:flex-end;align-items:center;gap:10px;color:#087b50;font-size:14px;font-weight:700;margin-bottom:64px}
.badge span{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:#f0faf5;font-size:20px}
.welcome{font-size:13px;letter-spacing:1.4px;font-weight:800;color:#7a89a3;margin-bottom:8px}
h1{font-size:50px;line-height:1;margin:0;letter-spacing:-2px;color:#08152f}h1 span{color:#008d5b}
.sub{font-size:16px;color:#72809a;margin:14px 0 34px}
.field{margin-bottom:21px}.field label{display:block;font-size:14px;font-weight:800;margin-bottom:8px}
.input{height:56px;border:1px solid #d4deea;border-radius:9px;display:flex;align-items:center;background:#fff}
.input:focus-within{border-color:#008f5b;box-shadow:0 0 0 3px #008f5b12}
.icon{width:50px;text-align:center;font-size:18px;color:#73829a}.input input{flex:1;height:100%;border:0;outline:0;font-size:16px;background:transparent;color:#16233d;padding:0 12px 0 0}
.pass{position:relative}.pass .input{padding-right:43px}.eye{position:absolute;right:6px;top:7px;width:40px;height:42px;border:0;background:transparent;color:#73829a;cursor:pointer;font-size:16px}
.row{display:flex;justify-content:space-between;align-items:center;margin:0 0 23px;font-size:14px}.remember{display:flex;align-items:center;gap:8px;color:#33425d}.remember input{width:19px;height:19px;margin:0;accent-color:#008f5b}.forgot{color:#008b58;text-decoration:none;font-weight:800}
.btn{width:100%;height:57px;border:0;border-radius:9px;background:#008f5b;color:#fff;font-size:17px;font-weight:800;cursor:pointer;box-shadow:0 7px 18px #008f5b22}
.divider{display:flex;align-items:center;gap:14px;color:#7d8aa0;font-size:13px;margin:27px 0 18px}.divider:before,.divider:after{content:"";height:1px;background:#e1e6ed;flex:1}
.pay{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.paybox{border:1px solid #e1e8ef;border-radius:10px;text-align:center;padding:14px 7px}.paybox .ico{font-size:22px;color:#008f5b}.paybox b{display:block;font-size:14px;margin-top:6px}.paybox small{display:block;font-size:11px;color:#7b89a1;margin-top:5px}
.secure{margin-top:18px;background:#ebf8f2;color:#16825a;text-align:center;border-radius:9px;padding:12px;font-size:12px;font-weight:700}.demo{text-align:center;font-size:11px;color:#8490a3;margin-top:12px}
.error{background:#fff0f0;border:1px solid #edcaca;color:#a22929;border-radius:8px;padding:10px 13px;margin-bottom:16px;font-size:13px}
@media(max-width:900px){.page{grid-template-columns:50% 50%}.login{padding:30px 5%}.badge{margin-bottom:40px}.visual{background-position:center}.card{max-width:520px}}
@media(max-width:700px){.page{display:block;height:auto;min-height:100vh;overflow:auto}.visual{height:300px;background-position:center top}.login{height:auto;min-height:calc(100vh - 300px);padding:35px 8%}.badge{margin-bottom:35px}h1{font-size:42px}}
</style>
</head>
<body>
<div class="page">
<div class="visual" aria-label="EDM Kienyeji Egg Shop"></div>
<div class="login"><div class="card">
<div class="badge"><span>🍃</span>Simple. Professional. Reliable.</div>
<div class="welcome">WELCOME BACK</div>
<h1>Sign <span>in</span></h1>
<p class="sub">Access your EDM Kienyeji Egg Shop dashboard.</p>
<?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post" action="login.php">
<div class="field"><label>Username</label><div class="input"><div class="icon">👤</div><input name="username" autocomplete="username" value="<?=htmlspecialchars($_POST['username']??'')?>" required autofocus></div></div>
<div class="field pass"><label>Password</label><div class="input"><div class="icon">🔒</div><input id="password" type="password" name="password" autocomplete="current-password" required></div><button class="eye" type="button" onclick="togglePassword()">◉</button></div>
<div class="row"><label class="remember"><input type="checkbox" name="remember"> Remember me</label><a class="forgot" href="#" onclick="return false">Forgot password?</a></div>
<button class="btn" type="submit">↪ &nbsp; Sign In</button>
</form>
<div class="divider">or continue with</div>
<div class="pay">
<div class="paybox"><div class="ico">▣</div><b>Cash</b><small>Simple &amp; Fast</small></div>
<div class="paybox"><div class="ico">▯</div><b>Mobile Money</b><small>Secure &amp; Convenient</small></div>
<div class="paybox"><div class="ico">▥</div><b>Bank</b><small>Safe &amp; Reliable</small></div>
</div>
<div class="secure">🛡 Secure • Reliable • Built for Egg Businesses</div>
<div class="demo">Demo administrator: <b>admin</b> / <b>admin123</b></div>
</div></div>
</div>
<script>function togglePassword(){const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';}</script>
</body>
</html>