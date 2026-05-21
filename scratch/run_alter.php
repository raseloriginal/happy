<?php
require_once 'c:/xampp/htdocs/happycrm2/config/db.php';
$pdo = getDB();
try {
    $pdo->exec("ALTER TABLE dsr_attendance MODIFY COLUMN checkin_time TIME NULL DEFAULT NULL");
    echo "Success!\n";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
print_r($pdo->query("SHOW CREATE TABLE dsr_attendance")->fetch(PDO::FETCH_ASSOC));
