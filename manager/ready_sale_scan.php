<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');
$pageTitle = 'Ready Sale Scan';
$pdo       = getDB();

// Fetch all active SRs and their company details
$srs = $pdo->query('
    SELECT s.id, u.name as sr_name, c.id as company_id, c.name as company_name 
    FROM sr s 
    JOIN users u ON u.id=s.user_id 
    JOIN companies c ON c.id=s.company_id 
    WHERE s.status=1 
    ORDER BY u.name
')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Ready Sale Scan — Happy Bangladesh</title>
  
  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;950&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
  <!-- CDNs -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <script src="<?= rootPath() ?>/assets/js/app.js" defer></script>
  
  <style>
    body { 
      font-family: 'Inter', sans-serif;
      background-color: #070B16;
      color: #F8FAFC;
      margin: 0; 
      display: flex; 
      flex-direction: column; 
      height: 96vh; 
      overflow: hidden;
    }
    
    /* Transparent scrollbar styling */
    ::-webkit-scrollbar {
      width: 4px;
    }
    ::-webkit-scrollbar-track {
      background: transparent;
    }
    ::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 99px;
    }

    /* Laser Scanning Animation */
    .laser-scan {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 3px;
      background: linear-gradient(90deg, transparent, #6366F1, transparent);
      box-shadow: 0 0 15px 4px rgba(99, 102, 241, 0.6);
      z-index: 10;
      animation: scan-vertical 2s ease-in-out infinite;
    }
    
    @keyframes scan-vertical {
      0% { top: 5%; opacity: 0.2; }
      50% { top: 95%; opacity: 1; }
      100% { top: 5%; opacity: 0.2; }
    }

    /* Rotating Camera Target corners */
    .target-corner {
      position: absolute;
      width: 24px;
      height: 24px;
      border-color: #6366F1;
      border-width: 3px;
      z-index: 15;
    }
    .corner-tl { top: 20px; left: 20px; border-right: 0; border-bottom: 0; border-top-left-radius: 8px; }
    .corner-tr { top: 20px; right: 20px; border-left: 0; border-bottom: 0; border-top-right-radius: 8px; }
    .corner-bl { bottom: 20px; left: 20px; border-right: 0; border-top: 0; border-bottom-left-radius: 8px; }
    .corner-br { bottom: 20px; right: 20px; border-left: 0; border-top: 0; border-bottom-right-radius: 8px; }

    /* Success Flash Animation */
    .flash-overlay {
      position: absolute;
      inset: 0;
      background: rgba(16, 185, 129, 0.25);
      pointer-events: none;
      opacity: 0;
      z-index: 50;
    }
    .flash-overlay.active {
      animation: flash-pulse 0.4s ease-out;
    }
    @keyframes flash-pulse {
      0% { opacity: 1; }
      100% { opacity: 0; }
    }

    /* Screen shake for error */
    .shake-error {
      animation: shake 0.4s ease-in-out;
    }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-6px); }
      40%, 80% { transform: translateX(6px); }
    }
  </style>
