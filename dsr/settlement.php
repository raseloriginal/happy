<?php
// dsr/settlement.php — Excel Sheet Responsive Mobile DSR Cash Settlement Page
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

  <title>ডিএসআর জমা টাকা — হ্যাপি বাংলাদেশ</title>
  
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
          <span class="text-[9px] text-blue-200 font-bold uppercase tracking-wider" id="header-subtitle">জমা টাকা</span>
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
    
    <!-- Settlement Date Selector -->
    <div class="bg-white border border-[#cbd5e1] p-3 shadow-sm rounded">
      <label class="block text-[9px] font-bold text-gray-400 uppercase mb-1 tracking-wider">তারিখ</label>
      <div class="relative">
        <i class="fa-solid fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-[#2563eb] text-xs"></i>
        <input type="date" id="settlement-date-select" onchange="loadSettlementForDate()" class="w-full bg-[#f8fafc] border border-[#cbd5e1] py-1.5 pl-8 pr-2 text-xs focus:outline-none focus:border-[#2563eb] text-gray-800 font-bold font-mono rounded" />
      </div>
    </div>

    <!-- Settlement Spreadsheet Table -->
    <div class="bg-white border border-[#cbd5e1] p-4 space-y-3">
      <div class="pb-2 border-b border-gray-200 flex justify-between items-center mb-3">
        <h3 class="font-bold text-xs text-gray-700 uppercase tracking-wide"><i class="fa-solid fa-calculator text-[#2563eb] mr-1.5"></i>হিসাব শিট</h3>
        <span class="text-[9px] bg-[#dbeafe] text-[#2563eb] px-2 py-0.5 font-bold uppercase">হিসাব চেক</span>
      </div>

      <!-- Excel Formula Table -->
      <div class="border border-[#cbd5e1]">
        <table class="excel-table">
          <thead>
            <tr class="bg-gray-100">
              <th class="text-left pl-4">হিসাবের ধাপ</th>
              <th class="text-right pr-4">পরিমাণ</th>
            </tr>
          </thead>
          <tbody id="settlement-formula-tbody">
            <tr>
              <td class="pl-4">মালের মোট দাম</td>
              <td class="text-right font-mono font-bold text-blue-600 pr-4" id="formula-out">৳0.00</td>
            </tr>
            <tr>
              <td class="pl-4">বাদ ফেরত</td>
              <td class="text-right font-mono font-bold text-red-500 pr-4" id="formula-return">- ৳0.00</td>
            </tr>
            <tr>
              <td class="pl-4 flex items-center gap-1.5">
                বাদ ক্ষতি (৳)
              </td>
              <td class="text-right p-1.5 pr-4">
                <input type="number" id="input-damage" value="0" step="any" min="0" oninput="calcSettlementExpected()" class="w-24 bg-white border border-[#cbd5e1] py-1 px-2 text-right text-xs focus:outline-none focus:border-[#2563eb] text-red-600 font-bold font-mono disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed" placeholder="০.০০" />
              </td>
            </tr>
            <tr>
              <td class="pl-4 flex items-center gap-1.5">
                বাদ খরচ (৳)
              </td>
              <td class="text-right p-1.5 pr-4">
                <input type="number" id="input-expense" value="0" step="any" min="0" oninput="calcSettlementExpected()" class="w-24 bg-white border border-[#cbd5e1] py-1 px-2 text-right text-xs focus:outline-none focus:border-[#2563eb] text-orange-600 font-bold font-mono disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed" placeholder="০.০০" />
              </td>
            </tr>
            <!-- SR Commission Rows Will Be Injected Here via JS -->
            <tr class="bg-blue-50" id="expected-row">
              <td class="font-bold text-blue-800 pl-4">জমা দেওয়ার টাকা</td>
              <td class="text-right font-mono font-bold text-[#2563eb] text-xs pr-4" id="formula-expected">৳0.00</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Banknote Counter Sheet Table -->
    <div class="bg-white border border-[#cbd5e1] overflow-hidden">
      <div class="px-4 py-2 bg-gray-100 border-b border-[#cbd5e1] flex justify-between items-center">
        <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">নোট গণনা</span>
        <span class="text-[9px] text-[#2563eb] font-mono font-bold uppercase tracking-widest">টাকা গোনা</span>
      </div>

      <div class="overflow-x-auto w-full">
        <table class="excel-table w-full">
          <thead>
            <tr class="bg-gray-100">
              <th class="excel-row-num">নং</th>
              <th class="text-left pl-4">নোট</th>
              <th class="text-center" style="width: 140px;">সংখ্যা (টি)</th>
              <th class="text-right pr-4" style="width: 110px;">মোট (৳)</th>
            </tr>
          </thead>
          <tbody id="banknotes-grid-table">
            <!-- Bangladeshi banknotes generated dynamically in Excel rows -->
          </tbody>
        </table>
      </div>
    </div>

    <!-- Notes Counter Summary and Discrepancy Card -->
    <div class="bg-white border border-[#cbd5e1] p-4 space-y-4">
      
      <!-- Sheet Totals Comparison Table -->
      <div class="border border-[#cbd5e1]">
        <table class="excel-table">
          <thead>
            <tr class="bg-gray-100">
              <th class="text-left pl-4">হিসাব মিলান</th>
              <th class="text-right pr-4">টাকা (৳)</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="pl-4">জমার লক্ষ্য (৳)</td>
              <td class="text-right font-mono font-bold text-gray-600 pr-4" id="audit-summary-expected">৳0.00</td>
            </tr>
            <tr>
              <td class="font-bold text-gray-700 pl-4">গোনা টাকা (৳)</td>
              <td class="text-right font-mono font-bold text-[#2563eb] text-xs pr-4" id="counted-total">৳0.00</td>
            </tr>
            <tr class="bg-gray-50">
              <td class="font-bold text-gray-700 pl-4">মিলান</td>
              <td class="text-right p-1 pr-4" id="discrepancy-badge-container">
                <span class="bg-gray-200 text-gray-700 text-[10px] font-black px-2 py-0.5">গণনা নেই</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Remarks -->
      <div>
        <label class="block text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">মন্তব্য</label>
        <textarea id="settlement-remarks" placeholder="মন্তব্য লিখুন..." rows="2" class="w-full bg-white border border-[#cbd5e1] p-2 text-xs focus:outline-none focus:border-[#2563eb] text-gray-700 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"></textarea>
      </div>

      <!-- জমা বোতাম -->
      <button onclick="submitCashSettlement()" id="submit-settle-btn" class="w-full bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-extrabold text-xs py-3 px-4 flex items-center justify-center gap-1.5 btn-bounce">
        <i class="fa-solid fa-circle-check"></i> টাকা জমা দিন
      </button>
    </div>
  </main>

  <!-- ================= BOTTOM NAVIGATION TABS (EXCEL TABS STYLE) ================= -->
  <nav class="bg-gray-100 shrink-0 flex items-center justify-around border-t border-[#cbd5e1] z-40 select-none">
    <a href="index.php" id="nav-home" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-r border-[#cbd5e1] border-b border-gray-100 btn-bounce">
      <i class="fa-solid fa-house-chimney text-xs"></i>
      <span>হোম</span>
    </a>
    <a href="stock.php" id="nav-van" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-r border-[#cbd5e1] border-b border-gray-100 btn-bounce">
      <i class="fa-solid fa-table-cells text-xs"></i>
      <span>মাল স্টক</span>
    </a>
    <a href="settlement.php" id="nav-settlement" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-b border-gray-100 btn-bounce tab-active">
      <i class="fa-solid fa-calculator text-xs"></i>
      <span>জমা টাকা</span>
    </a>
  </nav>

  <!-- ================= MOBILE TOAST SYSTEM ================= -->
  <div id="mob-toast" class="fixed top-4 left-1/2 -translate-x-1/2 pointer-events-none z-50 flex flex-col gap-2 w-11/12 max-w-sm"></div>

  <!-- JavaScript App Logic -->
  <script>
    const API_URL = '<?= rootPath() ?>/api/dsr_mobile.php';
    let dashboardData = null;

    // Currency Notes Data Matrix
    const currencyNotes = [
      { key: '1000', label: '১০০০ ৳ নোট', val: 1000, img: '1000tk.jpg' },
      { key: '500', label: '৫০০ ৳ নোট', val: 500, img: '500tk.jpg' },
      { key: '200', label: '২০০ ৳ নোট', val: 200, img: '200tk.png' },
      { key: '100', label: '১০০ ৳ নোট', val: 100, img: '100tk.jpg' },
      { key: '50', label: '৫০ ৳ নোট', val: 50, img: '50tk.jpg' },
      { key: '20', label: '২০ ৳ নোট', val: 20, img: '20tk.jpg' },
      { key: '10', label: '১০ ৳ নোট', val: 10, img: '10tk.jpg' }
    ];

    // Banknote quantity state holder
    let noteQuantities = { '1000':0, '500':0, '200':0, '100':0, '50':0, '20':0, '10':0 };

    // Initialize Page
    window.addEventListener('DOMContentLoaded', () => {
      // Setup Clock
      setInterval(updateClock, 1000);
      updateClock();

      // Setup default date picker to local date (today)
      const tzOffset = (new Date()).getTimezoneOffset() * 60000;
      const localISOTime = (new Date(Date.now() - tzOffset)).toISOString().slice(0, 10);
      const dateSelect = document.getElementById('settlement-date-select');
      if (dateSelect) {
        dateSelect.value = localISOTime;
      }

      // Generate visual Banknote Grid
      renderBanknotes();

      // Load initial settlement data
      loadSettlementForDate();

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

    // Fetch and Load dashboard details for active dispatch
    async function loadDashboard(dateVal = '') {
      const url = API_URL + '?action=dashboard' + (dateVal ? '&date=' + dateVal : '');
      const data = await apiCall(url);
      if (data.success) {
        dashboardData = data;

        // Write Active Dispatch Card values
        const formulaOut = document.getElementById('formula-out');
        const formulaReturn = document.getElementById('formula-return');

        if (data.active_dispatch) {
          const ad = data.active_dispatch;
          formulaOut.textContent = `৳${ad.out_value.toFixed(2)}`;
          formulaReturn.textContent = `- ৳${ad.return_value.toFixed(2)}`;

          // Pre-populate damages if they already submitted settlement
          if (ad.settlement) {
            document.getElementById('input-damage').value = ad.settlement.damage_amount || 0;
            document.getElementById('input-expense').value = ad.settlement.expense_amount || 0;
            document.getElementById('settlement-remarks').value = ad.settlement.notes || '';

            // Load quantities from database notes counter
            if (ad.settlement.notes_details) {
              noteQuantities = { ...noteQuantities, ...ad.settlement.notes_details };
              renderBanknotes();
            }
          } else {
            document.getElementById('input-damage').value = 0;
            document.getElementById('input-expense').value = 0;
            document.getElementById('settlement-remarks').value = '';
            noteQuantities = { '1000':0, '500':0, '200':0, '100':0, '50':0, '20':0, '10':0 };
            renderBanknotes();
          }

          const isApproved = ad.status === 'settled' || (ad.settlement && ad.settlement.status === 'approved');
          document.getElementById('input-damage').disabled = isApproved;
          document.getElementById('input-expense').disabled = isApproved;
          document.getElementById('settlement-remarks').disabled = isApproved;

          // Render SR Commissions
          const formulaTbody = document.querySelector('#settlement-formula-tbody');
          document.querySelectorAll('.sr-commission-row').forEach(e => e.remove());

          if (ad.assigned_srs && ad.assigned_srs.length > 0) {
            ad.assigned_srs.forEach(sr => {
              const srVal = (ad.settlement && ad.settlement.commission_details && ad.settlement.commission_details[sr.sr_id]) ? ad.settlement.commission_details[sr.sr_id] : 0;
              const tr = document.createElement('tr');
              tr.className = 'sr-commission-row';
              tr.innerHTML = `
                <td class="pl-4 flex items-center gap-1.5 py-1.5">
                  <div class="flex flex-col">
                    <span class="text-gray-700">যোগ ওভার/কমিশন (৳)</span>
                    <span class="text-[9px] text-gray-500 font-bold font-mono">${sr.sr_name} - ${sr.company_name}</span>
                  </div>
                </td>
                <td class="text-right p-1.5 pr-4">
                  <input type="number" data-srid="${sr.sr_id}" value="${srVal}" step="any" min="0" oninput="calcSettlementExpected()" class="sr-commission-input w-24 bg-white border border-[#cbd5e1] py-1 px-2 text-right text-xs focus:outline-none focus:border-[#2563eb] text-emerald-600 font-bold font-mono disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed" placeholder="০.০০" ${isApproved ? 'disabled' : ''} />
                </td>
              `;
              const expectedRow = document.getElementById('expected-row');
              formulaTbody.insertBefore(tr, expectedRow);
            });
          }

          const submitBtn = document.getElementById('submit-settle-btn');
          if (submitBtn) {
            if (isApproved) {
              submitBtn.disabled = true;
              submitBtn.innerHTML = '<i class="fa-solid fa-lock text-sm"></i> সেটেলমেন্ট অনুমোদিত ও বন্ধ';
              submitBtn.className = 'w-full bg-gray-400 text-white font-extrabold text-xs py-3 px-4 flex items-center justify-center gap-1.5 cursor-not-allowed';
            } else {
              submitBtn.disabled = false;
              submitBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> নগদ সেটেলমেন্ট রিপোর্ট জমা দিন';
              submitBtn.className = 'w-full bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-extrabold text-xs py-3 px-4 flex items-center justify-center gap-1.5 btn-bounce';
            }
          }
        } else {
          formulaOut.textContent = '৳0.00';
          formulaReturn.textContent = '- ৳0.00';
          document.getElementById('input-damage').value = 0;
          document.getElementById('input-expense').value = 0;
          document.querySelectorAll('.sr-commission-row').forEach(e => e.remove());
          document.getElementById('settlement-remarks').value = '';
          noteQuantities = { '1000':0, '500':0, '200':0, '100':0, '50':0, '20':0, '10':0 };
          renderBanknotes();

          document.getElementById('input-damage').disabled = true;
          document.getElementById('input-expense').disabled = true;
          document.getElementById('settlement-remarks').disabled = true;

          const submitBtn = document.getElementById('submit-settle-btn');
          if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> সেটেলমেন্টের জন্য কোনো সক্রিয় ডিসপ্যাচ নেই';
            submitBtn.className = 'w-full bg-gray-400 text-white font-extrabold text-xs py-3 px-4 flex items-center justify-center gap-1.5 cursor-not-allowed';
          }
        }
      }
    }

    // Load Settlement data dynamically for a selected date
    async function loadSettlementForDate() {
      const dateVal = document.getElementById('settlement-date-select').value;
      await loadDashboard(dateVal);
      calcSettlementExpected();
    }

    // Render Banknotes Table Grid
    function renderBanknotes() {
      const container = document.getElementById('banknotes-grid-table');
      container.innerHTML = '';

      const isEditable = dashboardData && dashboardData.active_dispatch && 
                         dashboardData.active_dispatch.status !== 'settled' && 
                         !(dashboardData.active_dispatch.settlement && dashboardData.active_dispatch.settlement.status === 'approved');

      let idx = 1;
      currencyNotes.forEach(n => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        
        row.innerHTML = `
          <td class="excel-row-num">${idx++}</td>
          <td class="font-bold text-gray-700">
            <div class="flex items-center gap-3 py-1.5">
              ${n.img ? `
                <div class="w-20 h-12 bg-gray-100 border border-gray-300 overflow-hidden shrink-0 shadow-sm flex items-center justify-center">
                  <img src="<?= rootPath() ?>/assets/img/extra/${n.img}" alt="${n.label}" class="w-full h-full object-cover" />
                </div>
              ` : `
                <div class="w-20 h-12 bg-gray-50 border border-dashed border-gray-200 overflow-hidden shrink-0 flex items-center justify-center text-[10px] text-gray-400 font-mono font-bold">
                  NO_IMG
                </div>
              `}
              <div>
                <div class="text-[13px] text-gray-800 font-extrabold leading-tight">${n.label}</div>
                <div class="text-[10px] text-gray-400 font-mono mt-0.5">Denomination: ৳${n.val}</div>
              </div>
            </div>
          </td>
          
          <td class="text-center p-1">
            <div class="inline-flex items-center border border-[#cbd5e1] bg-white">
              <button type="button" onclick="adjustNote('${n.key}', -1)" ${isEditable ? '' : 'disabled'} class="w-9 h-9 hover:bg-gray-100 text-gray-600 font-bold text-sm flex items-center justify-center shrink-0 border-r border-[#cbd5e1] btn-bounce disabled:text-gray-300 disabled:cursor-not-allowed disabled:bg-gray-50">-</button>
              <input type="number" id="note-input-${n.key}" value="${noteQuantities[n.key] || 0}" min="0" oninput="setNoteDirect('${n.key}', this.value)" ${isEditable ? '' : 'disabled'} class="w-14 bg-transparent text-center font-bold font-mono text-sm text-gray-800 outline-none focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none disabled:text-gray-400 disabled:cursor-not-allowed disabled:bg-gray-50" />
              <button type="button" onclick="adjustNote('${n.key}', 1)" ${isEditable ? '' : 'disabled'} class="w-9 h-9 hover:bg-gray-100 text-gray-600 font-bold text-sm flex items-center justify-center shrink-0 border-l border-[#cbd5e1] btn-bounce disabled:text-gray-300 disabled:cursor-not-allowed disabled:bg-gray-50">+</button>
            </div>
          </td>
          
          <td class="text-right font-mono font-bold text-indigo-600 text-sm bg-indigo-50/10" id="note-sub-${n.key}">৳0.00</td>
        `;
        container.appendChild(row);
        
        setNoteDirect(n.key, noteQuantities[n.key] || 0, false);
      });
    }

    // Adjust note count
    function adjustNote(key, delta) {
      let currentVal = parseInt(document.getElementById('note-input-' + key).value) || 0;
      let newVal = Math.max(currentVal + delta, 0);
      
      document.getElementById('note-input-' + key).value = newVal;
      setNoteDirect(key, newVal);
    }

    // Set note directly
    function setNoteDirect(key, value, triggerTotal = true) {
      let qty = parseInt(value) || 0;
      if (qty < 0) qty = 0;
      
      noteQuantities[key] = qty;
      
      const denom = parseInt(key);
      const subtotal = qty * denom;
      document.getElementById('note-sub-' + key).textContent = `৳${subtotal.toFixed(2)}`;

      if (triggerTotal) {
        calcSettlementExpected();
      }
    }

    // Recalculate expectation & totals
    function calcSettlementExpected() {
      if (!dashboardData || !dashboardData.active_dispatch) return;

      const ad = dashboardData.active_dispatch;
      const damage = parseFloat(document.getElementById('input-damage').value) || 0;
      const expense = parseFloat(document.getElementById('input-expense').value) || 0;
      
      let commission = 0;
      document.querySelectorAll('.sr-commission-input').forEach(input => {
        commission += parseFloat(input.value) || 0;
      });

      const out_val = parseFloat(ad.out_value) || 0;
      const return_val = parseFloat(ad.return_value) || 0;
      
      const expectedSubmit = out_val - return_val - damage - expense + commission;
      
      document.getElementById('formula-expected').textContent = `৳${expectedSubmit.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
      document.getElementById('audit-summary-expected').textContent = `৳${expectedSubmit.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;

      let totalCounted = 0;
      for (const [denomStr, qty] of Object.entries(noteQuantities)) {
        const denom = parseInt(denomStr);
        totalCounted += qty * denom;
      }

      document.getElementById('counted-total').textContent = `৳${totalCounted.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;

      const diff = totalCounted - expectedSubmit;
      const badgeContainer = document.getElementById('discrepancy-badge-container');

      if (totalCounted === 0) {
        badgeContainer.innerHTML = `
          <span class="bg-gray-100 text-gray-500 text-[10px] font-black px-2 py-0.5">খালি গণনা</span>
        `;
      } else if (Math.abs(diff) < 0.01) {
        badgeContainer.innerHTML = `
          <span class="bg-blue-100 text-blue-800 text-[10px] font-black px-2 py-0.5">সঠিক মিল</span>
        `;
      } else if (diff < 0) {
        badgeContainer.innerHTML = `
          <span class="bg-red-100 text-red-800 text-[10px] font-black px-2 py-0.5">ঘাটতি: ৳${Math.abs(diff).toFixed(0)}</span>
        `;
      } else {
        badgeContainer.innerHTML = `
          <span class="bg-blue-100 text-blue-800 text-[10px] font-black px-2 py-0.5">উদ্বৃত্ত: ৳${diff.toFixed(0)}</span>
        `;
      }
    }

    // Submit Settlement Report to Backend
    async function submitCashSettlement() {
      if (!dashboardData || !dashboardData.active_dispatch) {
        showToast('সেটেলমেন্টের জন্য কোনো সক্রিয় ডিসপ্যাচ লোড নেই।', 'warning');
        return;
      }

      const active_dispatch_id = dashboardData.active_dispatch.id;
      const damage = parseFloat(document.getElementById('input-damage').value) || 0;
      const expense = parseFloat(document.getElementById('input-expense').value) || 0;
      
      let commission_details = {};
      document.querySelectorAll('.sr-commission-input').forEach(input => {
        const srId = input.dataset.srid;
        commission_details[srId] = parseFloat(input.value) || 0;
      });

      let totalCounted = 0;
      for (const [denomStr, qty] of Object.entries(noteQuantities)) {
        const denom = parseInt(denomStr);
        totalCounted += qty * denom;
      }

      if (totalCounted <= 0) {
        showToast('জমা দেওয়ার আগে নগদ নোটের পরিমাণ গণনা করুন।', 'warning');
        return;
      }

      const remarks = document.getElementById('settlement-remarks').value.trim();

      const btn = document.getElementById('submit-settle-btn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> জমা হচ্ছে...';

      const res = await apiCall(API_URL + '?action=submit_settlement', 'POST', {
        dispatch_id: active_dispatch_id,
        damage_amount: damage,
        expense_amount: expense,
        commission_details: commission_details,
        amount_submitted: totalCounted,
        notes_details: noteQuantities,
        notes_text: remarks
      });

      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-circle-check mr-1"></i> নগদ সেটেলমেন্ট রিপোর্ট জমা দিন';

      if (res.success) {
        showToast(res.message, 'success');
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      } else {
        showToast(res.message || 'নগদ সেটেলমেন্ট জমা দিতে ত্রুটি হয়েছে', 'error');
      }
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
