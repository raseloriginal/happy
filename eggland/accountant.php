<?php
// eggland/accountant.php — Accountant Deposits CRUD (Excel Sheet Style)

require_once __DIR__ . '/data.php';

$pageTitle = 'Eggland Accountant Ledger';

$msg = '';
$err = '';

// Handle Actions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $agentId = intval($_POST['agent_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $date = $_POST['deposit_date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '');
        
        if ($agentId <= 0 || $amount <= 0) {
            $err = 'Please select an agent and enter a positive deposit amount.';
        } else {
            $deposits = eggGetDeposits();
            $newDeposit = [
                'id' => eggGetNextId($deposits),
                'agent_id' => $agentId,
                'amount' => $amount,
                'deposit_date' => $date,
                'notes' => $notes ?: 'Deposit payment received.',
                'created_at' => date('Y-m-d H:i:s')
            ];
            $deposits[] = $newDeposit;
            if (eggSaveDeposits($deposits)) {
                $msg = 'Deposit of ৳' . number_format($amount, 2) . ' logged successfully!';
            } else {
                $err = 'Failed to record deposit.';
            }
        }
    }
    
    elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $agentId = intval($_POST['agent_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $date = $_POST['deposit_date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '');
        
        if (!$id || $agentId <= 0 || $amount <= 0) {
            $err = 'Invalid deposit update inputs.';
        } else {
            $deposits = eggGetDeposits();
            $updated = false;
            foreach ($deposits as &$dep) {
                if (intval($dep['id']) === $id) {
                    $dep['agent_id'] = $agentId;
                    $dep['amount'] = $amount;
                    $dep['deposit_date'] = $date;
                    $dep['notes'] = $notes;
                    $updated = true;
                    break;
                }
            }
            if ($updated && eggSaveDeposits($deposits)) {
                $msg = 'Deposit record updated successfully!';
            } else {
                $err = 'Failed to update deposit record.';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            $err = 'Invalid deposit selection.';
        } else {
            $deposits = eggGetDeposits();
            $filtered = [];
            $found = false;
            foreach ($deposits as $dep) {
                if (intval($dep['id']) === $id) {
                    $found = true;
                    continue;
                }
                $filtered[] = $dep;
            }
            if ($found && eggSaveDeposits($filtered)) {
                $msg = 'Deposit record deleted successfully!';
            } else {
                $err = 'Failed to delete deposit record or deposit not found.';
            }
        }
    }
}

// Load data
$deposits = eggGetDeposits();
$agents = eggGetAgents();

// Map agents for quick lookup in grids
$agentMap = [];
foreach ($agents as $a) {
    $agentMap[intval($a['id'])] = $a;
}

include __DIR__ . '/../includes/header.php';
echo getExcelStyles();
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">

      <!-- Header & Excel Ribbon Brand -->
      <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-3 gap-2">
        <div>
          <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <span class="text-excel-green"><i class="fa-solid fa-calculator"></i></span> 
            Accountant Desk: Cash Deposits
          </h2>
          <p class="text-xs text-gray-500 font-mono">Operations Mode: Double-Entry Credit Receipts Registry</p>
        </div>
        <div>
          <button onclick="openAddDepositModal()" class="btn-excel"><i class="fa-solid fa-plus mr-1"></i> Log New Deposit</button>
        </div>
      </div>

      <!-- Excel tabs -->
      <?= renderExcelTabs('accountant') ?>

      <!-- Alerts -->
      <?php if ($msg): ?>
        <div class="bg-emerald-50 border border-emerald-500 text-emerald-800 px-4 py-2 mb-4 text-sm flex items-center gap-2">
          <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>
      <?php if ($err): ?>
        <div class="bg-red-50 border border-red-500 text-red-800 px-4 py-2 mb-4 text-sm flex items-center gap-2">
          <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($err) ?>
        </div>
      <?php endif; ?>

      <!-- DEPOSITS SPREADSHEET SPREAD -->
      <div class="bg-white border border-gray-200 p-4">
        <div class="excel-grid-container">
          <table class="excel-table">
            <thead>
              <tr>
                <th class="excel-row-header">Row</th>
                <th>A: Receipt Ref</th>
                <th>B: Agent/Shop Name</th>
                <th>C: Contact Phone</th>
                <th>D: Cash Deposit Date</th>
                <th class="text-right">E: Amount Deposited</th>
                <th>F: Accountant Remarks / Vouchers</th>
                <th class="text-center" style="width: 180px;">G: Voucher Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $rowNum = 1;
              // Sort deposits by date (most recent first)
              usort($deposits, function($a, $b) {
                  return intval($b['id']) <=> intval($a['id']);
              });
              
              foreach ($deposits as $d):
                  $agentId = intval($d['agent_id']);
                  $agentName = htmlspecialchars($agentMap[$agentId]['name'] ?? 'Unknown Agent');
                  $agentPhone = htmlspecialchars($agentMap[$agentId]['phone'] ?? 'N/A');
              ?>
              <tr>
                <td class="excel-row-header"><?= $rowNum++ ?></td>
                <td class="font-mono text-xs text-gray-500">#<?= str_pad($d['id'], 4, '0', STR_PAD_LEFT) ?></td>
                <td class="font-semibold text-gray-800">
                  <a href="<?= rootPath() ?>/eggland/agents.php?view_statement=<?= $agentId ?>" class="text-excel-green hover:underline">
                    <?= $agentName ?>
                  </a>
                </td>
                <td class="font-mono text-xs"><?= $agentPhone ?></td>
                <td class="font-mono text-xs"><?= htmlspecialchars($d['deposit_date']) ?></td>
                <td class="text-right font-mono font-bold text-blue-600">৳ <?= number_format($d['amount'], 2) ?></td>
                <td class="text-xs text-gray-500 italic max-w-[200px] truncate" title="<?= htmlspecialchars($d['notes']) ?>">
                  <?= htmlspecialchars($d['notes']) ?>
                </td>
                <td class="text-center flex justify-center gap-2">
                  <button onclick="openEditDepositModal(<?= htmlspecialchars(json_encode($d), ENT_QUOTES) ?>)" class="btn-excel btn-sm py-1 px-2 text-[11px]">
                    <i class="fa-solid fa-edit"></i> Edit
                  </button>
                  <button onclick="triggerDeleteDeposit(<?= $d['id'] ?>, <?= $d['amount'] ?>)" class="btn-excel-danger btn-sm py-1 px-2 text-[11px]">
                    <i class="fa-solid fa-trash"></i> Delete
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($deposits)): ?>
              <tr>
                <td class="excel-row-header">1</td>
                <td colspan="7" class="text-center py-6 text-gray-400">No cash deposit logs registered yet.</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ADD DEPOSIT MODAL -->
