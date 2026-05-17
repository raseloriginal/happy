<?php
/**
 * scratch/seed_test_data.php — Seeds active test data for Ready Sale scanning.
 * Visit: http://localhost/happycrm2/scratch/seed_test_data.php
 */

require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

echo "<h2>Happy Bangladesh ERP — Test Data Seeder</h2>";

try {
    // 1. Ensure Warehouse exists
    $pdo->exec("INSERT IGNORE INTO warehouses (name, address, area) VALUES ('Test Warehouse', 'Dhaka, Bangladesh', 'Dhaka')");
    $wid = $pdo->query("SELECT id FROM warehouses LIMIT 1")->fetchColumn();
    echo "✔ Warehouse ID: $wid<br>";

    // 2. Ensure Manager exists
    $hash = password_hash('manager123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role, status) VALUES (?,?,?,?,?)")
        ->execute(['Test Manager', 'manager@happy.com', $hash, 'manager', 1]);
    $uid = $pdo->query("SELECT id FROM users WHERE email='manager@happy.com'")->fetchColumn();
    
    // Link Manager to Warehouse
    $pdo->exec("INSERT IGNORE INTO managers (user_id, warehouse_id) VALUES ($uid, $wid)");
    echo "✔ Manager User ID: $uid (manager@happy.com / manager123)<br>";

    // 3. Ensure Dealer exists
    $dealerUserHash = password_hash('dealer123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role, status) VALUES (?,?,?,?,?)")
        ->execute(['Test Dealer', 'dealer@happy.com', $dealerUserHash, 'dealer', 1]);
    $duid = $pdo->query("SELECT id FROM users WHERE email='dealer@happy.com'")->fetchColumn();
    
    $pdo->exec("INSERT IGNORE INTO dealers (user_id, name, phone, address) VALUES ($duid, 'Test Dealer Co', '01700000000', 'Dhaka')");
    $did = $pdo->query("SELECT id FROM dealers WHERE user_id=$duid")->fetchColumn();
    echo "✔ Dealer ID: $did<br>";

    // 4. Ensure Company (Customer Outlet) exists
    $pdo->exec("INSERT IGNORE INTO companies (dealer_id, name, contact, address) VALUES ($did, 'Test Outlet Ltd', '01800000000', 'Chittagong')");
    $cid = $pdo->query("SELECT id FROM companies WHERE name='Test Outlet Ltd'")->fetchColumn();
    echo "✔ Company (Outlet) ID: $cid<br>";

    // 5. Ensure SR exists
    $srUserHash = password_hash('sr123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role, status) VALUES (?,?,?,?,?)")
        ->execute(['Test SR User', 'sr@happy.com', $srUserHash, 'sr', 1]);
    $sruid = $pdo->query("SELECT id FROM users WHERE email='sr@happy.com'")->fetchColumn();
    
    // Create SR mapping
    $pdo->exec("INSERT IGNORE INTO sr (user_id, company_id, status) VALUES ($sruid, $cid, 1)");
    $srid = $pdo->query("SELECT id FROM sr WHERE user_id=$sruid")->fetchColumn();
    echo "✔ SR ID: $srid (sr@happy.com / sr123)<br>";

    // 6. Ensure Category exists
    $pdo->exec("INSERT IGNORE INTO categories (company_id, name) VALUES ($cid, 'Bakery & Toast') ON DUPLICATE KEY UPDATE name=name");
    $catid = $pdo->query("SELECT id FROM categories WHERE company_id=$cid LIMIT 1")->fetchColumn();
    echo "✔ Category ID: $catid<br>";

    // 7. Ensure Product exists (Ghee Toast)
    $pdo->exec("INSERT IGNORE INTO products (company_id, category_id, name, pieces_per_box, selling_price, status) 
                VALUES ($cid, $catid, 'Ghee Toast Premium', 24, 15.00, 1)
                ON DUPLICATE KEY UPDATE selling_price=15.00");
    $pid = $pdo->query("SELECT id FROM products WHERE name='Ghee Toast Premium'")->fetchColumn();
    echo "✔ Product ID: $pid (Ghee Toast Premium, 24 pcs/box, ৳15.00/pcs)<br>";

    // 8. Ensure Inventory exists
    $pdo->exec("INSERT IGNORE INTO inventory (product_id, warehouse_id, qty_boxes, qty_pieces) VALUES ($pid, $wid, 10, 0)");
    echo "✔ Stock Seeded: 10 boxes in stock<br>";

    // 9. Generate active QR Codes for scanning
    // We need lot_items and lots first
    $pdo->exec("INSERT IGNORE INTO lots (company_id, warehouse_id, manager_id, lot_date, status) VALUES ($cid, $wid, $uid, '2026-05-17', 1)");
    $lotId = $pdo->query("SELECT id FROM lots ORDER BY id DESC LIMIT 1")->fetchColumn();
    
    $pdo->exec("INSERT IGNORE INTO lot_items (lot_id, product_id, qty_boxes, buying_price, total) VALUES ($lotId, $pid, 5, 10.00, 1200.00)");
    $lotItemId = $pdo->query("SELECT id FROM lot_items ORDER BY id DESC LIMIT 1")->fetchColumn();

    // Create 3 active QR codes
    $qrs = [
      ['qr_uid' => 'GT-0012', 'serial' => 101],
      ['qr_uid' => 'GT-0015', 'serial' => 102],
      ['qr_uid' => 'GT-0018', 'serial' => 103]
    ];
    foreach ($qrs as $q) {
      $pdo->prepare("INSERT IGNORE INTO qr_codes (lot_item_id, product_id, lot_id, qr_uid, serial_number, pieces_total, pieces_remaining, status)
                     VALUES (?, ?, ?, ?, ?, 24, 24, 'active')")
          ->execute([$lotItemId, $pid, $lotId, $q['qr_uid'], $q['serial']]);
    }
    
    echo "✔ Seeded 3 Active QR Codes for Ghee Toast:<br>";
    echo " &nbsp; - <b>GT-0012</b> (24 pieces remaining)<br>";
    echo " &nbsp; - <b>GT-0015</b> (24 pieces remaining)<br>";
    echo " &nbsp; - <b>GT-0018</b> (24 pieces remaining)<br>";
    echo "<br><h3 style='color:green'>Seeding Successful!</h3>";
    echo "Manager: <b>manager@happy.com</b> / <b>manager123</b><br>";
    echo "Try logging in and visiting <i>/manager/ready_sale_scan.php</i> or type the QR UIDs above directly!<br>";

} catch (Exception $e) {
    echo "<h3 style='color:red'>Seeding Failed: " . $e->getMessage() . "</h3>";
}
