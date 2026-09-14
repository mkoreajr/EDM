<?php
session_start();
if(isset($_SESSION['user_id'])){header("Location: dashboard.php");exit;}
$error=$_SESSION['login_error']??'';unset($_SESSION['login_error']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>EDM Kienyeji Egg Shop — Sign In</title>
<style>
*{box-sizing:border-box}
html,body{margin:0;width:100%;height:100%;font-family:Inter,Arial,Helvetica,sans-serif;color:#0b1732}
body{background:#fff}
.login-page{height:100vh;min-height:650px;display:grid;grid-template-columns:55.3% 44.7%;overflow:hidden}
.brand-panel{position:relative;background:#005d3d url('assets/login-left.png') center/cover no-repeat;color:#fff;overflow:hidden}
.brand-panel:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(0,72,47,.10),rgba(0,72,47,.02))}
.brand-content{position:relative;z-index:2;height:100%;padding:30px 7%;display:flex;flex-direction:column}
.brand-copy{margin-top:55px;max-width:650px}
.brand-copy h1{font-size:clamp(40px,4.2vw,65px);line-height:.98;letter-spacing:-2px;margin:0 0 18px;font-weight:900;text-shadow:0 2px 8px rgba(0,0,0,.12)}
.brand-copy h1 span{color:#ffd43b}
.brand-copy p{font-size:20px;margin:0 0 28px;font-weight:500}
.brand-features{display:grid;gap:13px;width:min(440px,100%)}
.feature{display:flex;align-items:center;gap:14px}
.feature-icon{width:52px;height:52px;border-radius:12px;background:rgba(0,125,84,.72);border:1px solid rgba(255,255,255,.18);display:grid;place-items:center;font-size:25px;flex:none;box-shadow:0 5px 16px rgba(0,0,0,.12)}
.feature b{display:block;font-size:17px}.feature small{display:block;margin-top:3px;font-size:13px;opacity:.86}
.tagline{margin-top:auto;display:inline-flex;align-self:flex-start;gap:12px;border:1px solid rgba(255,255,255,.45);background:rgba(0,75,48,.38);padding:13px 22px;border-radius:28px;font-size:13px;backdrop-filter:blur(4px)}
.form-panel{height:100%;display:flex;justify-content:center;align-items:center;background:#fff;padding:40px 7%}
.form-box{width:min(600px,100%)}
.top-badge{display:flex;justify-content:flex-end;margin-bottom:65px;color:#067c50;font-size:14px;font-weight:700}
.top-badge span{width:42px;height:42px;border-radius:50%;background:#f2fbf7;display:grid;place-items:center;margin-right:10px;font-size:21px}
.welcome{font-size:13px;letter-spacing:1.5px;font-weight:800;color:#7585a0;margin-bottom:8px}
h2{font-size:51px;line-height:1;margin:0;letter-spacing:-2px}h2 span{color:#008d5b}
.subtitle{color:#71809a;font-size:17px;margin:14px 0 35px}
.field{margin-bottom:22px}.field label{display:block;font-size:15px;font-weight:800;margin-bottom:9px}
.input{height:56px;width:100%;border:1px solid #d4deea;border-radius:9px;display:flex;align-items:center;background:#fff;transition:.15s}
.input:focus-within{border-color:#008e5b;box-shadow:0 0 0 3px rgba(0,142,91,.08)}
.input-icon{width:48px;text-align:center;color:#73829b;font-size:18px}
.input input{height:100%;border:0;outline:0;flex:1;font-size:16px;color:#14213c;background:transparent;padding:0 10px 0 0}
.password-wrap{position:relative}.password-wrap .input{padding-right:45px}.eye{position:absolute;right:7px;top:7px;width:40px;height:42px;border:0;background:transparent;color:#72819a;cursor:pointer;font-size:17px}
.row{display:flex;justify-content:space-between;align-items:center;margin:1px 0 24px;font-size:14px}
.remember{display:flex;align-items:center;gap:8px;color:#34435d}.remember input{width:19px;height:19px;margin:0;accent-color:#008e5b}
.row a{color:#008b58;font-weight:800;text-decoration:none}
.signin{width:100%;height:57px;border:0;border-radius:9px;background:#008f5b;color:#fff;font-size:17px;font-weight:800;cursor:pointer;box-shadow:0 7px 18px rgba(0,143,91,.16)}
.signin:hover{background:#007c4f}
.divider{display:flex;align-items:center;gap:14px;color:#7c8ba2;font-size:13px;margin:27px 0 18px}.divider:before,.divider:after{content:"";height:1px;background:#e0e6ed;flex:1}
.payments{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.payment{border:1px solid #e1e8ef;border-radius:10px;padding:14px 8px;text-align:center}.payment .ico{font-size:23px;color:#008f5b}.payment b{display:block;font-size:14px;margin-top:6px}.payment small{display:block;color:#7c8ba2;font-size:11px;margin-top:5px}
.secure{margin-top:18px;text-align:center;background:#ebf8f2;color:#138258;padding:12px;border-radius:9px;font-size:12px;font-weight:700}
.demo{text-align:center;color:#8490a4;font-size:11px;margin-top:12px}
.alert{padding:10px 13px;border-radius:8px;margin-bottom:18px;font-size:13px}.danger{background:#fff0f0;color:#a32929;border:1px solid #efcccc}
@media(max-width:1000px){.login-page{grid-template-columns:50% 50%}.brand-copy{margin-top:35px}.brand-copy h1{font-size:42px}.form-panel{padding:35px 6%}.top-badge{margin-bottom:40px}}
@media(max-width:760px){.login-page{display:block;overflow:auto;height:auto;min-height:100vh}.brand-panel{height:330px}.brand-content{padding:24px 7%}.brand-copy{margin-top:28px}.brand-copy h1{font-size:36px}.brand-copy p,.brand-features,.tagline{display:none}.form-panel{min-height:calc(100vh - 330px);padding:35px 8%}.top-badge{margin-bottom:35px}.form-box{max-width:520px}h2{font-size:43px}.payments{gap:8px}}
</style>
</head>
<body>
<div class="login-page">
<section class="brand-panel">
<div class="brand-content">
<div class="brand-copy">
<h1>Your Complete<br>Egg Sales<br><span>Management System</span></h1>
<p>Sell smarter. Manage stock. Grow your business.</p>
<div class="brand-features">
<div class="feature"><div class="feature-icon">🛒</div><div><b>Manage Sales</b><small>Fast, easy and reliable</small></div></div>
<div class="feature"><div class="feature-icon">📦</div><div><b>Track Stock</b><small>Real-time inventory</small></div></div>
<div class="feature"><div class="feature-icon">👥</div><div><b>Customers &amp; Suppliers</b><small>Build stronger relationships</small></div></div>
<div class="feature"><div class="feature-icon">📊</div><div><b>Reports &amp; Insights</b><small>Make better decisions</small></div></div>
<div class="feature"><div class="feature-icon">💳</div><div><b>Multiple Payments</b><small>Cash • Mobile Money • Bank</small></div></div>
</div></div>
<div class="tagline">Fresh Eggs &nbsp;•&nbsp; Healthy Families &nbsp;•&nbsp; A Better Tomorrow</div>
</div>
</section>
<section class="form-panel">
<div class="form-box">
<div class="top-badge"><span>🍃</span>Simple. Professional. Reliable.</div>
<div class="welcome">WELCOME BACK</div><h2>Sign <span>in</span></h2>
<p class="subtitle">Access your EDM Kienyeji Egg Shop dashboard.</p>
<?php if($error):?><div class="alert danger"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post" action="login.php">
<div class="field"><label>Username</label><div class="input"><div class="input-icon">👤</div><input name="username" autocomplete="username" value="<?=htmlspecialchars($_POST['username']??'')?>" required autofocus></div></div>
<div class="field password-wrap"><label>Password</label><div class="input"><div class="input-icon">🔒</div><input id="password" type="password" name="password" autocomplete="current-password" required></div><button class="eye" type="button" onclick="togglePassword()">◉</button></div>
<div class="row"><label class="remember"><input type="checkbox" name="remember"> Remember me</label><a href="#" onclick="return false">Forgot password?</a></div>
<button class="signin" type="submit">↪ &nbsp; Sign In</button>
</form>
<div class="divider">or continue with</div>
<div class="payments">
<div class="payment"><div class="ico">▣</div><b>Cash</b><small>Simple &amp; Fast</small></div>
<div class="payment"><div class="ico">▯</div><b>Mobile Money</b><small>Secure &amp; Convenient</small></div>
<div class="payment"><div class="ico">▥</div><b>Bank</b><small>Safe &amp; Reliable</small></div>
</div>
<div class="secure">🛡 Secure • Reliable • Built for Egg Businesses</div>
<div class="demo">Demo administrator: <b>admin</b> / <b>admin123</b></div>
</div>
</section>
</div>
<script>function togglePassword(){const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';}</script>
</body></html>