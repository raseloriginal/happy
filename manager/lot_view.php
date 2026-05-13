<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$id  = intval($_GET['id'] ?? 0);
$pdo = getDB();
$lot = $pdo->prepare('SELECT l.*, c.name as company_name, c.contact as company_contact, c.address as company_address, w.name as warehouse_name, u.name as manager_name FROM lots l JOIN companies c ON c.id=l.company_id JOIN warehouses w ON w.id=l.warehouse_id LEFT JOIN managers m ON m.id=l.manager_id LEFT JOIN users u ON u.id=m.user_id WHERE l.id=?');
$lot->execute([$id]); $lot = $lot->fetch();
if (!$lot) { echo 'Lot not found'; exit; }
$items = $pdo->prepare('SELECT li.*, p.name as product_name, p.pieces_per_box FROM lot_items li JOIN products p ON p.id=li.product_id WHERE li.lot_id=?');
$items->execute([$id]); $items = $items->fetchAll();
$pageTitle = 'Lot Invoice #' . $id;
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body max-w-3xl">
      <div class="flex items-center justify-between mb-6 print:hidden">
        <a href="<?= rootPath() ?>/manager/lots.php" class="btn btn-ghost">← Back</a>
        <button onclick="window.print()" class="btn btn-primary">🖨 Print Invoice</button>
      </div>

      <div id="invoice" class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
          <div>
            <div class="text-2xl font-black text-indigo-600">Happy Bangladesh</div>
            <div class="text-gray-500 text-sm mt-1">Lot Invoice</div>
          </div>
          <div class="text-right">
            <div class="text-lg font-bold text-gray-800">LOT #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></div>
            <div class="text-sm text-gray-500">Date: <?= htmlspecialchars($lot['lot_date']) ?></div>
            <div class="text-sm text-gray-500">Created: <?= date('d M Y', strtotime($lot['created_at'])) ?></div>
          </div>
        </div>

        <!-- Company + Warehouse Info -->
        <div class="grid grid-cols-2 gap-6 mb-8 pb-6 border-b border-gray-100">
          <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-2">Company</div>
            <div class="font-bold text-gray-800"><?= htmlspecialchars($lot['company_name']) ?></div>
            <?php if ($lot['company_contact']): ?><div class="text-sm text-gray-500"><?= htmlspecialchars($lot['company_contact']) ?></div><?php endif; ?>
            <?php if ($lot['company_address']): ?><div class="text-sm text-gray-500"><?= htmlspecialchars($lot['company_address']) ?></div><?php endif; ?>
          </div>
          <div>
            <div class="text-xs font-semibold text-gray-400 uppercase mb-2">Warehouse</div>
            <div class="font-bold text-gray-800"><?= htmlspecialchars($lot['warehouse_name']) ?></div>
            <div class="text-sm text-gray-500">Manager: <?= htmlspecialchars($lot['manager_name'] ?? '—') ?></div>
          </div>
        </div>

        <!-- Items Table -->
        <table class="data-table mb-8">
          <thead>
            <tr>
              <th>#</th>
              <th>Product</th>
              <th class="text-right">Qty (Boxes)</th>
              <th class="text-right">Buying Price</th>
              <th class="text-right">Total</th>
              <th class="text-center">QR Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $i => $item): ?>
            <tr>
              <td class="text-gray-400"><?= $i+1 ?></td>
              <td class="font-medium"><?= htmlspecialchars($item['product_name']) ?></td>
              <td class="text-right"><?= $item['qty_boxes'] ?></td>
              <td class="text-right">৳<?= number_format($item['buying_price'], 2) ?></td>
              <td class="text-right font-semibold">৳<?= number_format($item['total'], 2) ?></td>
              <td class="text-center">
                <?php if ($item['qr_generated']): ?>
                  <span class="badge badge-success">Generated ✓</span>
                <?php else: ?>
                  <span class="badge badge-warning">Pending</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Grand Total -->
        <div class="flex justify-end">
          <div class="bg-indigo-50 rounded-xl p-4 text-right min-w-48">
            <div class="text-sm text-gray-500">Grand Total</div>
            <div class="text-3xl font-black text-indigo-600">৳<?= number_format($lot['grand_total'], 2) ?></div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<style>
@media print {
  .sidebar, .main-content > header, .print\:hidden { display: none !important; }
  .main-content { margin-left: 0; }
  .page-body { padding: 0; }
  #invoice { box-shadow: none; border: none; }
}
</style>
