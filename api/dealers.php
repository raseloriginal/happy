<?php
// api/dealers.php — CRUD for dealers
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole('admin');

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $rows = $pdo->query('SELECT d.*, u.name as user_name, u.email FROM dealers d JOIN users u ON u.id=d.user_id WHERE d.status=1 ORDER BY d.id DESC')->fetchAll();
            echo json_encode(['success' => true, 'data' => $rows]);
            break;

        case 'POST':
            $d = json_decode(file_get_contents('php://input'), true);
            $pdo->beginTransaction();
            // Create user
            $hash = password_hash($d['password'], PASSWORD_DEFAULT);
            $u = $pdo->prepare('INSERT INTO users (name, email, password, phone, role) VALUES (?,?,?,?,?)');
            $u->execute([$d['name'], $d['email'], $hash, $d['phone'] ?? '', 'dealer']);
            $uid = $pdo->lastInsertId();
            // Create dealer
            $dl = $pdo->prepare('INSERT INTO dealers (user_id, name, phone, address) VALUES (?,?,?,?)');
            $dl->execute([$uid, $d['name'], $d['phone'] ?? '', $d['address'] ?? '']);
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Dealer created']);
            break;

        case 'PUT':
            $d = json_decode(file_get_contents('php://input'), true);
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE dealers SET name=?, phone=?, address=?, status=? WHERE id=?')
                ->execute([$d['name'], $d['phone'] ?? '', $d['address'] ?? '', $d['status'] ?? 1, $d['id']]);
            if (!empty($d['password'])) {
                $hash = password_hash($d['password'], PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE users SET name=?, email=?, password=?, phone=? WHERE id=?')
                    ->execute([$d['name'], $d['email'], $hash, $d['phone'] ?? '', $d['user_id']]);
            } else {
                $pdo->prepare('UPDATE users SET name=?, email=?, phone=? WHERE id=?')
                    ->execute([$d['name'], $d['email'], $d['phone'] ?? '', $d['user_id']]);
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Dealer updated']);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? 0;
            $pdo->prepare('UPDATE dealers SET status=0 WHERE id=?')->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Dealer deleted']);
            break;
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $msg = $e->getMessage();
    if (strpos($msg, 'Duplicate entry') !== false) {
        $msg = 'A user with this email already exists.';
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $msg]);
}
