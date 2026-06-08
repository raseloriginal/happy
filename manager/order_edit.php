<?php
// manager/order_edit.php — Edit existing order (routing via admin approval queue)
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');

$pdo = getDB();
$id  = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . rootPath() . '/manager/orders.php');
    exit;
}

// Fetch order
$stmt = $pdo->prepare('SELECT o.*, u.name as sr_name, c.name as company_name FROM orders o JOIN sr s ON s.id=o.sr_id JOIN users u ON u.id=s.user_id JOIN companies c ON c.id=o.company_id WHERE o.id=?');
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . rootPath() . '/manager/orders.php');
    exit;
}

$pageTitle = 'Edit Order #' . str_pad($id, 4, '0', STR_PAD_LEFT);

// Verify status
if ($order['status'] !== 'pending' && $order['status'] !== 'out_for_delivery') {
    $errorMsg = "Only pending or out_for_delivery orders can be edited. Current status: " . ucfirst($order['status']);
}

$wid = $_SESSION['warehouse_id'];

// If pending, fetch SRs and existing items
$srs = [];
$existingItems = [];
if ($order['status'] === 'pending') {
    $srs = $pdo->query('SELECT s.id, u.name, c.name as company_name FROM sr s JOIN users u ON u.id=s.user_id JOIN companies c ON c.id=s.company_id WHERE s.status=1 ORDER BY u.name')->fetchAll();
    
    $itemsStmt = $pdo->prepare('SELECT oi.*, p.name, p.pieces_per_box, p.selling_price, p.image FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?');
    $itemsStmt->execute([$id]);
    $existingItems = $itemsStmt->fetchAll();
}

