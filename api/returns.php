<?php
// api/returns.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole(['admin','manager']);

$pdo    = getDB();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'dispatches' && $method === 'GET') {
    $wid  = $_SESSION['warehouse_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT d.id, d.dispatch_date, u.name as dsr_name, o.order_date FROM dispatches d JOIN dsr ds ON ds.id=d.dsr_id JOIN users u ON u.id=ds.user_id LEFT JOIN orders o ON o.id=d.order_id WHERE d.warehouse_id=? AND d.status IN ('loaded','delivered') ORDER BY d.id DESC");
    $stmt->execute([$wid]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'dispatch_boxes' && $method === 'GET') {
    $did  = intval($_GET['dispatch_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT di.*, qr.qr_uid, qr.pieces_total, qr.pieces_remaining, qr.status as qr_status, p.name as product_name FROM dispatch_items di JOIN qr_codes qr ON qr.id=di.qr_code_id JOIN products p ON p.id=di.product_id WHERE di.dispatch_id=?');
    $stmt->execute([$did]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'complete' && $method === 'POST') {
    $d    = json_decode(file_get_contents('php://input'), true);
    $did  = intval($d['dispatch_id'] ?? 0);
    $items = $d['items'] ?? []; // [{qr_code_id, product_id, qty_in, type}]

    $mgr = $pdo->prepare('SELECT id FROM managers WHERE user_id=? LIMIT 1');
    $mgr->execute([$_SESSION['user_id']]);
    $manager_id = $mgr->fetchColumn() ?: 0;
    $wid        = $_SESSION['warehouse_id'] ?? 0;

    $pdo->beginTransaction();

    // Create return record
    $pdo->prepare('INSERT INTO returns (dispatch_id, manager_id, return_date, status) VALUES (?,?,CURDATE(),"completed")')
        ->execute([$did, $manager_id]);
    $return_id = $pdo->lastInsertId();

    foreach ($items as $item) {
        if (intval($item['qty_in']) <= 0) continue;
        $pdo->prepare('INSERT INTO return_items (return_id, qr_code_id, product_id, qty_in, type) VALUES (?,?,?,?,?)')
            ->execute([$return_id, $item['qr_code_id'], $item['product_id'], $item['qty_in'], $item['type'] ?? 'scan']);

        // Update qr_codes
        $remaining = $pdo->prepare('SELECT pieces_remaining FROM qr_codes WHERE id=?');
        $remaining->execute([$item['qr_code_id']]);
        $rem = intval($remaining->fetchColumn()) + intval($item['qty_in']);
        $total = $pdo->prepare('SELECT pieces_total FROM qr_codes WHERE id=?');
        $total->execute([$item['qr_code_id']]);
        $tot = intval($total->fetchColumn());
        $newStatus = ($rem >= $tot) ? 'returned' : 'dispatched';

        $pdo->prepare('UPDATE qr_codes SET pieces_remaining=?, status=? WHERE id=?')->execute([$rem, $newStatus, $item['qr_code_id']]);

        // Add back to inventory
        $pdo->prepare('UPDATE inventory SET qty_pieces = qty_pieces + ? WHERE product_id=? AND warehouse_id=?')
            ->execute([$item['qty_in'], $item['product_id'], $wid]);
    }

    // Update dispatch status
    $pdo->prepare("UPDATE dispatches SET status='settled' WHERE id=?")->execute([$did]);
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Return completed']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
