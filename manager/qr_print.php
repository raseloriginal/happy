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
<style>
  /* On-screen status border styling */
  .sticker-card.border-dispatched {
    border: 3px solid #dc2626 !important; /* Bold Red */
  }
  .sticker-card.border-not-original {
    border: 3px solid #eab308 !important; /* Bold Yellow */
  }
  .sticker-card.border-active {
    border: 3px solid #16a34a !important; /* Bold Green */
  }

  /* Do NOT print any borders */
  @media print {
    .sticker-card {
      border: none !important;
    }
  }
</style>
<!-- Load jsPDF for PDF generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body max-w-5xl">
      <div class="flex items-center justify-between mb-6 print:hidden">
        <div><h2 class="text-xl font-bold text-gray-800">Print QR Stickers</h2><p class="text-sm text-gray-500">38mm × 25mm (3.8×2.5cm) thermal label format</p></div>
        <a href="<?= rootPath() ?>/manager/lots.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left mr-1"></i> Lots</a>
      </div>

      <!-- Controls (hidden on print) -->
      <div id="controls" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6 print:hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
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
          <div>
            <label class="form-label">Filter by Status Border</label>
            <select id="status-filter" class="form-input" onchange="applyStatusFilter()">
              <option value="all">Show All</option>
              <option value="green">Active Original (Green)</option>
              <option value="yellow">Not Original Pcs (Yellow)</option>
              <option value="red">Out of Delivery (Red)</option>
            </select>
          </div>
        </div>
        <div class="flex gap-3 mt-4">
          <button onclick="loadStickers()" class="btn btn-primary" id="apply-btn" disabled>Apply</button>
          <button onclick="downloadPDF()" class="btn btn-danger" id="pdf-btn" style="display:none"><i class="fa-solid fa-file-pdf mr-1"></i> Download PDF (38x25mm)</button>
          <button onclick="downloadDoc()" class="btn btn-ghost" id="doc-btn" style="display:none"><i class="fa-solid fa-file-word mr-1"></i> Word Doc</button>
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
    psel.innerHTML += `<option value="${item.lot_item_id}" data-name="${item.product_name}" data-ppb="${item.pieces_per_box}" data-expiry="${item.expiry_date}" data-price="${item.selling_price}">${item.product_name}</option>`;
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
    
    // Determine border status class based on dispatch status and original pieces remaining
    let borderClass = 'border-active';
    if (qr.status === 'dispatched' || qr.status === 'depleted' || parseInt(qr.pieces_remaining) === 0) {
      borderClass = 'border-dispatched';
    } else if (parseInt(qr.pieces_remaining) < parseInt(qr.pieces_total)) {
      borderClass = 'border-not-original';
    }
    div.className = `sticker-card flex flex-row items-center ${borderClass}`;

    const priceText = qr.selling_price ? `${qr.selling_price}taka` : '';
    const expText = expiryDate ? `Exp: ${expiryDate}` : '';

    div.innerHTML = `
      <div class="sticker-left">
        <div class="sticker-qr-container"></div>
        <div class="sticker-qr-uid">${qr.qr_uid}</div>
      </div>
      <div class="sticker-right flex flex-col justify-between h-full">
        <div>
          <div class="sticker-product-name">${productName}</div>
          <div class="sticker-price">${priceText}</div>
        </div>
        <div class="mt-auto">
          <div class="sticker-qty">${qr.pieces_remaining} pcs</div>
          <div class="sticker-exp">${expText}</div>
        </div>
      </div>
    `;

    const canvas = document.createElement('canvas');
    generateQRCanvas(canvas, qr.qr_uid, 80); // <--- CHANGE WEB PREVIEW QR SIZE HERE (80 is the width/height in pixels)
    div.querySelector('.sticker-qr-container').appendChild(canvas);

    grid.appendChild(div);
  }

  // Reset status filter dropdown to default when new stickers load
  document.getElementById('status-filter').value = 'all';

  document.getElementById('pdf-btn').style.display = 'inline-flex';
  document.getElementById('doc-btn').style.display = 'inline-flex';
  showToast(`Loaded ${data.data.length} stickers`);
}

