<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Manager Dashboard';
$pdo       = getDB();
$wid       = $_SESSION['warehouse_id'];
$mgr_name  = $_SESSION['name'];

// Stats
$pendingOrders   = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN sr s ON s.id=o.sr_id JOIN companies c ON c.id=s.company_id WHERE o.status='pending'")->execute([]) ? $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status='pending'")->execute() : 0;
$pendingOrdersCnt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$outDelivery     = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='out_for_delivery'")->fetchColumn();
$stockItems      = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE warehouse_id=?")->execute([$wid]) ? $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE warehouse_id=? AND qty_boxes > 0")->execute([$wid]) : 0;
$inStockCnt      = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE warehouse_id=? AND qty_boxes > 0"); $inStockCnt->execute([$wid]); $inStockCnt = $inStockCnt->fetchColumn();
$lowStock        = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE warehouse_id=? AND qty_boxes < 5 AND qty_boxes > 0"); $lowStock->execute([$wid]); $lowStock = $lowStock->fetchColumn();
$pendingExp      = $pdo->query("SELECT COUNT(*) FROM expenses e JOIN dsr d ON d.id=e.dsr_id WHERE d.warehouse_id=$wid AND e.status='pending'")->fetchColumn();

// Recent orders
$recentOrders = $pdo->query("SELECT o.*, u.name as sr_name, c.name as company_name FROM orders o JOIN sr s ON s.id=o.sr_id JOIN users u ON u.id=s.user_id JOIN companies c ON c.id=o.company_id ORDER BY o.id DESC LIMIT 8")->fetchAll();

// Low stock products
$lowStockProducts = $pdo->prepare("SELECT p.name, i.qty_boxes, i.qty_pieces, co.name as company_name FROM inventory i JOIN products p ON p.id=i.product_id JOIN companies co ON co.id=p.company_id WHERE i.warehouse_id=? AND i.qty_boxes < 5 ORDER BY i.qty_boxes LIMIT 8");
$lowStockProducts->execute([$wid]); $lowStockProducts = $lowStockProducts->fetchAll();

// Chart: orders last 7 days
$ordersChart = $pdo->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM orders WHERE created_at >= DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY d")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">

      <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800">Manager Dashboard</h2>
        <p class="text-sm text-gray-500">Welcome back, <?= htmlspecialchars($mgr_name) ?>! Here's your warehouse overview.</p>
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="stat-card">
          <div class="stat-card-icon bg-yellow-100 mb-2">📋</div>
          <div class="text-2xl font-bold text-gray-800"><?= $pendingOrdersCnt ?></div>
          <div class="text-xs text-gray-500 mt-1">Pending Orders</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-blue-100 mb-2">🚚</div>
          <div class="text-2xl font-bold text-gray-800"><?= $outDelivery ?></div>
          <div class="text-xs text-gray-500 mt-1">Out for Delivery</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-green-100 mb-2">📦</div>
          <div class="text-2xl font-bold text-gray-800"><?= $inStockCnt ?></div>
          <div class="text-xs text-gray-500 mt-1">Products in Stock</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-red-100 mb-2">⚠️</div>
          <div class="text-2xl font-bold <?= $lowStock > 0 ? 'text-red-600' : 'text-gray-800' ?>"><?= $lowStock ?></div>
          <div class="text-xs text-gray-500 mt-1">Low Stock Alerts</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-purple-100 mb-2">💰</div>
          <div class="text-2xl font-bold text-gray-800"><?= $pendingExp ?></div>
          <div class="text-xs text-gray-500 mt-1">Pending Expenses</div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="flex flex-wrap gap-3 mb-6">
        <a href="/happycrm2/manager/order_add.php" class="btn btn-primary">+ New Order</a>
        <a href="/happycrm2/manager/lot_add.php" class="btn btn-success">+ Add Lot</a>
        <a href="/happycrm2/manager/delivery.php" class="btn btn-warning">🚚 Delivery</a>
        <a href="/happycrm2/manager/qr_generate.php" class="btn btn-ghost">🔲 Generate QR</a>
        <a href="/happycrm2/manager/inventory.php" class="btn btn-ghost">📦 Inventory</a>
      </div>

      <!-- Charts + Tables Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
          <h3 class="font-semibold text-gray-700 mb-4">Orders Last 7 Days</h3>
          <canvas id="ordersChart" height="200"></canvas>
        </div>

        <!-- Low Stock -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 flex justify-between">
            <h3 class="font-semibold text-gray-700">⚠️ Low Stock</h3>
            <a href="/happycrm2/manager/inventory.php" class="text-xs text-indigo-600">View All</a>
          </div>
          <table class="data-table">
            <thead><tr><th>Product</th><th>Company</th><th class="text-right">Boxes</th></tr></thead>
            <tbody>
              <?php foreach ($lowStockProducts as $p): ?>
              <tr class="<?= $p['qty_boxes'] == 0 ? 'bg-red-50' : 'bg-yellow-50' ?>">
                <td class="font-medium"><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['company_name']) ?></td>
                <td class="text-right"><span class="badge <?= $p['qty_boxes'] == 0 ? 'badge-danger' : 'badge-warning' ?>"><?= $p['qty_boxes'] ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($lowStockProducts)): ?><tr><td colspan="3" class="text-center py-4 text-gray-400">All stock OK ✓</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between">
          <h3 class="font-semibold text-gray-700">Recent Orders</h3>
          <a href="/happycrm2/manager/orders.php" class="text-xs text-indigo-600">View All →</a>
        </div>
        <table class="data-table">
          <thead><tr><th>Order</th><th>SR</th><th>Company</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($recentOrders as $o):
              $sc = ['pending'=>'badge-warning','out_for_delivery'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger'];
            ?>
            <tr>
              <td class="font-mono text-xs text-gray-500">#<?= str_pad($o['id'],4,'0',STR_PAD_LEFT) ?></td>
              <td class="font-medium"><?= htmlspecialchars($o['sr_name']) ?></td>
              <td><?= htmlspecialchars($o['company_name']) ?></td>
              <td class="text-xs text-gray-500"><?= $o['order_date'] ?></td>
              <td><span class="badge <?= $sc[$o['status']] ?? 'badge-gray' ?>"><?= str_replace('_',' ',ucfirst($o['status'])) ?></span></td>
              <td>
                <?php if ($o['status'] === 'pending'): ?>
                <a href="/happycrm2/manager/delivery.php" class="btn btn-primary btn-sm">Load</a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentOrders)): ?><tr><td colspan="6" class="text-center py-6 text-gray-400">No orders yet</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
const ordersData = <?= json_encode($ordersChart) ?>;
new Chart(document.getElementById('ordersChart'), {
  type: 'bar',
  data: {
    labels: ordersData.map(r => r.d),
    datasets: [{ label: 'Orders', data: ordersData.map(r => parseInt(r.c)),
      backgroundColor: 'rgba(79,70,229,0.7)', borderRadius: 6 }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
</script>
