<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');
$pageTitle = 'Warehouses';
$pdo = getDB();
$warehouses = $pdo->query('SELECT * FROM warehouses WHERE status=1 ORDER BY id DESC')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">

      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-xl font-bold text-gray-800">Warehouses</h2>
          <p class="text-sm text-gray-500 mt-0.5">Manage all warehouse locations</p>
        </div>
        <button onclick="openModal('add-modal')" class="btn btn-primary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Warehouse
        </button>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th><th>Name</th><th>Area</th><th>Address</th><th>Status</th><th>Actions</th>
            </tr>
          </thead>
          <tbody id="table-body">
            <?php foreach ($warehouses as $i => $w): ?>
            <tr>
              <td class="text-gray-400 text-xs"><?= $i + 1 ?></td>
              <td class="font-medium text-gray-800"><?= htmlspecialchars($w['name']) ?></td>
              <td><?= htmlspecialchars($w['area']) ?></td>
              <td class="text-gray-500"><?= htmlspecialchars($w['address']) ?></td>
              <td><span class="badge badge-success">Active</span></td>
              <td class="flex gap-2">
                <button onclick='editWarehouse(<?= json_encode($w) ?>)' class="btn btn-ghost btn-sm">Edit</button>
                <button onclick="deleteWarehouse(<?= $w['id'] ?>)" class="btn btn-danger btn-sm">Delete</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($warehouses)): ?>
            <tr><td colspan="6" class="text-center py-8 text-gray-400">No warehouses yet. Add one!</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add Modal -->
<div id="add-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-gray-800">Add Warehouse</h3>
      <button onclick="closeModal('add-modal')" class="text-gray-400 hover:text-gray-600">&times;</button>
    </div>
    <form id="add-form" class="space-y-3">
      <div><label class="form-label">Name *</label><input id="add-name" class="form-input" required /></div>
      <div><label class="form-label">Area</label><input id="add-area" class="form-input" /></div>
      <div><label class="form-label">Address</label><textarea id="add-address" class="form-input" rows="2"></textarea></div>
      <div class="flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary flex-1">Save</button>
        <button type="button" onclick="closeModal('add-modal')" class="btn btn-ghost flex-1">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-gray-800">Edit Warehouse</h3>
      <button onclick="closeModal('edit-modal')" class="text-gray-400 hover:text-gray-600">&times;</button>
    </div>
    <form id="edit-form" class="space-y-3">
      <input type="hidden" id="edit-id" />
      <div><label class="form-label">Name *</label><input id="edit-name" class="form-input" required /></div>
      <div><label class="form-label">Area</label><input id="edit-area" class="form-input" /></div>
      <div><label class="form-label">Address</label><textarea id="edit-address" class="form-input" rows="2"></textarea></div>
      <div class="flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary flex-1">Update</button>
        <button type="button" onclick="closeModal('edit-modal')" class="btn btn-ghost flex-1">Cancel</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
document.getElementById('add-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await api('<?= rootPath() ?>/api/warehouses.php', 'POST', {
    name: document.getElementById('add-name').value,
    area: document.getElementById('add-area').value,
    address: document.getElementById('add-address').value
  });
  if (data.success) { showToast('Warehouse added!'); closeModal('add-modal'); location.reload(); }
  else showToast(data.message, 'error');
});

function editWarehouse(w) {
  document.getElementById('edit-id').value    = w.id;
  document.getElementById('edit-name').value  = w.name;
  document.getElementById('edit-area').value  = w.area;
  document.getElementById('edit-address').value = w.address;
  openModal('edit-modal');
}

document.getElementById('edit-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await api('<?= rootPath() ?>/api/warehouses.php', 'PUT', {
    id: document.getElementById('edit-id').value,
    name: document.getElementById('edit-name').value,
    area: document.getElementById('edit-area').value,
    address: document.getElementById('edit-address').value
  });
  if (data.success) { showToast('Warehouse updated!'); closeModal('edit-modal'); location.reload(); }
  else showToast(data.message, 'error');
});

async function deleteWarehouse(id) {
  if (!confirmDelete('Delete this warehouse?')) return;
  const data = await api('<?= rootPath() ?>/api/warehouses.php?id=' + id, 'DELETE');
  if (data.success) { showToast('Warehouse deleted!'); location.reload(); }
  else showToast(data.message, 'error');
}
</script>
