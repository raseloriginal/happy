<?php
// api/warehouses.php — CRUD for warehouses
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        $rows = $pdo->query('SELECT * FROM warehouses ORDER BY id DESC')->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'POST':
        $d = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO warehouses (name, address, area) VALUES (?,?,?)');
        $stmt->execute([trim($d['name']), trim($d['address'] ?? ''), trim($d['area'] ?? '')]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Warehouse created']);
        break;

    case 'PUT':
        $d = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('UPDATE warehouses SET name=?, address=?, area=?, status=? WHERE id=?');
        $stmt->execute([trim($d['name']), trim($d['address'] ?? ''), trim($d['area'] ?? ''), $d['status'] ?? 1, $d['id']]);
        echo json_encode(['success' => true, 'message' => 'Warehouse updated']);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        $pdo->prepare('UPDATE warehouses SET status=0 WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Warehouse deleted']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
