<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');

$pageTitle = 'Attendance Management';
$pdo       = getDB();
$wid       = $_SESSION['warehouse_id'];
$today     = date('Y-m-d');

// Auto-migrate on page load too (safety net)
try { $pdo->query("SELECT id FROM attendance_settings LIMIT 0"); }
catch (PDOException $e) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `attendance_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `warehouse_id` INT(11) NOT NULL,
        `attend_time` TIME NOT NULL DEFAULT '09:00:00',
        `qr_token` VARCHAR(64) NOT NULL,
        `token_date` DATE NOT NULL,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_wh` (`warehouse_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
try { $pdo->query("SELECT latitude FROM dsr_attendance LIMIT 0"); }
catch (PDOException $e) {
    $pdo->exec("ALTER TABLE `dsr_attendance`
        ADD COLUMN `latitude`  DECIMAL(10,7) NULL DEFAULT NULL,
        ADD COLUMN `longitude` DECIMAL(10,7) NULL DEFAULT NULL,
        ADD COLUMN `note`      VARCHAR(255)  NULL DEFAULT NULL");
}

// Fetch current settings
$stRow = $pdo->prepare("SELECT * FROM attendance_settings WHERE warehouse_id=?");
$stRow->execute([$wid]);
$settings = $stRow->fetch() ?: ['attend_time' => '09:00:00', 'qr_token' => null, 'token_date' => null];

// Fetch DSRs for manual attend modal
$dsrList = $pdo->prepare("SELECT d.id as dsr_id, u.name, u.phone FROM dsr d JOIN users u ON u.id=d.user_id WHERE d.warehouse_id=? AND d.status=1 ORDER BY u.name");
$dsrList->execute([$wid]); $dsrList = $dsrList->fetchAll();

$attendTime = substr($settings['attend_time'] ?? '09:00:00', 0, 5); // HH:MM
$qrToken    = $settings['qr_token'] ?? '';
$tokenDate  = $settings['token_date'] ?? '';
$qrValid    = ($tokenDate === $today && $qrToken !== '');

include __DIR__ . '/../includes/header.php';
?>
<!-- Extra print styles -->
<style>
  @media print {
    body * { visibility: hidden !important; }
    #printArea, #printArea * { visibility: visible !important; }
    #printArea { position: fixed; top: 0; left: 0; width: 100%; padding: 20px; }
  }
  .att-status-present  { background:#dcfce7; color:#166534; }
  .att-status-absent   { background:#fee2e2; color:#991b1b; }
  .att-status-late     { background:#fef9c3; color:#854d0e; }
  .qr-box { border:2px dashed #a5b4fc; border-radius:16px; padding:24px; background:#f5f3ff;
             display:flex; flex-direction:column; align-items:center; gap:12px; }
  .map-link { display:inline-flex; align-items:center; gap:4px; color:#4f46e5;
              font-size:11px; text-decoration:none; }
  .map-link:hover { text-decoration:underline; }
</style>

<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">

      <!-- Page Header -->
      <div class="flex flex-wrap items-center justify-between mb-6 gap-3">
        <div>
          <h2 class="text-xl font-bold text-gray-800"><i class="fa-solid fa-user-clock text-indigo-500 mr-2"></i>Attendance Management</h2>
          <p class="text-sm text-gray-500">Set check-in time, generate daily QR, and manage DSR attendance.</p>
        </div>
        <div class="flex gap-2">
          <input type="date" id="filterDate" value="<?= $today ?>" class="form-input text-sm" onchange="loadAttendance()">
          <button onclick="openManualModal()" class="btn btn-success btn-sm"><i class="fa-solid fa-plus mr-1"></i>Manual Attend</button>
        </div>
      </div>

      <!-- Top Row: Settings + QR -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        <!-- Attend Time Settings Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <h3 class="font-semibold text-gray-700 mb-4"><i class="fa-regular fa-clock text-indigo-500 mr-2"></i>Check-In Time Settings</h3>
          <div class="flex gap-3 items-end">
            <div class="flex-1">
              <label class="form-label">Set Attend Time (deadline)</label>
              <input type="time" id="attendTime" value="<?= htmlspecialchars($attendTime) ?>" class="form-input text-lg font-mono" />
            </div>
            <button onclick="saveSettings()" class="btn btn-primary" id="saveBtn">
              <i class="fa-solid fa-floppy-disk mr-1"></i>Save & Generate QR
            </button>
          </div>
          <p class="text-xs text-gray-400 mt-3"><i class="fa-solid fa-circle-info mr-1"></i>Saving will generate a new QR code valid for today. Share it with DSRs to mark attendance.</p>

          <!-- Stats row -->
          <div class="grid grid-cols-3 gap-3 mt-5">
            <div class="rounded-lg bg-green-50 p-3 text-center">
              <div class="text-2xl font-bold text-green-700" id="cntPresent">—</div>
              <div class="text-xs text-green-600 mt-1">Present</div>
            </div>
            <div class="rounded-lg bg-yellow-50 p-3 text-center">
              <div class="text-2xl font-bold text-yellow-700" id="cntLate">—</div>
              <div class="text-xs text-yellow-600 mt-1">Late</div>
            </div>
            <div class="rounded-lg bg-red-50 p-3 text-center">
              <div class="text-2xl font-bold text-red-700" id="cntAbsent">—</div>
              <div class="text-xs text-red-600 mt-1">Absent</div>
            </div>
          </div>
        </div>

        <!-- QR Code Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-700"><i class="fa-solid fa-qrcode text-indigo-500 mr-2"></i>Daily QR Code</h3>
            <div class="flex gap-2">
              <button onclick="regenerateQR()" class="btn btn-ghost btn-sm" title="Regenerate"><i class="fa-solid fa-rotate-right mr-1"></i>Refresh</button>
              <button onclick="printQR()" class="btn btn-primary btn-sm"><i class="fa-solid fa-print mr-1"></i>Print</button>
            </div>
          </div>

          <div id="qrWrap" class="qr-box">
            <?php if ($qrValid): ?>
              <div id="qrCanvas"></div>
              <div class="text-center">
                <div class="text-xs font-mono text-indigo-600 break-all" id="qrTokenDisplay"><?= htmlspecialchars($qrToken) ?></div>
                <div class="text-xs text-gray-400 mt-1">Valid for: <strong><?= $today ?></strong> &nbsp;|&nbsp; Check-in by: <strong><?= htmlspecialchars($attendTime) ?></strong></div>
              </div>
            <?php else: ?>
              <div class="text-center text-gray-400 py-4">
                <i class="fa-solid fa-qrcode text-5xl mb-3 opacity-20"></i>
                <p class="text-sm">No QR for today yet.</p>
                <p class="text-xs mt-1">Set the attend time and click <strong>Save & Generate QR</strong>.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Attendance List -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 class="font-semibold text-gray-700"><i class="fa-solid fa-list-check text-indigo-500 mr-2"></i>Attendance List</h3>
          <span id="listDate" class="text-xs text-gray-400"><?= $today ?></span>
        </div>
        <div class="overflow-x-auto">
          <table class="data-table" id="attendTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Role</th>
                <th>Check-In Time</th>
                <th>Location</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="attendBody">
              <tr><td colspan="7" class="text-center py-8 text-gray-400">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /page-body -->
  </div>
</div>

<!-- ───── PRINT AREA ───────────────────────────────────────────── -->
<div id="printArea" style="display:none">
  <div style="font-family:'Inter',sans-serif; text-align:center; padding:30px;">
    <h2 style="font-size:20px; font-weight:700; color:#1e1b4b; margin-bottom:4px;">Happy Bangladesh</h2>
    <p style="font-size:13px; color:#4b5563; margin-bottom:16px;">Daily Attendance QR Code</p>
    <div id="printQrCanvas" style="display:inline-block; padding:16px; border:2px solid #c7d2fe; border-radius:12px; margin-bottom:14px;"></div>
    <p style="font-size:12px; color:#374151; margin:4px 0;">Date: <strong id="printDate"></strong></p>
    <p style="font-size:12px; color:#374151; margin:4px 0;">Check-in Deadline: <strong id="printTime"></strong></p>
    <p style="font-size:10px; color:#9ca3af; margin-top:12px;">Scan with Happy Bangladesh App to mark attendance</p>
  </div>
</div>

<!-- ───── EDIT MODAL ──────────────────────────────────────────── -->
<div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" style="display:none!important">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-gray-800"><i class="fa-solid fa-pen-to-square text-indigo-500 mr-2"></i>Edit Attendance</h3>
      <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-xl"></i></button>
    </div>
    <input type="hidden" id="editId" />
    <div class="space-y-4">
      <div>
        <label class="form-label">Employee Name</label>
        <input id="editName" class="form-input bg-gray-50" readonly />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="form-label">Check-In Time</label>
          <input type="time" id="editTime" class="form-input" step="1" />
        </div>
        <div>
          <label class="form-label">Status</label>
          <select id="editStatus" class="form-input">
            <option value="present">Present</option>
            <option value="late">Late</option>
            <option value="absent">Absent</option>
          </select>
        </div>
      </div>
      <div>
        <label class="form-label">Note (optional)</label>
        <input type="text" id="editNote" class="form-input" placeholder="Any remark…" />
      </div>
    </div>
    <div class="flex gap-2 mt-5">
      <button onclick="saveEdit()" class="btn btn-primary flex-1"><i class="fa-solid fa-check mr-1"></i>Save Changes</button>
      <button onclick="closeModal('editModal')" class="btn btn-ghost">Cancel</button>
    </div>
  </div>
</div>

<!-- ───── MANUAL ATTEND MODAL ─────────────────────────────────── -->
<div id="manualModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" style="display:none!important">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-gray-800"><i class="fa-solid fa-user-check text-green-500 mr-2"></i>Manual Attendance</h3>
      <button onclick="closeModal('manualModal')" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-xl"></i></button>
    </div>
    <div class="space-y-4">
      <div>
        <label class="form-label">Select DSR / Employee</label>
        <select id="manualDsr" class="form-input">
          <option value="">— Select DSR —</option>
          <?php foreach ($dsrList as $d): ?>
            <option value="<?= $d['dsr_id'] ?>"><?= htmlspecialchars($d['name']) ?> <?= $d['phone'] ? '('.$d['phone'].')' : '' ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="form-label">Date</label>
          <input type="date" id="manualDate" class="form-input" value="<?= $today ?>" />
        </div>
        <div>
          <label class="form-label">Check-In Time</label>
          <input type="time" id="manualTime" class="form-input" value="<?= date('H:i') ?>" />
        </div>
      </div>
    </div>
    <div class="flex gap-2 mt-5">
      <button onclick="submitManual()" class="btn btn-success flex-1"><i class="fa-solid fa-check mr-1"></i>Mark Attend</button>
      <button onclick="closeModal('manualModal')" class="btn btn-ghost">Cancel</button>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
const API     = '<?= rootPath() ?>/api/attendance.php';
const TODAY   = '<?= $today ?>';
let currentToken = '<?= addslashes($qrToken) ?>';
let attendTime   = '<?= htmlspecialchars($attendTime) ?>';

// ── Render QR on load if token exists ──────────────────────────
window.addEventListener('DOMContentLoaded', () => {
  if (currentToken) renderQR(currentToken, 'qrCanvas', 220);
  loadAttendance();
});

function renderQR(token, containerId, size = 220) {
  const el = document.getElementById(containerId);
  if (!el) return;
  el.innerHTML = '';
  const payload = JSON.stringify({ token, wh: '<?= $wid ?>' });
  new QRCode(el, { text: payload, width: size, height: size,
    colorDark: '#1e1b4b', colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M });
}

// ── Save settings & generate QR ───────────────────────────────
async function saveSettings() {
  const t = document.getElementById('attendTime').value;
  if (!t) return showToast('Please set a time', 'error');

  document.getElementById('saveBtn').disabled = true;
  const res = await api(API + '?action=save_settings', 'POST', { attend_time: t });
  document.getElementById('saveBtn').disabled = false;

  if (!res.success) return showToast(res.message || 'Error', 'error');
  currentToken = res.data.token;
  attendTime   = res.data.attend_time.substring(0,5);
  showToast('Settings saved & QR generated!');

  // Refresh QR area
  document.getElementById('qrWrap').innerHTML = `
    <div id="qrCanvas"></div>
    <div class="text-center">
      <div class="text-xs font-mono text-indigo-600 break-all" id="qrTokenDisplay">${currentToken}</div>
      <div class="text-xs text-gray-400 mt-1">Valid for: <strong>${TODAY}</strong> &nbsp;|&nbsp; Check-in by: <strong>${attendTime}</strong></div>
    </div>`;
  renderQR(currentToken, 'qrCanvas', 220);
}

// ── Regenerate QR ─────────────────────────────────────────────
async function regenerateQR() {
  const res = await api(API + '?action=regenerate_qr', 'POST', {});
  if (!res.success) return showToast(res.message || 'Error', 'error');
  currentToken = res.data.token;
  showToast('QR refreshed!');
  document.getElementById('qrCanvas') && renderQR(currentToken, 'qrCanvas', 220);
  const disp = document.getElementById('qrTokenDisplay');
  if (disp) disp.textContent = currentToken;
}

// ── Print QR ──────────────────────────────────────────────────
function printQR() {
  if (!currentToken) return showToast('No QR yet. Save settings first.', 'error');
  const area = document.getElementById('printArea');
  area.style.display = 'block';
  document.getElementById('printDate').textContent = TODAY;
  document.getElementById('printTime').textContent = attendTime;
  renderQR(currentToken, 'printQrCanvas', 280);
  setTimeout(() => { window.print(); area.style.display = 'none'; }, 400);
}

// ── Load attendance list ───────────────────────────────────────
async function loadAttendance() {
  const date = document.getElementById('filterDate').value;
  document.getElementById('listDate').textContent = date;
  document.getElementById('attendBody').innerHTML = '<tr><td colspan="7" class="text-center py-6 text-gray-400">Loading…</td></tr>';

  const res = await api(`${API}?action=list&date=${date}`);
  if (!res.success) return;

  const rows = res.data || [];
  let present = 0, late = 0, absent = 0;
  rows.forEach(r => {
    if (r.status === 'present') present++;
    else if (r.status === 'late') late++;
    else absent++;
  });
  document.getElementById('cntPresent').textContent = present;
  document.getElementById('cntLate').textContent    = late;
  document.getElementById('cntAbsent').textContent  = absent;

  if (!rows.length) {
    document.getElementById('attendBody').innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-400"><i class="fa-regular fa-calendar-xmark text-2xl mb-2 block opacity-40"></i>No attendance records for this date</td></tr>';
    return;
  }

  document.getElementById('attendBody').innerHTML = rows.map((r, i) => {
    const statusCls = `att-status-${r.status}`;
    const statusLabel = r.status.charAt(0).toUpperCase() + r.status.slice(1);
    let locHtml = '<span class="text-gray-300 text-xs">—</span>';
    if (r.latitude && r.longitude) {
      locHtml = `<a href="https://www.google.com/maps?q=${r.latitude},${r.longitude}" target="_blank" class="map-link">
        <i class="fa-solid fa-location-dot text-indigo-400"></i>
        ${parseFloat(r.latitude).toFixed(4)}, ${parseFloat(r.longitude).toFixed(4)}
      </a>`;
    }
    return `<tr>
      <td class="font-mono text-xs text-gray-400">${i+1}</td>
      <td class="font-medium">${esc(r.name)}</td>
      <td><span class="badge badge-gray text-xs">${esc(r.role?.toUpperCase())}</span></td>
      <td class="font-mono text-sm">${r.checkin_time ?? '—'}</td>
      <td>${locHtml}</td>
      <td><span class="px-2 py-0.5 rounded-full text-xs font-semibold ${statusCls}">${statusLabel}</span></td>
      <td>
        <div class="flex gap-1 flex-wrap">
          <button onclick="openManualForDsr(${r.id}, '${esc(r.name)}', '${r.checkin_time ?? ''}', '${r.status}', '${esc(r.note ?? '')}')"
                  class="btn btn-warning btn-sm" title="Mark Attend"><i class="fa-solid fa-user-check"></i></button>
          <button onclick="openEdit(${r.id}, '${esc(r.name)}', '${r.checkin_time ?? ''}', '${r.status}', '${esc(r.note ?? '')}')"
                  class="btn btn-ghost btn-sm" title="Edit"><i class="fa-solid fa-pen"></i></button>
          <button onclick="deleteRecord(${r.id})" class="btn btn-danger btn-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

// ── Edit record ───────────────────────────────────────────────
function openEdit(id, name, time, status, note) {
  document.getElementById('editId').value     = id;
  document.getElementById('editName').value   = name;
  document.getElementById('editTime').value   = time ? time.substring(0,5) : '';
  document.getElementById('editStatus').value = status;
  document.getElementById('editNote').value   = note;
  showModal('editModal');
}

async function saveEdit() {
  const id     = document.getElementById('editId').value;
  const time   = document.getElementById('editTime').value + ':00';
  const status = document.getElementById('editStatus').value;
  const note   = document.getElementById('editNote').value;
  const res    = await api(API + '?action=edit', 'POST', { id, checkin_time: time, status, note });
  if (res.success) { showToast('Updated!'); closeModal('editModal'); loadAttendance(); }
  else showToast(res.message || 'Error', 'error');
}

// ── Delete record ─────────────────────────────────────────────
async function deleteRecord(id) {
  if (!confirm('Delete this attendance record?')) return;
  const res = await api(API + '?action=delete', 'POST', { id });
  if (res.success) { showToast('Deleted'); loadAttendance(); }
  else showToast(res.message || 'Error', 'error');
}

// ── Manual attend modal ───────────────────────────────────────
function openManualModal() { showModal('manualModal'); }

async function submitManual() {
  const dsr_id = document.getElementById('manualDsr').value;
  const date   = document.getElementById('manualDate').value;
  const time   = document.getElementById('manualTime').value + ':00';
  if (!dsr_id) return showToast('Please select a DSR', 'error');
  const res = await api(API + '?action=manual_attend', 'POST', { dsr_id, date, time });
  if (res.success) { showToast('Attendance marked!'); closeModal('manualModal'); loadAttendance(); }
  else showToast(res.message || 'Error', 'error');
}

// ── Helpers ───────────────────────────────────────────────────
function esc(s) { return String(s ?? '').replace(/'/g, "\\'"); }

function showModal(id) {
  document.getElementById(id).style.cssText = 'display:flex!important';
}
function closeModal(id) {
  document.getElementById(id).style.cssText = 'display:none!important';
}
// Close on backdrop click
['editModal','manualModal'].forEach(id => {
  document.getElementById(id).addEventListener('click', function(e) {
    if (e.target === this) closeModal(id);
  });
});
</script>
