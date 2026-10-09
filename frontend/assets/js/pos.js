/**
 * Sales (POS) form: multiple product lines with live line and grand totals.
 */
(function () {
  'use strict';

  var container = document.getElementById('saleItems');
  var addButton = document.getElementById('addProductBtn');
  var grandTotal = document.getElementById('grandTotal');
  if (!container) return;

  function format(n) {
    return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }

  function rows() {
    return container.querySelectorAll('.sale-item-row');
  }

  function refreshRow(row) {
    var product = row.querySelector('.sale-product');
    var quantity = row.querySelector('.sale-qty');
    if (!product || !product.options.length) return 0;

    var option = product.options[product.selectedIndex];
    var unitPrice = Number((option && option.getAttribute('data-price')) || 0);
    var stock = Number((option && option.getAttribute('data-stock')) || 0);
    if (quantity) quantity.max = stock > 0 ? String(stock) : '';

    var lineTotal = Number((quantity && quantity.value) || 0) * unitPrice;
    row.querySelector('.sale-price').value = format(unitPrice);
    row.querySelector('.sale-total').value = format(lineTotal);
    return lineTotal;
  }

  function refreshAll() {
    var total = 0;
    var all = rows();
    all.forEach(function (row) {
      total += refreshRow(row);
      row.querySelector('.remove-sale-item').hidden = all.length <= 1;
    });
    if (grandTotal) grandTotal.textContent = format(total);
  }

  container.addEventListener('change', refreshAll);
  container.addEventListener('input', refreshAll);
  container.addEventListener('click', function (e) {
    var remove = e.target.closest('.remove-sale-item');
    if (remove && rows().length > 1) {
      remove.closest('.sale-item-row').remove();
      refreshAll();
    }
  });

  if (addButton) {
    addButton.addEventListener('click', function () {
      var source = container.querySelector('.sale-item-row');
      if (!source) return;
      var row = source.cloneNode(true);
      row.querySelector('.sale-qty').value = '1';
      row.querySelector('.sale-product').selectedIndex = 0;
      container.appendChild(row);
      refreshAll();
      row.querySelector('.sale-product').focus();
    });
  }

  refreshAll();
})();
