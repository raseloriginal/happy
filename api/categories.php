<?php
// api/categories.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole(['admin','manager']);

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'by_company') {
            $cid = $_GET['company_id'] ?? 0;
            $rows = $pdo->prepare('SELECT id, name FROM categories WHERE company_id=? AND status=1 ORDER BY name');
            $rows->execute([$cid]);
            echo json_encode(['success' => true, 'data' => $rows->fetchAll()]);
        } else {
            $rows = $pdo->query('SELECT c.*, co.name as company_name FROM categories c JOIN companies co ON co.id=c.company_id WHERE c.status=1 ORDER BY c.id DESC')->fetchAll();
            echo json_encode(['success' => true, 'data' => $rows]);
        }
        break;
    case 'POST':
        $d = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare('INSERT INTO categories (company_id, name) VALUES (?,?)')->execute([$d['company_id'], trim($d['name'])]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;
    case 'PUT':
        $d = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare('UPDATE categories SET company_id=?, name=?, status=? WHERE id=?')->execute([$d['company_id'], trim($d['name']), $d['status'] ?? 1, $d['id']]);
        echo json_encode(['success' => true]);
        break;
    case 'DELETE':
        $pdo->prepare('UPDATE categories SET status=0 WHERE id=?')->execute([$_GET['id'] ?? 0]);
        echo json_encode(['success' => true]);
        break;
}
