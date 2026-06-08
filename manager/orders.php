<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Orders';
$pdo       = getDB();
$wid       = $_SESSION['warehouse_id'];

// Stat cards
$grand  = $pdo->query("SELECT COALESCE(SUM(oi.qty_pieces * oi.unit_price),0) FROM order_items oi")->fetchColumn();
$outPcs = $pdo->query("SELECT (SELECT COALESCE(SUM(qty_out),0) FROM dispatch_items) + (SELECT COALESCE(SUM(oi.qty_pieces),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.status='ready_sale')")->fetchColumn();
$retPcs = $pdo->query("SELECT COALESCE(SUM(ri.qty_in),0) FROM return_items ri")->fetchColumn();
$avgDel = $outPcs > 0 ? round((($outPcs - $retPcs) / $outPcs) * 100, 1) : 0;

// Orders with product-level breakdown
$orders = $pdo->query('
    SELECT o.id as order_id, o.order_date, o.status, o.retailer_name, o.retailer_phone,
           u.name as sr_name, c.name as company_name,
           p.name as product_name, p.selling_price,
           oi.qty_pieces, oi.unit_price, oi.product_id,
           IF(o.status=\'ready_sale\', oi.qty_pieces, (SELECT COALESCE(SUM(di.qty_out), 0) FROM dispatch_items di WHERE di.order_id=o.id AND di.product_id=oi.product_id)) as dispatch_qty,
           (SELECT COALESCE(SUM(ri.qty_in), 0) FROM return_items ri JOIN returns r ON r.id=ri.return_id JOIN dispatches d ON d.id=r.dispatch_id WHERE d.order_id=o.id AND ri.product_id=oi.product_id) as back_qty,
           (SELECT du.name FROM dispatches d JOIN dsr ds ON ds.id = d.dsr_id JOIN users du ON du.id = ds.user_id WHERE d.order_id = o.id ORDER BY d.id DESC LIMIT 1) as dsr_name
    FROM orders o
    JOIN sr s ON s.id=o.sr_id
    JOIN users u ON u.id=s.user_id
    JOIN companies c ON c.id=o.company_id
    JOIN order_items oi ON oi.order_id=o.id
    JOIN products p ON p.id=oi.product_id
    ORDER BY CASE o.status
               WHEN \'pending\'          THEN 1
               WHEN \'ready_sale\'       THEN 2
               WHEN \'out_for_delivery\' THEN 3
               WHEN \'delivered\'        THEN 4
               WHEN \'cancelled\'        THEN 5
               ELSE 6
             END, o.id DESC, oi.id
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
          <a href="<?= rootPath() ?>/manager/ready_sale_scan.php" class="btn btn-indigo">
            <i class="fa-solid fa-qrcode"></i> Ready Sale
          </a>
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
        <div><label class="form-label">From</label><input type="date" id="f-from" class="form-input" onchange="filterTable()" /></div>
        <div><label class="form-label">To</label><input type="date" id="f-to" class="form-input" onchange="filterTable()" /></div>
        <div><label class="form-label">Status</label>
          <select id="f-status" class="form-input" onchange="filterTable()">
            <option value="">All Status</option>
            <option>pending</option><option value="ready_sale">Ready Sale</option><option>out_for_delivery</option><option>delivered</option><option>cancelled</option>
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
              <th class="text-right">Ordered Qty</th>
              <th class="text-right">Out Qty</th>
              <th class="text-right">Back Qty</th>
              <th class="text-right">Sell Qty</th>
              <th class="text-right">Sale Value</th>
              <th>Delivery %</th><th>Status</th><th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $o):
              $orderedQty  = (int)$o['qty_pieces'];
              $dispatchQty = (int)$o['dispatch_qty'];
              $backQty     = (int)$o['back_qty'];
              // Sell Qty is whatever went out minus whatever came back
              $sellQty     = max($dispatchQty - $backQty, 0);
              $saleVal     = $sellQty * $o['unit_price'];
              $ratio       = $dispatchQty > 0 ? round(($sellQty / $dispatchQty) * 100) : 0;
              $ratioClass  = $ratio >= 90 ? 'badge-success' : ($ratio >= 50 ? 'badge-warning' : 'badge-danger');
            ?>
            <tr data-date="<?= $o['order_date'] ?>" data-status="<?= htmlspecialchars($o['status']) ?>">
              <td class="font-mono text-xs text-gray-500">#<?= str_pad($o['order_id'], 4, '0', STR_PAD_LEFT) ?></td>
              <td class="font-medium"><?= htmlspecialchars($o['product_name']) ?></td>
              <td>
                <?= htmlspecialchars($o['sr_name']) ?>
                <?php if ($o['status'] === 'ready_sale' && !empty($o['retailer_name'])): ?>
                  <span class="block text-[10px] text-indigo-600 font-bold mt-0.5" title="<?= htmlspecialchars($o['retailer_phone'] ?? '') ?>">
                    <i class="fa-solid fa-store text-[8px]"></i> <?= htmlspecialchars($o['retailer_name']) ?>
                  </span>
                <?php elseif ($o['status'] !== 'ready_sale' && in_array($o['status'], ['out_for_delivery', 'delivered']) && !empty($o['dsr_name'])): ?>
                  <span class="block text-[10px] text-blue-600 font-bold mt-0.5">
                    <i class="fa-solid fa-truck text-[8px]"></i> <?= htmlspecialchars($o['dsr_name']) ?>
                  </span>
                <?php endif; ?>
              </td>
              <td class="text-xs text-gray-500"><?= $o['order_date'] ?></td>
              <td class="text-right font-medium"><?= number_format($orderedQty) ?></td>
              <td class="text-right font-medium text-blue-600"><?= number_format($dispatchQty) ?></td>
              <td class="text-right text-red-600"><?= number_format($backQty) ?></td>
              <td class="text-right text-green-600 font-bold"><?= number_format($sellQty) ?></td>
              <td class="text-right font-medium text-indigo-600">৳<?= number_format($saleVal, 0) ?></td>
              <td><span class="badge <?= $ratioClass ?>"><?= $ratio ?>%</span></td>
              <td>
                <?php
                $sc = ['pending'=>'badge-warning','ready_sale'=>'badge-success','out_for_delivery'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger'];
                echo '<span class="badge ' . ($sc[$o['status']] ?? 'badge-gray') . '">' . str_replace('_',' ',ucfirst($o['status'])) . '</span>';
                ?>
              </td>
              <td>
                <div class="flex gap-1.5 justify-center">
                  <?php if ($o['status'] === 'pending' || $o['status'] === 'out_for_delivery'): ?>
                    <a href="<?= rootPath() ?>/manager/order_edit.php?id=<?= $o['order_id'] ?>" class="btn btn-indigo btn-sm py-1 px-2.5 text-xs"><i class="fa-solid fa-pen-to-square mr-1"></i>Edit</a>
                  <?php endif; ?>
                  <?php if ($o['status'] !== 'cancelled' && $o['status'] !== 'delivered'): ?>
                    <button onclick="cancelOrder(<?= $o['order_id'] ?>)" class="btn btn-danger btn-sm py-1 px-2.5 text-xs"><i class="fa-solid fa-trash-can mr-1"></i>Cancel</button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?><tr><td colspan="12" class="text-center py-8 text-gray-400">No orders yet. <a href="<?= rootPath() ?>/manager/order_add.php" class="text-indigo-600">Add one</a></td></tr><?php endif; ?>
          </tbody>
        </table>
        <!-- Pagination -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 border-t border-gray-100" id="pagination-container" style="display:none">
          <div class="text-sm text-gray-500" id="pagination-info">
            Showing <span class="font-semibold text-gray-700 text-xs" id="pagination-start">0</span> to <span class="font-semibold text-gray-700 text-xs" id="pagination-end">0</span> of <span class="font-semibold text-gray-700 text-xs" id="pagination-total">0</span> orders
          </div>
          <div class="flex items-center gap-1.5" id="pagination-buttons"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
const PAGE_SIZE = 25;
let currentPage = 1;

function filterTable() {
  const from = document.getElementById('f-from').value;
  const to = document.getElementById('f-to').value;
  const status = document.getElementById('f-status').value;
  const q = (document.getElementById('f-search').value || '').toLowerCase();

  document.querySelectorAll('#orders-table tbody tr').forEach(tr => {
    // Skip the "no orders" empty row
    if (tr.cells.length === 1 && tr.cells[0].colSpan >= 10) return;

    const rowDate = tr.dataset.date;
    const rowStatus = tr.dataset.status;
    const textContent = tr.textContent.toLowerCase();

    const matchSearch = !q || textContent.includes(q);
    const matchFrom = !from || rowDate >= from;
    const matchTo = !to || rowDate <= to;
    const matchStatus = !status || rowStatus === status;

    tr.dataset.match = (matchSearch && matchFrom && matchTo && matchStatus) ? "true" : "false";
  });

  currentPage = 1;
  updatePagination();
}

function updatePagination() {
  const trs = Array.from(document.querySelectorAll('#orders-table tbody tr')).filter(tr => {
    // Exclude the empty-state row
    return !(tr.cells.length === 1 && tr.cells[0].colSpan >= 10);
  });

  const matchingRows = trs.filter(tr => tr.dataset.match === "true");
  const nonMatchingRows = trs.filter(tr => tr.dataset.match === "false");
  const totalMatching = matchingRows.length;
  const totalPages = Math.ceil(totalMatching / PAGE_SIZE) || 1;

  if (currentPage < 1) currentPage = 1;
  if (currentPage > totalPages) currentPage = totalPages;

  const startIdx = (currentPage - 1) * PAGE_SIZE;
  const endIdx = startIdx + PAGE_SIZE;

  // Always explicitly show/hide every row — no early returns before this
  matchingRows.forEach((tr, index) => {
    tr.style.display = (index >= startIdx && index < endIdx) ? '' : 'none';
  });

  // Always hide non-matching rows
  nonMatchingRows.forEach(tr => { tr.style.display = 'none'; });

  // Update pagination info
  document.getElementById('pagination-start').textContent = totalMatching > 0 ? startIdx + 1 : 0;
  document.getElementById('pagination-end').textContent   = totalMatching > 0 ? Math.min(endIdx, totalMatching) : 0;
  document.getElementById('pagination-total').textContent = totalMatching;

  // Show/hide pagination bar
  const paginationContainer = document.getElementById('pagination-container');
  const btnContainer = document.getElementById('pagination-buttons');
  btnContainer.innerHTML = '';

  if (totalPages <= 1) {
    paginationContainer.style.display = 'none';
    return;
  }
  paginationContainer.style.display = 'flex';

  // Prev Button
  const prevBtn = document.createElement('button');
  prevBtn.className = `px-2.5 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 disabled:opacity-40 disabled:pointer-events-none transition`;
  prevBtn.innerHTML = '<i class="fa-solid fa-chevron-left text-[10px]"></i>';
  prevBtn.disabled = currentPage === 1;
  prevBtn.onclick = () => { currentPage--; updatePagination(); };
  btnContainer.appendChild(prevBtn);

  // Page Numbers
  const maxButtons = 5;
  let startPage = Math.max(1, currentPage - 2);
  let endPage = Math.min(totalPages, startPage + maxButtons - 1);
  if (endPage - startPage + 1 < maxButtons) {
    startPage = Math.max(1, endPage - maxButtons + 1);
  }

  for (let i = startPage; i <= endPage; i++) {
    const pageBtn = document.createElement('button');
    pageBtn.className = `px-3 py-1.5 text-xs font-semibold rounded-lg border ${i === currentPage ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-700'} transition`;
    pageBtn.textContent = i;
    pageBtn.onclick = () => { currentPage = i; updatePagination(); };
    btnContainer.appendChild(pageBtn);
  }

  // Next Button
  const nextBtn = document.createElement('button');
  nextBtn.className = `px-2.5 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 disabled:opacity-40 disabled:pointer-events-none transition`;
  nextBtn.innerHTML = '<i class="fa-solid fa-chevron-right text-[10px]"></i>';
  nextBtn.disabled = currentPage === totalPages;
  nextBtn.onclick = () => { currentPage++; updatePagination(); };
  btnContainer.appendChild(nextBtn);
}

function clearFilters() {
  document.getElementById('f-from').value = '';
  document.getElementById('f-to').value = '';
  document.getElementById('f-status').value = '';
  document.getElementById('f-search').value = '';
  filterTable();
}

window.addEventListener('DOMContentLoaded', () => {
  // Explicitly mark all data rows as matching on first load — no filters active
  document.querySelectorAll('#orders-table tbody tr').forEach(tr => {
    if (tr.cells.length === 1 && tr.cells[0].colSpan >= 10) return; // skip empty-state row
    tr.dataset.match = 'true';
  });
  updatePagination();
});
async function cancelOrder(id) {
  if (!confirmDelete('Cancel this order?')) return;
  const data = await api('<?= rootPath() ?>/api/orders.php?id=' + id, 'DELETE');
  if (data.success) {
    showToast(data.message || 'Order cancelled');
    setTimeout(() => location.reload(), 1500);
  }
  else showToast(data.message || 'Error', 'error');
}
</script>
