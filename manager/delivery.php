<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Out for Delivery';
$pdo       = getDB();
$wid       = $_SESSION['warehouse_id'];
$dsrs      = $pdo->prepare('SELECT d.id, u.name FROM dsr d JOIN users u ON u.id=d.user_id WHERE d.warehouse_id=? AND d.status=1 ORDER BY u.name');
$dsrs->execute([$wid]); $dsrs = $dsrs->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body max-w-5xl">
      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Out for Delivery</h2><p class="text-sm text-gray-500">Scan QR boxes to load onto van</p></div>
        <a href="<?= rootPath() ?>/manager/orders.php" class="btn btn-ghost">← Orders</a>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Controls + Scanner -->
        <div class="space-y-4">

          <!-- Order & DSR Selection -->
          <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
            <div>
              <label class="form-label">Select Pending Order *</label>
              <select id="order-select" class="form-input" onchange="loadOrderItems()">
                <option value="">Loading orders…</option>
              </select>
            </div>
            <div>
              <label class="form-label">Select DSR (Driver) *</label>
              <select id="dsr-select" class="form-input">
                <option value="">Select DSR</option>
                <?php foreach ($dsrs as $d): ?>
                  <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- QR Scanner -->
          <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-semibold text-gray-700 mb-3">📷 QR Scanner</h3>
            <div id="qr-reader" class="rounded-lg overflow-hidden mb-3" style="min-height:80px"></div>
            <div class="flex gap-2">
              <button onclick="startScan()" id="scan-btn" class="btn btn-primary flex-1" disabled>Start Camera</button>
              <button onclick="stopScan()" id="stop-btn" class="btn btn-ghost flex-1" style="display:none">Stop</button>
            </div>
            <div class="mt-3">
              <label class="form-label">Manual / USB Input</label>
              <input id="manual-qr" type="text" class="form-input scanner-input" placeholder="Scan or type QR UID…" onkeydown="handleManualInput(event)" />
            </div>
          </div>

          <!-- Scanned log -->
          <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-semibold text-gray-700 mb-3">Scan Log</h3>
            <div id="scan-log" class="space-y-1 max-h-48 overflow-y-auto text-sm text-gray-600">
              <div class="text-gray-400 text-xs">Scanned boxes will appear here</div>
            </div>
          </div>
        </div>

        <!-- Right: Products Progress -->
        <div class="lg:col-span-2 space-y-4">

          <div id="order-placeholder" class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center text-gray-400">
            Select an order to begin loading
          </div>

          <div id="products-panel" style="display:none">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
              <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-700">Products to Load</h3>
              </div>
              <div id="products-list" class="divide-y divide-gray-100"></div>
            </div>

            <div class="mt-4">
              <button id="complete-btn" onclick="completeDelivery()" class="btn btn-success w-full py-3 text-base" disabled>
                ✅ Complete — Send to Van
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
let orderItems   = [];   // [{product_id, product_name, required_boxes, scanned: 0}]
let scannedBoxes = [];   // [{qr_id, product_id, qr_uid, pieces_total}]

// Load pending orders on page load
window.addEventListener('DOMContentLoaded', async () => {
  const data = await api('<?= rootPath() ?>/api/delivery.php?action=pending_orders');
  const sel  = document.getElementById('order-select');
  sel.innerHTML = '<option value="">Select Order</option>';
  (data.data || []).forEach(o => {
    sel.innerHTML += `<option value="${o.id}">#${String(o.id).padStart(4,'0')} — ${o.sr_name} (${o.company_name}) ${o.order_date}</option>`;
  });
});

async function loadOrderItems() {
  const oid = document.getElementById('order-select').value;
  orderItems   = [];
  scannedBoxes = [];
  document.getElementById('scan-log').innerHTML = '<div class="text-gray-400 text-xs">Scanned boxes will appear here</div>';

  if (!oid) {
    document.getElementById('order-placeholder').style.display = 'block';
    document.getElementById('products-panel').style.display = 'none';
    document.getElementById('scan-btn').disabled = true;
    return;
  }

  const data = await api('<?= rootPath() ?>/api/delivery.php?action=order_items&order_id=' + oid);
  const list  = document.getElementById('products-list');
  list.innerHTML = '';
  orderItems = [];

  (data.data || []).forEach(item => {
    const required = Math.ceil(item.qty_pieces / item.pieces_per_box);
    orderItems.push({ product_id: item.product_id, product_name: item.product_name, required, scanned: 0, pieces_per_box: item.pieces_per_box });

    const div = document.createElement('div');
    div.id    = 'prod-' + item.product_id;
    div.className = 'px-5 py-4';
    div.innerHTML = `
      <div class="flex items-center justify-between mb-2">
        <div class="font-medium text-gray-800">${item.product_name}</div>
        <div class="text-sm text-gray-500"><span id="cnt-${item.product_id}">0</span> / ${required} boxes</div>
      </div>
      <div class="progress-bar">
        <div class="progress-fill" id="bar-${item.product_id}" style="width:0%"></div>
      </div>
    `;
    list.appendChild(div);
  });

  document.getElementById('order-placeholder').style.display = 'none';
  document.getElementById('products-panel').style.display = 'block';
  document.getElementById('scan-btn').disabled = false;
  checkComplete();
}

