<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle  = 'Inventory';
$pdo        = getDB();
$wid        = $_SESSION['warehouse_id'];
$companies  = $pdo->query('SELECT id, name FROM companies WHERE status=1 ORDER BY name')->fetchAll();
$categories = $pdo->query('SELECT id, name FROM categories WHERE status=1 ORDER BY name')->fetchAll();

// Load inventory
$stmt = $pdo->prepare('SELECT i.*, p.name as product_name, p.pieces_per_box, p.selling_price, co.name as company_name, cat.name as category_name FROM inventory i JOIN products p ON p.id=i.product_id JOIN companies co ON co.id=p.company_id LEFT JOIN categories cat ON cat.id=p.category_id WHERE i.warehouse_id=? ORDER BY i.qty_boxes ASC, p.name');
$stmt->execute([$wid]);
$inventory = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">
      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Inventory</h2><p class="text-sm text-gray-500">Current stock levels in your warehouse</p></div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div>
          <label class="form-label">Company</label>
          <select id="f-company" class="form-input" onchange="filterTable()">
            <option value="">All Companies</option>
            <?php foreach ($companies as $c): ?><option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label">Category</label>
          <select id="f-cat" class="form-input" onchange="filterTable()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?><option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label">Search</label>
          <input id="f-search" class="form-input" placeholder="Product name…" oninput="filterTable()" />
        </div>
        <div class="flex items-center gap-2">
          <input type="checkbox" id="f-low" onchange="filterTable()" class="rounded" />
          <label for="f-low" class="text-sm text-gray-600">Low stock only</label>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="data-table" id="inv-table">
          <thead>
            <tr>
              <th>#</th><th>Product</th><th>Company</th><th>Category</th>
              <th class="text-right">Boxes</th><th class="text-right">Pieces</th>
              <th class="text-right">Stock Value</th><th>Last Updated</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($inventory as $i => $inv):
              $isLow = $inv['qty_boxes'] < 5;
              $rowClass = $inv['qty_boxes'] == 0 ? 'bg-red-50' : ($isLow ? 'bg-yellow-50' : '');
              $ppb = max((int)$inv['pieces_per_box'], 1);
              $total_pieces = ($inv['qty_boxes'] * $ppb) + $inv['qty_pieces'];
              $value = $total_pieces * $inv['selling_price'];
            ?>
            <tr class="cursor-pointer hover:bg-gray-50 <?= $rowClass ?>" onclick="toggleLogs(<?= $inv['product_id'] ?>)" data-company="<?= htmlspecialchars($inv['company_name']) ?>" data-cat="<?= htmlspecialchars($inv['category_name'] ?? '') ?>" data-low="<?= $isLow ? '1' : '0' ?>">
              <td class="text-gray-400 text-xs"><?= $i+1 ?></td>
              <td class="font-medium">
                <?= htmlspecialchars($inv['product_name']) ?>
                <?php if ($inv['qty_boxes'] == 0): ?><span class="badge badge-danger ml-2">Out of Stock</span><?php elseif ($isLow): ?><span class="badge badge-warning ml-2">Low</span><?php endif; ?>
              </td>
              <td><span class="badge badge-info"><?= htmlspecialchars($inv['company_name']) ?></span></td>
              <td class="text-gray-500"><?= htmlspecialchars($inv['category_name'] ?? '—') ?></td>
              <td class="text-right font-semibold <?= $isLow ? 'text-red-600' : 'text-gray-800' ?>"><?= $inv['qty_boxes'] ?></td>
              <td class="text-right text-gray-600"><?= $inv['qty_pieces'] ?></td>
              <td class="text-right text-green-700 font-medium">৳<?= number_format($value, 0) ?></td>
              <td class="text-xs text-gray-400"><?= date('d M Y H:i', strtotime($inv['last_updated'])) ?></td>
              <td class="text-gray-400"><i class="fa-solid fa-chevron-down"></i></td>
            </tr>
            <tr id="logs-row-<?= $inv['product_id'] ?>" style="display:none;" class="bg-gray-50/60">
              <td colspan="9" class="p-4 border-b">
                <div class="flex items-center justify-between mb-3">
                  <h4 class="font-bold text-gray-700">Stock History</h4>
                  <div class="flex gap-2 items-center text-sm">
                    <input type="date" id="date-from-<?= $inv['product_id'] ?>" class="form-input py-1 px-2 text-xs h-8" onchange="loadLogs(<?= $inv['product_id'] ?>)" />
                    <span class="text-gray-500">to</span>
                    <input type="date" id="date-to-<?= $inv['product_id'] ?>" class="form-input py-1 px-2 text-xs h-8" onchange="loadLogs(<?= $inv['product_id'] ?>)" />
                  </div>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                  <table class="w-full text-left text-sm m-0">
                    <thead class="bg-gray-100 text-gray-600">
                      <tr>
                        <th class="py-2 px-3 font-semibold text-xs uppercase tracking-wide">Date</th>
                        <th class="py-2 px-3 font-semibold text-xs uppercase tracking-wide">Activity</th>
                        <th class="py-2 px-3 font-semibold text-xs uppercase tracking-wide text-right">Change (+/-)</th>
                        <th class="py-2 px-3 font-semibold text-xs uppercase tracking-wide text-right">Balance</th>
                        <th class="py-2 px-3 font-semibold text-xs uppercase tracking-wide">User</th>
                        <th class="py-2 px-3 font-semibold text-xs uppercase tracking-wide">Notes</th>
                      </tr>
                    </thead>
                    <tbody id="logs-body-<?= $inv['product_id'] ?>">
                      <tr><td colspan="6" class="text-center py-4 text-gray-400">Loading...</td></tr>
                    </tbody>
                  </table>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($inventory)): ?><tr><td colspan="8" class="text-center py-8 text-gray-400">No inventory data yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
