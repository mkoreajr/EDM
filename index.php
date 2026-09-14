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
html,body{margin:0;width:100%;height:100%;font-family:Inter,Arial,Helvetica,sans-serif;background:#fff}
.page{width:100vw;height:100vh;min-height:680px;display:grid;grid-template-columns:56% 44%;overflow:hidden}
.left{position:relative;height:100%;overflow:hidden;background:
 radial-gradient(circle at 80% 15%,rgba(49,139,84,.55),transparent 34%),
 linear-gradient(135deg,#00532f 0%,#006c43 48%,#004c31 100%);color:#fff}
.left:before{content:"";position:absolute;inset:0;background:radial-gradient(ellipse at 78% 60%,rgba(255,255,255,.08),transparent 42%)}
.left-content{position:relative;z-index:2;height:100%;padding:30px 7%;display:flex;flex-direction:column}
.brand{display:flex;align-items:center;gap:14px}.egg-logo{width:48px;height:62px;border-radius:52% 48% 48% 52%;background:linear-gradient(145deg,#ffd77a,#f19a3b 58%,#c86b29);transform:rotate(-5deg);box-shadow:inset -7px -6px 12px #a9522540}
.brand-name strong,.brand-name b{display:block;line-height:1.02;font-size:25px}.brand-name b{color:#ffd63f}.brand-name small{display:block;font-size:12px;margin-top:7px;opacity:.92}
.left-main{margin-top:58px;position:relative;z-index:3;max-width:650px}.eyebrow{font-size:12px;letter-spacing:2px;color:#ffd43f;font-weight:900;margin-bottom:12px}
.left-main h1{font-size:clamp(42px,4.25vw,66px);line-height:.98;letter-spacing:-2px;margin:0;font-weight:900}.left-main h1 span{color:#ffd43f}
.left-main>p{font-size:20px;margin:18px 0 25px}
.features{display:grid;gap:12px;width:440px;max-width:100%}.feature{display:flex;align-items:center;gap:13px}.feature-icon{width:50px;height:50px;display:grid;place-items:center;border-radius:12px;background:#00815a;border:1px solid #ffffff20;font-size:24px;flex:none}.feature b{display:block;font-size:16px}.feature small{display:block;font-size:12px;opacity:.82;margin-top:3px}
.chicken{position:absolute;z-index:1;right:-2%;bottom:-2%;width:min(61%,680px);max-height:67%;object-fit:contain;object-position:right bottom;filter:drop-shadow(0 22px 18px rgba(0,0,0,.2))}
.slogan{position:absolute;z-index:3;left:7%;bottom:28px;color:#ffd43f;font-size:30px;font-family:cursive;font-style:italic;line-height:1.05}.slogan span{display:block;margin-left:45px}
.pills{position:absolute;z-index:4;right:5%;bottom:25px;display:flex;gap:24px;border:1px solid #ffffff55;background:#064f36aa;padding:13px 22px;border-radius:28px;font-size:13px;backdrop-filter:blur(5px)}
.right{height:100%;display:flex;align-items:center;justify-content:center;padding:35px 7%;background:#fff}.card{width:min(570px,100%)}
.badge{display:flex;justify-content:flex-end;align-items:center;gap:10px;color:#087b50;font-size:14px;font-weight:700;margin-bottom:58px}.badge span{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:#f0faf5;font-size:20px}
.welcome{font-size:13px;letter-spacing:1.5px;font-weight:800;color:#7a89a3;margin-bottom:8px}h2{font-size:51px;line-height:1;margin:0;letter-spacing:-2px;color:#08152f}h2 span{color:#008d5b}.sub{font-size:16px;color:#72809a;margin:14px 0 34px}
.field{margin-bottom:21px}.field label{display:block;font-size:14px;font-weight:800;margin-bottom:8px}.input{height:56px;border:1px solid #d4deea;border-radius:9px;display:flex;align-items:center;background:#fff}.input:focus-within{border-color:#008f5b;box-shadow:0 0 0 3px #008f5b12}.icon{width:50px;text-align:center;font-size:18px;color:#73829a}.input input{flex:1;height:100%;border:0;outline:0;font-size:16px;background:transparent;color:#16233d;padding:0 12px 0 0}
.pass{position:relative}.pass .input{padding-right:43px}.eye{position:absolute;right:6px;top:29px;width:40px;height:42px;border:0;background:transparent;color:#73829a;cursor:pointer}
.row{display:flex;justify-content:space-between;align-items:center;margin:0 0 23px;font-size:14px}.remember{display:flex;align-items:center;gap:8px;color:#33425d}.remember input{width:19px;height:19px;margin:0;accent-color:#008f5b}.forgot{color:#008b58;text-decoration:none;font-weight:800}
.btn{width:100%;height:57px;border:0;border-radius:9px;background:#008f5b;color:#fff;font-size:17px;font-weight:800;cursor:pointer;box-shadow:0 7px 18px #008f5b22}
.divider{display:flex;align-items:center;gap:14px;color:#7d8aa0;font-size:13px;margin:27px 0 18px}.divider:before,.divider:after{content:"";height:1px;background:#e1e6ed;flex:1}.pay{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.paybox{border:1px solid #e1e8ef;border-radius:10px;text-align:center;padding:14px 7px}.paybox .ico{font-size:22px;color:#008f5b}.paybox b{display:block;font-size:14px;margin-top:6px}.paybox small{display:block;font-size:11px;color:#7b89a1;margin-top:5px}.secure{margin-top:18px;background:#ebf8f2;color:#16825a;text-align:center;border-radius:9px;padding:12px;font-size:12px;font-weight:700}.demo{text-align:center;font-size:11px;color:#8490a3;margin-top:12px}.error{background:#fff0f0;border:1px solid #edcaca;color:#a22929;border-radius:8px;padding:10px 13px;margin-bottom:16px;font-size:13px}
@media(max-width:1050px){.page{grid-template-columns:52% 48%}.left-main{margin-top:42px}.left-main h1{font-size:43px}.chicken{width:62%}.pills{font-size:11px;gap:10px;padding:10px 14px}.slogan{font-size:23px}}
@media(max-width:760px){.page{display:block;height:auto;min-height:100vh;overflow:auto}.left{height:390px}.left-content{padding:24px 7%}.left-main{margin-top:35px}.left-main h1{font-size:38px}.left-main>p,.features,.slogan,.pills{display:none}.chicken{width:58%;max-height:58%;right:0;bottom:0}.right{height:auto;min-height:calc(100vh - 390px);padding:35px 8%}.badge{margin-bottom:35px}h2{font-size:43px}}
</style>
</head>
<body>
<div class="page">
<section class="left">
<div class="left-content">
<div class="brand"><div class="egg-logo"></div><div class="brand-name"><strong>EDM KIENYEJI</strong><b>EGG SHOP</b><small>Fresh Eggs • Healthy Families • A Better Tomorrow</small></div></div>
<div class="left-main">
<div class="eyebrow">SMART EGG BUSINESS MANAGEMENT</div>
<h1>Your Complete<br>Egg Sales<br><span>Management System</span></h1>
<p>Sell smarter. Manage stock. Grow your business.</p>
<div class="features">
<div class="feature"><div class="feature-icon">🛒</div><div><b>Manage Sales</b><small>Fast, easy and reliable</small></div></div>
<div class="feature"><div class="feature-icon">📦</div><div><b>Track Stock</b><small>Real-time inventory</small></div></div>
<div class="feature"><div class="feature-icon">👥</div><div><b>Customers &amp; Suppliers</b><small>Build stronger relationships</small></div></div>
<div class="feature"><div class="feature-icon">📊</div><div><b>Reports &amp; Insights</b><small>Make better decisions</small></div></div>
<div class="feature"><div class="feature-icon">💳</div><div><b>Multiple Payments</b><small>Cash • Mobile Money • Bank</small></div></div>
</div></div>
<img class="chicken" src="assets/chicken.png" alt="Hen sitting on eggs">
<div class="slogan">Kuku Bora, Mayai Bora<span>Maisha Bora</span></div>
<div class="pills">🌿 Fresh Eggs &nbsp; • &nbsp; ♥ Healthy Families &nbsp; • &nbsp; 📊 A Better Tomorrow</div>
</div>
</section>
<section class="right"><div class="card">
<div class="badge"><span>🍃</span>Simple. Professional. Reliable.</div>
<div class="welcome">WELCOME BACK</div><h2>Sign <span>in</span></h2><p class="sub">Access your EDM Kienyeji Egg Shop dashboard.</p>
<?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post" action="login.php">
<div class="field"><label>Username</label><div class="input"><div class="icon">👤</div><input name="username" autocomplete="username" value="<?=htmlspecialchars($_POST['username']??'')?>" required autofocus></div></div>
<div class="field pass"><label>Password</label><div class="input"><div class="icon">🔒</div><input id="password" type="password" name="password" autocomplete="current-password" required></div><button class="eye" type="button" onclick="togglePassword()">◉</button></div>
<div class="row"><label class="remember"><input type="checkbox" name="remember"> Remember me</label><a class="forgot" href="#" onclick="return false">Forgot password?</a></div>
<button class="btn" type="submit">↪ &nbsp; Sign In</button></form>
<div class="divider">or continue with</div><div class="pay">
<div class="paybox"><div class="ico">▣</div><b>Cash</b><small>Simple &amp; Fast</small></div>
<div class="paybox"><div class="ico">▯</div><b>Mobile Money</b><small>Secure &amp; Convenient</small></div>
<div class="paybox"><div class="ico">▥</div><b>Bank</b><small>Safe &amp; Reliable</small></div></div>
<div class="secure">🛡 Secure • Reliable • Built for Egg Businesses</div><div class="demo">Demo administrator: <b>admin</b> / <b>admin123</b></div>
</div></section>
</div>
<script>function togglePassword(){const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';}</script>
</body></html>