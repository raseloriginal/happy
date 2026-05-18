<?php
// manager/cashflow.php — Cash Flow Settlement & Verification
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Cash Flow & Settlements';
$pdo       = getDB();
$wid       = $_SESSION['warehouse_id'];

// Get active dispatches for manual settlement (excluding already settled dispatches)
$dispatches = $pdo->prepare("
    SELECT d.id, d.dispatch_date, u.name as dsr_name, d.status, COALESCE(cs.id, 0) as settled 
    FROM dispatches d 
    JOIN dsr ds ON ds.id=d.dsr_id 
    JOIN users u ON u.id=ds.user_id 
    LEFT JOIN cash_settlements cs ON cs.dispatch_id=d.id AND cs.status='approved'
    WHERE d.warehouse_id=? AND (d.status IN ('loaded', 'delivered') OR cs.id IS NULL)
    ORDER BY d.id DESC
");
$dispatches->execute([$wid]); 
$dispatches = $dispatches->fetchAll();

// Get DSR pending mobile settlements
$pendingMobile = $pdo->prepare("
    SELECT cs.*, u.name as dsr_name, d.dispatch_date, d.status as dispatch_status,
           o.id as order_id, c.name as company_name
    FROM cash_settlements cs 
    JOIN dsr ds ON ds.id=cs.dsr_id 
    JOIN users u ON u.id=ds.user_id 
    JOIN dispatches d ON d.id=cs.dispatch_id 
    LEFT JOIN orders o ON o.id=d.order_id
    LEFT JOIN companies c ON c.id=o.company_id
    WHERE cs.status='pending' AND d.warehouse_id=?
    ORDER BY cs.id DESC
");
$pendingMobile->execute([$wid]); 
$pendingMobile = $pendingMobile->fetchAll();

// Get settled history (excluding pending ones)
$settlements = $pdo->prepare("
    SELECT cs.*, u.name as dsr_name, d.dispatch_date 
    FROM cash_settlements cs 
    JOIN dsr ds ON ds.id=cs.dsr_id 
    JOIN users u ON u.id=ds.user_id 
    JOIN dispatches d ON d.id=cs.dispatch_id 
    WHERE d.warehouse_id=? AND cs.status='approved' 
    ORDER BY cs.id DESC LIMIT 50
");
$settlements->execute([$wid]); 
$settlements = $settlements->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">
      
      <!-- Top Title Bar -->
      <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-gray-800 tracking-tight font-outfit">Cash Flow & Settlements</h2>
          <p class="text-sm text-gray-500">Verify, audit and approve cash submissions from DSR delivery representatives</p>
        </div>
      </div>

      <!-- ================= PENDING MOBILE SETTLEMENTS SECTION ================= -->
      <?php if (!empty($pendingMobile)): ?>
        <div class="mb-8 bg-indigo-50/40 border border-indigo-100 rounded-xl p-5 shadow-sm">
          <div class="flex items-center gap-2.5 mb-4">
            <span class="flex h-3 w-3 relative">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-600"></span>
            </span>
            <h3 class="font-bold text-gray-800 text-base font-outfit">Pending DSR Mobile Submissions</h3>
            <span class="bg-indigo-600 text-white font-extrabold text-[10px] px-2 py-0.5 rounded-full"><?= count($pendingMobile) ?> NEW</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($pendingMobile as $pm): 
              $notes_details = json_decode($pm['notes_details'], true) ?: [];
              $diff = floatval($pm['difference']);
            ?>
              <div class="bg-white rounded-xl border border-indigo-100/80 shadow-md overflow-hidden flex flex-col justify-between transition-all hover:shadow-lg">
                <!-- Header -->
                <div class="px-5 py-3.5 bg-slate-900 text-white flex justify-between items-center">
                  <div>
                    <h4 class="font-bold text-sm"><?= htmlspecialchars($pm['dsr_name']) ?></h4>
                    <span class="text-[10px] text-gray-400 font-mono">Dispatch: #DISP-<?= str_pad($pm['dispatch_id'], 4, '0', STR_PAD_LEFT) ?> (<?= $pm['dispatch_date'] ?>)</span>
                  </div>
                  <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-[9px] font-black uppercase px-2 py-0.5 rounded">
                    <?= htmlspecialchars($pm['company_name'] ?? 'Ready Sale') ?>
                  </span>
                </div>

                <!-- Financial Ledger Breakdown -->
                <div class="p-5 space-y-4 flex-1">
                  <div class="grid grid-cols-3 gap-2.5 text-center text-xs">
                    <div class="bg-gray-50 border border-gray-100 p-2 rounded-lg">
                      <span class="text-[9px] text-gray-400 block uppercase font-bold">DSR Expected</span>
                      <span class="text-sm font-extrabold text-gray-700 font-outfit">৳<?= number_format($pm['amount_expected'], 2) ?></span>
                    </div>
                    <div class="bg-indigo-50 border border-indigo-100 p-2 rounded-lg">
                      <span class="text-[9px] text-indigo-500 block uppercase font-bold">DSR Submitted</span>
                      <span class="text-sm font-extrabold text-indigo-700 font-outfit">৳<?= number_format($pm['amount_submitted'], 2) ?></span>
                    </div>
                    <div class="p-2 rounded-lg border <?= $diff < 0 ? 'bg-red-50 border-red-100 text-red-700' : ($diff > 0 ? 'bg-indigo-50 border-indigo-100 text-indigo-700' : 'bg-emerald-50 border-emerald-100 text-emerald-700') ?>">
                      <span class="text-[9px] block uppercase font-bold opacity-80">Difference</span>
                      <span class="text-sm font-extrabold font-outfit">
                        ৳<?= number_format($diff, 2) ?>
                      </span>
                    </div>
                  </div>

                  <!-- Damages and Expenses inputs -->
                  <div class="grid grid-cols-2 gap-3 text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                    <div>
                      <span class="text-[9px] text-gray-500 font-bold block uppercase mb-0.5">Damages Subtracted</span>
                      <span class="font-bold text-red-600 font-outfit">৳<?= number_format($pm['damage_amount'], 2) ?></span>
                    </div>
                    <div>
                      <span class="text-[9px] text-gray-500 font-bold block uppercase mb-0.5">Expenses Subtracted</span>
                      <span class="font-bold text-amber-600 font-outfit">৳<?= number_format($pm['expense_amount'], 2) ?></span>
                    </div>
                  </div>

                  <!-- Banknotes breakdown -->
                  <div>
                    <h5 class="text-[10px] text-gray-400 font-extrabold uppercase tracking-wide mb-2"><i class="fa-solid fa-sack-dollar text-indigo-400"></i> Cash Notes Count Breakdown</h5>
                    <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto pr-1">
                      <?php 
                      $hasNotes = false;
                      foreach ($notes_details as $denom => $qty): 
                        if ($qty > 0): 
                          $hasNotes = true;
                          
                          // Denomination Colors
                          $badgeColor = 'bg-slate-100 text-slate-700 border-slate-200';
                          if ($denom == 1000) $badgeColor = 'bg-purple-50 text-purple-700 border-purple-200';
                          else if ($denom == 500) $badgeColor = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                          else if ($denom == 200) $badgeColor = 'bg-amber-50 text-amber-700 border-amber-200';
                          else if ($denom == 100) $badgeColor = 'bg-blue-50 text-blue-700 border-blue-200';
                          else if ($denom == 50) $badgeColor = 'bg-pink-50 text-pink-700 border-pink-200';
                        ?>
                          <span class="inline-flex items-center text-[10px] font-bold px-2 py-1 rounded border <?= $badgeColor ?>">
                            <?= $denom ?>৳ × <?= $qty ?>টি
                          </span>
                        <?php endif; endforeach; ?>
                      
                      <?php if (!$hasNotes): ?>
                        <span class="text-xs text-gray-400 italic">No banknote breakdown details provided</span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- DSR Remarks -->
                  <?php if (!empty($pm['notes'])): ?>
                    <div class="bg-gray-50 border border-gray-100 rounded-lg p-2.5 text-xs text-gray-600">
                      <span class="text-[9px] text-gray-400 font-black uppercase tracking-wider block">DSR Remarks</span>
                      <?= htmlspecialchars($pm['notes']) ?>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- Approval and Rejection actions footer -->
                <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex gap-2">
                  <button onclick="rejectSettlement(<?= $pm['id'] ?>)" class="flex-1 bg-white hover:bg-red-50 text-red-600 font-semibold text-xs py-2 px-3 rounded-lg border border-red-200 flex items-center justify-center gap-1 transition-all">
                    <i class="fa-solid fa-rotate-left"></i> Reject & Re-Count
                  </button>
                  <button onclick="approveSettlement(<?= $pm['id'] ?>)" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2 px-3 rounded-lg flex items-center justify-center gap-1 shadow-sm transition-all">
                    <i class="fa-solid fa-circle-check"></i> Approve & Settle
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- ================= MANUAL SETTLEMENT GRID ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: New Manual Settlement Form -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 lg:col-span-1">
          <div class="mb-4">
            <h3 class="font-bold text-gray-700 text-base font-outfit">Manual Settlement entry</h3>
            <p class="text-xs text-gray-400">Process offline or manual cash receipts here</p>
          </div>
          
          <form id="settle-form" class="space-y-4">
            <div>
              <label class="form-label text-xs font-bold text-gray-500 uppercase tracking-wider">Select Active Dispatch *</label>
              <select id="dispatch-sel" class="form-input text-xs" required onchange="calcExpected()">
                <option value="">Choose Dispatch ID</option>
                <?php foreach ($dispatches as $d): ?>
                  <option value="<?= $d['id'] ?>">#<?= str_pad($d['id'], 4, '0', STR_PAD_LEFT) ?> — <?= htmlspecialchars($d['dsr_name']) ?> (<?= $d['dispatch_date'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label text-xs font-bold text-gray-500 uppercase tracking-wider">Calculated Expected (৳)</label>
              <input id="amount-expected" type="number" step="0.01" class="form-input bg-gray-50 text-xs font-semibold" readonly placeholder="Auto-calculated" />
            </div>
            <div>
              <label class="form-label text-xs font-bold text-gray-500 uppercase tracking-wider">Submitted Cash Amount (৳) *</label>
              <input id="amount-submitted" type="number" step="0.01" class="form-input text-xs font-bold font-outfit text-indigo-600 focus:ring-indigo-500" required oninput="calcDiff()" placeholder="0.00" />
            </div>
            <div>
              <label class="form-label text-xs font-bold text-gray-500 uppercase tracking-wider">Calculated Discrepancy (৳)</label>
              <input id="difference" type="number" step="0.01" class="form-input bg-gray-50 text-xs font-bold font-outfit" readonly placeholder="0.00" />
            </div>
            <div>
              <label class="form-label text-xs font-bold text-gray-500 uppercase tracking-wider">Settlement Remarks</label>
              <textarea id="notes" class="form-input text-xs" rows="2" placeholder="Manual notes…"></textarea>
            </div>
            
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs py-3 rounded-lg flex items-center justify-center gap-1.5 shadow shadow-indigo-600/10 transition-colors">
              <i class="fa-solid fa-save"></i> Save Manual Settlement
            </button>
          </form>
        </div>

        <!-- Right Column: Settlement History Table -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden lg:col-span-2 flex flex-col">
          <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-700 text-base font-outfit">Settled Cash Records</h3>
            <span class="text-xs text-gray-400 font-bold uppercase" id="history-total"><?= count($settlements) ?> settled</span>
          </div>

          <div class="overflow-x-auto flex-1">
            <table class="data-table w-full text-xs">
              <thead>
                <tr class="bg-slate-50 border-b border-gray-100">
                  <th class="text-left py-3 px-4 text-gray-500 font-bold uppercase">Dispatch</th>
                  <th class="text-left py-3 px-4 text-gray-500 font-bold uppercase">DSR Representative</th>
                  <th class="text-right py-3 px-4 text-gray-500 font-bold uppercase">Expected</th>
                  <th class="text-right py-3 px-4 text-gray-500 font-bold uppercase">Submitted</th>
                  <th class="text-right py-3 px-4 text-gray-500 font-bold uppercase">Audit Diff</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php foreach ($settlements as $s): ?>
                <tr class="hover:bg-slate-50/60">
                  <td class="py-3 px-4 font-mono text-[11px] font-bold text-indigo-600">
                    #DISP-<?= str_pad($s['dispatch_id'], 4, '0', STR_PAD_LEFT) ?>
                    <span class="block text-[9px] text-gray-400 font-normal mt-0.5"><?= $s['dispatch_date'] ?></span>
                  </td>
                  <td class="py-3 px-4 font-semibold text-gray-700"><?= htmlspecialchars($s['dsr_name']) ?></td>
                  <td class="py-3 px-4 text-right font-semibold font-outfit text-gray-600">৳<?= number_format($s['amount_expected'], 2) ?></td>
                  <td class="py-3 px-4 text-right font-extrabold font-outfit text-gray-800">৳<?= number_format($s['amount_submitted'], 2) ?></td>
                  <td class="py-3 px-4 text-right font-extrabold font-outfit <?= $s['difference'] < 0 ? 'text-red-600 bg-red-50/20' : ($s['difference'] > 0 ? 'text-indigo-600 bg-indigo-50/20' : 'text-emerald-600 bg-emerald-50/20') ?>">
                    ৳<?= number_format($s['difference'], 2) ?>
                    <span class="text-[9px] font-normal block mt-0.5 opacity-60">
                      <?= $s['difference'] < 0 ? 'Shortage ↓' : ($s['difference'] > 0 ? 'Surplus ↑' : 'Balanced') ?>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($settlements)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-8 text-gray-400 italic">
                      <i class="fa-solid fa-money-bills text-2xl block mb-2 opacity-50"></i>
                      No cash flow records settled for this warehouse yet today.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// Expected cash calculation routines
async function calcExpected() {
  const did = document.getElementById('dispatch-sel').value;
  if (!did) return;
  const data = await api('<?= rootPath() ?>/api/cashflow.php?dispatch_id=' + did);
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

// Manual Save Event
document.getElementById('settle-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const did = document.getElementById('dispatch-sel').value;
  const exp = parseFloat(document.getElementById('amount-expected').value) || 0;
  const sub = parseFloat(document.getElementById('amount-submitted').value) || 0;
  const diff = sub - exp;
  const data = await api('<?= rootPath() ?>/api/cashflow.php', 'POST', {
    dispatch_id: parseInt(did), amount_expected: exp,
    amount_submitted: sub, difference: diff,
    notes: document.getElementById('notes').value
  });
  if (data.success) { 
    showToast('Manual settlement saved successfully!'); 
    setTimeout(() => location.reload(), 1000); 
  } else {
    showToast(data.message || 'Error saving settlement', 'error');
  }
});

// Approve DSR Mobile Settlement Submission
async function approveSettlement(settlementId) {
  if (!confirm('Are you sure you want to approve this DSR cash settlement? This will settle their active deliveries.')) return;
  
  const data = await api('<?= rootPath() ?>/api/cashflow.php?action=approve', 'POST', {
    settlement_id: settlementId
  });

  if (data.success) {
    showToast(data.message);
    setTimeout(() => location.reload(), 1200);
  } else {
    showToast(data.message || 'Approval failed', 'error');
  }
}

// Reject DSR Settlement and Return back for Recount
async function rejectSettlement(settlementId) {
  if (!confirm('Are you sure you want to reject this cash settlement? The DSR will receive a recount prompt on their phone and must re-enter cash notes.')) return;

  const data = await api('<?= rootPath() ?>/api/cashflow.php?action=reject', 'POST', {
    settlement_id: settlementId
  });

  if (data.success) {
    showToast(data.message, 'warning');
    setTimeout(() => location.reload(), 1200);
  } else {
    showToast(data.message || 'Rejection failed', 'error');
  }
}
</script>
