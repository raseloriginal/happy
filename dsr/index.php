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
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
  <title>DSR Sheet Ledger — Happy Bangladesh</title>
  
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
      --excel-green: #107c41;
      --excel-green-dark: #0a5c30;
      --excel-green-light: #e2f0d9;
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
      color: var(--excel-green) !important;
      background-color: #ffffff;
      border-top: 3px solid var(--excel-green);
      margin-top: -3px;
      border-bottom: 1px solid transparent !important;
      z-index: 10;
    }
  </style>
</head>
<body class="h-full flex flex-col justify-between overflow-hidden">

  <!-- ================= EXCEL STYLE HEADER ================= -->
  <header class="bg-[#107c41] text-white px-4 py-3 flex items-center justify-between z-40 shrink-0 border-b border-[#0a5c30]">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 bg-white text-[#107c41] flex items-center justify-center font-black text-base shadow-sm">
        X
      </div>
      <div>
        <h1 class="text-sm font-extrabold tracking-tight font-mono">DSR_SHEET.XLSX</h1>
        <div class="flex items-center gap-1">
          <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-400"></span>
          <span class="text-[9px] text-green-200 font-bold uppercase tracking-wider" id="header-subtitle">Mobile Grid Mode</span>
        </div>
      </div>
    </div>
    
    <div class="flex items-center gap-2">
      <!-- Digital Clock -->
      <div class="text-right mr-1.5 hidden xs:block">
        <div class="text-[10px] font-bold font-mono text-green-100" id="live-time">00:00:00 PM</div>
        <div class="text-[8px] text-green-200 font-bold uppercase tracking-wider" id="live-date">May 18, 2026</div>
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
      
      <!-- Formula Bar UI -->
      <div class="bg-white border border-[#cbd5e1] flex items-center text-xs font-mono select-none">
        <div class="bg-gray-100 px-3 py-1.5 border-r border-[#cbd5e1] text-gray-500 font-bold">fx</div>
        <input type="text" readonly id="excel-formula-bar" value="=WELCOME_DSR_REPRESENTATIVE()" class="flex-1 bg-transparent px-3 py-1.5 focus:outline-none text-gray-700" />
      </div>
      
      <!-- Greeting Sheet Block -->
      <div class="bg-white border border-[#cbd5e1] p-4 space-y-2">
        <div class="flex justify-between items-start border-b border-gray-200 pb-2">
          <div>
            <span class="text-[9px] text-[#107c41] font-mono font-bold">CELL_A1: GREETINGS</span>
            <h2 class="text-lg font-black text-gray-800" id="user-greeting">Assalamu Alaikum!</h2>
          </div>
          <span class="bg-[#e2f0d9] text-[#107c41] text-[9px] font-bold px-2 py-0.5" id="user-subtext">Loading...</span>
        </div>
        <p class="text-xs text-gray-500 leading-relaxed">
          Welcome to the **DSR Operations Ledger**. All numbers, attendance logs, and cash counts are processed dynamically as a live mobile-responsive Excel sheet.
        </p>
      </div>

      <!-- Attendance status sheet card -->
      <div class="bg-white border border-[#cbd5e1] p-4 space-y-3" id="attendance-card">
        <div class="flex items-center justify-between border-b border-gray-100 pb-2 mb-2">
          <h3 class="font-bold text-xs text-gray-700 uppercase tracking-wide"><i class="fa-solid fa-calendar-check text-[#107c41] mr-1.5"></i>Attendance Verification</h3>
          <span class="text-[9px] bg-red-100 text-red-700 font-bold px-2 py-0.5" id="att-badge">Absent</span>
        </div>

        <p class="text-xs text-gray-500" id="att-status-text">Checking attendance logs...</p>

        <!-- Attendance Checked In view -->
        <div id="att-checked-in-view" class="hidden bg-[#e2f0d9] border border-[#a9d18e] p-3 flex items-center gap-3">
          <div class="w-6 h-6 rounded-full bg-[#107c41] text-white flex items-center justify-center shrink-0 text-xs">
            <i class="fa-solid fa-check"></i>
          </div>
          <div>
            <div class="text-xs font-bold text-[#107c41]">Checked In Present</div>
            <div class="text-[10px] text-gray-600" id="att-checked-time">Checked-in at 10:30 AM</div>
          </div>
        </div>

        <!-- Attendance Checked Out View -->
        <div id="att-checked-out-view" class="hidden space-y-3">
          <div class="bg-red-50 border border-red-200 p-3 flex items-center gap-3">
            <div class="w-6 h-6 rounded-full bg-red-600 text-white flex items-center justify-center shrink-0 text-xs animate-pulse">
              <i class="fa-solid fa-exclamation"></i>
            </div>
            <div>
              <div class="text-xs font-bold text-red-700">Check-in Required</div>
              <div class="text-[10px] text-gray-600">Scan warehouse QR to verify field presence today.</div>
            </div>
          </div>

          <div class="flex gap-2">
            <!-- Camera QR scan trigger -->
            <button onclick="openAttendanceScanner()" class="flex-1 bg-[#107c41] hover:bg-[#0a5c30] text-white font-bold text-xs py-2.5 px-4 flex items-center justify-center gap-1.5 btn-bounce">
              <i class="fa-solid fa-camera"></i> Scan Warehouse QR
            </button>
          </div>

          <!-- Dev Mode / Simulation Dropdown -->
          <div class="border-t border-gray-200 pt-3">
            <div class="flex items-center justify-between mb-2">
              <label class="text-[9px] text-gray-500 font-bold uppercase tracking-wider">Simulation Sandbox</label>
              <span class="text-[8px] bg-gray-100 text-gray-500 font-bold px-1.5 py-0.5 border border-gray-200">DEV MODE</span>
            </div>
            <div class="flex gap-2">
              <select id="sim-warehouse-select" class="flex-1 bg-white border border-[#cbd5e1] text-xs text-gray-700 px-2 py-1.5 focus:outline-none focus:border-[#107c41]">
                <option value="">Choose Warehouse to Mock</option>
                <?php foreach ($warehouses as $wh): ?>
                  <option value="<?= $wh['id'] ?>"><?= htmlspecialchars($wh['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button onclick="simulateCheckIn()" class="bg-white hover:bg-gray-50 border border-[#cbd5e1] text-[#107c41] font-bold text-xs px-3 btn-bounce">
                Check-in
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Attendance Camera Scanner Card (Initially hidden) -->
      <div id="attendance-scanner-card" class="hidden bg-white border border-[#cbd5e1] p-4 space-y-3 shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
          <h4 class="font-bold text-xs text-gray-700 flex items-center gap-1.5 uppercase tracking-wide">
            <i class="fa-solid fa-camera text-[#107c41] animate-pulse"></i> Camera Scanner
          </h4>
          <button onclick="closeAttendanceScanner()" class="text-xs text-red-600 font-bold hover:underline"><i class="fa-solid fa-times"></i> Close</button>
        </div>
        <div id="att-qr-reader" class="overflow-hidden bg-black aspect-square max-w-[280px] mx-auto border border-[#cbd5e1]"></div>
        <p class="text-[9px] text-center text-gray-500">Hold QR code in front of the lens</p>
      </div>

      <!-- Active Dispatch Card -->
      <div class="bg-white border border-[#cbd5e1] p-4 space-y-3" id="dispatch-card">
        <div class="flex items-center justify-between pb-2 border-b border-gray-200">
          <h3 class="font-bold text-xs text-gray-700 uppercase tracking-wide"><i class="fa-solid fa-truck text-[#107c41] mr-1.5"></i>Active Deliveries Load</h3>
          <span class="bg-gray-100 text-gray-600 text-[9px] font-bold px-2 py-0.5" id="disp-badge">No Dispatch</span>
        </div>

        <!-- No active dispatch placeholder -->
        <div id="disp-empty-view" class="text-center py-6 space-y-1">
          <i class="fa-solid fa-box-open text-2xl text-gray-300 block"></i>
          <p class="text-xs text-gray-500 font-bold">No active dispatches on your van today.</p>
          <p class="text-[9px] text-gray-400">Ask your manager to load orders to your driver profile.</p>
        </div>

        <!-- Active dispatch content -->
        <div id="disp-active-view" class="hidden space-y-4">
          
          <!-- Excel Sheet Representation of Dispatch Info -->
          <div class="border border-[#cbd5e1]">
            <table class="excel-table">
              <thead>
                <tr>
                  <th class="excel-row-num">Row</th>
                  <th>Cell: Key</th>
                  <th>Value</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="excel-row-num">1</td>
                  <td class="bg-gray-50 font-bold text-gray-500 uppercase tracking-wider text-[9px]">A: Dispatch ID</td>
                  <td class="font-mono text-gray-800 font-bold" id="disp-id-label">#DISP-0000</td>
                </tr>
                <tr>
                  <td class="excel-row-num">2</td>
                  <td class="bg-gray-50 font-bold text-gray-500 uppercase tracking-wider text-[9px]">B: Company Name</td>
                  <td class="font-bold text-gray-700" id="disp-company">Eggland Co.</td>
                </tr>
                <tr>
                  <td class="excel-row-num">3</td>
                  <td class="bg-gray-50 font-bold text-gray-500 uppercase tracking-wider text-[9px]">C: Sales Rep (SR)</td>
                  <td class="text-gray-700" id="disp-sr">Anwar Hossain</td>
                </tr>
                <tr>
                  <td class="excel-row-num">4</td>
                  <td class="bg-gray-50 font-bold text-gray-500 uppercase tracking-wider text-[9px]">D: Loaded Load Value</td>
                  <td class="font-mono font-bold text-blue-600" id="disp-out-value">৳0.00</td>
                </tr>
                <tr>
                  <td class="excel-row-num">5</td>
                  <td class="bg-gray-50 font-bold text-gray-500 uppercase tracking-wider text-[9px]">E: Returned Value</td>
                  <td class="font-mono font-bold text-red-600" id="disp-return-value">৳0.00</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex gap-2">
            <button onclick="switchTab('van')" class="flex-1 bg-white hover:bg-gray-50 text-gray-700 font-semibold text-xs py-2 px-3 flex items-center justify-center gap-1 border border-[#cbd5e1] btn-bounce">
              <i class="fa-solid fa-list-check text-[#107c41]"></i> View Cargo Stock
            </button>
            <button onclick="switchTab('settlement')" class="flex-1 bg-[#107c41] hover:bg-[#0a5c30] text-white font-bold text-xs py-2 px-3 flex items-center justify-center gap-1 btn-bounce">
              <i class="fa-solid fa-money-bill-transfer"></i> Cash Settlement
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ==========================================
         TAB 2: VAN STOCK (CARGO SPREADSHEET LEDGER)
         ========================================== -->
    <div id="tab-van" class="tab-pane hidden space-y-4">
      
      <!-- Formula Bar UI -->
      <div class="bg-white border border-[#cbd5e1] flex items-center text-xs font-mono select-none">
        <div class="bg-gray-100 px-3 py-1.5 border-r border-[#cbd5e1] text-gray-500 font-bold">fx</div>
        <input type="text" readonly value="=ACTIVE_VAN_STOCKS_LEDGER()" class="flex-1 bg-transparent px-3 py-1.5 focus:outline-none text-gray-700" />
      </div>

      <!-- Search Input -->
      <div class="relative">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
        <input type="text" id="van-search" placeholder="Search loaded products in sheet..." oninput="filterVanStock()" class="w-full bg-white border border-[#cbd5e1] py-2 pl-8 pr-4 text-xs focus:outline-none focus:border-[#107c41] text-gray-800" />
      </div>

      <!-- Van Products Spreadsheet table -->
      <div class="bg-white border border-[#cbd5e1] overflow-hidden">
        <div class="px-4 py-2.5 bg-gray-100 border-b border-[#cbd5e1] flex justify-between items-center">
          <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Sheet: Van Cargo Inventory</span>
          <span class="text-[10px] font-mono text-[#107c41] font-bold" id="van-products-count">0 items loaded</span>
        </div>

        <div class="overflow-x-auto w-full">
          <table class="excel-table w-full" id="van-stock-table">
            <thead>
              <tr class="bg-gray-100">
                <th class="excel-row-num">Row</th>
                <th>A: Loaded Product</th>
                <th class="text-right">B: Out Qty</th>
                <th class="text-right">C: Returns</th>
                <th class="text-right">D: Net Sold</th>
                <th class="text-right">E: Net Value</th>
              </tr>
            </thead>
            <tbody id="van-products-list">
              <!-- Grid rows rendered dynamically in JS -->
              <tr>
                <td class="excel-row-num">1</td>
                <td colspan="5" class="text-center py-6 text-gray-400 italic">No products are currently loaded on your van.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ==========================================
         TAB 3: CASH SETTLEMENT (SPREADSHEET SETTLEMENT CALCULATOR)
         ========================================== -->
    <div id="tab-settlement" class="tab-pane hidden space-y-4">
      
      <!-- Formula Bar UI -->
      <div class="bg-white border border-[#cbd5e1] flex items-center text-xs font-mono select-none">
        <div class="bg-gray-100 px-3 py-1.5 border-r border-[#cbd5e1] text-gray-500 font-bold">fx</div>
        <input type="text" readonly value="=SUM(OUT_LOAD) - SUM(RETURNS) - SUM(DAMAGES)" class="flex-1 bg-transparent px-3 py-1.5 focus:outline-none text-gray-700" />
      </div>

      <!-- Settlement Spreadsheet Table -->
      <div class="bg-white border border-[#cbd5e1] p-4 space-y-3">
        <div class="pb-2 border-b border-gray-200 flex justify-between items-center mb-3">
          <h3 class="font-bold text-xs text-gray-700 uppercase tracking-wide"><i class="fa-solid fa-calculator text-[#107c41] mr-1.5"></i>Settlement Ledger Sheet</h3>
          <span class="text-[9px] bg-[#e2f0d9] text-[#107c41] px-2 py-0.5 font-bold uppercase">Dispatch audit</span>
        </div>

        <!-- Excel Formula Table -->
        <div class="border border-[#cbd5e1]">
          <table class="excel-table">
            <thead>
              <tr class="bg-gray-100">
                <th class="excel-row-num">Row</th>
                <th>Cell Formula Variable</th>
                <th class="text-right">Audit Value</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="excel-row-num">1</td>
                <td>A: Van Out Load Value</td>
                <td class="text-right font-mono font-bold text-blue-600" id="formula-out">৳0.00</td>
              </tr>
              <tr>
                <td class="excel-row-num">2</td>
                <td>B: Less Returns Value</td>
                <td class="text-right font-mono font-bold text-red-500" id="formula-return">- ৳0.00</td>
              </tr>
              <tr>
                <td class="excel-row-num">3</td>
                <td class="flex items-center gap-1.5">
                  C: Deduct Damage Amount (৳)
                </td>
                <td class="text-right p-1.5">
                  <input type="number" id="input-damage" value="0" step="any" min="0" oninput="calcSettlementExpected()" class="w-24 bg-white border border-[#cbd5e1] py-1 px-2 text-right text-xs focus:outline-none focus:border-[#107c41] text-red-600 font-bold font-mono" placeholder="0.00" />
                </td>
              </tr>
              <tr class="bg-emerald-50">
                <td class="excel-row-num">4</td>
                <td class="font-bold text-emerald-800">D: expected Net Payable submit (=A-B-C)</td>
                <td class="text-right font-mono font-bold text-[#107c41] text-xs" id="formula-expected">৳0.00</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Banknote Counter Sheet Table -->
      <div class="bg-white border border-[#cbd5e1] overflow-hidden">
        <div class="px-4 py-2 bg-gray-100 border-b border-[#cbd5e1] flex justify-between items-center">
          <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Sheet: Bangladeshi Banknote Counter</span>
          <span class="text-[9px] text-[#107c41] font-mono font-bold uppercase tracking-widest">BDT Counting</span>
        </div>

        <div class="overflow-x-auto w-full">
          <table class="excel-table w-full">
            <thead>
              <tr class="bg-gray-100">
                <th class="excel-row-num">Row</th>
                <th>A: Banknote Denomination</th>
                <th class="text-center" style="width: 140px;">B: Quantities Counted (টি)</th>
                <th class="text-right" style="width: 110px;">C: Subtotal (৳)</th>
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
                <th class="excel-row-num">Row</th>
                <th>Audit Check Matrix</th>
                <th class="text-right">Value (৳)</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="excel-row-num">1</td>
                <td>Total Expected Submission Amount (৳)</td>
                <td class="text-right font-mono font-bold text-gray-600" id="summary-expected">৳0.00</td>
              </tr>
              <tr>
                <td class="excel-row-num">2</td>
                <td class="font-bold text-gray-700">Total Cash Counted (৳)</td>
                <td class="text-right font-mono font-bold text-[#107c41] text-xs" id="counted-total">৳0.00</td>
              </tr>
              <tr class="bg-gray-50">
                <td class="excel-row-num">3</td>
                <td class="font-bold text-gray-700">Discrepancy Status</td>
                <td class="text-right p-1" id="discrepancy-badge-container">
                  <span class="bg-gray-200 text-gray-700 text-[10px] font-black px-2 py-0.5">Empty Counter</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Remarks -->
        <div>
          <label class="block text-[9px] text-gray-500 font-bold uppercase tracking-wider mb-1">Remarks / Settlement Notes</label>
          <textarea id="settlement-remarks" placeholder="Enter remarks about discrepancies or notes for the manager..." rows="2" class="w-full bg-white border border-[#cbd5e1] p-2 text-xs focus:outline-none focus:border-[#107c41] text-gray-700"></textarea>
        </div>

        <!-- Submit Button -->
        <button onclick="submitCashSettlement()" id="submit-settle-btn" class="w-full bg-[#107c41] hover:bg-[#0a5c30] text-white font-extrabold text-xs py-3 px-4 flex items-center justify-center gap-1.5 btn-bounce">
          <i class="fa-solid fa-circle-check"></i> Submit Cash Settlement Report
        </button>
      </div>
    </div>
  </main>

  <!-- ================= BOTTOM NAVIGATION TABS (EXCEL TABS STYLE) ================= -->
  <nav class="bg-gray-100 shrink-0 flex items-center justify-around border-t border-[#cbd5e1] z-40 select-none">
    <button onclick="switchTab('home')" id="nav-home" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-r border-[#cbd5e1] border-b border-gray-100 btn-bounce tab-active">
      <i class="fa-solid fa-house-chimney text-xs"></i>
      <span>Home_Sheet</span>
    </button>
    <button onclick="switchTab('van')" id="nav-van" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-r border-[#cbd5e1] border-b border-gray-100 btn-bounce">
      <i class="fa-solid fa-table-cells text-xs"></i>
      <span>Cargo_Sheet</span>
    </button>
    <button onclick="switchTab('settlement')" id="nav-settlement" class="flex items-center justify-center gap-2 text-gray-600 py-3.5 flex-1 font-mono text-[11px] font-bold border-b border-gray-100 btn-bounce">
      <i class="fa-solid fa-calculator text-xs"></i>
      <span>Cash_Settlement</span>
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
      { key: '1000', label: '১০০০ ৳ নোট', val: 1000 },
      { key: '500', label: '৫০০ ৳ নোট', val: 500 },
      { key: '200', label: '২০০ ৳ নোট', val: 200 },
      { key: '100', label: '১০০ ৳ নোট', val: 100 },
      { key: '50', label: '৫০ ৳ নোট', val: 50 },
      { key: '20', label: '২০ ৳ নোট', val: 20 },
      { key: '10', label: '১০ ৳ নোট', val: 10 },
      { key: '5', label: '৫ ৳ নোট', val: 5 },
      { key: '2', label: '২ ৳ নোট', val: 2 },
      { key: '1', label: '১ ৳ নোট', val: 1 }
    ];

    // Banknote quantity state holder
    let noteQuantities = { '1000':0, '500':0, '200':0, '100':0, '50':0, '20':0, '10':0, '5':0, '2':0, '1':0 };

    // Initialize SPA
    window.addEventListener('DOMContentLoaded', () => {
      // Setup Real-time Clock
      setInterval(updateClock, 1000);
      updateClock();

      // Check URL parameters for tab redirects (support legacy queries)
      const urlParams = new URLSearchParams(window.location.search);
      const tabParam = urlParams.get('tab');
      if (tabParam && ['home', 'van', 'settlement'].includes(tabParam)) {
        currentTab = tabParam;
      }

      // Generate visual Banknote Grid
      renderBanknotes();

      // Fetch dashboard data
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
        showToast('Internet connection problem. Please try again.', 'error');
        console.error('API Err:', err);
        return { success: false, message: 'API connection failed' };
      }
    }

    // Mobile Toast Controller
    function showToast(msg, type = 'success') {
      const container = document.getElementById('mob-toast');
      const toast = document.createElement('div');
      
      const theme = {
        success: 'bg-[#e2f0d9] border-[#107c41] text-[#107c41]',
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

      // Update Formula Bar text
      const formulaBar = document.getElementById('excel-formula-bar');
      if (tabId === 'home') {
        formulaBar.value = "=WELCOME_DSR_REPRESENTATIVE()";
        loadDashboard();
      } else if (tabId === 'van') {
        formulaBar.value = "=ACTIVE_VAN_STOCKS_LEDGER()";
        loadVanStock();
      } else if (tabId === 'settlement') {
        formulaBar.value = "=SUM(OUT_LOAD) - SUM(RETURNS) - SUM(DAMAGES)";
        loadDashboard().then(() => calcSettlementExpected());
      }
    }

    // ==========================================
    // DATA LOADERS & DOM WRITERS
    // ==========================================

    // Fetch Dashboard Data
    async function loadDashboard() {
      const data = await apiCall(API_URL + '?action=dashboard');
      if (data.success) {
        dashboardData = data;
        
        // Write profile details
        document.getElementById('user-greeting').textContent = `Assalamu Alaikum, ${data.profile.name}!`;
        document.getElementById('user-subtext').textContent = `Warehouse: ${data.profile.warehouse_name}`;

        // Write attendance card state
        const attBadge = document.getElementById('att-badge');
        const attStatusText = document.getElementById('att-status-text');

        if (data.attendance.checked_in) {
          attBadge.className = 'bg-[#e2f0d9] text-[#107c41] text-[9px] font-bold px-2 py-0.5';
          attBadge.textContent = 'Present';
          attStatusText.textContent = `Attendance checked-in at ${data.attendance.time} today.`;
          
          document.getElementById('att-checked-in-view').classList.remove('hidden');
          document.getElementById('att-checked-time').textContent = `Checked-in today at ${data.attendance.time} (${data.attendance.warehouse_name})`;
          document.getElementById('att-checked-out-view').classList.add('hidden');
        } else {
          attBadge.className = 'bg-red-100 text-red-700 text-[9px] font-bold px-2 py-0.5';
          attBadge.textContent = 'Absent';
          attStatusText.textContent = "You have not completed daily warehouse attendance registration today!";

          document.getElementById('att-checked-in-view').classList.add('hidden');
          document.getElementById('att-checked-out-view').classList.remove('hidden');
        }

        // Write Active Dispatch Card
        const dispBadge = document.getElementById('disp-badge');
        const dispIdLabel = document.getElementById('disp-id-label');
        const emptyView = document.getElementById('disp-empty-view');
        const activeView = document.getElementById('disp-active-view');

        if (data.active_dispatch) {
          const ad = data.active_dispatch;
          
          dispBadge.className = 'bg-green-100 text-green-800 text-[9px] font-bold px-2 py-0.5';
          
          if (ad.status === 'loaded') {
            dispBadge.textContent = 'ON DELIVERIES';
          } else if (ad.status === 'delivered') {
            dispBadge.textContent = 'SETTLED_PENDING';
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
            document.getElementById('input-damage').value = ad.settlement.damage_amount;
            document.getElementById('settlement-remarks').value = ad.settlement.notes;

            // Load quantities from database notes counter
            if (ad.settlement.notes_details) {
              noteQuantities = { ...noteQuantities, ...ad.settlement.notes_details };
              // Rerender banknote counters to match updated quantities
              renderBanknotes();
            }
          }

        } else {
          dispBadge.className = 'bg-gray-150 text-gray-500 text-[9px] font-bold px-2 py-0.5';
          dispBadge.textContent = 'No Dispatch';
          dispIdLabel.textContent = 'N/A';
          
          emptyView.classList.remove('hidden');
          activeView.classList.add('hidden');

          // Clear formula
          document.getElementById('formula-out').textContent = '৳0.00';
          document.getElementById('formula-return').textContent = '- ৳0.00';
        }
      }
    }

    // Fetch and Render Van Stock List
    async function loadVanStock() {
      const data = await apiCall(API_URL + '?action=van_stock');
      const container = document.getElementById('van-products-list');
      
      if (!data.success) {
        container.innerHTML = `
          <tr>
            <td class="excel-row-num">1</td>
            <td colspan="5" class="text-center py-6 text-red-500 italic">Failed to load cargo inventory ledger</td>
          </tr>
        `;
        return;
      }

      vanStockData = data;
      const countEl = document.getElementById('van-products-count');
      countEl.textContent = `${data.products ? data.products.length : 0} items loaded`;

      if (!data.products || data.products.length === 0) {
        container.innerHTML = `
          <tr>
            <td class="excel-row-num">1</td>
            <td colspan="5" class="text-center py-6 text-gray-400 italic">No products are currently loaded on your van.</td>
          </tr>
        `;
        return;
      }

      renderVanStockRows(data.products);
    }

    // Render Van Stock Rows DOM as Excel Spreadsheet Table rows
    function renderVanStockRows(products) {
      const container = document.getElementById('van-products-list');
      container.innerHTML = '';

      let idx = 1;
      products.forEach(p => {
        const row = document.createElement('tr');
        row.className = 'van-product-row hover:bg-gray-50';
        row.dataset.name = p.product_name.toLowerCase();
        
        row.innerHTML = `
          <td class="excel-row-num">${idx++}</td>
          <td class="font-bold text-gray-700">
            ${p.product_name}
            <span class="block text-[9px] text-gray-400 font-normal mt-0.5">৳${p.selling_price.toFixed(0)}/pc (${p.pieces_per_box} pcs/box)</span>
          </td>
          <td class="text-right font-mono">${p.loaded.formatted} <span class="text-[9px] text-gray-400 font-normal">(${p.loaded.pieces} pcs)</span></td>
          <td class="text-right font-mono text-red-500">${p.returned.formatted} <span class="text-[9px] text-red-400 font-normal">(${p.returned.pieces} pcs)</span></td>
          <td class="text-right font-mono text-green-700 font-bold">${p.sold.formatted} <span class="text-[9px] text-green-600 font-normal">(${p.sold.pieces} pcs)</span></td>
          <td class="text-right font-mono font-bold text-[#107c41] bg-emerald-50/20">৳${p.sold.value.toFixed(2)}</td>
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

    // ==========================================
    // ATTENDANCE QR CODE SCANNING FLOW
    // ==========================================

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
          showToast('QR Code Read Successful.', 'info');
          closeAttendanceScanner();

          // Submit check-in
          const res = await apiCall(API_URL + '?action=mark_attendance', 'POST', { qr_code: decodedText });
          if (res.success) {
            showToast(res.message, 'success');
            loadDashboard();
          } else {
            showToast(res.message || 'Check-in failed.', 'error');
          }
        },
        (errorMessage) => { /* ignore */ }
      ).catch(err => {
        console.warn('Camera start error:', err);
        showToast('Camera failed to start. Mock check-in using dropdown.', 'warning');
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
        showToast('Select a warehouse to mock check-in', 'warning');
        return;
      }
      
      const res = await apiCall(API_URL + '?action=mark_attendance', 'POST', { qr_code: `happy_warehouse_${val}` });
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

      let idx = 1;
      currencyNotes.forEach(n => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        
        row.innerHTML = `
          <td class="excel-row-num">${idx++}</td>
          <td class="font-bold text-gray-700">
            ${n.label}
            <span class="block text-[9px] text-gray-400 font-mono mt-0.5">Denomination: ৳${n.val}</span>
          </td>
          
          <td class="text-center p-1">
            <div class="inline-flex items-center border border-[#cbd5e1] bg-white">
              <button type="button" onclick="adjustNote('${n.key}', -1)" class="w-7 h-7 hover:bg-gray-100 text-gray-600 font-bold text-xs flex items-center justify-center shrink-0 border-r border-[#cbd5e1] btn-bounce">-</button>
              <input type="number" id="note-input-${n.key}" value="${noteQuantities[n.key] || 0}" min="0" oninput="setNoteDirect('${n.key}', this.value)" class="w-12 bg-transparent text-center font-bold font-mono text-xs text-gray-800 outline-none focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
              <button type="button" onclick="adjustNote('${n.key}', 1)" class="w-7 h-7 hover:bg-gray-100 text-gray-600 font-bold text-xs flex items-center justify-center shrink-0 border-l border-[#cbd5e1] btn-bounce">+</button>
            </div>
          </td>
          
          <td class="text-right font-mono font-bold text-indigo-600 text-xs bg-indigo-50/10" id="note-sub-${n.key}">৳0.00</td>
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
      const expense = 0.00; // DSR Expenses "spand area" fully removed!

      // Expected Submissions Formula: Out - Return - Damage
      const out_val = parseFloat(ad.out_value) || 0;
      const return_val = parseFloat(ad.return_value) || 0;
      
      const expectedSubmit = out_val - return_val - damage - expense;
      
      // Render expected submit
      document.getElementById('formula-expected').textContent = `৳${expectedSubmit.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
      document.getElementById('summary-expected').textContent = `৳${expectedSubmit.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;

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
          <span class="bg-gray-100 text-gray-500 text-[10px] font-black px-2 py-0.5">Empty Counter</span>
        `;
      } else if (Math.abs(diff) < 0.01) {
        badgeContainer.innerHTML = `
          <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5">Balanced</span>
        `;
      } else if (diff < 0) {
        badgeContainer.innerHTML = `
          <span class="bg-red-100 text-red-800 text-[10px] font-black px-2 py-0.5">Short: ৳${Math.abs(diff).toFixed(0)}</span>
        `;
      } else {
        badgeContainer.innerHTML = `
          <span class="bg-blue-100 text-blue-800 text-[10px] font-black px-2 py-0.5">Surplus: ৳${diff.toFixed(0)}</span>
        `;
      }
    }

    // Submit Settlement Report to Backend
    async function submitCashSettlement() {
      if (!dashboardData || !dashboardData.active_dispatch) {
        showToast('No active dispatch loaded to settle.', 'warning');
        return;
      }

      const active_dispatch_id = dashboardData.active_dispatch.id;
      const damage = parseFloat(document.getElementById('input-damage').value) || 0;
      const expense = 0.00; // DSR Expenses "spand area" fully removed!

      // Calculate total notes counted sum
      let totalCounted = 0;
      for (const [denomStr, qty] of Object.entries(noteQuantities)) {
        const denom = parseInt(denomStr);
        totalCounted += qty * denom;
      }

      if (totalCounted <= 0) {
        showToast('Count cash note quantities before submitting.', 'warning');
        return;
      }

      const remarks = document.getElementById('settlement-remarks').value.trim();

      const btn = document.getElementById('submit-settle-btn');
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Submitting...';

      const res = await apiCall(API_URL + '?action=submit_settlement', 'POST', {
        dispatch_id: active_dispatch_id,
        damage_amount: damage,
        expense_amount: expense,
        amount_submitted: totalCounted,
        notes_details: noteQuantities,
        notes_text: remarks
      });

      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-circle-check mr-1"></i> Submit Cash Settlement Report';

      if (res.success) {
        showToast(res.message, 'success');
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      } else {
        showToast(res.message || 'Error submitting cash settlement', 'error');
      }
    }

    // Global Logout Routine
    function logout() {
      if (confirm('Are you sure you want to logout of DSR Sheet App?')) {
        window.location.href = '<?= rootPath() ?>/logout.php';
      }
    }
  </script>
</body>
</html>
