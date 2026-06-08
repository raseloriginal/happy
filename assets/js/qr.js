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
    activeCameraScanner.stop().catch(() => { });
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
    activeCameraScanner.stop().catch(() => { });
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

document.addEventListener('keypress', function (e) {
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
 * @param {number} size - EDIT DISPLAY SIZE: Change the default '120' to adjust the displayed size in pixels.
 * @param {function} onComplete
 */
function generateQRCanvas(canvas, text, size = 120, onComplete) {
  if (typeof QRCode !== 'undefined' && QRCode.create) {
    try {
      // High error correction level ('H' = 30%) for super high scan ratio
      const qr = QRCode.create(text, { errorCorrectionLevel: 'H' });
      const moduleCount = qr.modules.size;

      // EDIT MARGIN: Change this value to adjust the quiet zone (white border) around the QR code.
      // 1 means 1 module width. Set to 0 to remove margin completely, or 2+ for a larger margin.
      const margin = 1;
      const totalModules = moduleCount + margin * 2;

      // Scale up for best resolution (8x internal resolution)
      const internalScale = 8;

      // EDIT WIDTH & HEIGHT: 'finalSize' is the internal high-res width and height of the canvas.
      // If you want to change the size on screen, pass a different 'size' parameter to this function.
      const finalSize = size * internalScale;
      const moduleSize = finalSize / totalModules;

      canvas.width = finalSize;
      canvas.height = finalSize;

      // EDIT CSS WIDTH & HEIGHT: CSS size keeps it correctly sized on screen.
      canvas.style.width = size + 'px';
      canvas.style.height = size + 'px';

      const ctx = canvas.getContext('2d');

      // Fill white background for max contrast
      // EDIT BACKGROUND OPACITY & COLOR: Change '#FFFFFF' to 'transparent' for no background. 
      // You can also use 'rgba(255, 255, 255, 0.5)' to change background opacity.
      ctx.fillStyle = '#FFFFFF';
      ctx.fillRect(0, 0, finalSize, finalSize);

      // Helper function to check if a module is active and NOT part of the finder patterns
      const isFinder = (row, col) => {
        return (row < 7 && col < 7) ||
          (row < 7 && col >= moduleCount - 7) ||
          (row >= moduleCount - 7 && col < 7);
      };

      const isActive = (row, col) => {
        if (row < 0 || row >= moduleCount || col < 0 || col >= moduleCount) return false;
        return qr.modules.get(row, col) && !isFinder(row, col);
      };

      // 1. Draw custom Finder Patterns
      const drawFinder = (startX, startY) => {
        const x = (startX + margin) * moduleSize;
        const y = (startY + margin) * moduleSize;
        const size = 7 * moduleSize;
        const radius = size * 0.25; // Rounded corners

        // Outer box (solid black)
        // EDIT FINDER COLOR: Change '#000000' below to change the outer square color of the 3 corners
        ctx.fillStyle = '#000000';
        ctx.beginPath();
        if (ctx.roundRect) {
          ctx.roundRect(x, y, size, size, radius);
        } else {
          ctx.rect(x, y, size, size); // Fallback
        }
        ctx.fill();

        // Cutout inner box (white)
        ctx.fillStyle = '#FFFFFF';
        ctx.beginPath();
        if (ctx.roundRect) {
          ctx.roundRect(x + moduleSize, y + moduleSize, size - 2 * moduleSize, size - 2 * moduleSize, radius * 0.7);
        } else {
          ctx.rect(x + moduleSize, y + moduleSize, size - 2 * moduleSize, size - 2 * moduleSize);
        }
        ctx.fill();

        // Inner eye (solid black)
        // EDIT FINDER INNER COLOR: Change '#000000' below to change the inner dot color of the 3 corners
        ctx.fillStyle = '#000000';
        ctx.beginPath();
        const innerSize = 3 * moduleSize;
        if (ctx.roundRect) {
          ctx.roundRect(x + 2 * moduleSize, y + 2 * moduleSize, innerSize, innerSize, radius * 0.4);
        } else {
          ctx.rect(x + 2 * moduleSize, y + 2 * moduleSize, innerSize, innerSize);
        }
        ctx.fill();
      };

      drawFinder(0, 0); // Top-left
      drawFinder(moduleCount - 7, 0); // Top-right
      drawFinder(0, moduleCount - 7); // Bottom-left

      // 2. Draw Data Modules (Connected dots)
      // EDIT DOT COLOR: Change '#000000' to any color code to change the QR dots color.
      ctx.fillStyle = '#000000';

      // EDIT GAPS & DOT SIZE: Change '0.42'. 
      // 0.5 means dots are full size and touch perfectly (no gaps).
      // Smaller values like 0.35 will make dots smaller, creating larger gaps between dots.
      const dotRadius = moduleSize * 0.42;

      for (let row = 0; row < moduleCount; row++) {
        for (let col = 0; col < moduleCount; col++) {
          if (isActive(row, col)) {
            const centerX = (col + margin + 0.5) * moduleSize;
            const centerY = (row + margin + 0.5) * moduleSize;

            // Draw base circle
            ctx.beginPath();
            ctx.arc(centerX, centerY, dotRadius, 0, 2 * Math.PI);
            ctx.fill();

            // Connect to right module
            if (isActive(row, col + 1)) {
              ctx.fillRect(centerX, centerY - dotRadius, moduleSize, dotRadius * 2);
            }

            // Connect to bottom module
            if (isActive(row + 1, col)) {
              ctx.fillRect(centerX - dotRadius, centerY, dotRadius * 2, moduleSize);
            }
          }
        }
      }

      // Draw center logo 
      // NOTE ON TEXT: This QR code uses an image in the center, not text. 
      // If you want text instead, you would replace ctx.drawImage below with ctx.fillText("Your Text", logoX, logoY + 15);
      // and use ctx.font = "20px Arial", ctx.textAlign = "center" to control text size and position.
      const img = new Image();
      img.onload = () => {
        // EDIT LOGO SIZE: Adjust '0.25' (25% of QR code size). Make it larger (e.g. 0.30) or smaller (e.g. 0.15).
        const logoSize = finalSize * 0.25;

        // EDIT LOGO POSITION: Calculates exact center. Add offsets like `+ 10` or `- 10` to move the logo around.
        const logoX = (finalSize - logoSize) / 2;
        const logoY = (finalSize - logoSize) / 2;

        // EDIT LOGO BACKGROUND & OPACITY: Change '#FFFFFF' to a transparent color or different hex color if needed.
        ctx.fillStyle = '#FFFFFF';
        ctx.beginPath();

        // EDIT PADDING AROUND LOGO: Change '2' to increase or decrease the white border box around the center logo.
        const padding = 2;
        const radius = 4; // Rounded corners for the logo cutout
        if (ctx.roundRect) {
          ctx.roundRect(logoX - padding, logoY - padding, logoSize + padding * 2, logoSize + padding * 2, radius);
        } else {
          ctx.rect(logoX - padding, logoY - padding, logoSize + padding * 2, logoSize + padding * 2);
        }
        ctx.fill();

        // EDIT LOGO OPACITY: Set ctx.globalAlpha = 0.5 here for 50% opacity, then reset to 1.0 after ctx.drawImage.
        ctx.drawImage(img, logoX, logoY, logoSize, logoSize);
        if (onComplete) onComplete();
      };
      img.onerror = () => {
        if (onComplete) onComplete();
      };
      let logoSrc = '../assets/img/logo/logo-icon-black.png';
      const existingLogo = document.querySelector('img[src*="logo.png"], img[src*="logo-black.png"]');
      if (existingLogo) {
        logoSrc = existingLogo.src.replace('logo.png', 'logo-icon-black.png').replace('logo-black.png', 'logo-icon-black.png');
      }
      img.src = logoSrc;

    } catch (err) {
      console.error('QR custom gen error:', err);
      // Fallback
      if (QRCode.toCanvas) {
        // EDIT FALLBACK MARGIN & SIZE: Adjust 'margin: 1' and 'width: size' here if custom drawing fails.
        QRCode.toCanvas(canvas, text, { width: size, margin: 1, errorCorrectionLevel: 'H' });
      }
      if (onComplete) onComplete();
    }
  } else if (typeof QRCode !== 'undefined' && QRCode.toCanvas) {
    // EDIT FALLBACK MARGIN & SIZE: Adjust 'margin: 1' and 'width: size' here if custom drawing isn't supported.
    QRCode.toCanvas(canvas, text, { width: size, margin: 1, errorCorrectionLevel: 'H' }, function (err) {
      if (err) console.error('QR gen error:', err);
      if (onComplete) onComplete();
    });
  } else {
    if (onComplete) onComplete();
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
      generateQRCanvas(canvas, text, size, () => {
        resolve(canvas.toDataURL('image/png'));
      });
    } catch (err) {
      reject(err);
    }
  });
}
