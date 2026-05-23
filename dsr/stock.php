<?php
// dsr/stock.php — Excel Sheet Responsive Mobile DSR Mal Stock Page
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('dsr');

$pdo = getDB();
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="bn" class="h-full">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
  
  <!-- PWA Meta Tags -->
  <link rel="manifest" href="manifest.json" />
  <meta name="theme-color" content="#2563eb" />
  <link rel="apple-touch-icon" href="../assets/img/logo/pwa-icon-192.png" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />

  <title>ডিএসআর মাল স্টক — হ্যাপি বাংলাদেশ</title>
  
  <!-- Premium Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Fira+Mono:wght@400;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Plus Jakarta Sans', 'sans-serif'],
            mono: ['Fira Mono', 'monospace']
          }
        }
      }
    }
  </script>

  <style>
    /* Absolute Zero Border Radius for Excel Look */
    *, .btn, .stat-card, .form-input, .badge, .modal-box, table, tr, th, td, input, select, textarea, button, div, span, img, a {
      border-radius: 0px !important;
    }

    :root {
      --excel-blue: #2563eb;
      --excel-blue-dark: #1d4ed8;
      --excel-blue-light: #dbeafe;
      --excel-border: #cbd5e1;
      --excel-row-num: #f3f2f1;
    }

    body {
      background-color: #f3f2f1;
      color: #323130;
      font-family: 'Plus Jakarta Sans', sans-serif;
      user-select: none;
      -webkit-tap-highlight-color: transparent;
      padding-bottom: env(safe-area-inset-bottom);
      padding-top: env(safe-area-inset-top);
      overscroll-behavior-y: contain; /* Prevents native browser refresh */
    }

    /* Hide scrollbars for clean mobile app feel */
    ::-webkit-scrollbar {
      display: none;
    }
    body, main, div, table {
      -ms-overflow-style: none;  /* IE and Edge */
      scrollbar-width: none;  /* Firefox */
    }

    /* Spring-Active Animation */
    .btn-bounce, button, nav a {
      transition: transform 0.1s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .btn-bounce:active, button:active, nav a:active {
      transform: scale(0.96);
    }

    /* Excel Table Styles */
    .excel-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 11px;
      background-color: #ffffff;
    }
    .excel-table th {
      background-color: #f3f2f1;
      color: #323130;
      font-weight: bold;
      border: 1px solid var(--excel-border);
      padding: 6px 8px;
      text-align: left;
      user-select: none;
    }
    .excel-table td {
      border: 1px solid var(--excel-border);
      padding: 6px 8px;
      vertical-align: middle;
    }
    
    /* Row numbers like Excel */
    .excel-row-num {
      background-color: #f3f2f1;
      color: #605e5c;
      font-weight: bold;
      text-align: center;
      width: 32px;
      border-right: 2px solid #a19f9d !important;
      font-family: 'Fira Mono', monospace;
      font-size: 10px;
      user-select: none;
    }

    /* Excel Active Tab Styling */
    .tab-active {
      color: var(--excel-blue) !important;
      background-color: #ffffff;
      border-top: 3px solid var(--excel-blue);
      margin-top: -3px;
      border-bottom: 1px solid transparent !important;
      z-index: 10;
    }

    /* --- GLOBAL SCALE UP FOR HIGHER READABILITY --- */
    .text-\[7px\] { font-size: 10px !important; }
    .text-\[8px\] { font-size: 11px !important; }
    .text-\[9px\] { font-size: 12px !important; }
    .text-\[10px\] { font-size: 13px !important; }
    .text-\[11px\] { font-size: 14px !important; }
    .text-xs { font-size: 14px !important; }
    .text-sm { font-size: 16px !important; }
    
    .excel-table {
      font-size: 13px !important;
    }
    .excel-table th {
      padding: 10px 12px !important;
    }
    .excel-table td {
      padding: 10px 12px !important;
    }
    .excel-row-num {
      font-size: 11px !important;
      width: 38px !important;
    }

    .p-2 { padding: 0.75rem !important; }
    .p-3 { padding: 1rem !important; }
    .p-4 { padding: 1.25rem !important; }
    .py-1\.5 { padding-top: 0.6rem !important; padding-bottom: 0.6rem !important; }
    .py-2 { padding-top: 0.75rem !important; padding-bottom: 0.75rem !important; }
    .py-2\.5 { padding-top: 0.9rem !important; padding-bottom: 0.9rem !important; }
    .py-3 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
    .py-3\.5 { padding-top: 1.1rem !important; padding-bottom: 1.1rem !important; }
    
    input[type="text"], input[type="number"], input[type="date"], select, textarea {
      padding-top: 0.6rem !important;
      padding-bottom: 0.6rem !important;
      font-size: 13px !important;
    }
    
    .btn-bounce, button {
      font-size: 13px !important;
    }
  </style>
