<?php
// api/routes.php — CRUD for routes
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $rows = $pdo->query('SELECT r.*, w.name as warehouse_name FROM routes r LEFT JOIN warehouses w ON w.id=r.warehouse_id WHERE r.status=1 ORDER BY r.id DESC')->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'POST':
        $d = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare('INSERT INTO routes (name, area, warehouse_id) VALUES (?,?,?)')->execute([trim($d['name']), $d['area'] ?? '', $d['warehouse_id'] ?? null]);
        echo json_encode(['success' => true, 'message' => 'Route created']);
        break;

    case 'PUT':
        $d = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare('UPDATE routes SET name=?, area=?, warehouse_id=?, status=? WHERE id=?')->execute([trim($d['name']), $d['area'] ?? '', $d['warehouse_id'] ?? null, $d['status'] ?? 1, $d['id']]);
        echo json_encode(['success' => true, 'message' => 'Route updated']);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        $pdo->prepare('UPDATE routes SET status=0 WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Route deleted']);
        break;
}
