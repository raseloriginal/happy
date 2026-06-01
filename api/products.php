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
        if ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Product ID required.']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT image FROM products WHERE id=?");
            $stmt->execute([$id]);
            $currentImg = $stmt->fetchColumn();
            
            $image = $currentImg;
            if (!empty($_FILES['image']['tmp_name'])) {
                $ext   = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allow = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($ext, $allow)) {
                    $dir = __DIR__ . '/../assets/img/products/';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $fname = md5(uniqid()) . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname);
                    $image = 'assets/img/products/' . $fname;
                }
            }

            try {
                $pdo->prepare('UPDATE products SET company_id=?, category_id=?, name=?, box_type=?, pieces_per_box=?, selling_price=?, dealer_percentage=?, status=?, image=? WHERE id=?')
                    ->execute([
                        $_POST['company_id'],
                        $_POST['category_id'] ?: null,
                        trim($_POST['name']),
                        $_POST['box_type'] ?? '',
                        $_POST['pieces_per_box'],
                        $_POST['selling_price'] ?? 0,
                        $_POST['dealer_percentage'] ?? 0,
                        $_POST['status'] ?? 1,
                        $image,
                        $id
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
            exit;
        }

        $company_id = $_POST['company_id'] ?? 0;
        $items = [];

        if (isset($_POST['name'])) {
            // Single item (legacy/fallback)
            $items[] = [
                'name'           => $_POST['name'],
                'category_id'    => $_POST['category_id'] ?? null,
                'box_type'       => $_POST['box_type'] ?? '',
                'pieces_per_box' => $_POST['pieces_per_box'] ?? 1,
                'dealer_percentage' => $_POST['dealer_percentage'] ?? 0,
                'initial_boxes'  => $_POST['initial_boxes'] ?? 0,
                'initial_pieces' => $_POST['initial_pieces'] ?? 0,
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
                $product_id = $pdo->lastInsertId();

                $init_boxes = (int)($item['initial_boxes'] ?? 0);
                $init_pcs   = (int)($item['initial_pieces'] ?? 0);
                $wid = $_SESSION['warehouse_id'] ?? 0;
                
                if ($wid > 0 && ($init_boxes > 0 || $init_pcs > 0)) {
                    $pdo->prepare('INSERT INTO inventory (product_id, warehouse_id, qty_boxes, qty_pieces) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE qty_boxes = qty_boxes + VALUES(qty_boxes), qty_pieces = qty_pieces + VALUES(qty_pieces)')
                        ->execute([$product_id, $wid, $init_boxes, $init_pcs]);
                    logInventoryActivity($pdo, $product_id, $wid, 'initial_stock', null, $init_boxes, $init_pcs, 'Initial stock entry');
                }
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
        
        if ($action === 'edit_stock') {
            $wid = $_SESSION['warehouse_id'] ?? 0;
            if (!$wid) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'No warehouse assigned to session.']);
                exit;
            }
            $product_id    = (int)$d['id'];
            $new_boxes     = (int)$d['qty_boxes'];
            $new_pieces    = (int)$d['qty_pieces'];
            $note          = trim($d['note'] ?? '');
            $buying_price  = isset($d['buying_price']) ? floatval($d['buying_price']) : null;
            
            $stmt = $pdo->prepare('SELECT qty_boxes, qty_pieces FROM inventory WHERE product_id=? AND warehouse_id=?');
            $stmt->execute([$product_id, $wid]);
            $current = $stmt->fetch();
            
            $curr_b = $current ? (int)$current['qty_boxes'] : 0;
            $curr_p = $current ? (int)$current['qty_pieces'] : 0;
            
            $diff_b = $new_boxes - $curr_b;
            $diff_p = $new_pieces - $curr_p;
            
            // Always update/insert to ensure inventory row exists
            $pdo->prepare('INSERT INTO inventory (product_id, warehouse_id, qty_boxes, qty_pieces) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE qty_boxes = VALUES(qty_boxes), qty_pieces = VALUES(qty_pieces)')
                ->execute([$product_id, $wid, $new_boxes, $new_pieces]);
            
            // Auto-migrate: add buying_price column to products if not exists
            try { $pdo->query("SELECT buying_price FROM products LIMIT 0"); }
            catch (PDOException $e) {
                $pdo->exec("ALTER TABLE products ADD COLUMN buying_price DECIMAL(10,2) DEFAULT NULL");
            }

            // Update buying price on the product if provided, and recalculate selling_price
            if ($buying_price !== null && $buying_price >= 0) {
                // Get dealer_percentage and pieces_per_box for this product
                $prodStmt = $pdo->prepare('SELECT pieces_per_box, dealer_percentage FROM products WHERE id=?');
                $prodStmt->execute([$product_id]);
                $prod = $prodStmt->fetch();

                if ($prod) {
                    $ppb = (float)($prod['pieces_per_box'] ?: 1);
                    $dp  = (float)($prod['dealer_percentage'] ?: 0);
                    // Same formula as lots: selling_price per piece = buying_price_per_box * (1 + dp/100) / ppb
                    $selling_price_box   = $buying_price * (1 + ($dp / 100));
                    $selling_price_piece = $selling_price_box / $ppb;

                    $pdo->prepare('UPDATE products SET buying_price=?, selling_price=? WHERE id=?')
                        ->execute([$buying_price, $selling_price_piece, $product_id]);
                } else {
                    $pdo->prepare('UPDATE products SET buying_price=? WHERE id=?')
                        ->execute([$buying_price, $product_id]);
                }
                if (!$note) $note = 'Buying price updated to ৳' . number_format($buying_price, 2) . '/box';
            }

            // Only log if there's an actual change, OR if it's their very first time saving stock for this product (i.e. current was false)
            if ($diff_b != 0 || $diff_p != 0 || !$current) {
                logInventoryActivity($pdo, $product_id, $wid, 'edit_stock', null, $diff_b, $diff_p, $note ?: 'Initial stock confirmation');
            }
            ob_clean();
            echo json_encode(['success' => true]);
            exit;
        }

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
