<?php
// api/companies.php — CRUD for companies
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $rows = $pdo->query('SELECT c.*, d.name as dealer_name FROM companies c JOIN dealers d ON d.id=c.dealer_id WHERE c.status=1 ORDER BY c.id DESC')->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'POST':
        $d = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO companies (dealer_id, name, contact, address) VALUES (?,?,?,?)');
        $stmt->execute([$d['dealer_id'], trim($d['name']), $d['contact'] ?? '', $d['address'] ?? '']);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Company created']);
        break;

    case 'PUT':
        $d = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare('UPDATE companies SET dealer_id=?, name=?, contact=?, address=?, status=? WHERE id=?')
            ->execute([$d['dealer_id'], trim($d['name']), $d['contact'] ?? '', $d['address'] ?? '', $d['status'] ?? 1, $d['id']]);
        echo json_encode(['success' => true, 'message' => 'Company updated']);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        $pdo->prepare('UPDATE companies SET status=0 WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Company deleted']);
        break;
}
