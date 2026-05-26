<?php
// api/inventory.php
ob_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole(['admin','manager']);

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'logs') {
            $product_id = intval($_GET['product_id'] ?? 0);
            $warehouse_id = $_SESSION['warehouse_id'] ?? intval($_GET['warehouse_id'] ?? 0);
            
            $from_date = $_GET['from_date'] ?? null;
            $to_date = $_GET['to_date'] ?? null;
            
            $query = 'SELECT il.*, u.name as user_name FROM inventory_logs il LEFT JOIN users u ON u.id = il.user_id WHERE il.product_id = ? AND il.warehouse_id = ?';
            $params = [$product_id, $warehouse_id];
            
            if ($from_date) {
                $query .= ' AND DATE(il.created_at) >= ?';
                $params[] = $from_date;
            }
            if ($to_date) {
                $query .= ' AND DATE(il.created_at) <= ?';
                $params[] = $to_date;
            }
            
            $query .= ' ORDER BY il.created_at DESC';
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            ob_clean();
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            exit;
        }
        break;
}

ob_clean();
echo json_encode(['success' => false, 'message' => 'Invalid action']);
