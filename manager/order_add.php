<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Add New Order';
$pdo       = getDB();
$srs       = $pdo->query('SELECT s.id, u.name, c.name as company_name FROM sr s JOIN users u ON u.id=s.user_id JOIN companies c ON c.id=s.company_id WHERE s.status=1 ORDER BY u.name')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body max-w-4xl">
      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Add New Order</h2><p class="text-sm text-gray-500">Select SR and add products manually</p></div>
        <a href="<?= rootPath() ?>/manager/orders.php" class="btn btn-ghost">← Orders</a>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form id="order-form" class="space-y-5">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="form-label">Select SR *</label>
              <select id="sr-select" class="form-input" required onchange="loadSRProducts()">
                <option value="">Select Sales Representative</option>
                <?php foreach ($srs as $s): ?>
                  <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> — <?= htmlspecialchars($s['company_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label">Order Date *</label>
              <input type="date" id="order-date" class="form-input" value="<?= date('Y-m-d') ?>" required />
            </div>
          </div>

          <!-- Product Rows -->
          <div>
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-semibold text-gray-700">Products</h3>
              <button type="button" onclick="addProductRow()" class="btn btn-ghost btn-sm" id="add-row-btn" disabled>+ Add Product</button>
            </div>

            <div id="no-sr-msg" class="text-center py-8 text-gray-400 border-2 border-dashed border-gray-200 rounded-xl">
              Select an SR above to load their products
            </div>

            <div id="products-section" style="display:none">
              <div class="overflow-x-auto">
                <table class="data-table" id="products-table">
                  <thead><tr><th class="w-1/2">Product</th><th>Qty (pcs) *</th><th>Price</th><th></th></tr></thead>
                  <tbody id="products-body"></tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-4 border-t border-gray-100">
            <button type="submit" id="save-btn" class="btn btn-primary px-10" disabled>Save Order</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
let srProducts = [];

async function loadSRProducts() {
  const srId = document.getElementById('sr-select').value;
  const tbody = document.getElementById('products-body');
  tbody.innerHTML = '';
  document.getElementById('no-sr-msg').style.display = 'none';
  document.getElementById('products-section').style.display = 'none';
  document.getElementById('add-row-btn').disabled = true;
  document.getElementById('save-btn').disabled = true;

  if (!srId) {
    document.getElementById('no-sr-msg').style.display = 'block';
    return;
  }

  const data = await api('<?= rootPath() ?>/api/orders.php?action=sr_products&sr_id=' + srId);
  srProducts  = data.data || [];

  if (srProducts.length === 0) {
    document.getElementById('no-sr-msg').textContent = 'No products for this SR\'s company yet.';
    document.getElementById('no-sr-msg').style.display = 'block';
    return;
  }

  document.getElementById('products-section').style.display = 'block';
  document.getElementById('add-row-btn').disabled = false;
  document.getElementById('save-btn').disabled = false;
  addProductRow();
}

function getProductOptions(selectedId = '') {
  let opts = '<option value="">Select Product</option>';
  srProducts.forEach(p => {
    opts += `<option value="${p.id}" data-price="${p.selling_price}" data-ppb="${p.pieces_per_box}" ${p.id == selectedId ? 'selected' : ''}>${p.name} (${p.pieces_per_box} pcs/box)</option>`;
  });
  return opts;
}

function addProductRow() {
  const tbody = document.getElementById('products-body');
  const tr    = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <select class="form-input product-sel" required onchange="rowChanged(this)">
        ${getProductOptions()}
      </select>
    </td>
    <td><input type="number" class="form-input qty-inp" min="1" placeholder="qty in pcs" required /></td>
    <td class="text-gray-500 text-sm row-price">—</td>
    <td><button type="button" onclick="this.closest('tr').remove(); updateUsed()" class="btn btn-danger btn-sm">✕</button></td>
  `;
  tbody.appendChild(tr);
  updateUsed();
}

function rowChanged(sel) {
  const opt = sel.options[sel.selectedIndex];
  const tr  = sel.closest('tr');
  tr.querySelector('.row-price').textContent = opt.value ? '৳' + parseFloat(opt.dataset.price || 0).toFixed(2) : '—';
  updateUsed();
}

function updateUsed() {
  const used = [];
  document.querySelectorAll('.product-sel').forEach(s => { if (s.value) used.push(s.value); });
  document.querySelectorAll('.product-sel').forEach(s => {
    s.querySelectorAll('option').forEach(o => {
      o.disabled = o.value && used.includes(o.value) && o.value !== s.value;
    });
  });
}

document.getElementById('order-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const items = [];
  let valid   = true;
  document.querySelectorAll('#products-body tr').forEach(tr => {
    const pid = tr.querySelector('.product-sel').value;
    const qty = parseInt(tr.querySelector('.qty-inp').value);
    if (!pid || !qty || qty < 1) { valid = false; return; }
    items.push({ product_id: pid, qty_pieces: qty });
  });
  if (!valid || items.length === 0) { showToast('Add at least one product with a valid quantity', 'error'); return; }
  const btn = document.getElementById('save-btn');
  btn.disabled = true; btn.textContent = 'Saving…';
  const data = await api('<?= rootPath() ?>/api/orders.php', 'POST', {
    sr_id: document.getElementById('sr-select').value,
    order_date: document.getElementById('order-date').value,
    items
  });
  if (data.success) {
    showToast('Order saved!');
    setTimeout(() => window.location.href = '<?= rootPath() ?>/manager/orders.php', 1000);
  } else {
    showToast(data.message || 'Error', 'error');
    btn.disabled = false; btn.textContent = 'Save Order';
  }
});
</script>
