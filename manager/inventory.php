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
            <tr class="<?= $rowClass ?>" data-company="<?= htmlspecialchars($inv['company_name']) ?>" data-cat="<?= htmlspecialchars($inv['category_name'] ?? '') ?>" data-low="<?= $isLow ? '1' : '0' ?>">
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
</script>
