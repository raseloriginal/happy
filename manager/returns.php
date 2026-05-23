<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Returns';
$pdo       = getDB();
$wid       = $_SESSION['warehouse_id'];
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body max-w-5xl">
      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Returns</h2><p class="text-sm text-gray-500">Process returned products from DSR</p></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Select Dispatch -->
        <div class="space-y-4">
          <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <label class="form-label">Select Dispatch *</label>
            <select id="dispatch-select" class="form-input" onchange="loadDispatch()">
              <option value="">Loading…</option>
            </select>
          </div>

          <!-- Scan Return -->
          <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5" id="scanner-box" style="display:none">
            <h3 class="font-semibold text-gray-700 mb-3">QR Scan Return</h3>
            <div id="return-qr-reader" class="rounded-lg overflow-hidden mb-3"></div>
            <div class="flex gap-2 mb-3">
              <button onclick="startReturnScan()" class="btn btn-primary btn-sm flex-1"><i class="fa-solid fa-camera mr-1"></i> Camera</button>
              <button onclick="stopCameraScanner()" class="btn btn-ghost btn-sm flex-1">Stop</button>
            </div>
            <input id="manual-return" type="text" class="form-input scanner-input" placeholder="Scan QR UID…" onkeydown="if(event.key==='Enter'){scanReturn(this.value.trim());this.value=''}" />
          </div>
        </div>

        <!-- Right: Box Table -->
        <div class="lg:col-span-2">
          <div id="no-dispatch" class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center text-gray-400">Select a dispatch to view boxes</div>
          <div id="dispatch-panel" style="display:none">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
              <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-semibold text-gray-700">Dispatched Boxes</h3>
                <button onclick="openModal('custom-modal')" class="btn btn-warning btn-sm">Custom Return</button>
              </div>
              <div class="overflow-x-auto">
                <table class="data-table">
                  <thead><tr><th>QR Code</th><th>Product</th><th>Pieces Out</th><th>Remaining</th><th>Return Qty</th><th>Status</th></tr></thead>
                  <tbody id="boxes-body"></tbody>
                </table>
              </div>
            </div>
            <div class="mt-4">
              <button onclick="completeReturn()" id="complete-btn" class="btn btn-success w-full py-3">Complete Return</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Custom Return Modal -->
<div id="custom-modal" class="modal-overlay" style="display:none">
  <div class="modal-box modal-box-lg">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Custom Return</h3><button onclick="closeModal('custom-modal')" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark"></i></button></div>
    <p class="text-sm text-gray-500 mb-4">Enter partial quantities for each box manually.</p>
    <div id="custom-boxes" class="space-y-2 max-h-96 overflow-y-auto"></div>
    <div class="flex gap-2 mt-4">
      <button onclick="applyCustom()" class="btn btn-primary flex-1">Apply</button>
      <button onclick="closeModal('custom-modal')" class="btn btn-ghost flex-1">Cancel</button>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
let dispatchBoxes = [];

window.addEventListener('DOMContentLoaded', async () => {
  const data = await api('<?= rootPath() ?>/api/returns.php?action=dispatches');
  const sel  = document.getElementById('dispatch-select');
  sel.innerHTML = '<option value="">Select Dispatch</option>';
  (data.data || []).forEach(d => {
    sel.innerHTML += `<option value="${d.id}">#${d.id} — ${d.dsr_name} (${d.dispatch_date})</option>`;
  });
});

async function loadDispatch() {
  const did = document.getElementById('dispatch-select').value;
  if (!did) { document.getElementById('no-dispatch').style.display = 'block'; document.getElementById('dispatch-panel').style.display = 'none'; return; }
  const data = await api('<?= rootPath() ?>/api/returns.php?action=dispatch_boxes&dispatch_id=' + did);
  dispatchBoxes = data.data || [];
  renderBoxTable();
  document.getElementById('no-dispatch').style.display = 'none';
  document.getElementById('dispatch-panel').style.display = 'block';
  document.getElementById('scanner-box').style.display = 'block';
}

