<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

$stmt = $pdo->prepare("UPDATE order_items SET qty_pieces = 500, qty_boxes_display = 10, qty_pieces_remainder = 0 WHERE order_id = 10 AND product_id = 4");
$stmt->execute();

echo "Order ID 10 has been successfully updated to 500 pieces (10 boxes)!\n";
