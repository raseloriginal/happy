<?php
// eggland/products.php — Products CRUD (Excel Sheet Style)

require_once __DIR__ . '/data.php';

$pageTitle = 'Eggland Products Sheet';

$msg = '';
$err = '';

// Handle Actions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $unit = trim($_POST['unit'] ?? 'Tray');
        $desc = trim($_POST['description'] ?? '');
        
        if (!$name || $price <= 0) {
            $err = 'Product Name and a positive Unit Price are required.';
        } else {
            $products = eggGetProducts();
            $newProduct = [
                'id' => eggGetNextId($products),
                'name' => $name,
                'price' => $price,
                'unit' => $unit,
                'description' => $desc,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $products[] = $newProduct;
            if (eggSaveProducts($products)) {
                $msg = "Product '{$name}' added successfully!";
            } else {
                $err = 'Failed to save product details.';
            }
        }
    }
    
    elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $unit = trim($_POST['unit'] ?? 'Tray');
        $desc = trim($_POST['description'] ?? '');
        
        if (!$id || !$name || $price <= 0) {
            $err = 'Invalid product inputs.';
        } else {
            $products = eggGetProducts();
            $updated = false;
            foreach ($products as &$product) {
                if (intval($product['id']) === $id) {
                    $product['name'] = $name;
                    $product['price'] = $price;
                    $product['unit'] = $unit;
                    $product['description'] = $desc;
                    $updated = true;
                    break;
                }
            }
            if ($updated && eggSaveProducts($products)) {
                $msg = "Product details updated successfully!";
            } else {
                $err = 'Failed to update product details.';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            $err = 'Invalid product selection.';
        } else {
            // Safety check: verify if the product was ever ordered
            $orders = eggGetOrders();
            $hasOrdered = false;
            foreach ($orders as $o) {
                foreach ($o['items'] as $item) {
                    if (intval($item['product_id']) === $id) {
                        $hasOrdered = true;
                        break 2;
                    }
                }
            }
            
            if ($hasOrdered) {
                $err = 'Cannot delete product: This product is associated with active historical orders. Keep it to preserve invoice records.';
            } else {
                $products = eggGetProducts();
                $filtered = [];
                $found = false;
                foreach ($products as $p) {
                    if (intval($p['id']) === $id) {
                        $found = true;
                        continue;
                    }
                    $filtered[] = $p;
                }
                if ($found && eggSaveProducts($filtered)) {
                    $msg = 'Product deleted successfully!';
                } else {
                    $err = 'Failed to delete product or product not found.';
                }
            }
        }
    }
}

// Load data
$products = eggGetProducts();

include __DIR__ . '/../includes/header.php';
echo getExcelStyles();
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">

      <!-- Header & Excel Ribbon Brand -->
      <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-3 gap-2">
        <div>
          <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <span class="text-excel-green"><i class="fa-solid fa-box"></i></span> 
            Products Catalog Sheet
          </h2>
          <p class="text-xs text-gray-500 font-mono">Operations Mode: Inventory & Unit Price Master Catalog</p>
        </div>
        <div>
          <button onclick="openAddProductModal()" class="btn-excel"><i class="fa-solid fa-plus mr-1"></i> Add New Product</button>
        </div>
      </div>

      <!-- Excel tabs -->
      <?= renderExcelTabs('products') ?>

      <!-- Alerts -->
      <?php if ($msg): ?>
        <div class="bg-emerald-50 border border-emerald-500 text-emerald-800 px-4 py-2 mb-4 text-sm flex items-center gap-2">
          <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>
      <?php if ($err): ?>
        <div class="bg-red-50 border border-red-500 text-red-800 px-4 py-2 mb-4 text-sm flex items-center gap-2">
          <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($err) ?>
        </div>
      <?php endif; ?>

      <!-- PRODUCTS MASTER SPREADSHEET SPREAD -->
      <div class="bg-white border border-gray-200 p-4">
        <div class="excel-grid-container">
          <table class="excel-table">
            <thead>
              <tr>
                <th class="excel-row-header">Row</th>
                <th>A: Product Ref ID</th>
                <th>B: Product Variety Name</th>
                <th>C: Default Selling Price</th>
                <th>D: Measurement Unit</th>
                <th>E: Specifications / Description</th>
                <th class="text-center" style="width: 180px;">F: Spreadsheet Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $rowNum = 1;
              foreach ($products as $p):
              ?>
              <tr>
                <td class="excel-row-header"><?= $rowNum++ ?></td>
                <td class="font-mono text-xs text-gray-500">#<?= str_pad($p['id'], 3, '0', STR_PAD_LEFT) ?></td>
                <td class="font-semibold text-gray-800"><?= htmlspecialchars($p['name']) ?></td>
                <td class="font-mono font-bold text-excel-green">৳ <?= number_format($p['price'], 2) ?></td>
                <td><span class="badge badge-gray"><?= htmlspecialchars($p['unit']) ?></span></td>
                <td class="text-xs text-gray-500 italic"><?= htmlspecialchars($p['description'] ?: 'No specifications entered.') ?></td>
                <td class="text-center flex justify-center gap-2">
                  <button onclick="openEditProductModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)" class="btn-excel btn-sm py-1 px-2 text-[11px]">
                    <i class="fa-solid fa-edit"></i> Edit
                  </button>
                  <button onclick="triggerDeleteProduct(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>')" class="btn-excel-danger btn-sm py-1 px-2 text-[11px]">
                    <i class="fa-solid fa-trash"></i> Delete
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($products)): ?>
              <tr>
                <td class="excel-row-header">1</td>
                <td colspan="6" class="text-center py-6 text-gray-400">No egg products listed in catalog yet.</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ADD PRODUCT MODAL -->
