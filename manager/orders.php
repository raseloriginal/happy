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
</script>