function applyStatusFilter() {
  const filterVal = document.getElementById('status-filter').value;
  const grid = document.getElementById('stickers-grid');
  const cards = grid.children;
  let count = 0;

  for (let i = 0; i < cards.length; i++) {
    const card = cards[i];
    let show = false;

    if (filterVal === 'all') {
      show = true;
    } else if (filterVal === 'green' && card.classList.contains('border-active')) {
      show = true;
    } else if (filterVal === 'yellow' && card.classList.contains('border-not-original')) {
      show = true;
    } else if (filterVal === 'red' && card.classList.contains('border-dispatched')) {
      show = true;
    }

    if (show) {
      card.style.display = 'flex';
      count++;
    } else {
      card.style.display = 'none';
    }
  }

  // Update PDF & Word buttons display based on visible sticker count
  const hasVisible = count > 0;
  document.getElementById('pdf-btn').style.display = hasVisible ? 'inline-flex' : 'none';
  document.getElementById('doc-btn').style.display = hasVisible ? 'inline-flex' : 'none';
}

/**
 * Renders Bengali (or any Unicode) text via the browser's own 2D canvas,
 * which applies full HarfBuzz shaping (conjuncts, matras, etc.).
 * Returns a PNG data-URL and the exact height in mm used.
 */
function renderBengaliText(text, fontSizePt, maxWidthMm, maxLines) {
  const SCALE   = 4;                          // high-res multiplier
  const MM2PX   = 3.7795 * SCALE;            // mm → canvas px
  const fPx     = Math.round(fontSizePt * (96 / 72) * SCALE); // pt → px
  const lhMm    = fontSizePt * 25.4 / 72 * 1.45;   // line-height in mm
  const lhPx    = Math.round(lhMm * MM2PX);
  const maxWPx  = Math.round(maxWidthMm * MM2PX);

  // ---- measure & word-wrap ----
  const probe = document.createElement('canvas').getContext('2d');
  probe.font  = `bold ${fPx}px 'Noto Sans Bengali', 'SolaimanLipi', sans-serif`;

  const lines = [];
  let cur = '';
  for (const word of text.split(' ')) {
    const test = cur ? cur + ' ' + word : word;
    if (probe.measureText(test).width > maxWPx && cur) {
      lines.push(cur);
      cur = word;
      if (lines.length >= maxLines) { cur = ''; break; }
    } else { cur = test; }
  }
  if (cur && lines.length < maxLines) lines.push(cur);
  const finalLines = lines.slice(0, maxLines);

  // ---- draw ----
  const paddingTopMm = 0.8; // Padding to prevent clipping of Bengali matras (e-kar, i-kar)
  const paddingTopPx = Math.round(paddingTopMm * MM2PX);

  const c   = document.createElement('canvas');
  c.width   = maxWPx;
  c.height  = (lhPx * (finalLines.length || 1)) + paddingTopPx;
  const ctx = c.getContext('2d');
  ctx.font        = `bold ${fPx}px 'Noto Sans Bengali', 'SolaimanLipi', sans-serif`;
  ctx.fillStyle   = '#000000';
  ctx.textBaseline = 'top';
  finalLines.forEach((line, i) => ctx.fillText(line, 0, paddingTopPx + (i * lhPx)));

  return {
    url   : c.toDataURL('image/png'),
    lines : finalLines.length,
    lhMm  : lhMm + (paddingTopMm / (finalLines.length || 1)) // Distribute padding across lines to maintain exact aspect ratio
  };
}

