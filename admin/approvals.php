<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');
$pageTitle = 'Approval Queue';
$pdo = getDB();

$pending_count    = $pdo->query("SELECT COUNT(*) FROM pending_approvals WHERE status='pending'")->fetchColumn();
$approvals = $pdo->query("
    SELECT pa.*, u.name AS requested_by_name, ru.name AS reviewed_by_name,
           CASE pa.action_type
             WHEN 'edit_order'   THEN CONCAT('Edit Order #', LPAD(pa.target_id,4,'0'))
             WHEN 'cancel_order' THEN CONCAT('Cancel Order #', LPAD(pa.target_id,4,'0'))
             WHEN 'edit_dispatch' THEN CONCAT('Edit Dispatch #', pa.target_id)
           END as action_label
    FROM pending_approvals pa
    JOIN users u ON u.id = pa.requested_by
    LEFT JOIN users ru ON ru.id = pa.reviewed_by
    ORDER BY CASE pa.status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 ELSE 3 END, pa.requested_at DESC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="page-body">

      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-xl font-bold text-gray-800">Approval Queue</h2>
          <p class="text-sm text-gray-500">Review and approve manager edit/cancel requests</p>
        </div>
        <?php if ($pending_count > 0): ?>
        <div class="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-4 py-2">
          <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
          <span class="text-amber-700 font-semibold text-sm"><?= $pending_count ?> pending approval<?= $pending_count > 1 ? 's' : '' ?></span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Filter Tabs -->
      <div class="flex gap-2 mb-4">
        <button onclick="filterApprovals('all')" id="tab-all" class="px-4 py-2 rounded-lg text-sm font-semibold border border-gray-200 bg-indigo-600 text-white transition">All</button>
        <button onclick="filterApprovals('pending')" id="tab-pending" class="px-4 py-2 rounded-lg text-sm font-semibold border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition">
          Pending <span class="ml-1 bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full text-xs"><?= $pending_count ?></span>
        </button>
        <button onclick="filterApprovals('approved')" id="tab-approved" class="px-4 py-2 rounded-lg text-sm font-semibold border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition">Approved</button>
        <button onclick="filterApprovals('rejected')" id="tab-rejected" class="px-4 py-2 rounded-lg text-sm font-semibold border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition">Rejected</button>
      </div>

      <!-- Approvals Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <?php if (empty($approvals)): ?>
        <div class="text-center py-16 text-gray-400">
          <i class="fa-solid fa-check-circle text-4xl mb-3 block text-green-300"></i>
          <p class="font-medium">No approval requests yet</p>
          <p class="text-sm mt-1">Manager edit/cancel requests will appear here</p>
        </div>
        <?php else: ?>
        <table class="data-table" id="approvals-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Action</th>
              <th>Summary</th>
              <th>Requested By</th>
              <th>Requested At</th>
              <th>Status</th>
              <th>Reviewed By</th>
              <th>Admin Notes</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($approvals as $a): ?>
            <tr data-status="<?= $a['status'] ?>">
              <td class="font-mono text-xs text-gray-500">#<?= $a['id'] ?></td>
              <td>
                <?php
                $typeIcon  = ['edit_order' => 'fa-pen', 'cancel_order' => 'fa-xmark', 'edit_dispatch' => 'fa-truck'];
                $typeColor = ['edit_order' => 'text-blue-600', 'cancel_order' => 'text-red-600', 'edit_dispatch' => 'text-purple-600'];
                $icon  = $typeIcon[$a['action_type']] ?? 'fa-question';
                $color = $typeColor[$a['action_type']] ?? 'text-gray-600';
                ?>
                <span class="font-medium <?= $color ?>">
                  <i class="fa-solid <?= $icon ?> mr-1"></i>
                  <?= htmlspecialchars($a['action_label']) ?>
                </span>
              </td>
              <td class="text-sm text-gray-600 max-w-xs truncate" title="<?= htmlspecialchars($a['summary']) ?>"><?= htmlspecialchars($a['summary'] ?: '—') ?></td>
              <td class="font-medium"><?= htmlspecialchars($a['requested_by_name']) ?></td>
              <td class="text-xs text-gray-500"><?= date('d M Y, H:i', strtotime($a['requested_at'])) ?></td>
              <td>
                <?php
                $sc = ['pending' => 'badge-warning', 'approved' => 'badge-success', 'rejected' => 'badge-danger'];
                echo '<span class="badge ' . ($sc[$a['status']] ?? 'badge-gray') . '">' . ucfirst($a['status']) . '</span>';
                ?>
              </td>
              <td class="text-sm"><?= $a['reviewed_by_name'] ? htmlspecialchars($a['reviewed_by_name']) : '—' ?></td>
              <td class="text-xs text-gray-500 max-w-xs truncate" title="<?= htmlspecialchars($a['admin_notes'] ?? '') ?>"><?= htmlspecialchars($a['admin_notes'] ?: '—') ?></td>
              <td>
                <?php if ($a['status'] === 'pending'): ?>
                <div class="flex gap-1.5">
                  <button onclick="showPayload(<?= $a['id'] ?>, <?= htmlspecialchars(json_encode($a['payload']), ENT_QUOTES) ?>)"
                    class="btn btn-ghost btn-sm" title="View details">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                  <button onclick="approveRequest(<?= $a['id'] ?>)" class="btn btn-success btn-sm" title="Approve">
                    <i class="fa-solid fa-check mr-1"></i>Approve
                  </button>
                  <button onclick="rejectRequest(<?= $a['id'] ?>)" class="btn btn-danger btn-sm" title="Reject">
                    <i class="fa-solid fa-xmark mr-1"></i>Reject
                  </button>
                </div>
                <?php else: ?>
                <button onclick="showPayload(<?= $a['id'] ?>, <?= htmlspecialchars(json_encode($a['payload']), ENT_QUOTES) ?>)"
                  class="btn btn-ghost btn-sm" title="View details">
                  <i class="fa-solid fa-eye"></i>
                </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<!-- Payload Modal -->
<div id="payload-modal" class="fixed inset-0 z-[200] hidden flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closePayloadModal()"></div>
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl relative z-10 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h3 class="font-bold text-gray-800">Request Details</h3>
      <button onclick="closePayloadModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-xl"></i></button>
    </div>
    <div class="p-6">
      <pre id="payload-content" class="bg-gray-50 rounded-xl p-4 text-xs text-gray-700 overflow-auto max-h-80 font-mono"></pre>
    </div>
  </div>
</div>

<!-- Reject Notes Modal -->
<div id="reject-modal" class="fixed inset-0 z-[200] hidden flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeRejectModal()"></div>
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative z-10 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h3 class="font-bold text-red-700"><i class="fa-solid fa-xmark-circle mr-2"></i>Reject Request</h3>
      <button onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-xl"></i></button>
    </div>
    <div class="p-6 space-y-4">
      <div>
        <label class="form-label">Reason for rejection (optional)</label>
        <textarea id="reject-notes" class="form-input" rows="3" placeholder="Explain why this request is rejected…"></textarea>
      </div>
      <div class="flex gap-3 justify-end">
        <button onclick="closeRejectModal()" class="btn btn-ghost">Cancel</button>
        <button onclick="confirmReject()" class="btn btn-danger"><i class="fa-solid fa-xmark mr-1"></i>Confirm Reject</button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
let rejectingId = null;

function filterApprovals(status) {
  const rows = document.querySelectorAll('#approvals-table tbody tr');
  rows.forEach(tr => {
    tr.style.display = (status === 'all' || tr.dataset.status === status) ? '' : 'none';
  });
  // Update tab styles
  ['all','pending','approved','rejected'].forEach(s => {
    const btn = document.getElementById('tab-' + s);
    if (!btn) return;
    if (s === status) {
      btn.className = 'px-4 py-2 rounded-lg text-sm font-semibold border border-indigo-600 bg-indigo-600 text-white transition';
    } else {
      btn.className = 'px-4 py-2 rounded-lg text-sm font-semibold border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition';
    }
  });
}

function showPayload(id, payloadStr) {
  let obj = payloadStr;
  if (typeof obj === 'string') { try { obj = JSON.parse(obj); } catch(e) {} }
  document.getElementById('payload-content').textContent = JSON.stringify(obj, null, 2);
  document.getElementById('payload-modal').classList.remove('hidden');
}
function closePayloadModal() { document.getElementById('payload-modal').classList.add('hidden'); }

async function approveRequest(id) {
  if (!confirm('Approve this request? The action will be executed immediately.')) return;
  const data = await api(`<?= rootPath() ?>/api/approvals.php?action=approve&id=${id}`, 'POST', { notes: '' });
  if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1200); }
  else showToast(data.message || 'Error', 'error');
}

function rejectRequest(id) {
  rejectingId = id;
  document.getElementById('reject-notes').value = '';
  document.getElementById('reject-modal').classList.remove('hidden');
}
function closeRejectModal() { document.getElementById('reject-modal').classList.add('hidden'); rejectingId = null; }

async function confirmReject() {
  const notes = document.getElementById('reject-notes').value;
  const data = await api(`<?= rootPath() ?>/api/approvals.php?action=reject&id=${rejectingId}`, 'POST', { notes });
  if (data.success) { showToast('Request rejected', 'info'); closeRejectModal(); setTimeout(() => location.reload(), 1200); }
  else showToast(data.message || 'Error', 'error');
}
</script>
