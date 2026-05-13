<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Expenses';
$pdo       = getDB();
$wid       = $_SESSION['warehouse_id'];

$expenses = $pdo->prepare("SELECT e.*, u.name as dsr_name, d.dispatch_date FROM expenses e JOIN dsr ds ON ds.id=e.dsr_id JOIN users u ON u.id=ds.user_id LEFT JOIN dispatches d ON d.id=e.dispatch_id WHERE ds.warehouse_id=? ORDER BY e.id DESC");
$expenses->execute([$wid]); $expenses = $expenses->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">
      <div class="mb-6"><h2 class="text-xl font-bold text-gray-800">DSR Expenses</h2><p class="text-sm text-gray-500">Approve or reject expense claims from DSRs</p></div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="data-table">
          <thead><tr><th>#</th><th>DSR</th><th>Amount</th><th>Description</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($expenses as $i => $e):
              $sc = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger'];
            ?>
            <tr>
              <td class="text-gray-400 text-xs"><?= $i+1 ?></td>
              <td class="font-medium"><?= htmlspecialchars($e['dsr_name']) ?></td>
              <td class="font-semibold text-indigo-700">৳<?= number_format($e['amount'], 2) ?></td>
              <td class="text-gray-600"><?= htmlspecialchars($e['description'] ?? '') ?></td>
              <td class="text-xs text-gray-500"><?= $e['expense_date'] ?></td>
              <td><span class="badge <?= $sc[$e['status']] ?? 'badge-gray' ?>"><?= ucfirst($e['status']) ?></span></td>
              <td>
                <?php if ($e['status'] === 'pending'): ?>
                <div class="flex gap-1">
                  <button onclick="setStatus(<?= $e['id'] ?>,'approved')" class="btn btn-success btn-sm">Approve</button>
                  <button onclick="setStatus(<?= $e['id'] ?>,'rejected')" class="btn btn-danger btn-sm">Reject</button>
                </div>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($expenses)): ?><tr><td colspan="7" class="text-center py-8 text-gray-400">No expenses submitted yet</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
async function setStatus(id, status) {
  const data = await api('/happycrm2/api/expenses.php', 'PUT', { id, status });
  if (data.success) { showToast('Expense ' + status); location.reload(); }
  else showToast(data.message || 'Error', 'error');
}
</script>
