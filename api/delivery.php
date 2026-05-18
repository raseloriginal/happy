<?php
// api/delivery.php — QR scan validation + dispatch creation
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole(['admin','manager']);

$pdo    = getDB();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'pending_orders' && $method === 'GET') {
    $wid  = $_SESSION['warehouse_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT o.id, o.order_date, u.name as sr_name, c.name as company_name FROM orders o JOIN sr s ON s.id=o.sr_id JOIN users u ON u.id=s.user_id JOIN companies c ON c.id=o.company_id WHERE o.status='pending' ORDER BY o.id DESC");
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'order_items' && $method === 'GET') {
    $order_id = intval($_GET['order_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT oi.*, p.name as product_name, p.pieces_per_box FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?');
    $stmt->execute([$order_id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'scan_box' && $method === 'POST') {
    $d        = json_decode(file_get_contents('php://input'), true);
    $qr_uid   = trim($d['qr_uid'] ?? '');
    $order_id = intval($d['order_id'] ?? 0);
    $scanned  = $d['scanned_ids'] ?? []; // already scanned qr ids in this session

    // Find QR code
    $stmt = $pdo->prepare('SELECT qr.*, p.name as product_name, p.pieces_per_box FROM qr_codes qr JOIN products p ON p.id=qr.product_id WHERE qr.qr_uid=?');
    $stmt->execute([$qr_uid]);
    $qr = $stmt->fetch();

    if (!$qr) { echo json_encode(['success' => false, 'type' => 'not_found', 'message' => 'QR code not found']); exit; }
    if (!in_array($qr['status'], ['active', 'returned'])) { echo json_encode(['success' => false, 'type' => 'not_active', 'message' => 'QR code is ' . $qr['status']]); exit; }
    if (in_array($qr['id'], $scanned)) { echo json_encode(['success' => false, 'type' => 'duplicate', 'message' => 'Already scanned']); exit; }

    // Check product belongs to this order
    $check = $pdo->prepare('SELECT id FROM order_items WHERE order_id=? AND product_id=?');
    $check->execute([$order_id, $qr['product_id']]);
    if (!$check->fetch()) { echo json_encode(['success' => false, 'type' => 'wrong_product', 'message' => 'This product is not in this order']); exit; }

    echo json_encode(['success' => true, 'data' => $qr]);
    exit;
}

if ($action === 'complete' && $method === 'POST') {
    $d        = json_decode(file_get_contents('php://input'), true);
    $order_id = intval($d['order_id'] ?? 0);
    $dsr_id   = intval($d['dsr_id'] ?? 0);
    $scanned  = $d['scanned'] ?? []; // [{qr_id, product_id, pieces_total}]

    // Get manager_id
    $mgr = $pdo->prepare('SELECT id FROM managers WHERE user_id=? LIMIT 1');
    $mgr->execute([$_SESSION['user_id']]);
    $manager_id  = $mgr->fetchColumn() ?: 0;
    $wid         = $_SESSION['warehouse_id'] ?? 0;

    $pdo->beginTransaction();

    // Create dispatch
    $pdo->prepare('INSERT INTO dispatches (dsr_id, order_id, warehouse_id, manager_id, dispatch_date, status) VALUES (?,?,?,?,CURDATE(),"loaded")')
        ->execute([$dsr_id, $order_id, $wid, $manager_id]);
    $dispatch_id = $pdo->lastInsertId();

    foreach ($scanned as $item) {
        // Insert dispatch item
        $pdo->prepare('INSERT INTO dispatch_items (dispatch_id, order_id, qr_code_id, product_id, qty_out) VALUES (?,?,?,?,?)')
            ->execute([$dispatch_id, $order_id, $item['qr_id'], $item['product_id'], $item['pieces_total']]);
        // Mark QR as dispatched
        $pdo->prepare("UPDATE qr_codes SET status='dispatched', pieces_remaining=0 WHERE id=?")->execute([$item['qr_id']]);
        
        // Update inventory (subtract actual pieces dispatched)
        $ppbStmt = $pdo->prepare('SELECT pieces_per_box FROM products WHERE id=?');
        $ppbStmt->execute([$item['product_id']]);
        $pieces_per_box = max((int)$ppbStmt->fetchColumn(), 1);

        $inv = $pdo->prepare('SELECT qty_boxes, qty_pieces FROM inventory WHERE product_id=? AND warehouse_id=?');
        $inv->execute([$item['product_id'], $wid]);
        $row = $inv->fetch();
        if ($row) {
            $total_pieces_now = ($row['qty_boxes'] * $pieces_per_box) + $row['qty_pieces'];
            $new_total = max($total_pieces_now - intval($item['pieces_total']), 0);
            
            $new_boxes = floor($new_total / $pieces_per_box);
            $new_pieces = $new_total % $pieces_per_box;

            $pdo->prepare('UPDATE inventory SET qty_boxes=?, qty_pieces=? WHERE product_id=? AND warehouse_id=?')
                ->execute([$new_boxes, $new_pieces, $item['product_id'], $wid]);
        }
    }

    // Update order status
    $pdo->prepare("UPDATE orders SET status='out_for_delivery' WHERE id=?")->execute([$order_id]);
    $pdo->commit();

    echo json_encode(['success' => true, 'dispatch_id' => $dispatch_id, 'message' => 'Order sent to van!']);
    exit;
}

if ($action === 'dsrs' && $method === 'GET') {
    $wid  = $_SESSION['warehouse_id'] ?? 0;
    $stmt = $pdo->prepare('SELECT d.id, u.name FROM dsr d JOIN users u ON u.id=d.user_id WHERE d.warehouse_id=? AND d.status=1 ORDER BY u.name');
    $stmt->execute([$wid]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
