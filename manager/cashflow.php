<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Cash Flow';
$pdo       = getDB();
$wid       = $_SESSION['warehouse_id'];

// Get dispatches for settlement
$dispatches = $pdo->prepare("SELECT d.id, d.dispatch_date, u.name as dsr_name, d.status, COALESCE(cs.id,0) as settled FROM dispatches d JOIN dsr ds ON ds.id=d.dsr_id JOIN users u ON u.id=ds.user_id LEFT JOIN cash_settlements cs ON cs.dispatch_id=d.id WHERE d.warehouse_id=? ORDER BY d.id DESC");
$dispatches->execute([$wid]); $dispatches = $dispatches->fetchAll();

// Existing settlements
$settlements = $pdo->prepare("SELECT cs.*, u.name as dsr_name, d.dispatch_date FROM cash_settlements cs JOIN dsr ds ON ds.id=cs.dsr_id JOIN users u ON u.id=ds.user_id JOIN dispatches d ON d.id=cs.dispatch_id WHERE d.warehouse_id=? ORDER BY cs.id DESC");
$settlements->execute([$wid]); $settlements = $settlements->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">
      <div class="mb-6"><h2 class="text-xl font-bold text-gray-800">Cash Flow</h2><p class="text-sm text-gray-500">Record cash settlements from DSRs</p></div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- New Settlement Form -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
          <h3 class="font-semibold text-gray-700 mb-4">New Settlement</h3>
          <form id="settle-form" class="space-y-4">
            <div>
              <label class="form-label">Select Dispatch *</label>
              <select id="dispatch-sel" class="form-input" required onchange="calcExpected()">
                <option value="">Select Dispatch</option>
                <?php foreach ($dispatches as $d): if ($d['settled']) continue; ?>
                  <option value="<?= $d['id'] ?>" data-dsr="<?= $d['id'] ?>">#<?= $d['id'] ?> — <?= htmlspecialchars($d['dsr_name']) ?> (<?= $d['dispatch_date'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label">Expected Amount (৳)</label>
              <input id="amount-expected" type="number" step="0.01" class="form-input bg-gray-50" readonly placeholder="Auto-calculated" />
            </div>
            <div>
              <label class="form-label">Submitted Amount (৳) *</label>
              <input id="amount-submitted" type="number" step="0.01" class="form-input" required oninput="calcDiff()" />
            </div>
            <div>
              <label class="form-label">Difference (৳)</label>
              <input id="difference" type="number" step="0.01" class="form-input bg-gray-50" readonly />
            </div>
            <div>
              <label class="form-label">Notes</label>
              <textarea id="notes" class="form-input" rows="2" placeholder="Any remarks…"></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-full">Save Settlement</button>
          </form>
        </div>

        <!-- Settlement History -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100"><h3 class="font-semibold text-gray-700">Settlement History</h3></div>
          <table class="data-table">
            <thead><tr><th>Dispatch</th><th>DSR</th><th>Expected</th><th>Submitted</th><th>Diff</th></tr></thead>
            <tbody>
              <?php foreach ($settlements as $s): ?>
              <tr>
                <td class="font-mono text-xs">#<?= $s['dispatch_id'] ?> (<?= $s['dispatch_date'] ?>)</td>
                <td><?= htmlspecialchars($s['dsr_name']) ?></td>
                <td class="text-right">৳<?= number_format($s['amount_expected'], 0) ?></td>
                <td class="text-right">৳<?= number_format($s['amount_submitted'], 0) ?></td>
                <td class="text-right <?= $s['difference'] < 0 ? 'text-red-600' : 'text-green-600' ?>">৳<?= number_format(abs($s['difference']), 0) ?><?= $s['difference'] < 0 ? ' ↓' : ' ↑' ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($settlements)): ?><tr><td colspan="5" class="text-center py-6 text-gray-400">No settlements yet</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
async function calcExpected() {
  const did = document.getElementById('dispatch-sel').value;
  if (!did) return;
  // Calculate expected: sum of (qty_out * selling_price) for this dispatch
  const data = await api('/happycrm2/api/cashflow.php?dispatch_id=' + did);
  if (data.success) {
    document.getElementById('amount-expected').value = data.expected.toFixed(2);
    calcDiff();
  }
}
function calcDiff() {
  const exp = parseFloat(document.getElementById('amount-expected').value) || 0;
  const sub = parseFloat(document.getElementById('amount-submitted').value) || 0;
  document.getElementById('difference').value = (sub - exp).toFixed(2);
}
document.getElementById('settle-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const did = document.getElementById('dispatch-sel').value;
  const exp = parseFloat(document.getElementById('amount-expected').value) || 0;
  const sub = parseFloat(document.getElementById('amount-submitted').value) || 0;
  const diff = sub - exp;
  const data = await api('/happycrm2/api/cashflow.php', 'POST', {
    dispatch_id: parseInt(did), amount_expected: exp,
    amount_submitted: sub, difference: diff,
    notes: document.getElementById('notes').value
  });
  if (data.success) { showToast('Settlement saved!'); setTimeout(() => location.reload(), 1000); }
  else showToast(data.message || 'Error', 'error');
});
</script>
