<?php
/**
 * setup.php — One-time database setup script
 * Visit: http://localhost/happycrm2/setup.php
 * DELETE this file after setup is complete!
 */

// Change these if needed
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'happy_bangladesh');

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>HappyCRM Setup</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>body{font-family:sans-serif;max-width:600px;margin:40px auto;padding:20px;background:#f9fafb}
h1{color:#4F46E5}.ok{color:#10B981}.err{color:#EF4444}.warn{color:#F59E0B}
.box{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;margin-bottom:16px}
.btn{background:#4F46E5;color:#fff;padding:10px 24px;border:none;border-radius:8px;cursor:pointer;font-size:14px;text-decoration:none;display:inline-block;margin-top:10px}
i { margin-right: 8px; }
</style></head><body>';

echo '<div class="box"><h1><i class="fa-solid fa-tools"></i> Happy Bangladesh ERP Setup</h1><p>Initializing database and tables...</p></div>';

try {
    // 1. Create Connection (MySQLi for multi_query)
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($conn->connect_error) throw new Exception("Connection failed: " . $conn->connect_error);
    $conn->set_charset('utf8mb4');
    
    echo '<div class="box"><p class="ok"><i class="fa-solid fa-check"></i> Connected to MySQL</p>';

    // 2. Create Database
    $conn->query("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db(DB_NAME);
    echo '<p class="ok"><i class="fa-solid fa-check"></i> Database created/selected: ' . DB_NAME . '</p>';

    // 3. Import SQL File (using multi_query)
    $sql = file_get_contents(__DIR__ . '/database/happy_bangladesh.sql');
    
    // Execute all queries
    if ($conn->multi_query($sql)) {
        do {
            // Consume results
            if ($result = $conn->store_result()) { $result->free(); }
        } while ($conn->next_result());
    }

    if ($conn->error) throw new Exception("SQL Error: " . $conn->error);
    
    echo '<p class="ok"><i class="fa-solid fa-check"></i> Schema imported successfully</p>';

    // 4. Create proper admin user with hashed password (using PDO for better security)
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE password=?")
        ->execute(['Admin', 'admin@happy.com', $hash, 'admin', 1, $hash]);

    echo '<p class="ok"><i class="fa-solid fa-check"></i> Admin user created/updated (admin@happy.com / admin123)</p>';
    echo '</div>';

    echo '<div class="box" style="background:#f0fdf4;border-color:#bbf7d0">
        <h3 class="ok"><i class="fa-solid fa-check-circle"></i> Setup Complete!</h3>
        <p>You can now login at:</p>
        <p><strong>URL:</strong> <a href="/">http://localhost/</a></p>
        <p><strong>Email:</strong> admin@happy.com</p>
        <p><strong>Password:</strong> admin123</p>
        <br>
        <p class="warn"><i class="fa-solid fa-exclamation-triangle"></i> <strong>Important:</strong> Delete this setup.php file after logging in!</p>
        <a href="/" class="btn">Go to Login <i class="fa-solid fa-arrow-right ml-1"></i></a>
    </div>';

} catch (Exception $e) {
    echo '<div class="box"><p class="err"><i class="fa-solid fa-times"></i> Error: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
}

echo '</body></html>';
