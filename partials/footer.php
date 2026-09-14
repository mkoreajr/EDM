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

<script>
(function(){
  var toggle=document.querySelector('.menu-toggle');
  var sidebar=document.querySelector('.sidebar');
  if(!toggle || !sidebar) return;
  var backdrop;
  toggle.addEventListener('click',function(e){
    e.preventDefault(); e.stopPropagation();
    var open=sidebar.classList.toggle('mobile-open');
    if(open){
      backdrop=document.createElement('div');
      backdrop.className='sidebar-backdrop';
      document.body.appendChild(backdrop);
      backdrop.addEventListener('click',function(){
        sidebar.classList.remove('mobile-open');
        if(backdrop) backdrop.remove();
      });
    }else if(backdrop){backdrop.remove();backdrop=null;}
  });
  window.addEventListener('resize',function(){
    if(window.innerWidth>850){
      sidebar.classList.remove('mobile-open');
      if(backdrop){backdrop.remove();backdrop=null;}
    }
  });
})();
</script>

</body></html>
