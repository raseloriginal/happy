<?php
// config/db.php — PDO MySQL Connection

// Environment Detection
$is_localhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost','10.146.105.89', '10.254.223.183', '127.0.0.1', '::1']) 
             || (php_sapi_name() === 'cli'); // Assume localhost if run from CLI for now, or refine if needed

if ($is_localhost) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'happy_bangladesh');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'rasedwwq_happy');
    define('DB_USER', 'rasedwwq_happy');
    define('DB_PASS', '5..mqEE2ASz4');
}
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Auto-migration: Check if retailer columns exist in orders table, add them if missing
            try {
                $pdo->query("SELECT retailer_name, retailer_phone FROM orders LIMIT 0");
            } catch (PDOException $e) {
                try {
                    $pdo->exec("ALTER TABLE `orders` ADD COLUMN `retailer_name` VARCHAR(255) NULL DEFAULT NULL AFTER `status`");
                    $pdo->exec("ALTER TABLE `orders` ADD COLUMN `retailer_phone` VARCHAR(50) NULL DEFAULT NULL AFTER `retailer_name`");
                } catch (PDOException $ex) {
                    // Ignore failure if the table itself doesn't exist yet
                }
            }
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
