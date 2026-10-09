/**
 * Admin Online Orders: View / Confirm panels, full-screen details on
 * tablet & mobile, cancellation-reason dialog, and status updates saved
 * without reloading the page.
 */
(function () {
  'use strict';

  var page = document.querySelector('.online-orders-page');
  if (!page) return;

  var transitions = JSON.parse(page.getAttribute('data-transitions') || '{}');
  var mobileQuery = window.matchMedia('(max-width: 1024px)');
  var statusClass = function (s) { return s.toLowerCase().replace(/\s+/g, '-'); };
  var isFinal = function (s) { return s === 'Delivered' || s === 'Cancelled'; };

  /* ------------------------------------------------ View / Confirm panels */
  function closePanels() {
    document.querySelectorAll('.order-view-row.is-open, .order-confirm-row.is-open').forEach(function (el) { el.classList.remove('is-open'); });
    document.querySelectorAll('.order-row.is-selected').forEach(function (el) { el.classList.remove('is-selected'); });
  }

  function togglePanel(rowId, id, onOpen) {
    var row = document.getElementById(rowId);
    var main = document.getElementById('order-row-' + id);
    if (!row || !main) return;
    var wasOpen = row.classList.contains('is-open');
    closePanels();
    if (wasOpen) return;
    row.classList.add('is-open');
    main.classList.add('is-selected');
    onOpen(row);
  }

  function viewOrder(id) {
    togglePanel('order-view-' + id, id, function (row) {
      if (mobileQuery.matches) {
        openMobileDetail(row);
      } else {
        setTimeout(function () { row.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 30);
      }
    });
  }

  function confirmOrder(id) {
    togglePanel('order-details-' + id, id, function (row) {
      setTimeout(function () { row.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 30);
      var input = row.querySelector('input[name="delivery_person"]');
      if (input) setTimeout(function () { input.focus({ preventScroll: true }); }, 220);
    });
  }

  /* Tablet / mobile: show the order details as their own screen. */
  var screen = document.getElementById('mobileOrderDetailScreen');

  function openMobileDetail(row) {
    var panel = row.querySelector('.order-view-panel');
    if (!screen || !panel) return;
    screen.innerHTML = panel.outerHTML;
    screen.classList.add('is-open');
    screen.setAttribute('aria-hidden', 'false');
    document.body.classList.add('mobile-order-detail-open');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function closeMobileDetail() {
    if (screen) {
      screen.classList.remove('is-open');
      screen.setAttribute('aria-hidden', 'true');
      screen.innerHTML = '';
    }
    document.body.classList.remove('mobile-order-detail-open');
    closePanels();
  }

  document.addEventListener('click', function (e) {
    var view = e.target.closest('[data-view-order]');
    if (view) return viewOrder(view.getAttribute('data-view-order'));
    var confirm = e.target.closest('[data-confirm-order]');
    if (confirm) return confirmOrder(confirm.getAttribute('data-confirm-order'));
    if (e.target.closest('[data-close-detail]')) closeMobileDetail();
  });

  /* ------------------------------------------------ Cancellation dialog */
  var modal = document.getElementById('cancel-modal');
  var reasonSelect = document.getElementById('cancel-reason-select');
  var reasonOther = document.getElementById('cancel-reason-custom');
  var otherWrap = document.getElementById('cancel-other-wrap');
  var modalError = document.getElementById('cancel-modal-error');
  var cancelForm = null;

  function field(form, name) { return form.querySelector('[name="' + name + '"]'); }

  function openCancelModal(form) {
    cancelForm = form;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    reasonSelect.value = field(form, 'cancellation_reason').value || '';
    reasonOther.value = field(form, 'cancellation_reason_custom').value || '';
    otherWrap.classList.toggle('is-visible', reasonSelect.value === 'Other');
    modalError.textContent = '';
    setTimeout(function () { reasonSelect.focus(); }, 50);
  }

  function hideModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
  }

  /* Closing without confirming puts the status back to what it was. */
  function abandonCancel() {
    var form = cancelForm;
    hideModal();
    cancelForm = null;
    if (!form) return;
    var row = document.getElementById('order-row-' + form.getAttribute('data-order-id'));
    var select = form.querySelector('[data-status-select]');
    if (row && select) select.value = row.getAttribute('data-status');
    field(form, 'cancellation_reason').value = '';
    field(form, 'cancellation_reason_custom').value = '';
  }

  if (modal) {
    modal.querySelectorAll('[data-cancel-close]').forEach(function (el) { el.addEventListener('click', abandonCancel); });
    reasonSelect.addEventListener('change', function () {
      otherWrap.classList.toggle('is-visible', reasonSelect.value === 'Other');
      modalError.textContent = '';
    });
    document.getElementById('cancel-confirm-btn').addEventListener('click', function () {
      if (!cancelForm) return;
      var reason = reasonSelect.value.trim();
      var other = reasonOther.value.trim();
      if (!reason) { modalError.textContent = 'Please choose a cancellation reason.'; return; }
      if (reason === 'Other' && !other) { modalError.textContent = 'Please specify the reason.'; reasonOther.focus(); return; }
      var form = cancelForm;
      field(form, 'cancellation_reason').value = reason;
      field(form, 'cancellation_reason_custom').value = other;
      hideModal();
      cancelForm = null;
      form.requestSubmit();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.classList.contains('is-open')) abandonCancel();
    });
  }

  document.querySelectorAll('[data-status-select]').forEach(function (select) {
    select.addEventListener('change', function () {
      var form = select.form;
      var row = document.getElementById('order-row-' + form.getAttribute('data-order-id'));
      if (select.value === 'Cancelled' && row && row.getAttribute('data-status') !== 'Cancelled') openCancelModal(form);
    });
  });

  /* ------------------------------------------------ Saving a status change */
  function setLoading(form, on) {
    var button = form.querySelector('.order-save-btn');
    if (!button) return;
    button.disabled = on;
    button.classList.toggle('is-loading', on);
    button.querySelector('.save-label').textContent = on ? 'Saving…' : 'Save changes';
  }

  function showMessage(form, text, ok) {
    var box = form.querySelector('.order-save-message');
    if (!box) return;
    box.textContent = text;
    box.className = 'order-save-message ' + (ok ? 'success' : 'error');
    clearTimeout(box._timer);
    if (text) box._timer = setTimeout(function () { box.textContent = ''; box.className = 'order-save-message'; }, 5000);
  }

  function bump(el, delta) {
    if (!el) return;
    var n = parseInt(el.textContent.replace(/[^0-9]/g, ''), 10) || 0;
    el.textContent = Math.max(0, n + delta).toLocaleString();
  }

  function updateCounts(oldStatus, newStatus) {
    if (oldStatus === newStatus) return;
    [[oldStatus, -1], [newStatus, 1]].forEach(function (pair) {
      var pill = page.querySelector('.order-filter-pill[data-filter="' + pair[0] + '"] b');
      var card = page.querySelector('[data-summary="' + pair[0] + '"] strong');
      bump(pill, pair[1]);
      bump(card, pair[1]);
    });
  }

  function updateRow(id, data, form) {
    var row = document.getElementById('order-row-' + id);
    var view = document.getElementById('order-view-' + id);
    var details = document.getElementById('order-details-' + id);

    if (row) {
      row.setAttribute('data-status', data.status);
      var chip = row.querySelector('[data-status-chip]');
      if (chip) { chip.className = 'table-status ' + statusClass(data.status); chip.textContent = data.status; }
      var actions = row.querySelector('.table-actions');
      var confirmButton = actions && actions.querySelector('[data-confirm-order]');
      if (isFinal(data.status) && confirmButton) {
        confirmButton.remove();
        if (details) details.classList.remove('is-open');
      }
      row.classList.add('just-updated');
      setTimeout(function () { row.classList.remove('just-updated'); }, 1300);
    }

    var label = details && details.querySelector('[data-current-label]');
    if (label) label.textContent = 'Current: ' + data.status;

    if (view) {
      view.querySelectorAll('[data-view-status], [data-view-delivery-status]').forEach(function (el) { el.textContent = data.status; });
      var note = view.querySelector('[data-view-admin-note]');
      if (note) note.textContent = field(form, 'admin_note').value.trim() || 'No admin note';
      var person = view.querySelector('[data-view-delivery-person]');
      if (person) person.textContent = field(form, 'delivery_person').value.trim() || '—';
      if (data.status === 'Cancelled' && data.cancellation_reason) {
        var reason = view.querySelector('[data-view-cancellation-reason]');
        if (!reason) {
          var wrap = document.createElement('div');
          wrap.innerHTML = '<dt>Cancellation Reason</dt><dd data-view-cancellation-reason></dd>';
          view.querySelector('[data-notes-list]').appendChild(wrap);
          reason = wrap.querySelector('dd');
        }
        reason.textContent = data.cancellation_reason;
      }
    }

    var select = form.querySelector('[data-status-select]');
    if (select) {
      select.value = data.status;
      var allowed = transitions[data.status] || [data.status];
      Array.prototype.forEach.call(select.options, function (opt) { opt.disabled = allowed.indexOf(opt.value) === -1; });
    }
    field(form, 'cancellation_reason').value = '';
    field(form, 'cancellation_reason_custom').value = '';

    // Hide the row if it no longer matches the status filter in use.
    var filter = new URLSearchParams(window.location.search).get('status');
    if (filter && filter !== data.status) {
      setTimeout(function () {
        [row, view, details].forEach(function (el) { if (el) el.remove(); });
      }, 700);
    }
  }

  document.querySelectorAll('.order-update-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var id = form.getAttribute('data-order-id');
      var row = document.getElementById('order-row-' + id);
      var oldStatus = row ? row.getAttribute('data-status') : '';
      var newStatus = form.querySelector('[data-status-select]').value;
      var reason = field(form, 'cancellation_reason').value.trim();
      var other = field(form, 'cancellation_reason_custom').value.trim();

      if (newStatus === 'Cancelled' && oldStatus !== 'Cancelled' && (!reason || (reason === 'Other' && !other))) {
        openCancelModal(form);
        return;
      }

      setLoading(form, true);
      showMessage(form, '', true);
      fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        credentials: 'same-origin',
        cache: 'no-store'
      })
        .then(function (response) {
          return response.text().then(function (raw) {
            var data = null;
            try { data = raw ? JSON.parse(raw) : null; } catch (err) { data = null; }
            if (!data) throw new Error(response.status === 401 ? 'Your session has expired. Please log in again.' : 'The server did not return a valid response. Please reload the page.');
            if (!response.ok || !data.ok) throw new Error(data.message || 'Unable to update the order.');
            return data;
          });
        })
        .then(function (data) {
          updateRow(id, data, form);
          updateCounts(oldStatus, data.status);
          showMessage(form, 'Order status updated successfully.', true);
        })
        .catch(function (err) {
          showMessage(form, err.message || 'Unable to update the order.', false);
        })
        .then(function () { setLoading(form, false); });
    });
  });
})();