function filterTable() {
  const company = document.getElementById('f-company').value.toLowerCase();
  const cat     = document.getElementById('f-cat').value.toLowerCase();
  const search  = document.getElementById('f-search').value.toLowerCase();
  const lowOnly = document.getElementById('f-low').checked;

  document.querySelectorAll('#inv-table tbody tr').forEach(tr => {
    if (tr.id && tr.id.startsWith('logs-row-')) return;
    
    const tComp = (tr.dataset.company || '').toLowerCase();
    const tCat  = (tr.dataset.cat   || '').toLowerCase();
    const tLow  = tr.dataset.low === '1';
    const tText = tr.textContent.toLowerCase();

    const show = (!company || tComp.includes(company))
              && (!cat    || tCat.includes(cat))
              && (!search || tText.includes(search))
              && (!lowOnly || tLow);

    tr.style.display = show ? '' : 'none';
  });
}

let openLogsRow = null;

function toggleLogs(pid) {
  const row = document.getElementById('logs-row-' + pid);
  if (row.style.display === 'none') {
    if (openLogsRow && openLogsRow !== row) openLogsRow.style.display = 'none';
    row.style.display = '';
    openLogsRow = row;
    loadLogs(pid);
  } else {
    row.style.display = 'none';
    openLogsRow = null;
  }
}

async function loadLogs(pid) {
  const from = document.getElementById('date-from-' + pid).value;
  const to = document.getElementById('date-to-' + pid).value;
  const tbody = document.getElementById('logs-body-' + pid);
  
  tbody.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-gray-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading stock history...</td></tr>';
  
  let url = '<?= rootPath() ?>/api/inventory.php?action=logs&product_id=' + pid;
  if (from) url += '&from_date=' + from;
  if (to) url += '&to_date=' + to;
  
  const res = await api(url);
  if (res.success) {
    if (res.data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-gray-400">No stock history found for the selected period.</td></tr>';
      return;
    }
    
    tbody.innerHTML = res.data.map(l => {
      const isPos = l.change_boxes > 0 || l.change_pieces > 0;
      const isNeg = l.change_boxes < 0 || l.change_pieces < 0;
      const color = isPos ? 'text-green-600 bg-green-50' : (isNeg ? 'text-red-600 bg-red-50' : 'text-gray-500 bg-gray-50');
      const sign = isPos ? '+' : '';
      
      const actionName = l.action_type.replace(/_/g, ' ').toUpperCase();
      
      return `
        <tr class="border-t border-gray-100">
          <td class="py-2 px-3 whitespace-nowrap text-xs text-gray-500">${l.created_at}</td>
          <td class="py-2 px-3 font-medium text-gray-700 text-xs">${actionName}</td>
          <td class="py-2 px-3 text-right font-semibold text-sm">
            <span class="inline-block px-2 py-0.5 rounded ${color}">
              ${sign}${l.change_boxes}B ${l.change_pieces != 0 ? sign+l.change_pieces+'P' : ''}
            </span>
          </td>
          <td class="py-2 px-3 text-right font-medium text-gray-800">
            ${l.balance_boxes}B ${l.balance_pieces != 0 ? l.balance_pieces+'P' : ''}
          </td>
          <td class="py-2 px-3 text-xs text-gray-600">${l.user_name || 'System'}</td>
          <td class="py-2 px-3 text-xs text-gray-500 max-w-[200px] truncate" title="${l.notes || ''}">${l.notes || '-'}</td>
        </tr>
      `;
    }).join('');
  } else {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-red-400">Failed to load logs.</td></tr>';
  }
}
</script>
