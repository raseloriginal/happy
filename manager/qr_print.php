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
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body max-w-5xl">
      <div class="flex items-center justify-between mb-6 print:hidden">
        <div><h2 class="text-xl font-bold text-gray-800">Print QR Stickers</h2><p class="text-sm text-gray-500">75mm × 100mm thermal label format</p></div>
        <a href="/happycrm2/manager/lots.php" class="btn btn-ghost">← Lots</a>
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
          <button onclick="window.print()" class="btn btn-success" id="print-btn" style="display:none">🖨 Print Stickers</button>
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
  document.getElementById('print-btn').style.display = 'none';
  document.getElementById('stickers-grid').innerHTML = '';

  if (!lid) { psel.innerHTML = '<option value="">Select Lot first</option>'; return; }

  const data = await api('/happycrm2/api/qr.php?action=lot_products&lot_id=' + lid);
  const items = (data.data || []).filter(i => i.qr_generated == 1);
  psel.innerHTML = '<option value="">Select Product</option>';
  items.forEach(item => {
    psel.innerHTML += `<option value="${item.lot_item_id}" data-name="${item.product_name}" data-ppb="${item.pieces_per_box}">${item.product_name}</option>`;
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

  const data = await api('/happycrm2/api/qr.php?action=fetch&lot_item_id=' + lid);
  if (!data.success) { showToast('Failed to load QR codes', 'error'); return; }

  const grid = document.getElementById('stickers-grid');
  grid.innerHTML = '';

  for (const qr of data.data) {
    const div = document.createElement('div');
    div.className = 'sticker-card';

    const canvas = document.createElement('canvas');
    generateQRCanvas(canvas, qr.qr_uid, 150);
    div.appendChild(canvas);

    div.innerHTML += `
      <div class="sticker-qr-uid">${qr.qr_uid}</div>
      <div class="sticker-product-name">${productName}</div>
      <div class="sticker-qty">${piecesPerBox} pcs/box</div>
    `;
    // Re-append canvas (innerHTML overwrites it)
    grid.appendChild(div);

    // Re-generate canvas since innerHTML cleared it
    const c2 = div.querySelector('canvas') || document.createElement('canvas');
    if (!div.contains(c2)) div.prepend(c2);
    generateQRCanvas(c2, qr.qr_uid, 150);
  }

  document.getElementById('print-btn').style.display = 'inline-flex';
  showToast(`Loaded ${data.data.length} stickers`);
}

// Auto-load if preset
window.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('lot-select').value) loadProducts();
});
</script>
