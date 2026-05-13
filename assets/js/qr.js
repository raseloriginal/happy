// assets/js/qr.js — QR Scanner: Camera + USB keyboard-wedge

let activeCameraScanner = null;

/**
 * Start camera-based QR scanner
 * @param {string} elementId - ID of the div to render scanner into
 * @param {function} onScanCallback - Called with the scanned string
 */
function startCameraScanner(elementId, onScanCallback) {
  if (activeCameraScanner) {
    activeCameraScanner.stop().catch(() => {});
    activeCameraScanner = null;
  }
  const scanner = new Html5Qrcode(elementId);
  scanner.start(
    { facingMode: 'environment' },
    { fps: 10, qrbox: { width: 250, height: 250 } },
    (qrCodeMessage) => {
      onScanCallback(qrCodeMessage);
    },
    (error) => { /* ignore scan errors */ }
  ).catch(err => {
    console.warn('Camera start failed:', err);
    showToast('Camera not available. Use USB scanner.', 'warning');
  });
  activeCameraScanner = scanner;
  return scanner;
}

/**
 * Stop the active camera scanner
 */
function stopCameraScanner() {
  if (activeCameraScanner) {
    activeCameraScanner.stop().catch(() => {});
    activeCameraScanner = null;
  }
}

// ── USB / Keyboard-Wedge Scanner ──────────────────────────
// USB barcode scanners emit keystrokes ending in Enter
let usbBuffer = '';
let usbScanCallback = null;
let usbTimeout = null;

/**
 * Register a callback for USB scanner input
 * @param {function} callback - Called with the scanned string
 */
function registerUsbScanner(callback) {
  usbScanCallback = callback;
}

document.addEventListener('keypress', function(e) {
  // Ignore if focused on an input/textarea (unless it's the scanner input)
  const tag = document.activeElement.tagName;
  if ((tag === 'INPUT' || tag === 'TEXTAREA') && !document.activeElement.classList.contains('scanner-input')) {
    return;
  }

  clearTimeout(usbTimeout);

  if (e.key === 'Enter') {
    if (usbBuffer.length > 5 && usbScanCallback) {
      usbScanCallback(usbBuffer.trim());
    }
    usbBuffer = '';
  } else {
    usbBuffer += e.key;
    // Auto-clear buffer after 500ms of no input (prevents stale data)
    usbTimeout = setTimeout(() => { usbBuffer = ''; }, 500);
  }
});

// ── Client-side QR Code Generator ─────────────────────────
/**
 * Generate a QR code image on a canvas element
 * @param {HTMLCanvasElement} canvas
 * @param {string} text
 * @param {number} size
 */
function generateQRCanvas(canvas, text, size = 120) {
  if (typeof QRCode !== 'undefined' && QRCode.toCanvas) {
    QRCode.toCanvas(canvas, text, { width: size, margin: 1 }, function(err) {
      if (err) console.error('QR gen error:', err);
    });
  }
}

/**
 * Generate QR code as data URL (for img src)
 * @param {string} text
 * @param {number} size
 * @returns {Promise<string>}
 */
function generateQRDataUrl(text, size = 120) {
  return new Promise((resolve, reject) => {
    if (typeof QRCode !== 'undefined' && QRCode.toDataURL) {
      QRCode.toDataURL(text, { width: size, margin: 1 }, (err, url) => {
        if (err) reject(err);
        else resolve(url);
      });
    } else {
      reject(new Error('QRCode library not loaded'));
    }
  });
}
