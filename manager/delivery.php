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
        <div class="flex gap-2">
          <a href="<?= rootPath() ?>/manager/delivery_scan.php" class="btn btn-primary" style="gap:6px;">
            <i class="fa-solid fa-mobile-screen-button"></i> Mobile Scan
          </a>
          <a href="<?= rootPath() ?>/manager/orders.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left mr-1"></i> Orders</a>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Controls + Scanner -->
        <div class="space-y-4">

          <!-- Order & DSR Selection -->
          <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
            <div>
              <label class="form-label">Select Pending SR(s) *</label>
              <div id="sr-container" class="form-input p-2 space-y-2 max-h-40 overflow-y-auto" style="height: auto; min-height: 42px;">
                 <div class="text-gray-400">Loading SRs...</div>
              </div>
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
            <h3 class="font-semibold text-gray-700 mb-3"><i class="fa-solid fa-camera mr-1"></i> QR Scanner</h3>
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
              <div id="scan-log-placeholder" class="text-gray-400 text-xs">Scanned boxes will appear here</div>
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
                <i class="fa-solid fa-circle-check mr-1"></i> Complete — Send to Van
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

// Load pending SRs on page load
window.addEventListener('DOMContentLoaded', async () => {
  const data = await api('<?= rootPath() ?>/api/delivery.php?action=pending_srs');
  const container  = document.getElementById('sr-container');
  container.innerHTML = '';
  if (!data.data || data.data.length === 0) {
      container.innerHTML = '<div class="text-gray-400">No pending orders.</div>';
      return;
  }
  data.data.forEach(s => {
    container.innerHTML += `<label class="flex items-center gap-2 cursor-pointer p-1 hover:bg-gray-50 rounded">
        <input type="checkbox" value="${s.id}" class="sr-checkbox rounded border-gray-300 text-primary focus:ring-primary" onchange="loadOrderItems()">
        <span>${s.sr_name}</span>
      </label>`;
  });
});

function getSelectedSRs() {
    return Array.from(document.querySelectorAll('.sr-checkbox:checked')).map(cb => parseInt(cb.value));
}

