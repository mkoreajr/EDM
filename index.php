<?php
session_start();
require_once __DIR__ . '/config/database.php';
if(isset($_SESSION['user_id'])){header('Location: dashboard.php');exit;}

$customSlides=[];
try{
  $sr=$conn->query("SELECT id,filename,mime_type,image_data FROM login_slides ORDER BY sort_order ASC,id ASC");
  while($row=$sr->fetch_assoc()){ $customSlides[]=$row; }
}catch(Throwable $e){ $customSlides=[]; }

$error=$_SESSION['login_error']??'';unset($_SESSION['login_error']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>EDM Kienyeji Shop — Login</title>
<style>
*{box-sizing:border-box}
html,body{margin:0;width:100%;height:100%;font-family:Arial,Helvetica,sans-serif;background:#07573b;color:#fff}
body{overflow:hidden}
.login-page{
  width:100vw;height:100vh;min-height:620px;position:relative;overflow:hidden;
  display:grid;grid-template-columns:50% 50%;
  background:
    radial-gradient(circle at 12% 18%,rgba(47,190,113,.18) 0 90px,transparent 300px),
    radial-gradient(circle at 92% 82%,rgba(64,177,116,.13) 0 150px,transparent 330px),
    linear-gradient(135deg,#075d3e 0%,#07543a 48%,#06442f 100%);
}
/* Left-side EDM product showcase */
.left{position:relative;height:100%;overflow:hidden;background:transparent!important;border-right:1px solid rgba(255,255,255,.14);}
.left-slogan{position:absolute;z-index:5;top:calc(50% - 330px);left:calc(50% - min(40%,342px));width:min(320px,36%);height:auto;display:block;pointer-events:none;}
.left-slogan img{display:block;width:100%;height:auto;object-fit:contain;}
.login-slideshow{position:absolute;inset:0;width:100%;height:100%;overflow:hidden;display:flex;align-items:center;justify-content:center;background:transparent!important;}
.login-slide{position:absolute;left:50%;top:50%;width:min(76%,620px);aspect-ratio:3/2;height:auto;transform:translate(-50%,-50%);opacity:0;transition:opacity 900ms ease-in-out;z-index:1;overflow:hidden;border-radius:20px;box-shadow:0 18px 45px rgba(0,0,0,.30);border:1px solid rgba(255,255,255,.20);background:transparent;}
.login-slide.active{opacity:1}
.login-slide img{width:100%;height:100%;display:block;object-fit:contain;object-position:center center;background:transparent;}
.login-slide-overlay{position:absolute;inset:0;z-index:2;pointer-events:none;background:radial-gradient(circle at center,rgba(0,0,0,0) 0%,rgba(0,45,28,.05) 100%)}
.slide-dots{position:absolute;z-index:3;left:50%;bottom:112px;transform:translateX(-50%);display:flex;gap:8px;padding:7px 10px;border-radius:20px;background:rgba(0,0,0,.28);backdrop-filter:blur(5px)}
.slide-dots .dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.55);transition:all .25s ease}.slide-dots .dot.active{width:22px;border-radius:10px;background:#ffd43f}
.slide-more{position:absolute;z-index:4;left:50%;bottom:82px;transform:translateX(-50%);padding:11px 23px;border-radius:24px;border:1px solid rgba(255,255,255,.16);background:#ffd34e;color:#173b29;font-size:12px;font-weight:900;text-decoration:none;box-shadow:0 8px 20px rgba(0,0,0,.18)}
/* subtle reference-style decorative circles */
.login-page:before,.login-page:after{content:"";position:absolute;border:1px solid rgba(255,255,255,.08);border-radius:50%;pointer-events:none}
.login-page:before{width:165px;height:165px;right:5%;top:7%}
.login-page:after{width:230px;height:230px;left:-100px;bottom:-115px;background:rgba(35,170,102,.08);border:0}
.glow{position:absolute;width:360px;height:360px;border-radius:50%;left:-150px;top:-160px;background:rgba(42,196,117,.08);filter:blur(8px);pointer-events:none}
.right-panel{position:relative;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:34px 7%;}
.login-card{
  position:relative;z-index:2;width:min(420px,calc(100vw - 36px));
  padding:27px 30px 22px;border-radius:21px;
  background:rgba(255,255,255,.085);
  border:1px solid rgba(255,255,255,.24);
  box-shadow:0 22px 55px rgba(0,0,0,.18);
  backdrop-filter:blur(15px);-webkit-backdrop-filter:blur(15px);
}
.logo-wrap{height:105px;width:230px;margin:0 auto 5px;display:flex;align-items:center;justify-content:center}
.logo-wrap img{width:100%;height:100%;object-fit:contain;display:block}
.welcome{font-size:12px;letter-spacing:1.5px;font-weight:800;color:#d9eee4;margin:0 0 8px}
.login-title{font-size:25px;line-height:1;font-weight:800;margin:0 0 9px;color:#fff;letter-spacing:-.4px}
.sub{font-size:12px;color:#c7ddd3;margin:0 0 22px;line-height:1.45}
.error{background:rgba(255,235,235,.11);border:1px solid rgba(255,175,175,.4);color:#ffd5d5;border-radius:9px;padding:9px 11px;margin:0 0 14px;font-size:11px}
.field{margin:0 0 15px}
.field label{display:block;font-size:11px;font-weight:500;color:#e5f2ec;margin:0 0 6px}
.input{height:45px;display:flex;align-items:center;border-radius:10px;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.34);overflow:hidden;transition:.2s}
.input:focus-within{border-color:#79dbaa;box-shadow:0 0 0 3px rgba(121,219,170,.11)}
.input-icon{width:42px;height:100%;display:grid;place-items:center;flex:none}
.input-icon svg{width:17px;height:17px;fill:none;stroke:#b9d4c8;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.input input{width:100%;height:100%;border:0;outline:0;background:transparent;color:#fff;font-family:Arial,Helvetica,sans-serif;font-size:13px;padding:0 10px 0 0}
.input input::placeholder{color:rgba(231,245,238,.52)}
/* Keep username/password fields visually identical to the login card, including browser autofill */
.input input:-webkit-autofill,.input input:-webkit-autofill:hover,.input input:-webkit-autofill:focus,.input input:-webkit-autofill:active{
  -webkit-text-fill-color:#fff!important;
  caret-color:#fff!important;
  -webkit-box-shadow:0 0 0 1000px rgba(255,255,255,.035) inset!important;
  box-shadow:0 0 0 1000px rgba(255,255,255,.035) inset!important;
  -webkit-background-clip:padding-box!important;
  background-clip:padding-box!important;
  transition:background-color 99999s ease-in-out 0s!important;
}
.pass-wrap{position:relative}
.pass-wrap .input{padding-right:40px}
.eye{position:absolute;right:5px;top:24px;width:34px;height:34px;border:0;background:transparent;color:#b9d4c8;display:flex;align-items:center;justify-content:center;padding:0;cursor:pointer}
.eye svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.forgot-row{display:flex;justify-content:flex-end;margin:-1px 0 17px}
.forgot{color:#bdebd3;text-decoration:none;font-size:11px;font-weight:700}
.btn{width:100%;height:46px;border:0;border-radius:10px;background:#32be76;color:#fff;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 8px 22px rgba(0,0,0,.16);transition:.2s}
.btn:hover{background:#3ac980}
.secure{margin-top:15px;padding:9px 8px;text-align:center;border-radius:9px;background:rgba(48,190,117,.08);border:1px solid rgba(255,255,255,.11);color:#c5e9d7;font-size:9px;font-weight:700}
.secure svg{width:13px;height:13px;vertical-align:middle;margin-right:4px;fill:none;stroke:#75d9a5;stroke-width:2}
.left-secure{position:absolute;z-index:4;left:50%;bottom:18px;transform:translateX(-50%);white-space:nowrap;color:#c5e9d7;font-size:13px;font-weight:700;text-align:center;text-shadow:0 1px 2px rgba(0,0,0,.15)}.left-secure svg{width:16px;height:16px;vertical-align:middle;margin-right:5px;fill:none;stroke:#75d9a5;stroke-width:2}
.right-footer{position:relative;z-index:2;width:min(420px,calc(100vw - 36px));text-align:center;margin-top:12px}.support{text-align:center;color:#9fc5b4;font-size:11px;line-height:1.5;margin:0}.support a{color:#bfe9d6;text-decoration:none}.footer{text-align:center;color:#87b4a2;font-size:10px;margin-top:8px}
@media(max-width:760px){
 body{overflow:auto}
 .login-page{display:block!important;min-height:100vh;height:auto;padding:0}
 /* On phones, hide the entire promotional left side; show only the login side. */
 .left{display:none!important}
 .right-panel{width:100%;min-height:100vh;padding:28px 8%;justify-content:center}
 .login-card{width:min(410px,calc(100vw - 28px));padding:24px 23px 20px}
 .logo-wrap{height:92px;width:205px}
 .right-footer{width:min(410px,calc(100vw - 28px));margin-top:12px}
}

/* Final reference layout tuning */
@media(min-width:761px){
  .left-slogan{top:calc(50% - 330px)!important;left:calc(50% - min(40%,342px))!important;width:min(320px,36%)!important;}
  .login-slide{width:min(80%,684px)!important;}
  .slide-dots{bottom:112px!important;}
  .slide-more{bottom:66px!important;}
  .left-secure{bottom:18px!important;}
  .right-panel{padding-left:6.5%!important;padding-right:6.5%!important;}
  .login-card{width:min(412px,calc(100vw - 36px))!important;}
  .right-footer{width:min(412px,calc(100vw - 36px))!important;}
}

/* Final requested layout: slogan sits above the slideshow, separate and not overlapping */
@media(min-width:761px){
  .left-slogan{
    position:absolute!important;
    z-index:6!important;
    top:18px!important;
    left:24px!important;
    width:min(360px,34%)!important;
    height:auto!important;
    transform:none!important;
  }
  .left-slogan img{width:100%!important;height:auto!important;display:block!important;object-fit:contain!important;}
  .login-slideshow{z-index:1!important;}
  .login-slide{z-index:1!important;}
}

/* Rich green decorative background — CSS only, no background photos */
.login-page{background:
  radial-gradient(circle at 16% 16%,rgba(43,205,119,.22) 0 70px,transparent 260px),
  radial-gradient(circle at 86% 18%,rgba(31,161,101,.20) 0 90px,transparent 250px),
  radial-gradient(circle at 50% 100%,rgba(35,188,112,.12) 0 130px,transparent 360px),
  linear-gradient(135deg,#075e40 0%,#07563b 45%,#06462f 100%)!important;}
.login-page:before{width:210px!important;height:210px!important;right:3%!important;top:5%!important;background:rgba(56,190,120,.07);box-shadow:inset 0 0 0 1px rgba(255,255,255,.05),0 0 0 34px rgba(56,190,120,.025)!important;}
.login-page:after{width:360px!important;height:360px!important;left:-170px!important;bottom:-205px!important;background:rgba(34,171,101,.10)!important;border:1px solid rgba(100,226,157,.06)!important;box-shadow:inset 0 0 0 45px rgba(34,171,101,.045),inset 0 0 0 90px rgba(34,171,101,.025)!important;}
.left{background:
  radial-gradient(circle at 4% 52%,rgba(90,222,143,.10) 0 2px,transparent 3px),
  radial-gradient(circle at 8% 55%,rgba(90,222,143,.10) 0 2px,transparent 3px),
  radial-gradient(circle at 12% 48%,rgba(90,222,143,.08) 0 2px,transparent 3px),
  transparent!important;}
.left:before{content:"";position:absolute;z-index:0;left:-80px;bottom:-150px;width:620px;height:300px;border-radius:50% 50% 0 0/100% 100% 0 0;background:linear-gradient(160deg,rgba(31,170,100,.18),rgba(9,102,67,.02));transform:rotate(-8deg);pointer-events:none;}
.left:after{content:"";position:absolute;z-index:0;left:-60px;bottom:-185px;width:650px;height:245px;border-radius:50%;border-top:5px solid rgba(61,201,125,.22);border-right:0;border-left:0;border-bottom:0;transform:rotate(-8deg);pointer-events:none;}
.right-panel:before{content:"";position:absolute;inset:0;z-index:0;pointer-events:none;background:
  radial-gradient(circle at 86% 74%,rgba(80,202,133,.10) 0 4px,transparent 5px),
  radial-gradient(circle at 91% 78%,rgba(80,202,133,.08) 0 3px,transparent 4px),
  radial-gradient(circle at 78% 82%,rgba(80,202,133,.08) 0 3px,transparent 4px);
  opacity:.9;}
.right-panel:after{content:"";position:absolute;z-index:0;right:-170px;bottom:-175px;width:520px;height:250px;border-radius:50%;background:linear-gradient(160deg,rgba(37,177,105,.12),rgba(7,80,52,.01));transform:rotate(-7deg);pointer-events:none;}
.left-slogan,.login-slideshow,.left-secure,.right-panel>*{z-index:2;}
.left-secure{font-size:14px!important;}
@media(max-width:760px){
  .right-panel:after{right:-220px;bottom:-180px;width:430px;height:220px;}
}

</style>
</head>
<body>
<div class="login-page">
  <div class="glow"></div>
  <section class="left" aria-label="EDM products">
    <div class="left-slogan" aria-label="Chakula Bora kwa Familia Yako"><img src="assets/branding/edm-slogan.png" alt="Chakula Bora kwa Familia Yako"></div>
    <div class="login-slideshow">
      <?php if($customSlides): ?>
        <?php foreach($customSlides as $i=>$slide): ?>
          <div class="login-slide <?= $i===0 ? 'active' : '' ?>"><img src="<?=htmlspecialchars((string)$slide['image_data'],ENT_QUOTES,'UTF-8')?>" alt="<?=htmlspecialchars((string)$slide['filename'],ENT_QUOTES,'UTF-8')?>"></div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="login-slide active"><img src="assets/login-slides/edm-rice-unga-1.png" alt="EDM Rice and Unga products"></div>
        <div class="login-slide"><img src="assets/login-slides/edm-rice-unga-2.png" alt="EDM Rice and Unga products"></div>
        <div class="login-slide"><img src="assets/login-slides/edm-juice.png" alt="EDM Juice products"></div>
      <?php endif; ?>
      <div class="login-slide-overlay"></div>
      <?php $slideCount=count($customSlides ?: [1,2,3]); ?>
      <div class="slide-dots" aria-hidden="true"><?php for($i=0;$i<$slideCount;$i++): ?><span class="dot <?= $i===0 ? 'active' : '' ?>"></span><?php endfor; ?></div>
      <div class="left-secure"><svg viewBox="0 0 24 24"><path d="M12 3 20 6v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>Secure • Reliable • Built for Egg Businesses</div>
    </div>
  </section>
  <section class="right-panel">
  <main class="login-card">
    <div class="logo-wrap"><img src="assets/branding/edm-rice-unga-logo.png" alt="EDM Rice & Unga"></div>
    <div class="welcome">WELCOME BACK</div>
    <h1 class="login-title">LOGIN</h1>
    <p class="sub">Access your EDM Kienyeji Shop dashboard.</p>
    <?php if($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <form method="post" action="login.php">
      <div class="field">
        <label for="username">Username</label>
        <div class="input">
          <span class="input-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.8-3.2 3.2-5 7-5s6.2 1.8 7 5"/></svg></span>
          <input id="username" type="text" name="username" autocomplete="username" placeholder="Enter your username" value="<?=htmlspecialchars($_POST['username']??'')?>" required autofocus>
        </div>
      </div>
      <div class="field pass-wrap">
        <label for="password">Password</label>
        <div class="input">
          <span class="input-icon"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
          <input id="password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
        </div>
        <button class="eye" type="button" onclick="togglePassword()" aria-label="Show password"><svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg></button>
      </div>
      <div class="forgot-row"><a class="forgot" href="admin-recovery.php">Forgot password?</a></div>
      <button class="btn" type="submit">LOGIN</button>
    </form>
  </main>
  <div class="right-footer">
    <div class="support">For any Technical inquiry, Please contact your Support Team at : <a href="mailto:ictsupport@gmail.com">ictsupport@gmail.com</a></div>
    <div class="footer">© 2025 - 2026 | EDM Kienyeji Shop | v1.1.0</div>
  </div>
  </section>
</div>
<script>
const slides=[...document.querySelectorAll('.login-slide')];
const dots=[...document.querySelectorAll('.slide-dots .dot')];
let currentSlide=0;
if(slides.length>1){setInterval(()=>{slides[currentSlide].classList.remove('active');dots[currentSlide].classList.remove('active');currentSlide=(currentSlide+1)%slides.length;slides[currentSlide].classList.add('active');dots[currentSlide].classList.add('active');},5000);}

function togglePassword(){const p=document.getElementById('password');const b=document.querySelector('.eye');p.type=p.type==='password'?'text':'password';b.setAttribute('aria-label',p.type==='password'?'Show password':'Hide password');}

// Remember username only. Password is intentionally never stored.
(function(){
  const u=document.getElementById('username');
  if(!u) return;
  try{
    const saved=localStorage.getItem('edm_login_username');
    if(!u.value && saved) u.value=saved;
    u.addEventListener('input',()=>{
      try{localStorage.setItem('edm_login_username',u.value);}catch(e){}
    });
    const form=u.closest('form');
    if(form) form.addEventListener('submit',()=>{
      try{localStorage.setItem('edm_login_username',u.value);}catch(e){}
    });
  }catch(e){}
})();
</script>
</body>
</html>
