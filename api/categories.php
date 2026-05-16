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
            // Include categories for this company OR global categories (company_id IS NULL)
            $rows = $pdo->prepare('SELECT id, name FROM categories WHERE (company_id=? OR company_id IS NULL) AND status=1 ORDER BY name');
            $rows->execute([$cid]);
            echo json_encode(['success' => true, 'data' => $rows->fetchAll()]);
        } else {
            // Use LEFT JOIN to show categories even if company_id is NULL
            $rows = $pdo->query('SELECT c.*, co.name as company_name FROM categories c LEFT JOIN companies co ON co.id=c.company_id WHERE c.status=1 ORDER BY c.id DESC')->fetchAll();
            echo json_encode(['success' => true, 'data' => $rows]);
        }
        break;
    case 'POST':
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        $company_id = !empty($d['company_id']) ? $d['company_id'] : null;
        $names = $d['names'] ?? [];
        if (empty($names) && !empty($d['name'])) $names = [$d['name']];

        $stmt = $pdo->prepare('INSERT INTO categories (company_id, name) VALUES (?,?)');
        try {
            foreach ($names as $name) {
                if (trim($name)) $stmt->execute([$company_id, trim($name)]);
            }
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'message' => 'Category already exists.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;
    case 'PUT':
        $d = json_decode(file_get_contents('php://input'), true);
        try {
            $pdo->prepare('UPDATE categories SET company_id=?, name=?, status=? WHERE id=?')->execute([$d['company_id'] ?: null, trim($d['name']), $d['status'] ?? 1, $d['id']]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'message' => 'Category name already exists.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;
    case 'DELETE':
        $pdo->prepare('UPDATE categories SET status=0 WHERE id=?')->execute([$_GET['id'] ?? 0]);
        echo json_encode(['success' => true]);
        break;
}
