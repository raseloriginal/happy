<?php
// api/products.php
ob_start(); // Buffer all output to prevent stray notices/warnings from corrupting JSON
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
            ob_clean();
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } else {
            $rows = $pdo->query('SELECT p.*, co.name as company_name, cat.name as category_name FROM products p JOIN companies co ON co.id=p.company_id LEFT JOIN categories cat ON cat.id=p.category_id WHERE p.status=1 ORDER BY p.id DESC')->fetchAll();
            ob_clean();
            echo json_encode(['success' => true, 'data' => $rows]);
        }
        break;

    case 'POST':
        $company_id = $_POST['company_id'] ?? 0;
        $items = [];

        if (isset($_POST['name'])) {
            // Single item (legacy/fallback)
            $items[] = [
                'name'           => $_POST['name'],
                'category_id'    => $_POST['category_id'] ?? null,
                'box_type'       => $_POST['box_type'] ?? '',
                'pieces_per_box' => $_POST['pieces_per_box'] ?? 1,
                'selling_price'  => $_POST['selling_price'] ?? 0,
                'dealer_percentage' => $_POST['dealer_percentage'] ?? 0,
                'image_idx'      => null,
            ];
        } elseif (isset($_POST['bulk'])) {
            $decoded = json_decode($_POST['bulk'], true);
            if (!is_array($decoded)) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid bulk data: could not parse product list.']);
                exit;
            }
            $items = $decoded;
        }

        if (empty($items)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'No products to save.']);
            exit;
        }

        $errors = [];
        foreach ($items as $idx => $item) {
            $name = trim($item['name'] ?? '');
            if (!$name) continue;

            $cat_id = $item['category_id'] ?: null;
            $box_t  = $item['box_type'] ?? '';
            $ppb    = (int)($item['pieces_per_box'] ?? 1);
            $price  = (float)($item['selling_price'] ?? 0);
            $dp     = (float)($item['dealer_percentage'] ?? 0);
            $image  = null;

            $fileKey = ($item['image_idx'] !== null) ? 'image_' . $item['image_idx'] : 'image';
            if (!empty($_FILES[$fileKey]['tmp_name'])) {
                $ext   = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
                $allow = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($ext, $allow)) {
                    $dir = __DIR__ . '/../assets/img/products/';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $fname = md5(uniqid()) . '.' . $ext;
                    move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dir . $fname);
                    $image = 'assets/img/products/' . $fname;
                }
            }

            try {
                $pdo->prepare('INSERT INTO products (company_id, category_id, name, box_type, image, pieces_per_box, selling_price, dealer_percentage) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$company_id, $cat_id, $name, $box_t, $image, $ppb, $price, $dp]);
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $errors[] = "\"$name\" already exists.";
                } else {
                    $errors[] = "\"$name\": " . $e->getMessage();
                }
            }
        }

        ob_clean();
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => 'Some products failed: ' . implode('; ', $errors)]);
        } else {
            echo json_encode(['success' => true]);
        }
        break;

    case 'PUT':
        $d = json_decode(file_get_contents('php://input'), true);
        try {
            $pdo->prepare('UPDATE products SET company_id=?, category_id=?, name=?, box_type=?, pieces_per_box=?, selling_price=?, dealer_percentage=?, status=? WHERE id=?')
                ->execute([
                    $d['company_id'],
                    $d['category_id'] ?: null,
                    trim($d['name']),
                    $d['box_type'] ?? '',
                    $d['pieces_per_box'],
                    $d['selling_price'] ?? 0,
                    $d['dealer_percentage'] ?? 0,
                    $d['status'] ?? 1,
                    $d['id']
                ]);
            ob_clean();
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            ob_clean();
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'message' => 'Product name already exists for this company.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;

    case 'DELETE':
        $pdo->prepare('UPDATE products SET status=0 WHERE id=?')->execute([$_GET['id'] ?? 0]);
        ob_clean();
        echo json_encode(['success' => true]);
        break;
}
