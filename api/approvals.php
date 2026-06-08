<?php
// api/approvals.php — Pending Approvals system for manager edit/cancel requests
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole(['admin', 'manager']);

$pdo    = getDB();

// Auto-run migration if pending_approvals table is missing
try {
    $pdo->query("SELECT 1 FROM pending_approvals LIMIT 1");
} catch (Exception $e) {
    $sqlFile = __DIR__ . '/../database/migrations/007_pending_approvals.sql';
    if (file_exists($sqlFile)) {
        try {
            $sql = file_get_contents($sqlFile);
            $pdo->exec($sql);
        } catch (Exception $ex) {
            echo json_encode(['success' => false, 'message' => 'Auto-migration failed: ' . $ex->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Table pending_approvals is missing. Please run migrations.']);
        exit;
    }
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$role   = $_SESSION['role'] ?? '';

// ─── GET: List approvals (admin sees all, manager sees their own) ────────────
if ($action === 'list' && $method === 'GET') {
    if ($role === 'admin') {
        $stmt = $pdo->query("
            SELECT pa.*, u.name AS requested_by_name, ru.name AS reviewed_by_name
            FROM pending_approvals pa
            JOIN users u ON u.id = pa.requested_by
            LEFT JOIN users ru ON ru.id = pa.reviewed_by
            ORDER BY pa.status ASC, pa.requested_at DESC
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT pa.*, u.name AS requested_by_name, ru.name AS reviewed_by_name
            FROM pending_approvals pa
            JOIN users u ON u.id = pa.requested_by
            LEFT JOIN users ru ON ru.id = pa.reviewed_by
            WHERE pa.requested_by = ?
            ORDER BY pa.requested_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
    }
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// ─── GET: Pending count (for badge) ─────────────────────────────────────────
if ($action === 'pending_count' && $method === 'GET') {
    $count = $pdo->query("SELECT COUNT(*) FROM pending_approvals WHERE status='pending'")->fetchColumn();
    echo json_encode(['success' => true, 'count' => (int)$count]);
    exit;
}

// ─── POST: Submit a new approval request (manager) ───────────────────────────
if ($action === 'request' && $method === 'POST') {
    requireRole('manager');
    $d           = json_decode(file_get_contents('php://input'), true);
    $action_type = $d['action_type'] ?? '';
    $target_id   = intval($d['target_id'] ?? 0);
    $payload     = $d['payload'] ?? [];
    $summary     = $d['summary'] ?? '';

    if (!in_array($action_type, ['edit_order', 'cancel_order', 'edit_dispatch'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid action type']); exit;
    }
    if ($target_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid target']); exit;
    }

    // Check for duplicate pending request for same target/action
    $dup = $pdo->prepare("SELECT id FROM pending_approvals WHERE action_type=? AND target_id=? AND status='pending'");
    $dup->execute([$action_type, $target_id]);
    if ($dup->fetch()) {
        echo json_encode(['success' => false, 'message' => 'A pending request for this action already exists. Please wait for admin approval.']); exit;
    }

    $stmt = $pdo->prepare("INSERT INTO pending_approvals (action_type, target_id, payload, summary, requested_by) VALUES (?,?,?,?,?)");
    $stmt->execute([$action_type, $target_id, json_encode($payload), $summary, $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Request submitted for admin approval.']);
    exit;
}

// ─── POST: Approve (admin only) ──────────────────────────────────────────────
if ($action === 'approve' && $method === 'POST') {
    requireRole('admin');
    $id = intval($_GET['id'] ?? 0);
    $d  = json_decode(file_get_contents('php://input'), true);
    $admin_notes = $d['notes'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM pending_approvals WHERE id=? AND status='pending'");
    $stmt->execute([$id]);
    $approval = $stmt->fetch();
    if (!$approval) {
        echo json_encode(['success' => false, 'message' => 'Approval not found or already processed']); exit;
    }

    $payload = json_decode($approval['payload'], true);
    $wid = $_SESSION['warehouse_id'] ?? 0;

    $pdo->beginTransaction();
    try {
        // ── Execute the approved action ──────────────────────────────────────
        if ($approval['action_type'] === 'cancel_order') {
            $order_id = $approval['target_id'];

            // Fetch order
            $order = $pdo->prepare('SELECT * FROM orders WHERE id=?');
            $order->execute([$order_id]);
            $order = $order->fetch();

            if (!$order || $order['status'] === 'cancelled') {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Order not found or already cancelled']); exit;
            }

            // Restock logic (same as DELETE handler in api/orders.php)
            if ($order['status'] === 'ready_sale') {
                $scanned_qrs = json_decode($order['scanned_qrs'] ?? '', true) ?: [];
                _restock_ready_sale($pdo, $scanned_qrs, $order_id, $wid);
            } elseif (in_array($order['status'], ['out_for_delivery', 'delivered'])) {
                $itemsStmt = $pdo->prepare('SELECT qr_code_id, product_id, qty_out FROM dispatch_items WHERE order_id=?');
                $itemsStmt->execute([$order_id]);
                _restock_dispatch($pdo, $itemsStmt->fetchAll(), $order_id, $wid);

                $pdo->prepare('DELETE FROM return_items WHERE return_id IN (SELECT id FROM returns WHERE dispatch_id IN (SELECT id FROM dispatches WHERE order_id=?))')->execute([$order_id]);
                $pdo->prepare('DELETE FROM returns WHERE dispatch_id IN (SELECT id FROM dispatches WHERE order_id=?)')->execute([$order_id]);
                $pdo->prepare('DELETE FROM cash_settlements WHERE dispatch_id IN (SELECT id FROM dispatches WHERE order_id=?)')->execute([$order_id]);
                $pdo->prepare('DELETE FROM dispatch_items WHERE order_id=?')->execute([$order_id]);
                $pdo->prepare('DELETE FROM dispatches WHERE order_id=?')->execute([$order_id]);
            }

            $pdo->prepare("UPDATE orders SET status='cancelled' WHERE id=?")->execute([$order_id]);

        } elseif ($approval['action_type'] === 'edit_order') {
            $order_id = $approval['target_id'];
            $new_items = $payload['items'] ?? [];

            // Get order status
            $orderRow = $pdo->prepare('SELECT status FROM orders WHERE id=?');
            $orderRow->execute([$order_id]);
            $orderRow = $orderRow->fetch();
            if (!$orderRow) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Order not found']); exit;
            }

            if ($orderRow['status'] === 'pending' && !empty($new_items)) {
                // Replace order items
                $pdo->prepare('DELETE FROM order_items WHERE order_id=?')->execute([$order_id]);
                $insertItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, qty_pieces, qty_boxes_display, qty_pieces_remainder, unit_price) VALUES (?,?,?,?,?,?)');
                foreach ($new_items as $item) {
                    $ppb = $pdo->prepare('SELECT pieces_per_box, selling_price FROM products WHERE id=?');
                    $ppb->execute([$item['product_id']]); $ppb = $ppb->fetch();
                    $qtyPcs  = intval($item['qty_pieces']);
                    $boxDisp = $ppb ? floor($qtyPcs / $ppb['pieces_per_box']) : 0;
                    $rem     = $ppb ? ($qtyPcs % $ppb['pieces_per_box']) : 0;
                    $price   = $ppb['selling_price'] ?? 0;
                    $insertItem->execute([$order_id, $item['product_id'], $qtyPcs, $boxDisp, $rem, $price]);
                }
                // Update order date if provided
                if (!empty($payload['order_date'])) {
                    $pdo->prepare('UPDATE orders SET order_date=? WHERE id=?')->execute([$payload['order_date'], $order_id]);
                }
                // Update sr_id and company_id if provided
                if (!empty($payload['sr_id'])) {
                    $srRow = $pdo->prepare('SELECT company_id FROM sr WHERE id=?');
                    $srRow->execute([$payload['sr_id']]);
                    $coId = $srRow->fetchColumn();
                    $pdo->prepare('UPDATE orders SET sr_id=?, company_id=? WHERE id=?')->execute([$payload['sr_id'], $coId ?: 0, $order_id]);
                }
            } elseif ($orderRow['status'] === 'out_for_delivery' && !empty($payload['dispatch_edit'])) {
                // Edit dispatch — update dispatch DSR and/or reconcile scanned QR codes
                $dispatch_data = $payload['dispatch_edit'];
                if (!empty($dispatch_data['dsr_id'])) {
                    $pdo->prepare('UPDATE dispatches SET dsr_id=? WHERE order_id=?')->execute([$dispatch_data['dsr_id'], $order_id]);
                }

                if (isset($dispatch_data['scanned_qrs'])) {
                    $new_qr_ids = array_map('intval', $dispatch_data['scanned_qrs']);

                    // Find current dispatch ID
                    $dStmt = $pdo->prepare("SELECT id FROM dispatches WHERE order_id=?");
                    $dStmt->execute([$order_id]);
                    $dispatch_id = intval($dStmt->fetchColumn() ?: 0);

                    if ($dispatch_id > 0) {
                        // Get currently dispatched QR codes
                        $curStmt = $pdo->prepare("SELECT qr_code_id, product_id, qty_out FROM dispatch_items WHERE dispatch_id=?");
                        $curStmt->execute([$dispatch_id]);
                        $current_items = $curStmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

                        // QRs to remove (currently in dispatch but not in new list)
                        foreach ($current_items as $qr_id => $item) {
                            if (!in_array($qr_id, $new_qr_ids)) {
                                $qty_out = intval($item['qty_out']);
                                $product_id = intval($item['product_id']);

                                // Fetch QR
                                $qrStmt = $pdo->prepare('SELECT pieces_total, pieces_remaining FROM qr_codes WHERE id=?');
                                $qrStmt->execute([$qr_id]);
                                $qr = $qrStmt->fetch();
                                if ($qr) {
                                    $new_rem = min(intval($qr['pieces_remaining']) + $qty_out, intval($qr['pieces_total']));
                                    $pdo->prepare("UPDATE qr_codes SET status='active', pieces_remaining=? WHERE id=?")->execute([$new_rem, $qr_id]);
                                }

                                // Update inventory
                                $ppbStmt = $pdo->prepare('SELECT pieces_per_box FROM products WHERE id=?');
                                $ppbStmt->execute([$product_id]);
                                $ppb = max((int)$ppbStmt->fetchColumn(), 1);

                                $inv = $pdo->prepare('SELECT qty_boxes, qty_pieces FROM inventory WHERE product_id=? AND warehouse_id=?');
                                $inv->execute([$product_id, $wid]);
                                $row = $inv->fetch();
                                if ($row) {
                                    $tot = ($row['qty_boxes'] * $ppb) + $row['qty_pieces'] + $qty_out;
                                    $pdo->prepare('UPDATE inventory SET qty_boxes=?, qty_pieces=? WHERE product_id=? AND warehouse_id=?')
                                        ->execute([floor($tot/$ppb), $tot%$ppb, $product_id, $wid]);
                                    logInventoryActivity($pdo, $product_id, $wid, 'order_cancelled', $order_id, 0, $qty_out, "Approved Remove QR $qr_id from Dispatch (Order $order_id)");
                                }

                                // Delete from dispatch_items
                                $pdo->prepare("DELETE FROM dispatch_items WHERE dispatch_id=? AND qr_code_id=?")->execute([$dispatch_id, $qr_id]);
                            }
                        }

                        // QRs to add (in new list but not currently in dispatch)
                        foreach ($new_qr_ids as $qr_id) {
                            if (!isset($current_items[$qr_id])) {
                                // Fetch QR info
                                $qrStmt = $pdo->prepare('SELECT product_id, pieces_remaining, pieces_total FROM qr_codes WHERE id=?');
                                $qrStmt->execute([$qr_id]);
                                $qr = $qrStmt->fetch();
                                if ($qr) {
                                    $qty_out = intval($qr['pieces_remaining']);
                                    if ($qty_out > 0) {
                                        $product_id = intval($qr['product_id']);

                                        // Update QR Code
                                        $pdo->prepare("UPDATE qr_codes SET status='dispatched', pieces_remaining=0 WHERE id=?")->execute([$qr_id]);

                                        // Update inventory
                                        $ppbStmt = $pdo->prepare('SELECT pieces_per_box FROM products WHERE id=?');
                                        $ppbStmt->execute([$product_id]);
                                        $ppb = max((int)$ppbStmt->fetchColumn(), 1);

                                        $inv = $pdo->prepare('SELECT qty_boxes, qty_pieces FROM inventory WHERE product_id=? AND warehouse_id=?');
                                        $inv->execute([$product_id, $wid]);
                                        $row = $inv->fetch();
                                        if ($row) {
                                            $tot = max(($row['qty_boxes'] * $ppb) + $row['qty_pieces'] - $qty_out, 0);
                                            $pdo->prepare('UPDATE inventory SET qty_boxes=?, qty_pieces=? WHERE product_id=? AND warehouse_id=?')
                                                ->execute([floor($tot/$ppb), $tot%$ppb, $product_id, $wid]);
                                            logInventoryActivity($pdo, $product_id, $wid, 'ready_sale', $order_id, 0, -$qty_out, "Approved Add QR $qr_id to Dispatch (Order $order_id)");
                                        }

                                        // Insert dispatch item
                                        $pdo->prepare("INSERT INTO dispatch_items (dispatch_id, order_id, qr_code_id, product_id, qty_out) VALUES (?,?,?,?,?)")
                                            ->execute([$dispatch_id, $order_id, $qr_id, $product_id, $qty_out]);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Update order_date for pending
            if ($orderRow['status'] === 'pending' && !empty($payload['order_date'])) {
                $pdo->prepare('UPDATE orders SET order_date=? WHERE id=?')->execute([$payload['order_date'], $order_id]);
            }

        } elseif ($approval['action_type'] === 'edit_dispatch') {
            $dispatch_id = $approval['target_id'];
            if (!empty($payload['dsr_id'])) {
                $pdo->prepare('UPDATE dispatches SET dsr_id=? WHERE id=?')->execute([$payload['dsr_id'], $dispatch_id]);
            }
        }

        // Mark as approved
        $pdo->prepare("UPDATE pending_approvals SET status='approved', reviewed_by=?, reviewed_at=NOW(), admin_notes=? WHERE id=?")
            ->execute([$_SESSION['user_id'], $admin_notes, $id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Approved and executed successfully.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ─── POST: Reject (admin only) ───────────────────────────────────────────────
if ($action === 'reject' && $method === 'POST') {
    requireRole('admin');
    $id = intval($_GET['id'] ?? 0);
    $d  = json_decode(file_get_contents('php://input'), true);
    $admin_notes = $d['notes'] ?? '';

    $stmt = $pdo->prepare("SELECT id FROM pending_approvals WHERE id=? AND status='pending'");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Approval not found or already processed']); exit;
    }

    $pdo->prepare("UPDATE pending_approvals SET status='rejected', reviewed_by=?, reviewed_at=NOW(), admin_notes=? WHERE id=?")
        ->execute([$_SESSION['user_id'], $admin_notes, $id]);

    echo json_encode(['success' => true, 'message' => 'Request rejected.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);

// ─── Helper: Restock ready_sale ───────────────────────────────────────────────
function _restock_ready_sale($pdo, $scanned_qrs, $order_id, $wid) {
    foreach ($scanned_qrs as $sqr) {
        $qr_id = is_array($sqr) ? intval($sqr['qr_id']) : intval($sqr);
        $qrStmt = $pdo->prepare('SELECT product_id, pieces_total, pieces_remaining FROM qr_codes WHERE id=?');
        $qrStmt->execute([$qr_id]); $qr = $qrStmt->fetch();
        if (!$qr) continue;
        $pieces_sold = is_array($sqr) ? intval($sqr['pieces_sold']) : intval($qr['pieces_total']);
        $new_rem = min(intval($qr['pieces_remaining']) + $pieces_sold, intval($qr['pieces_total']));
        $pdo->prepare("UPDATE qr_codes SET status='active', pieces_remaining=? WHERE id=?")->execute([$new_rem, $qr_id]);
        $ppb = max((int)$pdo->prepare('SELECT pieces_per_box FROM products WHERE id=?')->execute([$qr['product_id']]) ? 1 : 1, 1);
        $ppbStmt = $pdo->prepare('SELECT pieces_per_box FROM products WHERE id=?'); $ppbStmt->execute([$qr['product_id']]); $ppb = max((int)$ppbStmt->fetchColumn(), 1);
        $inv = $pdo->prepare('SELECT qty_boxes, qty_pieces FROM inventory WHERE product_id=? AND warehouse_id=?'); $inv->execute([$qr['product_id'], $wid]); $row = $inv->fetch();
        if ($row) {
            $tot = ($row['qty_boxes'] * $ppb) + $row['qty_pieces'] + $pieces_sold;
            $pdo->prepare('UPDATE inventory SET qty_boxes=?, qty_pieces=? WHERE product_id=? AND warehouse_id=?')->execute([floor($tot/$ppb), $tot%$ppb, $qr['product_id'], $wid]);
            logInventoryActivity($pdo, $qr['product_id'], $wid, 'order_cancelled', $order_id, 0, $pieces_sold, "Approved Cancel Ready Sale (Order $order_id)");
        }
    }
}

// ─── Helper: Restock dispatch ─────────────────────────────────────────────────
function _restock_dispatch($pdo, $items, $order_id, $wid) {
    foreach ($items as $item) {
        $qr_id = intval($item['qr_code_id']); $qty_out = intval($item['qty_out']);
        $qrStmt = $pdo->prepare('SELECT product_id, pieces_total, pieces_remaining FROM qr_codes WHERE id=?'); $qrStmt->execute([$qr_id]); $qr = $qrStmt->fetch();
        if (!$qr) continue;
        $new_rem = min(intval($qr['pieces_remaining']) + $qty_out, intval($qr['pieces_total']));
        $pdo->prepare("UPDATE qr_codes SET status='active', pieces_remaining=? WHERE id=?")->execute([$new_rem, $qr_id]);
        $ppbStmt = $pdo->prepare('SELECT pieces_per_box FROM products WHERE id=?'); $ppbStmt->execute([$item['product_id']]); $ppb = max((int)$ppbStmt->fetchColumn(), 1);
        $inv = $pdo->prepare('SELECT qty_boxes, qty_pieces FROM inventory WHERE product_id=? AND warehouse_id=?'); $inv->execute([$item['product_id'], $wid]); $row = $inv->fetch();
        if ($row) {
            $tot = ($row['qty_boxes'] * $ppb) + $row['qty_pieces'] + $qty_out;
            $pdo->prepare('UPDATE inventory SET qty_boxes=?, qty_pieces=? WHERE product_id=? AND warehouse_id=?')->execute([floor($tot/$ppb), $tot%$ppb, $item['product_id'], $wid]);
            logInventoryActivity($pdo, $item['product_id'], $wid, 'order_cancelled', $order_id, 0, $qty_out, "Approved Cancel Dispatch (Order $order_id)");
        }
    }
}
