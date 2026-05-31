<?php
// api/delivery.php — QR scan validation + dispatch creation
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole(['admin','manager']);

$pdo    = getDB();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'pending_srs' && $method === 'GET') {
    $stmt = $pdo->prepare("SELECT DISTINCT s.id, u.name as sr_name FROM orders o JOIN sr s ON s.id=o.sr_id JOIN users u ON u.id=s.user_id WHERE o.status='pending' ORDER BY u.name ASC");
    $stmt->execute();
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'order_items' && $method === 'GET') {
    $sr_ids_str = $_GET['sr_ids'] ?? '';
    if (empty($sr_ids_str)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    $sr_ids = explode(',', $sr_ids_str);
    $inQuery = implode(',', array_fill(0, count($sr_ids), '?'));
    $stmt = $pdo->prepare("SELECT oi.product_id, p.name as product_name, p.pieces_per_box, SUM(oi.qty_pieces) as qty_pieces FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id WHERE o.sr_id IN ($inQuery) AND o.status='pending' GROUP BY oi.product_id");
    $stmt->execute($sr_ids);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'scan_box' && $method === 'POST') {
    $d        = json_decode(file_get_contents('php://input'), true);
    $qr_uid   = trim($d['qr_uid'] ?? '');
    $sr_ids   = $d['sr_ids'] ?? [];
    $scanned  = $d['scanned_ids'] ?? []; // already scanned qr ids in this session

    // Find QR code
    $stmt = $pdo->prepare('SELECT qr.*, p.name as product_name, p.pieces_per_box FROM qr_codes qr JOIN products p ON p.id=qr.product_id WHERE qr.qr_uid=?');
    $stmt->execute([$qr_uid]);
    $qr = $stmt->fetch();

    if (!$qr) { echo json_encode(['success' => false, 'type' => 'not_found', 'message' => 'QR code not found']); exit; }
    if (!in_array($qr['status'], ['active', 'returned'])) { echo json_encode(['success' => false, 'type' => 'not_active', 'message' => 'QR code is ' . $qr['status']]); exit; }
    if (in_array($qr['id'], $scanned)) { echo json_encode(['success' => false, 'type' => 'duplicate', 'message' => 'Already scanned']); exit; }

    if (empty($sr_ids)) { echo json_encode(['success' => false, 'type' => 'wrong_product', 'message' => 'No SR selected']); exit; }

    // Check product belongs to any pending order of selected SRs
    $inQuery = implode(',', array_fill(0, count($sr_ids), '?'));
    $check = $pdo->prepare("SELECT oi.id FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.sr_id IN ($inQuery) AND o.status='pending' AND oi.product_id=?");
    $params = array_merge($sr_ids, [$qr['product_id']]);
    $check->execute($params);
    if (!$check->fetch()) { echo json_encode(['success' => false, 'type' => 'wrong_product', 'message' => 'This product is not needed by the selected SRs']); exit; }

    echo json_encode(['success' => true, 'data' => $qr]);
    exit;
}

if ($action === 'complete' && $method === 'POST') {
    $d        = json_decode(file_get_contents('php://input'), true);
    $sr_ids   = $d['sr_ids'] ?? [];
    $dsr_id   = intval($d['dsr_id'] ?? 0);
    $scanned  = $d['scanned'] ?? []; // [{qr_id, product_id, pieces_total}]

    // Get manager_id
    $mgr = $pdo->prepare('SELECT id FROM managers WHERE user_id=? LIMIT 1');
    $mgr->execute([$_SESSION['user_id']]);
    $manager_id  = $mgr->fetchColumn() ?: 0;
    $wid         = $_SESSION['warehouse_id'] ?? 0;

    $pdo->beginTransaction();

    if (empty($sr_ids)) { 
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'No SR selected']); 
        exit; 
    }

    $inQuery = implode(',', array_fill(0, count($sr_ids), '?'));
    $ordersStmt = $pdo->prepare("SELECT id FROM orders WHERE sr_id IN ($inQuery) AND status='pending' ORDER BY id ASC");
    $ordersStmt->execute($sr_ids);
    $pending_orders = $ordersStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($pending_orders)) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'No pending orders found for selected SRs']);
        exit;
    }

    $insertDispatch = $pdo->prepare('INSERT INTO dispatches (dsr_id, order_id, warehouse_id, manager_id, dispatch_date, status) VALUES (?,?,?,?,CURDATE(),"loaded")');
    $insertDispatchItem = $pdo->prepare('INSERT INTO dispatch_items (dispatch_id, order_id, qr_code_id, product_id, qty_out) VALUES (?,?,?,?,?)');
    $updateQR = $pdo->prepare("UPDATE qr_codes SET status='dispatched', pieces_remaining=0 WHERE id=?");
    $updateOrder = $pdo->prepare("UPDATE orders SET status='out_for_delivery' WHERE id=?");

    $reqStmt = $pdo->prepare("SELECT order_id, product_id, qty_pieces FROM order_items WHERE order_id IN (" . implode(',', array_fill(0, count($pending_orders), '?')) . ")");
    $reqStmt->execute($pending_orders);
    $orderReqs = [];
    foreach ($reqStmt->fetchAll() as $row) {
        $orderReqs[$row['order_id']][$row['product_id']] = $row['qty_pieces'];
    }

    $dispatchMap = []; 
    foreach ($pending_orders as $oid) {
        $insertDispatch->execute([$dsr_id, $oid, $wid, $manager_id]);
        $dispatchMap[$oid] = $pdo->lastInsertId();
    }

    $scannedByProduct = [];
    foreach ($scanned as $box) {
        $scannedByProduct[$box['product_id']][] = $box;
    }

    $inventoryUpdates = [];

    foreach ($scannedByProduct as $pid => $boxes) {
        foreach ($boxes as $box) {
            $allocated = false;
            foreach ($pending_orders as $oid) {
                if (isset($orderReqs[$oid][$pid]) && $orderReqs[$oid][$pid] > 0) {
                    $orderReqs[$oid][$pid] -= $box['pieces_total'];
                    
                    $insertDispatchItem->execute([$dispatchMap[$oid], $oid, $box['qr_id'], $pid, $box['pieces_total']]);
                    $updateQR->execute([$box['qr_id']]);
                    
                    if (!isset($inventoryUpdates[$pid])) $inventoryUpdates[$pid] = 0;
                    $inventoryUpdates[$pid] += $box['pieces_total'];

                    $allocated = true;
                    break;
                }
            }
            if (!$allocated) {
                $firstOid = $pending_orders[0];
                $insertDispatchItem->execute([$dispatchMap[$firstOid], $firstOid, $box['qr_id'], $pid, $box['pieces_total']]);
                $updateQR->execute([$box['qr_id']]);
                
                if (!isset($inventoryUpdates[$pid])) $inventoryUpdates[$pid] = 0;
                $inventoryUpdates[$pid] += $box['pieces_total'];
            }
        }
    }

    foreach ($inventoryUpdates as $pid => $total_pieces) {
        $ppbStmt = $pdo->prepare('SELECT pieces_per_box FROM products WHERE id=?');
        $ppbStmt->execute([$pid]);
        $pieces_per_box = max((int)$ppbStmt->fetchColumn(), 1);

        $inv = $pdo->prepare('SELECT qty_boxes, qty_pieces FROM inventory WHERE product_id=? AND warehouse_id=?');
        $inv->execute([$pid, $wid]);
        $row = $inv->fetch();
        if ($row) {
            $total_pieces_now = ($row['qty_boxes'] * $pieces_per_box) + $row['qty_pieces'];
            $new_total = max($total_pieces_now - $total_pieces, 0);
            
            $new_boxes = floor($new_total / $pieces_per_box);
            $new_pieces = $new_total % $pieces_per_box;

            $pdo->prepare('UPDATE inventory SET qty_boxes=?, qty_pieces=? WHERE product_id=? AND warehouse_id=?')
                ->execute([$new_boxes, $new_pieces, $pid, $wid]);
            logInventoryActivity($pdo, $pid, $wid, 'dispatch', $dispatchMap[$pending_orders[0]], 0, -$total_pieces, "Dispatched for multi-orders via SR");
        }
    }

    foreach ($pending_orders as $oid) {
        $updateOrder->execute([$oid]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Orders sent to van!']);
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
