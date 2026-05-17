<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole(['manager', 'admin']);
$pageTitle = 'Ready Sale Orders';
$pdo       = getDB();

// Fetch completed ready sales with product details
$rows = $pdo->query("
    SELECT o.id as order_id, o.order_date, o.status, o.created_at, o.retailer_name, o.retailer_phone,
           u.name as sr_name, c.id as company_id, c.name as company_name, c.contact as company_phone,
           p.name as product_name, p.selling_price, p.pieces_per_box,
           oi.qty_pieces, oi.unit_price, oi.product_id
    FROM orders o
    JOIN sr s ON s.id=o.sr_id
    JOIN users u ON u.id=s.user_id
    JOIN companies c ON c.id=o.company_id
    JOIN order_items oi ON oi.order_id=o.id
    JOIN products p ON p.id=oi.product_id
    WHERE o.status = 'ready_sale'
    ORDER BY o.id DESC, oi.id
")->fetchAll();

$readySales = [];
foreach ($rows as $row) {
    $oid = $row['order_id'];
    if (!isset($readySales[$oid])) {
        $readySales[$oid] = [
            'order_id' => $oid,
            'order_date' => $row['order_date'],
            'created_at' => $row['created_at'],
            'status' => $row['status'],
            'sr_name' => $row['sr_name'],
            'company_id' => $row['company_id'],
            'company_name' => $row['company_name'],
            'company_phone' => $row['company_phone'],
            'retailer_name' => $row['retailer_name'],
            'retailer_phone' => $row['retailer_phone'],
            'total_amount' => 0,
            'product_count' => 0,
            'total_pieces' => 0,
            'items' => []
        ];
    }
    
    $subtotal = $row['qty_pieces'] * $row['unit_price'];
    $readySales[$oid]['total_amount'] += $subtotal;
    $readySales[$oid]['product_count']++;
    $readySales[$oid]['total_pieces'] += $row['qty_pieces'];
    $readySales[$oid]['items'][] = [
        'product_id' => $row['product_id'],
        'product_name' => $row['product_name'],
        'qty_pieces' => $row['qty_pieces'],
        'pieces_per_box' => $row['pieces_per_box'],
        'unit_price' => $row['unit_price'],
        'subtotal' => $subtotal
    ];
}

// Compute initial page load stats
$totalRevenue = 0;
$totalReadySalesCount = count($readySales);
$totalPiecesSold = 0;
$uniqueCompanies = [];

foreach ($readySales as $sale) {
    $totalRevenue += $sale['total_amount'];
    $totalPiecesSold += $sale['total_pieces'];
    $uniqueCompanies[$sale['company_id']] = true;
}
$activeOutletsCount = count($uniqueCompanies);

include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">

      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Ready Sale Orders</h2>
          <p class="text-sm text-gray-500 mt-1">Direct completed sales entries with product breakdown details</p>
        </div>
        <div class="flex items-center gap-2">
          <a href="<?= rootPath() ?>/manager/ready_sale_scan.php" class="btn btn-indigo flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold shadow-indigo-100 hover:shadow-lg transition-all duration-300">
            <i class="fa-solid fa-qrcode"></i> Scan New Sale
          </a>
          <a href="<?= rootPath() ?>/manager/orders.php" class="btn btn-ghost border border-gray-200 bg-white hover:bg-gray-50 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300">
            <i class="fa-solid fa-clipboard-list mr-1.5"></i> All Orders
          </a>
        </div>
      </div>

      <!-- High-End Stats Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-all duration-300">
          <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 mb-3">
            <i class="fa-solid fa-bangladeshi-taka-sign text-lg"></i>
          </div>
          <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Ready Sales Value</div>
          <div class="text-2xl font-bold text-gray-800 mt-1" id="metric-sales">৳<?= number_format($totalRevenue, 0) ?></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-all duration-300">
          <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 mb-3">
            <i class="fa-solid fa-receipt text-lg"></i>
          </div>
          <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Invoice Count</div>
          <div class="text-2xl font-bold text-gray-800 mt-1" id="metric-count"><?= $totalReadySalesCount ?></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-all duration-300">
          <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 mb-3">
            <i class="fa-solid fa-cubes text-lg"></i>
          </div>
          <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Pieces Dispatched</div>
          <div class="text-2xl font-bold text-gray-800 mt-1" id="metric-pieces"><?= number_format($totalPiecesSold) ?></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-all duration-300">
          <div class="w-10 h-10 bg-violet-50 rounded-xl flex items-center justify-center text-violet-600 mb-3">
            <i class="fa-solid fa-shop text-lg"></i>
          </div>
          <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Active Customers</div>
          <div class="text-2xl font-bold text-gray-800 mt-1" id="metric-companies"><?= $activeOutletsCount ?></div>
        </div>
      </div>

      <!-- Filters panel -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[240px]">
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Search Invoice</label>
          <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" id="f-search" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-gray-50/50 hover:bg-gray-50 transition-all duration-300" placeholder="Type Order ID, Customer, SR, or Product..." oninput="filterReadySales()" />
          </div>
        </div>
        <div class="w-full sm:w-auto">
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">From Date</label>
          <input type="date" id="f-from" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-gray-50/50 hover:bg-gray-50 transition-all duration-300" onchange="filterReadySales()" />
        </div>
        <div class="w-full sm:w-auto">
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">To Date</label>
          <input type="date" id="f-to" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-gray-50/50 hover:bg-gray-50 transition-all duration-300" onchange="filterReadySales()" />
        </div>
        <div class="w-full sm:w-auto flex gap-2">
          <button onclick="clearReadyFilters()" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-sm font-semibold text-gray-600 transition-all duration-300">
            Clear Filters
          </button>
        </div>
      </div>

      <!-- Ready Sales Card Container -->
      <div class="space-y-6" id="ready-sales-container">
        <?php foreach ($readySales as $sale): 
          // Build search string containing all details for real-time search
          $itemsSearchStr = '';
          foreach ($sale['items'] as $item) {
              $itemsSearchStr .= ' ' . $item['product_name'];
          }
          $searchData = "RS-" . str_pad($sale['order_id'], 5, '0', STR_PAD_LEFT) . " " . $sale['company_name'] . " " . ($sale['retailer_name'] ?? '') . " " . ($sale['retailer_phone'] ?? '') . " " . ($sale['company_phone'] ?? '') . " " . $sale['sr_name'] . $itemsSearchStr;
        ?>
        <div class="ready-sale-card bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md hover:border-gray-200 transition-all duration-300" 
             data-date="<?= $sale['order_date'] ?>"
             data-amount="<?= $sale['total_amount'] ?>"
             data-pieces="<?= $sale['total_pieces'] ?>"
             data-company="<?= htmlspecialchars($sale['company_id']) ?>"
             data-search-text="<?= htmlspecialchars($searchData) ?>">
          
          <!-- Card Header & Metadata -->
          <div class="px-5 py-4 border-b border-gray-50 bg-gray-50/30 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
              <span class="font-mono text-sm font-bold text-gray-900 bg-gray-100 px-3 py-1 rounded-lg">#RS-<?= str_pad($sale['order_id'], 5, '0', STR_PAD_LEFT) ?></span>
              <span class="text-xs text-gray-500 font-medium">
                <i class="fa-regular fa-clock text-indigo-500 mr-1"></i>
                <?= date('d M Y, h:i A', strtotime($sale['created_at'])) ?>
              </span>
              <span class="text-xs text-gray-500 font-medium">
                <i class="fa-solid fa-user-tag text-indigo-500 mr-1 ml-2"></i>
                SR: <?= htmlspecialchars($sale['sr_name']) ?>
              </span>
            </div>
            
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/50">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Paid
              </span>
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/50">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                Delivered
              </span>
            </div>
          </div>

          <!-- Customer Info and Summary -->
          <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
              
              <!-- Customer Detail Block -->
              <div class="space-y-1">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Customer Outlet</div>
                <div class="font-semibold text-gray-800 text-base"><?= htmlspecialchars($sale['company_name']) ?></div>
                <?php if (!empty($sale['retailer_name'])): ?>
                  <div class="text-xs text-indigo-600 font-bold flex items-center gap-1.5 mt-0.5">
                    <i class="fa-solid fa-store"></i>
                    Retailer: <?= htmlspecialchars($sale['retailer_name']) ?>
                  </div>
                <?php endif; ?>
                <div class="text-xs text-gray-500 flex items-center gap-1.5">
                  <i class="fa-solid fa-phone text-gray-400"></i>
                  <?= htmlspecialchars(!empty($sale['retailer_phone']) ? $sale['retailer_phone'] : ($sale['company_phone'] ?: 'No Phone Number')) ?>
                </div>
              </div>

              <!-- Metrics Mini block -->
              <div class="flex items-center gap-8 border-l border-r border-gray-100 px-6 py-1 h-full">
                <div>
                  <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Products</div>
                  <div class="text-lg font-bold text-gray-800 mt-0.5"><?= $sale['product_count'] ?> items</div>
                </div>
                <div>
                  <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total Quantity</div>
                  <div class="text-lg font-bold text-gray-800 mt-0.5"><?= number_format($sale['total_pieces']) ?> pcs</div>
                </div>
              </div>

              <!-- Financial Summary and Print CTA -->
              <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between md:justify-end gap-4">
                <div class="text-left md:text-right">
                  <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Invoice Grand Total</div>
                  <div class="text-xl font-black text-indigo-600 mt-0.5">৳<?= number_format($sale['total_amount'], 2) ?></div>
                </div>
                <a href="<?= rootPath() ?>/manager/print_invoice.php?id=<?= $sale['order_id'] ?>" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-indigo-50 hover:bg-indigo-600 text-indigo-600 hover:text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm transition-all duration-300">
                  <i class="fa-solid fa-print"></i> Print Invoice
                </a>
              </div>
            </div>

            <!-- Product Accordion Section -->
            <div class="mt-5 border-t border-gray-50 pt-4">
              <button onclick="toggleDetails(<?= $sale['order_id'] ?>)" class="flex items-center justify-between w-full text-xs font-semibold text-gray-400 uppercase tracking-wider hover:text-gray-600 transition-colors">
                <span>Included Products breakdown</span>
                <i class="fa-solid fa-chevron-up transition-transform duration-300" id="chevron-<?= $sale['order_id'] ?>"></i>
              </button>
              
              <div id="details-<?= $sale['order_id'] ?>" class="overflow-hidden transition-all duration-300">
                <div class="overflow-x-auto mt-3 rounded-xl border border-gray-100 bg-gray-50/40 p-2">
                  <table class="w-full border-collapse text-left text-sm text-gray-700">
                    <thead>
                      <tr class="border-b border-gray-100 text-xs font-bold text-gray-400 uppercase tracking-wider">
                        <th class="py-2.5 px-3">Product Name</th>
                        <th class="py-2.5 px-3 text-right">Quantity (pcs / boxes)</th>
                        <th class="py-2.5 px-3 text-right">Unit Price</th>
                        <th class="py-2.5 px-3 text-right">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                      <?php foreach ($sale['items'] as $item): ?>
                      <tr class="hover:bg-gray-50/30 transition-all duration-150">
                        <td class="py-3 px-3 font-medium text-gray-900"><?= htmlspecialchars($item['product_name']) ?></td>
                        <td class="py-3 px-3 text-right text-gray-700 font-mono font-semibold">
                          <?= htmlspecialchars($item['qty_pieces']) ?> pcs
                          <?php if ($item['pieces_per_box'] > 1): 
                            $boxes = floor($item['qty_pieces'] / $item['pieces_per_box']);
                            $rem = $item['qty_pieces'] % $item['pieces_per_box'];
                            echo '<span class="text-[10px] text-gray-400 font-normal block">';
                            if ($boxes > 0) echo $boxes . ' box';
                            if ($boxes > 0 && $rem > 0) echo ' + ';
                            if ($rem > 0 || $boxes == 0) echo $rem . ' pcs';
                            echo '</span>';
                          endif; ?>
                        </td>
                        <td class="py-3 px-3 text-right text-gray-500 font-mono">৳<?= number_format($item['unit_price'], 2) ?></td>
                        <td class="py-3 px-3 text-right font-bold text-indigo-600 font-mono">৳<?= number_format($item['subtotal'], 2) ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- No Records Empty State -->
      <div id="no-records-msg" class="text-center py-20 text-gray-400 border border-dashed border-gray-200 rounded-3xl bg-white mt-6" style="display: none;">
        <i class="fa-solid fa-receipt text-5xl text-gray-200 mb-4 block"></i>
        <p class="text-base font-semibold text-gray-500">No ready sale invoices match your query</p>
        <p class="text-xs text-gray-400 mt-1">Try tweaking your date range or typing different search terms</p>
        <button onclick="clearReadyFilters()" class="btn btn-primary mt-4 py-2 px-6 rounded-xl font-bold">Clear Filters</button>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// Filter Ready Sales List
function filterReadySales() {
  const query = (document.getElementById('f-search').value || '').toLowerCase().trim();
  const fromDate = document.getElementById('f-from').value;
  const toDate = document.getElementById('f-to').value;
  
  let matchCount = 0;
  let totalRev = 0;
  let totalPcs = 0;
  const companies = new Set();
  
  document.querySelectorAll('.ready-sale-card').forEach(card => {
    const dateVal = card.getAttribute('data-date');
    const textVal = card.getAttribute('data-search-text').toLowerCase();
    const amountVal = parseFloat(card.getAttribute('data-amount')) || 0;
    const piecesVal = parseInt(card.getAttribute('data-pieces')) || 0;
    const companyId = card.getAttribute('data-company');
    
    let show = true;
    
    if (fromDate && dateVal < fromDate) show = false;
    if (toDate && dateVal > toDate) show = false;
    
    if (query) {
      if (!textVal.includes(query)) {
        show = false;
      }
    }
    
    if (show) {
      card.style.display = '';
      matchCount++;
      totalRev += amountVal;
      totalPcs += piecesVal;
      companies.add(companyId);
    } else {
      card.style.display = 'none';
    }
  });
  
  // Real-time metrics sync
  document.getElementById('metric-count').textContent = matchCount;
  document.getElementById('metric-sales').textContent = '৳' + totalRev.toLocaleString('en-BD', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  document.getElementById('metric-pieces').textContent = totalPcs.toLocaleString('en-BD');
  document.getElementById('metric-companies').textContent = companies.size;
  
  const emptyMsg = document.getElementById('no-records-msg');
  if (matchCount === 0) {
    emptyMsg.style.display = '';
  } else {
    emptyMsg.style.display = 'none';
  }
}

// Clear Filters
function clearReadyFilters() {
  document.getElementById('f-search').value = '';
  document.getElementById('f-from').value = '';
  document.getElementById('f-to').value = '';
  filterReadySales();
}

// Accordion Toggle Details
function toggleDetails(id) {
  const el = document.getElementById('details-' + id);
  const chevron = document.getElementById('chevron-' + id);
  if (el.style.maxHeight === '0px' || el.style.maxHeight === '') {
    el.style.maxHeight = el.scrollHeight + 'px';
    chevron.classList.remove('rotate-180');
    el.style.opacity = '1';
  } else {
    el.style.maxHeight = '0px';
    chevron.classList.add('rotate-180');
    el.style.opacity = '0';
  }
}

// Reset date input defaults and filter on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  // Collapse details by default or set a quick timeout to establish maxHeight values
  document.querySelectorAll('.ready-sale-card').forEach(card => {
    const id = card.getAttribute('data-search-text').split(' ')[0].replace('RS-', '');
    const idNum = parseInt(id) || 0;
    if (idNum) {
      const details = document.getElementById('details-' + idNum);
      if (details) {
        details.style.maxHeight = details.scrollHeight + 'px'; // open by default as requested
        details.style.opacity = '1';
      }
    }
  });
  filterReadySales();
});
</script>
