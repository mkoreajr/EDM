/**
 * Login page: slideshow with lazy-loaded slides, password visibility toggle,
 * remembered username (the password is never stored).
 */
(function () {
  'use strict';

  /* Slideshow */
  var slides = Array.prototype.slice.call(document.querySelectorAll('.login-slide'));
  var dots = Array.prototype.slice.call(document.querySelectorAll('.slide-dots .dot'));
  var current = 0;

  function loadSlide(index) {
    var img = slides[index] && slides[index].querySelector('img[data-src]');
    if (img) {
      img.src = img.getAttribute('data-src');
      img.removeAttribute('data-src');
    }
  }

  /* Resolves once the slide's image is downloaded and decoded, so switching never shows a blank frame. */
  function slideReady(index) {
    loadSlide(index);
    var img = slides[index] && slides[index].querySelector('img');
    if (!img) return Promise.reject();
    var loaded = (img.complete && img.naturalWidth > 0)
      ? Promise.resolve()
      : new Promise(function (resolve, reject) {
          img.addEventListener('load', resolve, { once: true });
          img.addEventListener('error', reject, { once: true });
        });
    return loaded.then(function () { return img.decode ? img.decode().catch(function () {}) : null; });
  }

  var switching = false;
  function showNext() {
    if (switching || document.hidden) return;
    var next = (current + 1) % slides.length;
    switching = true;
    slideReady(next).then(function () {
      var previous = current;
      slides[next].classList.add('active');
      if (dots[previous]) dots[previous].classList.remove('active');
      if (dots[next]) dots[next].classList.add('active');
      current = next;
      // Fade the new slide in over the old one, then hide the old one.
      setTimeout(function () {
        slides[previous].classList.remove('active');
        switching = false;
        slideReady((current + 1) % slides.length).catch(function () {});
      }, 700);
    }).catch(function () { switching = false; });
  }

  if (slides.length > 1) {
    slideReady(1).catch(function () {});
    setInterval(showNext, 5000);
  }

  /* Show / hide password */
  var toggle = document.getElementById('togglePassword');
  var password = document.getElementById('password');
  if (toggle && password) {
    toggle.addEventListener('click', function () {
      var hidden = password.type === 'password';
      password.type = hidden ? 'text' : 'password';
      toggle.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
    });
  }

  /* Remember username only */
  var username = document.getElementById('username');
  if (username) {
    try {
      var saved = localStorage.getItem('edm_login_username');
      if (!username.value && saved) username.value = saved;
      if (username.value && password) password.focus();
      var remember = function () {
        try { localStorage.setItem('edm_login_username', username.value); } catch (e) { /* storage blocked */ }
      };
      username.addEventListener('input', remember);
      if (username.form) username.form.addEventListener('submit', remember);
    } catch (e) { /* storage blocked */ }
  }
})();
