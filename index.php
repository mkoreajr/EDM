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
html,body{margin:0;width:100%;height:100%;font-family:Arial, Helvetica, sans-serif;background:#fff;color:#0b1732}
.page{width:100vw;height:100vh;min-height:680px;display:grid;grid-template-columns:58% 42%;overflow:hidden}
.left{position:relative;height:100%;overflow:hidden;background:linear-gradient(145deg,#00532f 0%,#007248 50%,#005b38 100%);color:#fff}
.left:before,.left:after{content:"";position:absolute;border-radius:50%;pointer-events:none}
.left:before{width:520px;height:520px;right:-180px;top:-230px;background:rgba(62,169,101,.15)}
.left:after{width:650px;height:650px;left:-390px;bottom:-410px;background:rgba(52,164,98,.13)}
.left-content{position:relative;z-index:2;height:100%;padding:30px 7%;display:flex;flex-direction:column}
.brand{display:flex;align-items:center;gap:14px}
.egg-logo{width:48px;height:62px;border-radius:52% 48% 48% 52%;background:linear-gradient(145deg,#ffd77a,#f19a3b 58%,#c86b29);transform:rotate(-5deg);box-shadow:inset -7px -6px 12px #a9522540}
.brand-name strong,.brand-name b{display:block;line-height:1.02;font-size:25px}.brand-name b{color:#ffd63f}.brand-name small{display:block;font-size:12px;margin-top:7px;opacity:.92}
.quality{position:absolute;right:7%;top:28px;text-align:center;color:#fff}.quality strong{display:block;font-family:cursive;font-size:31px;font-weight:500}.quality b{display:block;color:#ffd43f;font-family:cursive;font-size:30px;font-weight:500}.quality i{display:block;width:150px;height:4px;background:#ffd43f;border-radius:50%;transform:rotate(-7deg);margin:3px auto}
.left-main{margin-top:58px;max-width:700px}
.eyebrow{font-size:12px;letter-spacing:2px;color:#ffd43f;font-weight:900;margin-bottom:12px}
.left-main h1{font-size:clamp(42px,4.15vw,65px);line-height:.98;letter-spacing:-2px;margin:0;font-weight:900}.left-main h1 span{color:#ffd43f}
.left-main>p{font-size:20px;margin:18px 0 25px}
.features{display:grid;grid-template-columns:1fr 1fr;gap:12px;width:100%;max-width:760px}.feature{display:flex;align-items:center;gap:13px;padding:11px 14px;border:1px solid rgba(255,255,255,.17);background:rgba(0,111,70,.46);border-radius:12px}.feature-icon{width:47px;height:47px;display:grid;place-items:center;border-radius:11px;background:#00815a;border:1px solid #ffffff20;flex:none}.feature-icon svg{width:25px;height:25px;fill:none;stroke:#fff;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.feature b{display:block;font-size:15px}.feature small{display:block;font-size:11px;opacity:.82;margin-top:3px}
.slogan{position:absolute;left:7%;bottom:29px;color:#ffd43f;font-size:28px;font-family:cursive;font-style:italic;line-height:1.05}.slogan span{display:block;margin-left:45px}.pills{position:absolute;right:5%;bottom:27px;display:flex;gap:22px;border:1px solid #ffffff45;background:#064f36aa;padding:13px 21px;border-radius:28px;font-size:12px;backdrop-filter:blur(5px)}
.right{height:100%;display:flex;align-items:center;justify-content:center;padding:34px 7%;background:#fff}.card{width:min(590px,100%)}
.badge{display:flex;justify-content:flex-end;align-items:center;gap:10px;color:#087b50;font-size:14px;font-weight:700;margin-bottom:52px}.badge span{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:#f0faf5}.badge svg{width:22px;height:22px;fill:none;stroke:#087b50;stroke-width:2}
.welcome{font-size:13px;letter-spacing:1.5px;font-weight:800;color:#7a89a3;margin-bottom:8px}h2{font-size:51px;line-height:1;margin:0;letter-spacing:-2px;color:#08152f}h2 span{color:#008d5b}.sub{font-size:16px;color:#72809a;margin:14px 0 34px}
.field{margin-bottom:21px}.field label{display:block;font-size:14px;font-weight:500;margin-bottom:8px}.input{height:56px;border:1px solid #d4deea;border-radius:9px;display:flex;align-items:center;background:#fff}.input:focus-within{border-color:#008f5b;box-shadow:0 0 0 3px #008f5b12}.icon{width:50px;text-align:center}.icon svg{width:19px;height:19px;fill:none;stroke:#6f809a;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.input input{flex:1;height:100%;border:0;outline:0;font-size:16px;background:transparent;color:#16233d;padding:0 12px 0 0}
.pass{position:relative}.pass .input{padding-right:43px}.eye{position:absolute;right:6px;top:29px;width:40px;height:42px;border:0;background:transparent;color:#73829a;cursor:pointer}.eye svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:2}
.row{display:flex;justify-content:space-between;align-items:center;margin:0 0 23px;font-size:14px}.remember{display:flex;align-items:center;gap:8px;color:#33425d}.remember input{width:19px;height:19px;margin:0;accent-color:#008f5b}.forgot{color:#008b58;text-decoration:none;font-weight:800}
.btn{width:100%;height:57px;border:0;border-radius:9px;background:#008f5b;color:#fff;font-size:17px;font-weight:800;cursor:pointer;box-shadow:0 7px 18px #008f5b22}.btn svg{width:20px;height:20px;vertical-align:middle;fill:none;stroke:#fff;stroke-width:2}
.divider{display:flex;align-items:center;gap:14px;color:#7d8aa0;font-size:13px;margin:27px 0 18px}.divider:before,.divider:after{content:"";height:1px;background:#e1e6ed;flex:1}
.pay{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.paybox{border:1px solid #e1e8ef;border-radius:10px;text-align:center;padding:13px 7px;min-height:104px;display:flex;flex-direction:column;align-items:center;justify-content:center}.paybox .ico{width:43px;height:43px;border-radius:50%;display:grid;place-items:center;margin-bottom:5px}.paybox .ico svg{width:23px;height:23px;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.cash .ico{background:#e9f8ef}.cash svg{stroke:#0a9b5e}.mobile .ico{background:#eaf3ff}.mobile svg{stroke:#1877ed}.bank .ico{background:#f4eaff}.bank svg{stroke:#7d31cf}.paybox b{font-size:14px}.paybox small{font-size:11px;color:#7b89a1;margin-top:4px}
.secure{margin-top:18px;background:#ebf8f2;color:#16825a;text-align:center;border-radius:9px;padding:12px;font-size:12px;font-weight:700}.secure svg{width:15px;height:15px;vertical-align:middle;fill:none;stroke:#16825a;stroke-width:2;margin-right:4px}.demo{text-align:center;font-size:11px;color:#8490a3;margin-top:12px}.error{background:#fff0f0;border:1px solid #edcaca;color:#a22929;border-radius:8px;padding:10px 13px;margin-bottom:16px;font-size:13px}
@media(max-width:1100px){.page{grid-template-columns:53% 47%}.quality{display:none}.left-main{margin-top:45px}.left-main h1{font-size:43px}.features{grid-template-columns:1fr}.pills{font-size:10px;gap:9px;padding:10px 13px}.slogan{font-size:23px}}
@media(max-width:760px){.page{display:block;height:auto;min-height:100vh;overflow:auto}.left{height:410px}.left-content{padding:24px 7%}.left-main{margin-top:35px}.left-main h1{font-size:37px}.left-main>p,.features,.slogan,.pills{display:none}.right{height:auto;min-height:calc(100vh - 410px);padding:35px 8%}.badge{margin-bottom:35px}h2{font-size:43px}.pay{gap:8px}}

/* FINAL LOGIN SIZE FIX: equal-width panels */
.page{
  grid-template-columns:50% 50%!important;
}
.left,
.right{
  width:100%!important;
  min-width:0!important;
}
.right{
  padding-left:7%!important;
  padding-right:7%!important;
}

/* Username and Password labels: not bold */
.field label{
  font-weight:500!important;
}

/* Password eye: centered vertically with the password input */
.pass .eye{
  top:30px!important;
  height:42px!important;
  width:40px!important;
  display:flex!important;
  align-items:center!important;
  justify-content:center!important;
  padding:0!important;
  transform:none!important;
}
.pass .eye svg{
  width:20px!important;
  height:20px!important;
  display:block!important;
}

@media(max-width:760px){
  .page{
    display:block!important;
    height:auto!important;
    min-height:100vh!important;
    overflow:auto!important;
  }
  .left,.right{
    width:100%!important;
  }
}

</style>
</head>
<body>
<div class="page">
<section class="left">
<div class="left-content">
<div class="brand"><div class="egg-logo"></div><div class="brand-name"><strong>EDM KIENYEJI</strong><b>EGG SHOP</b><small>Fresh Eggs • Healthy Families • A Better Tomorrow</small></div></div>
<div class="quality"><strong>Quality Eggs</strong><b>Brighter Lives</b><i></i></div>
<div class="left-main">
<div class="eyebrow">SMART EGG BUSINESS MANAGEMENT</div>
<h1>Your Complete<br>Egg Sales<br><span>Management System</span></h1>
<p>Sell smarter. Manage stock. Grow your business.</p>
<div class="features">
<div class="feature"><div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M3 4h2l2 11h11l2-8H6"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg></div><div><b>Manage Sales</b><small>Fast, easy and reliable</small></div></div>
<div class="feature"><div class="feature-icon"><svg viewBox="0 0 24 24"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/></svg></div><div><b>Track Stock</b><small>Real-time inventory</small></div></div>
<div class="feature"><div class="feature-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c0-3 2.5-5 6-5s6 2 6 5M15 15c3 0 5 2 5 5"/></svg></div><div><b>Customers &amp; Suppliers</b><small>Build stronger relationships</small></div></div>
<div class="feature"><div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></div><div><b>Reports &amp; Insights</b><small>Make better decisions</small></div></div>
<div class="feature"><div class="feature-icon"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/></svg></div><div><b>Multiple Payments</b><small>Cash • Mobile Money • Bank</small></div></div>
</div></div>
<div class="slogan">Kuku Bora, Mayai Bora<span>Maisha Bora</span></div>
<div class="pills">Fresh Eggs &nbsp; • &nbsp; Healthy Families &nbsp; • &nbsp; A Better Tomorrow</div>
</div>
</section>

<section class="right"><div class="card">
<div class="badge"><span><svg viewBox="0 0 24 24"><path d="M5 19c7-1 12-5 14-14-8 0-13 4-14 9 0 2 0 3 0 5Z"/></svg></span>Simple. Professional. Reliable.</div>
<div class="welcome">WELCOME BACK</div><h2>Sign <span>in</span></h2><p class="sub">Access your EDM Kienyeji Egg Shop dashboard.</p>
<?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post" action="login.php">
<div class="field"><label>Username</label><div class="input"><div class="icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M5 20c0-4 3-6 7-6s7 2 7 6"/></svg></div><input name="username" autocomplete="username" placeholder="Enter your username" value="<?=htmlspecialchars($_POST['username']??'')?>" required autofocus></div></div>
<div class="field pass"><label>Password</label><div class="input"><div class="icon"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></div><input id="password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required></div><button class="eye" type="button" onclick="togglePassword()" aria-label="Show password"><svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div>
<div class="row"><label class="remember"><input type="checkbox" name="remember"> Remember me</label><a class="forgot" href="#" onclick="return false">Forgot password?</a></div>
<button class="btn" type="submit"><svg viewBox="0 0 24 24"><path d="M12 5v14M7 12l5-5 5 5"/></svg> Sign In</button>
<div style="text-align:right;margin-top:10px"><a href="admin-recovery.php" style="color:#007b52;text-decoration:none;font-size:13px">Forgot admin password?</a></div>
</form>
<div class="divider">or continue with</div>
<div class="pay">
<div class="paybox cash"><div class="ico"><svg viewBox="0 0 24 24"><rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M5 9h1M18 15h1"/></svg></div><b>Cash</b><small>Simple &amp; Fast</small></div>
<div class="paybox mobile"><div class="ico"><svg viewBox="0 0 24 24"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M10 18.5h4"/></svg></div><b>Mobile Money</b><small>Secure &amp; Convenient</small></div>
<div class="paybox bank"><div class="ico"><svg viewBox="0 0 24 24"><path d="M3 9h18L12 4 3 9Z"/><path d="M5 10v7M9 10v7M15 10v7M19 10v7M3 20h18"/></svg></div><b>Bank</b><small>Safe &amp; Reliable</small></div>
</div>
<div class="secure"><svg viewBox="0 0 24 24"><path d="M12 3 20 6v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>Secure • Reliable • Built for Egg Businesses</div>
<div class="demo">Demo administrator: <b>admin</b> / <b>admin123</b></div>
</div></section>
</div>
<script>function togglePassword(){const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';}</script>
</body></html>