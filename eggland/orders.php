<?php
// eggland/orders.php — Orders List & Deletion (Excel Sheet Style)

require_once __DIR__ . '/data.php';

$pageTitle = 'Eggland Orders Ledger';

$msg = '';
$err = '';

// Handle Delete Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        $err = 'Invalid order selection.';
    } else {
        $orders = eggGetOrders();
        $filtered = [];
        $found = false;
        foreach ($orders as $o) {
            if (intval($o['id']) === $id) {
                $found = true;
                continue;
            }
            $filtered[] = $o;
        }
        if ($found && eggSaveOrders($filtered)) {
            $msg = 'Order #' . str_pad($id, 4, '0', STR_PAD_LEFT) . ' deleted successfully! Agent outstanding balances recalculated.';
        } else {
            $err = 'Failed to delete order record.';
        }
    }
}

// Load data
$orders = eggGetOrders();
$agents = eggGetAgents();

// Map agents for quick lookup
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
            <span class="text-excel-green"><i class="fa-solid fa-list-check"></i></span> 
            Orders Operations Ledger
          </h2>
          <p class="text-xs text-gray-500 font-mono">Operations Mode: Invoice Management & Sales Ledger</p>
        </div>
        <div>
          <a href="<?= rootPath() ?>/eggland/order_add.php" class="btn-excel"><i class="fa-solid fa-plus mr-1"></i> Place New Order</a>
        </div>
      </div>

      <!-- Excel tabs -->
      <?= renderExcelTabs('orders') ?>

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

      <!-- ORDERS SPREADSHEET SPREAD -->
      <div class="bg-white border border-gray-200 p-4">
        <div class="excel-grid-container">
          <table class="excel-table">
            <thead>
              <tr>
                <th class="excel-row-header">Row</th>
                <th>A: Order Invoice ID</th>
                <th>B: Agent/Shop Name</th>
                <th>C: Contact Phone</th>
                <th>D: Order Placement Date</th>
                <th>E: Products Summary</th>
                <th class="text-right">F: Grand Total Billing</th>
                <th>G: Accountant remarks</th>
                <th class="text-center" style="width: 240px;">H: Invoice Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $rowNum = 1;
              // Display orders (most recent first)
              usort($orders, function($a, $b) {
                  return intval($b['id']) <=> intval($a['id']);
              });
              
              foreach ($orders as $o):
                  $agentId = intval($o['agent_id']);
                  $agentName = htmlspecialchars($agentMap[$agentId]['name'] ?? 'Unknown Agent');
                  $agentPhone = htmlspecialchars($agentMap[$agentId]['phone'] ?? 'N/A');
                  
                  // Summary items text
                  $itemSummaries = [];
                  foreach ($o['items'] as $item) {
                      $itemSummaries[] = htmlspecialchars($item['product_name']) . " (" . $item['quantity'] . ")";
                  }
                  $itemsText = implode(', ', $itemSummaries);
              ?>
              <tr>
                <td class="excel-row-header"><?= $rowNum++ ?></td>
                <td class="font-mono text-xs font-bold text-gray-700">#<?= str_pad($o['id'], 4, '0', STR_PAD_LEFT) ?></td>
                <td class="font-semibold text-gray-800">
                  <a href="<?= rootPath() ?>/eggland/agents.php?view_statement=<?= $agentId ?>" class="text-excel-green hover:underline">
                    <?= $agentName ?>
                  </a>
                </td>
                <td class="font-mono text-xs"><?= $agentPhone ?></td>
                <td class="font-mono text-xs"><?= htmlspecialchars($o['order_date']) ?></td>
                <td class="text-xs text-gray-600 truncate max-w-[200px]" title="<?= $itemsText ?>">
                  <?= $itemsText ?>
                </td>
                <td class="text-right font-mono font-bold text-excel-green">৳ <?= number_format($o['total_amount'], 2) ?></td>
                <td class="text-xs text-gray-500 italic max-w-[150px] truncate" title="<?= htmlspecialchars($o['notes']) ?>">
                  <?= htmlspecialchars($o['notes'] ?: '—') ?>
                </td>
                <td class="text-center flex justify-center gap-2">
                  <a href="<?= rootPath() ?>/eggland/print_invoice.php?id=<?= $o['id'] ?>" target="_blank" class="btn-excel btn-sm py-1 px-2 text-[11px]">
                    <i class="fa-solid fa-print"></i> Invoice
                  </a>
                  <a href="<?= rootPath() ?>/eggland/order_edit.php?id=<?= $o['id'] ?>" class="btn-excel-ghost btn-sm py-1 px-2 text-[11px]" style="text-decoration: none;">
                    <i class="fa-solid fa-edit"></i> Edit
                  </a>
                  <button onclick="triggerDeleteOrder(<?= $o['id'] ?>)" class="btn-excel-danger btn-sm py-1 px-2 text-[11px]">
                    <i class="fa-solid fa-trash"></i> Delete
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($orders)): ?>
              <tr>
                <td class="excel-row-header">1</td>
                <td colspan="8" class="text-center py-6 text-gray-400">No egg orders recorded in ledger yet.</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- DELETE ORDER CONFIRMATION FORM -->
<form action="" method="POST" id="delete-order-form" class="hidden">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="delete-order-id">
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
function triggerDeleteOrder(id) {
  const paddedId = String(id).padStart(4, '0');
  if (confirm("Are you absolutely sure you want to delete Egg Order #" + paddedId + "?\nDeleting this invoice will deduct its total billing from the agent's ledger, reducing their outstanding balance immediately!")) {
    document.getElementById('delete-order-id').value = id;
    document.getElementById('delete-order-form').submit();
  }
}
</script>
