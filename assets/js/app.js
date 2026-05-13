// assets/js/app.js — Global JS helpers for Happy Bangladesh ERP

// ── Toast Notifications ────────────────────────────────────
const toastContainer = document.createElement('div');
toastContainer.className = 'toast-container';
document.body.appendChild(toastContainer);

function showToast(msg, type = 'success') {
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = msg;
  toastContainer.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

// ── AJAX Helper ────────────────────────────────────────────
async function api(url, method = 'GET', body = null) {
  const opts = { method, headers: {} };
  if (body instanceof FormData) {
    opts.body = body;
  } else if (body) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  }
  try {
    const res = await fetch(url, opts);
    const data = await res.json();
    return data;
  } catch (e) {
    console.error('API Error:', e);
    return { success: false, message: 'Network error' };
  }
}

// ── Boxes + Pieces Display Helper ─────────────────────────
function formatQty(totalPieces, piecesPerBox) {
  if (!piecesPerBox || piecesPerBox <= 0) return `${totalPieces} pcs`;
  const boxes = Math.floor(totalPieces / piecesPerBox);
  const pieces = totalPieces % piecesPerBox;
  if (pieces === 0) return `${boxes} box`;
  return `${boxes} box ${pieces} pcs`;
}

// ── Currency Format ────────────────────────────────────────
function formatCurrency(amount) {
  return '৳ ' + parseFloat(amount || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 });
}

// ── Confirm Delete ─────────────────────────────────────────
function confirmDelete(message) {
  return confirm(message || 'Are you sure you want to delete this record?');
}

// ── Modal helpers ──────────────────────────────────────────
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = 'flex';
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = 'none';
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.style.display = 'none';
  }
});

// ── Date helpers ───────────────────────────────────────────
function todayDate() {
  return new Date().toISOString().split('T')[0];
}

// ── Set default dates on all date inputs ─────────────────
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('input[type="date"]:not([value])').forEach(input => {
    input.value = todayDate();
  });
});
