<?php
// dsr/index.php — Excel Sheet Responsive Mobile DSR SPA Shell
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('dsr');

$pdo = getDB();
$user_id = $_SESSION['user_id'];

// Get all active warehouses for checkout simulation list
$warehouses = $pdo->query('SELECT id, name FROM warehouses WHERE status=1 ORDER BY name')->fetchAll();
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

  <title>ডিএসআর শিট লেজার — হ্যাপি বাংলাদেশ</title>
  
  <!-- Premium Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Fira+Mono:wght@400;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- HTML5 QR Scanner -->
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  
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
    *, .btn, .stat-card, .form-input, .badge, .modal-box, table, tr, th, td, input, select, textarea, button, div, span, img {
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
    }

    /* Excel Scrollbar Styling */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: #f3f2f1;
    }
    ::-webkit-scrollbar-thumb {
      background: #cbd5e1;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: #a19f9d;
    }

    /* Spring-Active Animation */
    .btn-bounce {
      transition: transform 0.1s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .btn-bounce:active {
      transform: scale(0.95);
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
    /* Scale up tiny font sizes */
    .text-\[7px\] { font-size: 10px !important; }
    .text-\[8px\] { font-size: 11px !important; }
    .text-\[9px\] { font-size: 12px !important; }
    .text-\[10px\] { font-size: 13px !important; }
    .text-\[11px\] { font-size: 14px !important; }
    .text-xs { font-size: 14px !important; }
    .text-sm { font-size: 16px !important; }
    
    /* Scale tables cell padding and sizing */
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

    /* Scale general paddings of containers */
    .p-2 { padding: 0.75rem !important; }
    .p-3 { padding: 1rem !important; }
    .p-4 { padding: 1.25rem !important; }
    .py-1\.5 { padding-top: 0.6rem !important; padding-bottom: 0.6rem !important; }
    .py-2 { padding-top: 0.75rem !important; padding-bottom: 0.75rem !important; }
    .py-2\.5 { padding-top: 0.9rem !important; padding-bottom: 0.9rem !important; }
    .py-3 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
    .py-3\.5 { padding-top: 1.1rem !important; padding-bottom: 1.1rem !important; }
    
    /* Scale input fields */
    input[type="text"], input[type="number"], input[type="date"], select, textarea {
      padding-top: 0.6rem !important;
      padding-bottom: 0.6rem !important;
      font-size: 13px !important;
    }
    
    /* Scale quick-action buttons and submit buttons */
    .btn-bounce, button {
      font-size: 13px !important;
    }
  </style>
</head>
<body class="h-full flex flex-col justify-between overflow-hidden">

  <!-- ================= EXCEL STYLE HEADER ================= -->
  <header class="bg-[#2563eb] text-white px-4 py-3 flex items-center justify-between z-40 shrink-0 border-b border-[#1d4ed8]">
    <div class="flex items-center gap-3">
      <div class=" rounded-lg overflow-hidden w-8 h-8 bg-white flex items-center justify-center shadow-sm p-1">
        <img src="<?= rootPath() ?>/assets/img/logo/logo-icon-black.png" alt="Happy Bangladesh" class="w-full h-full object-contain" />
      </div>
      <div>
        <h1 class="text-sm font-extrabold tracking-tight font-mono">ডিএসআর প্যানেল</h1>
        <div class="flex items-center gap-1">
          <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-400"></span>
          <span class="text-[9px] text-blue-200 font-bold uppercase tracking-wider" id="header-subtitle">মোবাইল অ্যাপ</span>
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
    
    <!-- ==========================================
         TAB 1: HOME (EXCEL DASHBOARD)
         ========================================== -->
    <div id="tab-home" class="tab-pane space-y-4">
      
      <!-- Summary Cards Grid -->
      <div class="grid grid-cols-3 gap-2">
        <!-- Card 1: Delivery Ratio -->
        <div class="bg-white border border-[#cbd5e1] p-2 text-center">
          <div class="text-[7px] xs:text-[9px] text-gray-500 font-bold uppercase tracking-wider font-mono">ডেলিভারি_%</div>
          <div class="text-[11px] xs:text-xs font-black text-blue-600 mt-1 font-mono" id="card-delivery-ratio">0.0%</div>
        </div>
        <!-- কার্ড ২: বর্তমান ভ্যান মূল্য -->
        <div class="bg-white border border-[#cbd5e1] p-2 text-center">
          <div class="text-[7px] xs:text-[9px] text-gray-500 font-bold uppercase tracking-wider font-mono">ভ্যানের_টাকা</div>
          <div class="text-[11px] xs:text-xs font-black text-[#2563eb] mt-1 font-mono" id="card-van-value">৳0.00</div>
        </div>
        <!-- কার্ড ৩: মোট ডেলিভারি -->
        <div class="bg-white border border-[#cbd5e1] p-2 text-center">
          <div class="text-[7px] xs:text-[9px] text-gray-500 font-bold uppercase tracking-wider font-mono">মোট_বিক্রয়</div>
          <div class="text-[11px] xs:text-xs font-black text-emerald-600 mt-1 font-mono" id="card-total-delivered">৳0.00</div>
        </div>
      </div>

      <!-- Attendance status sheet card -->
      <div class="bg-white border border-[#cbd5e1] p-4 space-y-3" id="attendance-card">
        <div class="flex items-center justify-between border-b border-gray-100 pb-2 mb-2">
          <h3 class="font-bold text-xs text-gray-700 uppercase tracking-wide"><i class="fa-solid fa-calendar-check text-[#2563eb] mr-1.5"></i>হাজিরা</h3>
          <span class="text-[9px] bg-red-100 text-red-700 font-bold px-2 py-0.5" id="att-badge">অনুপস্থিত</span>
        </div>

        <p class="text-xs text-gray-500" id="att-status-text">হাজিরা চেক করা হচ্ছে...</p>

        <!-- Attendance Checked In view -->
        <div id="att-checked-in-view" class="hidden bg-[#dbeafe] border border-[#bfdbfe] p-3 flex items-center gap-3">
          <div class="w-6 h-6 rounded-full bg-[#2563eb] text-white flex items-center justify-center shrink-0 text-xs">
            <i class="fa-solid fa-check"></i>
          </div>
          <div>
            <div class="text-xs font-bold text-[#2563eb]">হাজির আছেন</div>
             <div class="text-[10px] text-gray-600" id="att-checked-time">চেক-ইন হয়েছে সকাল ১০:৩০ এ</div>
          </div>
        </div>

        <!-- Attendance Checked Out View -->
        <div id="att-checked-out-view" class="hidden space-y-3">
          <div class="bg-red-50 border border-red-200 p-3 flex items-center gap-3">
            <div class="w-6 h-6 rounded-full bg-red-600 text-white flex items-center justify-center shrink-0 text-xs animate-pulse">
              <i class="fa-solid fa-exclamation"></i>
            </div>
            <div>
              <div class="text-xs font-bold text-red-700">চেক-ইন দরকার</div>
              <div class="text-[10px] text-gray-600">আজ গুদামের QR কোড স্ক্যান করুন।</div>
            </div>
          </div>

          <div class="flex gap-2">
            <!-- Camera QR scan trigger -->
            <button onclick="openAttendanceScanner()" class="flex-1 bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-bold text-xs py-2.5 px-4 flex items-center justify-center gap-1.5 btn-bounce">
              <i class="fa-solid fa-camera"></i> QR স্ক্যান করুন
            </button>
          </div>

          <!-- Dev Mode / Simulation Dropdown -->
          <div class="border-t border-gray-200 pt-3">
            <div class="flex items-center justify-between mb-2">
              <label class="text-[9px] text-gray-500 font-bold uppercase tracking-wider">টেস্ট মোড</label>
              <span class="text-[8px] bg-gray-100 text-gray-500 font-bold px-1.5 py-0.5 border border-gray-200">টেস্ট</span>
            </div>
            <div class="flex gap-2">
              <select id="sim-warehouse-select" class="flex-1 bg-white border border-[#cbd5e1] text-xs text-gray-700 px-2 py-1.5 focus:outline-none focus:border-[#2563eb]">
                <option value="">গুদাম বেছে নিন</option>
                <?php foreach ($warehouses as $wh): ?>
                  <option value="<?= $wh['id'] ?>"><?= htmlspecialchars($wh['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button onclick="simulateCheckIn()" class="bg-white hover:bg-gray-50 border border-[#cbd5e1] text-[#2563eb] font-bold text-xs px-3 btn-bounce">
                চেক-ইন
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Attendance Camera Scanner Card (Initially hidden) -->
      <div id="attendance-scanner-card" class="hidden bg-white border border-[#cbd5e1] p-4 space-y-3 shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
          <h4 class="font-bold text-xs text-gray-700 flex items-center gap-1.5 uppercase tracking-wide">
            <i class="fa-solid fa-camera text-[#2563eb] animate-pulse"></i> ক্যামেরা
          </h4>
          <button onclick="closeAttendanceScanner()" class="text-xs text-red-600 font-bold hover:underline"><i class="fa-solid fa-times"></i> বন্ধ</button>
        </div>
        <div id="att-qr-reader" class="overflow-hidden bg-black aspect-square max-w-[280px] mx-auto border border-[#cbd5e1]"></div>
        <p class="text-[9px] text-center text-gray-500">QR কোড ধরুন ক্যামেরার সামনে</p>
      </div>

      <!-- Active Dispatch Card -->
      <div class="bg-white border border-[#cbd5e1] p-4 space-y-3" id="dispatch-card">
        <div class="flex items-center justify-between pb-2 border-b border-gray-200">
          <h3 class="font-bold text-xs text-gray-700 uppercase tracking-wide"><i class="fa-solid fa-truck text-[#2563eb] mr-1.5"></i>আজকের ডেলিভারি</h3>
          <span class="bg-gray-100 text-gray-600 text-[9px] font-bold px-2 py-0.5" id="disp-badge">ডিসপ্যাচ নেই</span>
        </div>

        <!-- No active dispatch placeholder -->
        <div id="disp-empty-view" class="text-center py-6 space-y-1">
          <i class="fa-solid fa-box-open text-2xl text-gray-300 block"></i>
          <p class="text-xs text-gray-500 font-bold">আজ ভ্যানে কোনো মাল নেই।</p>
          <p class="text-[9px] text-gray-400">ম্যানেজারকে মাল লোড করতে বলুন।</p>
        </div>

        <!-- Active dispatch content -->
        <div id="disp-active-view" class="hidden space-y-4">
          
          <!-- Excel Sheet Representation of Dispatch Info -->
          <div class="border border-[#cbd5e1]">
            <table class="excel-table">
              <thead>
                <tr>
                  <th class="excel-row-num">নং</th>
                  <th>বিষয়</th>
                  <th>তথ্য</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="excel-row-num">১</td>
                  <td class="bg-gray-50 font-bold text-gray-500 uppercase tracking-wider text-[9px]">ডিসপ্যাচ নং</td>
                  <td class="font-mono text-gray-800 font-bold" id="disp-id-label">#DISP-0000</td>
                </tr>
                <tr>
                  <td class="excel-row-num">২</td>
                  <td class="bg-gray-50 font-bold text-gray-500 uppercase tracking-wider text-[9px]">কোম্পানি</td>
                  <td class="font-bold text-gray-700" id="disp-company">Eggland Co.</td>
                </tr>
                <tr>
                  <td class="excel-row-num">৩</td>
                  <td class="bg-gray-50 font-bold text-gray-500 uppercase tracking-wider text-[9px]">SR এর নাম</td>
                  <td class="text-gray-700" id="disp-sr">Anwar Hossain</td>
                </tr>
                <tr>
                  <td class="excel-row-num">৪</td>
                  <td class="bg-gray-50 font-bold text-gray-500 uppercase tracking-wider text-[9px]">মালের দাম</td>
                  <td class="font-mono font-bold text-blue-600" id="disp-out-value">৳0.00</td>
                </tr>
                <tr>
                  <td class="excel-row-num">৫</td>
                  <td class="bg-gray-50 font-bold text-gray-500 uppercase tracking-wider text-[9px]">ফেরত টাকা</td>
                  <td class="font-mono font-bold text-red-600" id="disp-return-value">৳0.00</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex gap-2">
            <button onclick="location.href='stock.php'" class="flex-1 bg-white hover:bg-gray-50 text-gray-700 font-semibold text-xs py-2 px-3 flex items-center justify-center gap-1 border border-[#cbd5e1] btn-bounce">
              <i class="fa-solid fa-list-check text-[#2563eb]"></i> স্টক দেখুন
            </button>
            <button onclick="location.href='settlement.php'" class="flex-1 bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-bold text-xs py-2 px-3 flex items-center justify-center gap-1 btn-bounce">
              <i class="fa-solid fa-money-bill-transfer"></i> টাকা জমা
            </button>
          </div>
        </div>
      </div>

      <!-- Loaded Cargo Inventory Summary Table -->
      <div class="bg-white border border-[#cbd5e1] overflow-hidden hidden" id="home-van-cargo-card">
        <div class="px-4 py-2 bg-gray-100 border-b border-[#cbd5e1] flex justify-between items-center">
          <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider"><i class="fa-solid fa-boxes-stacked text-[#2563eb] mr-1.5"></i>ভ্যানের মাল</span>
          <span class="text-[10px] font-mono text-[#2563eb] font-bold" id="home-van-cargo-count">০টি পণ্য</span>
        </div>
        <div class="overflow-x-auto w-full">
          <table class="excel-table w-full">
            <thead>
              <tr class="bg-gray-100">
                <th class="text-left pl-4">পণ্য</th>
                <th class="text-right">পরিমাণ</th>
                <th class="text-right pr-4">মোট দাম</th>
              </tr>
            </thead>
            <tbody id="home-van-cargo-list">
              <!-- Grid rows rendered dynamically in JS -->
            </tbody>
          </table>
        </div>
      </div>

    </div>

  </main>

  <!-- ================= BOTTOM NAVIGATION TABS (EXCEL TABS STYLE) ================= -->
  <nav class="bg-gray-100 shrink-0 flex items-center justify-around border-t border-[#cbd5e1] z-40 select-none">
    <button onclick="location.href='index.php'" id="nav-home" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-r border-[#cbd5e1] border-b border-gray-100 btn-bounce tab-active">
      <i class="fa-solid fa-house-chimney text-xs"></i>
      <span>হোম</span>
    </button>
    <button onclick="location.href='stock.php'" id="nav-van" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-r border-[#cbd5e1] border-b border-gray-100 btn-bounce">
      <i class="fa-solid fa-table-cells text-xs"></i>
      <span>মাল স্টক</span>
    </button>
    <button onclick="location.href='settlement.php'" id="nav-settlement" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-b border-gray-100 btn-bounce">
      <i class="fa-solid fa-calculator text-xs"></i>
      <span>জমা টাকা</span>
    </button>
  </nav>

  <!-- ================= MOBILE TOAST SYSTEM ================= -->
  <div id="mob-toast" class="fixed top-4 left-1/2 -translate-x-1/2 pointer-events-none z-50 flex flex-col gap-2 w-11/12 max-w-sm"></div>

  <!-- JavaScript SPA App Logic -->
  <script>
    // Register Service Worker for PWA
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
          .then(registration => {
            console.log('ServiceWorker registration successful with scope: ', registration.scope);
          }, err => {
            console.log('ServiceWorker registration failed: ', err);
          });
      });
    }

    // App State Configuration
    const API_URL = '<?= rootPath() ?>/api/dsr_mobile.php';
    let dashboardData = null;
    let activeCameraScanner = null;

    // Initialize SPA
    window.addEventListener('DOMContentLoaded', () => {
      // Setup Real-time Clock
      setInterval(updateClock, 1000);
      updateClock();

      loadDashboard();
    });

    // Clock and Date Formatter
    function updateClock() {
      const now = new Date();
      const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
      const dateStr = now.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
      document.getElementById('live-time').textContent = timeStr;
      document.getElementById('live-date').textContent = dateStr;
    }

    // Modern AJAX Fetch Helper
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

    // Mobile Toast Controller
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
      
      // Animate entry
      setTimeout(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
      }, 50);

      // Auto dismiss
      setTimeout(() => {
        toast.classList.add('-translate-y-2', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
      }, 3500);
    }

    // ==========================================
    // DATA LOADERS & DOM WRITERS
    // ==========================================

    // Fetch Dashboard Data
    async function loadDashboard() {
      const url = API_URL + '?action=dashboard';
      const data = await apiCall(url);
      if (data.success) {
        dashboardData = data;

        // Write Summary Cards
        if (data.stats) {
          document.getElementById('card-delivery-ratio').textContent = `${data.stats.delivery_ratio.toFixed(1)}%`;
          document.getElementById('card-van-value').textContent = `৳${data.stats.current_van_value.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
          document.getElementById('card-total-delivered').textContent = `৳${data.stats.total_delivered_all_time.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
        }

        // Render Home Van Cargo Table
        const homeCargoCard = document.getElementById('home-van-cargo-card');
        const homeCargoList = document.getElementById('home-van-cargo-list');
        const homeCargoCount = document.getElementById('home-van-cargo-count');

        if (data.loaded_products && data.loaded_products.length > 0) {
          homeCargoCard.classList.remove('hidden');
          homeCargoCount.textContent = `${data.loaded_products.length}টি পণ্য লোড হয়েছে`;
          
          homeCargoList.innerHTML = '';
          data.loaded_products.forEach(p => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.innerHTML = `
              <td class="font-bold text-gray-700 pl-4">${p.product_name}</td>
              <td class="text-right font-mono">${p.qty_formatted} <span class="text-[9px] text-gray-400 font-normal">(${p.qty_pieces} pcs)</span></td>
              <td class="text-right font-mono font-bold text-[#2563eb] pr-4">৳${p.total_value.toFixed(2)}</td>
            `;
            homeCargoList.appendChild(row);
          });
        } else {
          homeCargoCard.classList.add('hidden');
        }

        // Write attendance card state
        const attBadge = document.getElementById('att-badge');
        const attStatusText = document.getElementById('att-status-text');

        if (data.attendance.checked_in) {
          document.getElementById('att-checked-in-view').classList.remove('hidden');
          document.getElementById('att-checked-time').textContent = `আজ ${data.attendance.time} এ চেক-ইন হয়েছে (${data.attendance.warehouse_name})`;
          document.getElementById('att-checked-out-view').classList.add('hidden');
          
          if (data.attendance.status === 'late') {
            attBadge.className = 'bg-yellow-100 text-yellow-700 text-[9px] font-bold px-2 py-0.5';
            attBadge.textContent = 'দেরি';
            attStatusText.textContent = `আজ ${data.attendance.time} এ দেরিতে হাজিরা নিশ্চিত হয়েছে।`;
          } else {
            // present
            attBadge.className = 'bg-[#dbeafe] text-[#2563eb] text-[9px] font-bold px-2 py-0.5';
            attBadge.textContent = 'উপস্থিত';
            attStatusText.textContent = `আজ ${data.attendance.time} এ হাজিরা নিশ্চিত হয়েছে।`;
          }
        } else {
          document.getElementById('att-checked-in-view').classList.add('hidden');
          document.getElementById('att-checked-out-view').classList.remove('hidden');
          
          if (data.attendance.status === 'pending') {
            attBadge.className = 'bg-amber-100 text-amber-700 text-[9px] font-bold px-2 py-0.5';
            attBadge.textContent = 'অপেক্ষমান';
            attStatusText.textContent = "আজকের হাজিরা এখনও নিশ্চিত করা হয়নি (অপেক্ষমান)।";
          } else {
            // absent
            attBadge.className = 'bg-red-100 text-red-700 text-[9px] font-bold px-2 py-0.5';
            attBadge.textContent = 'অনুপস্থিত';
            attStatusText.textContent = "আপনি আজ গুদামে হাজিরা নিশ্চিত করেননি (অনুপস্থিত)!";
          }
        }

        // Write Active Dispatch Card
        const dispBadge = document.getElementById('disp-badge');
        const dispIdLabel = document.getElementById('disp-id-label');
        const emptyView = document.getElementById('disp-empty-view');
        const activeView = document.getElementById('disp-active-view');

        if (data.active_dispatch) {
          const ad = data.active_dispatch;
          
          dispBadge.className = 'bg-blue-100 text-blue-800 text-[9px] font-bold px-2 py-0.5';
          
          if (ad.status === 'loaded') {
            dispBadge.textContent = 'ডেলিভারিতে আছে';
          } else if (ad.status === 'delivered') {
            dispBadge.textContent = 'সেটেলমেন্ট_মুলতুবি';
          } else {
            dispBadge.textContent = ad.status.toUpperCase();
          }

          dispIdLabel.textContent = `#DISP-${String(ad.id).padStart(4, '0')}`;
          
          document.getElementById('disp-company').textContent = ad.company_name;
          document.getElementById('disp-sr').textContent = ad.sr_name;
          document.getElementById('disp-out-value').textContent = `৳${ad.out_value.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
          document.getElementById('disp-return-value').textContent = `৳${ad.return_value.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;

          emptyView.classList.add('hidden');
          activeView.classList.remove('hidden');
        } else {
          dispBadge.className = 'bg-gray-150 text-gray-500 text-[9px] font-bold px-2 py-0.5';
          dispBadge.textContent = 'কোনো ডিসপ্যাচ নেই';
          dispIdLabel.textContent = 'প্রযোজ্য নয়';
          
          emptyView.classList.remove('hidden');
          activeView.classList.add('hidden');
        }
      }
    }

    // ==========================================
    // ATTENDANCE QR CODE SCANNING FLOW
    // ==========================================

    // Helper to get current location coordinates (lat, lng) with IP fallback
    function getCurrentLocation() {
      return new Promise((resolve) => {
        if (!navigator.geolocation) {
          console.warn("Geolocation not supported. Trying IP fallback...");
          fetchIPLocation().then(resolve);
          return;
        }
        navigator.geolocation.getCurrentPosition(
          (position) => {
            resolve({
              latitude: position.coords.latitude,
              longitude: position.coords.longitude
            });
          },
          (error) => {
            console.warn("Geolocation error:", error.message, ". Trying IP fallback...");
            fetchIPLocation().then(resolve);
          },
          { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
      });
    }

    async function fetchIPLocation() {
      try {
        const res = await fetch('https://ipapi.co/json/');
        if (res.ok) {
          const data = await res.json();
          if (data.latitude && data.longitude) {
            return { latitude: data.latitude, longitude: data.longitude };
          }
        }
      } catch (e) {
        console.warn("IP geolocation fallback 1 failed:", e);
      }
      try {
        const res = await fetch('https://ip-api.com/json/');
        if (res.ok) {
          const data = await res.json();
          if (data.lat && data.lon) {
            return { latitude: data.lat, longitude: data.lon };
          }
        }
      } catch (e) {
        console.warn("IP geolocation fallback 2 failed:", e);
      }
      return { latitude: null, longitude: null };
    }

    // Open Attendance Scanner
    function openAttendanceScanner() {
      document.getElementById('attendance-scanner-card').classList.remove('hidden');
      
      const reader = document.getElementById('att-qr-reader');
      reader.innerHTML = '';
      
      activeCameraScanner = new Html5Qrcode('att-qr-reader');
      activeCameraScanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 220, height: 220 } },
        async (decodedText) => {
          showToast('QR কোড সফলভাবে পড়া হয়েছে। অবস্থান নির্ধারণ করা হচ্ছে...', 'info');
          closeAttendanceScanner();

          const coords = await getCurrentLocation();

          // Submit check-in
          const res = await apiCall(API_URL + '?action=mark_attendance', 'POST', { 
            qr_code: decodedText,
            latitude: coords.latitude,
            longitude: coords.longitude
          });
          if (res.success) {
            showToast(res.message, 'success');
            loadDashboard();
          } else {
            showToast(res.message || 'চেক-ইন ব্যর্থ হয়েছে।', 'error');
          }
        },
        (errorMessage) => { /* ignore */ }
      ).catch(err => {
        console.warn('Camera start error:', err);
        showToast('ক্যামেরা চালু করা যায়নি। ড্রপডাউন থেকে মক চেক-ইন ব্যবহার করুন।', 'warning');
        closeAttendanceScanner();
      });
    }

    // Close Scanner
    function closeAttendanceScanner() {
      if (activeCameraScanner) {
        activeCameraScanner.stop().then(() => {
          activeCameraScanner = null;
        }).catch(() => {
          activeCameraScanner = null;
        });
      }
      document.getElementById('attendance-scanner-card').classList.add('hidden');
    }

    // Simulated Check In (Mock Sandbox)
    async function simulateCheckIn() {
      const val = document.getElementById('sim-warehouse-select').value;
      if (!val) {
        showToast('মক চেক-ইনের জন্য একটি গুদাম নির্বাচন করুন', 'warning');
        return;
      }

      showToast('অবস্থান নির্ধারণ করা হচ্ছে...', 'info');
      const coords = await getCurrentLocation();
      
      const res = await apiCall(API_URL + '?action=mark_attendance', 'POST', { 
        qr_code: `happy_warehouse_${val}`,
        latitude: coords.latitude,
        longitude: coords.longitude
      });
      if (res.success) {
        showToast(res.message, 'success');
        loadDashboard();
      } else {
        showToast(res.message, 'error');
      }
    }

    // Global Logout Routine
    function logout() {
      if (confirm('আপনি কি ডিএসআর শিট অ্যাপ থেকে লগআউট করতে চান?')) {
        window.location.href = '<?= rootPath() ?>/logout.php';
      }
    }

    // ==========================================
    // MOBILE PULL TO REFRESH SYSTEM
    // ==========================================
    let startY = 0;
    let currentY = 0;
    let isPulling = false;
    const threshold = 60; // drag distance in px to trigger refresh

    const ptrIndicator = document.createElement('div');
    ptrIndicator.id = 'ptr-indicator';
    ptrIndicator.className = 'w-full flex items-center justify-center bg-gray-50 border-b border-gray-200 overflow-hidden transition-all duration-200 ease-out shrink-0';
    ptrIndicator.style.height = '0px';
    ptrIndicator.innerHTML = `
      <div class="flex items-center gap-2 py-3 text-[#2563eb] font-bold text-xs">
        <i id="ptr-icon" class="fa-solid fa-arrows-rotate text-sm transition-transform duration-200"></i>
        <span id="ptr-text">রিফ্রেশ করতে নিচে টানুন...</span>
      </div>
    `;

    const mainContent = document.getElementById('main-content');
    if (mainContent) {
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
          const height = Math.min(diff * 0.4, 80);
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
  </script>
</body>
</html>
