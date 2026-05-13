<?php
// api/expenses.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole(['admin','manager','dsr']);

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $dsr_id = $_GET['dsr_id'] ?? 0;
        $stmt   = $pdo->prepare('SELECT * FROM expenses WHERE dsr_id=? ORDER BY id DESC');
        $stmt->execute([$dsr_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'POST':
        $d = json_decode(file_get_contents('php://input'), true);
        // Get DSR id from session user
        $dsr = $pdo->prepare('SELECT id FROM dsr WHERE user_id=? LIMIT 1');
        $dsr->execute([$_SESSION['user_id']]);
        $dsr_id = $dsr->fetchColumn();
        if (!$dsr_id) { echo json_encode(['success'=>false,'message'=>'DSR not found']); exit; }
        $pdo->prepare('INSERT INTO expenses (dsr_id, dispatch_id, amount, description, expense_date, status) VALUES (?,?,?,?,?,?)')
            ->execute([$dsr_id, $d['dispatch_id'] ?? null, $d['amount'], $d['description'], $d['expense_date'], 'pending']);
        echo json_encode(['success' => true]);
        break;

    case 'PUT':
        $d = json_decode(file_get_contents('php://input'), true);
        requireRole(['admin','manager']);
        $pdo->prepare('UPDATE expenses SET status=? WHERE id=?')->execute([$d['status'], $d['id']]);
        echo json_encode(['success' => true]);
        break;
}
