<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');
$pageTitle  = 'Delivery Reps (DSR)';
$pdo        = getDB();
$dsrs       = $pdo->query('SELECT d.*, u.name, u.email, u.phone, w.name as warehouse_name FROM dsr d JOIN users u ON u.id=d.user_id JOIN warehouses w ON w.id=d.warehouse_id WHERE d.status=1 ORDER BY d.id DESC')->fetchAll();
$warehouses = $pdo->query('SELECT id, name FROM warehouses WHERE status=1 ORDER BY name')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">
      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Delivery Reps (DSR)</h2></div>
        <button onclick="openModal('add-modal')" class="btn btn-primary">+ Add DSR</button>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="data-table">
          <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Warehouse</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($dsrs as $i => $d): ?>
            <tr>
              <td class="text-gray-400 text-xs"><?= $i+1 ?></td>
              <td class="font-medium"><?= htmlspecialchars($d['name']) ?></td>
              <td class="text-gray-500"><?= htmlspecialchars($d['email']) ?></td>
              <td><?= htmlspecialchars($d['phone']) ?></td>
              <td><span class="badge badge-warning"><?= htmlspecialchars($d['warehouse_name']) ?></span></td>
              <td class="flex gap-2">
                <button onclick='editDSR(<?= json_encode($d) ?>)' class="btn btn-ghost btn-sm">Edit</button>
                <button onclick="deleteDSR(<?= $d['id'] ?>)" class="btn btn-danger btn-sm">Delete</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($dsrs)): ?><tr><td colspan="6" class="text-center py-8 text-gray-400">No DSRs yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div id="add-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Add DSR</h3><button onclick="closeModal('add-modal')">&times;</button></div>
    <form id="add-form" class="space-y-3">
      <div><label class="form-label">Full Name *</label><input id="add-name" class="form-input" required /></div>
      <div><label class="form-label">Email *</label><input id="add-email" type="email" class="form-input" required /></div>
      <div><label class="form-label">Password *</label><input id="add-password" type="password" class="form-input" required /></div>
      <div><label class="form-label">Phone</label><input id="add-phone" class="form-input" /></div>
      <div><label class="form-label">Warehouse *</label>
        <select id="add-warehouse" class="form-input" required>
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
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Edit DSR</h3><button onclick="closeModal('edit-modal')">&times;</button></div>
    <form id="edit-form" class="space-y-3">
      <input type="hidden" id="edit-id" /><input type="hidden" id="edit-uid" />
      <div><label class="form-label">Full Name *</label><input id="edit-name" class="form-input" required /></div>
      <div><label class="form-label">Email *</label><input id="edit-email" type="email" class="form-input" required /></div>
      <div><label class="form-label">Password (leave blank to keep unchanged)</label><input id="edit-password" type="password" class="form-input" /></div>
      <div><label class="form-label">Phone</label><input id="edit-phone" class="form-input" /></div>
      <div><label class="form-label">Warehouse *</label>
        <select id="edit-warehouse" class="form-input" required>
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
  const data = await api('<?= rootPath() ?>/api/dsr.php', 'POST', {
    name: document.getElementById('add-name').value, email: document.getElementById('add-email').value,
    password: document.getElementById('add-password').value, phone: document.getElementById('add-phone').value,
    warehouse_id: document.getElementById('add-warehouse').value
  });
  if (data.success) { showToast('DSR added!'); closeModal('add-modal'); location.reload(); }
  else showToast(data.message, 'error');
});
function editDSR(d) {
  document.getElementById('edit-id').value = d.id; document.getElementById('edit-uid').value = d.user_id;
  document.getElementById('edit-name').value = d.name;
  document.getElementById('edit-email').value = d.email;
  document.getElementById('edit-password').value = '';
  document.getElementById('edit-phone').value = d.phone;
  document.getElementById('edit-warehouse').value = d.warehouse_id; openModal('edit-modal');
}
document.getElementById('edit-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await api('<?= rootPath() ?>/api/dsr.php', 'PUT', {
    id: document.getElementById('edit-id').value, user_id: document.getElementById('edit-uid').value,
    name: document.getElementById('edit-name').value, email: document.getElementById('edit-email').value,
    password: document.getElementById('edit-password').value, phone: document.getElementById('edit-phone').value,
    warehouse_id: document.getElementById('edit-warehouse').value
  });
  if (data.success) { showToast('DSR updated!'); closeModal('edit-modal'); location.reload(); }
  else showToast(data.message, 'error');
});
async function deleteDSR(id) {
  if (!confirmDelete('Delete this DSR?')) return;
  const data = await api('<?= rootPath() ?>/api/dsr.php?id=' + id, 'DELETE');
  if (data.success) { showToast('DSR deleted!'); location.reload(); }
}
</script>