async function loadOrderItems() {
  const srIds = getSelectedSRs();
  orderItems   = [];
  scannedBoxes = [];
  document.getElementById('scan-log').innerHTML = '<div id="scan-log-placeholder" class="text-gray-400 text-xs">Scanned boxes will appear here</div>';

  if (srIds.length === 0) {
    document.getElementById('order-placeholder').style.display = 'block';
    document.getElementById('products-panel').style.display = 'none';
    document.getElementById('scan-btn').disabled = true;
    return;
  }

  const data = await api('<?= rootPath() ?>/api/delivery.php?action=order_items&sr_ids=' + srIds.join(','));
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

let isScanning = false;

async function processQRScan(uid) {
  const srIds = getSelectedSRs();
  if (srIds.length === 0) { showToast('Select at least one SR first', 'warning'); return; }

  // 1. Local Duplicate Check (Already fully scanned)
  if (scannedBoxes.some(s => s.qr_uid === uid)) {
    // showToast('Already scanned: ' + uid, 'info');
    return;
  }

  // 2. Processing Lock (Currently sending to API)
  if (isScanning) return;
  isScanning = true;

  const scannedIds = scannedBoxes.map(s => s.qr_id);
  const data = await api('<?= rootPath() ?>/api/delivery.php?action=scan_box', 'POST', {
    qr_uid: uid, sr_ids: srIds, scanned_ids: scannedIds
  });

  isScanning = false;

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

  addLog(uid, true, qr.product_name, qr.id, qr.product_id);
  checkComplete();
  document.getElementById('manual-qr').value = '';
}

function addLog(uid, success, msg, qrId = null, productId = null) {
  const log  = document.getElementById('scan-log');
  // Remove empty state
  const placeholder = document.getElementById('scan-log-placeholder');
  if (placeholder) placeholder.remove();
  
  const div  = document.createElement('div');
  div.className = `flex items-center justify-between gap-2 py-1 border-b border-gray-50`;
  if (qrId) {
    div.id = 'log-qr-' + qrId;
  }
  
  let undoButton = '';
  if (success && qrId && productId) {
    undoButton = `
      <button type="button" onclick="undoScannedBox('${qrId}', '${productId}', '${uid}')" class="w-5 h-5 rounded bg-gray-100 hover:bg-red-50 text-red-500 flex items-center justify-center active:scale-75 transition ml-2 border border-gray-200" title="Undo Scan">
        <i class="fa-solid fa-rotate-left text-[9px]"></i>
      </button>
    `;
  }
  
  div.innerHTML = `
    <div class="flex items-center gap-2">
      <span class="${success ? 'text-green-500' : 'text-red-500'}">
        <i class="fa-solid fa-${success ? 'check' : 'xmark'}"></i>
      </span>
      <span class="font-mono text-xs">${uid}</span>
      <span class="text-gray-400 text-xs">${msg}</span>
    </div>
    ${undoButton}
  `;
  log.prepend(div);
}

function undoScannedBox(qrId, productId, uid) {
  // Find index in scannedBoxes
  const idx = scannedBoxes.findIndex(s => s.qr_id == qrId);
  if (idx === -1) return;

  // Remove from scannedBoxes array
  scannedBoxes.splice(idx, 1);

  // Update product row progress
  const item = orderItems.find(i => i.product_id == productId);
  if (item) {
    item.scanned = Math.max(item.scanned - 1, 0);
    const pct = Math.min((item.scanned / item.required) * 100, 100);
    const bar = document.getElementById('bar-' + productId);
    bar.style.width = pct + '%';
    bar.classList.remove('complete');
    
    document.getElementById('cnt-' + productId).textContent = item.scanned;
    
    const row = document.getElementById('prod-' + productId);
    if (item.scanned < item.required) {
      row.style.background = '';
    }
  }

  // Remove the row from the scan log DOM
  const logItem = document.getElementById(`log-qr-${qrId}`);
  if (logItem) {
    logItem.remove();
  }

  // Show empty state if scan log becomes empty
  const log = document.getElementById('scan-log');
  if (log.children.length === 0) {
    log.innerHTML = `<div id="scan-log-placeholder" class="text-gray-400 text-xs">Scanned boxes will appear here</div>`;
  }

  checkComplete();
  showToast(`Box scan ${uid.substring(0, 10)}... undone!`, 'info');
}

function checkComplete() {
  const hasScanned = scannedBoxes.length > 0;
  document.getElementById('complete-btn').disabled = !hasScanned;
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
  const srIds = getSelectedSRs();
  const dsrId = document.getElementById('dsr-select').value;
  if (!dsrId) { showToast('Select a DSR first', 'warning'); return; }

  // Warning check if not all boxes scanned
  const allDone = orderItems.length > 0 && orderItems.every(i => i.scanned >= i.required);
  if (!allDone) {
    if (!confirm('Warning: You have not scanned all the ordered boxes for the selected SRs. Are you sure you want to complete this delivery dispatch with ONLY the scanned boxes? Unscanned boxes will not be loaded onto the van.')) {
      return;
    }
  }

  const btn = document.getElementById('complete-btn');
  btn.disabled = true; btn.textContent = 'Processing…';
  stopScan();

  const data = await api('<?= rootPath() ?>/api/delivery.php?action=complete', 'POST', {
    sr_ids: srIds,
    dsr_id: parseInt(dsrId),
    scanned: scannedBoxes
  });

  if (data.success) {
    showToast('Orders sent to van!');
    setTimeout(() => window.location.href = '<?= rootPath() ?>/manager/orders.php', 1500);
  } else {
    showToast(data.message || 'Error', 'error');
    btn.disabled = false; btn.textContent = 'Complete — Send to Van';
  }
}
</script>
