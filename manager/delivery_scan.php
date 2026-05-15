<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Mobile Scan';
$pdo       = getDB();
$wid       = $_SESSION['warehouse_id'];
$dsrs      = $pdo->prepare('SELECT d.id, u.name FROM dsr d JOIN users u ON u.id=d.user_id WHERE d.warehouse_id=? AND d.status=1 ORDER BY u.name');
$dsrs->execute([$wid]); $dsrs = $dsrs->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Mobile Delivery Scan — Happy Bangladesh</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <script src="<?= rootPath() ?>/assets/js/app.js" defer></script>
  <script src="<?= rootPath() ?>/assets/js/qr.js" defer></script>
  <style>
    * { box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background: #0F172A; min-height: 100vh; margin: 0; }

    /* ── Header ── */
    .mob-header {
      background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
      padding: 14px 16px 14px;
      display: flex; align-items: center; gap: 12px;
      position: sticky; top: 0; z-index: 50;
      box-shadow: 0 4px 20px rgba(79,70,229,0.4);
    }
    .mob-header .back-btn {
      width: 36px; height: 36px; border-radius: 10px;
      background: rgba(255,255,255,0.15);
      border: none; color: #fff; font-size: 18px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; text-decoration: none; flex-shrink: 0;
      transition: background 0.15s;
    }
    .mob-header .back-btn:hover { background: rgba(255,255,255,0.25); }
    .mob-header .title { color: #fff; font-size: 17px; font-weight: 700; flex: 1; }
    .mob-header .status-dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: #10B981; flex-shrink: 0;
      box-shadow: 0 0 0 3px rgba(16,185,129,0.3);
    }

    /* ── Main body ── */
    .mob-body { padding: 14px; display: flex; flex-direction: column; gap: 14px; }

    /* ── Card ── */
    .mob-card {
      background: #1E293B; border-radius: 16px;
      border: 1px solid rgba(255,255,255,0.07);
      overflow: hidden;
    }
    .mob-card-header {
      padding: 12px 16px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      display: flex; align-items: center; gap: 8px;
    }
    .mob-card-header .icon {
      width: 32px; height: 32px; border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      font-size: 15px; flex-shrink: 0;
    }
    .mob-card-header .label {
      font-size: 13px; font-weight: 600; color: #E2E8F0;
    }
    .mob-card-body { padding: 12px 16px; }

    /* ── Scanner box ── */
    #qr-reader {
      border-radius: 12px; overflow: hidden;
      background: #000;
    }
    #qr-reader video { width: 100% !important; height: auto !important; }
    .scanner-actions { display: flex; gap: 10px; margin-top: 10px; }

    /* ── Inputs ── */
    .mob-input {
      width: 100%; background: #0F172A; border: 1.5px solid rgba(255,255,255,0.1);
      border-radius: 10px; padding: 11px 14px;
      color: #E2E8F0; font-size: 14px; font-family: 'Inter', sans-serif;
      outline: none; transition: border-color 0.15s;
      appearance: none; -webkit-appearance: none;
    }
    .mob-input:focus { border-color: #4F46E5; box-shadow: 0 0 0 3px rgba(79,70,229,0.2); }
    .mob-input option { background: #1E293B; }

    /* ── Buttons ── */
    .mob-btn {
      flex: 1; padding: 11px 14px; border-radius: 10px;
      font-size: 13px; font-weight: 600; border: none; cursor: pointer;
      transition: all 0.15s; display: flex; align-items: center;
      justify-content: center; gap: 6px;
    }
    .mob-btn-primary { background: #4F46E5; color: #fff; }
    .mob-btn-primary:hover { background: #4338CA; }
    .mob-btn-primary:disabled { background: #334155; color: #64748B; cursor: not-allowed; }
    .mob-btn-stop { background: rgba(239,68,68,0.15); color: #F87171; border: 1px solid rgba(239,68,68,0.3); }
    .mob-btn-stop:hover { background: rgba(239,68,68,0.25); }
    .mob-btn-success {
      width: 100%; padding: 15px; border-radius: 12px;
      font-size: 15px; font-weight: 700; border: none; cursor: pointer;
      background: linear-gradient(135deg, #10B981, #059669);
      color: #fff; transition: all 0.2s;
      box-shadow: 0 4px 15px rgba(16,185,129,0.35);
    }
    .mob-btn-success:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(16,185,129,0.45); }
    .mob-btn-success:disabled { background: #1E293B; color: #475569; box-shadow: none; cursor: not-allowed; transform: none; }

    /* ── Product row ── */
    .product-row {
      padding: 12px 16px;
      border-bottom: 1px solid rgba(255,255,255,0.05);
      transition: background 0.2s;
    }
    .product-row:last-child { border-bottom: none; }
    .product-row.done { background: rgba(16,185,129,0.08); }
    .product-name { font-size: 13px; font-weight: 600; color: #E2E8F0; }
    .product-count { font-size: 12px; color: #94A3B8; }
    .product-count.done { color: #10B981; font-weight: 600; }

    /* Progress bar */
    .prog-track {
      height: 5px; background: rgba(255,255,255,0.08);
      border-radius: 999px; margin-top: 8px; overflow: hidden;
    }
    .prog-fill {
      height: 100%; border-radius: 999px;
      background: linear-gradient(90deg, #4F46E5, #7C3AED);
      transition: width 0.4s ease;
    }
    .prog-fill.complete { background: linear-gradient(90deg, #10B981, #059669); }

    /* ── Scan log ── */
    .log-item {
      display: flex; align-items: center; gap: 8px;
      padding: 7px 0; border-bottom: 1px solid rgba(255,255,255,0.05);
      font-size: 12px;
    }
    .log-item:last-child { border-bottom: none; }
    .log-icon { width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; flex-shrink: 0; }
    .log-icon.ok { background: rgba(16,185,129,0.15); color: #10B981; }
    .log-icon.err { background: rgba(239,68,68,0.15); color: #F87171; }
    .log-uid { font-family: monospace; color: #94A3B8; font-size: 11px; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .log-msg { color: #64748B; font-size: 11px; text-align: right; flex-shrink: 0; max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    /* ── Toast ── */
    #mob-toast {
      position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%) translateY(60px);
      background: #1E293B; color: #E2E8F0; padding: 12px 20px;
      border-radius: 12px; font-size: 13px; font-weight: 500;
      box-shadow: 0 8px 30px rgba(0,0,0,0.4); z-index: 999;
      transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s;
      opacity: 0; white-space: nowrap; max-width: 90vw;
      display: flex; align-items: center; gap: 8px;
    }
    #mob-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
    #mob-toast.success { border-left: 3px solid #10B981; }
    #mob-toast.error { border-left: 3px solid #EF4444; }
    #mob-toast.warning { border-left: 3px solid #F59E0B; }

    /* ── Pulse scan animation ── */
    @keyframes scan-pulse {
      0%, 100% { box-shadow: 0 0 0 0 rgba(79,70,229,0.4); }
      50% { box-shadow: 0 0 0 8px rgba(79,70,229,0); }
    }
    .scanning-active { animation: scan-pulse 1.5s ease-in-out infinite; border: 2px solid #4F46E5 !important; }

    /* ── Empty state ── */
    .empty-state { text-align: center; padding: 24px 16px; color: #475569; font-size: 13px; }
    .empty-state .emoji { font-size: 32px; display: block; margin-bottom: 8px; }
  </style>
</head>
<body>

<!-- ── Header ── -->
<div class="mob-header">
  <a href="<?= rootPath() ?>/manager/delivery.php" class="back-btn">←</a>
  <span class="title">🚚 Delivery Scan</span>
  <div class="status-dot" id="conn-dot"></div>
</div>

<div class="mob-body">

  <!-- ── QR Scanner Card ── -->
  <div class="mob-card">
    <div class="mob-card-header">
      <div class="icon" style="background:rgba(79,70,229,0.15)">📷</div>
      <span class="label">QR Camera Scanner</span>
    </div>
    <div class="mob-card-body">
      <div id="qr-reader" style="min-height:60px; border-radius:12px; overflow:hidden; background:#0a0a0a;"></div>
      <div class="scanner-actions">
        <button id="scan-btn" onclick="startMobileScan()" class="mob-btn mob-btn-primary" disabled>
          📷 Start Camera
        </button>
        <button id="stop-btn" onclick="stopMobileScan()" class="mob-btn mob-btn-stop" style="display:none">
          ⏹ Stop
        </button>
      </div>
      <!-- Manual input -->
      <div style="margin-top:10px;">
        <input id="manual-qr" type="text" class="mob-input scanner-input"
          placeholder="Or type/scan QR UID here…"
          onkeydown="handleManualInput(event)" />
      </div>
    </div>
  </div>

  <!-- ── Order Selection Card ── -->
  <div class="mob-card">
    <div class="mob-card-header">
      <div class="icon" style="background:rgba(245,158,11,0.15)">📦</div>
      <span class="label">Select Order</span>
    </div>
    <div class="mob-card-body" style="padding-top:10px; padding-bottom:10px;">
      <select id="order-select" class="mob-input" onchange="loadOrderItems()">
        <option value="">Loading orders…</option>
      </select>
    </div>
  </div>

  <!-- ── DSR Selection Card ── -->
  <div class="mob-card">
    <div class="mob-card-header">
      <div class="icon" style="background:rgba(16,185,129,0.15)">🧑‍✈️</div>
      <span class="label">Select DSR (Driver)</span>
    </div>
    <div class="mob-card-body" style="padding-top:10px; padding-bottom:10px;">
      <select id="dsr-select" class="mob-input">
        <option value="">Select DSR</option>
        <?php foreach ($dsrs as $d): ?>
          <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- ── Products Panel ── -->
  <div class="mob-card" id="products-panel" style="display:none">
    <div class="mob-card-header" style="justify-content:space-between">
      <div style="display:flex; align-items:center; gap:8px;">
        <div class="icon" style="background:rgba(124,58,237,0.15)">📋</div>
        <span class="label">Products to Load</span>
      </div>
      <span id="total-badge" style="font-size:11px; color:#64748B; background:rgba(255,255,255,0.06); padding:3px 9px; border-radius:999px;">0 / 0</span>
    </div>
    <div id="products-list"></div>
  </div>

  <!-- ── Placeholder ── -->
  <div class="mob-card" id="order-placeholder">
    <div class="empty-state">
      <span class="emoji">📦</span>
      Select an order above to begin scanning boxes
    </div>
  </div>

  <!-- ── Scan Log ── -->
  <div class="mob-card">
    <div class="mob-card-header" style="justify-content:space-between">
      <div style="display:flex; align-items:center; gap:8px;">
        <div class="icon" style="background:rgba(15,23,42,0.5)">🗂️</div>
        <span class="label">Scan Log</span>
      </div>
      <span id="scan-count-badge" style="font-size:11px; color:#10B981; font-weight:600;">0 scanned</span>
    </div>
    <div class="mob-card-body" style="max-height:200px; overflow-y:auto; padding-top:4px; padding-bottom:4px;">
      <div id="scan-log">
        <div class="empty-state" style="padding:12px 0;">
          <span style="font-size:20px; display:block; margin-bottom:4px;">🔍</span>
          Scanned boxes will appear here
        </div>
      </div>
    </div>
  </div>

  <!-- ── Complete Button ── -->
  <div id="complete-section" style="display:none; padding-bottom:20px;">
    <button id="complete-btn" onclick="completeDelivery()" class="mob-btn-success" disabled>
      ✅ Complete — Send to Van
    </button>
  </div>

</div>

<!-- Toast -->
<div id="mob-toast"></div>

<script>
let orderItems   = [];
let scannedBoxes = [];
let isScanning   = false;
let scanCount    = 0;

// ── Toast ──
function mobToast(msg, type = 'success') {
  const t = document.getElementById('mob-toast');
  t.textContent = '';
  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
  t.innerHTML = `<span>${icons[type] || '✅'}</span><span>${msg}</span>`;
  t.className = `show ${type}`;
  clearTimeout(t._timer);
  t._timer = setTimeout(() => { t.className = ''; }, 3000);
}

// Override global showToast with mobile version
window.showToast = mobToast;

// ── Load orders on init ──
window.addEventListener('DOMContentLoaded', async () => {
  const data = await api('<?= rootPath() ?>/api/delivery.php?action=pending_orders');
  const sel  = document.getElementById('order-select');
  sel.innerHTML = '<option value="">Select Order</option>';
  (data.data || []).forEach(o => {
    sel.innerHTML += `<option value="${o.id}">#${String(o.id).padStart(4,'0')} — ${o.sr_name} (${o.company_name}) ${o.order_date}</option>`;
  });
});

// ── Load order items ──
async function loadOrderItems() {
  const oid = document.getElementById('order-select').value;
  orderItems   = [];
  scannedBoxes = [];
  scanCount    = 0;
  document.getElementById('scan-log').innerHTML = '<div class="empty-state" style="padding:12px 0;"><span style="font-size:20px; display:block; margin-bottom:4px;">🔍</span>Scanned boxes will appear here</div>';
  document.getElementById('scan-count-badge').textContent = '0 scanned';

  if (!oid) {
    document.getElementById('order-placeholder').style.display = 'block';
    document.getElementById('products-panel').style.display = 'none';
    document.getElementById('complete-section').style.display = 'none';
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
    div.className = 'product-row';
    div.innerHTML = `
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
        <div class="product-name">${item.product_name}</div>
        <div class="product-count" id="cnt-${item.product_id}">0 / ${required} boxes</div>
      </div>
      <div class="prog-track">
        <div class="prog-fill" id="bar-${item.product_id}" style="width:0%"></div>
      </div>
    `;
    list.appendChild(div);
  });

  updateTotalBadge();
  document.getElementById('order-placeholder').style.display = 'none';
  document.getElementById('products-panel').style.display = 'block';
  document.getElementById('complete-section').style.display = 'block';
  document.getElementById('scan-btn').disabled = false;
  checkComplete();
}

// ── Update header badge ──
function updateTotalBadge() {
  const totalScanned = orderItems.reduce((a, i) => a + i.scanned, 0);
  const totalRequired = orderItems.reduce((a, i) => a + i.required, 0);
  document.getElementById('total-badge').textContent = `${totalScanned} / ${totalRequired}`;
}

// ── Process scan ──
async function processQRScan(uid) {
  const oid = document.getElementById('order-select').value;
  if (!oid) { mobToast('Select an order first', 'warning'); return; }
  if (scannedBoxes.some(s => s.qr_uid === uid)) return;
  if (isScanning) return;
  isScanning = true;

  const data = await api('<?= rootPath() ?>/api/delivery.php?action=scan_box', 'POST', {
    qr_uid: uid, order_id: parseInt(oid), scanned_ids: scannedBoxes.map(s => s.qr_id)
  });
  isScanning = false;

  if (!data.success) {
    const toastType = data.type === 'duplicate' ? 'warning' : 'error';
    mobToast(data.message, toastType);
    addLog(uid, false, data.message);
    document.getElementById('manual-qr').value = '';
    return;
  }

  const qr = data.data;
  scannedBoxes.push({ qr_id: qr.id, product_id: qr.product_id, qr_uid: uid, pieces_total: qr.pieces_total });
  scanCount++;
  document.getElementById('scan-count-badge').textContent = scanCount + ' scanned';

  // Update progress row
  const item = orderItems.find(i => i.product_id == qr.product_id);
  if (item) {
    item.scanned++;
    const pct = Math.min((item.scanned / item.required) * 100, 100);
    const bar = document.getElementById('bar-' + item.product_id);
    bar.style.width = pct + '%';
    const cntEl = document.getElementById('cnt-' + item.product_id);
    cntEl.textContent = `${item.scanned} / ${item.required} boxes`;
    if (item.scanned >= item.required) {
      bar.classList.add('complete');
      document.getElementById('prod-' + item.product_id).classList.add('done');
      cntEl.classList.add('done');
      mobToast(`✅ ${item.product_name} — Complete!`, 'success');
    } else {
      mobToast(`📦 ${qr.product_name} scanned`, 'success');
    }
  }

  updateTotalBadge();
  addLog(uid, true, qr.product_name);
  checkComplete();
  document.getElementById('manual-qr').value = '';
}

// ── Scan log ──
function addLog(uid, ok, msg) {
  const log = document.getElementById('scan-log');
  // Remove empty state
  if (log.querySelector('.empty-state')) log.innerHTML = '';
  const div = document.createElement('div');
  div.className = 'log-item';
  div.innerHTML = `
    <div class="log-icon ${ok ? 'ok' : 'err'}">${ok ? '✓' : '✗'}</div>
    <span class="log-uid">${uid}</span>
    <span class="log-msg">${msg}</span>
  `;
  log.prepend(div);
}

// ── Check all done ──
function checkComplete() {
  const allDone = orderItems.length > 0 && orderItems.every(i => i.scanned >= i.required);
  document.getElementById('complete-btn').disabled = !allDone;
}

// ── Camera controls ──
function startMobileScan() {
  document.getElementById('scan-btn').style.display = 'none';
  document.getElementById('stop-btn').style.display = 'flex';
  const reader = document.getElementById('qr-reader');
  reader.classList.add('scanning-active');
  startCameraScanner('qr-reader', processQRScan);
}

function stopMobileScan() {
  stopCameraScanner();
  document.getElementById('scan-btn').style.display = 'flex';
  document.getElementById('stop-btn').style.display = 'none';
  document.getElementById('qr-reader').classList.remove('scanning-active');
}

function handleManualInput(e) {
  if (e.key === 'Enter') {
    const val = document.getElementById('manual-qr').value.trim();
    if (val) processQRScan(val);
  }
}

registerUsbScanner(processQRScan);

// ── Complete delivery ──
async function completeDelivery() {
  const oid   = document.getElementById('order-select').value;
  const dsrId = document.getElementById('dsr-select').value;
  if (!dsrId) { mobToast('Select a DSR first', 'warning'); return; }

  const btn = document.getElementById('complete-btn');
  btn.disabled = true; btn.textContent = '⏳ Processing…';
  stopMobileScan();

  const data = await api('<?= rootPath() ?>/api/delivery.php?action=complete', 'POST', {
    order_id: parseInt(oid),
    dsr_id: parseInt(dsrId),
    scanned: scannedBoxes
  });

  if (data.success) {
    mobToast('Order sent to van! 🚚', 'success');
    btn.textContent = '🚚 Done!';
    setTimeout(() => window.location.href = '<?= rootPath() ?>/manager/delivery.php', 1800);
  } else {
    mobToast(data.message || 'Error', 'error');
    btn.disabled = false; btn.textContent = '✅ Complete — Send to Van';
  }
}
</script>
</body>
</html>
