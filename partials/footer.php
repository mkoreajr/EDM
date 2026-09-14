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


<div class="clear-confirm-overlay" id="clearConfirmOverlay" aria-hidden="true">
  <div class="clear-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="clearConfirmTitle">
    <div class="clear-confirm-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none">
        <path d="M12 3.5 21 20H3L12 3.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
        <path d="M12 9v5M12 17.2v.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      </svg>
    </div>
    <h3 id="clearConfirmTitle">Are you sure you want to delete the notifications?</h3>
    <p>This action will remove all notifications, including unread notifications. This cannot be undone.</p>
    <div class="clear-confirm-buttons">
      <button type="button" class="clear-confirm-no" id="clearConfirmNo">No</button>
      <button type="button" class="clear-confirm-yes" id="clearConfirmYes">Yes, Delete</button>
    </div>
  </div>
</div>
<script>
(function(){
  var overlay=document.getElementById('clearConfirmOverlay');
  var yes=document.getElementById('clearConfirmYes');
  var no=document.getElementById('clearConfirmNo');
  var activeForm=null;
  document.querySelectorAll('.clear-notification-form').forEach(function(form){
    form.addEventListener('submit',function(e){
      e.preventDefault();
      activeForm=form;
      overlay.classList.add('show');
      overlay.setAttribute('aria-hidden','false');
    });
  });
  function closeModal(){
    overlay.classList.remove('show');
    overlay.setAttribute('aria-hidden','true');
    activeForm=null;
  }
  if(no) no.addEventListener('click',closeModal);
  if(yes) yes.addEventListener('click',function(){
    if(activeForm) activeForm.submit();
    closeModal();
  });
  if(overlay) overlay.addEventListener('click',function(e){
    if(e.target===overlay) closeModal();
  });
  document.addEventListener('keydown',function(e){
    if(e.key==='Escape' && overlay && overlay.classList.contains('show')) closeModal();
  });
})();
</script>

</body></html>
