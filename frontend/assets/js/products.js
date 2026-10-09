/**
 * Products page: keeps the unit and package-size fields in sync with the
 * product type (Eggs are sold by tray; Rice/Flour by bag with a 1–20 Kg
 * package size), and runs the "Rename product" dialog.
 */
(function () {
  'use strict';

  /* ---------------------------------------------- Add / edit form */
  var category = document.getElementById('category');
  var packageSize = document.getElementById('package_size_kg');
  var unit = document.getElementById('unit_display');
  var stockLabel = document.getElementById('stock_label');
  var stockHelp = document.getElementById('stock_help');
  var unitHelp = document.getElementById('unit_help');

  function sync() {
    var isEgg = category.value === 'Eggs';
    packageSize.disabled = isEgg;
    packageSize.required = !isEgg;
    packageSize.closest('.package-field').classList.toggle('hidden', isEgg);
    unit.value = isEgg ? 'Tray' : 'Bag';
    stockLabel.textContent = isEgg ? 'Stock Quantity (Trays)' : 'Stock Quantity (Bags)';
    stockHelp.textContent = isEgg
      ? 'Enter whole trays. Example: 1 = 1 tray = 30 eggs.'
      : 'Enter whole bags/packages. Each bag uses the selected package size (1–20 Kg).';
    unitHelp.textContent = isEgg
      ? 'Egg stock is counted by tray. 1 tray = 30 eggs.'
      : 'Rice/Flour stock is counted by bags/packages.';
  }

  if (category && packageSize) {
    category.addEventListener('change', sync);
    sync();
  }

  /* ---------------------------------------------- Rename dialog */
  var dialog = document.getElementById('renameDialog');
  if (!dialog || typeof dialog.showModal !== 'function') return;

  var idField = document.getElementById('renameId');
  var current = document.getElementById('renameCurrent');
  var nameField = document.getElementById('renameName');

  document.querySelectorAll('[data-rename-id]').forEach(function (button) {
    button.addEventListener('click', function () {
      var name = button.getAttribute('data-rename-name');
      idField.value = button.getAttribute('data-rename-id');
      current.textContent = name;
      nameField.value = name;
      dialog.showModal();
      nameField.select();
    });
  });

  document.getElementById('renameCancel').addEventListener('click', function () { dialog.close(); });
  dialog.addEventListener('click', function (e) {
    if (e.target === dialog) dialog.close(); // click on the backdrop
  });
})();
