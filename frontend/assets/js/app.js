/**
 * Shared behaviour for every signed-in page:
 * top-bar dropdowns, responsive sidebar, confirmation prompts.
 */
(function () {
  'use strict';

  /* ---------------------------------------------- Top-bar dropdowns */
  function closeDropdowns(except) {
    document.querySelectorAll('.dropdown.open').forEach(function (el) {
      if (el !== except) el.classList.remove('open');
    });
    document.querySelectorAll('.profile-button.open').forEach(function (el) {
      el.classList.remove('open');
    });
    document.querySelectorAll('.dropdown-wrap [aria-expanded="true"]').forEach(function (el) {
      el.setAttribute('aria-expanded', 'false');
    });
  }

  function bindDropdown(buttonId, dropdownId, toggleButtonClass) {
    var button = document.getElementById(buttonId);
    var dropdown = document.getElementById(dropdownId);
    if (!button || !dropdown) return;
    button.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var open = !dropdown.classList.contains('open');
      closeDropdowns(open ? dropdown : null);
      dropdown.classList.toggle('open', open);
      if (toggleButtonClass) button.classList.toggle('open', open);
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  bindDropdown('notificationButton', 'notificationDropdown', false);
  bindDropdown('profileButton', 'profileDropdown', true);

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.dropdown-wrap')) closeDropdowns(null);
  });

  /* ---------------------------------------------- Sidebar */
  var body = document.body;
  var menuButton = document.querySelector('.menu-btn');
  var sidebar = document.querySelector('.sidebar');
  var backdrop = null;

  function isMobile() { return window.innerWidth <= 850; }

  function closeMobileSidebar() {
    body.classList.remove('sidebar-mobile-open');
    if (backdrop) backdrop.style.display = 'none';
    if (menuButton) menuButton.setAttribute('aria-expanded', 'false');
  }

  if (menuButton && sidebar) {
    backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    backdrop.style.display = 'none';
    body.appendChild(backdrop);
    backdrop.addEventListener('click', closeMobileSidebar);

    menuButton.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (isMobile()) {
        var open = body.classList.toggle('sidebar-mobile-open');
        backdrop.style.display = open ? 'block' : 'none';
        menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
      } else {
        var collapsed = body.classList.toggle('sidebar-collapsed');
        menuButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      }
    });

    window.addEventListener('resize', function () {
      if (isMobile()) {
        body.classList.remove('sidebar-collapsed');
        closeMobileSidebar();
      } else {
        body.classList.remove('sidebar-mobile-open');
        backdrop.style.display = 'none';
        menuButton.setAttribute('aria-expanded', body.classList.contains('sidebar-collapsed') ? 'false' : 'true');
      }
    });

    menuButton.setAttribute('aria-expanded', isMobile() ? 'false' : 'true');
  }

  /* ---------------------------------------------- Confirm before destructive forms */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    var question = form.getAttribute('data-confirm');
    if (question && !window.confirm(question)) {
      e.preventDefault();
    }
  });

  /* ---------------------------------------------- "Clear notifications" modal */
  var overlay = document.getElementById('clearConfirmOverlay');
  var pendingForm = null;

  function closeModal() {
    if (!overlay) return;
    overlay.classList.remove('show');
    overlay.setAttribute('aria-hidden', 'true');
    pendingForm = null;
  }

  if (overlay) {
    document.querySelectorAll('.clear-notification-form').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        pendingForm = form;
        overlay.classList.add('show');
        overlay.setAttribute('aria-hidden', 'false');
      });
    });
    document.getElementById('clearConfirmNo').addEventListener('click', closeModal);
    document.getElementById('clearConfirmYes').addEventListener('click', function () {
      if (pendingForm) pendingForm.submit();
      closeModal();
    });
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal();
    });
  }

  /* ---------------------------------------------- Escape closes everything */
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    closeDropdowns(null);
    closeModal();
    if (isMobile()) closeMobileSidebar();
  });

  /* ---------------------------------------------- Prevent double submits */
  document.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return;
    var button = e.target.querySelector('button[type="submit"], button:not([type])');
    if (button) {
      setTimeout(function () {
        button.disabled = true;
        button.setAttribute('data-auto-disabled', '');
      }, 0);
    }
  });
  // Re-enable those buttons when the page is restored with the browser's Back button.
  window.addEventListener('pageshow', function (e) {
    if (!e.persisted) return;
    document.querySelectorAll('button[data-auto-disabled]').forEach(function (b) {
      b.disabled = false;
      b.removeAttribute('data-auto-disabled');
    });
  });

  /* ---------------------------------------------- Show / hide password */
  document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
      var input = document.getElementById(button.getAttribute('data-password-toggle'));
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      button.setAttribute('aria-pressed', show ? 'true' : 'false');
      button.classList.toggle('is-visible', show);
    });
  });

  /* ---------------------------------------------- Inactivity logout
     The server enforces the same limit (ADMIN_IDLE_TIMEOUT); this just
     leaves the page promptly so nothing stays on screen. */
  var idleSeconds = parseInt(body.getAttribute('data-idle-timeout') || '0', 10);
  if (idleSeconds > 0) {
    var idleTimer = null;
    var lastPing = Date.now();
    var resetIdle = function () {
      clearTimeout(idleTimer);
      idleTimer = setTimeout(function () { window.location.replace('/logout?timeout=1'); }, idleSeconds * 1000);
      // Someone filling in a long form makes no requests; tell the server they are still here.
      if (Date.now() - lastPing > idleSeconds * 500) {
        lastPing = Date.now();
        fetch('/session/ping', { credentials: 'same-origin', cache: 'no-store', headers: { 'X-Requested-With': 'XMLHttpRequest' } }).catch(function () {});
      }
    };
    ['click', 'keydown', 'pointerdown', 'touchstart', 'scroll'].forEach(function (evt) {
      window.addEventListener(evt, resetIdle, { passive: true });
    });
    resetIdle();
  }
})();
