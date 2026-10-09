/**
 * Receipt page: print and PDF download (falls back to printing if the
 * PDF library could not load).
 */
(function () {
  'use strict';

  var printButton = document.getElementById('printReceipt');
  if (printButton) {
    printButton.addEventListener('click', function () { window.print(); });
  }

  var pdfButton = document.getElementById('downloadPdf');
  if (!pdfButton) return;

  pdfButton.addEventListener('click', function () {
    var original = pdfButton.innerHTML;
    var restore = function () {
      pdfButton.disabled = false;
      pdfButton.innerHTML = original;
    };

    if (!window.html2pdf) {
      window.print();
      return;
    }

    pdfButton.disabled = true;
    pdfButton.textContent = 'Preparing PDF...';
    window.html2pdf()
      .set({
        margin: [7, 7, 7, 7],
        filename: pdfButton.getAttribute('data-filename') || 'Receipt.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
      })
      .from(document.getElementById('receiptDocument'))
      .save()
      .then(restore)
      .catch(function () {
        restore();
        window.print();
      });
  });
})();
