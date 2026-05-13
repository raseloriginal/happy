<?php
// api/cashflow.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole(['admin','manager']);

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $did = intval($_GET['dispatch_id'] ?? 0);
    // Expected: sum(qty_out * selling_price) for dispatch
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(di.qty_out * p.selling_price),0) as expected FROM dispatch_items di JOIN products p ON p.id=di.product_id WHERE di.dispatch_id=?');
    $stmt->execute([$did]);
    $row = $stmt->fetch();
    echo json_encode(['success' => true, 'expected' => floatval($row['expected'])]);
    exit;
}

if ($method === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    // Get manager + dsr
    $mgr = $pdo->prepare('SELECT id FROM managers WHERE user_id=? LIMIT 1');
    $mgr->execute([$_SESSION['user_id']]);
    $manager_id = $mgr->fetchColumn() ?: 0;

    $dsr = $pdo->prepare('SELECT dsr_id FROM dispatches WHERE id=?');
    $dsr->execute([$d['dispatch_id']]);
    $dsr_id = $dsr->fetchColumn();

    $pdo->prepare('INSERT INTO cash_settlements (dsr_id, dispatch_id, amount_expected, amount_submitted, difference, settlement_date, manager_id, notes) VALUES (?,?,?,?,?,CURDATE(),?,?)')
        ->execute([$dsr_id, $d['dispatch_id'], $d['amount_expected'], $d['amount_submitted'], $d['difference'], $manager_id, $d['notes'] ?? '']);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid']);
