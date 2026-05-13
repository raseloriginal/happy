<?php
// manager/return_custom.php — Custom return modal handler (used by returns.php)
// This file handles the custom partial return flow via AJAX
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole(['admin','manager']);

$pdo = getDB();
$did = intval($_GET['dispatch_id'] ?? 0);

if (!$did) {
    echo json_encode(['success' => false, 'message' => 'Dispatch ID required']);
    exit;
}

// Return all dispatched boxes for this dispatch with piece info
$stmt = $pdo->prepare('
    SELECT di.qr_code_id, qr.qr_uid, qr.pieces_total, qr.pieces_remaining, qr.status as qr_status,
           p.id as product_id, p.name as product_name, p.pieces_per_box
    FROM dispatch_items di
    JOIN qr_codes qr ON qr.id = di.qr_code_id
    JOIN products p ON p.id = di.product_id
    WHERE di.dispatch_id = ?
    ORDER BY p.name, qr.serial_number
');
$stmt->execute([$did]);
$boxes = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $boxes]);
