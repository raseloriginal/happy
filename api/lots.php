<?php
// api/lots.php
ob_start();
header('Content-Type: application/json');

try {
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

                // Auto update product selling price
                // Formula: selling_price = (buying_price + (buying_price * dealer_percentage / 100)) / pieces_per_box
                $prodStmt = $pdo->prepare('SELECT pieces_per_box, dealer_percentage FROM products WHERE id=?');
                $prodStmt->execute([$item['product_id']]);
                $prod = $prodStmt->fetch();

                if ($prod) {
                    $ppb = (float)($prod['pieces_per_box'] ?: 1);
                    $dp  = (float)($prod['dealer_percentage'] ?: 0);
                    $buying_price = (float)$item['buying_price'];
                    
                    $selling_price_box = $buying_price * (1 + ($dp / 100));
                    $selling_price_piece = $selling_price_box / $ppb;

                    $pdo->prepare('UPDATE products SET selling_price=? WHERE id=?')
                        ->execute([$selling_price_piece, $item['product_id']]);
                }
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'lot_id' => $lot_id, 'message' => 'Lot created successfully']);
            break;

        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            $pdo->beginTransaction();

            // 1. Get the lot's warehouse_id and current status
            $stmt = $pdo->prepare('SELECT warehouse_id, status FROM lots WHERE id = ?');
            $stmt->execute([$id]);
            $lot = $stmt->fetch();

            if (!$lot) {
                throw new Exception('Lot not found');
            }

            if (intval($lot['status']) === 0) {
                throw new Exception('Lot is already deleted');
            }

            $warehouse_id = intval($lot['warehouse_id']);

            // 2. Prevent deletion if any QR codes associated with this lot are dispatched or used.
            // A QR code is considered "used/touched" if:
            // - its status is not 'active'
            // - or pieces_remaining < pieces_total
            // - or its ID exists in dispatch_items
            // - or its ID exists in return_items
            $chk = $pdo->prepare("
                SELECT COUNT(*) FROM qr_codes 
                WHERE lot_id = ? 
                  AND (
                    status != 'active' 
                    OR pieces_remaining < pieces_total 
                    OR id IN (SELECT DISTINCT qr_code_id FROM dispatch_items)
                    OR id IN (SELECT DISTINCT qr_code_id FROM return_items)
                  )
            ");
            $chk->execute([$id]);
            if (intval($chk->fetchColumn()) > 0) {
                throw new Exception('Cannot delete this lot. Some QR codes have already been dispatched or modified.');
            }

            // 3. Get all items of the lot to reduce inventory
            $stmtItems = $pdo->prepare('SELECT product_id, qty_boxes FROM lot_items WHERE lot_id = ?');
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll();

            foreach ($items as $item) {
                $product_id = intval($item['product_id']);
                $qty_boxes  = intval($item['qty_boxes']);

                // Reduce inventory. Cap at 0 using GREATEST to avoid negative inventory.
                $pdo->prepare('UPDATE inventory SET qty_boxes = GREATEST(0, qty_boxes - ?) WHERE product_id = ? AND warehouse_id = ?')
                    ->execute([$qty_boxes, $product_id, $warehouse_id]);
            }

            // 4. Delete the unused active QR codes for this lot
            $pdo->prepare('DELETE FROM qr_codes WHERE lot_id = ?')->execute([$id]);

            // 5. Update lot status to 0 (soft delete)
            $pdo->prepare('UPDATE lots SET status = 0 WHERE id = ?')->execute([$id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Lot deleted successfully and inventory updated']);
            break;

        case 'PUT':
            $id = intval($_GET['id'] ?? 0);
            $d  = json_decode(file_get_contents('php://input'), true);
            $pdo->beginTransaction();

            // 1. Get the lot's warehouse_id and current status
            $stmt = $pdo->prepare('SELECT warehouse_id, status FROM lots WHERE id = ?');
            $stmt->execute([$id]);
            $lot = $stmt->fetch();

            if (!$lot) {
                throw new Exception('Lot not found');
            }

            if (intval($lot['status']) === 0) {
                throw new Exception('Lot is deleted and cannot be edited');
            }

            $warehouse_id = intval($lot['warehouse_id']);

            // 2. Prevent editing if any QR codes associated with this lot are dispatched or used.
            $chk = $pdo->prepare("
                SELECT COUNT(*) FROM qr_codes 
                WHERE lot_id = ? 
                  AND (
                    status != 'active' 
                    OR pieces_remaining < pieces_total 
                    OR id IN (SELECT DISTINCT qr_code_id FROM dispatch_items)
                    OR id IN (SELECT DISTINCT qr_code_id FROM return_items)
                  )
            ");
            $chk->execute([$id]);
            if (intval($chk->fetchColumn()) > 0) {
                throw new Exception('Cannot edit this lot. Some QR codes have already been dispatched or modified.');
            }

            // 3. Get all existing items of the lot to revert inventory
            $stmtItems = $pdo->prepare('SELECT product_id, qty_boxes FROM lot_items WHERE lot_id = ?');
            $stmtItems->execute([$id]);
            $old_items = $stmtItems->fetchAll();

            foreach ($old_items as $old_item) {
                $product_id = intval($old_item['product_id']);
                $qty_boxes  = intval($old_item['qty_boxes']);

                // Reduce inventory. Cap at 0 to avoid negative inventory.
                $pdo->prepare('UPDATE inventory SET qty_boxes = GREATEST(0, qty_boxes - ?) WHERE product_id = ? AND warehouse_id = ?')
                    ->execute([$qty_boxes, $product_id, $warehouse_id]);
            }

            // 4. Delete existing active/unused QR codes for this lot
            $pdo->prepare('DELETE FROM qr_codes WHERE lot_id = ?')->execute([$id]);

            // 5. Delete old lot items
            $pdo->prepare('DELETE FROM lot_items WHERE lot_id = ?')->execute([$id]);

            // 6. Insert new items and update inventory/selling prices
            $new_warehouse_id = $_SESSION['warehouse_id'] ?? ($d['warehouse_id'] ?? $warehouse_id);

            foreach ($d['items'] as $item) {
                $total = $item['qty_boxes'] * $item['buying_price'];
                $pdo->prepare('INSERT INTO lot_items (lot_id, product_id, qty_boxes, expiry_date, buying_price, total, qr_generated) VALUES (?,?,?,?,?,?,0)')
                    ->execute([$id, $item['product_id'], $item['qty_boxes'], $item['expiry_date'], $item['buying_price'], $total]);

                // Update inventory
                $pdo->prepare('INSERT INTO inventory (product_id, warehouse_id, qty_boxes, qty_pieces) VALUES (?,?,?,0) ON DUPLICATE KEY UPDATE qty_boxes = qty_boxes + VALUES(qty_boxes)')
                    ->execute([$item['product_id'], $new_warehouse_id, $item['qty_boxes']]);

                // Auto update product selling price
                $prodStmt = $pdo->prepare('SELECT pieces_per_box, dealer_percentage FROM products WHERE id=?');
                $prodStmt->execute([$item['product_id']]);
                $prod = $prodStmt->fetch();

                if ($prod) {
                    $ppb = (float)($prod['pieces_per_box'] ?: 1);
                    $dp  = (float)($prod['dealer_percentage'] ?: 0);
                    $buying_price = (float)$item['buying_price'];
                    
                    $selling_price_box = $buying_price * (1 + ($dp / 100));
                    $selling_price_piece = $selling_price_box / $ppb;

                    $pdo->prepare('UPDATE products SET selling_price=? WHERE id=?')
                        ->execute([$selling_price_piece, $item['product_id']]);
                }
            }

            // 7. Update lot header
            $pdo->prepare('UPDATE lots SET company_id = ?, warehouse_id = ?, lot_date = ?, grand_total = ? WHERE id = ?')
                ->execute([$d['company_id'], $new_warehouse_id, $d['lot_date'], $d['grand_total'], $id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Lot updated successfully']);
            break;

        default:
            throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

ob_end_flush();
