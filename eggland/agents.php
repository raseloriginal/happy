<?php
// eggland/agents.php — Agents CRUD & Statement Ledger (Excel Sheet Style)

require_once __DIR__ . '/data.php';

$pageTitle = 'Eggland Agents Ledger';

$msg = '';
$err = '';

// Handle Actions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        if (!$name || !$phone) {
            $err = 'Agent Name and Phone Number are required.';
        } else {
            $agents = eggGetAgents();
            $newAgent = [
                'id' => eggGetNextId($agents),
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $agents[] = $newAgent;
            if (eggSaveAgents($agents)) {
                $msg = "Agent '{$name}' added successfully!";
            } else {
                $err = 'Failed to save agent details.';
            }
        }
    }
    
    elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        if (!$id || !$name || !$phone) {
            $err = 'Invalid input parameters.';
        } else {
            $agents = eggGetAgents();
            $updated = false;
            foreach ($agents as &$agent) {
                if (intval($agent['id']) === $id) {
                    $agent['name'] = $name;
                    $agent['phone'] = $phone;
                    $agent['address'] = $address;
                    $updated = true;
                    break;
                }
            }
            if ($updated && eggSaveAgents($agents)) {
                $msg = "Agent details updated successfully!";
            } else {
                $err = 'Failed to update agent details.';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            $err = 'Invalid agent selection.';
        } else {
            // Safety check: verify if agent has active orders or deposits
            $orders = eggGetOrders();
            $deposits = eggGetDeposits();
            $hasTrans = false;
            
            foreach ($orders as $o) {
                if (intval($o['agent_id']) === $id) { $hasTrans = true; break; }
            }
            if (!$hasTrans) {
                foreach ($deposits as $d) {
                    if (intval($d['agent_id']) === $id) { $hasTrans = true; break; }
                }
            }
            
            if ($hasTrans) {
                $err = 'Cannot delete agent: This agent has active transaction history (orders or deposits). Clean up their transactions first.';
            } else {
                $agents = eggGetAgents();
                $filtered = [];
                $found = false;
                foreach ($agents as $agent) {
                    if (intval($agent['id']) === $id) {
                        $found = true;
                        continue;
                    }
                    $filtered[] = $agent;
                }
                if ($found && eggSaveAgents($filtered)) {
                    $msg = 'Agent deleted successfully!';
                } else {
                    $err = 'Failed to delete agent or agent not found.';
                }
            }
        }
    }
}

// Load data
$ledger = eggGetLedgerData();
$viewAgentId = intval($_GET['view_statement'] ?? 0);
$statement = [];
$viewAgent = null;
if ($viewAgentId > 0) {
    $viewAgent = eggGetAgent($viewAgentId);
    if ($viewAgent) {
        $statement = eggGetAgentStatement($viewAgentId);
    }
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
            <span class="text-excel-green"><i class="fa-solid fa-users"></i></span> 
            Agent Management Ledger
          </h2>
          <p class="text-xs text-gray-500 font-mono">Operations Mode: Wholesaler & Agent Master Sheet</p>
        </div>
        <div>
          <button onclick="openAddAgentModal()" class="btn-excel"><i class="fa-solid fa-user-plus mr-1"></i> Add New Agent</button>
        </div>
      </div>

      <!-- Excel tabs -->
      <?= renderExcelTabs('agents') ?>

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

      <!-- AGENTS MASTER SPREADSHEET SPREAD -->
      <div class="bg-white border border-gray-200 p-4">
        <div class="excel-grid-container">
          <table class="excel-table">
            <thead>
              <tr>
                <th class="excel-row-header">Row</th>
                <th>A: Agent ID</th>
                <th>B: Agent Name</th>
                <th>C: Contact Phone</th>
                <th>D: Business Address</th>
                <th class="text-right">E: Cumulative Debits (Orders)</th>
                <th class="text-right">F: Cumulative Credits (Deposits)</th>
                <th class="text-right">G: Net Balance (Outstanding)</th>
                <th class="text-center" style="width: 260px;">H: Spreadsheet Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $rowNum = 1;
              foreach ($ledger as $aId => $data):
                  $a = $data['agent'];
                  $bal = floatval($data['balance']);
              ?>
              <tr>
                <td class="excel-row-header"><?= $rowNum++ ?></td>
                <td class="font-mono text-xs text-gray-500">#<?= str_pad($a['id'], 3, '0', STR_PAD_LEFT) ?></td>
                <td class="font-semibold text-gray-800"><?= htmlspecialchars($a['name']) ?></td>
                <td class="font-mono text-xs"><?= htmlspecialchars($a['phone']) ?></td>
                <td><?= htmlspecialchars($a['address']) ?></td>
                <td class="text-right font-mono">৳ <?= number_format($data['total_orders'], 2) ?></td>
                <td class="text-right font-mono text-blue-600">৳ <?= number_format($data['total_deposits'], 2) ?></td>
                <td class="text-right font-mono <?= $bal > 0 ? 'text-red-600 font-bold bg-red-50' : 'text-emerald-600' ?>">
                  ৳ <?= number_format($bal, 2) ?>
                </td>
                <td class="text-center flex justify-center gap-2">
                  <a href="?view_statement=<?= $a['id'] ?>" class="btn-excel btn-sm py-1 px-2 text-[11px]">
                    <i class="fa-solid fa-receipt"></i> Statement
                  </a>
                  <button onclick="openEditAgentModal(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)" class="btn-excel-ghost btn-sm py-1 px-2 text-[11px]">
                    <i class="fa-solid fa-edit"></i> Edit
                  </button>
                  <button onclick="triggerDeleteAgent(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name'], ENT_QUOTES) ?>')" class="btn-excel-danger btn-sm py-1 px-2 text-[11px]">
                    <i class="fa-solid fa-trash"></i> Delete
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($ledger)): ?>
              <tr>
                <td class="excel-row-header">1</td>
                <td colspan="8" class="text-center py-6 text-gray-400">No agents registered yet.</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ADD AGENT MODAL -->
