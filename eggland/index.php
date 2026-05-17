<?php
// eggland/index.php — Eggland Dashboard (Excel Sheet Style)

require_once __DIR__ . '/data.php';

$pageTitle = 'Eggland Dashboard';
$mgr_name = $_SESSION['name'];

// Load data
$ledger = eggGetLedgerData();
$products = eggGetProducts();
$orders = eggGetOrders();
$deposits = eggGetDeposits();

// Calculate Stats
$totalRevenue = 0.0;
foreach ($orders as $o) {
    $totalRevenue += floatval($o['total_amount']);
}

$totalReceived = 0.0;
foreach ($deposits as $d) {
    $totalReceived += floatval($d['amount']);
}

$totalOutstanding = $totalRevenue - $totalReceived;
$agentsCount = count($ledger);
$productsCount = count($products);

// Prepare Chart Data (Order totals grouped by date)
$chartData = [];
foreach ($orders as $o) {
    $date = $o['order_date'];
    if (!isset($chartData[$date])) {
        $chartData[$date] = 0.0;
    }
    $chartData[$date] += floatval($o['total_amount']);
}
ksort($chartData); // sort by date

// Handle quick deposit from dashboard
$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_deposit') {
    $agentId = intval($_POST['agent_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $date = $_POST['deposit_date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');

    if ($agentId <= 0) {
        $err = 'Please select a valid agent.';
    } elseif ($amount <= 0) {
        $err = 'Please enter a valid deposit amount.';
    } else {
        $allDeposits = eggGetDeposits();
        $newDep = [
            'id' => eggGetNextId($allDeposits),
            'agent_id' => $agentId,
            'amount' => $amount,
            'deposit_date' => $date,
            'notes' => $notes ?: 'Quick deposit from dashboard.',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $allDeposits[] = $newDep;
        if (eggSaveDeposits($allDeposits)) {
            $msg = 'Deposit of ৳' . number_format($amount, 2) . ' recorded successfully!';
            // Refresh data variables
            $ledger = eggGetLedgerData();
            $deposits = eggGetDeposits();
            $totalReceived += $amount;
            $totalOutstanding -= $amount;
        } else {
            $err = 'Failed to save deposit record.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
echo getExcelStyles(); // Inject Excel Theme CSS
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
            <span class="text-excel-green"><i class="fa-solid fa-egg"></i></span> 
            Eggland Bangladesh
          </h2>
          <p class="text-xs text-gray-500 font-mono">Operations Mode: Fully Separated Business Unit | Active Manager: <?= htmlspecialchars($mgr_name) ?></p>
        </div>
        <div class="flex flex-wrap gap-2">
          <a href="<?= rootPath() ?>/eggland/order_add.php" class="btn-excel"><i class="fa-solid fa-plus mr-1"></i> New Order</a>
          <button onclick="openQuickDepositModal()" class="btn-excel-ghost"><i class="fa-solid fa-calculator mr-1"></i> Log Deposit</button>
        </div>
      </div>

      <!-- Excel tabs sheet selection -->
      <?= renderExcelTabs('dashboard') ?>

      <!-- Alert Status Blocks -->
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

      <!-- KPI Excel Cards Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
        <div class="excel-kpi-card">
          <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Sales Revenue</div>
          <div class="text-xl font-bold text-gray-900 font-mono">৳ <?= number_format($totalRevenue, 2) ?></div>
          <div class="text-[10px] text-gray-400 mt-1">Sum of all orders</div>
        </div>
        <div class="excel-kpi-card" style="border-left-color: #0078d4 !important;">
          <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Cash Received</div>
          <div class="text-xl font-bold text-gray-900 font-mono text-blue-600">৳ <?= number_format($totalReceived, 2) ?></div>
          <div class="text-[10px] text-gray-400 mt-1">Sum of deposits</div>
        </div>
        <div class="excel-kpi-card" style="border-left-color: <?= $totalOutstanding > 0 ? '#d83b01' : '#107c41' ?> !important;">
          <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Net Outstanding</div>
          <div class="text-xl font-bold font-mono <?= $totalOutstanding > 0 ? 'text-orange-600' : 'text-emerald-600' ?>">
            ৳ <?= number_format($totalOutstanding, 2) ?>
          </div>
          <div class="text-[10px] text-gray-400 mt-1">Pending payments</div>
        </div>
        <div class="excel-kpi-card" style="border-left-color: #7a7574 !important;">
          <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Registered Agents</div>
          <div class="text-xl font-bold text-gray-900 font-mono"><?= $agentsCount ?></div>
          <div class="text-[10px] text-gray-400 mt-1">Egg wholesalers</div>
        </div>
        <div class="excel-kpi-card" style="border-left-color: #8764b8 !important;">
          <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Egg Products</div>
          <div class="text-xl font-bold text-gray-900 font-mono"><?= $productsCount ?></div>
          <div class="text-[10px] text-gray-400 mt-1">Available varieties</div>
        </div>
      </div>

      <!-- Charts & Quick Data Row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
        <div class="bg-white border border-gray-200 p-4 lg:col-span-2">
          <h3 class="font-bold text-gray-700 mb-3 text-sm flex items-center gap-2">
            <i class="fa-solid fa-chart-simple text-excel-green"></i> 
            Egg Sales Timeline (Daily BDT Turnover)
          </h3>
          <div class="h-64">
            <canvas id="eggSalesChart"></canvas>
          </div>
        </div>

        <div class="bg-white border border-gray-200 p-4">
          <h3 class="font-bold text-gray-700 mb-3 text-sm flex items-center gap-2">
            <i class="fa-solid fa-file-invoice text-excel-green"></i> 
            Quick Actions Sheet
          </h3>
          <div class="space-y-2">
            <a href="<?= rootPath() ?>/eggland/order_add.php" class="w-full btn-excel justify-center py-3">
              <i class="fa-solid fa-cart-plus"></i> Place Egg Order
            </a>
            <button onclick="openQuickDepositModal()" class="w-full btn-excel-ghost justify-center py-3">
              <i class="fa-solid fa-money-bill-transfer"></i> Record Agent Deposit
            </button>
            <a href="<?= rootPath() ?>/eggland/agents.php" class="w-full btn-excel-ghost justify-center py-3">
              <i class="fa-solid fa-user-plus"></i> Add New Agent
            </a>
            <a href="<?= rootPath() ?>/eggland/products.php" class="w-full btn-excel-ghost justify-center py-3">
              <i class="fa-solid fa-box-open"></i> Manage Products
            </a>
          </div>
        </div>
      </div>

      <!-- OUTSTANDING LEDGER GRID TABLE (Excel Sheet style) -->
      <div class="bg-white border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-bold text-gray-700 text-sm flex items-center gap-2">
            <i class="fa-solid fa-calculator text-excel-green"></i> 
            Active Outstanding Ledger (Agents Balances)
          </h3>
          <span class="text-xs text-gray-400 font-mono">Formula: [Outstanding] = [Total Orders] - [Total Deposits]</span>
        </div>

        <div class="excel-grid-container">
          <table class="excel-table">
            <thead>
              <tr>
                <th class="excel-row-header font-sans">Row</th>
                <th>A: Agent Shop Name</th>
                <th>B: Contact Phone</th>
                <th>C: Business Address</th>
                <th class="text-right">D: Total Ordered Amount</th>
                <th class="text-right">E: Total Deposited Amount</th>
                <th class="text-right">F: Outstanding Balance</th>
                <th class="text-center" style="width: 180px;">G: Ledger Actions</th>
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
                <td class="font-semibold text-gray-800">
                  <a href="<?= rootPath() ?>/eggland/agents.php?view_statement=<?= $a['id'] ?>" class="hover:underline text-excel-green">
                    <?= htmlspecialchars($a['name']) ?>
                  </a>
                </td>
                <td class="font-mono text-xs"><?= htmlspecialchars($a['phone']) ?></td>
                <td><?= htmlspecialchars($a['address']) ?></td>
                <td class="text-right font-mono">৳ <?= number_format($data['total_orders'], 2) ?></td>
                <td class="text-right font-mono text-blue-600">৳ <?= number_format($data['total_deposits'], 2) ?></td>
                <td class="text-right font-mono <?= $bal > 0 ? 'text-red-600 font-bold bg-red-50' : 'text-emerald-600' ?>">
                  ৳ <?= number_format($bal, 2) ?>
                </td>
                <td class="text-center flex justify-center gap-2">
                  <button onclick="triggerDepositModal(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name'], ENT_QUOTES) ?>')" class="btn-excel btn-sm py-1 px-2 text-[11px]" title="Collect Payment">
                    <i class="fa-solid fa-money-bill-wave"></i> Deposit
                  </button>
                  <a href="<?= rootPath() ?>/eggland/agents.php?view_statement=<?= $a['id'] ?>" class="btn-excel-ghost btn-sm py-1 px-2 text-[11px]" title="Statement Ledger">
                    <i class="fa-solid fa-book"></i> Ledger
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($ledger)): ?>
              <tr>
                <td class="excel-row-header">1</td>
                <td colspan="7" class="text-center py-6 text-gray-400">No agents registered in Eggland yet.</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- QUICK DEPOSIT MODAL -->
<div id="quick-deposit-modal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-4">
      <h3 class="font-bold text-gray-800 text-base"><i class="fa-solid fa-money-check-dollar text-excel-green"></i> Log Agent Deposit (Receipt Form)</h3>
      <button onclick="closeQuickDepositModal()" class="text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
    </div>
    
    <form action="" method="POST" class="space-y-3">
      <input type="hidden" name="action" value="quick_deposit">
      
      <div>
        <label class="form-label">Select Agent / Shop</label>
        <select name="agent_id" id="deposit-agent-select" class="excel-input select" required>
          <option value="">-- Choose Agent --</option>
          <?php foreach ($ledger as $aId => $data): ?>
            <option value="<?= $aId ?>"><?= htmlspecialchars($data['agent']['name']) ?> (Bal: ৳<?= number_format($data['balance'], 2) ?>)</option>
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
        <textarea name="notes" rows="2" class="excel-input" placeholder="Bank receipt no, cash collection detail, etc."></textarea>
      </div>

      <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
        <button type="button" onclick="closeQuickDepositModal()" class="btn-excel-ghost">Cancel</button>
        <button type="submit" class="btn-excel">Save Receipt</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Chart rendering -->
<script>
document.addEventListener("DOMContentLoaded", function() {
  const chartData = <?= json_encode($chartData) ?>;
  const labels = Object.keys(chartData);
  const values = Object.values(chartData);

  new Chart(document.getElementById('eggSalesChart'), {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Order Total (BDT)',
        data: values,
        backgroundColor: '#107c41',
        borderColor: '#0a5c30',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: '#f1f5f9' },
          ticks: { font: { family: 'monospace' } }
        },
        x: {
          grid: { display: false }
        }
      }
    }
  });
});

function openQuickDepositModal() {
  document.getElementById('deposit-agent-select').value = "";
  document.getElementById('quick-deposit-modal').classList.remove('hidden');
}

function closeQuickDepositModal() {
  document.getElementById('quick-deposit-modal').classList.add('hidden');
}

function triggerDepositModal(agentId, agentName) {
  document.getElementById('deposit-agent-select').value = agentId;
  document.getElementById('quick-deposit-modal').classList.remove('hidden');
}
</script>