</head>
<body class="safe-bottom">

  <!-- Floating Summary Metrics Grid & Header -->
  <div class="bg-slate-950/80 border-b border-slate-900 backdrop-filter backdrop-blur-xl sticky top-0 z-50 flex flex-col">
    <!-- Header Controls -->
    <div class="px-4 py-3 flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <a href="javascript:void(0)" onclick="handleBackAction()" class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 flex items-center justify-center active:scale-95 transition">
          <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="flex flex-col">
          <h1 class="text-sm font-black tracking-wide text-white uppercase flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
            Ready Sale
          </h1>
          <span class="text-[10px] font-bold text-slate-400">Mobile Scanner App</span>
        </div>
      </div>
      
      <!-- Metrics Dashboard & Complete Button Inside Header -->
      <div class="flex items-center gap-2">
        <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-xl p-1 pr-3">
          <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-mono text-sm font-black" id="stat-unique-products">0</div>
          <div class="flex flex-col flex-shrink-0">
            <span class="text-[8px] font-bold text-slate-500 uppercase tracking-widest leading-none">Products</span>
            <span class="text-[10px] font-black text-slate-300 mt-0.5 leading-none" id="stat-total-pieces">0 pcs</span>
          </div>
        </div>
        
        <button onclick="saveReadyOrder()" id="complete-scan-btn" class="px-3.5 h-10 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-black text-xs flex items-center justify-center gap-1.5 shadow-lg shadow-emerald-500/20 active:scale-95 transition disabled:opacity-40 disabled:pointer-events-none" disabled>
          <i class="fa-solid fa-circle-check"></i> Complete
        </button>
      </div>
    </div>
    
    <!-- Grand Total Bar -->
    <div class="px-4 pb-3 flex items-center justify-between border-t border-slate-900/60 pt-2 bg-slate-950/40">
      <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Grand Total Value:</span>
      <span class="font-mono text-base font-black text-emerald-400" id="stat-grand-total">৳ 0.00</span>
    </div>
  </div>

  <!-- Camera Scanner Container (Collapsible) -->
  <div id="scanner-viewport-container" class="relative w-full h-[32vh] bg-black overflow-hidden border-b border-slate-900 transition-all duration-500 ease-in-out">
    <div id="ready-scan-reader" class="w-full h-full"></div>
    
    <!-- Scanning overlays -->
    <div class="target-corner corner-tl"></div>
    <div class="target-corner corner-tr"></div>
    <div class="target-corner corner-bl"></div>
    <div class="target-corner corner-br"></div>
    <div class="laser-scan"></div>
    <div id="success-flash" class="flash-overlay"></div>
  </div>

  <!-- Main Responsive Content Panel -->
  <div class="flex-1 overflow-hidden flex flex-col bg-gradient-to-b from-[#070B16] to-[#0A0E1C]">
    
    <!-- Tactile Scanner Control Panel -->
    <div class="px-4 py-3 flex gap-2 border-b border-slate-900 bg-slate-950/40">
      <button type="button" id="camera-toggle-btn" onclick="toggleCamera()" class="flex-1 py-2.5 px-3 rounded-xl border border-red-500/20 bg-red-500/10 text-red-400 font-bold text-xs flex items-center justify-center gap-1.5 transition duration-300 shadow-sm">
        <i class="fa-solid fa-video-slash"></i> Stop Scanner
      </button>
      <button type="button" onclick="focusManualInput()" class="flex-1 py-2.5 px-3 rounded-xl border border-slate-800 bg-slate-900 text-slate-400 font-bold text-xs flex items-center justify-center gap-1.5 transition hover:text-white">
        <i class="fa-solid fa-keyboard"></i> Manual Type
      </button>
    </div>

    <!-- Scrollable Workspace -->
    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-4">
      
      <!-- Retailer Details Card -->
      <div class="bg-slate-950/60 border border-slate-900 rounded-2xl p-4 shadow-md flex flex-col gap-3">
        <label class="text-[10px] font-black text-indigo-400 uppercase tracking-widest flex items-center gap-1.5">
          <i class="fa-solid fa-store"></i>
          Retailer Details *
        </label>
        <div class="grid grid-cols-1 gap-3">
          <div class="relative">
            <i class="fa-solid fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
            <input id="retailer-name" type="text" class="w-full bg-slate-900 border border-slate-800 text-slate-100 py-3.5 pl-10 pr-4 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all duration-300 placeholder-slate-600" placeholder="Retailer / Dokan Name *" required />
          </div>
          <div class="relative">
            <i class="fa-solid fa-phone absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
            <input id="retailer-phone" type="tel" class="w-full bg-slate-900 border border-slate-800 text-slate-100 py-3.5 pl-10 pr-4 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all duration-300 placeholder-slate-600" placeholder="Retailer Phone Number *" required />
          </div>
        </div>
      </div>

      <!-- Interactive SR Selector Card (Hidden) -->
      <div class="hidden bg-slate-950/60 border border-slate-900 rounded-2xl p-4 shadow-md flex-col gap-2.5">
        <div class="flex items-center justify-between">
          <label class="text-[10px] font-black text-indigo-400 uppercase tracking-widest flex items-center gap-1">
            <i class="fa-solid fa-user-tag"></i>
            Select Sales Rep (SR) *
          </label>
          <span id="sr-lock-badge" class="hidden text-[8px] font-bold px-2 py-0.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 uppercase">Auto Locked</span>
        </div>
        <select id="sr-select" onchange="handleSRChange()" class="w-full bg-slate-900 border border-slate-800 text-slate-100 py-3.5 px-4 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all duration-300">
          <option value="">Choose Sales Representative...</option>
          <?php foreach ($srs as $sr): ?>
            <option value="<?= $sr['id'] ?>" data-company-id="<?= $sr['company_id'] ?>" data-company-name="<?= htmlspecialchars($sr['company_name']) ?>">
              <?= htmlspecialchars($sr['sr_name']) ?> — <?= htmlspecialchars($sr['company_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Manual Input Search Bar -->
      <div class="relative bg-slate-950/40 rounded-2xl border border-slate-900 p-1 flex items-center shadow-inner">
        <i class="fa-solid fa-barcode text-slate-500 ml-3.5 mr-2"></i>
        <input id="manual-qr" type="text" onkeydown="handleManualInput(event)" class="w-full bg-transparent text-white py-3 px-1 text-xs font-semibold outline-none placeholder-slate-500" placeholder="Type or paste Box QR UID here..." />
        <button onclick="submitManualInput()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2.5 px-4 rounded-xl mr-1 transition active:scale-95">Add</button>
      </div>

      <!-- Header Label for List -->
      <div class="flex items-center justify-between px-1">
        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Scanned Products Log</span>
        <span class="text-[9px] font-bold text-indigo-400 animate-pulse flex items-center gap-1">
          <i class="fa-solid fa-circle text-[6px]"></i>
          Tap product to edit boxes
        </span>
      </div>

      <!-- Empty Message Placeholder -->
      <div class="text-center py-12 bg-slate-950/30 border border-dashed border-slate-900 rounded-3xl p-6 flex flex-col items-center justify-center opacity-70" id="empty-scan-msg">
        <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800/80 text-slate-400 flex items-center justify-center text-2xl mb-4 shadow-sm animate-pulse">
          <i class="fa-solid fa-barcode"></i>
        </div>
        <h4 class="text-sm font-bold text-slate-200">No scanned items yet</h4>
        <p class="text-[11px] text-slate-500 mt-1 max-w-[200px]">Scan a physical QR label or type a valid box code to build the order.</p>
      </div>

      <!-- Scanned List Container -->
      <div class="space-y-3.5 pb-8" id="scanned-items-list"></div>

    </div>
  </div>

  <!-- Accidental Exit & Warning Dialog -->
  <div id="confirm-modal" class="fixed inset-0 bg-black/80 backdrop-filter backdrop-blur-md z-[200] hidden items-center justify-center p-4">
    <div class="bg-slate-950 border border-slate-800 rounded-3xl p-6 w-full max-w-sm shadow-2xl scale-95 transition-all duration-300 transform" id="confirm-modal-box">
      <div class="w-14 h-14 bg-amber-500/10 border border-amber-500/20 text-amber-500 rounded-2xl flex items-center justify-center text-2xl mb-4 mx-auto animate-bounce">
        <i class="fa-solid fa-triangle-exclamation"></i>
      </div>
      <h3 class="text-lg font-bold text-white text-center mb-2" id="confirm-title">Warning</h3>
      <p class="text-xs text-slate-400 text-center mb-6" id="confirm-message">Are you sure you want to proceed?</p>
      <div class="flex gap-3">
        <button id="confirm-cancel-btn" class="flex-1 py-3 bg-slate-900 border border-slate-800 text-slate-400 rounded-xl font-semibold text-xs hover:bg-slate-800 transition">Cancel</button>
        <button id="confirm-ok-btn" class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs shadow-lg shadow-indigo-600/30 transition">Yes, Clear</button>
      </div>
    </div>
  </div>

  <!-- Box Breakdown Popup Modal -->
  <div id="breakdown-modal" class="fixed inset-0 bg-black/90 backdrop-filter backdrop-blur-lg z-[150] hidden flex-col transition-all duration-300">
    <!-- Modal Header -->
    <div class="px-5 py-4 border-b border-slate-800 bg-slate-950 flex items-center justify-between">
      <div class="flex flex-col">
        <span class="text-[9px] font-black text-indigo-400 uppercase tracking-widest">Adjust Box Quantities</span>
        <h3 class="text-sm font-black text-white" id="breakdown-product-name">Product Name</h3>
      </div>
      <button onclick="closeBreakdownModal()" class="w-9 h-9 rounded-full bg-slate-900 border border-slate-800 text-slate-400 flex items-center justify-center hover:text-white transition">
        <i class="fa-solid fa-xmark text-sm"></i>
      </button>
    </div>
    
    <!-- Modal Content (Scrollable) -->
    <div class="flex-1 overflow-y-auto p-5 space-y-4" id="breakdown-modal-content">
      <!-- Dynamic Box Cards go here -->
    </div>
    
    <!-- Modal Footer -->
    <div class="p-5 border-t border-slate-800 bg-slate-950 flex gap-3">
      <button onclick="closeBreakdownModal()" class="flex-1 py-3.5 border border-slate-800 bg-slate-900 text-slate-300 rounded-xl font-bold text-xs hover:bg-slate-800 transition">
        Cancel
      </button>
      <button onclick="applyBreakdownChanges()" class="flex-1 py-3.5 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white rounded-xl font-black text-xs shadow-lg shadow-emerald-500/20 transition">
        Apply Changes
      </button>
    </div>
  </div>



  <!-- Dynamic JS Core -->
  <script>
  let scannedData = {};
  let scannedQrIds = [];
  let activeScanner = null;
  let selectedSrId = null;
  let selectedCompanyId = null;
  let isCameraActive = true;
  let pendingSRChange = null;
  
  // Breakdown modal state variables
  let activeBreakdownPid = null;
  let temporaryBoxPieces = {}; // tracks temporary changes while modal is open {qrId: currentPieces}

  window.addEventListener('DOMContentLoaded', () => {
    startReadyScanner();
    updateOrderStats();
  });

  // Start HTML5Qrcode camera engine
  function startReadyScanner() {
    activeScanner = new Html5Qrcode("ready-scan-reader");
    activeScanner.start(
      { facingMode: "environment" },
      { fps: 15, qrbox: { width: 300, height: 300 } },
      (decodedText) => handleReadyScan(decodedText),
      (errorMessage) => {}
    ).catch(err => {
      console.warn("Camera auto start failed:", err);
      // Fail silently unless they explicitly click start
    });
  }

  // Focus on manual input field
  function focusManualInput() {
    const input = document.getElementById('manual-qr');
    input.focus();
    // Scroll to the manual input smoothly on mobile
    input.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  // Toggle Camera Switch (CPU & Battery Saver)
  async function toggleCamera() {
    const container = document.getElementById('scanner-viewport-container');
    const btn = document.getElementById('camera-toggle-btn');
    
    if (isCameraActive) {
      // Stop scanner
      if (activeScanner) {
        try {
          await activeScanner.stop();
        } catch (e) {
          console.error("Scanner stop fail:", e);
        }
      }
      container.style.height = '0px';
      btn.innerHTML = '<i class="fa-solid fa-video mr-1.5"></i> Start Camera';
      btn.classList.remove('bg-red-500/10', 'text-red-400', 'border-red-500/20');
      btn.classList.add('bg-indigo-500/10', 'text-indigo-400', 'border-indigo-500/20');
      isCameraActive = false;
      playClickSound();
    } else {
      // Start scanner
      container.style.height = '32vh';
      btn.innerHTML = '<i class="fa-solid fa-video-slash mr-1.5"></i> Stop Scanner';
      btn.classList.remove('bg-indigo-500/10', 'text-indigo-400', 'border-indigo-500/20');
      btn.classList.add('bg-red-500/10', 'text-red-400', 'border-red-500/20');
      isCameraActive = true;
      playClickSound();
      // Brief delay to let the CSS transition end before initiating camera
      setTimeout(() => {
        startReadyScanner();
      }, 500);
    }
  }

  // Handles manual QR submit button
  function submitManualInput() {
    const input = document.getElementById('manual-qr');
    const val = input.value.trim();
    if (val) {
      handleReadyScan(val);
      input.value = '';
    }
  }

  // Handle Enter key on manual input
  function handleManualInput(e) {
    if (e.key === 'Enter') {
      submitManualInput();
    }
  }

  // Main Scan handler
  async function handleReadyScan(uid) {
    const retailerName = document.getElementById('retailer-name').value.trim();
    const retailerPhone = document.getElementById('retailer-phone').value.trim();
    
    if (!retailerName || !retailerPhone) {
      triggerShake();
      playErrorBeep();
      showToast('Please enter Retailer Name and Phone Number first!', 'warning');
      if (!retailerName) {
        document.getElementById('retailer-name').focus();
      } else {
        document.getElementById('retailer-phone').focus();
      }
      return;
    }

    if (window._scanning) return;
    window._scanning = true;
    setTimeout(() => window._scanning = false, 1200);

    const url = `<?= rootPath() ?>/api/orders.php?action=scan_ready_sale&qr_uid=${encodeURIComponent(uid)}` + 
                (selectedSrId ? `&sr_id=${selectedSrId}` : '');
                
    const res = await api(url);
    
    if (!res.success) {
      triggerShake();
      playErrorBeep();
      showToast(res.message, 'error');
      return;
    }

    const p = res.data;
    
    // Strict Duplicate Check
    if (scannedQrIds.includes(p.qr_id)) {
      triggerShake();
      playErrorBeep();
      showToast('Box already scanned!', 'warning');
      return;
    }

    // Auto-detect and set SR if not selected
    if (!selectedSrId) {
      selectedSrId = p.sr_id;
      const sel = document.getElementById('sr-select');
      if (sel) {
        sel.value = selectedSrId;
        const opt = sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
        selectedCompanyId = opt ? opt.dataset.companyId : null;
      }
      const badge = document.getElementById('sr-lock-badge');
      if (badge) badge.classList.remove('hidden');
    }

    triggerFlash();
    playSuccessBeep();
    scannedQrIds.push(p.qr_id);

    const pieces = parseInt(p.scanned_pieces) || 0;

    // Structured storage to track individual boxes
    if (!scannedData[p.id]) {
      scannedData[p.id] = { 
        id: p.id,
        name: p.name, 
        qty: pieces, 
        price: p.selling_price, 
        ppb: p.pieces_per_box,
        boxes: [
          {
            qr_id: p.qr_id,
            qr_uid: uid,
            max_pieces: pieces,
            pieces_sold: pieces
          }
        ]
      };
    } else {
      scannedData[p.id].boxes.push({
        qr_id: p.qr_id,
        qr_uid: uid,
        max_pieces: pieces,
        pieces_sold: pieces
      });
      // Recalculate total quantity
      scannedData[p.id].qty = scannedData[p.id].boxes.reduce((sum, b) => sum + b.pieces_sold, 0);
    }
    
    updateOrderStats();
    renderScannedList();
  }

  // Renders products scanned list with deep visual design
  function renderScannedList() {
    const container = document.getElementById('scanned-items-list');
    container.innerHTML = '';
    
    const pids = Object.keys(scannedData);
    if (pids.length === 0) {
      document.getElementById('empty-scan-msg').style.display = 'flex';
      document.getElementById('complete-scan-btn').disabled = true;
      return;
    }
    
    document.getElementById('empty-scan-msg').style.display = 'none';
    document.getElementById('complete-scan-btn').disabled = false;
    
    // Render list (newest first)
    pids.reverse().forEach(pid => {
      const item = scannedData[pid];
      const card = document.createElement('div');
      card.className = "bg-slate-900/40 border border-slate-800/80 rounded-2xl p-4 flex flex-col gap-3 shadow-lg active:scale-[0.99] active:bg-slate-900/60 transition-all duration-200 cursor-pointer";
      
      // Tap on card to open boxes editor modal
      card.onclick = (e) => {
        if (e.target.closest('.delete-product-btn')) return; // ignore delete clicks
        openBreakdownModal(pid);
      };
      
      // Box breakdown formula
      let boxBreakdownText = "";
      if (item.ppb > 1) {
        const boxes = Math.floor(item.qty / item.ppb);
        const rem = item.qty % item.ppb;
        if (boxes > 0 && rem > 0) {
          boxBreakdownText = `${boxes} box + ${rem} pcs`;
        } else if (boxes > 0) {
          boxBreakdownText = `${boxes} box${boxes > 1 ? 's' : ''}`;
        } else {
          boxBreakdownText = `${rem} pcs`;
        }
      } else {
        boxBreakdownText = `${item.qty} pcs`;
      }
      
      // Individual mini box pill labels
      let boxPillsHtml = "";
      item.boxes.forEach(b => {
        const isPartial = b.pieces_sold < b.max_pieces;
        boxPillsHtml += `
          <span class="inline-flex items-center gap-1 text-[9px] font-mono px-2 py-0.5 rounded bg-slate-950 border ${isPartial ? 'border-amber-500/25 text-amber-400 bg-amber-500/5' : 'border-slate-900 text-slate-400'}">
            <i class="fa-solid fa-box text-[8px] opacity-70"></i>
            ${b.qr_uid.substring(0, 10)}: ${b.pieces_sold} pcs
            ${isPartial ? '<span class="w-1 h-1 rounded-full bg-amber-500 animate-pulse"></span>' : ''}
          </span>
        `;
      });

      const subtotal = item.qty * item.price;
      
      card.innerHTML = `
        <div class="flex items-start justify-between gap-3">
          <div class="flex-1">
            <h4 class="font-bold text-white text-sm tracking-wide leading-tight">${item.name}</h4>
            <span class="text-[10px] text-slate-500 font-semibold uppercase mt-0.5 block">
              ${item.ppb} pcs/box • ৳ ${parseFloat(item.price).toFixed(2)}/pcs
            </span>
          </div>
          
          <button type="button" onclick="deleteProduct('${pid}')" class="delete-product-btn w-8 h-8 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500 hover:text-white flex items-center justify-center active:scale-90 transition">
            <i class="fa-solid fa-trash-can text-xs"></i>
          </button>
        </div>
        
        <!-- Inner Box pills -->
        <div class="flex flex-wrap gap-1.5 py-1.5 border-t border-b border-slate-900">
          ${boxPillsHtml}
        </div>
        
        <!-- Breakdown & Financial subtotal values -->
        <div class="flex items-center justify-between mt-0.5">
          <div class="flex flex-col">
            <span class="text-[8px] font-black text-indigo-400 uppercase tracking-widest leading-none">Breakdown</span>
            <span class="text-xs font-bold text-slate-300 mt-1 leading-none">${boxBreakdownText}</span>
          </div>
          
          <div class="text-right">
            <span class="text-[8px] font-black text-emerald-400 uppercase tracking-widest leading-none block">Subtotal</span>
            <span class="font-mono text-sm font-black text-white mt-1 leading-none">৳ ${subtotal.toLocaleString('en-BD', { minimumFractionDigits: 2 })}</span>
          </div>
        </div>
      `;
      
      container.appendChild(card);
    });
  }

  // Deletes single product and clears associated QR codes
  function deleteProduct(pid) {
    const item = scannedData[pid];
    if (!item) return;
    
    showCustomConfirm(
      "Delete Product?",
      `Are you sure you want to remove ${item.name} from this order scan?`,
      () => {
        // Clear matching QR IDs
        item.boxes.forEach(b => {
          const idx = scannedQrIds.indexOf(b.qr_id);
          if (idx > -1) scannedQrIds.splice(idx, 1);
        });
        
        delete scannedData[pid];
        
        // Reset SR locking if empty
        if (scannedQrIds.length === 0) {
          document.getElementById('sr-lock-badge').classList.add('hidden');
          // If the selector was empty initially, let's unlock selectedSrId
          if (document.getElementById('sr-select').value === '') {
            selectedSrId = null;
            selectedCompanyId = null;
          }
        }
        
        updateOrderStats();
        renderScannedList();
        playErrorBeep();
        showToast("Product removed!", "info");
      }
    );
  }

  // Searchable SR selector manual change callback with dynamic warnings
  function handleSRChange() {
    const sel = document.getElementById('sr-select');
    const nextSrId = sel.value;
    
    if (scannedQrIds.length > 0) {
      if (nextSrId !== selectedSrId) {
        pendingSRChange = nextSrId;
        showCustomConfirm(
          "Change Representative?",
          "Are you sure you want to assign a new Sales Representative to this order scan?",
          () => {
            // Confirm OK - Keep items, just update representative
            selectedSrId = pendingSRChange;
            
            if (selectedSrId) {
              const opt = sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
              selectedCompanyId = opt ? opt.dataset.companyId : null;
              document.getElementById('sr-lock-badge').classList.remove('hidden');
            } else {
              selectedCompanyId = null;
              document.getElementById('sr-lock-badge').classList.add('hidden');
            }
            
            updateOrderStats();
            renderScannedList();
            playClickSound();
            pendingSRChange = null;
          },
          () => {
            // Confirm Cancel
            sel.value = selectedSrId || '';
            pendingSRChange = null;
          }
        );
      }
    } else {
      selectedSrId = nextSrId;
      if (selectedSrId) {
        const opt = sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
        selectedCompanyId = opt ? opt.dataset.companyId : null;
        document.getElementById('sr-lock-badge').classList.remove('hidden');
      } else {
        selectedCompanyId = null;
        document.getElementById('sr-lock-badge').classList.add('hidden');
      }
      updateOrderStats();
      renderScannedList();
    }
  }

  // Opens Box Breakdown Modal
  function openBreakdownModal(pid) {
    activeBreakdownPid = pid;
    const item = scannedData[pid];
    if (!item) return;
    
    document.getElementById('breakdown-product-name').textContent = item.name;
    const container = document.getElementById('breakdown-modal-content');
    container.innerHTML = '';
    
    // Clear & sync temp pieces
    temporaryBoxPieces = {};
    
    item.boxes.forEach((box, index) => {
      temporaryBoxPieces[box.qr_id] = box.pieces_sold;
      
      const card = document.createElement('div');
      card.className = "bg-slate-900/50 border border-slate-800/80 rounded-2xl p-4 flex flex-col gap-3 shadow-md";
      card.innerHTML = `
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-black flex items-center justify-center">${index + 1}</span>
            <span class="font-mono text-xs font-bold text-slate-300">Box UID: ${box.qr_uid}</span>
          </div>
          <span class="text-[9px] font-bold text-slate-500 uppercase">Original Max: ${box.max_pieces} pcs</span>
        </div>
        
        <div class="flex items-center justify-between gap-4 mt-1 bg-slate-950 p-2.5 rounded-xl border border-slate-900/60">
          <span class="text-xs text-slate-400 font-semibold">Selling Quantity:</span>
          <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-lg p-1">
            <button type="button" onclick="decrementTempBox('${box.qr_id}')" class="w-8 h-8 rounded-md bg-slate-950 text-slate-300 font-bold hover:bg-slate-800 transition flex items-center justify-center">
              <i class="fa-solid fa-minus text-[9px]"></i>
            </button>
            <input type="number" id="temp-box-input-${box.qr_id}" min="0" max="${box.max_pieces}" value="${box.pieces_sold}" oninput="validateTempBox('${box.qr_id}', ${box.max_pieces})" class="w-14 bg-transparent text-center font-mono text-sm font-black text-white outline-none border-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
            <button type="button" onclick="incrementTempBox('${box.qr_id}', ${box.max_pieces})" class="w-8 h-8 rounded-md bg-slate-950 text-slate-300 font-bold hover:bg-slate-800 transition flex items-center justify-center">
              <i class="fa-solid fa-plus text-[9px]"></i>
            </button>
          </div>
        </div>
      `;
      container.appendChild(card);
    });
    
    const modal = document.getElementById('breakdown-modal');
    modal.style.display = 'flex';
    playClickSound();
  }

  // Modal box item handlers
  function decrementTempBox(qrId) {
    const input = document.getElementById(`temp-box-input-${qrId}`);
    let val = parseInt(input.value) || 0;
    if (val > 0) {
      val--;
      input.value = val;
      temporaryBoxPieces[qrId] = val;
      playClickSound();
    }
  }

  function incrementTempBox(qrId, maxPcs) {
    const input = document.getElementById(`temp-box-input-${qrId}`);
    let val = parseInt(input.value) || 0;
    if (val < maxPcs) {
      val++;
      input.value = val;
      temporaryBoxPieces[qrId] = val;
      playClickSound();
    }
  }

  // Sanitize numerical inputs directly typed
  function validateTempBox(qrId, maxPcs) {
    const input = document.getElementById(`temp-box-input-${qrId}`);
    let val = parseInt(input.value);
    if (isNaN(val) || val < 0) val = 0;
    if (val > maxPcs) val = maxPcs;
    input.value = val;
    temporaryBoxPieces[qrId] = val;
  }

  // Closes Box Breakdown Modal
  function closeBreakdownModal() {
    document.getElementById('breakdown-modal').style.display = 'none';
    playClickSound();
    activeBreakdownPid = null;
  }

  // Save Modal box changes
  function applyBreakdownChanges() {
    const pid = activeBreakdownPid;
    const item = scannedData[pid];
    if (!item) return;
    
    // Save to scannedData
    item.boxes.forEach(box => {
      if (temporaryBoxPieces[box.qr_id] !== undefined) {
        box.pieces_sold = temporaryBoxPieces[box.qr_id];
      }
    });
    
    // Update product quantity accumulator
    item.qty = item.boxes.reduce((sum, b) => sum + b.pieces_sold, 0);
    
    closeBreakdownModal();
    playSuccessBeep();
    showToast("Pieces updated!", "success");
    
    updateOrderStats();
    renderScannedList();
  }

  // Custom Warning Confirmation Drawer Box
  function showCustomConfirm(title, message, onOk, onCancel) {
    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-message').textContent = message;
    
    const modal = document.getElementById('confirm-modal');
    modal.style.display = 'flex';
    
    const okBtn = document.getElementById('confirm-ok-btn');
    const cancelBtn = document.getElementById('confirm-cancel-btn');
    
    okBtn.onclick = () => {
      modal.style.display = 'none';
      if (onOk) onOk();
    };
    
    cancelBtn.onclick = () => {
      modal.style.display = 'none';
      if (onCancel) onCancel();
    };
  }

  // Accidental exit check on back button
  function handleBackAction() {
    if (scannedQrIds.length > 0) {
      showCustomConfirm(
        "Discard Scan?",
        "You have scanned items in this session. Leaving this page will completely discard your current progress. Leave anyway?",
        () => {
          window.location.href = '<?= rootPath() ?>/manager/orders.php';
        }
      );
    } else {
      window.location.href = '<?= rootPath() ?>/manager/orders.php';
    }
  }

  // Audio Synth triggers
  function playSuccessBeep() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime); // Crisp A5 note
      gain.gain.setValueAtTime(0.08, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
      
      osc.start();
      osc.stop(ctx.currentTime + 0.15);
      
      if (navigator.vibrate) {
        navigator.vibrate(85);
      }
    } catch (e) {
      console.warn("Audio fail:", e);
    }
  }

  // Dynamic error audio chime
  function playErrorBeep() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      
      const tone = (freq, start, dur) => {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(freq, ctx.currentTime + start);
        gain.gain.setValueAtTime(0.12, ctx.currentTime + start);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + start + dur);
        osc.start(ctx.currentTime + start);
        osc.stop(ctx.currentTime + start + dur);
      };
      
      tone(180, 0, 0.12);
      tone(150, 0.14, 0.18);
      
      if (navigator.vibrate) {
        navigator.vibrate([100, 60, 100]);
      }
    } catch (e) {
      console.warn("Audio fail:", e);
    }
  }

  function playClickSound() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'sine';
      osc.frequency.setValueAtTime(1400, ctx.currentTime); // Gentle mechanical tick click
      gain.gain.setValueAtTime(0.03, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.04);
      osc.start();
      osc.stop(ctx.currentTime + 0.04);
    } catch(e) {}
  }

  // Visual success scan flash
  function triggerFlash() {
    const flash = document.getElementById('success-flash');
    flash.classList.remove('active'); void flash.offsetWidth; flash.classList.add('active');
  }

  // Visual shake animation
  function triggerShake() {
    document.body.classList.remove('shake-error');
    void document.body.offsetWidth;
    document.body.classList.add('shake-error');
  }

  // Recalculates metrics and updates dynamic buttons
  function updateOrderStats() {
    let uniqueProducts = 0;
    let totalPieces = 0;
    let grandTotal = 0;
    
    for (const pid in scannedData) {
      uniqueProducts++;
      const item = scannedData[pid];
      totalPieces += item.qty;
      grandTotal += (item.qty * item.price);
    }
    
    document.getElementById('stat-unique-products').textContent = uniqueProducts;
    document.getElementById('stat-total-pieces').textContent = totalPieces.toLocaleString('en-BD');
    document.getElementById('stat-grand-total').textContent = '৳ ' + grandTotal.toLocaleString('en-BD', { minimumFractionDigits: 2 });
    
    const completeBtn = document.getElementById('complete-scan-btn');
    if (totalPieces > 0) {
      completeBtn.disabled = false;
      completeBtn.innerHTML = `<i class="fa-solid fa-circle-check mr-1.5 animate-pulse"></i> Complete (৳${grandTotal.toLocaleString('en-BD', { maximumFractionDigits: 0 })})`;
    } else {
      completeBtn.disabled = true;
      completeBtn.innerHTML = `<i class="fa-solid fa-circle-check mr-1.5"></i> Complete`;
    }
  }

  // POST Order to server
  async function saveReadyOrder() {
    const retailerName = document.getElementById('retailer-name').value.trim();
    const retailerPhone = document.getElementById('retailer-phone').value.trim();
    
    if (!retailerName || !retailerPhone) {
      showToast('Please enter Retailer Name and Phone Number first!', 'warning');
      return;
    }

    if (!selectedSrId) {
      showToast('Please scan a product first to assign the order!', 'warning');
      return;
    }
    
    const items = [];
    const scanned_qrs = [];
    
    for (const pid in scannedData) {
      const item = scannedData[pid];
      if (item.qty > 0) {
        items.push({ product_id: pid, qty_pieces: item.qty });
        
        // Push scanned QR boxes details with their sold quantities
        item.boxes.forEach(box => {
          if (box.pieces_sold > 0) {
            scanned_qrs.push({
              qr_id: box.qr_id,
              pieces_sold: box.pieces_sold
            });
          }
        });
      }
    }
    
    if (items.length === 0 || scanned_qrs.length === 0) {
      showToast('Order cannot be empty. Please scan some box quantities first.', 'warning');
      return;
    }
    
    const btn = document.getElementById('complete-scan-btn');
    btn.disabled = true; 
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Saving Order…';
    
    const data = await api('<?= rootPath() ?>/api/orders.php', 'POST', {
      sr_id: selectedSrId,
      order_date: todayDate(),
      status: 'ready_sale',
      retailer_name: retailerName,
      retailer_phone: retailerPhone,
      items,
      scanned_qrs
    });

    if (data.success) {
      playSuccessBeep();
      showToast('Ready Sale Order completed successfully!');
      // Reset scanning data to prevent double-unload alerts
      scannedQrIds = [];
      scannedData = {};
      setTimeout(() => window.location.href = '<?= rootPath() ?>/manager/orders.php', 1600);
    } else {
      playErrorBeep();
      showToast(data.message || 'Saving failed', 'error');
      btn.disabled = false;
      updateOrderStats();
    }
  }

  // Sleek animated Toast notification
  function showToast(msg, type = 'success') {
    if (window.mobToast) { window.mobToast(msg, type); return; }
    
    const toast = document.createElement('div');
    toast.className = `fixed top-6 left-1/2 -translate-x-1/2 px-5 py-3.5 rounded-2xl z-[300] font-bold text-xs shadow-2xl flex items-center gap-2 border transition-all duration-300 transform -translate-y-12 opacity-0`;
    
    let icon = "";
    if (type === 'success') {
      toast.classList.add('bg-emerald-500/10', 'border-emerald-500/20', 'text-emerald-400');
      icon = '<i class="fa-solid fa-circle-check"></i>';
    } else if (type === 'error') {
      toast.classList.add('bg-red-500/10', 'border-red-500/20', 'text-red-400');
      icon = '<i class="fa-solid fa-circle-xmark"></i>';
    } else if (type === 'warning') {
      toast.classList.add('bg-amber-500/10', 'border-amber-500/20', 'text-amber-400');
      icon = '<i class="fa-solid fa-triangle-exclamation"></i>';
    } else {
      toast.classList.add('bg-slate-900/90', 'border-slate-800', 'text-slate-300');
      icon = '<i class="fa-solid fa-circle-info"></i>';
    }
    
    toast.innerHTML = `${icon}<span>${msg}</span>`;
    document.body.appendChild(toast);
    
    // Animate In
    setTimeout(() => {
      toast.classList.remove('-translate-y-12', 'opacity-0');
      toast.classList.add('translate-y-0', 'opacity-100');
    }, 50);
    
    // Animate Out & remove
    setTimeout(() => {
      toast.classList.remove('translate-y-0', 'opacity-100');
      toast.classList.add('-translate-y-12', 'opacity-0');
      setTimeout(() => toast.remove(), 350);
    }, 3200);
  }
  </script>
</body>
</html>
