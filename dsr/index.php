<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('dsr');
$pageTitle = 'DSR Dashboard';
$pdo       = getDB();
$user_id   = $_SESSION['user_id'];

// Get DSR record
$dsr = $pdo->prepare('SELECT d.*, w.name as warehouse_name FROM dsr d JOIN warehouses w ON w.id=d.warehouse_id WHERE d.user_id=?');
$dsr->execute([$user_id]); $dsr = $dsr->fetch();
$dsr_id = $dsr['id'] ?? 0;

// Stats
$myDispatches  = $pdo->prepare("SELECT COUNT(*) FROM dispatches WHERE dsr_id=?"); $myDispatches->execute([$dsr_id]); $myDispatches = $myDispatches->fetchColumn();
$activeDisp    = $pdo->prepare("SELECT COUNT(*) FROM dispatches WHERE dsr_id=? AND status='loaded'"); $activeDisp->execute([$dsr_id]); $activeDisp = $activeDisp->fetchColumn();
$myExpenses    = $pdo->prepare("SELECT COUNT(*) FROM expenses WHERE dsr_id=? AND status='pending'"); $myExpenses->execute([$dsr_id]); $myExpenses = $myExpenses->fetchColumn();

// Recent dispatches
$recent = $pdo->prepare("SELECT d.*, o.order_date FROM dispatches d LEFT JOIN orders o ON o.id=d.order_id WHERE d.dsr_id=? ORDER BY d.id DESC LIMIT 8");
$recent->execute([$dsr_id]); $recent = $recent->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">
      <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800">My Dashboard</h2>
        <p class="text-sm text-gray-500">Welcome, <?= htmlspecialchars($_SESSION['name']) ?> — <?= htmlspecialchars($dsr['warehouse_name'] ?? '') ?></p>
      </div>

      <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="stat-card">
          <div class="stat-card-icon bg-blue-100 mb-2">🚚</div>
          <div class="text-2xl font-bold"><?= $myDispatches ?></div>
          <div class="text-xs text-gray-500">Total Deliveries</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-yellow-100 mb-2">📦</div>
          <div class="text-2xl font-bold"><?= $activeDisp ?></div>
          <div class="text-xs text-gray-500">Active Loads</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon bg-red-100 mb-2">💰</div>
          <div class="text-2xl font-bold"><?= $myExpenses ?></div>
          <div class="text-xs text-gray-500">Pending Expenses</div>
        </div>
      </div>

      <div class="flex gap-3 mb-6">
        <a href="/happycrm2/dsr/expenses.php" class="btn btn-primary">+ Add Expense</a>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-700">My Recent Dispatches</h3></div>
        <table class="data-table">
          <thead><tr><th>Dispatch#</th><th>Date</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($recent as $r):
              $sc = ['loading'=>'badge-warning','loaded'=>'badge-info','delivered'=>'badge-success','settled'=>'badge-gray'];
            ?>
            <tr>
              <td class="font-mono text-xs">#<?= str_pad($r['id'],4,'0',STR_PAD_LEFT) ?></td>
              <td><?= $r['dispatch_date'] ?></td>
              <td><span class="badge <?= $sc[$r['status']] ?? 'badge-gray' ?>"><?= ucfirst($r['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recent)): ?><tr><td colspan="3" class="text-center py-6 text-gray-400">No dispatches yet</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
