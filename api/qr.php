<?php
// api/qr.php — QR code generation, fetching, scanning
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
requireRole(['admin','manager']);

$pdo    = getDB();
$action = $_GET['action'] ?? '';

if ($action === 'lot_products') {
    // Get products in a lot (for dropdowns)
    $lot_id = intval($_GET['lot_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT li.id as lot_item_id, li.qty_boxes, li.qr_generated, li.expiry_date, p.id as product_id, p.name as product_name, p.pieces_per_box, p.selling_price FROM lot_items li JOIN products p ON p.id=li.product_id WHERE li.lot_id=?');
    $stmt->execute([$lot_id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'fetch') {
    // Fetch QR codes for a lot_item (for print page)
    $lot_item_id = intval($_GET['lot_item_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT qr.*, p.name as product_name, p.pieces_per_box, p.selling_price, li.expiry_date FROM qr_codes qr JOIN products p ON p.id=qr.product_id JOIN lot_items li ON li.id=qr.lot_item_id WHERE qr.lot_item_id=? ORDER BY qr.serial_number');
    $stmt->execute([$lot_item_id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $d           = json_decode(file_get_contents('php://input'), true);
    $lot_item_id = intval($d['lot_item_id'] ?? 0);
    $lot_id      = intval($d['lot_id'] ?? 0);
    $product_id  = intval($d['product_id'] ?? 0);

    // Get lot_item details
    $li = $pdo->prepare('SELECT li.*, p.name as product_name, p.pieces_per_box FROM lot_items li JOIN products p ON p.id=li.product_id WHERE li.id=? AND li.lot_id=?');
    $li->execute([$lot_item_id, $lot_id]);
    $li = $li->fetch();
    if (!$li) { echo json_encode(['success' => false, 'message' => 'Lot item not found']); exit; }

    // Check if already generated
    $existing = $pdo->prepare('SELECT * FROM qr_codes WHERE lot_item_id=? ORDER BY serial_number');
    $existing->execute([$lot_item_id]);
    $existingRows = $existing->fetchAll();
    if (!empty($existingRows)) {
        echo json_encode(['success' => true, 'data' => $existingRows, 'message' => 'Already generated']);
        exit;
    }

    // Get global max serial for this product
    $maxSerial = $pdo->prepare('SELECT COALESCE(MAX(serial_number), 0) FROM qr_codes WHERE product_id=?');
    $maxSerial->execute([$product_id]);
    $serial = intval($maxSerial->fetchColumn());

    // Get product short code (first 2 chars uppercase)
    $shortCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $li['product_name']), 0, 2));
    if (strlen($shortCode) < 2) $shortCode = str_pad($shortCode, 2, 'X');

    $year  = date('y');
    $codes = [];

    $pdo->beginTransaction();
    for ($i = 0; $i < $li['qty_boxes']; $i++) {
        $serial++;
        $qr_uid = 'HB-' . $year . '-' . $shortCode . '-' . str_pad($serial, 5, '0', STR_PAD_LEFT);
        $pdo->prepare('INSERT INTO qr_codes (lot_item_id, product_id, lot_id, qr_uid, serial_number, pieces_total, pieces_remaining) VALUES (?,?,?,?,?,?,?)')
            ->execute([$lot_item_id, $product_id, $lot_id, $qr_uid, $serial, $li['pieces_per_box'], $li['pieces_per_box']]);
        $codes[] = ['qr_uid' => $qr_uid, 'serial_number' => $serial, 'pieces_total' => $li['pieces_per_box']];
    }
    // Mark as generated
    $pdo->prepare('UPDATE lot_items SET qr_generated=1 WHERE id=?')->execute([$lot_item_id]);
    $pdo->commit();

    echo json_encode(['success' => true, 'data' => $codes]);
    exit;
}

if ($action === 'scan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate QR scan for delivery
    $d      = json_decode(file_get_contents('php://input'), true);
    $qr_uid = trim($d['qr_uid'] ?? '');
    $stmt   = $pdo->prepare('SELECT qr.*, p.name as product_name, p.pieces_per_box FROM qr_codes qr JOIN products p ON p.id=qr.product_id WHERE qr.qr_uid=?');
    $stmt->execute([$qr_uid]);
    $qr = $stmt->fetch();
    if (!$qr) { echo json_encode(['success' => false, 'message' => 'QR code not found']); exit; }
    if ($qr['status'] !== 'active') { echo json_encode(['success' => false, 'message' => 'QR code is ' . $qr['status'], 'status' => $qr['status']]); exit; }
    echo json_encode(['success' => true, 'data' => $qr]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
