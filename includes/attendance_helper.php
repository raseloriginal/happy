<?php
// includes/attendance_helper.php — Attendance pre-generation and absent cutoff helper

function autoGenerateAttendance($pdo) {
    date_default_timezone_set('Asia/Dhaka'); // Bangladesh Standard Time (UTC+6)

    // Avoid double run if already done in this request
    static $run = false;
    if ($run) return;
    $run = true;

    $today = date('Y-m-d');
    
    // 1. Initialize today's records if not done
    initAttendanceForDate($pdo, $today);
    
    // 2. Initialize tomorrow's records if current time is >= 5:00 PM (17:00:00)
    if (time() >= strtotime('17:00:00')) {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        initAttendanceForDate($pdo, $tomorrow);
    }
    
    // 3. Check and mark absent if DSR hasn't checked in past deadline + 5 hours
    autoMarkAbsent($pdo);
}

function initAttendanceForDate($pdo, $date) {
    // Get all active DSRs
    $dsrs = $pdo->query("SELECT id, warehouse_id FROM dsr WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($dsrs)) {
        return;
    }
    
    // Prepare insert query
    $ins = $pdo->prepare("
        INSERT IGNORE INTO dsr_attendance (dsr_id, warehouse_id, checkin_date, checkin_time, status, latitude, longitude, note)
        VALUES (?, ?, ?, NULL, 'pending', NULL, NULL, NULL)
    ");
    
    foreach ($dsrs as $d) {
        $ins->execute([$d['id'], $d['warehouse_id'], $date]);
    }
}

function autoMarkAbsent($pdo) {
    $today = date('Y-m-d');
    $now = date('H:i:s');
    
    // Find all pending attendance entries for today
    $stmt = $pdo->prepare("
        SELECT a.id, a.warehouse_id, COALESCE(s.attend_time, '09:00:00') as attend_time
        FROM dsr_attendance a
        LEFT JOIN attendance_settings s ON s.warehouse_id = a.warehouse_id
        WHERE a.checkin_date = ? AND a.status = 'pending'
    ");
    $stmt->execute([$today]);
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pending)) {
        return;
    }
    
    $upd = $pdo->prepare("UPDATE dsr_attendance SET status = 'absent' WHERE id = ?");
    
    foreach ($pending as $row) {
        $attTime = $row['attend_time'];
        // Compute 5 hours after attend time
        $cutoffTime = date('H:i:s', strtotime($attTime) + 5 * 3600);
        if ($now >= $cutoffTime) {
            $upd->execute([$row['id']]);
        }
    }
}
