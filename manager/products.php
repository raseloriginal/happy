<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle  = 'Products';
$pdo        = getDB();
$products   = $pdo->query('SELECT p.*, co.name as company_name, cat.name as category_name FROM products p JOIN companies co ON co.id=p.company_id LEFT JOIN categories cat ON cat.id=p.category_id WHERE p.status=1 ORDER BY p.id DESC')->fetchAll();
$companies  = $pdo->query('SELECT id, name FROM companies WHERE status=1 ORDER BY name')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">
      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Products</h2><p class="text-sm text-gray-500">Manage product catalog</p></div>
        <button onclick="openModal('add-modal')" class="btn btn-primary">+ Add Product</button>
      </div>

      <!-- Search bar -->
      <div class="mb-4">
        <input type="text" id="search" placeholder="Search products…" class="form-input max-w-xs" oninput="filterTable()" />
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="data-table" id="products-table">
          <thead><tr><th>Image</th><th>Name</th><th>Company</th><th>Category</th><th>Pcs/Box</th><th>Price</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
              <td>
                <?php if ($p['image']): ?>
                  <img src="<?= rootPath() ?>/<?= htmlspecialchars($p['image']) ?>" class="w-10 h-10 rounded-lg object-cover" />
                <?php else: ?>
                  <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 text-xs">N/A</div>
                <?php endif; ?>
              </td>
              <td class="font-medium"><?= htmlspecialchars($p['name']) ?></td>
              <td><span class="badge badge-info"><?= htmlspecialchars($p['company_name']) ?></span></td>
              <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
              <td><?= $p['pieces_per_box'] ?></td>
              <td>৳<?= number_format($p['selling_price'], 2) ?></td>
              <td class="flex gap-2">
                <button onclick='editProduct(<?= json_encode($p) ?>)' class="btn btn-ghost btn-sm">Edit</button>
                <button onclick="deleteProduct(<?= $p['id'] ?>)" class="btn btn-danger btn-sm">Delete</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?><tr><td colspan="7" class="text-center py-8 text-gray-400">No products yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add Modal -->
<div id="add-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Add Product</h3><button onclick="closeModal('add-modal')">&times;</button></div>
    <form id="add-form" class="space-y-3" enctype="multipart/form-data">
      <div><label class="form-label">Company *</label>
        <select id="add-company" class="form-input" required onchange="loadCategories('add-category', this.value)">
          <option value="">Select Company</option>
          <?php foreach ($companies as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label class="form-label">Category</label>
        <select id="add-category" class="form-input"><option value="">Select Category</option></select>
      </div>
      <div><label class="form-label">Product Name *</label><input id="add-name" class="form-input" required /></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="form-label">Pieces per Box *</label><input id="add-ppb" type="number" class="form-input" min="1" value="1" required /></div>
        <div><label class="form-label">Selling Price (৳)</label><input id="add-price" type="number" step="0.01" class="form-input" value="0" /></div>
      </div>
      <div><label class="form-label">Product Image</label><input id="add-image" type="file" class="form-input" accept="image/*" /></div>
      <div class="flex gap-2 pt-2"><button type="submit" class="btn btn-primary flex-1">Save</button><button type="button" onclick="closeModal('add-modal')" class="btn btn-ghost flex-1">Cancel</button></div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Edit Product</h3><button onclick="closeModal('edit-modal')">&times;</button></div>
    <form id="edit-form" class="space-y-3">
      <input type="hidden" id="edit-id" />
      <div><label class="form-label">Company *</label>
        <select id="edit-company" class="form-input" required onchange="loadCategories('edit-category', this.value)">
          <?php foreach ($companies as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label class="form-label">Category</label>
        <select id="edit-category" class="form-input"><option value="">Select Category</option></select>
      </div>
      <div><label class="form-label">Product Name *</label><input id="edit-name" class="form-input" required /></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="form-label">Pieces per Box</label><input id="edit-ppb" type="number" class="form-input" min="1" /></div>
        <div><label class="form-label">Selling Price (৳)</label><input id="edit-price" type="number" step="0.01" class="form-input" /></div>
      </div>
      <div class="flex gap-2 pt-2"><button type="submit" class="btn btn-primary flex-1">Update</button><button type="button" onclick="closeModal('edit-modal')" class="btn btn-ghost flex-1">Cancel</button></div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
async function loadCategories(selectId, companyId) {
  const sel = document.getElementById(selectId);
  sel.innerHTML = '<option value="">Loading…</option>';
  if (!companyId) { sel.innerHTML = '<option value="">Select Company first</option>'; return; }
  const data = await api('<?= rootPath() ?>/api/categories.php?action=by_company&company_id=' + companyId);
  sel.innerHTML = '<option value="">No Category</option>';
  (data.data || []).forEach(c => { sel.innerHTML += `<option value="${c.id}">${c.name}</option>`; });
}

document.getElementById('add-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const fd = new FormData();
  fd.append('name', document.getElementById('add-name').value);
  fd.append('company_id', document.getElementById('add-company').value);
  fd.append('category_id', document.getElementById('add-category').value);
  fd.append('pieces_per_box', document.getElementById('add-ppb').value);
  fd.append('selling_price', document.getElementById('add-price').value);
  const img = document.getElementById('add-image').files[0];
  if (img) fd.append('image', img);
  const data = await api('<?= rootPath() ?>/api/products.php', 'POST', fd);
  if (data.success) { showToast('Product added!'); closeModal('add-modal'); location.reload(); }
  else showToast(data.message || 'Error', 'error');
});

function editProduct(p) {
  document.getElementById('edit-id').value = p.id;
  document.getElementById('edit-company').value = p.company_id;
  loadCategories('edit-category', p.company_id).then(() => {
    document.getElementById('edit-category').value = p.category_id || '';
  });
  document.getElementById('edit-name').value = p.name;
  document.getElementById('edit-ppb').value = p.pieces_per_box;
  document.getElementById('edit-price').value = p.selling_price;
  openModal('edit-modal');
}

document.getElementById('edit-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await api('<?= rootPath() ?>/api/products.php', 'PUT', {
    id: document.getElementById('edit-id').value,
    company_id: document.getElementById('edit-company').value,
    category_id: document.getElementById('edit-category').value,
    name: document.getElementById('edit-name').value,
    pieces_per_box: document.getElementById('edit-ppb').value,
    selling_price: document.getElementById('edit-price').value
  });
  if (data.success) { showToast('Product updated!'); closeModal('edit-modal'); location.reload(); }
  else showToast(data.message || 'Error', 'error');
});

async function deleteProduct(id) {
  if (!confirmDelete('Delete this product?')) return;
  const data = await api('<?= rootPath() ?>/api/products.php?id=' + id, 'DELETE');
  if (data.success) { showToast('Product deleted!'); location.reload(); }
}

function filterTable() {
  const q = document.getElementById('search').value.toLowerCase();
  document.querySelectorAll('#products-table tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
