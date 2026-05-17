<?php
// eggland/order_add.php — Create Order (Excel Spreadsheet Style)

require_once __DIR__ . '/data.php';

$pageTitle = 'Eggland Order Placement';

$msg = '';
$err = '';

// Load master data
$agents = eggGetAgents();
$products = eggGetProducts();

// Map products for quick validation lookup
$productMap = [];
foreach ($products as $p) {
    $productMap[intval($p['id'])] = $p;
}

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agentId = intval($_POST['agent_id'] ?? 0);
    $orderDate = $_POST['order_date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');
    
    // Dynamic items parsed from arrays
    $formProductIds = $_POST['item_product_id'] ?? [];
    $formQuantities = $_POST['item_quantity'] ?? [];
    
    // Validations
    $agent = eggGetAgent($agentId);
    
    if (!$agent) {
        $err = 'Please select a valid agent wholesaler.';
    } elseif (empty($formProductIds)) {
        $err = 'Please add at least one product row to the order sheet.';
    } else {
        $orderItems = [];
        $grandTotal = 0.0;
        
        for ($i = 0; $i < count($formProductIds); $i++) {
            $pId = intval($formProductIds[$i]);
            $qty = intval($formQuantities[$i] ?? 0);
            
            if ($pId <= 0 || $qty <= 0) {
                continue; // Skip invalid or blank rows
            }
            
            $p = $productMap[$pId] ?? null;
            if (!$p) {
                $err = 'One of the selected products in the sheet is invalid.';
                break;
            }
            
            $subtotal = floatval($p['price']) * $qty;
            $grandTotal += $subtotal;
            
            $orderItems[] = [
                'product_id' => $pId,
                'product_name' => $p['name'],
                'quantity' => $qty,
                'price' => floatval($p['price']),
                'subtotal' => $subtotal
            ];
        }
        
        if (empty($err)) {
            if (empty($orderItems)) {
                $err = 'All added product rows have invalid quantities. Order cannot be blank.';
            } else {
                $orders = eggGetOrders();
                $newOrderId = eggGetNextId($orders);
                $newOrder = [
                    'id' => $newOrderId,
                    'agent_id' => $agentId,
                    'order_date' => $orderDate,
                    'items' => $orderItems,
                    'total_amount' => $grandTotal,
                    'notes' => $notes,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $orders[] = $newOrder;
                if (eggSaveOrders($orders)) {
                    // Redirect to orders ledger list with success msg
                    header('Location: ' . rootPath() . '/eggland/orders.php?msg_success=1');
                    exit;
                } else {
                    $err = 'Failed to persist order invoice to JSON database.';
                }
            }
        }
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
            <span class="text-excel-green"><i class="fa-solid fa-cart-plus"></i></span> 
            Create Egg Order Billing Sheet
          </h2>
          <p class="text-xs text-gray-500 font-mono">Operations Mode: Interactive Order Placement Sheet</p>
        </div>
        <div>
          <a href="<?= rootPath() ?>/eggland/orders.php" class="btn-excel-ghost"><i class="fa-solid fa-arrow-left mr-1"></i> Back to Ledger</a>
        </div>
      </div>

      <!-- Alerts -->
      <?php if ($err): ?>
        <div class="bg-red-50 border border-red-500 text-red-800 px-4 py-2 mb-4 text-sm flex items-center gap-2">
          <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($err) ?>
        </div>
      <?php endif; ?>

      <!-- MAIN ORDER FORM -->
      <form action="" method="POST" id="order-form" onsubmit="return validateOrderSheet()">
        
        <!-- Metadata Row (Clean grid styling) -->
        <div class="bg-white border border-gray-200 p-4 mb-4">
          <h3 class="text-xs font-bold text-gray-400 font-mono uppercase tracking-wider mb-3">Sheet Headers & Billing Info</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="form-label">Select Agent / Wholesaler</label>
              <select name="agent_id" class="excel-input select" required>
                <option value="">-- Choose Agent --</option>
                <?php foreach ($agents as $a): ?>
                  <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?> (<?= htmlspecialchars($a['phone']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div>
              <label class="form-label">Order Date</label>
              <input type="date" name="order_date" class="excel-input" value="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div>
              <label class="form-label">Accountant Remarks / Shipping Notes</label>
              <input type="text" name="notes" class="excel-input" placeholder="e.g. Morning delivery, collect on bank DBBL transfer">
            </div>
          </div>
        </div>

        <!-- DYNAMIC ITEMS SPREADSHEET GRID -->
        <div class="bg-white border border-gray-200 p-4 mb-4">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold text-gray-400 font-mono uppercase tracking-wider">Order Items Grid Calculator</h3>
            <button type="button" onclick="appendItemRow()" class="btn-excel btn-sm py-1 px-2 text-xs">
              <i class="fa-solid fa-plus"></i> Add Row
            </button>
          </div>

          <div class="excel-grid-container">
            <table class="excel-table" id="items-grid-table">
              <thead>
                <tr>
                  <th class="excel-row-header">Row</th>
                  <th>A: Product Variety</th>
                  <th style="width: 140px;">B: Unit Type</th>
                  <th class="text-right" style="width: 150px;">C: Unit Rate (BDT)</th>
                  <th class="text-center" style="width: 140px;">D: Quantity</th>
                  <th class="text-right" style="width: 180px;">E: Subtotal Amount</th>
                  <th class="text-center" style="width: 60px;">F: Clear</th>
                </tr>
              </thead>
              <tbody id="items-grid-body">
                <!-- Javascript will inject initial row -->
              </tbody>
              <tfoot>
                <tr class="bg-gray-50 font-bold border-t-2 border-gray-300">
                  <td class="excel-row-header font-sans">#</td>
                  <td colspan="4" class="text-right text-gray-700 uppercase tracking-wider text-[11px] font-sans">Grand Total Billing Amount:</td>
                  <td class="text-right text-excel-green font-mono text-base border-r border-gray-200" id="grand-total-display">৳ 0.00</td>
                  <td>&nbsp;</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Form Submission Button -->
        <div class="flex justify-end gap-3 pb-6">
          <a href="<?= rootPath() ?>/eggland/orders.php" class="btn-excel-ghost py-2.5 px-5">Cancel Order</a>
          <button type="submit" class="btn-excel py-2.5 px-6 font-bold"><i class="fa-solid fa-save"></i> Save & Authorize Order</button>
        </div>

      </form>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Javascript Interactive Engine -->
<script>
// Array map of products for easy client retrieval
const productsCatalog = <?= json_encode($products) ?>;
const productsMap = {};
productsCatalog.forEach(p => {
    productsMap[p.id] = p;
});

let rowCounter = 0;

function appendItemRow() {
    rowCounter++;
    
    const tbody = document.getElementById('items-grid-body');
    const tr = document.createElement('tr');
    tr.id = 'row-' + rowCounter;
    
    // Generate options html
    let optionsHtml = '<option value="">-- Select Product --</option>';
    productsCatalog.forEach(p => {
        optionsHtml += `<option value="${p.id}" data-price="${p.price}" data-unit="${p.unit}">${p.name} (৳${p.price.toFixed(2)})</option>`;
    });

    tr.innerHTML = `
        <td class="excel-row-header row-num-indicator">${rowCounter}</td>
        <td>
            <select name="item_product_id[]" class="excel-input select-product" onchange="onProductSelect(${rowCounter})" required>
                ${optionsHtml}
            </select>
        </td>
        <td>
            <span class="badge badge-gray text-xs font-mono text-center block py-1 w-full item-unit-label" id="unit-label-${rowCounter}">—</span>
        </td>
        <td class="text-right font-mono font-bold text-gray-700">
            ৳ <span id="rate-display-${rowCounter}">0.00</span>
        </td>
        <td>
            <input type="number" name="item_quantity[]" class="excel-input text-center font-mono input-qty" id="qty-${rowCounter}" value="1" min="1" required oninput="calculateRow(${rowCounter})">
        </td>
        <td class="text-right font-mono font-bold text-excel-green">
            ৳ <span class="row-subtotal-val" id="subtotal-display-${rowCounter}">0.00</span>
        </td>
        <td class="text-center">
            <button type="button" class="text-red-500 hover:text-red-700 font-bold focus:outline-none" onclick="removeItemRow(${rowCounter})">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(tr);
    reindexRows();
}

function removeItemRow(rId) {
    const row = document.getElementById('row-' + rId);
    if (row) {
        row.remove();
        reindexRows();
        recalculateGrandTotal();
    }
}

function reindexRows() {
    const rows = document.querySelectorAll('#items-grid-body tr');
    let index = 1;
    rows.forEach(tr => {
        tr.querySelector('.row-num-indicator').innerText = index++;
    });
}

function onProductSelect(rId) {
    const tr = document.getElementById('row-' + rId);
    const select = tr.querySelector('.select-product');
    const selectedOption = select.options[select.selectedIndex];
    
    const rateDisplay = document.getElementById('rate-display-' + rId);
    const unitLabel = document.getElementById('unit-label-' + rId);
    
    if (selectedOption && selectedOption.value !== "") {
        const price = parseFloat(selectedOption.getAttribute('data-price') || 0);
        const unit = selectedOption.getAttribute('data-unit') || 'Piece';
        
        rateDisplay.innerText = price.toFixed(2);
        unitLabel.innerText = unit;
        
        calculateRow(rId);
    } else {
        rateDisplay.innerText = "0.00";
        unitLabel.innerText = "—";
        document.getElementById('subtotal-display-' + rId).innerText = "0.00";
        recalculateGrandTotal();
    }
}

function calculateRow(rId) {
    const tr = document.getElementById('row-' + rId);
    const select = tr.querySelector('.select-product');
    const selectedOption = select.options[select.selectedIndex];
    
    const qtyInput = document.getElementById('qty-' + rId);
    const subtotalDisplay = document.getElementById('subtotal-display-' + rId);
    
    if (selectedOption && selectedOption.value !== "") {
        const price = parseFloat(selectedOption.getAttribute('data-price') || 0);
        const qty = parseInt(qtyInput.value) || 0;
        
        const subtotal = price * qty;
        subtotalDisplay.innerText = subtotal.toFixed(2);
    } else {
        subtotalDisplay.innerText = "0.00";
    }
    
    recalculateGrandTotal();
}

function recalculateGrandTotal() {
    const subtotals = document.querySelectorAll('.row-subtotal-val');
    let grand = 0.0;
    subtotals.forEach(span => {
        grand += parseFloat(span.innerText || 0);
    });
    
    document.getElementById('grand-total-display').innerText = '৳ ' + grand.toFixed(2);
}

function validateOrderSheet() {
    const rows = document.querySelectorAll('#items-grid-body tr');
    if (rows.length === 0) {
        alert("Excel Sheet Alert: You must add at least one product row to save the order!");
        return false;
    }
    
    let validRowFound = false;
    rows.forEach(tr => {
        const select = tr.querySelector('.select-product');
        const qtyInput = tr.querySelector('.input-qty');
        
        if (select && select.value !== "" && parseInt(qtyInput.value) > 0) {
            validRowFound = true;
        }
    });
    
    if (!validRowFound) {
        alert("Excel Sheet Alert: You must select a product and enter a valid quantity in at least one row!");
        return false;
    }
    
    return true;
}

// Initialise with one empty row at startup
document.addEventListener("DOMContentLoaded", function() {
    appendItemRow();
});
</script>
