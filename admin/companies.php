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

      <div class="action-bar">
        <div><h2 class="text-xl font-bold text-gray-800">Companies</h2><p class="text-sm text-gray-500 mt-0.5">Manage companies under dealers</p></div>
        <div class="flex items-center gap-2">
          <button class="btn btn-ghost">Export</button>
          <button onclick="openModal('add-modal')" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Company
          </button>
        </div>
      </div>

      <!-- Filters Bar -->
      <div class="bg-white border border-gray-200 border-b-0 rounded-t-md p-2 flex items-center gap-2 shadow-sm text-sm">
        <button class="btn btn-ghost btn-sm flex items-center gap-1 text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg> Filter</button>
        <button class="btn btn-ghost btn-sm flex items-center gap-1 text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Group</button>
        <div class="h-4 w-px bg-gray-300 mx-1"></div>
        <input type="text" placeholder="Search companies..." class="form-input py-1 px-2 text-xs w-64 border-transparent hover:border-gray-200 focus:border-indigo-500 bg-gray-50 focus:bg-white transition-all" />
      </div>

      <div class="spreadsheet-container rounded-t-none border-t-0 shadow-sm">
        <table class="data-table">
          <thead>
            <tr>
              <th class="sticky-col w-10 text-center"><input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" /></th>
              <th class="sticky-col">Company Name</th>
              <th>Dealer</th>
              <th>Contact</th>
              <th>Address</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($companies as $i => $c): ?>
            <tr onclick="this.classList.toggle('selected')">
              <td class="sticky-col w-10 text-center"><input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" onclick="event.stopPropagation()" /></td>
              <td class="sticky-col font-medium text-gray-800"><?= htmlspecialchars($c['name']) ?></td>
              <td><span class="badge badge-info"><?= htmlspecialchars($c['dealer_name']) ?></span></td>
              <td><?= htmlspecialchars($c['contact'] ?? '') ?></td>
              <td class="text-gray-500"><?= htmlspecialchars($c['address'] ?? '') ?></td>
              <td class="flex justify-end gap-1" onclick="event.stopPropagation()">
                <button onclick='editCompany(<?= json_encode($c) ?>)' class="p-1 text-gray-400 hover:text-indigo-600 rounded hover:bg-indigo-50" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                <button onclick="deleteCompany(<?= $c['id'] ?>)" class="p-1 text-gray-400 hover:text-red-600 rounded hover:bg-red-50" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($companies)): ?><tr><td colspan="6" class="text-center py-10 text-gray-400">No companies yet.</td></tr><?php endif; ?>
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
  const data = await api('<?= rootPath() ?>/api/companies.php', 'POST', {
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
  const data = await api('<?= rootPath() ?>/api/companies.php', 'PUT', {
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
  const data = await api('<?= rootPath() ?>/api/companies.php?id=' + id, 'DELETE');
  if (data.success) { showToast('Company deleted!'); location.reload(); }
}
</script>
