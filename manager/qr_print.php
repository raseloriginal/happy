<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Print QR Stickers';
$pdo       = getDB();
$wid       = $_SESSION['warehouse_id'];
// Only lots that have at least one qr_generated product
$lots = $pdo->prepare('SELECT DISTINCT l.id, l.lot_date, c.name as company_name FROM lots l JOIN companies c ON c.id=l.company_id JOIN lot_items li ON li.lot_id=l.id WHERE l.status=1 AND l.warehouse_id=? AND li.qr_generated=1 ORDER BY l.id DESC');
$lots->execute([$wid]); $lots = $lots->fetchAll();
$preselect = intval($_GET['lot_id'] ?? 0);
include __DIR__ . '/../includes/header.php';
?>
<!-- Load jsPDF for PDF generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body max-w-5xl">
      <div class="flex items-center justify-between mb-6 print:hidden">
        <div><h2 class="text-xl font-bold text-gray-800">Print QR Stickers</h2><p class="text-sm text-gray-500">38mm × 25mm (3.8×2.5cm) thermal label format</p></div>
        <a href="<?= rootPath() ?>/manager/lots.php" class="btn btn-ghost">← Lots</a>
      </div>

      <!-- Controls (hidden on print) -->
      <div id="controls" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6 print:hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
          <div>
            <label class="form-label">Select Lot *</label>
            <select id="lot-select" class="form-input" onchange="loadProducts()">
              <option value="">Select Lot</option>
              <?php foreach ($lots as $l): ?>
                <option value="<?= $l['id'] ?>" <?= $l['id'] == $preselect ? 'selected' : '' ?>>
                  #<?= $l['id'] ?> — <?= htmlspecialchars($l['company_name']) ?> (<?= $l['lot_date'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Select Product *</label>
            <select id="product-select" class="form-input" disabled>
              <option value="">Select Lot first</option>
            </select>
          </div>
        </div>
        <div class="flex gap-3 mt-4">
          <button onclick="loadStickers()" class="btn btn-primary" id="apply-btn" disabled>Apply</button>
          <button onclick="downloadPDF()" class="btn btn-danger" id="pdf-btn" style="display:none">📄 Download PDF (38x25mm)</button>
          <button onclick="downloadDoc()" class="btn btn-ghost" id="doc-btn" style="display:none">📥 Word Doc</button>
        </div>
      </div>

      <!-- Sticker Preview Area -->
      <div id="print-area">
        <div id="stickers-grid" class="grid grid-cols-2 sm:grid-cols-3 gap-4 print:block"></div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
async function loadProducts() {
  const lid  = document.getElementById('lot-select').value;
  const psel = document.getElementById('product-select');
  psel.innerHTML = '<option value="">Loading…</option>';
  psel.disabled  = true;
  document.getElementById('apply-btn').disabled = true;
  document.getElementById('pdf-btn').style.display = 'none';
  document.getElementById('doc-btn').style.display = 'none';
  document.getElementById('stickers-grid').innerHTML = '';

  if (!lid) { psel.innerHTML = '<option value="">Select Lot first</option>'; return; }

  const data = await api('<?= rootPath() ?>/api/qr.php?action=lot_products&lot_id=' + lid);
  const items = (data.data || []).filter(i => i.qr_generated == 1);
  psel.innerHTML = '<option value="">Select Product</option>';
  items.forEach(item => {
    psel.innerHTML += `<option value="${item.lot_item_id}" data-name="${item.product_name}" data-ppb="${item.pieces_per_box}" data-expiry="${item.expiry_date}">${item.product_name}</option>`;
  });
  psel.disabled = false;
  document.getElementById('apply-btn').disabled = false;
}

async function loadStickers() {
  const psel = document.getElementById('product-select');
  const lid  = psel.value;
  if (!lid) { showToast('Select a product', 'warning'); return; }

  const opt         = psel.options[psel.selectedIndex];
  const productName = opt.dataset.name;
  const piecesPerBox = opt.dataset.ppb;
  const expiryDate   = opt.dataset.expiry;

  const data = await api('<?= rootPath() ?>/api/qr.php?action=fetch&lot_item_id=' + lid);
  if (!data.success) { showToast('Failed to load QR codes', 'error'); return; }

  const grid = document.getElementById('stickers-grid');
  grid.innerHTML = '';

  for (const qr of data.data) {
    const div = document.createElement('div');
    div.className = 'sticker-card flex flex-row items-center';

    const expText = expiryDate ? ` | Exp: ${expiryDate}` : '';

    div.innerHTML = `
      <div class="sticker-left">
        <!-- Canvas will be appended here -->
        <div class="sticker-qr-uid">${qr.qr_uid}</div>
      </div>
      <div class="sticker-right">
        <div class="sticker-product-name">${productName}</div>
        <div class="sticker-qty">${piecesPerBox} pcs/box ${expText}</div>
      </div>
    `;

    const canvas = document.createElement('canvas');
    generateQRCanvas(canvas, qr.qr_uid, 80); // Reduced from 150 to 80 to prevent overlap
    div.querySelector('.sticker-left').prepend(canvas);

    grid.appendChild(div);
  }

  document.getElementById('pdf-btn').style.display = 'inline-flex';
  document.getElementById('doc-btn').style.display = 'inline-flex';
  showToast(`Loaded ${data.data.length} stickers`);
}

