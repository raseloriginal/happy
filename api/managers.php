<?php
// api/managers.php — CRUD for managers
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $rows = $pdo->query('SELECT m.*, u.name, u.email, u.phone, w.name as warehouse_name FROM managers m JOIN users u ON u.id=m.user_id JOIN warehouses w ON w.id=m.warehouse_id WHERE m.status=1 ORDER BY m.id DESC')->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'POST':
        $d = json_decode(file_get_contents('php://input'), true);
        $pdo->beginTransaction();
        $hash = password_hash($d['password'], PASSWORD_DEFAULT);
        $u = $pdo->prepare('INSERT INTO users (name, email, password, phone, role) VALUES (?,?,?,?,?)');
        $u->execute([$d['name'], $d['email'], $hash, $d['phone'] ?? '', 'manager']);
        $uid = $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO managers (user_id, warehouse_id) VALUES (?,?)')->execute([$uid, $d['warehouse_id']]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Manager created']);
        break;

    case 'PUT':
        $d = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare('UPDATE managers SET warehouse_id=?, status=? WHERE id=?')->execute([$d['warehouse_id'], $d['status'] ?? 1, $d['id']]);
        $pdo->prepare('UPDATE users SET name=?, phone=? WHERE id=?')->execute([$d['name'], $d['phone'] ?? '', $d['user_id']]);
        echo json_encode(['success' => true, 'message' => 'Manager updated']);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        $pdo->prepare('UPDATE managers SET status=0 WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Manager deleted']);
        break;
}
