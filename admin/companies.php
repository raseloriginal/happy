<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');
$pageTitle = 'Companies';
$pdo = getDB();
$companies = $pdo->query('SELECT c.*, d.name as dealer_name FROM companies c JOIN dealers d ON d.id=c.dealer_id WHERE c.status=1 ORDER BY c.id DESC')->fetchAll();
$dealers   = $pdo->query('SELECT id, name FROM dealers WHERE status=1 ORDER BY name')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">

      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Companies</h2><p class="text-sm text-gray-500 mt-0.5">Manage companies under dealers</p></div>
        <button onclick="openModal('add-modal')" class="btn btn-primary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Company
        </button>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="data-table">
          <thead><tr><th>#</th><th>Company Name</th><th>Dealer</th><th>Contact</th><th>Address</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($companies as $i => $c): ?>
            <tr>
              <td class="text-gray-400 text-xs"><?= $i+1 ?></td>
              <td class="font-medium text-gray-800"><?= htmlspecialchars($c['name']) ?></td>
              <td><span class="badge badge-info"><?= htmlspecialchars($c['dealer_name']) ?></span></td>
              <td><?= htmlspecialchars($c['contact'] ?? '') ?></td>
              <td class="text-gray-500"><?= htmlspecialchars($c['address'] ?? '') ?></td>
              <td class="flex gap-2">
                <button onclick='editCompany(<?= json_encode($c) ?>)' class="btn btn-ghost btn-sm">Edit</button>
                <button onclick="deleteCompany(<?= $c['id'] ?>)" class="btn btn-danger btn-sm">Delete</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($companies)): ?><tr><td colspan="6" class="text-center py-8 text-gray-400">No companies yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add Modal -->
<div id="add-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold text-gray-800">Add Company</h3><button onclick="closeModal('add-modal')" class="text-gray-400">&times;</button></div>
    <form id="add-form" class="space-y-3">
      <div><label class="form-label">Dealer *</label>
        <select id="add-dealer" class="form-input" required>
          <option value="">Select Dealer</option>
          <?php foreach ($dealers as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label class="form-label">Company Name *</label><input id="add-name" class="form-input" required /></div>
      <div><label class="form-label">Contact</label><input id="add-contact" class="form-input" /></div>
      <div><label class="form-label">Address</label><textarea id="add-address" class="form-input" rows="2"></textarea></div>
      <div class="flex gap-2 pt-2"><button type="submit" class="btn btn-primary flex-1">Save</button><button type="button" onclick="closeModal('add-modal')" class="btn btn-ghost flex-1">Cancel</button></div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold text-gray-800">Edit Company</h3><button onclick="closeModal('edit-modal')" class="text-gray-400">&times;</button></div>
    <form id="edit-form" class="space-y-3">
      <input type="hidden" id="edit-id" />
      <div><label class="form-label">Dealer *</label>
        <select id="edit-dealer" class="form-input" required>
          <?php foreach ($dealers as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label class="form-label">Company Name *</label><input id="edit-name" class="form-input" required /></div>
      <div><label class="form-label">Contact</label><input id="edit-contact" class="form-input" /></div>
      <div><label class="form-label">Address</label><textarea id="edit-address" class="form-input" rows="2"></textarea></div>
      <div class="flex gap-2 pt-2"><button type="submit" class="btn btn-primary flex-1">Update</button><button type="button" onclick="closeModal('edit-modal')" class="btn btn-ghost flex-1">Cancel</button></div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
document.getElementById('add-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await api('/happycrm2/api/companies.php', 'POST', {
    dealer_id: document.getElementById('add-dealer').value,
    name: document.getElementById('add-name').value,
    contact: document.getElementById('add-contact').value,
    address: document.getElementById('add-address').value
  });
  if (data.success) { showToast('Company added!'); closeModal('add-modal'); location.reload(); }
  else showToast(data.message, 'error');
});

function editCompany(c) {
  document.getElementById('edit-id').value = c.id;
  document.getElementById('edit-dealer').value = c.dealer_id;
  document.getElementById('edit-name').value = c.name;
  document.getElementById('edit-contact').value = c.contact || '';
  document.getElementById('edit-address').value = c.address || '';
  openModal('edit-modal');
}

document.getElementById('edit-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await api('/happycrm2/api/companies.php', 'PUT', {
    id: document.getElementById('edit-id').value,
    dealer_id: document.getElementById('edit-dealer').value,
    name: document.getElementById('edit-name').value,
    contact: document.getElementById('edit-contact').value,
    address: document.getElementById('edit-address').value
  });
  if (data.success) { showToast('Company updated!'); closeModal('edit-modal'); location.reload(); }
  else showToast(data.message, 'error');
});

async function deleteCompany(id) {
  if (!confirmDelete('Delete this company?')) return;
  const data = await api('/happycrm2/api/companies.php?id=' + id, 'DELETE');
  if (data.success) { showToast('Company deleted!'); location.reload(); }
}
</script>