</head>
<body class="h-full flex flex-col justify-between overflow-hidden">

  <!-- ================= EXCEL STYLE HEADER ================= -->
  <header class="bg-[#2563eb] text-white px-4 py-3 flex items-center justify-between z-40 shrink-0 border-b border-[#1d4ed8]">
    <div class="flex items-center gap-3">
      <div class="rounded-lg overflow-hidden w-8 h-8 bg-white flex items-center justify-center shadow-sm p-1">
        <img src="../assets/img/logo/logo-icon-black.png" alt="Happy Bangladesh" class="w-full h-full object-contain" />
      </div>
      <div>
        <h1 class="text-sm font-extrabold tracking-tight font-mono">ডিএসআর প্যানেল</h1>
        <div class="flex items-center gap-1">
          <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-400"></span>
          <span class="text-[9px] text-blue-200 font-bold uppercase tracking-wider" id="header-subtitle">মাল স্টক</span>
        </div>
      </div>
    </div>
    
    <div class="flex items-center gap-2">
      <!-- Digital Clock -->
      <div class="text-right mr-1.5 hidden xs:block">
        <div class="text-[10px] font-bold font-mono text-blue-100" id="live-time">00:00:00 PM</div>
        <div class="text-[8px] text-blue-200 font-bold uppercase tracking-wider" id="live-date">May 18, 2026</div>
      </div>
      
      <button onclick="logout()" class="w-7 h-7 bg-red-800 text-red-100 flex items-center justify-center btn-bounce border border-red-700">
        <i class="fa-solid fa-power-off text-xs"></i>
      </button>
    </div>
  </header>

  <!-- ================= MAIN EXCEL WORKSPACE SCROLLER ================= -->
  <main class="flex-1 overflow-y-auto p-3 space-y-4" id="main-content">
    
    <!-- Controls: Date selector and Search -->
    <div class="grid grid-cols-2 gap-2 bg-white border border-[#cbd5e1] p-3 shadow-sm rounded">
      <div>
        <label class="block text-[9px] font-bold text-gray-400 uppercase mb-1 tracking-wider">তারিখ</label>
        <div class="relative">
          <i class="fa-solid fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-[#2563eb] text-xs"></i>
          <input type="date" id="van-date-select" onchange="loadVanStock()" class="w-full bg-[#f8fafc] border border-[#cbd5e1] py-1.5 pl-8 pr-2 text-xs focus:outline-none focus:border-[#2563eb] text-gray-800 font-bold font-mono rounded" />
        </div>
      </div>
      <div>
        <label class="block text-[9px] font-bold text-gray-400 uppercase mb-1 tracking-wider">পণ্য খুঁজুন</label>
        <div class="relative">
          <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
          <input type="text" id="van-search" placeholder="পণ্য খুঁজুন..." oninput="filterVanStock()" class="w-full bg-[#f8fafc] border border-[#cbd5e1] py-1.5 pl-8 pr-2 text-xs focus:outline-none focus:border-[#2563eb] text-gray-800 rounded" />
        </div>
      </div>
    </div>

    <!-- Van Products Spreadsheet table -->
    <div class="bg-white border border-[#cbd5e1] overflow-hidden">
      <div class="px-4 py-2.5 bg-gray-100 border-b border-[#cbd5e1] flex justify-between items-center">
        <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">ভ্যানের মাল</span>
        <span class="text-[10px] font-mono text-[#2563eb] font-bold" id="van-products-count">০টি পণ্য</span>
      </div>

      <div class="overflow-x-auto w-full">
        <table class="excel-table w-full" id="van-stock-table">
          <thead>
            <tr class="bg-gray-100">
              <th class="text-left pl-4">পণ্য</th>
              <th class="text-right">বের</th>
              <th class="text-right">ফেরত</th>
              <th class="text-right">বিক্রয়</th>
              <th class="text-right pr-4">টাকা</th>
            </tr>
          </thead>
          <tbody id="van-products-list">
            <!-- Skeleton Loader Injected on Load -->
            <tr>
              <td colspan="5" class="text-center py-8">
                <i class="fa-solid fa-spinner fa-spin text-xl text-[#2563eb]"></i>
                <span class="block text-xs text-gray-500 font-bold mt-2">লোড হচ্ছে...</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Dispatch Summary Report Card -->
    <div class="bg-white border border-[#cbd5e1] overflow-hidden hidden" id="van-summary-card">
      <div class="px-4 py-2.5 bg-gray-100 border-b border-[#cbd5e1] flex justify-between items-center">
        <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">হিসাব: সেটেলমেন্ট ও খরচ</span>
        <span class="text-[10px] font-mono text-[#2563eb] font-bold" id="summary-dispatch-id">#DISP-N/A</span>
      </div>
      <div class="p-4 grid grid-cols-2 gap-4">
        <!-- Left Column Metrics -->
        <div class="space-y-3">
          <div>
            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">মোট বিক্রয়</div>
            <div class="text-lg font-extrabold font-mono text-gray-700 mt-0.5" id="summary-gross-sales">৳0.00</div>
          </div>
          <div>
            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">ক্ষতি</div>
            <div class="text-lg font-extrabold font-mono text-red-500 mt-0.5" id="summary-damage">৳0.00</div>
          </div>
          <div>
            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">খরচ</div>
            <div class="text-lg font-extrabold font-mono text-orange-500 mt-0.5" id="summary-expenses">৳0.00</div>
          </div>
        </div>
        <!-- Right Column Metrics -->
        <div class="space-y-3">
          <div>
            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">জমার লক্ষ্য</div>
            <div class="text-lg font-extrabold font-mono text-blue-600 mt-0.5" id="summary-expected">৳0.00</div>
          </div>
          <div>
            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">জমা দেওয়া হয়েছে</div>
            <div class="text-lg font-extrabold font-mono text-green-600 mt-0.5" id="summary-submitted">৳0.00</div>
          </div>
          <div>
            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">পার্থক্য (কম/বেশি)</div>
            <div class="text-lg font-extrabold font-mono mt-0.5" id="summary-difference">৳0.00</div>
          </div>
        </div>
      </div>
      <!-- Remarks Section -->
      <div class="px-4 py-3 bg-gray-50 border-t border-[#cbd5e1]">
        <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider mb-1">মন্তব্য</div>
        <div class="text-xs text-gray-600 italic font-mono" id="summary-remarks">কোনো মন্তব্য নেই।</div>
      </div>
    </div>
  </main>

  <!-- ================= BOTTOM NAVIGATION TABS (EXCEL TABS STYLE) ================= -->
  <nav class="bg-gray-100 shrink-0 flex items-center justify-around border-t border-[#cbd5e1] z-40 select-none">
    <a href="index.php" id="nav-home" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-r border-[#cbd5e1] border-b border-gray-100 btn-bounce">
      <i class="fa-solid fa-house-chimney text-xs"></i>
      <span>হোম</span>
    </a>
    <a href="stock.php" id="nav-van" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-r border-[#cbd5e1] border-b border-gray-100 btn-bounce tab-active">
      <i class="fa-solid fa-table-cells text-xs"></i>
      <span>মাল স্টক</span>
    </a>
    <a href="settlement.php" id="nav-settlement" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-b border-gray-100 btn-bounce">
      <i class="fa-solid fa-calculator text-xs"></i>
      <span>জমা টাকা</span>
    </a>
  </nav>

  <!-- ================= MOBILE TOAST SYSTEM ================= -->
  <div id="mob-toast" class="fixed top-4 left-1/2 -translate-x-1/2 pointer-events-none z-50 flex flex-col gap-2 w-11/12 max-w-sm"></div>

  <!-- JavaScript App Logic -->
  <script>
    const API_URL = '<?= rootPath() ?>/api/dsr_mobile.php';
    let vanStockData = null;

    // Initialize Page
    window.addEventListener('DOMContentLoaded', () => {
      // Setup Clock
      setInterval(updateClock, 1000);
      updateClock();

      // Setup default date picker to local date (today)
      const tzOffset = (new Date()).getTimezoneOffset() * 60000;
      const localISOTime = (new Date(Date.now() - tzOffset)).toISOString().slice(0, 10);
      const dateSelect = document.getElementById('van-date-select');
      if (dateSelect) {
        dateSelect.value = localISOTime;
      }

      // Initial Data Fetch
      loadVanStock();

      // Initialize Pull-to-refresh
      initPullToRefresh();
    });

    // Clock Formatter
    function updateClock() {
      const now = new Date();
      const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
      const dateStr = now.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
      document.getElementById('live-time').textContent = timeStr;
      document.getElementById('live-date').textContent = dateStr;
    }

    // AJAX API helper
    async function apiCall(endpoint, method = 'GET', body = null) {
      try {
        const options = {
          method,
          headers: { 'Content-Type': 'application/json' }
        };
        if (body) {
          options.body = JSON.stringify(body);
        }
        const res = await fetch(endpoint, options);
        return await res.json();
      } catch (err) {
        showToast('ইন্টারনেট সংযোগ সমস্যা। আবার চেষ্টা করুন।', 'error');
        console.error('API Err:', err);
        return { success: false, message: 'API সংযোগ ব্যর্থ হয়েছে' };
      }
    }

    // Toast alerts
    function showToast(msg, type = 'success') {
      const container = document.getElementById('mob-toast');
      const toast = document.createElement('div');
      
      const theme = {
        success: 'bg-[#dbeafe] border-[#2563eb] text-[#2563eb]',
        error: 'bg-red-50 border-red-500 text-red-800',
        warning: 'bg-amber-50 border-amber-500 text-amber-800',
        info: 'bg-blue-50 border-blue-500 text-blue-800'
      };

      toast.className = `flex items-center gap-2 px-3 py-2 border font-bold text-[11px] shadow-md transition-all duration-300 transform translate-y-2 opacity-0 ${theme[type] || theme.success}`;
      toast.innerHTML = `
        <span class="shrink-0"><i class="fa-solid fa-circle-info"></i></span>
        <span class="leading-tight">${msg}</span>
      `;
      
      container.appendChild(toast);
      
      setTimeout(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
      }, 50);

      setTimeout(() => {
        toast.classList.add('-translate-y-2', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
      }, 3500);
    }

    // Fetch and Render Van Stock List
    async function loadVanStock() {
      const container = document.getElementById('van-products-list');
      // Show loading spinner
      container.innerHTML = `
        <tr>
          <td colspan="5" class="text-center py-8">
            <i class="fa-solid fa-spinner fa-spin text-xl text-[#2563eb]"></i>
            <span class="block text-xs text-gray-500 font-bold mt-2">লোড হচ্ছে...</span>
          </td>
        </tr>
      `;

      const dateVal = document.getElementById('van-date-select').value;
      const url = API_URL + '?action=van_stock' + (dateVal ? '&date=' + dateVal : '');
      const data = await apiCall(url);
      
      if (!data.success) {
        container.innerHTML = `
          <tr>
            <td colspan="5" class="text-center py-6 text-red-500 italic px-4">মালের তালিকা লোড করা ব্যর্থ হয়েছে</td>
          </tr>
        `;
        document.getElementById('van-summary-card').classList.add('hidden');
        return;
      }

      vanStockData = data;
      const countEl = document.getElementById('van-products-count');
      countEl.textContent = `${data.products ? data.products.length : 0}টি পণ্য লোড হয়েছে`;

      if (!data.products || data.products.length === 0) {
        container.innerHTML = `
          <tr>
            <td colspan="5" class="text-center py-6 text-gray-400 italic px-4">এই তারিখে কোনো পণ্য লোড হয়নি।</td>
          </tr>
        `;
      } else {
        renderVanStockRows(data.products);
      }

      // Render Summary Report
      const summaryCard = document.getElementById('van-summary-card');
      if (data.dispatch_id) {
        summaryCard.classList.remove('hidden');
        document.getElementById('summary-dispatch-id').textContent = `#DISP-${String(data.dispatch_id).padStart(4, '0')}`;
        
        let grossSales = 0;
        if (data.products) {
          data.products.forEach(p => {
            grossSales += p.sold.value;
          });
        }
        
        document.getElementById('summary-gross-sales').textContent = `৳${grossSales.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
        
        if (data.settlement) {
          const dmg = parseFloat(data.settlement.damage_amount) || 0;
          const exp = parseFloat(data.settlement.expense_amount) || 0;
          const expected = parseFloat(data.settlement.amount_expected) || 0;
          const submitted = parseFloat(data.settlement.amount_submitted) || 0;
          const diff = parseFloat(data.settlement.difference) || 0;
          const remarks = data.settlement.notes || 'No remarks provided.';

          document.getElementById('summary-damage').textContent = `৳${dmg.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
          document.getElementById('summary-expenses').textContent = `৳${exp.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
          document.getElementById('summary-expected').textContent = `৳${expected.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
          document.getElementById('summary-submitted').textContent = `৳${submitted.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
          
          const diffEl = document.getElementById('summary-difference');
          diffEl.textContent = `৳${diff.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
          if (diff < 0) {
            diffEl.className = 'text-lg font-extrabold font-mono text-red-500 mt-0.5';
          } else if (diff > 0) {
            diffEl.className = 'text-lg font-extrabold font-mono text-blue-600 mt-0.5';
          } else {
            diffEl.className = 'text-lg font-extrabold font-mono text-green-600 mt-0.5';
          }
          
          document.getElementById('summary-remarks').textContent = remarks;
        } else {
          document.getElementById('summary-damage').textContent = '৳0.00';
          document.getElementById('summary-expenses').textContent = '৳0.00';
          document.getElementById('summary-expected').textContent = '৳0.00';
          document.getElementById('summary-submitted').textContent = '৳0.00';
          document.getElementById('summary-difference').textContent = '৳0.00';
          document.getElementById('summary-difference').className = 'text-lg font-extrabold font-mono text-gray-500 mt-0.5';
          document.getElementById('summary-remarks').textContent = 'এই ডিসপ্যাচের সেটেলমেন্ট এখনো জমা/তৈরি হয়নি।';
        }
      } else {
        summaryCard.classList.add('hidden');
      }
    }

    // Render Table Rows
    function renderVanStockRows(products) {
      const container = document.getElementById('van-products-list');
      container.innerHTML = '';

      products.forEach(p => {
        const row = document.createElement('tr');
        row.className = 'van-product-row hover:bg-gray-50';
        row.dataset.name = p.product_name.toLowerCase();
        
        row.innerHTML = `
          <td class="font-bold text-gray-700 pl-4">
            ${p.product_name}
            <span class="block text-[9px] text-gray-400 font-normal mt-0.5">৳${p.selling_price.toFixed(0)}/pc (${p.pieces_per_box} pcs/box)</span>
          </td>
          <td class="text-right font-mono">${p.loaded.formatted} <span class="text-[9px] text-gray-400 font-normal">(${p.loaded.pieces} pcs)</span></td>
          <td class="text-right font-mono text-red-500">${p.returned.formatted} <span class="text-[9px] text-red-400 font-normal">(${p.returned.pieces} pcs)</span></td>
          <td class="text-right font-mono text-blue-700 font-bold">${p.sold.formatted} <span class="text-[9px] text-blue-600 font-normal">(${p.sold.pieces} pcs)</span></td>
          <td class="text-right font-mono font-bold text-[#2563eb] bg-blue-50/20 pr-4">৳${p.sold.value.toFixed(2)}</td>
        `;
        container.appendChild(row);
      });
    }

    // Filter Search List
    function filterVanStock() {
      const query = document.getElementById('van-search').value.trim().toLowerCase();
      const rows = document.querySelectorAll('.van-product-row');
      
      rows.forEach(row => {
        if (row.dataset.name.includes(query)) {
          row.classList.remove('hidden');
        } else {
          row.classList.add('hidden');
        }
      });
    }

    // Custom Pull-To-Refresh logic
    function initPullToRefresh() {
      let startY = 0;
      let currentY = 0;
      let isPulling = false;
      const threshold = 60; // px
      
      const ptrIndicator = document.createElement('div');
      ptrIndicator.id = 'ptr-indicator';
      ptrIndicator.className = 'w-full flex items-center justify-center bg-white border-b border-gray-200 overflow-hidden transition-all duration-200 ease-out shrink-0';
      ptrIndicator.style.height = '0px';
      ptrIndicator.innerHTML = `
        <div class="flex items-center gap-2 py-3 text-[#2563eb] font-bold text-xs">
          <i id="ptr-icon" class="fa-solid fa-arrows-rotate text-sm transition-transform duration-200"></i>
          <span id="ptr-text">রিফ্রেশ করতে নিচে টানুন...</span>
        </div>
      `;
      
      const mainContent = document.getElementById('main-content');
      mainContent.insertBefore(ptrIndicator, mainContent.firstChild);
      
      mainContent.addEventListener('touchstart', (e) => {
        if (mainContent.scrollTop === 0) {
          startY = e.touches[0].clientY;
          isPulling = true;
          ptrIndicator.classList.remove('transition-all', 'duration-200');
          document.getElementById('ptr-icon').classList.remove('animate-spin');
        }
      }, { passive: true });
      
      mainContent.addEventListener('touchmove', (e) => {
        if (!isPulling) return;
        
        currentY = e.touches[0].clientY;
        const diff = currentY - startY;
        
        if (diff > 0 && mainContent.scrollTop === 0) {
          if (e.cancelable) e.preventDefault();
          const height = Math.min(diff * 0.4, 80); // apply resistance
          ptrIndicator.style.height = height + 'px';
          
          const icon = document.getElementById('ptr-icon');
          const text = document.getElementById('ptr-text');
          
          icon.style.transform = `rotate(${height * 4}deg)`;
          
          if (height >= threshold) {
            text.textContent = 'ছেড়ে দিন রিফ্রেশ করতে...';
          } else {
            text.textContent = 'রিফ্রেশ করতে নিচে টানুন...';
          }
        } else {
          isPulling = false;
          ptrIndicator.classList.add('transition-all', 'duration-200');
          ptrIndicator.style.height = '0px';
        }
      }, { passive: false });
      
      mainContent.addEventListener('touchend', () => {
        if (!isPulling) return;
        isPulling = false;
        
        ptrIndicator.classList.add('transition-all', 'duration-200');
        const height = parseInt(ptrIndicator.style.height);
        
        if (height >= threshold) {
          ptrIndicator.style.height = '50px';
          document.getElementById('ptr-text').textContent = 'লোড হচ্ছে...';
          const icon = document.getElementById('ptr-icon');
          icon.style.transform = '';
          icon.classList.add('animate-spin');
          
          setTimeout(() => {
            window.location.reload();
          }, 800);
        } else {
          ptrIndicator.style.height = '0px';
        }
      });
    }

    // Logout
    function logout() {
      if (confirm('আপনি কি ডিএসআর শিট অ্যাপ থেকে লগআউট করতে চান?')) {
        window.location.href = '<?= rootPath() ?>/logout.php';
      }
    }
  </script>
</body>
</html>
