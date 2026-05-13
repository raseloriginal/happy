<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');
$pageTitle = 'Admin Dashboard';
$pdo = getDB();

// Stat queries
$totalOrders   = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$totalRevenue  = $pdo->query("SELECT COALESCE(SUM(oi.qty_pieces * oi.unit_price),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE DATE(o.created_at)=CURDATE()")->fetchColumn();
$activeDSRs    = $pdo->query("SELECT COUNT(*) FROM dsr WHERE status=1")->fetchColumn();
$pendingRet    = $pdo->query("SELECT COUNT(*) FROM returns WHERE status='pending'")->fetchColumn();
$stockValue    = $pdo->query("SELECT COALESCE(SUM(i.qty_boxes * p.selling_price),0) FROM inventory i JOIN products p ON p.id=i.product_id")->fetchColumn();

// Chart: orders last 7 days
$ordersChart = $pdo->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM orders WHERE created_at >= DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY d")->fetchAll();

// Top 5 products
$topProducts = $pdo->query("SELECT p.name, SUM(oi.qty_pieces) as total FROM order_items oi JOIN products p ON p.id=oi.product_id GROUP BY oi.product_id ORDER BY total DESC LIMIT 5")->fetchAll();

// Recent orders
$recentOrders = $pdo->query("SELECT o.*, u.name as sr_name, c.name as company_name FROM orders o JOIN sr s ON s.id=o.sr_id JOIN users u ON u.id=s.user_id JOIN companies c ON c.id=o.company_id ORDER BY o.created_at DESC LIMIT 10")->fetchAll();

// Low stock
$lowStock = $pdo->query("SELECT p.name, w.name as warehouse, i.qty_boxes FROM inventory i JOIN products p ON p.id=i.product_id JOIN warehouses w ON w.id=i.warehouse_id WHERE i.qty_boxes < 5 ORDER BY i.qty_boxes")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">

      <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800">Admin Dashboard</h2>
        <p class="text-sm text-gray-500">Welcome back, <?= htmlspecialchars($_SESSION['name']) ?>!</p>
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="stat-card">
          <div class="stat-card-icon bg-indigo-100 text-indigo-600 mb-3">📋</div>
          <div class="text-2xl font-bold text-gray-800"><?= $totalOrders ?></div>
          <div class="text-xs text-gray-500 mt-1">Orders Today</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-green-100 text-green-600 mb-3">💰</div>
          <div class="text-2xl font-bold text-gray-800">৳<?= number_format($totalRevenue, 0) ?></div>
          <div class="text-xs text-gray-500 mt-1">Today's Revenue</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-yellow-100 text-yellow-600 mb-3">🚚</div>
          <div class="text-2xl font-bold text-gray-800"><?= $activeDSRs ?></div>
          <div class="text-xs text-gray-500 mt-1">Active DSRs</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-blue-100 text-blue-600 mb-3">📦</div>
          <div class="text-2xl font-bold text-gray-800">৳<?= number_format($stockValue, 0) ?></div>
          <div class="text-xs text-gray-500 mt-1">Stock Value</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-red-100 text-red-600 mb-3">↩️</div>
          <div class="text-2xl font-bold text-gray-800"><?= $pendingRet ?></div>
          <div class="text-xs text-gray-500 mt-1">Pending Returns</div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
          <h3 class="font-semibold text-gray-700 mb-4">Orders Last 7 Days</h3>
          <canvas id="ordersChart" height="200"></canvas>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
          <h3 class="font-semibold text-gray-700 mb-4">Top 5 Products</h3>
          <canvas id="productsChart" height="200"></canvas>
        </div>
      </div>

      <!-- Tables Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Orders -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">Recent Orders</h3>
            <a href="/happycrm2/manager/orders.php" class="text-xs text-indigo-600 hover:underline">View All</a>
          </div>
          <table class="data-table">
            <thead><tr><th>SR</th><th>Company</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($recentOrders as $o): ?>
              <tr>
                <td class="font-medium"><?= htmlspecialchars($o['sr_name']) ?></td>
                <td><?= htmlspecialchars($o['company_name']) ?></td>
                <td class="text-gray-500 text-xs"><?= $o['order_date'] ?></td>
                <td>
                  <?php
                  $sc = ['pending'=>'badge-warning','out_for_delivery'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger'];
                  $badge = $sc[$o['status']] ?? 'badge-gray';
                  ?>
                  <span class="badge <?= $badge ?>"><?= str_replace('_',' ',ucfirst($o['status'])) ?></span>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($recentOrders)): ?><tr><td colspan="4" class="text-center py-6 text-gray-400">No orders yet</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Low Stock -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700">⚠️ Low Stock Alerts</h3>
          </div>
          <table class="data-table">
            <thead><tr><th>Product</th><th>Warehouse</th><th>Boxes</th></tr></thead>
            <tbody>
              <?php foreach ($lowStock as $s): ?>
              <tr class="<?= $s['qty_boxes'] == 0 ? 'bg-red-50' : 'bg-yellow-50' ?>">
                <td class="font-medium"><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['warehouse']) ?></td>
                <td><span class="badge <?= $s['qty_boxes'] == 0 ? 'badge-danger' : 'badge-warning' ?>"><?= $s['qty_boxes'] ?> boxes</span></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($lowStock)): ?><tr><td colspan="3" class="text-center py-6 text-gray-400">All stock levels OK ✓</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
// Orders Line Chart
const ordersData = <?= json_encode($ordersChart) ?>;
const labels = ordersData.map(r => r.d);
const counts = ordersData.map(r => parseInt(r.c));
new Chart(document.getElementById('ordersChart'), {
  type: 'line',
  data: {
    labels, datasets: [{
      label: 'Orders', data: counts,
      borderColor: '#4F46E5', backgroundColor: 'rgba(79,70,229,0.08)',
      tension: 0.4, fill: true, pointRadius: 4, pointBackgroundColor: '#4F46E5'
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

// Top Products Bar Chart
const topData = <?= json_encode($topProducts) ?>;
new Chart(document.getElementById('productsChart'), {
  type: 'bar',
  data: {
    labels: topData.map(r => r.name),
    datasets: [{ label: 'Pieces Sold', data: topData.map(r => parseInt(r.total)),
      backgroundColor: ['#4F46E5','#7C3AED','#10B981','#F59E0B','#EF4444'] }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
