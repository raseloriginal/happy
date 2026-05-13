<?php
// includes/sidebar.php
require_once __DIR__ . '/../config/session.php';
requireAuth();
$role     = $_SESSION['role'] ?? '';
$current  = $_SERVER['SCRIPT_NAME'] ?? '';
$root     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/..';

function navLink(string $href, string $icon, string $label, string $current): string {
    $active = (strpos($current, $href) !== false) ? 'active' : '';
    return '<a href="' . $href . '" class="sidebar-link ' . $active . '">' . $icon . '<span>' . $label . '</span></a>';
}
?>
<nav class="sidebar">
  <!-- Logo -->
  <div class="px-5 py-5 border-b border-white/10">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center text-white font-bold text-sm">HB</div>
      <div>
        <div class="text-white font-bold text-sm leading-tight">Happy Bangladesh</div>
        <div class="text-slate-400 text-xs">ERP System</div>
      </div>
    </div>
  </div>

  <div class="flex-1 px-3 py-4 space-y-0.5">

    <?php if ($role === 'admin'): ?>
      <div class="sidebar-section">Dashboard</div>
      <?= navLink(rootPath() . '/admin/index.php', '📊', 'Dashboard', $current) ?>

      <div class="sidebar-section">Master Data</div>
      <?= navLink(rootPath() . '/admin/warehouses.php', '🏭', 'Warehouses', $current) ?>
      <?= navLink(rootPath() . '/admin/dealers.php', '🤝', 'Dealers', $current) ?>
      <?= navLink(rootPath() . '/admin/companies.php', '🏢', 'Companies', $current) ?>
      <?= navLink(rootPath() . '/admin/routes.php', '🗺️', 'Routes', $current) ?>

      <div class="sidebar-section">People</div>
      <?= navLink(rootPath() . '/admin/managers.php', '👔', 'Managers', $current) ?>
      <?= navLink(rootPath() . '/admin/sr.php', '🧑‍💼', 'Sales Reps (SR)', $current) ?>
      <?= navLink(rootPath() . '/admin/dsr.php', '🚚', 'Delivery Reps (DSR)', $current) ?>

      <div class="sidebar-section">Reports</div>
      <?= navLink(rootPath() . '/admin/reports.php', '📈', 'Reports', $current) ?>

    <?php elseif ($role === 'manager'): ?>
      <div class="sidebar-section">Dashboard</div>
      <?= navLink(rootPath() . '/manager/index.php', '📊', 'Dashboard', $current) ?>

      <div class="sidebar-section">Products</div>
      <?= navLink(rootPath() . '/manager/categories.php', '🗂️', 'Categories', $current) ?>
      <?= navLink(rootPath() . '/manager/products.php', '📦', 'Products', $current) ?>

      <div class="sidebar-section">Lots & QR</div>
      <?= navLink(rootPath() . '/manager/lots.php', '🗃️', 'Lots', $current) ?>
      <?= navLink(rootPath() . '/manager/lot_add.php', '➕', 'Add Lot', $current) ?>
      <?= navLink(rootPath() . '/manager/qr_generate.php', '🔲', 'Generate QR', $current) ?>
      <?= navLink(rootPath() . '/manager/qr_print.php', '🖨️', 'Print QR', $current) ?>

      <div class="sidebar-section">Orders</div>
      <?= navLink(rootPath() . '/manager/orders.php', '📋', 'Orders', $current) ?>
      <?= navLink(rootPath() . '/manager/order_add.php', '➕', 'Add Order', $current) ?>
      <?= navLink(rootPath() . '/manager/delivery.php', '🚚', 'Out for Delivery', $current) ?>

      <div class="sidebar-section">Operations</div>
      <?= navLink(rootPath() . '/manager/returns.php', '↩️', 'Returns', $current) ?>
      <?= navLink(rootPath() . '/manager/inventory.php', '🏪', 'Inventory', $current) ?>
      <?= navLink(rootPath() . '/manager/expenses.php', '💰', 'Expenses', $current) ?>
      <?= navLink(rootPath() . '/manager/cashflow.php', '💵', 'Cash Flow', $current) ?>

    <?php elseif ($role === 'dsr'): ?>
      <div class="sidebar-section">Dashboard</div>
      <?= navLink(rootPath() . '/dsr/index.php', '📊', 'Dashboard', $current) ?>
      <?= navLink(rootPath() . '/dsr/expenses.php', '💰', 'My Expenses', $current) ?>

    <?php elseif ($role === 'dealer'): ?>
      <div class="sidebar-section">Dashboard</div>
      <?= navLink(rootPath() . '/dealer/index.php', '📊', 'Dashboard', $current) ?>
    <?php endif; ?>

  </div>

  <!-- Footer -->
  <div class="px-5 py-4 border-t border-white/10">
    <a href="<?= rootPath() ?>/logout.php" class="sidebar-link w-full">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      <span>Logout</span>
    </a>
  </div>
</nav>
