<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Ready Sale Scan';
$pdo       = getDB();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Ready Sale Scan — Happy Bangladesh</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <script src="<?= rootPath() ?>/assets/js/app.js" defer></script>
  <link rel="stylesheet" href="<?= rootPath() ?>/assets/css/scanner.css" />
  <style>
    body { 
      background: #0F172A; 
      color: #E2E8F0; 
      margin: 0; 
      display: flex; 
      flex-direction: column; 
      height: 100vh; 
      overflow: hidden;
    }
    
    /* ── Mobile Header ── */
    .app-header {
      background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
      padding: 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 4px 20px rgba(79, 70, 229, 0.4);
      z-index: 50;
    }
    .back-btn {
      width: 36px; height: 36px; border-radius: 12px;
      background: rgba(255, 255, 255, 0.15);
      border: none; color: #fff; display: flex;
      align-items: center; justify-content: center;
      text-decoration: none; font-size: 18px;
    }
    .app-title { font-weight: 700; font-size: 18px; color: #fff; flex: 1; }

    /* ── Scanner Viewport Override ── */
    .mobile-scanner-container {
      position: relative;
      width: 100%;
      height: 45vh;
      background: #000;
      overflow: hidden;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    #ready-scan-reader { width: 100% !important; height: 100% !important; }
    #ready-scan-reader video { object-fit: cover !important; }

    /* ── Content Area ── */
    .app-content {
      flex: 1;
      overflow-y: auto;
      padding: 16px;
      display: flex;
      flex-direction: column;
      gap: 12px;
      background: radial-gradient(circle at top, rgba(79, 70, 229, 0.05), transparent 40%);
    }

    /* ── Scanned List ── */
    .scanned-list {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    /* ── Floating Footer ── */
    .app-footer {
      padding: 12px 16px 24px;
      background: rgba(15, 23, 42, 0.95);
      backdrop-filter: blur(10px);
      border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .status-badge {
      font-size: 11px;
      background: rgba(79, 70, 229, 0.15);
      color: #818CF8;
      padding: 2px 10px;
      border-radius: 999px;
      font-weight: 600;
      border: 1px solid rgba(79, 70, 229, 0.3);
    }

    /* Success Flash Override */
    .success-flash { z-index: 100; }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="app-header">
    <a href="<?= rootPath() ?>/manager/orders.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
    <span class="app-title"><i class="fa-solid fa-qrcode mr-2"></i> Ready Sale</span>
    <div id="sr-badge-container" style="display:none">
      <span class="status-badge" id="sr-name-badge">SR: Loading...</span>
    </div>
  </div>

  <!-- Scanner -->
  <div class="mobile-scanner-container">
    <div id="ready-scan-reader"></div>
    <div class="scanner-laser"></div>
    <div id="success-flash" class="success-flash"></div>
  </div>

  <!-- Content -->
  <div class="app-content">
    <div class="scanned-list" id="scanned-items-list">
      <div class="text-center py-20 text-gray-500 opacity-50" id="empty-scan-msg">
        <i class="fa-solid fa-barcode text-5xl mb-4 block"></i>
        <p class="text-sm font-medium">Scan QR codes to start order</p>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div class="app-footer">
    <button onclick="saveReadyOrder()" id="complete-scan-btn" class="btn-complete w-full" disabled style="height: 54px; font-size: 16px;">
      <i class="fa-solid fa-circle-check mr-2"></i> Complete Order
    </button>
  </div>

<script>
let scannedData = {};
let scannedQrIds = [];
let activeScanner = null;
let selectedSrId = null;

window.addEventListener('DOMContentLoaded', () => {
  startReadyScanner();
});

function startReadyScanner() {
  activeScanner = new Html5Qrcode("ready-scan-reader");
  activeScanner.start(
    { facingMode: "environment" },
    { fps: 15, qrbox: { width: 320, height: 320 } },
    (decodedText) => handleReadyScan(decodedText),
    (errorMessage) => {}
  ).catch(err => {
    alert('Camera error: ' + err);
  });
}

async function handleReadyScan(uid) {
  if (window._scanning) return;
  window._scanning = true;
  setTimeout(() => window._scanning = false, 1000);

  const url = `<?= rootPath() ?>/api/orders.php?action=scan_ready_sale&qr_uid=${uid}` + (selectedSrId ? `&sr_id=${selectedSrId}` : '');
  const res = await api(url);
  
  if (!res.success) {
    triggerShake();
    showToast(res.message, 'error');
    return;
  }

  const p = res.data;
  
  // Strict Duplicate Check
  if (scannedQrIds.includes(p.qr_id)) {
    showToast('Box already scanned', 'warning');
    return;
  }

  if (!selectedSrId) {
    selectedSrId = p.sr_id;
    document.getElementById('sr-badge-container').style.display = 'block';
    document.getElementById('sr-name-badge').textContent = 'Order Active';
  }

  triggerFlash();
  scannedQrIds.push(p.qr_id);

  const pieces = parseInt(p.scanned_pieces) || 0;

  if (!scannedData[p.id]) {
    scannedData[p.id] = { name: p.name, qty: pieces, price: p.selling_price, ppb: p.pieces_per_box };
    renderScannedItem(p.id, true);
  } else {
    scannedData[p.id].qty = parseInt(scannedData[p.id].qty) + pieces;
    renderScannedItem(p.id, false);
  }
  
  document.getElementById('complete-scan-btn').disabled = false;
  document.getElementById('empty-scan-msg').style.display = 'none';
}

function renderScannedItem(pid, isNew) {
  const container = document.getElementById('scanned-items-list');
  let row = document.getElementById(`scan-row-${pid}`);
  const item = scannedData[pid];
  if (!row) {
    row = document.createElement('div');
    row.id = `scan-row-${pid}`;
    row.className = 'scanned-item';
    row.style.marginBottom = '10px';
    container.prepend(row);
  }
  row.innerHTML = `<div class="scanned-info"><div class="scanned-name">${item.name}</div><div class="scanned-meta">${item.ppb} pcs/box • ৳${item.price}/pcs</div></div><div class="scanned-qty-badge" id="qty-badge-${pid}">${item.qty} pcs</div>`;
  if (!isNew) {
    row.classList.remove('updating'); void row.offsetWidth; row.classList.add('updating');
    const badge = document.getElementById(`qty-badge-${pid}`);
    badge.classList.add('bump'); setTimeout(() => badge.classList.remove('bump'), 300);
  }
}

function triggerFlash() {
  const flash = document.getElementById('success-flash');
  flash.classList.remove('trigger'); void flash.offsetWidth; flash.classList.add('trigger');
}

function triggerShake() {
  document.body.classList.remove('shake');
  void document.body.offsetWidth;
  document.body.classList.add('shake');
}

async function saveReadyOrder() {
  const items = [];
  for (const pid in scannedData) {
    items.push({ product_id: pid, qty_pieces: scannedData[pid].qty });
  }
  
  const btn = document.getElementById('complete-scan-btn');
  btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Saving Order…';
  
  const data = await api('<?= rootPath() ?>/api/orders.php', 'POST', {
    sr_id: selectedSrId,
    order_date: new Date().toISOString().split('T')[0],
    items
  });

  if (data.success) {
    showToast('Order created successfully!');
    setTimeout(() => window.location.href = '<?= rootPath() ?>/manager/orders.php', 1500);
  } else {
    showToast(data.message || 'Error', 'error');
    btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-circle-check mr-2"></i> Complete Order';
  }
}

// Minimal toast for mobile
function showToast(msg, type = 'success') {
  if (window.mobToast) { window.mobToast(msg, type); return; }
  // Fallback if app.js not fully loaded
  const toast = document.createElement('div');
  toast.style = "position:fixed; bottom:100px; left:50%; transform:translateX(-50%); background:#1E293B; color:#fff; padding:12px 20px; border-radius:12px; z-index:1000; font-size:14px; box-shadow:0 10px 30px rgba(0,0,0,0.5);";
  toast.textContent = msg;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}
</script>
</body>
</html>
