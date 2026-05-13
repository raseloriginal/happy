<?php
// includes/navbar.php
require_once __DIR__ . '/../config/session.php';
requireAuth();
$userName  = htmlspecialchars($_SESSION['name']  ?? 'User');
$userRole  = htmlspecialchars($_SESSION['role']  ?? '');
$roleColors = [
  'admin'   => 'badge-danger',
  'manager' => 'badge-info',
  'dsr'     => 'badge-warning',
  'dealer'  => 'badge-success',
  'sr'      => 'badge-gray',
];
$roleBadge = $roleColors[$userRole] ?? 'badge-gray';
$rootPath  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/..';
?>
<header class="bg-white border-b border-gray-100 px-6 py-3 flex items-center justify-between sticky top-0 z-30 shadow-sm">
  <div class="flex items-center gap-3">
    <button id="sidebar-toggle" class="text-gray-500 hover:text-gray-700 lg:hidden" onclick="document.querySelector('.sidebar').classList.toggle('sidebar-open')">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <h1 class="text-sm font-semibold text-gray-700"><?= $pageTitle ?? 'Dashboard' ?></h1>
  </div>
  <div class="flex items-center gap-3">
    <span class="badge <?= $roleBadge ?> uppercase"><?= $userRole ?></span>
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm">
        <?= strtoupper(substr($userName, 0, 1)) ?>
      </div>
      <span class="text-sm font-medium text-gray-700 hidden sm:block"><?= $userName ?></span>
    </div>
    <a href="<?= $rootPath ?>/logout.php" class="btn btn-ghost btn-sm">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Logout
    </a>
  </div>
</header>
