<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Generate QR Codes';
$pdo       = getDB();
$wid       = $_SESSION['warehouse_id'];
$lots      = $pdo->prepare('SELECT l.id, l.lot_date, c.name as company_name FROM lots l JOIN companies c ON c.id=l.company_id WHERE l.status=1 AND l.warehouse_id=? ORDER BY l.id DESC');
$lots->execute([$wid]); $lots = $lots->fetchAll();
$preselect = intval($_GET['lot_id'] ?? 0);
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body max-w-5xl">
      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Generate QR Codes</h2><p class="text-sm text-gray-500">Generate QR codes for each box in a lot</p></div>
        <a href="<?= rootPath() ?>/manager/lots.php" class="btn btn-ghost">← Lots</a>
      </div>

      <!-- Controls -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
          <div>
            <label class="form-label">Select Lot *</label>
            <select id="lot-select" class="form-input" onchange="loadLotProducts()">
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
            <select id="product-select" class="form-input" onchange="fillQty()" disabled>
              <option value="">Select Lot first</option>
            </select>
          </div>
          <div>
            <label class="form-label">Qty in Lot (Boxes)</label>
            <input id="qty-display" class="form-input bg-gray-50" readonly placeholder="—" />
          </div>
        </div>
        <div class="mt-4">
          <button id="generate-btn" onclick="generateQR()" class="btn btn-primary" disabled>
            🔲 Generate QR Codes
          </button>
        </div>
      </div>

      <!-- QR Grid -->
      <div id="qr-section" style="display:none">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-gray-700">Generated QR Codes</h3>
          <a href="<?= rootPath() ?>/manager/qr_print.php" class="btn btn-ghost btn-sm">🖨 Go to Print Page</a>
        </div>
        <div id="qr-grid" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3"></div>
      </div>

      <div id="loading" style="display:none" class="text-center py-12">
        <div class="text-gray-400 text-sm">Generating QR codes…</div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
let currentLotItems = [];

async function loadLotProducts() {
  const lid = document.getElementById('lot-select').value;
  const psel = document.getElementById('product-select');
  const qtyEl = document.getElementById('qty-display');
  psel.innerHTML = '<option value="">Loading…</option>';
  psel.disabled = true;
  document.getElementById('generate-btn').disabled = true;
  document.getElementById('qr-section').style.display = 'none';
  qtyEl.value = '';

  if (!lid) { psel.innerHTML = '<option value="">Select Lot first</option>'; return; }

  const data = await api('<?= rootPath() ?>/api/qr.php?action=lot_products&lot_id=' + lid);
  currentLotItems = data.data || [];
  psel.innerHTML = '<option value="">Select Product</option>';
  currentLotItems.forEach(item => {
    const status = item.qr_generated ? ' ✓' : '';
    psel.innerHTML += `<option value="${item.lot_item_id}" data-qty="${item.qty_boxes}" data-pid="${item.product_id}">${item.product_name}${status}</option>`;
  });
  psel.disabled = false;
}

function fillQty() {
  const psel = document.getElementById('product-select');
  const opt  = psel.options[psel.selectedIndex];
  document.getElementById('qty-display').value = opt.dataset.qty ? opt.dataset.qty + ' boxes' : '';
  document.getElementById('generate-btn').disabled = !psel.value;
}

async function generateQR() {
  const psel       = document.getElementById('product-select');
  const lot_id     = document.getElementById('lot-select').value;
  const lot_item_id = psel.value;
  const product_id = psel.options[psel.selectedIndex].dataset.pid;

  if (!lot_id || !lot_item_id) return;

  document.getElementById('loading').style.display = 'block';
  document.getElementById('qr-section').style.display = 'none';

  const data = await api('<?= rootPath() ?>/api/qr.php?action=generate', 'POST', { lot_id: parseInt(lot_id), lot_item_id: parseInt(lot_item_id), product_id: parseInt(product_id) });

  document.getElementById('loading').style.display = 'none';

  if (!data.success) { showToast(data.message || 'Error', 'error'); return; }

  const grid = document.getElementById('qr-grid');
  grid.innerHTML = '';
  (data.data || []).forEach(qr => {
    const div = document.createElement('div');
    div.className = 'qr-card';
    const canvas = document.createElement('canvas');
    generateQRCanvas(canvas, qr.qr_uid, 100);
    div.appendChild(canvas);
    const uid = document.createElement('div');
    uid.className = 'qr-uid';
    uid.textContent = qr.qr_uid;
    div.appendChild(uid);
    grid.appendChild(div);
  });

  document.getElementById('qr-section').style.display = 'block';
  if (data.message === 'Already generated') showToast('QR codes already existed — showing existing', 'info');
  else showToast(`Generated ${data.data.length} QR codes!`);
}

// Auto-load if lot_id preset
window.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('lot-select').value) loadLotProducts();
});
</script>
