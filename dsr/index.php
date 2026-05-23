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
            <button onclick="switchTab('van')" class="flex-1 bg-white hover:bg-gray-50 text-gray-700 font-semibold text-xs py-2 px-3 flex items-center justify-center gap-1 border border-[#cbd5e1] btn-bounce">
              <i class="fa-solid fa-list-check text-[#2563eb]"></i> স্টক দেখুন
            </button>
            <button onclick="switchTab('settlement')" class="flex-1 bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-bold text-xs py-2 px-3 flex items-center justify-center gap-1 btn-bounce">
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

    <!-- ==========================================
         TAB 2: VAN STOCK (CARGO SPREADSHEET LEDGER)
         ========================================== -->
    <div id="tab-van" class="tab-pane hidden space-y-4">

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
              <!-- Grid rows rendered dynamically in JS -->
              <tr>
                <td colspan="5" class="text-center py-6 text-gray-400 italic px-4">এই তারিখে মাল নেই।</td>
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
          <!-- ডান কলামের তথ্য -->
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
    </div>

    <!-- ==========================================
         TAB 3: CASH SETTLEMENT (SPREADSHEET SETTLEMENT CALCULATOR)
         ========================================== -->
    <div id="tab-settlement" class="tab-pane hidden space-y-4">

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
    </div>
  </main>

  <!-- ================= BOTTOM NAVIGATION TABS (EXCEL TABS STYLE) ================= -->
  <nav class="bg-gray-100 shrink-0 flex items-center justify-around border-t border-[#cbd5e1] z-40 select-none">
    <button onclick="switchTab('home')" id="nav-home" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-r border-[#cbd5e1] border-b border-gray-100 btn-bounce tab-active">
      <i class="fa-solid fa-house-chimney text-xs"></i>
      <span>হোম</span>
    </button>
    <button onclick="switchTab('van')" id="nav-van" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-r border-[#cbd5e1] border-b border-gray-100 btn-bounce">
      <i class="fa-solid fa-table-cells text-xs"></i>
      <span>মাল স্টক</span>
    </button>
    <button onclick="switchTab('settlement')" id="nav-settlement" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-b border-gray-100 btn-bounce">
      <i class="fa-solid fa-calculator text-xs"></i>
      <span>জমা টাকা</span>
    </button>
  </nav>

  <!-- ================= MOBILE TOAST SYSTEM ================= -->
  <div id="mob-toast" class="fixed top-4 left-1/2 -translate-x-1/2 pointer-events-none z-50 flex flex-col gap-2 w-11/12 max-w-sm"></div>

  <!-- JavaScript SPA App Logic -->
  <script>
    // App State Configuration
    const API_URL = '<?= rootPath() ?>/api/dsr_mobile.php';
    let currentTab = 'home';
    let dashboardData = null;
    let vanStockData = null;
    let activeCameraScanner = null;

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

    // Initialize SPA
    window.addEventListener('DOMContentLoaded', () => {
      // Setup Real-time Clock
      setInterval(updateClock, 1000);
      updateClock();

      // Setup default date picker to local ISO date (today)
      const tzOffset = (new Date()).getTimezoneOffset() * 60000;
      const localISOTime = (new Date(Date.now() - tzOffset)).toISOString().slice(0, 10);
      const dateSelect = document.getElementById('van-date-select');
      if (dateSelect) {
        dateSelect.value = localISOTime;
      }
      const settleDateSelect = document.getElementById('settlement-date-select');
      if (settleDateSelect) {
        settleDateSelect.value = localISOTime;
      }

      // Check URL parameters for tab redirects (support legacy queries)
      const urlParams = new URLSearchParams(window.location.search);
      const tabParam = urlParams.get('tab');
      if (tabParam && ['home', 'van', 'settlement'].includes(tabParam)) {
        currentTab = tabParam;
      }

      // Generate visual Banknote Grid
      renderBanknotes();

      // Fetch initial data based on active tab
      if (currentTab === 'van') {
        document.getElementById('nav-home').classList.remove('tab-active');
        document.getElementById('tab-home').classList.add('hidden');
        document.getElementById('nav-van').classList.add('tab-active');
        document.getElementById('tab-van').classList.remove('hidden');
        loadVanStock();
      } else if (currentTab === 'settlement') {
        document.getElementById('nav-home').classList.remove('tab-active');
        document.getElementById('tab-home').classList.add('hidden');
        document.getElementById('nav-settlement').classList.add('tab-active');
        document.getElementById('tab-settlement').classList.remove('hidden');
        const dVal = document.getElementById('settlement-date-select').value;
        loadDashboard(dVal).then(() => calcSettlementExpected());
      } else {
        loadDashboard();
      }
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

    // Switch Tabs Flow
    function switchTab(tabId) {
      // Clear legacy param
      if (tabId === 'expenses') tabId = 'home'; // expenses is removed

      if (currentTab === tabId) return;

      // Close open scanner if switching tabs
      closeAttendanceScanner();

      // Deactivate current tab
      document.getElementById('nav-' + currentTab).classList.remove('tab-active');
      document.getElementById('tab-' + currentTab).classList.add('hidden');

      // Activate new tab
      document.getElementById('nav-' + tabId).classList.add('tab-active');
      document.getElementById('tab-' + tabId).classList.remove('hidden');

      currentTab = tabId;

      if (tabId === 'home') {
        loadDashboard();
      } else if (tabId === 'van') {
        loadVanStock();
      } else if (tabId === 'settlement') {
        const dateVal = document.getElementById('settlement-date-select').value;
        loadDashboard(dateVal).then(() => calcSettlementExpected());
      }
    }

    // ==========================================
    // DATA LOADERS & DOM WRITERS
    // ==========================================

    // Fetch Dashboard Data
    async function loadDashboard(dateVal = '') {
      const url = API_URL + '?action=dashboard' + (dateVal ? '&date=' + dateVal : '');
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

          // Populate settlement formula variables directly
          document.getElementById('formula-out').textContent = `৳${ad.out_value.toFixed(2)}`;
          document.getElementById('formula-return').textContent = `- ৳${ad.return_value.toFixed(2)}`;

            // Pre-populate damages if they already submitted settlement
            if (ad.settlement) {
              document.getElementById('input-damage').value = ad.settlement.damage_amount || 0;
              document.getElementById('input-expense').value = ad.settlement.expense_amount || 0;
              document.getElementById('settlement-remarks').value = ad.settlement.notes || '';

            // Load quantities from database notes counter
            if (ad.settlement.notes_details) {
              noteQuantities = { ...noteQuantities, ...ad.settlement.notes_details };
              // Rerender banknote counters to match updated quantities
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
            // first remove all old commission rows
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
          dispBadge.className = 'bg-gray-150 text-gray-500 text-[9px] font-bold px-2 py-0.5';
          dispBadge.textContent = 'কোনো ডিসপ্যাচ নেই';
          dispIdLabel.textContent = 'প্রযোজ্য নয়';
          
          emptyView.classList.remove('hidden');
          activeView.classList.add('hidden');

          // Clear formula
          document.getElementById('formula-out').textContent = '৳0.00';
          document.getElementById('formula-return').textContent = '- ৳0.00';
          document.getElementById('input-damage').value = 0;
          document.getElementById('input-expense').value = 0;
          document.querySelectorAll('.sr-commission-row').forEach(e => e.remove());
          document.getElementById('settlement-remarks').value = '';
          noteQuantities = { '1000':0, '500':0, '200':0, '100':0, '50':0, '20':0, '10':0 };
          renderBanknotes();

          // Disable inputs and submit button as there is no active dispatch
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

    // Fetch and Render Van Stock List
    // Fetch and Render Van Stock List
    async function loadVanStock() {
      const dateVal = document.getElementById('van-date-select').value;
      const url = API_URL + '?action=van_stock' + (dateVal ? '&date=' + dateVal : '');
      const data = await apiCall(url);
      const container = document.getElementById('van-products-list');
      
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
          // No settlement submitted yet
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

    // Render Van Stock Rows DOM as Excel Spreadsheet Table rows
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

    // Search Van Stock Filter
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

    // Load Settlement data dynamically for a selected date
    async function loadSettlementForDate() {
      const dateVal = document.getElementById('settlement-date-select').value;
      await loadDashboard(dateVal);
      calcSettlementExpected();
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

    // ==========================================
    // CASH SETTLEMENT & NOTE COUNTING LOGIC
    // ==========================================

    // RENDER BANKNOTE CARDS DYNAMICALLY IN EXCEL TABLE
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
        
        // Initial subtotal trigger
        setNoteDirect(n.key, noteQuantities[n.key] || 0, false);
      });
    }

    // Adjust Note Count with + and - Buttons
    function adjustNote(key, delta) {
      let currentVal = parseInt(document.getElementById('note-input-' + key).value) || 0;
      let newVal = Math.max(currentVal + delta, 0);
      
      document.getElementById('note-input-' + key).value = newVal;
      setNoteDirect(key, newVal);
    }

    // Direct Note Count Setting
    function setNoteDirect(key, value, triggerTotal = true) {
      let qty = parseInt(value) || 0;
      if (qty < 0) qty = 0;
      
      noteQuantities[key] = qty;
      
      // Update Subtotal on Card
      const denom = parseInt(key);
      const subtotal = qty * denom;
      document.getElementById('note-sub-' + key).textContent = `৳${subtotal.toFixed(2)}`;

      // Update grand totals
      if (triggerTotal) {
        calcSettlementExpected();
      }
    }

    // Calculate final expected submission amount & counted notes sum
    function calcSettlementExpected() {
      if (!dashboardData || !dashboardData.active_dispatch) return;

      const ad = dashboardData.active_dispatch;
      const damage = parseFloat(document.getElementById('input-damage').value) || 0;
      const expense = parseFloat(document.getElementById('input-expense').value) || 0;
      
      let commission = 0;
      document.querySelectorAll('.sr-commission-input').forEach(input => {
        commission += parseFloat(input.value) || 0;
      });

      // Expected Submissions Formula: Out - Return - Damage - Expense + Commission
      const out_val = parseFloat(ad.out_value) || 0;
      const return_val = parseFloat(ad.return_value) || 0;
      
      const expectedSubmit = out_val - return_val - damage - expense + commission;
      
      // Render expected submit
      document.getElementById('formula-expected').textContent = `৳${expectedSubmit.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
      document.getElementById('audit-summary-expected').textContent = `৳${expectedSubmit.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;

      // Calculate total notes counted cash sum
      let totalCounted = 0;
      for (const [denomStr, qty] of Object.entries(noteQuantities)) {
        const denom = parseInt(denomStr);
        totalCounted += qty * denom;
      }

      // Render total notes sum
      document.getElementById('counted-total').textContent = `৳${totalCounted.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;

      // Render Discrepancy Status Badges
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

      // Calculate total notes counted sum
      let totalCounted = 0;
      for (const [denomStr, qty] of Object.entries(noteQuantities)) {
        const denom = parseInt(denomStr);
        totalCounted += qty * denom;
      }

      if (totalCounted <= 0) {
        showToast('জমা দেওয়ার আগে নগদ নোটের পরিমাণ গণনা করুন।', 'warning');
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
        showToast(res.message || 'নগদ সেটেলমেন্ট জমা দিতে ত্রুটি হয়েছে', 'error');
      }
    }

    // Global Logout Routine
    function logout() {
      if (confirm('আপনি কি ডিএসআর শিট অ্যাপ থেকে লগআউট করতে চান?')) {
        window.location.href = '<?= rootPath() ?>/logout.php';
      }
    }
  </script>
</body>
</html>
