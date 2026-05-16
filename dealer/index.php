<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('dealer');
$pageTitle = 'Dealer Dashboard';
$pdo       = getDB();
$user_id   = $_SESSION['user_id'];

$dealer = $pdo->prepare('SELECT d.* FROM dealers d WHERE d.user_id=? LIMIT 1');
$dealer->execute([$user_id]);
$dealer = $dealer->fetch();
$dealer_id = $dealer['id'] ?? 0;

// Companies under this dealer
$companies = $pdo->prepare('SELECT c.*, (SELECT COUNT(*) FROM orders o JOIN sr s ON s.id=o.sr_id WHERE s.company_id=c.id) as order_count FROM companies c WHERE c.dealer_id=? AND c.status=1');
$companies->execute([$dealer_id]);
$companies = $companies->fetchAll();

$company_ids = array_column($companies, 'id');
$totalOrders = 0; $totalRevenue = 0;
if ($company_ids) {
    $in = implode(',', array_map('intval', $company_ids));
    $totalOrders  = $pdo->query("SELECT COUNT(*) FROM orders WHERE company_id IN ($in)")->fetchColumn();
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(oi.qty_pieces * oi.unit_price),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.company_id IN ($in)")->fetchColumn();
}

// Recent orders across all companies
$recentOrders = [];
if ($company_ids) {
    $in = implode(',', array_map('intval', $company_ids));
    $recentOrders = $pdo->query("SELECT o.*, u.name as sr_name, c.name as company_name FROM orders o JOIN sr s ON s.id=o.sr_id JOIN users u ON u.id=s.user_id JOIN companies c ON c.id=o.company_id WHERE o.company_id IN ($in) ORDER BY o.id DESC LIMIT 10")->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">
      <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800">Dealer Dashboard</h2>
        <p class="text-sm text-gray-500">Welcome, <?= htmlspecialchars($_SESSION['name']) ?></p>
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="stat-card">
          <div class="stat-card-icon bg-indigo-100 mb-2 text-indigo-600"><i class="fa-solid fa-building"></i></div>
          <div class="text-2xl font-bold"><?= count($companies) ?></div>
          <div class="text-xs text-gray-500">My Companies</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-green-100 mb-2 text-green-600"><i class="fa-solid fa-clipboard-list"></i></div>
          <div class="text-2xl font-bold"><?= $totalOrders ?></div>
          <div class="text-xs text-gray-500">Total Orders</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-yellow-100 mb-2 text-yellow-600"><i class="fa-solid fa-money-bill-wave"></i></div>
          <div class="text-2xl font-bold">৳<?= number_format($totalRevenue, 0) ?></div>
          <div class="text-xs text-gray-500">Total Revenue</div>
        </div>
      </div>

      <!-- Companies Table -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-700">My Companies</h3></div>
        <table class="data-table">
          <thead><tr><th>#</th><th>Company Name</th><th>Contact</th><th>Total Orders</th></tr></thead>
          <tbody>
            <?php foreach ($companies as $i => $c): ?>
            <tr>
              <td class="text-gray-400 text-xs"><?= $i+1 ?></td>
              <td class="font-medium"><?= htmlspecialchars($c['name']) ?></td>
              <td class="text-gray-500"><?= htmlspecialchars($c['contact'] ?? '—') ?></td>
              <td><span class="badge badge-info"><?= $c['order_count'] ?> orders</span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($companies)): ?><tr><td colspan="4" class="text-center py-6 text-gray-400">No companies assigned</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Recent Orders -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-700">Recent Orders</h3></div>
        <table class="data-table">
          <thead><tr><th>Order#</th><th>Company</th><th>SR</th><th>Date</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($recentOrders as $o):
              $sc = ['pending'=>'badge-warning','out_for_delivery'=>'badge-info','delivered'=>'badge-success','cancelled'=>'badge-danger'];
            ?>
            <tr>
              <td class="font-mono text-xs">#<?= str_pad($o['id'],4,'0',STR_PAD_LEFT) ?></td>
              <td><?= htmlspecialchars($o['company_name']) ?></td>
              <td><?= htmlspecialchars($o['sr_name']) ?></td>
              <td class="text-xs text-gray-500"><?= $o['order_date'] ?></td>
              <td><span class="badge <?= $sc[$o['status']] ?? 'badge-gray' ?>"><?= str_replace('_',' ',ucfirst($o['status'])) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentOrders)): ?><tr><td colspan="5" class="text-center py-6 text-gray-400">No orders yet</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
