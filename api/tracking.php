<?php
// api/tracking.php — Fetch latest DSR location coordinates for Admin/Manager map views
header('Content-Type: application/json');
date_default_timezone_set('Asia/Dhaka'); // Bangladesh Standard Time (UTC+6)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

// Auth Guard — Admins and Managers only
requireRole(['admin', 'manager']);

$pdo = getDB();
$role = $_SESSION['role'];
$warehouse_id = $_SESSION['warehouse_id'] ?? null;

try {
    $today = date('Y-m-d'); // Use PHP Bangladesh date, not MySQL CURDATE() which uses UTC
    $query = "
        SELECT 
            d.id as dsr_id,
            u.name as dsr_name,
            u.phone as dsr_phone,
            w.name as warehouse_name,
            d.warehouse_id,
            dl.latitude,
            dl.longitude,
            dl.accuracy,
            dl.recorded_at,
            da.checkin_time,
            da.status as attendance_status
        FROM dsr d
        JOIN users u ON u.id = d.user_id
        JOIN warehouses w ON w.id = d.warehouse_id
        LEFT JOIN dsr_attendance da ON da.dsr_id = d.id AND da.checkin_date = ?
        LEFT JOIN (
            SELECT dl1.*
            FROM dsr_locations dl1
            INNER JOIN (
                SELECT dsr_id, MAX(id) as max_id
                FROM dsr_locations
                GROUP BY dsr_id
            ) dl2 ON dl1.id = dl2.max_id
        ) dl ON dl.dsr_id = d.id
        WHERE d.status = 1
    ";

    $params = [$today]; // today as first param for the LEFT JOIN
    if ($role === 'manager') {
        $query .= " AND d.warehouse_id = ?";
        $params[] = $warehouse_id;
    }

    $query .= " ORDER BY u.name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($rows as $r) {
        $is_online = false;
        $last_seen = 'Never';
        if ($r['recorded_at']) {
            $recorded_ts = strtotime($r['recorded_at']);
            $elapsed = time() - $recorded_ts;
            // Consider DSR "online" if they device updated location within the last 5 minutes (300 seconds)
            if ($elapsed <= 300) {
                $is_online = true;
            }
            
            // Human readable diff
            if ($elapsed < 60) {
                $last_seen = 'Just now';
            } elseif ($elapsed < 3600) {
                $mins = floor($elapsed / 60);
                $last_seen = $mins . 'm ago';
            } elseif ($elapsed < 86400) {
                $hours = floor($elapsed / 3600);
                $last_seen = $hours . 'h ago';
            } else {
                $last_seen = date('M d, h:i A', $recorded_ts);
            }
        }

        $formatted[] = [
            'dsr_id' => intval($r['dsr_id']),
            'name' => $r['dsr_name'],
            'phone' => $r['dsr_phone'] ?: 'N/A',
            'warehouse_name' => $r['warehouse_name'],
            'warehouse_id' => intval($r['warehouse_id']),
            'latitude' => $r['latitude'] !== null ? floatval($r['latitude']) : null,
            'longitude' => $r['longitude'] !== null ? floatval($r['longitude']) : null,
            'accuracy' => $r['accuracy'] !== null ? floatval($r['accuracy']) : null,
            'recorded_at' => $r['recorded_at'],
            'last_seen' => $last_seen,
            'is_online' => $is_online,
            'checkin_time' => $r['checkin_time'] ? date('h:i A', strtotime($r['checkin_time'])) : null,
            'attendance_status' => $r['attendance_status'] ?: 'absent'
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $formatted
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
