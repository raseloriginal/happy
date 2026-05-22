<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');

$id = intval($_GET['id'] ?? 0);
$pdo = getDB();

// Fetch lot header
$stmt = $pdo->prepare('SELECT * FROM lots WHERE id = ? AND status = 1');
$stmt->execute([$id]);
$lot = $stmt->fetch();

if (!$lot) {
    echo "Lot not found.";
    exit;
}

// Check if lot can be edited (no QR codes dispatched or modified)
$chk = $pdo->prepare("
    SELECT COUNT(*) FROM qr_codes 
    WHERE lot_id = ? 
      AND (
        status != 'active' 
        OR pieces_remaining < pieces_total 
        OR id IN (SELECT DISTINCT qr_code_id FROM dispatch_items)
        OR id IN (SELECT DISTINCT qr_code_id FROM return_items)
      )
");
$chk->execute([$id]);
$is_editable = (intval($chk->fetchColumn()) === 0);

// Fetch companies
$companies  = $pdo->query('SELECT id, name FROM companies WHERE status=1 ORDER BY name')->fetchAll();

// Fetch lot items with product names
$itemsStmt = $pdo->prepare('
    SELECT li.*, p.name as product_name, p.pieces_per_box 
    FROM lot_items li 
    JOIN products p ON p.id = li.product_id 
    WHERE li.lot_id = ?
');
$itemsStmt->execute([$id]);
$lot_items = $itemsStmt->fetchAll();

$pageTitle  = 'Edit Lot';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body max-w-4xl">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-xl font-bold text-gray-800">Edit Lot #<?= $id ?></h2>
          <p class="text-sm text-gray-500">Update company details, products, quantities, or prices of this batch</p>
        </div>
        <a href="<?= rootPath() ?>/manager/lots.php" id="back-btn" class="btn btn-ghost"><i class="fa-solid fa-arrow-left mr-1"></i> Back to Lots</a>
      </div>

      <?php if (!$is_editable): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-start gap-3">
          <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
          <div>
            <div class="font-bold">Editing Blocked</div>
            <div class="text-sm">This lot has QR codes that have already been dispatched or modified. To maintain data consistency, you cannot edit this lot.</div>
          </div>
        </div>
      <?php endif; ?>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form id="lot-form" class="space-y-5">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="form-label">Company *</label>
              <select id="company_id" class="form-input" required onchange="loadCompanyProducts()" <?= !$is_editable ? 'disabled' : '' ?>>
                <option value="">Select Company</option>
                <?php foreach ($companies as $c): ?>
                  <option value="<?= $c['id'] ?>" <?= $c['id'] == $lot['company_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="form-label">Lot Date *</label>
              <input type="date" id="lot_date" class="form-input" required value="<?= htmlspecialchars($lot['lot_date']) ?>" <?= !$is_editable ? 'disabled' : '' ?> />
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-semibold text-gray-700">Products</h3>
              <?php if ($is_editable): ?>
                <button type="button" onclick="addRow()" class="btn btn-ghost btn-sm">+ Add Row</button>
              <?php endif; ?>
            </div>
            <div class="overflow-x-auto">
              <table class="data-table" id="items-table">
                <thead>
                  <tr>
                    <th class="w-1/3">Product</th>
                    <th>Qty (Boxes)</th>
                    <th>Expiry Date</th>
                    <th>Buying Price (৳)</th>
                    <th>Total (৳)</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="items-body">
                  <!-- rows populated by JS -->
                </tbody>
              </table>
            </div>
          </div>

          <div class="flex items-center justify-between pt-4 border-t border-gray-100">
            <div class="text-right">
              <div class="text-sm text-gray-500">Grand Total</div>
              <div class="text-2xl font-bold text-indigo-600">৳<span id="grand-total">0.00</span></div>
            </div>
            <?php if ($is_editable): ?>
              <button type="submit" id="submit-btn" class="btn btn-primary px-8">Save Changes</button>
            <?php endif; ?>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
let products = [];
const existingItems = <?= json_encode($lot_items) ?>;
const isEditable = <?= $is_editable ? 'true' : 'false' ?>;

async function loadCompanyProducts() {
  const cid = document.getElementById('company_id').value;
  if (!cid) return;
  const data = await api('<?= rootPath() ?>/api/products.php?action=by_company&company_id=' + cid);
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
  if (!isEditable) return;
  const tbody = document.getElementById('items-body');
  const tr    = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <select class="form-input product-select" onchange="rowChanged(this)" required>
        <option value="">Select Product</option>
        ${products.map(p => `<option value="${p.id}" data-ppb="${p.pieces_per_box}">${p.name}</option>`).join('')}
      </select>
    </td>
    <td><input type="number" class="form-input qty-input" min="1" value="1" oninput="calcRow(this)" required /></td>
    <td><input type="date" class="form-input expiry-input" required /></td>
    <td><input type="number" class="form-input price-input" min="0" step="0.01" value="0" oninput="calcRow(this)" required /></td>
    <td class="font-medium row-total text-gray-700">৳0.00</td>
    <td><button type="button" onclick="removeRow(this)" class="btn btn-danger btn-sm"><i class="fa-solid fa-xmark"></i></button></td>
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
  if (!isEditable) return;
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
  if (!isEditable) return;

  const btn    = document.getElementById('submit-btn');
  const items  = [];
  let valid    = true;

  document.querySelectorAll('#items-body tr').forEach(tr => {
    const pid    = tr.querySelector('.product-select').value;
    const qty    = parseInt(tr.querySelector('.qty-input').value);
    const expiry = tr.querySelector('.expiry-input').value;
    const price  = parseFloat(tr.querySelector('.price-input').value);
    if (!pid || !qty || !expiry) { valid = false; return; }
    items.push({ product_id: pid, qty_boxes: qty, expiry_date: expiry, buying_price: price });
  });

  if (!valid || items.length === 0) { showToast('Add at least one valid product row with expiry date', 'error'); return; }

  if (!confirmDelete('Are you sure you want to update this lot? Unused QR codes will be reset and you will need to regenerate them.')) {
    return;
  }

  btn.disabled = true; btn.textContent = 'Saving…';

  const data = await api('<?= rootPath() ?>/api/lots.php?id=<?= $id ?>', 'PUT', {
    company_id: document.getElementById('company_id').value,
    lot_date: document.getElementById('lot_date').value,
    grand_total: parseFloat(document.getElementById('grand-total').textContent),
    items
  });

  if (data.success) {
    showToast('Lot updated successfully!');
    setTimeout(() => window.location.href = '<?= rootPath() ?>/manager/lots.php', 1000);
  } else {
    showToast(data.message || 'Error saving lot', 'error');
    btn.disabled = false; btn.textContent = 'Save Changes';
  }
});

// Load everything on startup
window.addEventListener('DOMContentLoaded', async () => {
  await loadCompanyProducts();

  const tbody = document.getElementById('items-body');
  tbody.innerHTML = '';

  existingItems.forEach(item => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>
        <select class="form-input product-select" onchange="rowChanged(this)" required <?= !$is_editable ? 'disabled' : '' ?>>
          <option value="">Select Product</option>
          ${products.map(p => `<option value="${p.id}" data-ppb="${p.pieces_per_box}" ${p.id == item.product_id ? 'selected' : ''}>${p.name}</option>`).join('')}
        </select>
      </td>
      <td><input type="number" class="form-input qty-input" min="1" value="${item.qty_boxes}" oninput="calcRow(this)" required <?= !$is_editable ? 'disabled' : '' ?> /></td>
      <td><input type="date" class="form-input expiry-input" value="${item.expiry_date || ''}" required <?= !$is_editable ? 'disabled' : '' ?> /></td>
      <td><input type="number" class="form-input price-input" min="0" step="0.01" value="${item.buying_price}" oninput="calcRow(this)" required <?= !$is_editable ? 'disabled' : '' ?> /></td>
      <td class="font-medium row-total text-gray-700">৳${parseFloat(item.total).toFixed(2)}</td>
      <td>
        <?php if ($is_editable): ?>
          <button type="button" onclick="removeRow(this)" class="btn btn-danger btn-sm"><i class="fa-solid fa-xmark"></i></button>
        <?php endif; ?>
      </td>
    `;
    tbody.appendChild(tr);
  });

  calcGrandTotal();
  disableUsedProducts();
});
</script>
