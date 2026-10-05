<?php
session_start();
require_once __DIR__ . '/config/database.php';
if(isset($_SESSION['user_id'])){header('Location: dashboard.php');exit;}

$customSlides=[];
$firstSlideSrc='';
try{
  // Fetch only lightweight slide metadata on login. Image bytes are served by the
  // dedicated cached endpoint so refreshes do not embed a large base64 image in HTML.
  $sr=$conn->query("SELECT id,filename,mime_type,sort_order FROM login_slides ORDER BY sort_order ASC,id ASC");
  while($row=$sr->fetch_assoc()){
    $customSlides[]=$row;
  }
  if($customSlides){
    $firstSlideSrc='login_slide_image.php?id='.(int)$customSlides[0]['id'];
  }
}catch(Throwable $e){ $customSlides=[]; $firstSlideSrc=''; }

$error=$_SESSION['login_error']??'';unset($_SESSION['login_error']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MSINDA Food Shop — Login</title>
<?php if($firstSlideSrc!==''): ?><link rel="preload" as="image" href="<?=htmlspecialchars($firstSlideSrc,ENT_QUOTES,'UTF-8')?>" fetchpriority="high"><?php endif; ?>
<style>
*{box-sizing:border-box}
html,body{margin:0;width:100%;min-height:100%;font-family:Arial,Helvetica,sans-serif;background:#eef3f1;color:#17352b}
body{overflow:auto}
.login-page{min-height:100vh;width:100%;display:flex;align-items:center;justify-content:center;padding:34px 24px 26px;position:relative;overflow:hidden;background:linear-gradient(135deg,#f4f7f5 0%,#e8efec 100%)}
.login-page:before{content:"";position:absolute;width:520px;height:520px;border-radius:50%;background:rgba(0,117,75,.06);left:-180px;top:-220px}
.login-page:after{content:"";position:absolute;width:430px;height:430px;border-radius:50%;background:rgba(0,117,75,.045);right:-180px;bottom:-210px}
.glow{display:none}
.center-divider{display:none}
.left{position:relative;width:min(920px,92vw);height:560px;display:flex;overflow:hidden;border-radius:18px 0 0 18px;background:linear-gradient(145deg,#086f49,#034d35);z-index:2;box-shadow:0 24px 60px rgba(18,58,45,.18)}
.login-page>.right-panel{position:relative;width:min(460px,46vw);height:560px;background:#fff;border-radius:0 18px 18px 0;display:flex;flex-direction:column;justify-content:center;align-items:center;padding:38px 48px;z-index:2;box-shadow:0 24px 60px rgba(18,58,45,.18)}
/* Left promotional/slideshow side */
.left-slogan{position:absolute!important;z-index:6!important;top:22px!important;left:24px!important;width:min(310px,42%)!important;height:auto!important;pointer-events:none}
.left-slogan img{width:100%;height:auto;display:block;object-fit:contain}
.login-slideshow{position:absolute;inset:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;overflow:hidden;background:linear-gradient(145deg,#08734a,#045236)!important}
.login-slide{position:absolute;left:50%;top:51%;width:80%;max-width:680px;aspect-ratio:3/2;height:auto;transform:translate(-50%,-50%);opacity:0;transition:opacity .7s ease;z-index:1;overflow:hidden;border-radius:14px;background:transparent;border:1px solid rgba(255,255,255,.22);box-shadow:0 18px 40px rgba(0,0,0,.22);will-change:opacity}
.login-slide.active{opacity:1;z-index:2}
.login-slide.is-ready{visibility:visible}
.login-slideshow{isolation:isolate}
.login-slide img{width:100%;height:100%;display:block;object-fit:cover;object-position:center;background:transparent}
.login-slide-overlay{position:absolute;inset:0;z-index:2;pointer-events:none;background:linear-gradient(180deg,rgba(0,58,38,.08),rgba(0,48,31,.18))}
.slide-dots{position:absolute;z-index:7;left:50%;bottom:14px;transform:translateX(-50%);display:flex;align-items:center;gap:8px;padding:0;background:transparent;border:0;box-shadow:none}
.slide-dots .dot{display:block;width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.9);border:1px solid rgba(0,0,0,.12);box-shadow:0 1px 3px rgba(0,0,0,.18);transition:.25s}.slide-dots .dot.active{width:22px;border-radius:8px;background:#ffd447}
.left-secure{position:absolute!important;z-index:7!important;left:30px!important;bottom:8px!important;transform:none!important;color:#fff!important;font-size:13px!important;font-weight:700!important;text-shadow:0 1px 2px rgba(0,0,0,.2);white-space:nowrap}
.left-secure svg{width:15px;height:15px;vertical-align:middle;margin-right:5px;fill:none;stroke:#ffd447;stroke-width:2}
/* Right login side */
.login-card{position:relative;z-index:3;width:100%;padding:0;background:transparent;border:0;box-shadow:none;backdrop-filter:none;-webkit-backdrop-filter:none}
.logo-wrap{height:94px;width:240px;margin:0 auto 17px;display:flex;align-items:center;justify-content:center}
.logo-wrap img{width:100%;height:100%;object-fit:contain;display:block}
.welcome{font-size:12px;letter-spacing:1.1px;font-weight:800;color:#60746c;margin:0 0 7px;text-transform:none}
.login-title{font-size:26px;line-height:1;font-weight:800;margin:0 0 8px;color:#163b2f;letter-spacing:-.3px}
.sub{font-size:12px;color:#74857f;margin:0 0 22px;line-height:1.45}
.error{background:#fff0f0;border:1px solid #f1b5b5;color:#b33b3b;border-radius:8px;padding:9px 11px;margin:0 0 14px;font-size:11px}
.field{margin:0 0 14px}.field label{display:block;font-size:11px;font-weight:500;color:#566961;margin:0 0 6px}
.input{height:43px;display:flex;align-items:center;border-radius:8px;background:#fff;border:1px solid #d5dfdb;overflow:hidden;transition:.2s}
.input:focus-within{border-color:#15945f;box-shadow:0 0 0 3px rgba(21,148,95,.08)}
.input-icon{width:40px;height:100%;display:grid;place-items:center;flex:none}.input-icon svg{width:16px;height:16px;fill:none;stroke:#8b9b95;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.input input{width:100%;height:100%;border:0;outline:0;background:#fff;color:#233b32;font-family:Arial,Helvetica,sans-serif;font-size:13px;padding:0 10px 0 0}
.input input::placeholder{color:#a3aea9}
.input input:-webkit-autofill,.input input:-webkit-autofill:hover,.input input:-webkit-autofill:focus,.input input:-webkit-autofill:active{-webkit-text-fill-color:#233b32!important;caret-color:#233b32!important;-webkit-box-shadow:0 0 0 1000px #fff inset!important;box-shadow:0 0 0 1000px #fff inset!important}
.pass-wrap{position:relative}.pass-wrap .input{padding-right:40px}
.eye{position:absolute;right:5px;top:22px;width:34px;height:34px;border:0;background:transparent;color:#8b9b95;display:flex;align-items:center;justify-content:center;padding:0;cursor:pointer}.eye svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.forgot-row{display:flex;justify-content:flex-end;margin:-1px 0 17px}.forgot{color:#157d57;text-decoration:none;font-size:11px;font-weight:700}
.btn{width:100%;height:45px;border:0;border-radius:8px;background:#159b62;color:#fff;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 8px 18px rgba(21,155,98,.18);transition:.2s}.btn:hover{background:#12a568}
.secure{display:none}
.right-footer{position:absolute;z-index:3;left:50%;bottom:-69px;transform:translateX(-50%);width:100%;text-align:center;color:#71847c}.support{text-align:center;color:#75867f;font-size:13px;line-height:1.5;margin:0}.support a{color:#157d57;text-decoration:none}.footer{text-align:center;color:#87958f;font-size:12px;margin-top:7px}
@media(max-width:920px) and (min-width:761px){.left{width:52vw;height:520px}.login-page>.right-panel{width:42vw;height:520px;padding:32px 34px}.login-slide{width:88%}.left-slogan{width:45%;left:18px}}
@media(max-width:760px){
 html,body{background:#eef3f1}.login-page{display:flex!important;min-height:100vh;height:auto;padding:24px 14px 70px;align-items:center;justify-content:center;background:linear-gradient(145deg,#edf4f1,#f7f9f8)}
 .left{display:none!important}
 .login-page>.right-panel{width:min(430px,100%);height:auto;min-height:0;border-radius:16px;padding:28px 24px 26px;box-shadow:0 18px 45px rgba(18,58,45,.14)}
 .logo-wrap{height:88px;width:225px;margin-bottom:14px}.login-title{font-size:25px}.sub{margin-bottom:20px}.right-footer{position:relative;left:auto;bottom:auto;transform:none;width:100%;margin-top:16px}.support{font-size:13px}.footer{font-size:12px}
}
</style>
</head>
<body>
<div class="login-page">
  <div class="center-divider" aria-hidden="true"></div>
  <div class="glow"></div>
  <section class="left" aria-label="EDM products">
    <div class="login-slideshow">
      <?php if($customSlides): ?>
        <?php foreach($customSlides as $i=>$slide): ?>
          <div class="login-slide <?= $i===0 ? 'active' : '' ?>"><img <?= $i===0 && $firstSlideSrc!=='' ? 'src="'.htmlspecialchars($firstSlideSrc,ENT_QUOTES,'UTF-8').'" fetchpriority="high"' : 'data-src="login_slide_image.php?id='.(int)$slide['id'].'"' ?> alt="<?=htmlspecialchars((string)$slide['filename'],ENT_QUOTES,'UTF-8')?>" decoding="async" loading="eager"></div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="login-slide active"><img src="assets/login-slides/edm-rice-unga-1.webp" alt="EDM Rice and Unga products" fetchpriority="high" decoding="async"></div>
        <div class="login-slide"><img data-src="assets/login-slides/edm-rice-unga-2.webp" alt="EDM Rice and Unga products" decoding="async"></div>
        <div class="login-slide"><img data-src="assets/login-slides/edm-juice.webp" alt="EDM Juice products" decoding="async"></div>
      <?php endif; ?>
      <div class="login-slide-overlay"></div>
      <?php $slideCount=count($customSlides ?: [1,2,3]); ?>
      <div class="slide-dots" aria-hidden="true"><?php for($i=0;$i<$slideCount;$i++): ?><span class="dot <?= $i===0 ? 'active' : '' ?>"></span><?php endfor; ?></div>
      <div class="left-secure"><svg viewBox="0 0 24 24"><path d="M12 3 20 6v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>Secure • Reliable • Built for Food Businesses</div>
    </div>
  </section>
  <section class="right-panel">
  <main class="login-card">
    <div class="logo-wrap"><img src="assets/branding/msinda-agricultural-emblem.png" alt="MSINDA Food Shop" width="240" height="94" decoding="async" fetchpriority="high"></div>
    <div class="welcome">WELCOME BACK</div>
    <h1 class="login-title">LOGIN</h1>
    <p class="sub">Access your MSINDA Food Shop dashboard.</p>
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
    <div class="support">Need help? Contact <a href="mailto:ict@psrs.go.tz">ICT Support</a> : +225 682 657 202</div>
    <div class="footer">© 2026 MSINDA Food Shop | All Rights Reserved.</div>
  </div>
  </section>
</div>
<script>
const slides=[...document.querySelectorAll('.login-slide')];
const dots=[...document.querySelectorAll('.slide-dots .dot')];
let currentSlide=0;
let switching=false;

// Preload an image and wait until it is actually decoded before showing it.
// This prevents the green slideshow background from flashing between slides.
const prepareSlide=(index)=>new Promise((resolve,reject)=>{
  const img=slides[index]?.querySelector('img');
  if(!img) return reject();
  if(!img.src && img.dataset.src){
    img.src=img.dataset.src;
    img.removeAttribute('data-src');
  }
  const done=async()=>{
    try{ if(img.decode) await img.decode(); }catch(e){}
    slides[index].classList.add('is-ready');
    resolve();
  };
  if(img.complete && img.naturalWidth>0){ done(); return; }
  img.addEventListener('load',done,{once:true});
  img.addEventListener('error',()=>reject(),{once:true});
});

const showSlide=(next)=>{
  if(next===currentSlide || switching) return;
  switching=true;
  prepareSlide(next).then(()=>{
    const previous=currentSlide;
    slides[next].classList.add('active');
    if(dots[previous]) dots[previous].classList.remove('active');
    if(dots[next]) dots[next].classList.add('active');
    currentSlide=next;
    window.setTimeout(()=>{
      slides[previous].classList.remove('active');
      switching=false;
      prepareSlide((currentSlide+1)%slides.length).catch(()=>{});
    },700);
  }).catch(()=>{ switching=false; });
};

// Keep the first slide active immediately. The browser can paint it as soon as
// the cached image arrives; we no longer hide the whole slide while decode runs.
prepareSlide(0).then(()=>{
  slides[0].classList.add('active');
  if(dots[0]) dots[0].classList.add('active');
  prepareSlide(1).catch(()=>{});
}).catch(()=>{
  slides[0]?.classList.add('active');
});

if(slides.length>1){
  setInterval(()=>showSlide((currentSlide+1)%slides.length),5000);
}

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
