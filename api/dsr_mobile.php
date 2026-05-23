<?php
// api/dsr_mobile.php — Backend REST API for DSR Mobile App
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

// Auth Guard — Ensure logged in as DSR
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'dsr') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Forbidden: DSR access only']);
    exit;
}

$pdo    = getDB();
require_once __DIR__ . '/../includes/attendance_helper.php';
autoGenerateAttendance($pdo);

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Fetch DSR profile
$dsrStmt = $pdo->prepare('SELECT d.*, w.name as warehouse_name FROM dsr d JOIN warehouses w ON w.id=d.warehouse_id WHERE d.user_id=? LIMIT 1');
$dsrStmt->execute([$userId]);
$dsr = $dsrStmt->fetch();

if (!$dsr) {
    echo json_encode(['success' => false, 'message' => 'DSR profile not found in database.']);
    exit;
}

$dsr_id       = $dsr['id'];
$warehouse_id = $dsr['warehouse_id'];

switch ($action) {
    case 'dashboard':
        if ($method !== 'GET') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        // Today's attendance status
        $attStmt = $pdo->prepare('SELECT * FROM dsr_attendance WHERE dsr_id=? AND checkin_date=CURDATE() LIMIT 1');
        $attStmt->execute([$dsr_id]);
        $attendance = $attStmt->fetch();

        // Query by specific date if supplied
        $selected_date = isset($_GET['date']) ? trim($_GET['date']) : '';
        if (!empty($selected_date)) {
            $dispStmt = $pdo->prepare("
                SELECT d.*, o.order_date, u.name as sr_name, c.name as company_name 
                FROM dispatches d 
                LEFT JOIN orders o ON o.id=d.order_id 
                LEFT JOIN sr s ON s.id=o.sr_id 
                LEFT JOIN users u ON u.id=s.user_id 
                LEFT JOIN companies c ON c.id=o.company_id 
                WHERE d.dsr_id=? AND d.dispatch_date=? 
                ORDER BY d.id DESC LIMIT 1
            ");
            $dispStmt->execute([$dsr_id, $selected_date]);
        } else {
            // Active dispatch for this DSR (status loaded or delivered)
            $dispStmt = $pdo->prepare("
                SELECT d.*, o.order_date, u.name as sr_name, c.name as company_name 
                FROM dispatches d 
                LEFT JOIN orders o ON o.id=d.order_id 
                LEFT JOIN sr s ON s.id=o.sr_id 
                LEFT JOIN users u ON u.id=s.user_id 
                LEFT JOIN companies c ON c.id=o.company_id 
                WHERE d.dsr_id=? AND d.status IN ('loaded', 'delivered') 
                ORDER BY d.id DESC LIMIT 1
            ");
            $dispStmt->execute([$dsr_id]);
        }
        $activeDispatch = $dispStmt->fetch();

        $outVal = 0.00;
        $returnVal = 0.00;
        $settledRecord = null;

        if ($activeDispatch) {
            $dispatch_id = $activeDispatch['id'];

            // Total Out Value: sum(qty_out * selling_price)
            $outStmt = $pdo->prepare('
                SELECT COALESCE(SUM(di.qty_out * p.selling_price), 0) 
                FROM dispatch_items di 
                JOIN products p ON p.id=di.product_id 
                WHERE di.dispatch_id=?
            ');
            $outStmt->execute([$dispatch_id]);
            $outVal = floatval($outStmt->fetchColumn());

            // Return Value: sum(qty_in * selling_price) from return items
            $retStmt = $pdo->prepare('
                SELECT COALESCE(SUM(ri.qty_in * p.selling_price), 0) 
                FROM return_items ri 
                JOIN returns r ON r.id=ri.return_id 
                JOIN products p ON p.id=ri.product_id 
                WHERE r.dispatch_id=? AND r.status="completed"
            ');
            $retStmt->execute([$dispatch_id]);
            $returnVal = floatval($retStmt->fetchColumn());

            // Check if cash settlement has been submitted
            $settleStmt = $pdo->prepare('SELECT * FROM cash_settlements WHERE dispatch_id=? LIMIT 1');
            $settleStmt->execute([$dispatch_id]);
            $settledRecord = $settleStmt->fetch();

            $srsStmt = $pdo->prepare('
                SELECT DISTINCT s.id as sr_id, u.name as sr_name, c.name as company_name
                FROM dispatch_items di
                JOIN orders o ON o.id = di.order_id
                JOIN sr s ON s.id = o.sr_id
                JOIN users u ON u.id = s.user_id
                JOIN companies c ON c.id = s.company_id
                WHERE di.dispatch_id=?
                ORDER BY c.name, u.name
            ');
            $srsStmt->execute([$dispatch_id]);
            $assignedSRs = $srsStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Recent Settled Dispatches
        $recentStmt = $pdo->prepare("
            SELECT d.id, d.dispatch_date, d.status, COALESCE(cs.amount_submitted, 0) as submitted 
            FROM dispatches d 
            LEFT JOIN cash_settlements cs ON cs.dispatch_id=d.id 
            WHERE d.dsr_id=? AND d.status='settled' 
            ORDER BY d.id DESC LIMIT 5
        ");
        $recentStmt->execute([$dsr_id]);
        $recentDispatches = $recentStmt->fetchAll();

        // All time stats for average delivery ratio and total delivered all-time
        $allLoadedStmt = $pdo->prepare('
            SELECT COALESCE(SUM(di.qty_out * p.selling_price), 0) 
            FROM dispatches d
            JOIN dispatch_items di ON di.dispatch_id = d.id
            JOIN products p ON p.id = di.product_id
            WHERE d.dsr_id = ?
        ');
        $allLoadedStmt->execute([$dsr_id]);
        $allLoadedVal = floatval($allLoadedStmt->fetchColumn());

        $allReturnedStmt = $pdo->prepare('
            SELECT COALESCE(SUM(ri.qty_in * p.selling_price), 0) 
            FROM dispatches d
            JOIN returns r ON r.dispatch_id = d.id AND r.status = "completed"
            JOIN return_items ri ON ri.return_id = r.id
            JOIN products p ON p.id = ri.product_id
            WHERE d.dsr_id = ?
        ');
        $allReturnedStmt->execute([$dsr_id]);
        $allReturnedVal = floatval($allReturnedStmt->fetchColumn());

        $allDeliveredVal = max($allLoadedVal - $allReturnedVal, 0);
        $avgDeliveryRatio = $allLoadedVal > 0 ? ($allDeliveredVal / $allLoadedVal) * 100 : 0;

        $loadedProductsList = [];
        if ($activeDispatch) {
            $dispatch_id = $activeDispatch['id'];
            $prodStmt = $pdo->prepare('
                SELECT 
                    p.name as product_name, 
                    SUM(di.qty_out) as qty_loaded,
                    p.selling_price,
                    p.pieces_per_box
                FROM dispatch_items di
                JOIN products p ON p.id=di.product_id
                WHERE di.dispatch_id=?
                GROUP BY p.id
            ');
            $prodStmt->execute([$dispatch_id]);
            $rawProds = $prodStmt->fetchAll();

            foreach ($rawProds as $rp) {
                $ppb = max(intval($rp['pieces_per_box']), 1);
                $qty = intval($rp['qty_loaded']);
                $val = $qty * floatval($rp['selling_price']);
                
                $boxes = floor($qty / $ppb);
                $remainder = $qty % $ppb;
                $formatted_qty = $boxes . ' Box' . ($boxes != 1 ? 'es':'') . ($remainder > 0 ? ' & ' . $remainder . ' Pcs' : '');

                $loadedProductsList[] = [
                    'product_name' => $rp['product_name'],
                    'qty_pieces' => $qty,
                    'qty_formatted' => $formatted_qty,
                    'total_value' => $val
                ];
            }
        }

        $checkedIn = ($attendance && $attendance['status'] !== 'pending' && $attendance['status'] !== 'absent');

        echo json_encode([
            'success' => true,
            'profile' => [
                'name' => $_SESSION['name'],
                'warehouse_name' => $dsr['warehouse_name'],
                'phone' => $_SESSION['phone'] ?? ''
            ],
            'attendance' => $checkedIn ? [
                'checked_in' => true,
                'time' => date('h:i A', strtotime($attendance['checkin_time'])),
                'warehouse_name' => $dsr['warehouse_name'],
                'status' => $attendance['status']
            ] : [
                'checked_in' => false,
                'status' => $attendance ? $attendance['status'] : 'absent'
            ],
            'active_dispatch' => $activeDispatch ? [
                'id' => $activeDispatch['id'],
                'dispatch_date' => $activeDispatch['dispatch_date'],
                'status' => $activeDispatch['status'],
                'sr_name' => $activeDispatch['sr_name'] ?? 'Ready Sale',
                'company_name' => $activeDispatch['company_name'] ?? 'N/A',
                'out_value' => $outVal,
                'return_value' => $returnVal,
                'settlement' => $settledRecord ? [
                    'id' => $settledRecord['id'],
                    'status' => $settledRecord['status'],
                    'amount_expected' => floatval($settledRecord['amount_expected']),
                    'amount_submitted' => floatval($settledRecord['amount_submitted']),
                    'difference' => floatval($settledRecord['difference']),
                    'damage_amount' => floatval($settledRecord['damage_amount']),
                    'expense_amount' => floatval($settledRecord['expense_amount']),
                    'commission_amount' => floatval($settledRecord['commission_amount'] ?? 0.00),
                    'commission_details' => json_decode($settledRecord['commission_details'] ?? '{}', true),
                    'notes' => $settledRecord['notes'],
                    'notes_details' => json_decode($settledRecord['notes_details'], true)
                ] : null,
                'assigned_srs' => $assignedSRs ?? []
            ] : null,
            'recent_dispatches' => $recentDispatches,
            'stats' => [
                'delivery_ratio' => $avgDeliveryRatio,
                'current_van_value' => $activeDispatch ? $outVal : 0.00,
                'total_delivered_all_time' => $allDeliveredVal
            ],
            'loaded_products' => $loadedProductsList
        ]);
        break;

    case 'mark_attendance':
        if ($method !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $d = json_decode(file_get_contents('php://input'), true);
        $qrCode = trim($d['qr_code'] ?? '');
        $latitude = isset($d['latitude']) ? floatval($d['latitude']) : null;
        $longitude = isset($d['longitude']) ? floatval($d['longitude']) : null;

        // Backend IP lookup fallback if frontend fails to send coordinates
        if (empty($latitude) || empty($longitude)) {
            $userIp = $_SERVER['REMOTE_ADDR'] ?? '';
            if (!empty($userIp) && $userIp !== '127.0.0.1' && $userIp !== '::1' && !str_starts_with($userIp, '192.168.') && !str_starts_with($userIp, '10.') && !str_starts_with($userIp, '172.16.')) {
                $ipData = @json_decode(@file_get_contents("http://ip-api.com/json/{$userIp}"), true);
                if (!empty($ipData) && isset($ipData['lat'], $ipData['lon'])) {
                    $latitude = floatval($ipData['lat']);
                    $longitude = floatval($ipData['lon']);
                }
            }
        }

        if (empty($qrCode)) {
            echo json_encode(['success' => false, 'message' => 'No QR code provided. Please scan a warehouse QR code.']);
            exit;
        }

        $scanned_warehouse_id = null;
        $scanned_token = null;

        // Attempt to parse QR code as a JSON payload (generated by new manager system)
        $json_payload = json_decode($qrCode, true);
        if (is_array($json_payload) && isset($json_payload['wh'], $json_payload['token'])) {
            $scanned_warehouse_id = intval($json_payload['wh']);
            $scanned_token = trim($json_payload['token']);
        } else {
            // Legacy / simulation mock fallback formats
            if (is_numeric($qrCode)) {
                $scanned_warehouse_id = intval($qrCode);
            } else if (preg_match('/happy_warehouse_(\d+)/', $qrCode, $matches)) {
                $scanned_warehouse_id = intval($matches[1]);
            } else if (preg_match('/warehouse_(\d+)/', $qrCode, $matches)) {
                $scanned_warehouse_id = intval($matches[1]);
            } else if (preg_match('/warehouse:(\d+)/', $qrCode, $matches)) {
                $scanned_warehouse_id = intval($matches[1]);
            } else {
                // Attempt to search warehouse by name
                $nameStmt = $pdo->prepare('SELECT id FROM warehouses WHERE name=? LIMIT 1');
                $nameStmt->execute([$qrCode]);
                $scanned_warehouse_id = $nameStmt->fetchColumn();
            }
        }

        if (!$scanned_warehouse_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid QR Code. Could not identify warehouse.']);
            exit;
        }

        // 1. DSR Warehouse Mapping Guard: DSR can only scan their assigned warehouse
        if (intval($scanned_warehouse_id) !== intval($warehouse_id)) {
            echo json_encode(['success' => false, 'message' => 'You can only scan the QR code of your assigned warehouse.']);
            exit;
        }

        // Verify warehouse exists
        $whStmt = $pdo->prepare('SELECT name FROM warehouses WHERE id=? LIMIT 1');
        $whStmt->execute([$scanned_warehouse_id]);
        $warehouseName = $whStmt->fetchColumn();

        if (!$warehouseName) {
            echo json_encode(['success' => false, 'message' => 'Warehouse not found.']);
            exit;
        }

        // Fetch settings for token & attend_time calculation
        $settStmt = $pdo->prepare('SELECT attend_time, qr_token FROM attendance_settings WHERE warehouse_id = ? LIMIT 1');
        $settStmt->execute([$scanned_warehouse_id]);
        $setting = $settStmt->fetch();

        // 2. Validate token if present in QR code (JSON format)
        if ($scanned_token !== null) {
            if (!$setting || $setting['qr_token'] !== $scanned_token) {
                echo json_encode(['success' => false, 'message' => 'Invalid QR Code token. Please scan the official printed warehouse QR card.']);
                exit;
            }
        }

        // 3. Calculate status dynamically based on check-in time vs attend_time deadline
        $attend_time = $setting ? $setting['attend_time'] : '09:00:00';
        $checkin_date = date('Y-m-d');
        $checkin_time = date('H:i:s');
        $status = ($checkin_time <= $attend_time) ? 'present' : 'late';

        try {
            // 4. Save and update on duplicate checkin date for DSR
            $stmt = $pdo->prepare('
                INSERT INTO dsr_attendance (dsr_id, warehouse_id, checkin_date, checkin_time, status, latitude, longitude) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    checkin_time = VALUES(checkin_time),
                    status = VALUES(status),
                    latitude = VALUES(latitude),
                    longitude = VALUES(longitude)
            ');
            $stmt->execute([$dsr_id, $scanned_warehouse_id, $checkin_date, $checkin_time, $status, $latitude, $longitude]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Attendance marked successfully!',
                'warehouse_name' => $warehouseName,
                'time' => date('h:i A', strtotime($checkin_time)),
                'status' => $status
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'van_stock':
        if ($method !== 'GET') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $selected_date = isset($_GET['date']) ? trim($_GET['date']) : '';

        if (!empty($selected_date)) {
            // Find dispatch on that specific date
            $dispStmt = $pdo->prepare("SELECT id FROM dispatches WHERE dsr_id=? AND dispatch_date=? ORDER BY id DESC LIMIT 1");
            $dispStmt->execute([$dsr_id, $selected_date]);
        } else {
            // Get active dispatch ID
            $dispStmt = $pdo->prepare("SELECT id FROM dispatches WHERE dsr_id=? AND status IN ('loaded', 'delivered') ORDER BY id DESC LIMIT 1");
            $dispStmt->execute([$dsr_id]);
        }
        $active_dispatch_id = $dispStmt->fetchColumn();

        if (!$active_dispatch_id) {
            echo json_encode(['success' => true, 'dispatch_id' => null, 'products' => [], 'settlement' => null]);
            exit;
        }

        // Fetch products loaded on this dispatch
        $stockStmt = $pdo->prepare('
            SELECT 
                p.id as product_id, 
                p.name as product_name, 
                p.selling_price, 
                p.pieces_per_box,
                SUM(di.qty_out) as pieces_loaded
            FROM dispatch_items di
            JOIN products p ON p.id=di.product_id
            WHERE di.dispatch_id=?
            GROUP BY p.id
        ');
        $stockStmt->execute([$active_dispatch_id]);
        $loadedProducts = $stockStmt->fetchAll();

        $products = [];
        foreach ($loadedProducts as $lp) {
            // Get returned pieces for this product in this dispatch (manager verified returns)
            $retStmt = $pdo->prepare('
                SELECT COALESCE(SUM(ri.qty_in), 0) as pieces_returned
                FROM return_items ri
                JOIN returns r ON r.id=ri.return_id
                WHERE r.dispatch_id=? AND ri.product_id=? AND r.status="completed"
            ');
            $retStmt->execute([$active_dispatch_id, $lp['product_id']]);
            $pieces_returned = intval($retStmt->fetchColumn());

            $pieces_sold = max(intval($lp['pieces_loaded']) - $pieces_returned, 0);
            $value_sold = $pieces_sold * floatval($lp['selling_price']);

            $ppb = max(intval($lp['pieces_per_box']), 1);

            // Format counts as readable boxes + pieces
            $loaded_boxes = floor($lp['pieces_loaded'] / $ppb);
            $loaded_remainder = $lp['pieces_loaded'] % $ppb;
            $loaded_formatted = $loaded_boxes . ' Box' . ($loaded_boxes != 1 ? 'es':'') . ($loaded_remainder > 0 ? ' & ' . $loaded_remainder . ' Pcs' : '');

            $returned_boxes = floor($pieces_returned / $ppb);
            $returned_remainder = $pieces_returned % $ppb;
            $returned_formatted = $returned_boxes . ' Box' . ($returned_boxes != 1 ? 'es':'') . ($returned_remainder > 0 ? ' & ' . $returned_remainder . ' Pcs' : '');

            $sold_boxes = floor($pieces_sold / $ppb);
            $sold_remainder = $pieces_sold % $ppb;
            $sold_formatted = $sold_boxes . ' Box' . ($sold_boxes != 1 ? 'es':'') . ($sold_remainder > 0 ? ' & ' . $sold_remainder . ' Pcs' : '');

            $products[] = [
                'product_id' => $lp['product_id'],
                'product_name' => $lp['product_name'],
                'selling_price' => floatval($lp['selling_price']),
                'pieces_per_box' => $ppb,
                'loaded' => [
                    'pieces' => intval($lp['pieces_loaded']),
                    'formatted' => $loaded_formatted
                ],
                'returned' => [
                    'pieces' => $pieces_returned,
                    'formatted' => $returned_formatted
                ],
                'sold' => [
                    'pieces' => $pieces_sold,
                    'value' => $value_sold,
                    'formatted' => $sold_formatted
                ]
            ];
        }

        // Fetch settlement summary
        $settlement = null;
        $settleStmt = $pdo->prepare("SELECT * FROM cash_settlements WHERE dispatch_id=? LIMIT 1");
        $settleStmt->execute([$active_dispatch_id]);
        $settleRow = $settleStmt->fetch(PDO::FETCH_ASSOC);
        if ($settleRow) {
            $settlement = [
                'id' => $settleRow['id'],
                'amount_expected' => floatval($settleRow['amount_expected']),
                'amount_submitted' => floatval($settleRow['amount_submitted']),
                'difference' => floatval($settleRow['difference']),
                'damage_amount' => floatval($settleRow['damage_amount']),
                'expense_amount' => floatval($settleRow['expense_amount']),
                'notes' => $settleRow['notes']
            ];
        }

        echo json_encode([
            'success' => true,
            'dispatch_id' => $active_dispatch_id ? intval($active_dispatch_id) : null,
            'products' => $products,
            'settlement' => $settlement
        ]);
        break;

    case 'submit_settlement':
        if ($method !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $d = json_decode(file_get_contents('php://input'), true);
        $dispatch_id = intval($d['dispatch_id'] ?? 0);
        $damage_amount = floatval($d['damage_amount'] ?? 0);
        $expense_amount = floatval($d['expense_amount'] ?? 0);
        $commission_details = $d['commission_details'] ?? [];
        $commission_amount = 0;
        if (is_array($commission_details)) {
            foreach ($commission_details as $amt) {
                $commission_amount += floatval($amt);
            }
        }
        $amount_submitted = floatval($d['amount_submitted'] ?? 0);
        $notes_details = $d['notes_details'] ?? [];
        $notes_text = trim($d['notes_text'] ?? '');

        // Verify active dispatch belongs to this DSR
        $checkDisp = $pdo->prepare('SELECT id, status FROM dispatches WHERE id=? AND dsr_id=? LIMIT 1');
        $checkDisp->execute([$dispatch_id, $dsr_id]);
        $disp = $checkDisp->fetch();

        if (!$disp) {
            echo json_encode(['success' => false, 'message' => 'Active dispatch not found for this user.']);
            exit;
        }

        // Calculate expected submission based on actual SQL loaded/returned products
        // Out Value
        $outStmt = $pdo->prepare('
            SELECT COALESCE(SUM(di.qty_out * p.selling_price), 0) 
            FROM dispatch_items di 
            JOIN products p ON p.id=di.product_id 
            WHERE di.dispatch_id=?
        ');
        $outStmt->execute([$dispatch_id]);
        $outVal = floatval($outStmt->fetchColumn());

        // Return Value
        $retStmt = $pdo->prepare('
            SELECT COALESCE(SUM(ri.qty_in * p.selling_price), 0) 
            FROM return_items ri 
            JOIN returns r ON r.id=ri.return_id 
            JOIN products p ON p.id=ri.product_id 
            WHERE r.dispatch_id=? AND r.status="completed"
        ');
        $retStmt->execute([$dispatch_id]);
        $returnVal = floatval($retStmt->fetchColumn());

        // expected submitted cash = out_val - return_val - damage - expense + commission
        $amount_expected = $outVal - $returnVal - $damage_amount - $expense_amount + $commission_amount;
        $difference      = $amount_submitted - $amount_expected;

        $pdo->beginTransaction();

        // Check if settlement already exists
        $settleCheck = $pdo->prepare('SELECT id FROM cash_settlements WHERE dispatch_id=? LIMIT 1');
        $settleCheck->execute([$dispatch_id]);
        $existId = $settleCheck->fetchColumn();

        if ($existId) {
            $upStmt = $pdo->prepare('
                UPDATE cash_settlements 
                SET amount_expected=?, amount_submitted=?, difference=?, return_amount=?, damage_amount=?, expense_amount=?, commission_amount=?, commission_details=?, notes_details=?, notes=?, settlement_date=CURDATE(), status="pending" 
                WHERE id=?
            ');
            $upStmt->execute([
                $amount_expected, $amount_submitted, $difference, $returnVal, $damage_amount, $expense_amount, $commission_amount, json_encode($commission_details),
                json_encode($notes_details), $notes_text, $existId
            ]);
        } else {
            $insStmt = $pdo->prepare('
                INSERT INTO cash_settlements (dsr_id, dispatch_id, amount_expected, amount_submitted, difference, return_amount, damage_amount, expense_amount, commission_amount, commission_details, notes_details, notes, settlement_date, status) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),"pending")
            ');
            $insStmt->execute([
                $dsr_id, $dispatch_id, $amount_expected, $amount_submitted, $difference, 
                $returnVal, $damage_amount, $expense_amount, $commission_amount, json_encode($commission_details), json_encode($notes_details), $notes_text
            ]);
        }

        // Update dispatch status to 'delivered' indicating deliveries complete and settlement submitted
        if ($disp['status'] === 'loaded') {
            $pdo->prepare('UPDATE dispatches SET status="delivered" WHERE id=?')->execute([$dispatch_id]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Settlement details submitted successfully! Waiting for manager approval.',
            'data' => [
                'expected' => $amount_expected,
                'submitted' => $amount_submitted,
                'difference' => $difference
            ]
        ]);
        break;

    case 'expenses':
        if ($method === 'GET') {
            $expStmt = $pdo->prepare('SELECT * FROM expenses WHERE dsr_id=? ORDER BY id DESC LIMIT 20');
            $expStmt->execute([$dsr_id]);
            echo json_encode([
                'success' => true,
                'expenses' => $expStmt->fetchAll()
            ]);
        } else if ($method === 'POST') {
            $d = json_decode(file_get_contents('php://input'), true);
            $amount      = floatval($d['amount'] ?? 0);
            $description = trim($d['description'] ?? '');
            $expense_date= $d['expense_date'] ?? date('Y-m-d');
            $dispatch_id = !empty($d['dispatch_id']) ? intval($d['dispatch_id']) : null;

            if ($amount <= 0 || empty($description)) {
                echo json_encode(['success' => false, 'message' => 'Amount and description are required.']);
                exit;
            }

            $insExp = $pdo->prepare('INSERT INTO expenses (dsr_id, dispatch_id, amount, description, expense_date, status) VALUES (?,?,?,?,?, "pending")');
            $insExp->execute([$dsr_id, $dispatch_id, $amount, $description, $expense_date]);

            echo json_encode(['success' => true, 'message' => 'Expense submitted successfully for manager approval!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        }
        break;

    case 'track_location':
        if ($method !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $d = json_decode(file_get_contents('php://input'), true);
        $latitude = isset($d['latitude']) ? floatval($d['latitude']) : null;
        $longitude = isset($d['longitude']) ? floatval($d['longitude']) : null;
        $accuracy = isset($d['accuracy']) ? floatval($d['accuracy']) : null;

        if (empty($latitude) || empty($longitude)) {
            echo json_encode(['success' => false, 'message' => 'Latitude and longitude are required.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO dsr_locations (dsr_id, latitude, longitude, accuracy) 
                VALUES (?, ?, ?, ?)
            ');
            $stmt->execute([$dsr_id, $latitude, $longitude, $accuracy]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Location tracked successfully'
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid mobile API endpoint action']);
        break;
}
