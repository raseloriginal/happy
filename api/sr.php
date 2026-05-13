<?php
// api/sr.php — CRUD for Sales Representatives
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $rows = $pdo->query('SELECT s.*, u.name, u.email, u.phone, c.name as company_name, r.name as route_name FROM sr s JOIN users u ON u.id=s.user_id JOIN companies c ON c.id=s.company_id LEFT JOIN routes r ON r.id=s.route_id WHERE s.status=1 ORDER BY s.id DESC')->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'POST':
        $d = json_decode(file_get_contents('php://input'), true);
        $pdo->beginTransaction();
        $hash = password_hash($d['password'], PASSWORD_DEFAULT);
        $u = $pdo->prepare('INSERT INTO users (name, email, password, phone, role) VALUES (?,?,?,?,?)');
        $u->execute([$d['name'], $d['email'], $hash, $d['phone'] ?? '', 'sr']);
        $uid = $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO sr (user_id, company_id, route_id) VALUES (?,?,?)')->execute([$uid, $d['company_id'], $d['route_id'] ?? null]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'SR created']);
        break;

    case 'PUT':
        $d = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare('UPDATE sr SET company_id=?, route_id=?, status=? WHERE id=?')->execute([$d['company_id'], $d['route_id'] ?? null, $d['status'] ?? 1, $d['id']]);
        $pdo->prepare('UPDATE users SET name=?, phone=? WHERE id=?')->execute([$d['name'], $d['phone'] ?? '', $d['user_id']]);
        echo json_encode(['success' => true, 'message' => 'SR updated']);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        $pdo->prepare('UPDATE sr SET status=0 WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'SR deleted']);
        break;
}
