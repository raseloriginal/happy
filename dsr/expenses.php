<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('dsr');
$pageTitle = 'My Expenses';
$pdo       = getDB();
$user_id   = $_SESSION['user_id'];

$dsr = $pdo->prepare('SELECT id FROM dsr WHERE user_id=? LIMIT 1');
$dsr->execute([$user_id]);
$dsr_id = $dsr->fetchColumn();

$dispatches = $pdo->prepare("SELECT id, dispatch_date FROM dispatches WHERE dsr_id=? ORDER BY id DESC");
$dispatches->execute([$dsr_id]);
$dispatches = $dispatches->fetchAll();

$expenses = $pdo->prepare("SELECT * FROM expenses WHERE dsr_id=? ORDER BY id DESC");
$expenses->execute([$dsr_id]);
$expenses = $expenses->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body max-w-4xl">
      <div class="mb-6"><h2 class="text-xl font-bold text-gray-800">My Expenses</h2></div>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
          <h3 class="font-semibold text-gray-700 mb-4">Submit Expense</h3>
          <form id="exp-form" class="space-y-4">
            <div>
              <label class="form-label">Linked Dispatch</label>
              <select id="dispatch-sel" class="form-input">
                <option value="">No dispatch</option>
                <?php foreach ($dispatches as $d): ?>
                  <option value="<?= $d['id'] ?>">#<?= $d['id'] ?> — <?= $d['dispatch_date'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div><label class="form-label">Amount (৳) *</label><input id="amount" type="number" step="0.01" class="form-input" required /></div>
            <div><label class="form-label">Description *</label><textarea id="description" class="form-input" rows="3" required></textarea></div>
            <div><label class="form-label">Date *</label><input id="exp-date" type="date" class="form-input" value="<?= date('Y-m-d') ?>" required /></div>
            <button type="submit" class="btn btn-primary w-full">Submit</button>
          </form>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100"><h3 class="font-semibold">History</h3></div>
          <table class="data-table">
            <thead><tr><th>Amount</th><th>Description</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($expenses as $e):
                $sc = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger'];
              ?>
              <tr>
                <td class="font-semibold text-indigo-700">৳<?= number_format($e['amount'],2) ?></td>
                <td class="text-sm"><?= htmlspecialchars($e['description']??'') ?></td>
                <td class="text-xs text-gray-500"><?= $e['expense_date'] ?></td>
                <td><span class="badge <?= $sc[$e['status']]??'badge-gray' ?>"><?= ucfirst($e['status']) ?></span></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($expenses)): ?><tr><td colspan="4" class="text-center py-6 text-gray-400">No expenses yet</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
document.getElementById('exp-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await api('<?= rootPath() ?>/api/expenses.php', 'POST', {
    dispatch_id: document.getElementById('dispatch-sel').value || null,
    amount: document.getElementById('amount').value,
    description: document.getElementById('description').value,
    expense_date: document.getElementById('exp-date').value
  });
  if (data.success) { showToast('Expense submitted!'); setTimeout(()=>location.reload(),1000); }
  else showToast(data.message||'Error','error');
});
</script>
