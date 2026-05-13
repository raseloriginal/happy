<?php
// api/inventory.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole(['admin','manager']);

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$wid    = $_SESSION['warehouse_id'] ?? 0;

$where  = ['i.warehouse_id=?'];
$params = [$wid];
if (!empty($_GET['company_id'])) { $where[] = 'p.company_id=?'; $params[] = $_GET['company_id']; }
if (!empty($_GET['category_id'])){ $where[] = 'p.category_id=?'; $params[] = $_GET['category_id']; }

$sql = 'SELECT i.*, p.name as product_name, p.pieces_per_box, p.selling_price, co.name as company_name, cat.name as category_name, w.name as warehouse_name
        FROM inventory i
        JOIN products p ON p.id=i.product_id
        JOIN companies co ON co.id=p.company_id
        LEFT JOIN categories cat ON cat.id=p.category_id
        JOIN warehouses w ON w.id=i.warehouse_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY i.qty_boxes ASC, p.name';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