// If out_for_delivery, fetch DSRs, current dispatch, and currently dispatched QR codes
$dsrs = [];
$currentDSR = 0;
$dispatchedQRs = [];
if ($order['status'] === 'out_for_delivery') {
    $dsrsStmt = $pdo->prepare('SELECT d.id, u.name FROM dsr d JOIN users u ON u.id=d.user_id WHERE d.warehouse_id=? AND d.status=1 ORDER BY u.name');
    $dsrsStmt->execute([$wid]);
    $dsrs = $dsrsStmt->fetchAll();

    $dispatchStmt = $pdo->prepare('SELECT id, dsr_id FROM dispatches WHERE order_id=?');
    $dispatchStmt->execute([$id]);
    $dispatch = $dispatchStmt->fetch();
    $currentDSR = intval($dispatch['dsr_id'] ?? 0);
    $dispatch_id = intval($dispatch['id'] ?? 0);

    if ($dispatch_id > 0) {
        $qrsStmt = $pdo->prepare("
            SELECT di.qr_code_id, di.qty_out, qr.qr_uid, p.name as product_name, p.id as product_id, p.pieces_per_box
            FROM dispatch_items di
            JOIN qr_codes qr ON qr.id = di.qr_code_id
            JOIN products p ON p.id = di.product_id
            WHERE di.dispatch_id = ?
        ");
        $qrsStmt->execute([$dispatch_id]);
        $dispatchedQRs = $qrsStmt->fetchAll();
    }
}

include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= rootPath() ?>/assets/css/scanner.css">
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body max-w-5xl">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-xl font-bold text-gray-800">Edit Order #<?= str_pad($id, 4, '0', STR_PAD_LEFT) ?></h2>
          <p class="text-sm text-gray-500">Submit order edit request for Admin Approval</p>
        </div>
        <a href="<?= rootPath() ?>/manager/orders.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left mr-1"></i> Orders</a>
      </div>

      <?php if (!empty($errorMsg)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl flex items-start gap-3">
          <i class="fa-solid fa-circle-exclamation mt-1"></i>
          <div>
            <div class="font-bold">Cannot Edit Order</div>
            <div class="text-sm mt-0.5"><?= htmlspecialchars($errorMsg) ?></div>
            <a href="<?= rootPath() ?>/manager/orders.php" class="btn btn-danger btn-sm mt-3 inline-block">Back to Orders</a>
          </div>
        </div>
      <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <form id="order-edit-form" class="space-y-6">
            <input type="hidden" id="order-id" value="<?= $id ?>" />
            <input type="hidden" id="order-status" value="<?= $order['status'] ?>" />

            <?php if ($order['status'] === 'out_for_delivery'): ?>
              <!-- OUT FOR DELIVERY MODE: Full Edit of Dispatch / Scans -->
              <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-4 text-sm text-blue-700">
                <i class="fa-solid fa-circle-info mr-1"></i> This order is currently <strong>Out for Delivery</strong>. You can change the Delivery Driver (DSR) and add/remove scanned QR code boxes for this dispatch.
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Driver & Scan controls -->
                <div class="space-y-4">
                  <div>
                    <label class="form-label font-bold text-gray-700">Select DSR (Driver) *</label>
                    <select id="dsr-select" class="form-input mt-1" required>
                      <option value="">Select DSR</option>
                      <?php foreach ($dsrs as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $d['id'] == $currentDSR ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <!-- QR Scanner / Manual Input -->
                  <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3 shadow-sm">
                    <h3 class="font-semibold text-gray-700"><i class="fa-solid fa-camera mr-1"></i> Add QR Box</h3>
                    <div id="qr-reader" class="rounded-lg overflow-hidden" style="min-height:80px"></div>
                    <div class="flex gap-2">
                      <button type="button" onclick="startScan()" id="scan-btn" class="btn btn-primary flex-1">Start Camera</button>
                      <button type="button" onclick="stopScan()" id="stop-btn" class="btn btn-ghost flex-1" style="display:none">Stop</button>
                    </div>
                    <div>
                      <label class="form-label">Manual QR Code Input</label>
                      <input id="manual-qr" type="text" class="form-input scanner-input" placeholder="Scan or type QR UID…" onkeydown="handleManualInput(event)" />
                    </div>
                  </div>
                </div>

                <!-- Dispatched items list -->
                <div class="lg:col-span-2 space-y-4">
                  <h3 class="font-semibold text-gray-700 flex items-center justify-between">
                    <span>Loaded QR Codes / Boxes</span>
                    <span class="badge badge-info" id="qr-count">0 boxes</span>
                  </h3>
                  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                    <table class="data-table">
                      <thead>
                        <tr>
                          <th>Product</th>
                          <th>QR Code UID</th>
                          <th class="text-right">Qty (pieces)</th>
                          <th class="w-10"></th>
                        </tr>
                      </thead>
                      <tbody id="dispatched-qrs-body">
                        <!-- Dispatched items injected here -->
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

            <?php else: ?>
              <!-- PENDING MODE: Full Edit of Order Details -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="form-label">Order Date *</label>
                  <input type="date" id="order-date" class="form-input" value="<?= htmlspecialchars($order['order_date']) ?>" required onchange="checkSRAvailability()" />
                </div>
                <div>
                  <label class="form-label">Select SR *</label>
                  <select id="sr-select" class="form-input" required onchange="loadSRProducts()">
                    <option value="">Select Sales Representative</option>
                    <?php foreach ($srs as $s): ?>
                      <option value="<?= $s['id'] ?>" <?= $s['id'] == $order['sr_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?> — <?= htmlspecialchars($s['company_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <!-- Product Rows -->
              <div>
                <div class="flex items-center justify-between mb-3">
                  <h3 class="font-semibold text-gray-700">Products</h3>
                  <button type="button" onclick="addProductRow()" class="btn btn-ghost btn-sm" id="add-row-btn">+ Add Product</button>
                </div>

                <div id="no-sr-msg" class="text-center py-8 text-gray-400 border-2 border-dashed border-gray-200 rounded-xl" style="display:none">
                  Select an SR above to load their products
                </div>

                <div id="products-section">
                  <div class="overflow-x-auto">
                    <table class="data-table" id="products-table">
                      <thead>
                        <tr>
                          <th class="w-1/2">Product</th>
                          <th class="w-24">Qty (boxes) *</th>
                          <th class="w-32 text-right">Price/Box</th>
                          <th class="w-32 text-right">Total</th>
                          <th class="w-10"></th>
                        </tr>
                      </thead>
                      <tbody id="products-body"></tbody>
                      <tfoot>
                        <tr class="bg-gray-50 font-bold">
                          <td colspan="3" class="text-right py-3">Grand Total:</td>
                          <td class="text-right py-3 text-indigo-600" id="grand-total">৳0.00</td>
                          <td></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <div class="flex justify-end pt-4 border-t border-gray-100 gap-3">
              <a href="<?= rootPath() ?>/manager/orders.php" class="btn btn-ghost">Cancel</a>
              <button type="submit" id="save-btn" class="btn btn-primary px-10">Request Edit Approval</button>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Product Selection Modal -->
<div id="product-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onclick="closeProductModal()"></div>
  <div class="bg-white rounded-xl shadow-xl w-full max-w-lg relative z-10 flex flex-col max-h-[90vh]">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <h3 class="font-bold text-gray-800">Select Product</h3>
      <button type="button" onclick="closeProductModal()" class="text-gray-400 hover:text-gray-600 transition"><i class="fa-solid fa-xmark text-xl"></i></button>
    </div>
    <div class="p-5 overflow-y-auto flex-1 space-y-3" id="product-modal-list">
      <!-- products injected here -->
    </div>
  </div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
let srProducts = [];
let existingItems = <?= json_encode($existingItems) ?>;
let dispatchedQRs = <?= json_encode($dispatchedQRs) ?>;
let initialLoad = true;
let html5QrCode = null;

// ─── PENDING ORDER EDIT LOGIC ────────────────────────────────────────────────
async function checkSRAvailability() {
  const dateVal = document.getElementById('order-date')?.value;
  if (!dateVal) return;

  const res = await api('<?= rootPath() ?>/api/orders.php?action=check_sr_availability&date=' + dateVal);
  if (!res || !res.success) return;

  const blockedIds = res.blocked_sr_ids || [];
  const srSelect = document.getElementById('sr-select');
  if (!srSelect) return;
  const currentVal = srSelect.value;
  let currentValBlocked = false;

  const originalSrId = <?= json_encode($order['sr_id']) ?>;

  Array.from(srSelect.options).forEach(opt => {
    if (!opt.value) return;
    
    if (!opt.dataset.originalText) {
      opt.dataset.originalText = opt.textContent;
    }

    const srId = parseInt(opt.value);
    if (blockedIds.includes(srId) && srId !== parseInt(originalSrId)) {
      opt.disabled = true;
      opt.textContent = opt.dataset.originalText + " (Already has order on this date)";
      if (currentVal == opt.value) {
        currentValBlocked = true;
      }
    } else {
      opt.disabled = false;
      opt.textContent = opt.dataset.originalText;
    }
  });

  if (currentValBlocked) {
    srSelect.value = "";
    loadSRProducts();
    showToast("The selected SR already has an order on this date and has been deselected.", "warning");
  }
}

async function loadSRProducts() {
  const srSelect = document.getElementById('sr-select');
  if (!srSelect) return;
  const srId = srSelect.value;
  const tbody = document.getElementById('products-body');
  tbody.innerHTML = '';
  document.getElementById('no-sr-msg').style.display = 'none';
  document.getElementById('products-section').style.display = 'none';
  document.getElementById('add-row-btn').disabled = true;
  document.getElementById('save-btn').disabled = true;
  document.getElementById('grand-total').textContent = '৳0.00';

  if (!srId) {
    document.getElementById('no-sr-msg').style.display = 'block';
    return;
  }

  const data = await api('<?= rootPath() ?>/api/orders.php?action=sr_products&sr_id=' + srId);
  srProducts  = data.data || [];

  if (srProducts.length === 0) {
    document.getElementById('no-sr-msg').textContent = 'No products for this SR\'s company yet.';
    document.getElementById('no-sr-msg').style.display = 'block';
    return;
  }

  document.getElementById('products-section').style.display = 'block';
  document.getElementById('add-row-btn').disabled = false;
  document.getElementById('save-btn').disabled = false;

  if (initialLoad) {
    initialLoad = false;
    existingItems.forEach(item => {
      addProductRow(item.product_id, item.qty_boxes_display);
    });
    if (existingItems.length === 0) {
      addProductRow();
    }
  } else {
    addProductRow();
  }
}

let currentRowForModal = null;

function openProductModal(triggerEl) {
  currentRowForModal = triggerEl.closest('tr');
  const list = document.getElementById('product-modal-list');
  list.innerHTML = '';
  
  const used = [];
  document.querySelectorAll('.product-sel-id').forEach(inp => {
    if (inp.value) used.push(inp.value);
  });
  const currentVal = currentRowForModal.querySelector('.product-sel-id').value;

  srProducts.forEach(p => {
    const isUsed = used.includes(String(p.id)) && String(p.id) !== currentVal;
    const opacity = isUsed ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:border-primary/50 hover:bg-indigo-50/30';
    const clickAttr = isUsed ? '' : `onclick="selectProductFromModal(${p.id})"`;
    const imgUrl = p.image ? ('<?= rootPath() ?>/' + p.image) : '<?= rootPath() ?>/assets/img/placeholder.png';
    
    list.innerHTML += `
      <div class="flex items-center gap-4 p-3 border border-gray-100 rounded-xl transition ${opacity}" ${clickAttr}>
        <img src="${imgUrl}" class="w-12 h-12 object-contain rounded-lg bg-gray-50 border border-gray-100 p-1" onerror="this.src='<?= rootPath() ?>/assets/img/placeholder.png'" />
        <div class="flex-1">
          <div class="font-bold text-gray-800 text-sm">${p.name}</div>
          <div class="text-xs text-gray-500">${p.pieces_per_box} pcs/box • ৳${p.selling_price}/pc</div>
        </div>
        ${isUsed ? '<span class="text-xs text-red-500 font-medium">Added</span>' : '<button type="button" class="btn btn-ghost btn-sm">Select</button>'}
      </div>
    `;
  });
  
  document.getElementById('product-modal').classList.remove('hidden');
}

function closeProductModal() {
  document.getElementById('product-modal').classList.add('hidden');
  currentRowForModal = null;
}

function selectProductFromModal(productId) {
  if (!currentRowForModal) return;
  const p = srProducts.find(x => x.id == productId);
  if (!p) return;
  
  const tr = currentRowForModal;
  
  tr.querySelector('.product-sel-id').value = p.id;
  
  const nameEl = tr.querySelector('.product-sel-name');
  nameEl.textContent = p.name;
  nameEl.classList.remove('text-gray-500');
  nameEl.classList.add('text-gray-900');
  
  const imgEl = tr.querySelector('.product-sel-img');
  imgEl.src = p.image ? ('<?= rootPath() ?>/' + p.image) : '<?= rootPath() ?>/assets/img/placeholder.png';
  imgEl.classList.remove('hidden');

  tr.dataset.ppb = p.pieces_per_box;
  tr.dataset.price = p.selling_price;
  
  const ppb = parseInt(p.pieces_per_box || 1);
  const piecePrice = parseFloat(p.selling_price || 0);
  const boxPrice = piecePrice * ppb;
  tr.querySelector('.row-price').textContent = '৳' + boxPrice.toFixed(2) + ' (৳' + piecePrice.toFixed(2) + '/pcs)';
  
  updateRowTotal(tr.querySelector('.qty-inp'));
  closeProductModal();
  updateUsed();
}

function addProductRow(selectedId = '', qty = '') {
  const tbody = document.getElementById('products-body');
  if (!tbody) return;
  const tr    = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <input type="hidden" class="product-sel-id" value="${selectedId}" required>
      <div class="flex items-center gap-3 cursor-pointer border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 hover:bg-gray-100 transition" onclick="openProductModal(this)">
        <img src="" class="w-10 h-10 object-contain rounded hidden product-sel-img" />
        <span class="product-sel-name text-gray-500 font-medium text-sm">Click to Select Product</span>
      </div>
    </td>
    <td><input type="number" class="form-input qty-inp" min="1" placeholder="boxes" value="${qty}" required oninput="updateRowTotal(this)" /></td>
    <td class="text-right text-gray-500 text-sm row-price">—</td>
    <td class="text-right font-medium row-total">৳0.00</td>
    <td><button type="button" onclick="this.closest('tr').remove(); updateUsed(); updateGrandTotal();" class="btn btn-danger btn-sm"><i class="fa-solid fa-xmark"></i></button></td>
  `;
  tbody.appendChild(tr);

  if (selectedId) {
    const p = srProducts.find(x => x.id == selectedId);
    if (p) {
      const nameEl = tr.querySelector('.product-sel-name');
      nameEl.textContent = p.name;
      nameEl.classList.remove('text-gray-500');
      nameEl.classList.add('text-gray-900');
      
      const imgEl = tr.querySelector('.product-sel-img');
      imgEl.src = p.image ? ('<?= rootPath() ?>/' + p.image) : '<?= rootPath() ?>/assets/img/placeholder.png';
      imgEl.classList.remove('hidden');

      tr.dataset.ppb = p.pieces_per_box;
      tr.dataset.price = p.selling_price;
      
      const ppb = parseInt(p.pieces_per_box || 1);
      const piecePrice = parseFloat(p.selling_price || 0);
      const boxPrice = piecePrice * ppb;
      tr.querySelector('.row-price').textContent = '৳' + boxPrice.toFixed(2) + ' (৳' + piecePrice.toFixed(2) + '/pcs)';
      
      if (qty) {
        const total = qty * boxPrice;
        tr.querySelector('.row-total').textContent = '৳' + total.toFixed(2);
      }
    }
  }

  updateUsed();
  updateGrandTotal();
}

function updateRowTotal(input) {
  const tr = input.closest('tr');
  const selId = tr.querySelector('.product-sel-id').value;
  const qty = parseFloat(input.value) || 0;
  
  let total = 0;
  if (selId) {
    const piecePrice = parseFloat(tr.dataset.price || 0);
    const ppb = parseInt(tr.dataset.ppb || 1);
    const boxPrice = piecePrice * ppb;
    total = qty * boxPrice;
  }
  tr.querySelector('.row-total').textContent = '৳' + total.toFixed(2);
  updateGrandTotal();
}

function updateGrandTotal() {
  let grand = 0;
  document.querySelectorAll('.row-total').forEach(td => {
    const val = parseFloat(td.textContent.replace('৳', '')) || 0;
    grand += val;
  });
  const grandTotalEl = document.getElementById('grand-total');
  if (grandTotalEl) {
    grandTotalEl.textContent = '৳' + grand.toFixed(2);
  }
}

function updateUsed() {}

// ─── OUT FOR DELIVERY DISPATCH LOGIC ──────────────────────────────────────────
function renderDispatchedQRs() {
  const tbody = document.getElementById('dispatched-qrs-body');
  if (!tbody) return;
  tbody.innerHTML = '';
  document.getElementById('qr-count').textContent = `${dispatchedQRs.length} boxes`;

  if (dispatchedQRs.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-6 text-gray-400">No boxes loaded in this dispatch yet. Scan one to add.</td></tr>`;
    return;
  }

  dispatchedQRs.forEach((qr, index) => {
    tbody.innerHTML += `
      <tr>
        <td class="font-medium text-gray-800">${escapeHTML(qr.product_name)}</td>
        <td class="font-mono text-xs text-gray-500">${escapeHTML(qr.qr_uid)}</td>
        <td class="text-right font-semibold text-blue-600">${parseInt(qr.qty_out || qr.pieces_total || qr.scanned_pieces)} pcs</td>
        <td class="text-center">
          <button type="button" onclick="removeQRFromDispatch(${index})" class="btn btn-danger btn-sm p-1"><i class="fa-solid fa-trash-can"></i></button>
        </td>
      </tr>
    `;
  });
}

function removeQRFromDispatch(index) {
  dispatchedQRs.splice(index, 1);
  renderDispatchedQRs();
}

async function handleManualInput(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    const input = document.getElementById('manual-qr');
    const val = input.value.trim();
    if (!val) return;
    input.value = '';
    await scanQRBox(val);
  }
}

async function scanQRBox(qrUid) {
  // Prevent duplicate scans
  if (dispatchedQRs.some(q => q.qr_uid.toLowerCase() === qrUid.toLowerCase())) {
    showToast('This QR code is already scanned and added to this dispatch.', 'warning');
    return;
  }

  // Verify/Find QR on server
  const data = await api('<?= rootPath() ?>/api/orders.php?action=scan_ready_sale&qr_uid=' + encodeURIComponent(qrUid));
  if (data.success && data.data) {
    const qrInfo = data.data;
    dispatchedQRs.push({
      qr_code_id: qrInfo.qr_id,
      qr_uid: qrUid,
      product_name: qrInfo.name,
      product_id: qrInfo.id,
      qty_out: qrInfo.scanned_pieces,
      pieces_total: qrInfo.scanned_pieces
    });
    showToast(`Added: ${qrInfo.name}`);
    renderDispatchedQRs();
  } else {
    showToast(data.message || 'QR not found or not active', 'error');
  }
}

function startScan() {
  document.getElementById('scan-btn').style.display = 'none';
  document.getElementById('stop-btn').style.display = 'block';

  html5QrCode = new Html5Qrcode("qr-reader");
  html5QrCode.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: { width: 250, height: 250 } },
    async (decodedText) => {
      stopScan();
      await scanQRBox(decodedText.trim());
    },
    () => {}
  ).catch(err => {
    showToast("Camera error: " + err, "error");
    stopScan();
  });
}

