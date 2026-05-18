<?php
/**
 * scratch/seed_second_company.php — Seeds a second company & products for multi-company scan test.
 */
require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

try {
    $wid = $pdo->query("SELECT id FROM warehouses LIMIT 1")->fetchColumn();
    $uid = $pdo->query("SELECT id FROM users WHERE email='manager@happy.com'")->fetchColumn();
    $did = $pdo->query("SELECT id FROM dealers LIMIT 1")->fetchColumn();

    // 1. Create a second Company (Outlet)
    $pdo->exec("INSERT IGNORE INTO companies (dealer_id, name, contact, address) VALUES ($did, 'Global Foods Ltd', '01900000000', 'Sylhet')");
    $cid2 = $pdo->query("SELECT id FROM companies WHERE name='Global Foods Ltd'")->fetchColumn();
    echo "✔ Second Company ID: $cid2<br>";

    // 2. Create Category for second company
    $pdo->exec("INSERT IGNORE INTO categories (company_id, name) VALUES ($cid2, 'Cookies & Biscuits')");
    $catid2 = $pdo->query("SELECT id FROM categories WHERE company_id=$cid2 LIMIT 1")->fetchColumn();
    echo "✔ Category ID: $catid2<br>";

    // 3. Create Product (Choco Cookies)
    $pdo->exec("INSERT IGNORE INTO products (company_id, category_id, name, pieces_per_box, selling_price, status) 
                VALUES ($cid2, $catid2, 'Choco Cookies Supreme', 12, 45.00, 1)
                ON DUPLICATE KEY UPDATE selling_price=45.00");
    $pid2 = $pdo->query("SELECT id FROM products WHERE name='Choco Cookies Supreme'")->fetchColumn();
    echo "✔ Second Product ID: $pid2<br>";

    // 4. Seed Inventory
    $pdo->exec("INSERT IGNORE INTO inventory (product_id, warehouse_id, qty_boxes, qty_pieces) VALUES ($pid2, $wid, 15, 0)");
    echo "✔ Second Product Stock Seeded: 15 boxes<br>";

    // 5. Create Lot & Lot Items
    $pdo->exec("INSERT IGNORE INTO lots (company_id, warehouse_id, manager_id, lot_date, status) VALUES ($cid2, $wid, $uid, '2026-05-18', 1)");
    $lotId2 = $pdo->query("SELECT id FROM lots ORDER BY id DESC LIMIT 1")->fetchColumn();
    
    $pdo->exec("INSERT IGNORE INTO lot_items (lot_id, product_id, qty_boxes, buying_price, total, qr_generated) VALUES ($lotId2, $pid2, 3, 30.00, 1080.00, 1)");
    $lotItemId2 = $pdo->query("SELECT id FROM lot_items ORDER BY id DESC LIMIT 1")->fetchColumn();

    // Create 2 active QR codes for second product
    $qrs = [
      ['qr_uid' => 'CC-9001', 'serial' => 201],
      ['qr_uid' => 'CC-9002', 'serial' => 202]
    ];
    foreach ($qrs as $q) {
      $pdo->prepare("INSERT IGNORE INTO qr_codes (lot_item_id, product_id, lot_id, qr_uid, serial_number, pieces_total, pieces_remaining, status)
                     VALUES (?, ?, ?, ?, ?, 12, 12, 'active')")
          ->execute([$lotItemId2, $pid2, $lotId2, $q['qr_uid'], $q['serial']]);
    }
    
    echo "✔ Seeded 2 Active QR Codes for Choco Cookies:<br>";
    echo " &nbsp; - <b>CC-9001</b> (12 pieces remaining)<br>";
    echo " &nbsp; - <b>CC-9002</b> (12 pieces remaining)<br>";
    echo "<br><h3 style='color:green'>Second Company Seeding Successful!</h3>";

} catch (Exception $e) {
    echo "<h3 style='color:red'>Second Company Seeding Failed: " . $e->getMessage() . "</h3>";
}
