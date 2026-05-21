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
    $stmt = $pdo->prepare("SELECT d.id, d.dispatch_date, u.name as dsr_name, o.order_date FROM dispatches d JOIN dsr ds ON ds.id=d.dsr_id JOIN users u ON u.id=ds.user_id LEFT JOIN orders o ON o.id=d.order_id LEFT JOIN returns r ON r.dispatch_id=d.id WHERE d.warehouse_id=? AND d.status IN ('loaded','delivered') AND r.id IS NULL ORDER BY d.id DESC");
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
        $remaining = $pdo->prepare('SELECT pieces_remaining, pieces_total FROM qr_codes WHERE id=?');
        $remaining->execute([$item['qr_code_id']]);
        $qrRow = $remaining->fetch();
        $rem = intval($qrRow['pieces_remaining']) + intval($item['qty_in']);
        
        $newStatus = 'active';

        $pdo->prepare('UPDATE qr_codes SET pieces_remaining=?, status=? WHERE id=?')->execute([$rem, $newStatus, $item['qr_code_id']]);

        // Add back to inventory (recalculate boxes and pieces)
        $ppbStmt = $pdo->prepare('SELECT pieces_per_box FROM products WHERE id=?');
        $ppbStmt->execute([$item['product_id']]);
        $pieces_per_box = max((int)$ppbStmt->fetchColumn(), 1);

        $inv = $pdo->prepare('SELECT qty_boxes, qty_pieces FROM inventory WHERE product_id=? AND warehouse_id=?');
        $inv->execute([$item['product_id'], $wid]);
        $row = $inv->fetch();
        if ($row) {
            $total_pieces_now = ($row['qty_boxes'] * $pieces_per_box) + $row['qty_pieces'];
            $new_total = $total_pieces_now + intval($item['qty_in']);
            
            $new_boxes = floor($new_total / $pieces_per_box);
            $new_pieces = $new_total % $pieces_per_box;

            $pdo->prepare('UPDATE inventory SET qty_boxes=?, qty_pieces=? WHERE product_id=? AND warehouse_id=?')
                ->execute([$new_boxes, $new_pieces, $item['product_id'], $wid]);
        } else {
            $new_boxes = floor(intval($item['qty_in']) / $pieces_per_box);
            $new_pieces = intval($item['qty_in']) % $pieces_per_box;
            $pdo->prepare('INSERT INTO inventory (product_id, warehouse_id, qty_boxes, qty_pieces) VALUES (?,?,?,?)')
                ->execute([$item['product_id'], $wid, $new_boxes, $new_pieces]);
        }
    }

    // Update dispatch status
    // Note: Do NOT set status='settled' here. The dispatch is only fully settled
    // after the cash settlement is submitted by DSR and approved by the manager.
    // $pdo->prepare("UPDATE dispatches SET status='settled' WHERE id=?")->execute([$did]);
    
    // Mark order as delivered
    $dispStmt = $pdo->prepare('SELECT order_id FROM dispatches WHERE id=?');
    $dispStmt->execute([$did]);
    $order_id = $dispStmt->fetchColumn();
    if ($order_id) {
        $pdo->prepare("UPDATE orders SET status='delivered' WHERE id=?")->execute([$order_id]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Return completed']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
