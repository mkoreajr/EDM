/**
 * Customer portal: password show/hide buttons and the product search/filter.
 */
(function () {
  'use strict';

  /* Show / hide password (each button names its input in data-password-toggle) */
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

  /* Product search + category pills */
  var search = document.getElementById('productSearch');
  var cards = Array.prototype.slice.call(document.querySelectorAll('.product-card'));
  var pills = Array.prototype.slice.call(document.querySelectorAll('.category-pill'));
  var noResults = document.getElementById('noResults');
  var selected = 'all';

  function filterProducts() {
    var q = (search ? search.value : '').trim().toLowerCase();
    var shown = 0;
    cards.forEach(function (card) {
      var show = (selected === 'all' || card.getAttribute('data-category') === selected)
        && (!q || card.getAttribute('data-name').indexOf(q) !== -1);
      card.hidden = !show;
      if (show) shown++;
    });
    if (noResults) noResults.hidden = shown > 0;
  }

  function selectCategory(pill) {
    pills.forEach(function (p) { p.classList.toggle('active', p === pill); });
    selected = pill.getAttribute('data-category');
    filterProducts();
  }

  pills.forEach(function (pill) {
    pill.addEventListener('click', function () { selectCategory(pill); });
  });
  if (search) search.addEventListener('input', filterProducts);

  var showAll = document.getElementById('showAllProducts');
  if (showAll) {
    showAll.addEventListener('click', function () {
      if (search) search.value = '';
      if (pills[0]) selectCategory(pills[0]);
    });
  }

  /* Prevent double submits (e.g. placing the same order twice) */
  document.addEventListener('submit', function (e) {
    var button = e.target.querySelector('button[type="submit"]');
    if (button) setTimeout(function () { button.disabled = true; }, 0);
  });
  window.addEventListener('pageshow', function (e) {
    if (!e.persisted) return;
    document.querySelectorAll('button[type="submit"]:disabled:not([aria-disabled])').forEach(function (b) { b.disabled = false; });
  });
})();
