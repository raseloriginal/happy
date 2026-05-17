<?php
// includes/sidebar.php
require_once __DIR__ . '/../config/session.php';
requireAuth();
$role     = $_SESSION['role'] ?? '';
$current  = $_SERVER['SCRIPT_NAME'] ?? '';
$root     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/..';

function navLink(string $href, string $icon, string $label, string $current): string {
    $active = (strpos($current, $href) !== false) ? 'active' : '';
    return '<a href="' . $href . '" class="sidebar-link ' . $active . '" title="' . $label . '"><span class="text-lg flex items-center justify-center w-6"><i class="' . $icon . '"></i></span><span class="sidebar-text">' . $label . '</span></a>';
}
?>
<nav class="sidebar" id="app-sidebar">
  <!-- Logo -->
  <div class="px-5 py-5 border-b border-white/10 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center text-white font-bold text-sm flex-shrink-0">HB</div>
      <div class="sidebar-text">
        <div class="text-white font-bold text-sm leading-tight whitespace-nowrap">Happy Bangladesh</div>
        <div class="text-slate-400 text-xs whitespace-nowrap">ERP System</div>
      </div>
    </div>
  </div>

  <div class="flex-1 px-3 py-4 space-y-0.5">

    <?php if ($role === 'admin'): ?>
      <div class="sidebar-section">Dashboard</div>
      <?= navLink(rootPath() . '/admin/index.php', 'fa-solid fa-chart-line', 'Dashboard', $current) ?>

      <div class="sidebar-section">Master Data</div>
      <?= navLink(rootPath() . '/admin/warehouses.php', 'fa-solid fa-warehouse', 'Warehouses', $current) ?>
      <?= navLink(rootPath() . '/admin/dealers.php', 'fa-solid fa-handshake', 'Dealers', $current) ?>
      <?= navLink(rootPath() . '/admin/companies.php', 'fa-solid fa-building', 'Companies', $current) ?>
      <?= navLink(rootPath() . '/admin/routes.php', 'fa-solid fa-route', 'Routes', $current) ?>

      <div class="sidebar-section">People</div>
      <?= navLink(rootPath() . '/admin/managers.php', 'fa-solid fa-user-tie', 'Managers', $current) ?>
      <?= navLink(rootPath() . '/admin/sr.php', 'fa-solid fa-user-tag', 'Sales Reps (SR)', $current) ?>
      <?= navLink(rootPath() . '/admin/dsr.php', 'fa-solid fa-truck', 'Delivery Reps (DSR)', $current) ?>

      <div class="sidebar-section">Reports</div>
      <?= navLink(rootPath() . '/admin/reports.php', 'fa-solid fa-chart-bar', 'Reports', $current) ?>

      <div class="sidebar-section">System</div>
      <?= navLink(rootPath() . '/admin/db_sync.php', 'fa-solid fa-sync', 'Database Sync', $current) ?>

    <?php elseif ($role === 'manager'): ?>
      <div class="sidebar-section">Dashboard</div>
      <?= navLink(rootPath() . '/manager/index.php', 'fa-solid fa-chart-line', 'Dashboard', $current) ?>

      <div class="sidebar-section">Products</div>
      <?= navLink(rootPath() . '/manager/categories.php', 'fa-solid fa-tags', 'Categories', $current) ?>
      <?= navLink(rootPath() . '/manager/products.php', 'fa-solid fa-box', 'Products', $current) ?>

      <div class="sidebar-section">Lots & QR</div>
      <?= navLink(rootPath() . '/manager/lots.php', 'fa-solid fa-boxes-stacked', 'Lots', $current) ?>
      <?= navLink(rootPath() . '/manager/lot_add.php', 'fa-solid fa-plus', 'Add Lot', $current) ?>
      <?= navLink(rootPath() . '/manager/qr_generate.php', 'fa-solid fa-qrcode', 'Generate QR', $current) ?>
      <?= navLink(rootPath() . '/manager/qr_print.php', 'fa-solid fa-print', 'Print QR', $current) ?>

      <div class="sidebar-section">Orders</div>
      <?= navLink(rootPath() . '/manager/orders.php', 'fa-solid fa-clipboard-list', 'Orders', $current) ?>
      <?= navLink(rootPath() . '/manager/ready_sales.php', 'fa-solid fa-receipt', 'Ready Sales', $current) ?>
      <?= navLink(rootPath() . '/manager/order_add.php', 'fa-solid fa-plus', 'Add Order', $current) ?>
      <?= navLink(rootPath() . '/manager/delivery.php', 'fa-solid fa-truck-fast', 'Out for Delivery', $current) ?>

      <div class="sidebar-section">Operations</div>
      <?= navLink(rootPath() . '/manager/returns.php', 'fa-solid fa-undo', 'Returns', $current) ?>
      <?= navLink(rootPath() . '/manager/inventory.php', 'fa-solid fa-store', 'Inventory', $current) ?>
      <?= navLink(rootPath() . '/manager/expenses.php', 'fa-solid fa-money-bill-wave', 'Expenses', $current) ?>
      <?= navLink(rootPath() . '/manager/cashflow.php', 'fa-solid fa-money-check-dollar', 'Cash Flow', $current) ?>

    <?php elseif ($role === 'dsr'): ?>
      <div class="sidebar-section">Dashboard</div>
      <?= navLink(rootPath() . '/dsr/index.php', 'fa-solid fa-chart-line', 'Dashboard', $current) ?>
      <?= navLink(rootPath() . '/dsr/expenses.php', 'fa-solid fa-money-bill-wave', 'My Expenses', $current) ?>

    <?php elseif ($role === 'dealer'): ?>
      <div class="sidebar-section">Dashboard</div>
      <?= navLink(rootPath() . '/dealer/index.php', 'fa-solid fa-chart-line', 'Dashboard', $current) ?>
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

<!-- Mobile Overlay -->
<div class="mobile-overlay" onclick="document.getElementById('app-sidebar').classList.remove('sidebar-open')"></div>