function stopScan() {
  document.getElementById('scan-btn').style.display = 'block';
  document.getElementById('stop-btn').style.display = 'none';
  if (html5QrCode) {
    html5QrCode.stop().then(() => html5QrCode = null).catch(() => {});
  }
}

function escapeHTML(str) {
  return str.replace(/[&<>'"]/g, 
    tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
  );
}

// ─── INITIALIZATION & SUBMIT ─────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', async () => {
  const orderStatus = document.getElementById('order-status')?.value;
  if (orderStatus === 'pending') {
    await checkSRAvailability();
    await loadSRProducts();
  } else if (orderStatus === 'out_for_delivery') {
    renderDispatchedQRs();
  }
});

document.getElementById('order-edit-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const orderId = document.getElementById('order-id').value;
  const orderStatus = document.getElementById('order-status').value;

  let payload = {};
  let summary = "";

  if (orderStatus === 'out_for_delivery') {
    const dsrId = document.getElementById('dsr-select').value;
    if (!dsrId) {
      showToast('Please select a DSR driver', 'error');
      return;
    }
    const dsrText = document.getElementById('dsr-select').options[document.getElementById('dsr-select').selectedIndex].text;
    const qrIds = dispatchedQRs.map(q => q.qr_code_id);

    payload = {
      dispatch_edit: {
        dsr_id: parseInt(dsrId),
        scanned_qrs: qrIds
      }
    };
    summary = `Edit Delivery Driver & scan items for Order #${orderId} (DSR: ${dsrText}, loaded: ${qrIds.length} boxes)`;
  } else {
    // pending order full edit
    const items = [];
    let valid = true;
    document.querySelectorAll('#products-body tr').forEach(tr => {
      const pid = tr.querySelector('.product-sel-id').value;
      const qtyBoxes = parseInt(tr.querySelector('.qty-inp').value);
      if (!pid || !qtyBoxes || qtyBoxes < 1) { valid = false; return; }
      
      const ppb = parseInt(tr.dataset.ppb || 1);
      const qtyPieces = qtyBoxes * ppb;
      items.push({ product_id: parseInt(pid), qty_pieces: qtyPieces });
    });

    if (!valid || items.length === 0) {
      showToast('Add at least one product with a valid quantity', 'error');
      return;
    }

    const orderDate = document.getElementById('order-date').value;
    const srId = document.getElementById('sr-select').value;
    const srText = document.getElementById('sr-select').options[document.getElementById('sr-select').selectedIndex].text;

    payload = {
      order_date: orderDate,
      sr_id: parseInt(srId),
      items: items
    };
    summary = `Edit items & details for Order #${orderId} (SR: ${srText}, Date: ${orderDate})`;
  }

  const btn = document.getElementById('save-btn');
  btn.disabled = true;
  btn.textContent = 'Submitting...';

  const data = await api('<?= rootPath() ?>/api/approvals.php?action=request', 'POST', {
    action_type: 'edit_order',
    target_id: parseInt(orderId),
    payload: payload,
    summary: summary
  });

  if (data.success) {
    showToast(data.message || 'Edit request submitted for admin approval');
    setTimeout(() => window.location.href = '<?= rootPath() ?>/manager/orders.php', 1500);
  } else {
    showToast(data.message || 'Error submitting request', 'error');
    btn.disabled = false;
    btn.textContent = 'Request Edit Approval';
  }
});
</script>
