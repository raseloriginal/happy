<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle  = 'Add Lot';
$pdo        = getDB();
$companies  = $pdo->query('SELECT id, name FROM companies WHERE status=1 ORDER BY name')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body max-w-4xl">
      <div class="flex items-center justify-between mb-6">
        <div><h2 class="text-xl font-bold text-gray-800">Add New Lot</h2><p class="text-sm text-gray-500">Record a new product batch received from company</p></div>
        <a href="/happycrm2/manager/lots.php" class="btn btn-ghost">← Back to Lots</a>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form id="lot-form" class="space-y-5">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="form-label">Company *</label>
              <select id="company_id" class="form-input" required onchange="loadCompanyProducts()">
                <option value="">Select Company</option>
                <?php foreach ($companies as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label">Lot Date *</label>
              <input type="date" id="lot_date" class="form-input" required value="<?= date('Y-m-d') ?>" />
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-semibold text-gray-700">Products</h3>
              <button type="button" onclick="addRow()" class="btn btn-ghost btn-sm">+ Add Row</button>
            </div>
            <div class="overflow-x-auto">
              <table class="data-table" id="items-table">
                <thead>
                  <tr>
                    <th class="w-1/2">Product</th>
                    <th>Qty (Boxes)</th>
                    <th>Buying Price (৳)</th>
                    <th>Total (৳)</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="items-body">
                  <!-- rows added by JS -->
                </tbody>
              </table>
            </div>
          </div>

          <div class="flex items-center justify-between pt-4 border-t border-gray-100">
            <div class="text-right">
              <div class="text-sm text-gray-500">Grand Total</div>
              <div class="text-2xl font-bold text-indigo-600">৳<span id="grand-total">0.00</span></div>
            </div>
            <button type="submit" id="submit-btn" class="btn btn-primary px-8">Save Lot</button>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
let products = [];

async function loadCompanyProducts() {
  const cid = document.getElementById('company_id').value;
  if (!cid) return;
  const data = await api('/happycrm2/api/products.php?action=by_company&company_id=' + cid);
  products = data.data || [];
  // Refresh all product selects
  document.querySelectorAll('.product-select').forEach(sel => refreshProductSelect(sel));
}

function refreshProductSelect(sel) {
  const current = sel.value;
  sel.innerHTML = '<option value="">Select Product</option>';
  products.forEach(p => {
    sel.innerHTML += `<option value="${p.id}" data-ppb="${p.pieces_per_box}">${p.name}</option>`;
  });
  if (current) sel.value = current;
}

function addRow() {
  const tbody = document.getElementById('items-body');
  const idx   = tbody.children.length;
  const tr    = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <select class="form-input product-select" onchange="rowChanged(this)" required>
        <option value="">Select Product</option>
        ${products.map(p => `<option value="${p.id}" data-ppb="${p.pieces_per_box}">${p.name}</option>`).join('')}
      </select>
    </td>
    <td><input type="number" class="form-input qty-input" min="1" value="1" oninput="calcRow(this)" required /></td>
    <td><input type="number" class="form-input price-input" min="0" step="0.01" value="0" oninput="calcRow(this)" required /></td>
    <td class="font-medium row-total text-gray-700">৳0.00</td>
    <td><button type="button" onclick="removeRow(this)" class="btn btn-danger btn-sm">✕</button></td>
  `;
  tbody.appendChild(tr);
}

function rowChanged(sel) { calcRow(sel); disableUsedProducts(); }

function calcRow(input) {
  const tr    = input.closest('tr');
  const qty   = parseFloat(tr.querySelector('.qty-input').value) || 0;
  const price = parseFloat(tr.querySelector('.price-input').value) || 0;
  tr.querySelector('.row-total').textContent = '৳' + (qty * price).toFixed(2);
  calcGrandTotal();
}

function calcGrandTotal() {
  let total = 0;
  document.querySelectorAll('.row-total').forEach(el => {
    total += parseFloat(el.textContent.replace('৳','')) || 0;
  });
  document.getElementById('grand-total').textContent = total.toFixed(2);
}

function removeRow(btn) {
  btn.closest('tr').remove();
  calcGrandTotal();
  disableUsedProducts();
}

function disableUsedProducts() {
  const used = [];
  document.querySelectorAll('.product-select').forEach(sel => { if (sel.value) used.push(sel.value); });
  document.querySelectorAll('.product-select').forEach(sel => {
    sel.querySelectorAll('option').forEach(opt => {
      opt.disabled = opt.value && used.includes(opt.value) && opt.value !== sel.value;
    });
  });
}

document.getElementById('lot-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn    = document.getElementById('submit-btn');
  const items  = [];
  let valid    = true;

  document.querySelectorAll('#items-body tr').forEach(tr => {
    const pid   = tr.querySelector('.product-select').value;
    const qty   = parseInt(tr.querySelector('.qty-input').value);
    const price = parseFloat(tr.querySelector('.price-input').value);
    if (!pid || !qty) { valid = false; return; }
    items.push({ product_id: pid, qty_boxes: qty, buying_price: price });
  });

  if (!valid || items.length === 0) { showToast('Add at least one valid product row', 'error'); return; }

  btn.disabled = true; btn.textContent = 'Saving…';

  const data = await api('/happycrm2/api/lots.php', 'POST', {
    company_id: document.getElementById('company_id').value,
    lot_date: document.getElementById('lot_date').value,
    grand_total: parseFloat(document.getElementById('grand-total').textContent),
    items
  });

  if (data.success) {
    showToast('Lot created successfully!');
    setTimeout(() => window.location.href = '/happycrm2/manager/lots.php', 1000);
  } else {
    showToast(data.message || 'Error saving lot', 'error');
    btn.disabled = false; btn.textContent = 'Save Lot';
  }
});

// Add first row on load
addRow();
</script>
