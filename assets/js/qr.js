// assets/js/qr.js — QR Scanner: Camera + USB keyboard-wedge

let lastScannedCode = null;
let lastScannedTime = 0;
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
      const now = Date.now();
      // Lock: If same code is scanned within 2 seconds, ignore it
      if (qrCodeMessage === lastScannedCode && (now - lastScannedTime) < 2000) {
        return;
      }
      lastScannedCode = qrCodeMessage;
      lastScannedTime = now;
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
 * Generate a QR code image on a canvas element with high-res dotted style
 * @param {HTMLCanvasElement} canvas
 * @param {string} text
 * @param {number} size
 */
function generateQRCanvas(canvas, text, size = 120) {
  if (typeof QRCode !== 'undefined' && QRCode.create) {
    try {
      // High error correction level ('H' = 30%) for super high scan ratio
      const qr = QRCode.create(text, { errorCorrectionLevel: 'H' });
      const moduleCount = qr.modules.size;
      const margin = 1; // 1 module margin
      const totalModules = moduleCount + margin * 2;
      
      // Scale up for best resolution (8x internal resolution)
      const internalScale = 8;
      const finalSize = size * internalScale;
      const moduleSize = finalSize / totalModules;
      
      canvas.width = finalSize;
      canvas.height = finalSize;
      // CSS size keeps it correctly sized on screen
      canvas.style.width = size + 'px';
      canvas.style.height = size + 'px';
      
      const ctx = canvas.getContext('2d');
      
      // Fill white background for max contrast
      ctx.fillStyle = '#FFFFFF';
      ctx.fillRect(0, 0, finalSize, finalSize);
      
      ctx.fillStyle = '#000000';
      const dotRadius = moduleSize * 0.45; // Dot radius (leaves small gaps)
      
      for (let row = 0; row < moduleCount; row++) {
        for (let col = 0; col < moduleCount; col++) {
          if (qr.modules.get(row, col)) {
            // Check if module is within the 3 finder patterns (top-left, top-right, bottom-left)
            const isFinder = (row < 7 && col < 7) || 
                             (row < 7 && col >= moduleCount - 7) || 
                             (row >= moduleCount - 7 && col < 7);
            
            const x = (col + margin) * moduleSize;
            const y = (row + margin) * moduleSize;
            
            if (isFinder) {
              // Draw solid blocks for finder patterns for max readability by scanners
              // +0.5 prevents anti-aliasing gaps
              ctx.fillRect(x, y, moduleSize + 0.5, moduleSize + 0.5);
            } else {
              // Draw dots for the rest of the data modules
              ctx.beginPath();
              ctx.arc(x + moduleSize / 2, y + moduleSize / 2, dotRadius, 0, 2 * Math.PI);
              ctx.fill();
            }
          }
        }
      }
    } catch (err) {
      console.error('QR custom gen error:', err);
      // Fallback
      if (QRCode.toCanvas) {
        QRCode.toCanvas(canvas, text, { width: size, margin: 1, errorCorrectionLevel: 'H' });
      }
    }
  } else if (typeof QRCode !== 'undefined' && QRCode.toCanvas) {
    QRCode.toCanvas(canvas, text, { width: size, margin: 1, errorCorrectionLevel: 'H' }, function(err) {
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
    try {
      const canvas = document.createElement('canvas');
      generateQRCanvas(canvas, text, size);
      resolve(canvas.toDataURL('image/png'));
    } catch (err) {
      reject(err);
    }
  });
}
