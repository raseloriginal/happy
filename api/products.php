<?php
// api/products.php
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
            $cid  = $_GET['company_id'] ?? 0;
            $stmt = $pdo->prepare('SELECT p.*, cat.name as category_name FROM products p LEFT JOIN categories cat ON cat.id=p.category_id WHERE p.company_id=? AND p.status=1 ORDER BY p.name');
            $stmt->execute([$cid]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } else {
            $rows = $pdo->query('SELECT p.*, co.name as company_name, cat.name as category_name FROM products p JOIN companies co ON co.id=p.company_id LEFT JOIN categories cat ON cat.id=p.category_id WHERE p.status=1 ORDER BY p.id DESC')->fetchAll();
            echo json_encode(['success' => true, 'data' => $rows]);
        }
        break;

    case 'POST':
        $name       = trim($_POST['name'] ?? '');
        $company_id = $_POST['company_id'] ?? 0;
        $cat_id     = $_POST['category_id'] ?: null;
        $ppb        = $_POST['pieces_per_box'] ?? 1;
        $price      = $_POST['selling_price'] ?? 0;
        $image      = null;

        if (!empty($_FILES['image']['tmp_name'])) {
            $ext   = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allow = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allow)) {
                $dir  = __DIR__ . '/../assets/img/products/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = md5(uniqid()) . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname);
                $image = 'assets/img/products/' . $fname;
            }
        }

        $pdo->prepare('INSERT INTO products (company_id, category_id, name, image, pieces_per_box, selling_price) VALUES (?,?,?,?,?,?)')->execute([$company_id, $cat_id, $name, $image, $ppb, $price]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'PUT':
        $d = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare('UPDATE products SET company_id=?, category_id=?, name=?, pieces_per_box=?, selling_price=?, status=? WHERE id=?')
            ->execute([$d['company_id'], $d['category_id'] ?: null, trim($d['name']), $d['pieces_per_box'], $d['selling_price'], $d['status'] ?? 1, $d['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        $pdo->prepare('UPDATE products SET status=0 WHERE id=?')->execute([$_GET['id'] ?? 0]);
        echo json_encode(['success' => true]);
        break;
}