function renderBoxTable() {
  const tbody = document.getElementById('boxes-body');
  tbody.innerHTML = '';
  dispatchBoxes.forEach(box => {
    const returned = box.returnQty !== undefined ? box.returnQty : 0;
    const tr = document.createElement('tr');
    tr.id   = 'box-' + box.qr_code_id;
    tr.className = returned > 0 ? 'bg-green-50' : '';
    tr.innerHTML = `
      <td class="font-mono text-xs">${box.qr_uid}</td>
      <td>${box.product_name}</td>
      <td class="text-right">${box.qty_out}</td>
      <td class="text-right">${box.qty_out}</td>
      <td class="text-right font-semibold text-green-700">${returned}</td>
      <td><span class="badge ${box.qr_status === 'dispatched' ? 'badge-warning' : 'badge-success'}">${box.qr_status}</span></td>
    `;
    tbody.appendChild(tr);
  });
}

async function scanReturn(uid) {
  if (!uid) return;
  uid = uid.trim().toUpperCase();
  const qr = dispatchBoxes.find(b => b.qr_uid.trim().toUpperCase() === uid);
  if (!qr) { showToast('QR not in this dispatch', 'error'); return; }
  if (qr.returnQty > 0) return;
  qr.returnQty = parseInt(qr.qty_out) || 0;
  qr.type = 'scan';
  renderBoxTable();
  showToast('Marked: ' + uid);
}

function startReturnScan() { startCameraScanner('return-qr-reader', scanReturn); }

function openCustomModal() {
  const container = document.getElementById('custom-boxes');
  container.innerHTML = '';
  dispatchBoxes.forEach(box => {
    const qtyOut = parseInt(box.qty_out) || 0;
    container.innerHTML += `
      <div class="flex items-center gap-3 p-2 border border-gray-100 rounded-lg">
        <div class="flex-1">
          <div class="font-mono text-xs text-gray-600">${box.qr_uid}</div>
          <div class="text-sm">${box.product_name} — ${qtyOut} remaining</div>
        </div>
        <input type="number" id="custom-qty-${box.qr_code_id}" class="form-input w-24" min="0" max="${qtyOut}" value="${box.returnQty || 0}" />
      </div>`;
  });
}

function applyCustom() {
  dispatchBoxes.forEach(box => {
    const el = document.getElementById('custom-qty-' + box.qr_code_id);
    if (el) { box.returnQty = parseInt(el.value) || 0; box.type = 'custom'; }
  });
  renderBoxTable();
  closeModal('custom-modal');
  showToast('Custom quantities applied');
}

document.getElementById('custom-modal').addEventListener('click', function(e) {
  if (e.target === this) { /* keep open */ }
});
// Override openModal for custom
function openModal(id) {
  const el = document.getElementById(id);
  if (el) { el.style.display = 'flex'; if (id === 'custom-modal') openCustomModal(); }
}

async function completeReturn() {
  const did   = document.getElementById('dispatch-select').value;
  const items = dispatchBoxes.filter(b => (b.returnQty || 0) > 0).map(b => ({
    qr_code_id: b.qr_code_id, product_id: b.product_id, qty_in: b.returnQty, type: b.type || 'scan'
  }));
  
  if (items.length === 0) { 
    if (!confirm('You have scanned 0 returns. Are you sure you want to complete this dispatch with NO returned products?')) return;
  } else {
    if (!confirm(`Are you sure you want to process returns for ${items.length} boxes?`)) return;
  }

  const btn = document.getElementById('complete-btn');
  btn.disabled = true;
  btn.innerText = 'Processing...';

  const data = await api('<?= rootPath() ?>/api/returns.php?action=complete', 'POST', { dispatch_id: parseInt(did), items });
  if (data.success) { 
    showToast('Return completed!'); 
    setTimeout(() => location.reload(), 1500); 
  } else {
    showToast(data.message || 'Error', 'error');
    btn.disabled = false;
    btn.innerText = 'Complete Return';
  }
}

registerUsbScanner(scanReturn);
</script>
