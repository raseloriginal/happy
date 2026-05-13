<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Lots';
$pdo       = getDB();
$wid       = $_SESSION['warehouse_id'];
$lots      = $pdo->prepare('SELECT l.*, c.name as company_name, w.name as warehouse_name, COUNT(li.id) as item_count FROM lots l JOIN companies c ON c.id=l.company_id JOIN warehouses w ON w.id=l.warehouse_id LEFT JOIN lot_items li ON li.lot_id=l.id WHERE l.status=1 AND l.warehouse_id=? GROUP BY l.id ORDER BY l.id DESC');
$lots->execute([$wid]);
$lots = $lots->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">
      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Lots</h2><p class="text-sm text-gray-500">Product batches received from companies</p></div>
        <a href="/happycrm2/manager/lot_add.php" class="btn btn-primary">+ Add Lot</a>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="data-table">
          <thead><tr><th>#</th><th>Date</th><th>Company</th><th>Warehouse</th><th>Items</th><th>Grand Total</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($lots as $i => $l): ?>
            <tr>
              <td class="text-gray-400 text-xs"><?= $i+1 ?></td>
              <td class="font-medium"><?= htmlspecialchars($l['lot_date']) ?></td>
              <td><span class="badge badge-info"><?= htmlspecialchars($l['company_name']) ?></span></td>
              <td><?= htmlspecialchars($l['warehouse_name']) ?></td>
              <td><?= $l['item_count'] ?> products</td>
              <td class="font-semibold text-green-700">৳<?= number_format($l['grand_total'], 2) ?></td>
              <td>
                <div class="flex gap-1 flex-wrap">
                  <a href="/happycrm2/manager/qr_generate.php?lot_id=<?= $l['id'] ?>" class="btn btn-primary btn-sm">🔲 QR</a>
                  <a href="/happycrm2/manager/qr_print.php?lot_id=<?= $l['id'] ?>" class="btn btn-ghost btn-sm">🖨 Print</a>
                  <a href="/happycrm2/manager/lot_view.php?id=<?= $l['id'] ?>" class="btn btn-ghost btn-sm">👁 View</a>
                  <button onclick="deleteLot(<?= $l['id'] ?>)" class="btn btn-danger btn-sm">Delete</button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($lots)): ?><tr><td colspan="7" class="text-center py-8 text-gray-400">No lots yet. <a href="/happycrm2/manager/lot_add.php" class="text-indigo-600">Add one</a></td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
async function deleteLot(id) {
  if (!confirmDelete('Delete this lot? This cannot be undone.')) return;
  const data = await api('/happycrm2/api/lots.php?id=' + id, 'DELETE');
  if (data.success) { showToast('Lot deleted'); location.reload(); }
  else showToast(data.message || 'Error', 'error');
}
</script>
