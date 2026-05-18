<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

echo "=== PRODUCTS ===\n";
$products = $pdo->query("SELECT id, name, pieces_per_box, selling_price FROM products")->fetchAll();
foreach ($products as $p) {
    echo "ID: {$p['id']} | Name: {$p['name']} | Pieces/Box: {$p['pieces_per_box']} | Price: {$p['selling_price']}\n";
}

echo "\n=== LAST 5 ORDERS ===\n";
$orders = $pdo->query("SELECT id, status, order_date FROM orders ORDER BY id DESC LIMIT 5")->fetchAll();
foreach ($orders as $o) {
    echo "Order ID: {$o['id']} | Status: {$o['status']} | Date: {$o['order_date']}\n";
    $items = $pdo->query("SELECT oi.*, p.name as product_name, p.pieces_per_box FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id = {$o['id']}")->fetchAll();
    foreach ($items as $item) {
        echo "  - Product: {$item['product_name']} (ID: {$item['product_id']}) | Qty Pieces: {$item['qty_pieces']} | Pieces/Box: {$item['pieces_per_box']} | Display Boxes: {$item['qty_boxes_display']} | Remainder: {$item['qty_pieces_remainder']}\n";
    }
}
