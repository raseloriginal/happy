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
          <thead><tr><th>Image</th><th>Name</th><th>Company</th><th>Category</th><th>Box Type</th><th>Pcs/Box</th><th>Dealer %</th><th>Actions</th></tr></thead>
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
              <td><?= htmlspecialchars($p['box_type'] ?? '—') ?></td>
              <td><?= $p['pieces_per_box'] ?></td>
              <td><?= number_format($p['dealer_percentage'], 2) ?>%</td>
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

<div id="add-modal" class="modal-overlay" style="display:none">
  <div class="modal-box max-w-5xl">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Bulk Add Products</h3><button onclick="closeModal('add-modal')" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark"></i></button></div>
    <form id="add-form" class="space-y-4">
      <div class="flex gap-4 items-end">
        <div class="flex-1"><label class="form-label">Company *</label>
          <select id="bulk-company" class="form-input" required onchange="onCompanyChange()">
            <option value="">Select Company</option>
            <?php foreach ($companies as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <button type="button" onclick="addBulkRow()" class="btn btn-ghost border border-dashed border-gray-300 text-indigo-600 hover:bg-indigo-50">+ Add Row</button>
      </div>
      
      <div class="overflow-x-auto border rounded-lg max-h-[400px] overflow-y-auto">
        <table class="data-table mb-0">
          <thead class="sticky top-0 bg-white shadow-sm z-10"><tr><th class="w-40">Category</th><th class="w-32">Image</th><th>Product Name *</th><th>Box Type</th><th class="w-24">Pcs/Box</th><th class="w-24">Dealer % *</th><th></th></tr></thead>
          <tbody id="bulk-items-body"></tbody>
        </table>
      </div>

      <div class="flex gap-2 pt-2"><button type="submit" id="save-btn" class="btn btn-primary flex-1">Save All Products</button><button type="button" onclick="closeModal('add-modal')" class="btn btn-ghost flex-1">Cancel</button></div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4"><h3 class="font-bold">Edit Product</h3><button onclick="closeModal('edit-modal')" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark"></i></button></div>
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
      <div class="grid grid-cols-3 gap-3">
        <div><label class="form-label">Box Type</label>
          <select id="edit-bt" class="form-input">
            <option value="বক্স">বক্স</option>
            <option value="পলি">পলি</option>
            <option value="কার্টুন">কার্টুন</option>
            <option value="পিস">পিস</option>
            <option value="কেস">কেস</option>
            <option value="বস্তা">বস্তা</option>
            <option value="জার">জার</option>
            <option value="কেজি">কেজি</option>
            <option value="ডজন">ডজন</option>
            <option value="কম্বো">কম্বো</option>
          </select>
        </div>
        <div><label class="form-label">Pieces per Box</label><input id="edit-ppb" type="number" class="form-input" min="1" /></div>
        <div><label class="form-label">Dealer %</label><input id="edit-dp" type="number" step="0.01" class="form-input" /></div>
      </div>
      <div class="flex gap-2 pt-2"><button type="submit" class="btn btn-primary flex-1">Update</button><button type="button" onclick="closeModal('edit-modal')" class="btn btn-ghost flex-1">Cancel</button></div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
let bulkCategories = [];

async function onCompanyChange() {
  const cid = document.getElementById('bulk-company').value;
  if (!cid) { bulkCategories = []; return; }
  const data = await api('<?= rootPath() ?>/api/categories.php?action=by_company&company_id=' + cid);
  bulkCategories = data.data || [];
  // Update all current category selects in the table
  document.querySelectorAll('.row-cat').forEach(sel => {
    const val = sel.value;
    sel.innerHTML = '<option value="">No Category</option>' + bulkCategories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    sel.value = val;
  });
}

function addBulkRow() {
  const tbody = document.getElementById('bulk-items-body');
  const idx = Date.now(); // Unique index for this session
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <select class="form-input row-cat text-xs">
        <option value="">No Category</option>
        ${bulkCategories.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}
      </select>
    </td>
    <td>
      <div class="flex items-center gap-2">
        <div class="w-10 h-10 bg-gray-50 rounded border border-gray-200 flex items-center justify-center overflow-hidden shrink-0">
          <img id="prev-${idx}" class="hidden w-full h-full object-cover" />
          <span id="label-${idx}" class="text-[10px] text-gray-400">Preview</span>
        </div>
        <input type="file" class="hidden row-img" id="img-${idx}" accept="image/*" onchange="previewImg('${idx}', this)" />
        <button type="button" onclick="document.getElementById('img-${idx}').click()" class="btn btn-ghost btn-xs text-indigo-600">Upload</button>
      </div>
    </td>
    <td><input class="form-input row-name" placeholder="Enter name..." required /></td>
    <td>
      <select class="form-input row-bt">
        <option value="বক্স">বক্স</option>
        <option value="পলি">পলি</option>
        <option value="কার্টুন">কার্টুন</option>
        <option value="পিস">পিস</option>
        <option value="বস্তা">বস্তা</option>
        <option value="জার">জার</option>
        <option value="কেজি">কেজি</option>
        <option value="ডজন">ডজন</option>
        <option value="কম্বো">কম্বো</option>
      </select>
    </td>
    <td><input type="number" class="form-input row-ppb" value="1" min="1" /></td>
    <td><input type="number" class="form-input row-dp" value="0.00" step="0.01" required /></td>
    <td><button type="button" onclick="this.closest('tr').remove()" class="btn btn-ghost btn-sm text-red-500 hover:bg-red-50"><i class="fa-solid fa-xmark"></i></button></td>
  `;
  tbody.appendChild(tr);
}

function previewImg(idx, input) {
  const file = input.files[0];
  const prev = document.getElementById('prev-' + idx);
  const label = document.getElementById('label-' + idx);
  if (file) {
    prev.src = URL.createObjectURL(file);
    prev.classList.remove('hidden');
    label.classList.add('hidden');
  }
}

document.getElementById('add-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const cid = document.getElementById('bulk-company').value;
  if (!cid) return showToast('Select a company', 'error');
  
  const rows = document.querySelectorAll('#bulk-items-body tr');
  if (rows.length === 0) return showToast('Add at least one row', 'error');

  const btn = document.getElementById('save-btn');
  btn.disabled = true; btn.textContent = 'Saving…';

  const fd = new FormData();
  fd.append('company_id', cid);
  
  const bulkData = [];
  rows.forEach((tr, i) => {
    const name = tr.querySelector('.row-name').value;
    const cat = tr.querySelector('.row-cat').value;
    const bt = tr.querySelector('.row-bt').value;
    const ppb = tr.querySelector('.row-ppb').value;
    const dp = tr.querySelector('.row-dp').value;
    const file = tr.querySelector('.row-img').files[0];
    
    const item = { name, category_id: cat, box_type: bt, pieces_per_box: ppb, dealer_percentage: dp, image_idx: null };
    if (file) {
      item.image_idx = i;
      fd.append('image_' + i, file);
    }
    bulkData.push(item);
  });
  
  fd.append('bulk', JSON.stringify(bulkData));
  
  const data = await api('<?= rootPath() ?>/api/products.php', 'POST', fd);
  if (data.success) { showToast('Products added!'); location.reload(); }
  else { 
    showToast(data.message || 'Error saving products', 'error'); 
    btn.disabled = false; btn.textContent = 'Save All Products'; 
  }
});

function editProduct(p) {
  document.getElementById('edit-id').value = p.id;
  document.getElementById('edit-company').value = p.company_id;
  loadCategories('edit-category', p.company_id).then(() => {
    document.getElementById('edit-category').value = p.category_id || '';
  });
  document.getElementById('edit-name').value = p.name;
  document.getElementById('edit-bt').value = p.box_type || '';
  document.getElementById('edit-ppb').value = p.pieces_per_box;
  document.getElementById('edit-dp').value = p.dealer_percentage;
  openModal('edit-modal');
}

document.getElementById('edit-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = await api('<?= rootPath() ?>/api/products.php', 'PUT', {
    id: document.getElementById('edit-id').value,
    company_id: document.getElementById('edit-company').value,
    category_id: document.getElementById('edit-category').value,
    name: document.getElementById('edit-name').value,
    box_type: document.getElementById('edit-bt').value,
    pieces_per_box: document.getElementById('edit-ppb').value,
    dealer_percentage: document.getElementById('edit-dp').value
  });
  if (data.success) { showToast('Product updated!'); closeModal('edit-modal'); location.reload(); }
  else showToast(data.message || 'Error', 'error');
});

async function deleteProduct(id) {
  if (!confirmDelete('Delete this product?')) return;
  const data = await api('<?= rootPath() ?>/api/products.php?id=' + id, 'DELETE');
  if (data.success) { showToast('Product deleted!'); location.reload(); }
}

// Helper for Edit modal categories
async function loadCategories(selectId, companyId) {
  const sel = document.getElementById(selectId);
  sel.innerHTML = '<option value="">Loading…</option>';
  if (!companyId) { sel.innerHTML = '<option value="">Select Company first</option>'; return; }
  const data = await api('<?= rootPath() ?>/api/categories.php?action=by_company&company_id=' + companyId);
  sel.innerHTML = '<option value="">No Category</option>';
  (data.data || []).forEach(c => { sel.innerHTML += `<option value="${c.id}">${c.name}</option>`; });
}

// Add first row on open
document.querySelector('[onclick="openModal(\'add-modal\')"]').addEventListener('click', () => {
  if (document.getElementById('bulk-items-body').children.length === 0) addBulkRow();
});

function filterTable() {
  const q = document.getElementById('search').value.toLowerCase();
  document.querySelectorAll('#products-table tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
