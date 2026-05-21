<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('manager');

header('Content-Type: application/json');
$pdo    = getDB();
$wid    = $_SESSION['warehouse_id'];
$mgr_id = $_SESSION['user_id'] ?? 0;
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Auto-migrate: add latitude/longitude columns ─────────────────────────────
try {
    $pdo->query("SELECT latitude FROM dsr_attendance LIMIT 0");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE `dsr_attendance`
        ADD COLUMN `latitude`  DECIMAL(10,7) NULL DEFAULT NULL,
        ADD COLUMN `longitude` DECIMAL(10,7) NULL DEFAULT NULL,
        ADD COLUMN `note`      VARCHAR(255)  NULL DEFAULT NULL");
}

// ── Auto-migrate: attendance_settings table ───────────────────────────────────
try {
    $pdo->query("SELECT id FROM attendance_settings LIMIT 0");
} catch (PDOException $e) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `attendance_settings` (
        `id`           INT(11)  NOT NULL AUTO_INCREMENT,
        `warehouse_id` INT(11)  NOT NULL,
        `attend_time`  TIME     NOT NULL DEFAULT '09:00:00',
        `qr_token`     VARCHAR(64) NOT NULL,
        `token_date`   DATE     NOT NULL,
        `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_wh` (`warehouse_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// ── Helper ────────────────────────────────────────────────────────────────────
function jsonOut(bool $success, $data = null, string $msg = ''): void {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $msg]);
    exit;
}

// ── Get current settings ──────────────────────────────────────────────────────
if ($action === 'get_settings') {
    $st = $pdo->prepare("SELECT * FROM attendance_settings WHERE warehouse_id=?");
    $st->execute([$wid]);
    $row = $st->fetch();
    jsonOut(true, $row ?: ['attend_time' => '09:00', 'qr_token' => null, 'token_date' => null]);
}

// ── Save attend time & regenerate QR token ────────────────────────────────────
if ($action === 'save_settings') {
    $body        = json_decode(file_get_contents('php://input'), true) ?? [];
    $attend_time = $body['attend_time'] ?? '09:00';
    $today       = date('Y-m-d');
    $token       = bin2hex(random_bytes(20)); // 40-char secure token

    $pdo->prepare("INSERT INTO attendance_settings (warehouse_id, attend_time, qr_token, token_date)
                   VALUES (?,?,?,?)
                   ON DUPLICATE KEY UPDATE attend_time=VALUES(attend_time),
                                           qr_token=VALUES(qr_token),
                                           token_date=VALUES(token_date)")
        ->execute([$wid, $attend_time, $token, $today]);

    jsonOut(true, ['token' => $token, 'attend_time' => $attend_time, 'token_date' => $today], 'Settings saved');
}

// ── Regenerate QR token only ──────────────────────────────────────────────────
if ($action === 'regenerate_qr') {
    $today = date('Y-m-d');
    $token = bin2hex(random_bytes(20));

    $pdo->prepare("UPDATE attendance_settings SET qr_token=?, token_date=? WHERE warehouse_id=?")
        ->execute([$token, $today, $wid]);

    jsonOut(true, ['token' => $token, 'token_date' => $today], 'QR refreshed');
}

// ── List attendance ───────────────────────────────────────────────────────────
if ($action === 'list') {
    $date   = $_GET['date'] ?? date('Y-m-d');
    $stmt   = $pdo->prepare("
        SELECT a.id, u.name, u.role, a.checkin_date, a.checkin_time,
               a.latitude, a.longitude, a.status, a.note
        FROM dsr_attendance a
        JOIN dsr d ON d.id = a.dsr_id
        JOIN users u ON u.id = d.user_id
        WHERE a.warehouse_id = ? AND a.checkin_date = ?
        ORDER BY a.checkin_time ASC
    ");
    $stmt->execute([$wid, $date]);
    jsonOut(true, $stmt->fetchAll());
}

// ── Mark attend (from QR scan token) ─────────────────────────────────────────
if ($action === 'attend') {
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
    $token   = $body['token']   ?? '';
    $dsr_id  = intval($body['dsr_id']  ?? 0);
    $lat     = $body['latitude']  ?? null;
    $lng     = $body['longitude'] ?? null;
    $today   = date('Y-m-d');

    // Validate token
    $st = $pdo->prepare("SELECT * FROM attendance_settings WHERE warehouse_id=? AND qr_token=? AND token_date=?");
    $st->execute([$wid, $token, $today]);
    if (!$st->fetch()) jsonOut(false, null, 'Invalid or expired QR token');

    // Verify DSR belongs to this warehouse
    $ds = $pdo->prepare("SELECT id FROM dsr WHERE id=? AND warehouse_id=?");
    $ds->execute([$dsr_id, $wid]);
    if (!$ds->fetch()) jsonOut(false, null, 'DSR not found in this warehouse');

    try {
        $pdo->prepare("INSERT INTO dsr_attendance (dsr_id, warehouse_id, checkin_date, checkin_time, latitude, longitude, status)
                       VALUES (?, ?, ?, NOW(), ?, ?, 'present')
                       ON DUPLICATE KEY UPDATE checkin_time=VALUES(checkin_time),
                                               latitude=VALUES(latitude),
                                               longitude=VALUES(longitude)")
            ->execute([$dsr_id, $wid, $today, $lat, $lng]);
        jsonOut(true, null, 'Attendance marked successfully');
    } catch (PDOException $ex) {
        jsonOut(false, null, $ex->getMessage());
    }
}

// ── Manual mark attend (manager action) ──────────────────────────────────────
if ($action === 'manual_attend') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $dsr_id = intval($body['dsr_id'] ?? 0);
    $date   = $body['date'] ?? date('Y-m-d');
    $time   = $body['time'] ?? date('H:i:s');

    try {
        $pdo->prepare("INSERT INTO dsr_attendance (dsr_id, warehouse_id, checkin_date, checkin_time, status)
                       VALUES (?, ?, ?, ?, 'present')
                       ON DUPLICATE KEY UPDATE checkin_time=VALUES(checkin_time), status='present'")
            ->execute([$dsr_id, $wid, $date, $time]);
        jsonOut(true, null, 'Attendance marked');
    } catch (PDOException $ex) {
        jsonOut(false, null, $ex->getMessage());
    }
}

// ── Edit attendance ───────────────────────────────────────────────────────────
if ($action === 'edit') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = intval($body['id'] ?? 0);
    $time   = $body['checkin_time'] ?? '';
    $status = $body['status'] ?? 'present';
    $note   = $body['note'] ?? '';

    $pdo->prepare("UPDATE dsr_attendance SET checkin_time=?, status=?, note=?
                   WHERE id=? AND warehouse_id=?")
        ->execute([$time, $status, $note, $id, $wid]);
    jsonOut(true, null, 'Updated');
}

// ── Delete attendance ─────────────────────────────────────────────────────────
if ($action === 'delete') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = intval($body['id'] ?? 0);

    $pdo->prepare("DELETE FROM dsr_attendance WHERE id=? AND warehouse_id=?")
        ->execute([$id, $wid]);
    jsonOut(true, null, 'Deleted');
}

// ── List DSRs in warehouse ────────────────────────────────────────────────────
if ($action === 'list_dsrs') {
    $stmt = $pdo->prepare("SELECT d.id as dsr_id, u.name, u.phone, u.role
                           FROM dsr d JOIN users u ON u.id=d.user_id
                           WHERE d.warehouse_id=? AND d.status=1 ORDER BY u.name");
    $stmt->execute([$wid]);
    jsonOut(true, $stmt->fetchAll());
}

jsonOut(false, null, 'Unknown action');
