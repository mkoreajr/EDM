</main><script>
// Global admin dropdowns: Administrator and Notifications are real clickable controls.
(function(){
  const profileBtn=document.getElementById('profileButton');
  const profileMenu=document.getElementById('profileDropdown');
  const notifyBtn=document.getElementById('notificationButton');
  const notifyMenu=document.getElementById('notificationDropdown');
  const closeMenus=()=>{
    if(profileBtn&&profileMenu){profileBtn.classList.remove('open');profileBtn.setAttribute('aria-expanded','false');profileMenu.classList.remove('open');}
    if(notifyBtn&&notifyMenu){notifyBtn.classList.remove('open');notifyBtn.setAttribute('aria-expanded','false');notifyMenu.classList.remove('open');}
  };
  if(profileBtn&&profileMenu){
    profileBtn.addEventListener('click',function(e){
      e.stopPropagation();
      const open=!profileMenu.classList.contains('open');
      closeMenus();
      if(open){profileMenu.classList.add('open');profileBtn.classList.add('open');profileBtn.setAttribute('aria-expanded','true');}
    });
  }
  if(notifyBtn&&notifyMenu){
    notifyBtn.addEventListener('click',function(e){
      e.stopPropagation();
      const open=!notifyMenu.classList.contains('open');
      closeMenus();
      if(open){notifyMenu.classList.add('open');notifyBtn.classList.add('open');notifyBtn.setAttribute('aria-expanded','true');}
    });
    notifyMenu.querySelectorAll('.notification-item.unread').forEach(item=>{
      item.addEventListener('click',function(){
        const badge=document.getElementById('notificationCount');
        if(badge){
          const current=parseInt(badge.textContent||'0',10)||0;
          if(current<=1){badge.remove();}else{badge.textContent=current-1;}
        }
      });
    });
  }
  document.addEventListener('click',function(){closeMenus();});
  document.addEventListener('keydown',function(e){if(e.key==='Escape')closeMenus();});
  document.querySelectorAll('[data-confirm]').forEach(el=>el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm))e.preventDefault()}));
})();
</script></body></html>