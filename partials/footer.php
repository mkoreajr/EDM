<footer class="app-footer">
  <div class="footer-content">
    <div>© 2024 EDM Kienyeji Egg Shop. All rights reserved.</div>
    <div class="footer-tagline">“Fresh Eggs • Healthy Families • A Better Tomorrow”</div>
  </div>
</footer>
<script>
(function(){
  function closeAll(except){
    document.querySelectorAll('.dropdown.open').forEach(function(el){
      if(el!==except) el.classList.remove('open');
    });
    document.querySelectorAll('.profile-button.open').forEach(function(el){
      if(!except || el!==except) el.classList.remove('open');
    });
    document.querySelectorAll('[aria-expanded="true"]').forEach(function(el){
      if(!except || el!==except) el.setAttribute('aria-expanded','false');
    });
  }

  var notificationButton=document.getElementById('notificationButton');
  var notificationDropdown=document.getElementById('notificationDropdown');
  if(notificationButton && notificationDropdown){
    notificationButton.addEventListener('click',function(e){
      e.preventDefault(); e.stopPropagation();
      var open=!notificationDropdown.classList.contains('open');
      closeAll(open ? notificationDropdown : null);
      notificationDropdown.classList.toggle('open',open);
      notificationButton.setAttribute('aria-expanded',open?'true':'false');
    });
  }

  var profileButton=document.getElementById('profileButton');
  var profileDropdown=document.getElementById('profileDropdown');
  if(profileButton && profileDropdown){
    profileButton.addEventListener('click',function(e){
      e.preventDefault(); e.stopPropagation();
      var open=!profileDropdown.classList.contains('open');
      closeAll(open ? profileDropdown : null);
      profileDropdown.classList.toggle('open',open);
      profileButton.classList.toggle('open',open);
      profileButton.setAttribute('aria-expanded',open?'true':'false');
    });
  }

  document.addEventListener('click',function(e){
    if(!e.target.closest('.dropdown-wrap')) closeAll(null);
  });

  document.addEventListener('keydown',function(e){
    if(e.key==='Escape') closeAll(null);
  });

  // Clicking an unread notification marks it read through the existing server endpoint.
  document.querySelectorAll('.notification-item.unread').forEach(function(item){
    item.addEventListener('click',function(){
      item.classList.remove('unread');
    });
  });
})();
</script>
</body></html>
