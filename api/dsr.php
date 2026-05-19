<?php
// api/dsr.php — CRUD for Delivery Sales Representatives
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $rows = $pdo->query('SELECT d.*, u.name, u.email, u.phone, w.name as warehouse_name FROM dsr d JOIN users u ON u.id=d.user_id JOIN warehouses w ON w.id=d.warehouse_id WHERE d.status=1 ORDER BY d.id DESC')->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'POST':
        $d = json_decode(file_get_contents('php://input'), true);
        $pdo->beginTransaction();
        $hash = password_hash($d['password'], PASSWORD_DEFAULT);
        $u = $pdo->prepare('INSERT INTO users (name, email, password, phone, role) VALUES (?,?,?,?,?)');
        $u->execute([$d['name'], $d['email'], $hash, $d['phone'] ?? '', 'dsr']);
        $uid = $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO dsr (user_id, warehouse_id) VALUES (?,?)')->execute([$uid, $d['warehouse_id']]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'DSR created']);
        break;

    case 'PUT':
        $d = json_decode(file_get_contents('php://input'), true);
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE dsr SET warehouse_id=?, status=? WHERE id=?')->execute([$d['warehouse_id'], $d['status'] ?? 1, $d['id']]);
            if (!empty($d['password'])) {
                $hash = password_hash($d['password'], PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE users SET name=?, email=?, password=?, phone=? WHERE id=?')
                    ->execute([$d['name'], $d['email'], $hash, $d['phone'] ?? '', $d['user_id']]);
            } else {
                $pdo->prepare('UPDATE users SET name=?, email=?, phone=? WHERE id=?')
                    ->execute([$d['name'], $d['email'], $d['phone'] ?? '', $d['user_id']]);
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'DSR updated']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $msg = $e->getMessage();
            if (strpos($msg, 'Duplicate entry') !== false) {
                $msg = 'A user with this email already exists.';
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $msg]);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        $pdo->prepare('UPDATE dsr SET status=0 WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'DSR deleted']);
        break;
}