async function downloadPDF() {
  const grid = document.getElementById('stickers-grid');
  if (!grid.children.length) { showToast('No stickers to download', 'warning'); return; }

  const { jsPDF } = window.jspdf;
  const pdf = new jsPDF({
    orientation: 'l',
    unit: 'mm',
    format: [38, 25],
    putOnlyUsedFonts: true,
    floatPrecision: 16
  });

  const cards = grid.children;
  for (let i = 0; i < cards.length; i++) {
    const card = cards[i];
    const canvas = card.querySelector('canvas');
    if (!canvas) continue;
    const qrData = canvas.toDataURL('image/png');
    const productName = card.querySelector('.sticker-product-name').innerText;
    const qtyText = card.querySelector('.sticker-qty').innerText;
    const qrUid = card.querySelector('.sticker-qr-uid').innerText;

    if (i > 0) pdf.addPage([38, 25], 'l');

    // Draw QR Code (18mm x 18mm)
    pdf.addImage(qrData, 'PNG', 2, 2, 18, 18);
    
    // Draw QR UID under QR (Center-aligned to QR)
    pdf.setFontSize(6);
    pdf.setFont('helvetica', 'bold');
    pdf.text(qrUid, 11, 22.5, { align: 'center' });

    // Draw Product Name (on the right)
    pdf.setFontSize(7); // Smaller font for safety
    pdf.setTextColor(0, 0, 0);
    // Split text and limit to 3 lines
    const splitTitle = pdf.splitTextToSize(productName, 17);
    const limitedTitle = splitTitle.slice(0, 3);
    pdf.text(limitedTitle, 20, 5);

    // Draw Qty text (Fixed near bottom)
    pdf.setFontSize(5);
    pdf.setFont('helvetica', 'normal');
    pdf.text(qtyText, 20, 18);
  }

  const lotSelect = document.getElementById('lot-select');
  const lotText = lotSelect.options[lotSelect.selectedIndex].text.split('—')[0].trim();
  pdf.save(`stickers_${lotText.replace('#', '')}.pdf`);
  showToast('PDF Downloaded');
}

function downloadDoc() {
  const grid = document.getElementById('stickers-grid');
  if (!grid.children.length) { showToast('No stickers to download', 'warning'); return; }

  let htmlContent = `
    <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
    <head><meta charset='utf-8'>
    <style>
      @page Section1 { size: 38mm 25mm; margin: 0mm; }
      div.Section1 { page: Section1; }
      .sticker-card {
        width: 38mm;
        height: 25mm;
        padding: 0;
        margin: 0;
        box-sizing: border-box;
        display: block;
        page-break-after: always;
        font-family: Arial, sans-serif;
      }
      .sticker-container {
        width: 38mm;
        height: 25mm;
        position: relative;
        overflow: hidden;
      }
      .sticker-left { float: left; width: 19mm; height: 25mm; padding-top: 1mm; text-align: center; }
      .sticker-right { float: left; width: 18mm; height: 25mm; padding-top: 3mm; padding-left: 0.5mm; }
      .sticker-product-name { font-size: 7pt; font-weight: bold; line-height: 1; color: #000; word-wrap: break-word; }
      .sticker-qty { font-size: 5pt; color: #111; margin-top: 1mm; }
      .sticker-qr-uid { font-size: 6.5pt; font-weight: bold; margin-top: 0.2mm; color: #000; text-align: center; }
      img.qr-code { width: 18mm; height: 18mm; display: block; margin: 0 auto; }
    </style>
    </head>
    <body><div class="Section1">
  `;

  for (const card of grid.children) {
    const canvas = card.querySelector('canvas');
    if (!canvas) continue;
    const qrImage = canvas.toDataURL('image/png');
    const productName = card.querySelector('.sticker-product-name').innerText;
    const qtyText = card.querySelector('.sticker-qty').innerText;
    const qrUid = card.querySelector('.sticker-qr-uid').innerText;

    htmlContent += `
      <div class="sticker-card">
        <div class="sticker-container">
          <div class="sticker-left">
            <img class="qr-code" src="${qrImage}" />
            <div class="sticker-qr-uid">${qrUid}</div>
          </div>
          <div class="sticker-right">
            <div class="sticker-product-name">${productName}</div>
            <div class="sticker-qty">${qtyText}</div>
          </div>
          <div style="clear:both;"></div>
        </div>
      </div>
    `;
  }

  htmlContent += `</div></body></html>`;

  const blob = new Blob(['\ufeff', htmlContent], { type: 'application/msword' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  const lotSelect = document.getElementById('lot-select');
  const lotText = lotSelect.options[lotSelect.selectedIndex].text.split('—')[0].trim();
  
  link.href = url;
  link.download = `stickers_${lotText.replace('#', '')}.doc`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

// Auto-load if preset
window.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('lot-select').value) loadProducts();
});
</script>