<div id="add-agent-modal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-4">
      <h3 class="font-bold text-gray-800 text-base"><i class="fa-solid fa-user-plus text-excel-green"></i> Add New Agent Wholesaler</h3>
      <button onclick="closeAddAgentModal()" class="text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
    </div>
    
    <form action="" method="POST" class="space-y-3">
      <input type="hidden" name="action" value="add">
      
      <div>
        <label class="form-label">Shop/Agent Name</label>
        <input type="text" name="name" class="excel-input" placeholder="e.g. Karim Egg Traders" required>
      </div>

      <div>
        <label class="form-label">Contact Phone Number</label>
        <input type="text" name="phone" class="excel-input font-mono" placeholder="e.g. 01711122233" required>
      </div>

      <div>
        <label class="form-label">Delivery & Business Address</label>
        <textarea name="address" rows="3" class="excel-input" placeholder="e.g. Plot 15, Kawran Bazar, Dhaka" required></textarea>
      </div>

      <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
        <button type="button" onclick="closeAddAgentModal()" class="btn-excel-ghost">Cancel</button>
        <button type="submit" class="btn-excel">Save Agent</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT AGENT MODAL -->
<div id="edit-agent-modal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-4">
      <h3 class="font-bold text-gray-800 text-base"><i class="fa-solid fa-user-pen text-excel-green"></i> Edit Agent Details</h3>
      <button onclick="closeEditAgentModal()" class="text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
    </div>
    
    <form action="" method="POST" class="space-y-3">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit-agent-id">
      
      <div>
        <label class="form-label">Shop/Agent Name</label>
        <input type="text" name="name" id="edit-agent-name" class="excel-input" required>
      </div>

      <div>
        <label class="form-label">Contact Phone Number</label>
        <input type="text" name="phone" id="edit-agent-phone" class="excel-input font-mono" required>
      </div>

      <div>
        <label class="form-label">Delivery & Business Address</label>
        <textarea name="address" id="edit-agent-address" rows="3" class="excel-input" required></textarea>
      </div>

      <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
        <button type="button" onclick="closeEditAgentModal()" class="btn-excel-ghost">Cancel</button>
        <button type="submit" class="btn-excel">Update Details</button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE AGENT CONFIRMATION FORM -->
<form action="" method="POST" id="delete-agent-form" class="hidden">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="delete-agent-id">
</form>

