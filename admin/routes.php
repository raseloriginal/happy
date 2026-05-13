<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');
$pageTitle  = 'Routes';
$pdo        = getDB();
$routes     = $pdo->query('SELECT r.*, w.name as warehouse_name FROM routes r LEFT JOIN warehouses w ON w.id=r.warehouse_id WHERE r.status=1 ORDER BY r.id DESC')->fetchAll();
$warehouses = $pdo->query('SELECT id, name FROM warehouses WHERE status=1 ORDER BY name')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">
      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Routes</h2><p class="text-sm text-gray-500">Manage delivery routes</p></div>
        <button onclick="openModal('add-modal')" class="btn btn-primary">+ Add Route</button>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="data-table">
          <thead><tr><th>#</th><th>Route Name</th><th>Area</th><th>Warehouse</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($routes as $i => $r): ?>
            <tr>
              <td class="text-gray-400 text-xs"><?= $i+1 ?></td>
              <td class="font-medium"><?= htmlspecialchars($r['name']) ?></td>
              <td><?= htmlspecialchars($r['area'] ?? '') ?></td>
              <td><span class="badge badge-info"><?= htmlspecialchars($r['warehouse_name'] ?? '—') ?></span></td>
              <td class="flex gap-2">
                <button onclick='editRoute(<?= json_encode($r) ?>)' class="btn btn-ghost btn-sm">Edit</button>
                <button onclick="deleteRoute(<?= $r['id'] ?>)" class="btn btn-danger btn-sm">Delete</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($routes)): ?><tr><td colspan="5" class="text-center py-8 text-gray-400">No routes yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<div id="add-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Add Route</h3><button onclick="closeModal('add-modal')">&times;</button></div>
    <form id="add-form" class="space-y-3">
      <div><label class="form-label">Route Name *</label><input id="add-name" class="form-input" required /></div>
      <div><label class="form-label">Area</label><input id="add-area" class="form-input" /></div>
      <div><label class="form-label">Warehouse</label>
        <select id="add-warehouse" class="form-input">
          <option value="">Select Warehouse</option>
          <?php foreach ($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="flex gap-2 pt-2"><button type="submit" class="btn btn-primary flex-1">Save</button><button type="button" onclick="closeModal('add-modal')" class="btn btn-ghost flex-1">Cancel</button></div>
    </form>
  </div>
</div>
<div id="edit-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Edit Route</h3><button onclick="closeModal('edit-modal')">&times;</button></div>
    <form id="edit-form" class="space-y-3">
      <input type="hidden" id="edit-id" />
      <div><label class="form-label">Route Name *</label><input id="edit-name" class="form-input" required /></div>
      <div><label class="form-label">Area</label><input id="edit-area" class="form-input" /></div>
      <div><label class="form-label">Warehouse</label>
        <select id="edit-warehouse" class="form-input">
          <option value="">Select Warehouse</option>
          <?php foreach ($warehouses as $w): ?><option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="flex gap-2 pt-2"><button type="submit" class="btn btn-primary flex-1">Update</button><button type="button" onclick="closeModal('edit-modal')" class="btn btn-ghost flex-1">Cancel</button></div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
document.getElementById('add-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await api('/happycrm2/api/routes.php', 'POST', {
    name: document.getElementById('add-name').value,
    area: document.getElementById('add-area').value,
    warehouse_id: document.getElementById('add-warehouse').value || null
  });
  if (data.success) { showToast('Route added!'); closeModal('add-modal'); location.reload(); }
  else showToast(data.message, 'error');
});
function editRoute(r) {
  document.getElementById('edit-id').value = r.id;
  document.getElementById('edit-name').value = r.name;
  document.getElementById('edit-area').value = r.area || '';
  document.getElementById('edit-warehouse').value = r.warehouse_id || '';
  openModal('edit-modal');
}
document.getElementById('edit-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await api('/happycrm2/api/routes.php', 'PUT', {
    id: document.getElementById('edit-id').value,
    name: document.getElementById('edit-name').value,
    area: document.getElementById('edit-area').value,
    warehouse_id: document.getElementById('edit-warehouse').value || null
  });
  if (data.success) { showToast('Route updated!'); closeModal('edit-modal'); location.reload(); }
  else showToast(data.message, 'error');
});
async function deleteRoute(id) {
  if (!confirmDelete('Delete this route?')) return;
  const data = await api('/happycrm2/api/routes.php?id=' + id, 'DELETE');
  if (data.success) { showToast('Route deleted!'); location.reload(); }
}
</script>
