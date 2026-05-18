<?php
// api/cashflow.php — Cash Flow Settlement Actions
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole(['admin','manager']);

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Get manager_id from session
$mgr = $pdo->prepare('SELECT id FROM managers WHERE user_id=? LIMIT 1');
$mgr->execute([$_SESSION['user_id']]);
$manager_id = $mgr->fetchColumn() ?: 0;

if ($method === 'GET') {
    if ($action === 'pending_dsr') {
        // Fetch all pending DSR cash settlements
        $stmt = $pdo->prepare("
            SELECT cs.*, u.name as dsr_name, d.dispatch_date, d.status as dispatch_status,
                   o.id as order_id, c.name as company_name
            FROM cash_settlements cs 
            JOIN dsr ds ON ds.id=cs.dsr_id 
            JOIN users u ON u.id=ds.user_id 
            JOIN dispatches d ON d.id=cs.dispatch_id 
            LEFT JOIN orders o ON o.id=d.order_id
            LEFT JOIN companies c ON c.id=o.company_id
            WHERE cs.status='pending' 
            ORDER BY cs.id DESC
        ");
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        exit;
    }

    $did = intval($_GET['dispatch_id'] ?? 0);
    // Expected: sum(qty_out * selling_price) - sum(qty_in * selling_price)
    // Out Value
    $outStmt = $pdo->prepare('SELECT COALESCE(SUM(di.qty_out * p.selling_price),0) as expected FROM dispatch_items di JOIN products p ON p.id=di.product_id WHERE di.dispatch_id=?');
    $outStmt->execute([$did]);
    $outVal = floatval($outStmt->fetchColumn());

    // Return Value
    $retStmt = $pdo->prepare('SELECT COALESCE(SUM(ri.qty_in * p.selling_price),0) as returned FROM return_items ri JOIN returns r ON r.id=ri.return_id JOIN products p ON p.id=ri.product_id WHERE r.dispatch_id=? AND r.status="completed"');
    $retStmt->execute([$did]);
    $returnVal = floatval($retStmt->fetchColumn());

    $expected = max($outVal - $returnVal, 0);

    echo json_encode(['success' => true, 'expected' => $expected]);
    exit;
}

if ($method === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);

    if ($action === 'approve') {
        $settlement_id = intval($d['settlement_id'] ?? 0);
        if (!$settlement_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid Settlement ID']);
            exit;
        }

        // Get settlement record details
        $csStmt = $pdo->prepare('SELECT * FROM cash_settlements WHERE id=? LIMIT 1');
        $csStmt->execute([$settlement_id]);
        $cs = $csStmt->fetch();

        if (!$cs) {
            echo json_encode(['success' => false, 'message' => 'Settlement record not found']);
            exit;
        }

        $pdo->beginTransaction();

        // Update cash settlement record
        $upCS = $pdo->prepare('UPDATE cash_settlements SET status="approved", manager_id=?, settlement_date=CURDATE() WHERE id=?');
        $upCS->execute([$manager_id, $settlement_id]);

        // Settle the dispatch
        $upDisp = $pdo->prepare('UPDATE dispatches SET status="settled" WHERE id=?');
        $upDisp->execute([$cs['dispatch_id']]);

        // Complete the return record if pending
        $retCheck = $pdo->prepare('SELECT id FROM returns WHERE dispatch_id=? LIMIT 1');
        $retCheck->execute([$cs['dispatch_id']]);
        $retId = $retCheck->fetchColumn();

        if ($retId) {
            $pdo->prepare('UPDATE returns SET status="completed", manager_id=? WHERE id=?')->execute([$manager_id, $retId]);
        }

        // Update linked order status to 'delivered'
        $dispStmt = $pdo->prepare('SELECT order_id FROM dispatches WHERE id=?');
        $dispStmt->execute([$cs['dispatch_id']]);
        $order_id = $dispStmt->fetchColumn();
        if ($order_id) {
            $pdo->prepare("UPDATE orders SET status='delivered' WHERE id=?")->execute([$order_id]);
        }

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Settlement approved and dispatch completed!']);
        exit;
    }

    if ($action === 'reject') {
        $settlement_id = intval($d['settlement_id'] ?? 0);
        if (!$settlement_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid Settlement ID']);
            exit;
        }

        $pdo->beginTransaction();

        // Delete or mark rejected
        // For simple retry, we can delete the cash_settlement record and revert dispatch status back to 'loaded' so DSR can submit again!
        // This is extremely intuitive for the delivery representative!
        $csStmt = $pdo->prepare('SELECT dispatch_id FROM cash_settlements WHERE id=? LIMIT 1');
        $csStmt->execute([$settlement_id]);
        $dispatch_id = $csStmt->fetchColumn();

        if ($dispatch_id) {
            $pdo->prepare('UPDATE dispatches SET status="loaded" WHERE id=?')->execute([$dispatch_id]);
        }

        $delCS = $pdo->prepare('DELETE FROM cash_settlements WHERE id=?');
        $delCS->execute([$settlement_id]);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Settlement rejected. DSR can now recount and resubmit.']);
        exit;
    }

    // Standard Manual Settlement from manager panel
    $dispatch_id = intval($d['dispatch_id'] ?? 0);
    $amount_expected = floatval($d['amount_expected'] ?? 0);
    $amount_submitted = floatval($d['amount_submitted'] ?? 0);
    $difference = floatval($d['difference'] ?? 0);
    $notes = trim($d['notes'] ?? '');

    $dsrStmt = $pdo->prepare('SELECT dsr_id FROM dispatches WHERE id=?');
    $dsrStmt->execute([$dispatch_id]);
    $dsr_id = $dsrStmt->fetchColumn();

    if (!$dsr_id) {
        echo json_encode(['success' => false, 'message' => 'Dispatch not found']);
        exit;
    }

    $pdo->beginTransaction();

    // Check if DSR already submitted a pending settlement
    $existStmt = $pdo->prepare('SELECT id FROM cash_settlements WHERE dispatch_id=? LIMIT 1');
    $existStmt->execute([$dispatch_id]);
    $existId = $existStmt->fetchColumn();

    if ($existId) {
        $up = $pdo->prepare('UPDATE cash_settlements SET amount_expected=?, amount_submitted=?, difference=?, notes=?, settlement_date=CURDATE(), manager_id=?, status="approved" WHERE id=?');
        $up->execute([$amount_expected, $amount_submitted, $difference, $notes, $manager_id, $existId]);
    } else {
        $ins = $pdo->prepare('INSERT INTO cash_settlements (dsr_id, dispatch_id, amount_expected, amount_submitted, difference, settlement_date, manager_id, status, notes) VALUES (?,?,?,?,?,CURDATE(),?,"approved",?)');
        $ins->execute([$dsr_id, $dispatch_id, $amount_expected, $amount_submitted, $difference, $manager_id, $notes]);
    }

    // Update dispatch to settled
    $pdo->prepare('UPDATE dispatches SET status="settled" WHERE id=?')->execute([$dispatch_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Manual settlement saved!']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid Request']);