<!-- AGENT TRANSACTION STATEMENT LEDGER MODAL -->
<?php if ($viewAgent): ?>
<div id="statement-modal" class="modal-overlay">
  <div class="modal-box modal-box-lg">
    <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-3">
      <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
        <i class="fa-solid fa-file-invoice-dollar text-excel-green"></i> 
        Account Statement Ledger: <?= htmlspecialchars($viewAgent['name']) ?>
      </h3>
      <a href="agents.php" class="text-gray-400 hover:text-gray-600 font-bold text-lg" style="text-decoration: none;">&times;</a>
    </div>

    <!-- Agent metadata header -->
    <div class="bg-gray-50 border border-gray-200 p-3 mb-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-xs font-mono">
      <div>
        <div class="text-gray-400">Agent Ref ID</div>
        <div class="font-bold text-gray-900">#<?= str_pad($viewAgent['id'], 3, '0', STR_PAD_LEFT) ?></div>
      </div>
      <div>
        <div class="text-gray-400">Phone Number</div>
        <div class="font-bold text-gray-900"><?= htmlspecialchars($viewAgent['phone']) ?></div>
      </div>
      <div>
        <div class="text-gray-400">Current Outstanding</div>
        <div class="font-bold text-red-600">৳ <?= number_format($ledger[$viewAgent['id']]['balance'] ?? 0, 2) ?></div>
      </div>
      <div>
        <div class="text-gray-400">Business Address</div>
        <div class="font-bold text-gray-900 truncate" title="<?= htmlspecialchars($viewAgent['address']) ?>"><?= htmlspecialchars($viewAgent['address']) ?></div>
      </div>
    </div>
    
    <div class="excel-grid-container" style="max-height: 50vh;">
      <table class="excel-table">
        <thead>
          <tr>
            <th class="excel-row-header">Row</th>
            <th>Date</th>
            <th>Reference ID</th>
            <th>Type</th>
            <th class="text-right">Debit (Charge/Sales)</th>
            <th class="text-right">Credit (Receipt/Payment)</th>
            <th class="text-right">Running Outstanding Balance</th>
            <th>Remarks / Notes</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $rowNum = 1;
          $runningBalance = 0.0;
          foreach ($statement as $tx):
              $debit = floatval($tx['debit']);
              $credit = floatval($tx['credit']);
              $runningBalance += ($debit - $credit);
          ?>
          <tr>
            <td class="excel-row-header"><?= $rowNum++ ?></td>
            <td class="font-mono text-xs"><?= htmlspecialchars($tx['date']) ?></td>
            <td class="font-mono text-xs font-bold text-gray-700">
              <?php if ($tx['type'] === 'ORDER'): ?>
                <a href="<?= rootPath() ?>/eggland/print_invoice.php?id=<?= $tx['id'] ?>" target="_blank" class="text-excel-green hover:underline">
                  <?= htmlspecialchars($tx['ref']) ?> <i class="fa-solid fa-up-right-from-square text-[9px] ml-0.5"></i>
                </a>
              <?php else: ?>
                <?= htmlspecialchars($tx['ref']) ?>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= $tx['type'] === 'ORDER' ? 'badge-warning' : 'badge-success' ?>">
                <?= $tx['type'] ?>
              </span>
            </td>
            <td class="text-right font-mono text-gray-900">
              <?= $debit > 0 ? '৳ ' . number_format($debit, 2) : '—' ?>
            </td>
            <td class="text-right font-mono text-blue-600">
              <?= $credit > 0 ? '৳ ' . number_format($credit, 2) : '—' ?>
            </td>
            <td class="text-right font-mono font-bold <?= $runningBalance > 0 ? 'text-red-600 bg-red-50' : 'text-emerald-600' ?>">
              ৳ <?= number_format($runningBalance, 2) ?>
            </td>
            <td class="text-xs text-gray-500 italic truncate max-w-[200px]" title="<?= htmlspecialchars($tx['notes']) ?>">
              <?= htmlspecialchars($tx['notes']) ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($statement)): ?>
          <tr>
            <td class="excel-row-header">1</td>
            <td colspan="7" class="text-center py-6 text-gray-400">This agent has no order or deposit history.</td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="flex justify-end gap-2 pt-3 border-t border-gray-200">
      <button onclick="window.printStatement()" class="btn-excel"><i class="fa-solid fa-print"></i> Print Statement</button>
      <a href="agents.php" class="btn-excel-ghost" style="text-decoration: none;">Close Sheet</a>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
function openAddAgentModal() {
  document.getElementById('add-agent-modal').classList.remove('hidden');
}
function closeAddAgentModal() {
  document.getElementById('add-agent-modal').classList.add('hidden');
}

function openEditAgentModal(agent) {
  document.getElementById('edit-agent-id').value = agent.id;
  document.getElementById('edit-agent-name').value = agent.name;
  document.getElementById('edit-agent-phone').value = agent.phone;
  document.getElementById('edit-agent-address').value = agent.address;
  document.getElementById('edit-agent-modal').classList.remove('hidden');
}
function closeEditAgentModal() {
  document.getElementById('edit-agent-modal').classList.add('hidden');
}

function triggerDeleteAgent(id, name) {
  if (confirm("Are you absolutely sure you want to delete agent '" + name + "'?\nThis action cannot be undone, and will fail if the agent has active transaction ledger rows.")) {
    document.getElementById('delete-agent-id').value = id;
    document.getElementById('delete-agent-form').submit();
  }
}

function printStatement() {
  // Simple browser printing of statement box content
  const modalBox = document.querySelector("#statement-modal .modal-box").innerHTML;
  const originalBody = document.body.innerHTML;
  
  // Hide all but modal box
  document.body.innerHTML = `
    <div style="padding: 20px; font-family: sans-serif;">
      <h2 style="text-align:center; color:#107c41; margin-bottom:5px;">EGGLAND BANGLADESH</h2>
      <h4 style="text-align:center; margin-top:0; color:#555;">STATEMENT OF ACCOUNT STATEMENT</h4>
      <hr style="border: 1px solid #107c41; margin-bottom: 20px;">
      ${modalBox}
    </div>
  `;
  // Hide buttons
  const buttons = document.querySelectorAll("button, a");
  buttons.forEach(btn => btn.style.display = 'none');
  
  window.print();
  // Restore body
  document.body.innerHTML = originalBody;
  window.location.reload();
}
</script>
