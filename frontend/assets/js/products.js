/**
 * Product form: keeps type, name, unit and package-size fields in sync
 * (Eggs are sold by tray; Rice/Flour by bag with a 1–20 Kg package size).
 */
(function () {
  'use strict';

  var category = document.getElementById('category');
  var productName = document.getElementById('product_name');
  var packageSize = document.getElementById('package_size_kg');
  var unit = document.getElementById('unit_display');
  var stockLabel = document.getElementById('stock_label');
  var stockHelp = document.getElementById('stock_help');
  var unitHelp = document.getElementById('unit_help');
  if (!category || !productName || !packageSize) return;

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

  category.addEventListener('change', function () {
    productName.value = category.value;
    sync();
  });
  productName.addEventListener('change', function () {
    category.value = productName.value;
    sync();
  });

  sync();
})();
