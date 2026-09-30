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


/* Login left-side product slideshow */
.left{position:relative!important;overflow:hidden!important;background:#073d2a!important}
.login-slideshow{position:absolute;inset:0;width:100%;height:100%;overflow:hidden;background:linear-gradient(145deg,#00532f 0%,#007248 50%,#005b38 100%);display:flex;align-items:center;justify-content:center}
.login-slide{position:absolute;left:50%;top:50%;width:min(76%,620px);aspect-ratio:3/2;height:auto;transform:translate(-50%,-50%);opacity:0;transition:opacity 900ms ease-in-out;z-index:1;overflow:hidden;border-radius:20px;box-shadow:0 18px 45px rgba(0,0,0,.30);border:1px solid rgba(255,255,255,.20);background:#fff}
.login-slide.active{opacity:1}
.login-slide img{width:100%;height:100%;display:block;object-fit:contain;object-position:center center;background:#fff}
.login-slide-overlay{position:absolute;inset:0;z-index:2;pointer-events:none;background:radial-gradient(circle at center,rgba(0,0,0,0) 0%,rgba(0,45,28,.06) 100%)}
.slide-dots{position:absolute;z-index:3;left:50%;bottom:72px;transform:translateX(-50%);display:flex;gap:8px;padding:7px 10px;border-radius:20px;background:rgba(0,0,0,.28);backdrop-filter:blur(5px)}
.slide-dots .dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.55);transition:all .25s ease}
.slide-dots .dot.active{width:22px;border-radius:10px;background:#ffd43f}
@media(max-width:1100px){
  .login-slide{width:min(78%,520px);height:auto;aspect-ratio:3/2}
  .slide-dots{bottom:54px}
}
@media(max-width:760px){
  .left{height:410px!important}
  .login-slide{width:78%;height:auto;aspect-ratio:3/2;border-radius:16px}
  .login-slide img{object-position:center center}
  .slide-dots{bottom:18px}
}

</style>

<style id="login-reference-v51">
/* V51: Match supplied reference — unified green canvas + compact glass login card */
html,body{background:#064f36!important;color:#fff!important}
.page{grid-template-columns:50% 50%!important;background:
  radial-gradient(circle at 13% 12%,rgba(41,190,112,.20) 0 120px,transparent 330px),
  radial-gradient(circle at 86% 84%,rgba(45,170,105,.12) 0 160px,transparent 340px),
  linear-gradient(135deg,#075b3c 0%,#064f36 50%,#073f2d 100%)!important}
.left,.right{background:transparent!important}
.left{border-right:1px solid rgba(255,255,255,.14)!important}
.right{border-left:0!important;padding:32px 7%!important}
.right .login-card{width:min(470px,100%)!important;padding:30px 30px 24px!important;border:1px solid rgba(255,255,255,.22)!important;border-radius:22px!important;background:rgba(255,255,255,.075)!important;box-shadow:0 18px 45px rgba(0,0,0,.12)!important;backdrop-filter:blur(12px)!important;color:#fff!important}
.edm-mark{font-weight:1000;font-style:italic;font-size:56px;line-height:.8;letter-spacing:-5px;color:#fff;text-align:center;text-shadow:3px 3px 0 #064f36,-1px -1px 0 #d8c42b,1px -1px 0 #d8c42b,-1px 1px 0 #d8c42b,1px 1px 0 #d8c42b;margin:0 auto 22px}
.right .welcome{color:#d9eee3!important;letter-spacing:1.4px;font-size:12px;margin:0 0 8px!important}
.right .sub{color:#c4ddd1!important;font-size:14px;margin:0 0 22px!important}
.user-profile{height:72px;display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:14px;background:rgba(255,255,255,.075);border:1px solid rgba(255,255,255,.13);margin-bottom:20px}
.avatar{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;flex:none;background:#24b873;color:#fff;font-weight:900;font-size:13px;box-shadow:0 0 0 3px rgba(255,255,255,.05)}
.user-profile-main{min-width:0;flex:1;display:flex;flex-direction:column;gap:2px}
.profile-kicker{font-size:10px;color:#b7d8c9}
.username-inline{width:100%;padding:0;border:0;outline:0;background:transparent;color:#fff;font-weight:700;font-size:14px;font-family:Arial,Helvetica,sans-serif}
.username-inline::placeholder{color:#e0eee8;opacity:.9}
.profile-role{font-size:10px;color:#a7cbbb}
.profile-edit{width:31px;height:31px;border-radius:8px;display:grid;place-items:center;background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.12);color:#d8ebe2;font-size:16px}
.right .field{margin-bottom:15px!important}
.right .field label{color:#e6f3ed!important;font-size:12px!important;font-weight:600!important;margin-bottom:7px!important}
.right .input{height:49px!important;border-radius:11px!important;background:rgba(255,255,255,.035)!important;border:1px solid rgba(255,255,255,.34)!important}
.right .input input{font-size:14px!important;color:#fff!important}
.right .icon{width:45px!important}
.right .eye{top:24px!important;height:37px!important}
.right .row{margin:0 0 18px!important;font-size:12px!important}
.right .forgot{color:#d4f2e2!important;font-weight:700!important}
.right .btn{height:49px!important;border-radius:11px!important;background:#2fbd75!important;font-size:15px!important;box-shadow:0 8px 22px rgba(0,0,0,.15)!important}
.right .secure{margin-top:16px!important;padding:10px!important;background:rgba(44,190,117,.08)!important;color:#c5ead7!important;border:1px solid rgba(255,255,255,.10)!important;font-size:10px!important}
.support{text-align:center;color:#9fc3b2;font-size:9px;line-height:1.5;margin-top:12px}
.right .error{background:rgba(255,235,235,.10)!important;border-color:rgba(255,170,170,.35)!important;color:#ffd3d3!important}
@media(max-width:760px){
 .page{display:block!important;background:#064f36!important}
 .left{height:410px!important;border-right:0!important;border-bottom:1px solid rgba(255,255,255,.14)!important}
 .right{min-height:calc(100vh - 410px)!important;padding:26px 8%!important}
 .right .login-card{width:100%!important;padding:26px 22px 22px!important}
}
</style>
</head>
<body>
<div class="page">
<section class="left">
<div class="left-slogan" aria-label="EDM slogan"><span class="slogan-leaf">⌁</span><div><strong>Chakula Bora</strong><b>kwa Familia Yako</b></div></div>
<div class="login-slideshow" aria-label="EDM products">
  <div class="login-slide active"><img src="assets/login-slides/edm-rice-unga-1.png" alt="EDM Rice and Unga products"></div>
  <div class="login-slide"><img src="assets/login-slides/edm-rice-unga-2.png" alt="EDM Rice and Unga products"></div>
  <div class="login-slide"><img src="assets/login-slides/edm-juice.png" alt="EDM Juice products"></div>
  <div class="login-slide-overlay"></div>
  <div class="slide-dots" aria-hidden="true">
    <span class="dot active"></span><span class="dot"></span><span class="dot"></span>
  </div>
</div>
</section>


<section class="right">
  <div class="card login-card">
    <div class="edm-mark" aria-label="EDM">EDM</div>
    <div class="welcome">WELCOME BACK</div>
    <p class="sub">Access your EDM Kienyeji Egg Shop dashboard.</p>
    <?php if($error):?><div class="error"><?=htmlspecialchars($error)?></div><?php endif;?>

    <form method="post" action="login.php">
      <div class="user-profile">
        <div class="avatar">EDM</div>
        <div class="user-profile-main">
          <span class="profile-kicker">EDM Kienyeji Shop</span>
          <input class="username-inline" name="username" autocomplete="username" placeholder="Enter username" value="<?=htmlspecialchars($_POST['username']??'')?>" required autofocus aria-label="Username">
          <span class="profile-role">Authorized user</span>
        </div>
        <span class="profile-edit" aria-hidden="true">✎</span>
      </div>

      <div class="field pass"><label>Password</label><div class="input"><div class="icon"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></div><input id="password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required></div><button class="eye" type="button" onclick="togglePassword()" aria-label="Show password"><svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div>
      <div class="row login-row"><span></span><a class="forgot" href="admin-recovery.php">Forgot password?</a></div>
      <button class="btn" type="submit">LOGIN</button>
    </form>

    <div class="secure"><svg viewBox="0 0 24 24"><path d="M12 3 20 6v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>Secure • Reliable • Built for Egg Businesses</div>
    <div class="support">For any account or technical inquiry, please contact your EDM support team.</div>
  </div>
</section>
</div>
<script>
function togglePassword(){const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';}
(function(){
  const slides=[...document.querySelectorAll('.login-slide')];
  const dots=[...document.querySelectorAll('.slide-dots .dot')];
  if(slides.length<2)return;
  let current=0;
  setInterval(function(){
    slides[current].classList.remove('active');
    dots[current]?.classList.remove('active');
    current=(current+1)%slides.length;
    slides[current].classList.add('active');
    dots[current]?.classList.add('active');
  },5000);
})();
</script>
</body></html><style id="login-right-green-v49">
/* V49: Green glass login panel inspired by the supplied reference */
.page{background:#064f36!important}
.right{
  position:relative!important;
  overflow:hidden!important;
  background:
    radial-gradient(circle at 86% 16%,rgba(106,190,139,.20) 0 90px,transparent 91px),
    radial-gradient(circle at 94% 88%,rgba(117,196,151,.16) 0 150px,transparent 151px),
    linear-gradient(145deg,#075a3b 0%,#064a32 55%,#073f2d 100%)!important;
  color:#fff!important;
  border-left:1px solid rgba(255,255,255,.16)!important;
}
.right:before,.right:after{content:"";position:absolute;border:1px solid rgba(255,255,255,.08);border-radius:50%;pointer-events:none}
.right:before{width:150px;height:150px;left:-72px;bottom:-60px}
.right:after{width:90px;height:90px;right:10%;top:10%;}
.right .card{position:relative;z-index:2;width:min(520px,100%)!important}
.right .badge{justify-content:flex-start!important;margin:0 0 42px!important;color:#d9f4e5!important}
.right .badge span{width:42px;height:42px;background:rgba(235,255,245,.10)!important;border:1px solid rgba(255,255,255,.08)!important}
.right .badge svg{stroke:#72d7a3!important}
.right .welcome{color:#b8d7c7!important}
.right h2{color:#fff!important}
.right h2 span{color:#46d28d!important}
.right .sub{color:#c4d9cf!important}
.right .field label{color:#e7f4ee!important}
.right .input{background:rgba(255,255,255,.045)!important;border:1px solid rgba(255,255,255,.34)!important;border-radius:12px!important;box-shadow:none!important}
.right .input:focus-within{border-color:#78dbaa!important;box-shadow:0 0 0 3px rgba(120,219,170,.12)!important}
.right .input input{color:#fff!important}
.right .input input::placeholder{color:rgba(232,246,239,.52)!important}
.right .icon svg{stroke:#b8d3c7!important}
.right .eye{color:#b8d3c7!important}
.right .remember{color:#d2e5dc!important}
.right .remember input{accent-color:#42c98a!important}
.right .forgot{color:#8de1b5!important}
.right .btn{background:#35b96f!important;color:#fff!important;border-radius:11px!important;box-shadow:0 9px 24px rgba(0,0,0,.16)!important}
.right .btn:hover{background:#3bc77a!important}
.right .secure{background:rgba(66,201,138,.10)!important;color:#b9ead0!important;border:1px solid rgba(255,255,255,.08)!important}
.right .secure svg{stroke:#70d9a4!important}
.right .demo{color:#8eb8a6!important}
.right .error{background:rgba(255,235,235,.10)!important;border-color:rgba(255,170,170,.35)!important;color:#ffd3d3!important}
@media(max-width:760px){
  .right{border-left:0!important;border-top:1px solid rgba(255,255,255,.16)!important}
  .right .card{width:100%!important}
}
</style>

<style id="login-edm-brand-v50">
/* V50: EDM retail brand slogan + unified green background */
.left{
  background:
    radial-gradient(circle at 12% 12%,rgba(38,190,111,.24) 0 120px,transparent 300px),
    radial-gradient(circle at 82% 86%,rgba(45,170,105,.12) 0 160px,transparent 330px),
    linear-gradient(145deg,#075a3b 0%,#064f36 52%,#073f2d 100%)!important;
}
.left-slogan{
  position:absolute;
  z-index:6;
  top:30px;
  left:5.5%;
  display:flex;
  align-items:center;
  gap:10px;
  padding:10px 18px 12px 12px;
  border-radius:8px 8px 28px 8px;
  background:linear-gradient(145deg,#0a6a3e 0%,#075431 70%);
  border:2px solid #d9c92c;
  box-shadow:0 10px 25px rgba(0,0,0,.22);
  transform:rotate(-3deg);
  color:#fff;
}
.left-slogan:after{
  content:"";
  position:absolute;
  left:32px;
  right:20px;
  bottom:-5px;
  height:3px;
  border-radius:50%;
  background:#f3d52b;
  transform:rotate(-2deg);
}
.left-slogan .slogan-leaf{
  width:36px;
  height:36px;
  display:grid;
  place-items:center;
  border-radius:50%;
  color:#8ee19c;
  font-size:30px;
  font-weight:900;
  transform:rotate(-28deg);
}
.left-slogan strong,.left-slogan b{
  display:block;
  font-family:cursive;
  font-style:italic;
  line-height:.95;
  white-space:nowrap;
}
.left-slogan strong{font-size:25px;color:#fff}
.left-slogan b{font-size:23px;color:#ffe22d;margin-top:5px}
.login-slideshow{background:transparent!important}
@media(max-width:1100px){
  .left-slogan{top:22px;left:5%;padding:8px 14px 10px 9px}
  .left-slogan strong{font-size:20px}
  .left-slogan b{font-size:18px}
}
@media(max-width:760px){
  .left-slogan{top:18px;left:6%;transform:rotate(-2deg) scale(.86);transform-origin:left top}
}
</style>
