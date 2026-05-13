<?php
// api/auth.php — Login handler
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = 1 LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

// Set session
$_SESSION['user_id']    = $user['id'];
$_SESSION['name']       = $user['name'];
$_SESSION['email']      = $user['email'];
$_SESSION['role']       = $user['role'];

// Load role-specific session data
switch ($user['role']) {
    case 'manager':
        $m = $pdo->prepare('SELECT warehouse_id FROM managers WHERE user_id = ? AND status = 1 LIMIT 1');
        $m->execute([$user['id']]);
        $mgr = $m->fetch();
        $_SESSION['warehouse_id'] = $mgr['warehouse_id'] ?? null;
        break;

    case 'dsr':
        $d = $pdo->prepare('SELECT warehouse_id FROM dsr WHERE user_id = ? AND status = 1 LIMIT 1');
        $d->execute([$user['id']]);
        $dsrRow = $d->fetch();
        $_SESSION['warehouse_id'] = $dsrRow['warehouse_id'] ?? null;
        break;

    case 'dealer':
        $dl = $pdo->prepare('SELECT id FROM dealers WHERE user_id = ? AND status = 1 LIMIT 1');
        $dl->execute([$user['id']]);
        $dlr = $dl->fetch();
        $_SESSION['dealer_id'] = $dlr['id'] ?? null;
        break;

    case 'sr':
        $sr = $pdo->prepare('SELECT id, company_id, route_id FROM sr WHERE user_id = ? AND status = 1 LIMIT 1');
        $sr->execute([$user['id']]);
        $srRow = $sr->fetch();
        $_SESSION['sr_id']      = $srRow['id'] ?? null;
        $_SESSION['company_id'] = $srRow['company_id'] ?? null;
        break;
}

echo json_encode([
    'success'  => true,
    'role'     => $user['role'],
    'name'     => $user['name'],
    'redirect' => getDashboardUrl()
]);
