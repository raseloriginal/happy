<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle  = 'Categories';
$pdo        = getDB();
$wid        = $_SESSION['warehouse_id'];
$categories = $pdo->query('SELECT c.*, co.name as company_name FROM categories c LEFT JOIN companies co ON co.id=c.company_id WHERE c.status=1 ORDER BY c.id DESC')->fetchAll();
$companies  = $pdo->query('SELECT id, name FROM companies WHERE status=1 ORDER BY name')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">
      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Categories</h2><p class="text-sm text-gray-500">Manage product categories by company</p></div>
        <button onclick="openModal('add-modal')" class="btn btn-primary">+ Add Category</button>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="data-table">
          <thead><tr><th>#</th><th>Category Name</th><th>Company</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($categories as $i => $c): ?>
            <tr>
              <td class="text-gray-400 text-xs"><?= $i+1 ?></td>
              <td class="font-medium"><?= htmlspecialchars($c['name']) ?></td>
              <td><span class="badge badge-info"><?= htmlspecialchars($c['company_name'] ?? 'General') ?></span></td>
              <td class="flex gap-2">
                <button onclick='editCat(<?= json_encode($c) ?>)' class="btn btn-ghost btn-sm">Edit</button>
                <button onclick="deleteCat(<?= $c['id'] ?>)" class="btn btn-danger btn-sm">Delete</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?><tr><td colspan="4" class="text-center py-8 text-gray-400">No categories yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<div id="add-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Bulk Add Categories</h3><button onclick="closeModal('add-modal')" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark"></i></button></div>
    <form id="add-form" class="space-y-3">
      <div><label class="form-label">Company (Optional)</label>
        <select id="add-company" class="form-input">
          <option value="">Select Company (Global)</option>
          <?php foreach ($companies as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label class="form-label">Category Names * (One per line)</label>
        <textarea id="add-names" class="form-input h-32" placeholder="Category 1&#10;Category 2&#10;Category 3" required></textarea>
      </div>
      <div class="flex gap-2 pt-2"><button type="submit" class="btn btn-primary flex-1">Save All</button><button type="button" onclick="closeModal('add-modal')" class="btn btn-ghost flex-1">Cancel</button></div>
    </form>
  </div>
</div>
<div id="edit-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Edit Category</h3><button onclick="closeModal('edit-modal')" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark"></i></button></div>
    <form id="edit-form" class="space-y-3">
      <input type="hidden" id="edit-id" />
      <div><label class="form-label">Company (Optional)</label>
        <select id="edit-company" class="form-input">
          <option value="">Select Company (Global)</option>
          <?php foreach ($companies as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label class="form-label">Category Name *</label><input id="edit-name" class="form-input" required /></div>
      <div class="flex gap-2 pt-2"><button type="submit" class="btn btn-primary flex-1">Update</button><button type="button" onclick="closeModal('edit-modal')" class="btn btn-ghost flex-1">Cancel</button></div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
document.getElementById('add-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const names = document.getElementById('add-names').value.split('\n').map(s => s.trim()).filter(s => s);
  if (names.length === 0) return;
  const data = await api('<?= rootPath() ?>/api/categories.php', 'POST', {
    company_id: document.getElementById('add-company').value,
    names: names
  });
  if (data.success) { showToast('Categories added!'); closeModal('add-modal'); location.reload(); }
  else showToast(data.message, 'error');
});
function editCat(c) {
  document.getElementById('edit-id').value = c.id;
  document.getElementById('edit-company').value = c.company_id || '';
  document.getElementById('edit-name').value = c.name;
  openModal('edit-modal');
}
document.getElementById('edit-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await api('<?= rootPath() ?>/api/categories.php', 'PUT', {
    id: document.getElementById('edit-id').value,
    company_id: document.getElementById('edit-company').value,
    name: document.getElementById('edit-name').value
  });
  if (data.success) { showToast('Category updated!'); closeModal('edit-modal'); location.reload(); }
  else showToast(data.message, 'error');
});
async function deleteCat(id) {
  if (!confirmDelete('Delete this category?')) return;
  const data = await api('<?= rootPath() ?>/api/categories.php?id=' + id, 'DELETE');
  if (data.success) { showToast('Deleted!'); location.reload(); }
}
</script>
