<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Orders';
$pdo       = getDB();
$wid       = $_SESSION['warehouse_id'];

// Stat cards
$grand  = $pdo->query("SELECT COALESCE(SUM(oi.qty_pieces * oi.unit_price),0) FROM order_items oi")->fetchColumn();
$outPcs = $pdo->query("SELECT COALESCE(SUM(di.qty_out),0) FROM dispatch_items di")->fetchColumn();
$retPcs = $pdo->query("SELECT COALESCE(SUM(ri.qty_in),0) FROM return_items ri")->fetchColumn();
$avgDel = $outPcs > 0 ? round((($outPcs - $retPcs) / $outPcs) * 100, 1) : 0;

// Orders with product-level breakdown
$orders = $pdo->query('
    SELECT o.id as order_id, o.order_date, o.status,
           u.name as sr_name, c.name as company_name,
           p.name as product_name, p.selling_price,
           oi.qty_pieces, oi.unit_price, oi.product_id
    FROM orders o
    JOIN sr s ON s.id=o.sr_id
    JOIN users u ON u.id=s.user_id
    JOIN companies c ON c.id=o.company_id
    JOIN order_items oi ON oi.order_id=o.id
    JOIN products p ON p.id=oi.product_id
    ORDER BY o.id DESC, oi.id
')->fetchAll();

$companies = $pdo->query('SELECT id, name FROM companies WHERE status=1 ORDER BY name')->fetchAll();
$srs       = $pdo->query('SELECT s.id, u.name FROM sr s JOIN users u ON u.id=s.user_id WHERE s.status=1 ORDER BY u.name')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= rootPath() ?>/assets/css/scanner.css">
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">

      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Orders</h2><p class="text-sm text-gray-500">All SR orders with product-level breakdown</p></div>
        <div class="flex gap-2">
          <button onclick="openReadyScanner()" class="btn btn-indigo">
            <i class="fa-solid fa-qrcode"></i> Ready Sale
          </button>
          <a href="<?= rootPath() ?>/manager/order_add.php" class="btn btn-primary">+ Add New Order</a>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
          <div class="stat-card-icon bg-green-100 mb-2 text-green-600"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
          <div class="text-xl font-bold">৳<?= number_format($grand, 0) ?></div>
          <div class="text-xs text-gray-500 mt-1">Grand Total Sales</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-blue-100 mb-2 text-blue-600"><i class="fa-solid fa-truck-ramp-box"></i></div>
          <div class="text-xl font-bold"><?= number_format($outPcs) ?></div>
          <div class="text-xs text-gray-500 mt-1">Total Out (pcs)</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-red-100 mb-2 text-red-600"><i class="fa-solid fa-rotate-left"></i></div>
          <div class="text-xl font-bold"><?= number_format($retPcs) ?></div>
          <div class="text-xs text-gray-500 mt-1">Total Back (pcs)</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-indigo-100 mb-2 text-indigo-600"><i class="fa-solid fa-chart-line"></i></div>
          <div class="text-xl font-bold"><?= $avgDel ?>%</div>
          <div class="text-xs text-gray-500 mt-1">Avg Delivery Rate</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div><label class="form-label">From</label><input type="date" id="f-from" class="form-input" /></div>
        <div><label class="form-label">To</label><input type="date" id="f-to" class="form-input" /></div>
        <div><label class="form-label">Status</label>
          <select id="f-status" class="form-input">
            <option value="">All Status</option>
            <option>pending</option><option>out_for_delivery</option><option>delivered</option><option>cancelled</option>
          </select>
        </div>
        <div><label class="form-label">Search</label><input type="text" id="f-search" class="form-input" placeholder="SR, Product…" oninput="filterTable()" /></div>
        <button onclick="filterTable()" class="btn btn-primary">Filter</button>
        <button onclick="clearFilters()" class="btn btn-ghost">Clear</button>
      </div>

      <!-- Orders Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="data-table" id="orders-table">
          <thead>
            <tr>
              <th>Order#</th><th>Product</th><th>SR</th><th>Date</th>
              <th class="text-right">Total Qty</th><th class="text-right">Back Qty</th>
              <th class="text-right">Sell Qty</th><th class="text-right">Sale Value</th>
              <th>Delivery %</th><th>Status</th><th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $o):
              $sellQty  = $o['qty_pieces']; // simplified; back qty needs join
              $saleVal  = $sellQty * $o['unit_price'];
              $ratio    = 100; // full delivery until returns happen
              $ratioClass = 'badge-success';
            ?>
            <tr>
              <td class="font-mono text-xs text-gray-500">#<?= str_pad($o['order_id'], 4, '0', STR_PAD_LEFT) ?></td>
              <td class="font-medium"><?= htmlspecialchars($o['product_name']) ?></td>
              <td><?= htmlspecialchars($o['sr_name']) ?></td>
              <td class="text-xs text-gray-500"><?= $o['order_date'] ?></td>
              <td class="text-right"><?= number_format($o['qty_pieces']) ?></td>
              <td class="text-right text-red-600">0</td>
              <td class="text-right text-green-600"><?= number_format($sellQty) ?></td>
              <td class="text-right font-medium">৳<?= number_format($saleVal, 0) ?></td>
              <td><span class="badge <?= $ratioClass ?>"><?= $ratio ?>%</span></td>
              <td>
                <?php
                $sc = ['pending'=>'badge-warning','out_for_delivery'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger'];
                echo '<span class="badge ' . ($sc[$o['status']] ?? 'badge-gray') . '">' . str_replace('_',' ',ucfirst($o['status'])) . '</span>';
                ?>
              </td>
              <td>
                <button onclick="cancelOrder(<?= $o['order_id'] ?>)" class="btn btn-danger btn-sm">Cancel</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?><tr><td colspan="11" class="text-center py-8 text-gray-400">No orders yet. <a href="<?= rootPath() ?>/manager/order_add.php" class="text-indigo-600">Add one</a></td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Ready Sale Scanner Modal -->
<div id="ready-scanner-overlay" class="scanner-modal-overlay">
  <div class="scanner-modal">
    <div class="scanner-header">
      <h3 id="modal-title"><i class="fa-solid fa-qrcode text-indigo-500"></i> Ready Sale</h3>
      <button onclick="closeReadyScanner()" class="scanner-close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    
    <!-- Step 2: Scanner -->
    <div id="step-scan">
      <div class="scanner-viewport">
        <div id="ready-scan-reader"></div>
        <div class="scanner-laser"></div>
        <div id="success-flash" class="success-flash"></div>
      </div>

      <div class="scanned-list-container" id="scanned-items-list">
        <div class="text-center py-10 text-gray-500" id="empty-scan-msg">
          <i class="fa-solid fa-barcode text-3xl mb-3 block opacity-20"></i>
          Scan QR codes to add products
        </div>
      </div>

      <div class="scanner-footer">
        <button onclick="saveReadyOrder()" id="complete-scan-btn" class="btn-complete" disabled>
          Complete Order
        </button>
      </div>
    </div>
  </div>
</div>
<script>
function filterTable() {
  const q = (document.getElementById('f-search').value || '').toLowerCase();
  document.querySelectorAll('#orders-table tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
function clearFilters() {
  document.getElementById('f-from').value = '';
  document.getElementById('f-to').value = '';
  document.getElementById('f-status').value = '';
  document.getElementById('f-search').value = '';
  filterTable();
}
async function cancelOrder(id) {
  if (!confirmDelete('Cancel this order?')) return;
  const data = await api('<?= rootPath() ?>/api/orders.php?id=' + id, 'DELETE');
  if (data.success) { showToast('Order cancelled'); location.reload(); }
  else showToast(data.message || 'Error', 'error');
}

// ── Ready Sale Scanner Logic ──────────────────────────────
let scannedData = {};
let activeScanner = null;
let selectedSrId = null;

function openReadyScanner() {
  document.getElementById('ready-scanner-overlay').classList.add('active');
  document.getElementById('step-scan').style.display = 'block';
  document.getElementById('modal-title').innerHTML = '<i class="fa-solid fa-qrcode text-indigo-500"></i> Ready Sale Scanner';
  selectedSrId = null; // Reset for auto-detection
  startReadyScanner();
}

function closeReadyScanner() {
  if (activeScanner) {
    activeScanner.stop().catch(() => {});
    activeScanner = null;
  }
  document.getElementById('ready-scanner-overlay').classList.remove('active');
  scannedData = {};
  selectedSrId = null;
  document.getElementById('scanned-items-list').innerHTML = `<div class="text-center py-10 text-gray-500" id="empty-scan-msg"><i class="fa-solid fa-barcode text-3xl mb-3 block opacity-20"></i>Scan QR codes to add products</div>`;
}

function startReadyScanner() {
  activeScanner = new Html5Qrcode("ready-scan-reader");
  activeScanner.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: { width: 250, height: 250 } },
    (decodedText) => handleReadyScan(decodedText),
    (errorMessage) => {}
  ).catch(err => {
    showToast('Camera error: ' + err, 'error');
  });
}

async function handleReadyScan(uid) {
  if (window._scanning) return;
  window._scanning = true;
  setTimeout(() => window._scanning = false, 1500);

  const url = `<?= rootPath() ?>/api/orders.php?action=scan_ready_sale&qr_uid=${uid}` + (selectedSrId ? `&sr_id=${selectedSrId}` : '');
  const res = await api(url);
  
  if (!res.success) {
    triggerShake();
    showToast(res.message, 'error');
    return;
  }

  const p = res.data;
  if (!selectedSrId) selectedSrId = p.sr_id; // Set SR ID from first scan

  triggerFlash();

  if (!scannedData[p.id]) {
    scannedData[p.id] = { name: p.name, qty: p.scanned_pieces, price: p.selling_price, ppb: p.pieces_per_box, qrIds: [p.qr_id] };
    renderScannedItem(p.id, true);
  } else {
    if (scannedData[p.id].qrIds.includes(p.qr_id)) { showToast('Already scanned', 'warning'); return; }
    scannedData[p.id].qty += p.scanned_pieces;
    scannedData[p.id].qrIds.push(p.qr_id);
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
  const modal = document.querySelector('.scanner-modal');
  modal.classList.remove('shake'); void modal.offsetWidth; modal.classList.add('shake');
}

async function saveReadyOrder() {
  const items = [];
  for (const pid in scannedData) {
    items.push({ product_id: pid, qty_pieces: scannedData[pid].qty });
  }
  
  const btn = document.getElementById('complete-scan-btn');
  btn.disabled = true; btn.textContent = 'Saving…';
  
  const data = await api('<?= rootPath() ?>/api/orders.php', 'POST', {
    sr_id: selectedSrId,
    order_date: new Date().toISOString().split('T')[0],
    items
  });

  if (data.success) {
    showToast('Order created successfully!');
    setTimeout(() => location.reload(), 1000);
  } else {
    showToast(data.message || 'Error', 'error');
    btn.disabled = false; btn.textContent = 'Complete Order';
  }
}
</script>
