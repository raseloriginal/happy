<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');
$pageTitle = 'Dealers';
$pdo = getDB();
$dealers = $pdo->query('SELECT d.*, u.name, u.email, u.phone FROM dealers d JOIN users u ON u.id=d.user_id WHERE d.status=1 ORDER BY d.id DESC')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">

      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Dealers</h2><p class="text-sm text-gray-500 mt-0.5">Manage dealer accounts</p></div>
        <button onclick="openModal('add-modal')" class="btn btn-primary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Dealer
        </button>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="data-table">
          <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($dealers as $i => $d): ?>
            <tr>
              <td class="text-gray-400 text-xs"><?= $i+1 ?></td>
              <td class="font-medium text-gray-800"><?= htmlspecialchars($d['name']) ?></td>
              <td class="text-gray-500"><?= htmlspecialchars($d['email']) ?></td>
              <td><?= htmlspecialchars($d['phone']) ?></td>
              <td class="text-gray-500"><?= htmlspecialchars($d['address'] ?? '') ?></td>
              <td class="flex gap-2">
                <button onclick='editDealer(<?= json_encode($d) ?>)' class="btn btn-ghost btn-sm">Edit</button>
                <button onclick="deleteDealer(<?= $d['id'] ?>)" class="btn btn-danger btn-sm">Delete</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($dealers)): ?>
            <tr><td colspan="6" class="text-center py-8 text-gray-400">No dealers yet.</td></tr>
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
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold text-gray-800">Add Dealer</h3><button onclick="closeModal('add-modal')" class="text-gray-400">&times;</button></div>
    <form id="add-form" class="space-y-3">
      <div><label class="form-label">Full Name *</label><input id="add-name" class="form-input" required /></div>
      <div><label class="form-label">Email *</label><input id="add-email" type="email" class="form-input" required /></div>
      <div><label class="form-label">Password *</label><input id="add-password" type="password" class="form-input" required /></div>
      <div><label class="form-label">Phone</label><input id="add-phone" class="form-input" /></div>
      <div><label class="form-label">Address</label><textarea id="add-address" class="form-input" rows="2"></textarea></div>
      <div class="flex gap-2 pt-2"><button type="submit" class="btn btn-primary flex-1">Save</button><button type="button" onclick="closeModal('add-modal')" class="btn btn-ghost flex-1">Cancel</button></div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold text-gray-800">Edit Dealer</h3><button onclick="closeModal('edit-modal')" class="text-gray-400">&times;</button></div>
    <form id="edit-form" class="space-y-3">
      <input type="hidden" id="edit-id" /><input type="hidden" id="edit-uid" />
      <div><label class="form-label">Full Name *</label><input id="edit-name" class="form-input" required /></div>
      <div><label class="form-label">Phone</label><input id="edit-phone" class="form-input" /></div>
      <div><label class="form-label">Address</label><textarea id="edit-address" class="form-input" rows="2"></textarea></div>
      <div class="flex gap-2 pt-2"><button type="submit" class="btn btn-primary flex-1">Update</button><button type="button" onclick="closeModal('edit-modal')" class="btn btn-ghost flex-1">Cancel</button></div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
document.getElementById('add-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await api('/happycrm2/api/dealers.php', 'POST', {
    name: document.getElementById('add-name').value,
    email: document.getElementById('add-email').value,
    password: document.getElementById('add-password').value,
    phone: document.getElementById('add-phone').value,
    address: document.getElementById('add-address').value
  });
  if (data.success) { showToast('Dealer added!'); closeModal('add-modal'); location.reload(); }
  else showToast(data.message, 'error');
});

function editDealer(d) {
  document.getElementById('edit-id').value = d.id;
  document.getElementById('edit-uid').value = d.user_id;
  document.getElementById('edit-name').value = d.name;
  document.getElementById('edit-phone').value = d.phone;
  document.getElementById('edit-address').value = d.address || '';
  openModal('edit-modal');
}

document.getElementById('edit-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await api('/happycrm2/api/dealers.php', 'PUT', {
    id: document.getElementById('edit-id').value,
    user_id: document.getElementById('edit-uid').value,
    name: document.getElementById('edit-name').value,
    phone: document.getElementById('edit-phone').value,
    address: document.getElementById('edit-address').value
  });
  if (data.success) { showToast('Dealer updated!'); closeModal('edit-modal'); location.reload(); }
  else showToast(data.message, 'error');
});

async function deleteDealer(id) {
  if (!confirmDelete('Delete this dealer?')) return;
  const data = await api('/happycrm2/api/dealers.php?id=' + id, 'DELETE');
  if (data.success) { showToast('Dealer deleted!'); location.reload(); }
  else showToast(data.message, 'error');
}
</script>
