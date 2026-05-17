<?php
// api/orders.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole(['admin','manager']);

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'sr_products') {
            $sr_id = intval($_GET['sr_id'] ?? 0);
            $stmt  = $pdo->prepare('SELECT p.id, p.name, p.pieces_per_box, p.selling_price FROM sr s JOIN products p ON p.company_id=s.company_id WHERE s.id=? AND p.status=1 ORDER BY p.name');
            $stmt->execute([$sr_id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } elseif ($action === 'stats') {
            $wid = $_SESSION['warehouse_id'] ?? 0;
            $grand  = $pdo->query("SELECT COALESCE(SUM(oi.qty_pieces * oi.unit_price),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id")->fetchColumn();
            $outPcs = $pdo->query("SELECT (SELECT COALESCE(SUM(qty_out),0) FROM dispatch_items) + (SELECT COALESCE(SUM(oi.qty_pieces),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.status='ready_sale')")->fetchColumn();
            $retPcs = $pdo->query("SELECT COALESCE(SUM(ri.qty_in),0) FROM return_items ri")->fetchColumn();
            $sellPcs = $outPcs - $retPcs;
            $avgDel = $outPcs > 0 ? round(($sellPcs / $outPcs) * 100, 1) : 0;
            echo json_encode(['success' => true, 'grand_total' => $grand, 'out_pcs' => $outPcs, 'back_pcs' => $retPcs, 'avg_delivery' => $avgDel]);
        } elseif ($action === 'scan_ready_sale') {
            $qr_uid = trim($_GET['qr_uid'] ?? '');
            $sr_id  = intval($_GET['sr_id'] ?? 0);

            if (!$qr_uid) {
                echo json_encode(['success' => false, 'message' => 'Missing QR UID']); exit;
            }

            // Find QR and Product
            $stmt = $pdo->prepare('SELECT qr.*, p.name as product_name, p.pieces_per_box, p.selling_price, p.company_id 
                                   FROM qr_codes qr 
                                   JOIN products p ON p.id=qr.product_id 
                                   WHERE qr.qr_uid=?');
            $stmt->execute([$qr_uid]);
            $qr = $stmt->fetch();

            if (!$qr) {
                echo json_encode(['success' => false, 'message' => 'QR code not found']); exit;
            }

            // Auto-detect SR if not provided
            if (!$sr_id) {
                $sr = $pdo->prepare('SELECT id FROM sr WHERE company_id=? AND status=1 LIMIT 1');
                $sr->execute([$qr['company_id']]);
                $sr_id = $sr->fetchColumn();
                if (!$sr_id) {
                    echo json_encode(['success' => false, 'message' => 'No active SR found for this product\'s company']); exit;
                }
            } else {
                // Validate existing SR matches company
                $sr = $pdo->prepare('SELECT company_id FROM sr WHERE id=?');
                $sr->execute([$sr_id]);
                $sr_company = $sr->fetchColumn();
                if ($qr['company_id'] != $sr_company) {
                    echo json_encode(['success' => false, 'message' => 'Product does not belong to the same company as previous items']); exit;
                }
            }

            if ($qr['status'] !== 'active') {
                echo json_encode(['success' => false, 'message' => 'QR code is ' . $qr['status']]); exit;
            }


            echo json_encode(['success' => true, 'data' => [
                'id' => $qr['product_id'],
                'name' => $qr['product_name'],
                'pieces_per_box' => $qr['pieces_per_box'],
                'selling_price' => $qr['selling_price'],
                'scanned_pieces' => $qr['pieces_remaining'],
                'qr_id' => $qr['id'],
                'sr_id' => $sr_id
            ]]);
            exit;
        } else {
            // List orders with filters
            $where = ['1=1'];
            $params = [];
            if (!empty($_GET['sr_id']))      { $where[] = 'o.sr_id=?';       $params[] = $_GET['sr_id']; }
            if (!empty($_GET['company_id'])) { $where[] = 'o.company_id=?';  $params[] = $_GET['company_id']; }
            if (!empty($_GET['status']))     { $where[] = 'o.status=?';       $params[] = $_GET['status']; }
            if (!empty($_GET['date_from']))  { $where[] = 'o.order_date>=?'; $params[] = $_GET['date_from']; }
            if (!empty($_GET['date_to']))    { $where[] = 'o.order_date<=?'; $params[] = $_GET['date_to']; }

            $sql = 'SELECT o.*, u.name as sr_name, c.name as company_name,
                    SUM(oi.qty_pieces) as total_qty,
                    SUM(oi.qty_pieces * oi.unit_price) as out_value,
                    COALESCE((SELECT SUM(ri.qty_in) FROM return_items ri JOIN returns ret ON ret.id=ri.return_id JOIN dispatches d ON d.order_id=o.id WHERE ri.return_id=ret.id),0) as back_qty
                    FROM orders o
                    JOIN sr s ON s.id=o.sr_id
                    JOIN users u ON u.id=s.user_id
                    JOIN companies c ON c.id=o.company_id
                    LEFT JOIN order_items oi ON oi.order_id=o.id
                    WHERE ' . implode(' AND ', $where) . '
                    GROUP BY o.id ORDER BY o.id DESC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        break;

    case 'POST':
        $d = json_decode(file_get_contents('php://input'), true);
        $pdo->beginTransaction();

        // Get company_id from sr
        $srRow = $pdo->prepare('SELECT company_id FROM sr WHERE id=?');
        $srRow->execute([$d['sr_id']]); $srRow = $srRow->fetch();
        $company_id = $srRow['company_id'] ?? 0;

        $ord = $pdo->prepare('INSERT INTO orders (sr_id, company_id, order_date, status) VALUES (?,?,?,?)');
        $status = !empty($d['status']) ? $d['status'] : 'pending';
        $ord->execute([$d['sr_id'], $company_id, $d['order_date'], $status]);
        $order_id = $pdo->lastInsertId();

        foreach ($d['items'] as $item) {
            // Get pieces_per_box for display calculations
            $ppb = $pdo->prepare('SELECT pieces_per_box, selling_price FROM products WHERE id=?');
            $ppb->execute([$item['product_id']]); $ppb = $ppb->fetch();
            $qtyPcs  = intval($item['qty_pieces']);
            $boxDisp = $ppb ? floor($qtyPcs / $ppb['pieces_per_box']) : 0;
            $rem     = $ppb ? ($qtyPcs % $ppb['pieces_per_box']) : 0;
            $price   = $ppb['selling_price'] ?? $item['unit_price'] ?? 0;

            $pdo->prepare('INSERT INTO order_items (order_id, product_id, qty_pieces, qty_boxes_display, qty_pieces_remainder, unit_price) VALUES (?,?,?,?,?,?)')
                ->execute([$order_id, $item['product_id'], $qtyPcs, $boxDisp, $rem, $price]);
        }
        // --- READY SALE INVENTORY DEDUCTION ---
        if ($status === 'ready_sale' && !empty($d['scanned_qrs'])) {
            $wid = $_SESSION['warehouse_id'] ?? 0;
            foreach ($d['scanned_qrs'] as $qr_id) {
                // Fetch QR
                $qrStmt = $pdo->prepare('SELECT product_id, pieces_remaining FROM qr_codes WHERE id=?');
                $qrStmt->execute([$qr_id]);
                $qr = $qrStmt->fetch();
                if (!$qr) continue;
                
                // Update QR
                $pdo->prepare("UPDATE qr_codes SET status='dispatched', pieces_remaining=0 WHERE id=?")->execute([$qr_id]);
                
                // Update inventory
                $ppbStmt = $pdo->prepare('SELECT pieces_per_box FROM products WHERE id=?');
                $ppbStmt->execute([$qr['product_id']]);
                $pieces_per_box = max((int)$ppbStmt->fetchColumn(), 1);

                $inv = $pdo->prepare('SELECT qty_boxes, qty_pieces FROM inventory WHERE product_id=? AND warehouse_id=?');
                $inv->execute([$qr['product_id'], $wid]);
                $row = $inv->fetch();
                if ($row) {
                    $total_pieces_now = ($row['qty_boxes'] * $pieces_per_box) + $row['qty_pieces'];
                    $new_total = max($total_pieces_now - intval($qr['pieces_remaining']), 0);
                    
                    $new_boxes = floor($new_total / $pieces_per_box);
                    $new_pieces = $new_total % $pieces_per_box;

                    $pdo->prepare('UPDATE inventory SET qty_boxes=?, qty_pieces=? WHERE product_id=? AND warehouse_id=?')
                        ->execute([$new_boxes, $new_pieces, $qr['product_id'], $wid]);
                }
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'order_id' => $order_id, 'message' => 'Order created']);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        $pdo->prepare('UPDATE orders SET status="cancelled" WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true]);
        break;
}
