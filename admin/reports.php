<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');
$pageTitle = 'Reports';
$pdo       = getDB();

// Date range filter
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

// Summary stats
$totalOrders   = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_date BETWEEN ? AND ?")->execute([$from,$to]) ? 0 : 0;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_date BETWEEN ? AND ?"); $stmt->execute([$from,$to]); $totalOrders = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.qty_pieces * oi.unit_price),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.order_date BETWEEN ? AND ?"); $stmt->execute([$from,$to]); $totalRevenue = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM dispatches WHERE dispatch_date BETWEEN ? AND ?"); $stmt->execute([$from,$to]); $totalDisp = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date BETWEEN ? AND ? AND status='approved'"); $stmt->execute([$from,$to]); $totalExp = $stmt->fetchColumn();

// Orders by company
$byCompany = $pdo->prepare("SELECT c.name, COUNT(DISTINCT o.id) as orders, COALESCE(SUM(oi.qty_pieces * oi.unit_price),0) as revenue FROM orders o JOIN companies c ON c.id=o.company_id JOIN order_items oi ON oi.order_id=o.id WHERE o.order_date BETWEEN ? AND ? GROUP BY c.id ORDER BY revenue DESC");
$byCompany->execute([$from,$to]); $byCompany = $byCompany->fetchAll();

// Top products
$topProducts = $pdo->prepare("SELECT p.name, SUM(oi.qty_pieces) as qty, COALESCE(SUM(oi.qty_pieces*oi.unit_price),0) as value FROM order_items oi JOIN products p ON p.id=oi.product_id JOIN orders o ON o.id=oi.order_id WHERE o.order_date BETWEEN ? AND ? GROUP BY p.id ORDER BY qty DESC LIMIT 10");
$topProducts->execute([$from,$to]); $topProducts = $topProducts->fetchAll();

// DSR performance
$dsrPerf = $pdo->prepare("SELECT u.name, COUNT(d.id) as dispatches, COALESCE(SUM(cs.amount_submitted),0) as collected FROM dispatches d JOIN dsr ds ON ds.id=d.dsr_id JOIN users u ON u.id=ds.user_id LEFT JOIN cash_settlements cs ON cs.dispatch_id=d.id WHERE d.dispatch_date BETWEEN ? AND ? GROUP BY d.dsr_id ORDER BY dispatches DESC");
$dsrPerf->execute([$from,$to]); $dsrPerf = $dsrPerf->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">
      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Reports</h2><p class="text-sm text-gray-500">Business analytics and performance</p></div>
      </div>

      <!-- Date Filter -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-wrap gap-4 items-end">
        <form method="GET" class="flex gap-3 items-end flex-wrap">
          <div><label class="form-label">From</label><input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-input" /></div>
          <div><label class="form-label">To</label><input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-input" /></div>
          <button type="submit" class="btn btn-primary">Apply</button>
          <a href="reports.php" class="btn btn-ghost">Reset</a>
        </form>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card"><div class="stat-card-icon bg-indigo-100 mb-2 text-indigo-600"><i class="fa-solid fa-clipboard-list"></i></div><div class="text-2xl font-bold"><?= $totalOrders ?></div><div class="text-xs text-gray-500">Total Orders</div></div>
        <div class="stat-card"><div class="stat-card-icon bg-green-100 mb-2 text-green-600"><i class="fa-solid fa-money-bill-wave"></i></div><div class="text-2xl font-bold">৳<?= number_format($totalRevenue,0) ?></div><div class="text-xs text-gray-500">Revenue</div></div>
        <div class="stat-card"><div class="stat-card-icon bg-blue-100 mb-2 text-blue-600"><i class="fa-solid fa-truck"></i></div><div class="text-2xl font-bold"><?= $totalDisp ?></div><div class="text-xs text-gray-500">Dispatches</div></div>
        <div class="stat-card"><div class="stat-card-icon bg-red-100 mb-2 text-red-600"><i class="fa-solid fa-file-invoice-dollar"></i></div><div class="text-2xl font-bold">৳<?= number_format($totalExp,0) ?></div><div class="text-xs text-gray-500">Approved Expenses</div></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- By Company -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-700">Revenue by Company</h3></div>
          <table class="data-table">
            <thead><tr><th>Company</th><th class="text-right">Orders</th><th class="text-right">Revenue</th></tr></thead>
            <tbody>
              <?php foreach ($byCompany as $c): ?>
              <tr>
                <td class="font-medium"><?= htmlspecialchars($c['name']) ?></td>
                <td class="text-right"><?= $c['orders'] ?></td>
                <td class="text-right font-semibold text-green-700">৳<?= number_format($c['revenue'],0) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($byCompany)): ?><tr><td colspan="3" class="text-center py-6 text-gray-400">No data for this period</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Top Products -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-700">Top Products</h3></div>
          <table class="data-table">
            <thead><tr><th>Product</th><th class="text-right">Qty (pcs)</th><th class="text-right">Value</th></tr></thead>
            <tbody>
              <?php foreach ($topProducts as $p): ?>
              <tr>
                <td class="font-medium"><?= htmlspecialchars($p['name']) ?></td>
                <td class="text-right"><?= number_format($p['qty']) ?></td>
                <td class="text-right font-semibold text-indigo-700">৳<?= number_format($p['value'],0) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($topProducts)): ?><tr><td colspan="3" class="text-center py-6 text-gray-400">No data</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- DSR Performance -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-700">DSR Performance</h3></div>
        <table class="data-table">
          <thead><tr><th>DSR Name</th><th class="text-right">Dispatches</th><th class="text-right">Cash Collected</th></tr></thead>
          <tbody>
            <?php foreach ($dsrPerf as $d): ?>
            <tr>
              <td class="font-medium"><?= htmlspecialchars($d['name']) ?></td>
              <td class="text-right"><?= $d['dispatches'] ?></td>
              <td class="text-right font-semibold text-green-700">৳<?= number_format($d['collected'],0) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($dsrPerf)): ?><tr><td colspan="3" class="text-center py-6 text-gray-400">No data</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
