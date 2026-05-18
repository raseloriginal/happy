<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

$qrs = $pdo->query("SELECT qr.id, qr.qr_uid, qr.status, qr.pieces_remaining, p.name as product_name, p.id as product_id FROM qr_codes qr JOIN products p ON p.id=qr.product_id WHERE qr.status='active'")->fetchAll();
echo "=== ACTIVE QR CODES ===\n";
foreach ($qrs as $q) {
    echo "UID: {$q['qr_uid']} | Product: {$q['product_name']} (ID: {$q['product_id']}) | Remaining: {$q['pieces_remaining']}\n";
}