async function processQRScan(uid) {
  const oid = document.getElementById('order-select').value;
  if (!oid) { showToast('Select an order first', 'warning'); return; }

  const scannedIds = scannedBoxes.map(s => s.qr_id);
  const data = await api('<?= rootPath() ?>/api/delivery.php?action=scan_box', 'POST', {
    qr_uid: uid, order_id: parseInt(oid), scanned_ids: scannedIds
  });

  if (!data.success) {
    const type = data.type || 'error';
    const toastType = type === 'duplicate' ? 'warning' : 'error';
    showToast(data.message, toastType);
    addLog(uid, false, data.message);
    return;
  }

  const qr = data.data;
  scannedBoxes.push({ qr_id: qr.id, product_id: qr.product_id, qr_uid: uid, pieces_total: qr.pieces_total });

  // Update product row
  const item = orderItems.find(i => i.product_id == qr.product_id);
  if (item) {
    item.scanned++;
    const pct = Math.min((item.scanned / item.required) * 100, 100);
    const bar = document.getElementById('bar-' + item.product_id);
    bar.style.width = pct + '%';
    document.getElementById('cnt-' + item.product_id).textContent = item.scanned;
    if (item.scanned >= item.required) {
      bar.classList.add('complete');
      document.getElementById('prod-' + item.product_id).style.background = '#F0FDF4';
    }
  }

  addLog(uid, true, qr.product_name);
  checkComplete();
  document.getElementById('manual-qr').value = '';
}

function addLog(uid, success, msg) {
  const log  = document.getElementById('scan-log');
  const div  = document.createElement('div');
  div.className = `flex items-center gap-2 py-1 border-b border-gray-50`;
  div.innerHTML = `<span class="${success ? 'text-green-500' : 'text-red-500'}">${success ? '✓' : '✗'}</span><span class="font-mono text-xs">${uid}</span><span class="text-gray-400 text-xs">${msg}</span>`;
  log.prepend(div);
}

function checkComplete() {
  const allDone = orderItems.length > 0 && orderItems.every(i => i.scanned >= i.required);
  document.getElementById('complete-btn').disabled = !allDone;
}

function startScan() {
  document.getElementById('scan-btn').style.display = 'none';
  document.getElementById('stop-btn').style.display = 'inline-flex';
  startCameraScanner('qr-reader', processQRScan);
}

function stopScan() {
  stopCameraScanner();
  document.getElementById('scan-btn').style.display = 'inline-flex';
  document.getElementById('stop-btn').style.display = 'none';
}

function handleManualInput(e) {
  if (e.key === 'Enter') {
    const val = document.getElementById('manual-qr').value.trim();
    if (val) processQRScan(val);
  }
}

// USB scanner
registerUsbScanner(processQRScan);

async function completeDelivery() {
  const oid   = document.getElementById('order-select').value;
  const dsrId = document.getElementById('dsr-select').value;
  if (!dsrId) { showToast('Select a DSR first', 'warning'); return; }

  const btn = document.getElementById('complete-btn');
  btn.disabled = true; btn.textContent = 'Processing…';
  stopScan();

  const data = await api('<?= rootPath() ?>/api/delivery.php?action=complete', 'POST', {
    order_id: parseInt(oid),
    dsr_id: parseInt(dsrId),
    scanned: scannedBoxes
  });

  if (data.success) {
    showToast('Order sent to van! 🚚');
    setTimeout(() => window.location.href = '<?= rootPath() ?>/manager/orders.php', 1500);
  } else {
    showToast(data.message || 'Error', 'error');
    btn.disabled = false; btn.textContent = '✅ Complete — Send to Van';
  }
}
</script>