<div id="add-deposit-modal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-4">
      <h3 class="font-bold text-gray-800 text-base"><i class="fa-solid fa-calculator text-excel-green"></i> Log Cash Receipt / Deposit Voucher</h3>
      <button onclick="closeAddDepositModal()" class="text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
    </div>
    
    <form action="" method="POST" class="space-y-3">
      <input type="hidden" name="action" value="add">
      
      <div>
        <label class="form-label">Select Agent / Shop</label>
        <select name="agent_id" class="excel-input select" required>
          <option value="">-- Choose Agent --</option>
          <?php foreach ($agents as $a): ?>
            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?> (<?= htmlspecialchars($a['phone']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="form-label">Deposit Amount (BDT)</label>
          <input type="number" step="0.01" name="amount" class="excel-input font-mono" placeholder="0.00" required min="0.01">
        </div>
        <div>
          <label class="form-label">Deposit Date</label>
          <input type="date" name="deposit_date" class="excel-input" value="<?= date('Y-m-d') ?>" required>
        </div>
      </div>

      <div>
        <label class="form-label">Accountant Remarks / Notes</label>
        <textarea name="notes" rows="3" class="excel-input" placeholder="e.g. Bank deposit voucher reference, cash collection route detail, etc." required></textarea>
      </div>

      <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
        <button type="button" onclick="closeAddDepositModal()" class="btn-excel-ghost">Cancel</button>
        <button type="submit" class="btn-excel">Save Voucher</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT DEPOSIT MODAL -->
<div id="edit-deposit-modal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-4">
      <h3 class="font-bold text-gray-800 text-base"><i class="fa-solid fa-edit text-excel-green"></i> Edit Deposit Voucher Details</h3>
      <button onclick="closeEditDepositModal()" class="text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
    </div>
    
    <form action="" method="POST" class="space-y-3">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit-deposit-id">
      
      <div>
        <label class="form-label">Select Agent / Shop</label>
        <select name="agent_id" id="edit-deposit-agent" class="excel-input select" required>
          <option value="">-- Choose Agent --</option>
          <?php foreach ($agents as $a): ?>
            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="form-label">Deposit Amount (BDT)</label>
          <input type="number" step="0.01" name="amount" id="edit-deposit-amount" class="excel-input font-mono" required min="0.01">
        </div>
        <div>
          <label class="form-label">Deposit Date</label>
          <input type="date" name="deposit_date" id="edit-deposit-date" class="excel-input" required>
        </div>
      </div>

      <div>
        <label class="form-label">Accountant Remarks / Notes</label>
        <textarea name="notes" id="edit-deposit-notes" rows="3" class="excel-input" required></textarea>
      </div>

      <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
        <button type="button" onclick="closeEditDepositModal()" class="btn-excel-ghost">Cancel</button>
        <button type="submit" class="btn-excel">Update Voucher</button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE DEPOSIT FORM -->
<form action="" method="POST" id="delete-deposit-form" class="hidden">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="delete-deposit-id">
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
function openAddDepositModal() {
  document.getElementById('add-deposit-modal').classList.remove('hidden');
}
function closeAddDepositModal() {
  document.getElementById('add-deposit-modal').classList.add('hidden');
}

function openEditDepositModal(dep) {
  document.getElementById('edit-deposit-id').value = dep.id;
  document.getElementById('edit-deposit-agent').value = dep.agent_id;
  document.getElementById('edit-deposit-amount').value = dep.amount;
  document.getElementById('edit-deposit-date').value = dep.deposit_date;
  document.getElementById('edit-deposit-notes').value = dep.notes;
  document.getElementById('edit-deposit-modal').classList.remove('hidden');
}
function closeEditDepositModal() {
  document.getElementById('edit-deposit-modal').classList.add('hidden');
}

function triggerDeleteDeposit(id, amount) {
  const amountStr = Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2 });
  if (confirm("Are you absolutely sure you want to delete cash deposit voucher #" + String(id).padStart(4, '0') + " for ৳" + amountStr + "?\nDeleting this voucher will immediately increase the agent's ledger outstanding balance!")) {
    document.getElementById('delete-deposit-id').value = id;
    document.getElementById('delete-deposit-form').submit();
  }
}
</script>