function drawStickerToPDF(pdf, card, pageCount) {
  const canvas = card.querySelector('canvas');
  if (!canvas) return;
  const qrData      = canvas.toDataURL('image/png');
  const productName = card.querySelector('.sticker-product-name').innerText;
  const qtyText     = card.querySelector('.sticker-qty').innerText;
  const qrUid       = card.querySelector('.sticker-qr-uid').innerText;

  if (pageCount > 0) pdf.addPage([38, 25], 'l');

  // Draw QR Code (14mm x 14mm)
  pdf.addImage(qrData, 'PNG', 1, 1, 18, 18); // <--- CHANGE PDF QR POSITION & SIZE HERE (1.5=X pos, 3.5=Y pos, 14=Width, 14=Height in mm)

  // Draw QR UID under QR
  pdf.setFontSize(7);
  pdf.setFont('helvetica', 'bold');
  pdf.text(qrUid, 11, 22, { align: 'center' });

  // Right side constants
  const textX     = 20;
  const textWidth = 15;

  // 1. Product Name — rendered by browser canvas for correct Bengali shaping
  const nr     = renderBengaliText(productName, 6, textWidth, 3);
  const nameY  = 1.5;  // Aligned with QR Code top (3.5mm)
  const nameH  = nr.lines * nr.lhMm;
  pdf.addImage(nr.url, 'PNG', textX, nameY, textWidth, nameH);
  let currentY = nameY + nameH + 2;

  // 2. Price
  const priceText = card.querySelector('.sticker-price').innerText;
  if (priceText) {
    pdf.setFontSize(7);
    pdf.setFont('helvetica', 'bold');
    pdf.text(priceText, textX, currentY);
    currentY += 4;
  }

  // 3. Qty — flows right after price
  pdf.setFontSize(7);
  pdf.setFont('helvetica', 'bold');
  pdf.text(qtyText.split('\n')[0], textX, currentY);
  currentY += 3;

  // 4. Exp — flows right after Qty
  const expText = card.querySelector('.sticker-exp').innerText;
  if (expText) {
    pdf.setFontSize(6);
    pdf.setFont('helvetica', 'bold');
    pdf.text(expText, textX, currentY);
  }
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
  let pageCount = 0;
  for (let i = 0; i < cards.length; i++) {
    const card = cards[i];
    if (card.style.display === 'none') continue; // Skip filtered-out stickers!

    drawStickerToPDF(pdf, card, pageCount);
    pageCount++;
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
      .sticker-left { float: left; width: 16mm; height: 25mm; padding-top: 1.5mm; text-align: center; } /* <--- CHANGE WORD DOC QR CONTAINER PADDING/WIDTH HERE */
      .sticker-right { float: left; width: 21mm; height: 25mm; padding-top: 2mm; padding-left: 1mm; }
      .sticker-product-name { font-size: 8.5pt; font-weight: bold; line-height: 1; color: #000; margin-bottom: 0.5mm; }
      .sticker-price { font-size: 7.5pt; font-weight: bold; color: #000; }
      .sticker-qty { font-size: 6pt; color: #111; margin-top: 2.5mm; line-height: 1; }
      .sticker-exp { font-size: 6pt; color: #111; line-height: 1; }
      .sticker-qr-uid { font-size: 5.5pt; font-weight: bold; margin-top: 0.5mm; color: #000; text-align: center; }
      img.qr-code { width: 14mm; height: 14mm; display: block; margin: 0 auto; } /* <--- CHANGE WORD DOC QR SIZE & MARGIN HERE */
    </style>
    </head>
    <body><div class="Section1">
  `;

  for (const card of grid.children) {
    if (card.style.display === 'none') continue; // Skip filtered-out stickers!

    const canvas = card.querySelector('canvas');
    if (!canvas) continue;
    const qrImage     = canvas.toDataURL('image/png');
    const productName = card.querySelector('.sticker-product-name').innerText;
    const qtyText     = card.querySelector('.sticker-qty').innerText;
    const qrUid       = card.querySelector('.sticker-qr-uid').innerText;
    const priceText   = card.querySelector('.sticker-price').innerText;
    const expText     = card.querySelector('.sticker-exp').innerText;

    htmlContent += `
      <div class="sticker-card">
        <div class="sticker-container">
          <div class="sticker-left">
            <img class="qr-code" src="${qrImage}" />
            <div class="sticker-qr-uid">${qrUid}</div>
          </div>
          <div class="sticker-right">
            <div class="sticker-product-name">${productName}</div>
            <div class="sticker-price">${priceText}</div>
            <div class="sticker-qty">${qtyText.split('\n')[0]}</div>
            <div class="sticker-exp">${expText}</div>
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
