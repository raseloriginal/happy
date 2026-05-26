<?php
// config/db.php — PDO MySQL Connection

// Environment Detection
$is_localhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1', '10.146.105.89', '10.254.223.183']) 
             || in_array($_SERVER['SERVER_ADDR'] ?? '', ['127.0.0.1', '::1'])
             || (php_sapi_name() === 'cli'); // Assume localhost if run from CLI for now, or refine if needed

if ($is_localhost) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    define('DB_HOST', 'localhost');
    define('DB_NAME', 'happy_bangladesh');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

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

            // Auto-migration: Check if scanned_qrs column exists in orders table, add it if missing
            try {
                $pdo->query("SELECT scanned_qrs FROM orders LIMIT 0");
                // Column exists, mark migration as applied to prevent duplicate execution in migrate.php
                try {
                    $pdo->exec("INSERT IGNORE INTO migrations (migration_name) VALUES ('005_add_scanned_qrs_and_ready_sale.sql')");
                } catch (PDOException $ex) {}
            } catch (PDOException $e) {
                try {
                    $pdo->exec("ALTER TABLE `orders` ADD COLUMN `scanned_qrs` TEXT NULL DEFAULT NULL AFTER `status`");
                    // Insert into migrations table if it exists to keep migrations in sync
                    $pdo->exec("INSERT IGNORE INTO migrations (migration_name) VALUES ('005_add_scanned_qrs_and_ready_sale.sql')");
                } catch (PDOException $ex) {
                    // Ignore failure
                }
            }

            // Auto-migration: Check if status column contains ready_sale
            try {
                $col = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'status'")->fetch();
                if ($col && strpos($col['Type'], 'ready_sale') === false) {
                    $pdo->exec("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending','ready_sale','out_for_delivery','delivered','cancelled') DEFAULT 'pending'");
                }
            } catch (PDOException $e) {
                // Ignore
            }
            // Auto-migration: attendance_settings table
            try {
                $pdo->query("SELECT id FROM attendance_settings LIMIT 0");
            } catch (PDOException $e) {
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `attendance_settings` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `warehouse_id` INT(11) NOT NULL,
                        `attend_time` TIME NOT NULL DEFAULT '09:00:00',
                        `qr_token` VARCHAR(64) NOT NULL DEFAULT '',
                        `token_date` DATE NOT NULL,
                        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `unique_wh` (`warehouse_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                } catch (PDOException $ex) { }
            }
            // Auto-migration: add latitude/longitude/note to dsr_attendance
            try {
                $pdo->query("SELECT latitude FROM dsr_attendance LIMIT 0");
            } catch (PDOException $e) {
                try {
                    $pdo->exec("ALTER TABLE `dsr_attendance`
                        ADD COLUMN `latitude`  DECIMAL(10,7) NULL DEFAULT NULL,
                        ADD COLUMN `longitude` DECIMAL(10,7) NULL DEFAULT NULL,
                        ADD COLUMN `note`      VARCHAR(255)  NULL DEFAULT NULL");
                } catch (PDOException $ex) { }
            }

            // Auto-migration: Fix dispatches with 'settled' status but no approved cash settlement
            try {
                $pdo->exec("
                    UPDATE dispatches d
                    LEFT JOIN cash_settlements cs ON cs.dispatch_id = d.id AND cs.status = 'approved'
                    SET d.status = 'loaded'
                    WHERE d.status = 'settled' AND cs.id IS NULL
                ");
            } catch (PDOException $e) {
                // Ignore failure if tables don't exist yet
            }

            // Auto-migration: Add commission_details to cash_settlements
            try {
                $pdo->query("SELECT commission_details FROM cash_settlements LIMIT 0");
            } catch (PDOException $e) {
                try {
                    $pdo->exec("ALTER TABLE `cash_settlements` ADD COLUMN `commission_details` TEXT NULL DEFAULT NULL AFTER `commission_amount`");
                } catch (PDOException $ex) { }
            }

            // Auto-migration: dsr_locations table
            try {
                $pdo->query("SELECT id FROM dsr_locations LIMIT 0");
            } catch (PDOException $e) {
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `dsr_locations` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `dsr_id` INT NOT NULL,
                        `latitude` DECIMAL(10, 8) NOT NULL,
                        `longitude` DECIMAL(11, 8) NOT NULL,
                        `accuracy` DECIMAL(8, 2) DEFAULT NULL,
                        `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        KEY `dsr_id` (`dsr_id`),
                        CONSTRAINT `dsr_locations_ibfk_1` FOREIGN KEY (`dsr_id`) REFERENCES `dsr` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                } catch (PDOException $ex) { }
            }

            // Auto-migration: inventory_logs table
            try {
                $pdo->query("SELECT id FROM inventory_logs LIMIT 0");
            } catch (PDOException $e) {
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `inventory_logs` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `product_id` int(11) NOT NULL,
                        `warehouse_id` int(11) NOT NULL,
                        `user_id` int(11) DEFAULT NULL,
                        `action_type` varchar(50) NOT NULL, 
                        `reference_id` int(11) DEFAULT NULL,
                        `change_boxes` int(11) NOT NULL DEFAULT 0,
                        `change_pieces` int(11) NOT NULL DEFAULT 0,
                        `balance_boxes` int(11) NOT NULL DEFAULT 0,
                        `balance_pieces` int(11) NOT NULL DEFAULT 0,
                        `notes` text DEFAULT NULL,
                        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                        PRIMARY KEY (`id`),
                        KEY `product_id` (`product_id`),
                        KEY `warehouse_id` (`warehouse_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                } catch (PDOException $ex) { }
            }
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function logInventoryActivity($pdo, $product_id, $warehouse_id, $action_type, $ref_id = null, $change_boxes = 0, $change_pieces = 0, $notes = '') {
    $user_id = $_SESSION['user_id'] ?? null;
    
    // Fetch current balance
    $stmt = $pdo->prepare('SELECT qty_boxes, qty_pieces FROM inventory WHERE product_id=? AND warehouse_id=?');
    $stmt->execute([$product_id, $warehouse_id]);
    $row = $stmt->fetch();
    
    $balance_boxes = $row ? (int)$row['qty_boxes'] : 0;
    $balance_pieces = $row ? (int)$row['qty_pieces'] : 0;
    
    // Skip logging if there is no change and it's not an initial setup
    if ($change_boxes == 0 && $change_pieces == 0 && $action_type !== 'initial_stock' && $action_type !== 'edit_stock') {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO inventory_logs (product_id, warehouse_id, user_id, action_type, reference_id, change_boxes, change_pieces, balance_boxes, balance_pieces, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $product_id, $warehouse_id, $user_id, $action_type, $ref_id, 
        $change_boxes, $change_pieces, $balance_boxes, $balance_pieces, $notes
    ]);
}
