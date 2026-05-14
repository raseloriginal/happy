<?php
// api/lots.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole(['admin','manager']);

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'view') {
            $id  = $_GET['id'] ?? 0;
            $lot = $pdo->prepare('SELECT l.*, c.name as company_name, w.name as warehouse_name, u.name as manager_name FROM lots l JOIN companies c ON c.id=l.company_id JOIN warehouses w ON w.id=l.warehouse_id LEFT JOIN managers m ON m.id=l.manager_id LEFT JOIN users u ON u.id=m.user_id WHERE l.id=?');
            $lot->execute([$id]); $lot = $lot->fetch();
            $items = $pdo->prepare('SELECT li.*, p.name as product_name, p.pieces_per_box, li.qr_generated FROM lot_items li JOIN products p ON p.id=li.product_id WHERE li.lot_id=?');
            $items->execute([$id]);
            echo json_encode(['success' => true, 'lot' => $lot, 'items' => $items->fetchAll()]);
        } else {
            $wid  = $_SESSION['warehouse_id'] ?? null;
            $sql  = 'SELECT l.*, c.name as company_name, w.name as warehouse_name, COUNT(li.id) as item_count FROM lots l JOIN companies c ON c.id=l.company_id JOIN warehouses w ON w.id=l.warehouse_id LEFT JOIN lot_items li ON li.lot_id=l.id WHERE l.status=1';
            if ($wid) $sql .= ' AND l.warehouse_id=' . intval($wid);
            $sql .= ' GROUP BY l.id ORDER BY l.id DESC';
            echo json_encode(['success' => true, 'data' => $pdo->query($sql)->fetchAll()]);
        }
        break;

    case 'POST':
        $d = json_decode(file_get_contents('php://input'), true);
        try {
            $pdo->beginTransaction();

            // Get manager_id
            $mgr = $pdo->prepare('SELECT id FROM managers WHERE user_id=? LIMIT 1');
            $mgr->execute([$_SESSION['user_id']]);
            $manager_id = $mgr->fetchColumn() ?: 0;

            $wid = $_SESSION['warehouse_id'] ?? ($d['warehouse_id'] ?? 0);

            $stmt = $pdo->prepare('INSERT INTO lots (company_id, warehouse_id, manager_id, lot_date, grand_total) VALUES (?,?,?,?,?)');
            $stmt->execute([$d['company_id'], $wid, $manager_id, $d['lot_date'], $d['grand_total']]);
            $lot_id = $pdo->lastInsertId();

            foreach ($d['items'] as $item) {
                $total = $item['qty_boxes'] * $item['buying_price'];
                $pdo->prepare('INSERT INTO lot_items (lot_id, product_id, qty_boxes, expiry_date, buying_price, total, qr_generated) VALUES (?,?,?,?,?,?,0)')
                    ->execute([$lot_id, $item['product_id'], $item['qty_boxes'], $item['expiry_date'], $item['buying_price'], $total]);

                // Update inventory
                $pdo->prepare('INSERT INTO inventory (product_id, warehouse_id, qty_boxes, qty_pieces) VALUES (?,?,?,0) ON DUPLICATE KEY UPDATE qty_boxes = qty_boxes + VALUES(qty_boxes)')
                    ->execute([$item['product_id'], $wid, $item['qty_boxes']]);
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'lot_id' => $lot_id, 'message' => 'Lot created successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        $pdo->prepare('UPDATE lots SET status=0 WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true]);
        break;
}
