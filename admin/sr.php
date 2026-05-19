<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');
$pageTitle = 'Sales Representatives';
$pdo       = getDB();
$srs       = $pdo->query('SELECT s.*, u.name, u.email, u.phone, c.name as company_name, r.name as route_name FROM sr s JOIN users u ON u.id=s.user_id JOIN companies c ON c.id=s.company_id LEFT JOIN routes r ON r.id=s.route_id WHERE s.status=1 ORDER BY s.id DESC')->fetchAll();
$companies = $pdo->query('SELECT id, name FROM companies WHERE status=1 ORDER BY name')->fetchAll();
$routes    = $pdo->query('SELECT id, name FROM routes WHERE status=1 ORDER BY name')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">
      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Sales Representatives</h2><p class="text-sm text-gray-500">Manage SR accounts and assignments</p></div>
        <button onclick="openModal('add-modal')" class="btn btn-primary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add SR
        </button>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="data-table">
          <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Company</th><th>Route</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($srs as $i => $s): ?>
            <tr>
              <td class="text-gray-400 text-xs"><?= $i+1 ?></td>
              <td class="font-medium"><?= htmlspecialchars($s['name']) ?></td>
              <td class="text-gray-500"><?= htmlspecialchars($s['email']) ?></td>
              <td><?= htmlspecialchars($s['phone']) ?></td>
              <td><span class="badge badge-info"><?= htmlspecialchars($s['company_name']) ?></span></td>
              <td><?= htmlspecialchars($s['route_name'] ?? '—') ?></td>
              <td class="flex gap-2">
                <button onclick='editSR(<?= json_encode($s) ?>)' class="btn btn-ghost btn-sm">Edit</button>
                <button onclick="deleteSR(<?= $s['id'] ?>)" class="btn btn-danger btn-sm">Delete</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($srs)): ?><tr><td colspan="7" class="text-center py-8 text-gray-400">No SRs yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add Modal -->
<div id="add-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold text-gray-800">Add SR</h3><button onclick="closeModal('add-modal')" class="text-gray-400">&times;</button></div>
    <form id="add-form" class="space-y-3">
      <div><label class="form-label">Full Name *</label><input id="add-name" class="form-input" required /></div>
      <div><label class="form-label">Email *</label><input id="add-email" type="email" class="form-input" required /></div>
      <div><label class="form-label">Password *</label><input id="add-password" type="password" class="form-input" required /></div>
      <div><label class="form-label">Phone</label><input id="add-phone" class="form-input" /></div>
      <div><label class="form-label">Company *</label>
        <select id="add-company" class="form-input" required>
          <option value="">Select Company</option>
          <?php foreach ($companies as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label class="form-label">Route</label>
        <select id="add-route" class="form-input">
          <option value="">Select Route (optional)</option>
          <?php foreach ($routes as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="flex gap-2 pt-2"><button type="submit" class="btn btn-primary flex-1">Save</button><button type="button" onclick="closeModal('add-modal')" class="btn btn-ghost flex-1">Cancel</button></div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold text-gray-800">Edit SR</h3><button onclick="closeModal('edit-modal')" class="text-gray-400">&times;</button></div>
    <form id="edit-form" class="space-y-3">
      <input type="hidden" id="edit-id" /><input type="hidden" id="edit-uid" />
      <div><label class="form-label">Full Name *</label><input id="edit-name" class="form-input" required /></div>
      <div><label class="form-label">Email *</label><input id="edit-email" type="email" class="form-input" required /></div>
      <div><label class="form-label">Password (leave blank to keep unchanged)</label><input id="edit-password" type="password" class="form-input" /></div>
      <div><label class="form-label">Phone</label><input id="edit-phone" class="form-input" /></div>
      <div><label class="form-label">Company *</label>
        <select id="edit-company" class="form-input" required>
          <?php foreach ($companies as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label class="form-label">Route</label>
        <select id="edit-route" class="form-input">
          <option value="">No Route</option>
          <?php foreach ($routes as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?>
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
  const data = await api('<?= rootPath() ?>/api/sr.php', 'POST', {
    name: document.getElementById('add-name').value,
    email: document.getElementById('add-email').value,
    password: document.getElementById('add-password').value,
    phone: document.getElementById('add-phone').value,
    company_id: document.getElementById('add-company').value,
    route_id: document.getElementById('add-route').value || null
  });
  if (data.success) { showToast('SR added!'); closeModal('add-modal'); location.reload(); }
  else showToast(data.message, 'error');
});

function editSR(s) {
  document.getElementById('edit-id').value = s.id;
  document.getElementById('edit-uid').value = s.user_id;
  document.getElementById('edit-name').value = s.name;
  document.getElementById('edit-email').value = s.email;
  document.getElementById('edit-password').value = '';
  document.getElementById('edit-phone').value = s.phone;
  document.getElementById('edit-company').value = s.company_id;
  document.getElementById('edit-route').value = s.route_id || '';
  openModal('edit-modal');
}

document.getElementById('edit-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await api('<?= rootPath() ?>/api/sr.php', 'PUT', {
    id: document.getElementById('edit-id').value,
    user_id: document.getElementById('edit-uid').value,
    name: document.getElementById('edit-name').value,
    email: document.getElementById('edit-email').value,
    password: document.getElementById('edit-password').value,
    phone: document.getElementById('edit-phone').value,
    company_id: document.getElementById('edit-company').value,
    route_id: document.getElementById('edit-route').value || null
  });
  if (data.success) { showToast('SR updated!'); closeModal('edit-modal'); location.reload(); }
  else showToast(data.message, 'error');
});

async function deleteSR(id) {
  if (!confirmDelete('Delete this SR?')) return;
  const data = await api('<?= rootPath() ?>/api/sr.php?id=' + id, 'DELETE');
  if (data.success) { showToast('SR deleted!'); location.reload(); }
}
</script>