<div id="add-product-modal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-4">
      <h3 class="font-bold text-gray-800 text-base"><i class="fa-solid fa-box text-excel-green"></i> Add New Product to Catalog</h3>
      <button onclick="closeAddProductModal()" class="text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
    </div>
    
    <form action="" method="POST" class="space-y-3">
      <input type="hidden" name="action" value="add">
      
      <div>
        <label class="form-label">Product Name / Variety</label>
        <input type="text" name="name" class="excel-input" placeholder="e.g. Organic Brown Duck Eggs" required>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="form-label">Default Price per Unit (BDT)</label>
          <input type="number" step="0.01" name="price" class="excel-input font-mono" placeholder="0.00" required min="0.01">
        </div>
        <div>
          <label class="form-label">Measurement Unit</label>
          <select name="unit" class="excel-input select" required>
            <option value="Tray">Tray of 30 Eggs</option>
            <option value="Piece">Single Piece</option>
            <option value="Box">Box of 100 Eggs</option>
            <option value="Pack">Pack of 10 Eggs</option>
            <option value="Dozen">Dozen (12 Eggs)</option>
          </select>
        </div>
      </div>

      <div>
        <label class="form-label">Product Description / Notes</label>
        <textarea name="description" rows="3" class="excel-input" placeholder="e.g. Sourced from organic duck farms in Netrokona, medium-large sizes."></textarea>
      </div>

      <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
        <button type="button" onclick="closeAddProductModal()" class="btn-excel-ghost">Cancel</button>
        <button type="submit" class="btn-excel">Save Product</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT PRODUCT MODAL -->
<div id="edit-product-modal" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-4">
      <h3 class="font-bold text-gray-800 text-base"><i class="fa-solid fa-edit text-excel-green"></i> Edit Product Variety</h3>
      <button onclick="closeEditProductModal()" class="text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
    </div>
    
    <form action="" method="POST" class="space-y-3">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit-product-id">
      
      <div>
        <label class="form-label">Product Name / Variety</label>
        <input type="text" name="name" id="edit-product-name" class="excel-input" required>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="form-label">Default Price per Unit (BDT)</label>
          <input type="number" step="0.01" name="price" id="edit-product-price" class="excel-input font-mono" required min="0.01">
        </div>
        <div>
          <label class="form-label">Measurement Unit</label>
          <select name="unit" id="edit-product-unit" class="excel-input select" required>
            <option value="Tray">Tray of 30 Eggs</option>
            <option value="Piece">Single Piece</option>
            <option value="Box">Box of 100 Eggs</option>
            <option value="Pack">Pack of 10 Eggs</option>
            <option value="Dozen">Dozen (12 Eggs)</option>
          </select>
        </div>
      </div>

      <div>
        <label class="form-label">Product Description / Notes</label>
        <textarea name="description" id="edit-product-desc" rows="3" class="excel-input"></textarea>
      </div>

      <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
        <button type="button" onclick="closeEditProductModal()" class="btn-excel-ghost">Cancel</button>
        <button type="submit" class="btn-excel">Update Product</button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE PRODUCT FORM -->
<form action="" method="POST" id="delete-product-form" class="hidden">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="delete-product-id">
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
function openAddProductModal() {
  document.getElementById('add-product-modal').classList.remove('hidden');
}
function closeAddProductModal() {
  document.getElementById('add-product-modal').classList.add('hidden');
}

function openEditProductModal(prod) {
  document.getElementById('edit-product-id').value = prod.id;
  document.getElementById('edit-product-name').value = prod.name;
  document.getElementById('edit-product-price').value = prod.price;
  document.getElementById('edit-product-unit').value = prod.unit;
  document.getElementById('edit-product-desc').value = prod.description;
  document.getElementById('edit-product-modal').classList.remove('hidden');
}
function closeEditProductModal() {
  document.getElementById('edit-product-modal').classList.add('hidden');
}

function triggerDeleteProduct(id, name) {
  if (confirm("Are you absolutely sure you want to delete product '" + name + "'?\nThis action will fail if the product is found in historical order invoice statements.")) {
    document.getElementById('delete-product-id').value = id;
    document.getElementById('delete-product-form').submit();
  }
}
</script>
