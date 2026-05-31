<?php
// config/session.php — Session management & role guards

// 30 days in seconds
$session_lifetime = 30 * 24 * 60 * 60;

// Dynamic Session Naming based on script path or Referer
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$script = str_replace('\\', '/', $script);
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$refererPath = parse_url($referer, PHP_URL_PATH) ?: '';

$session_name = 'HAPPY_GENERAL_SESS';

if (strpos($script, '/admin/') !== false || strpos($refererPath, '/admin/') !== false) {
    $session_name = 'HAPPY_ADMIN_SESS';
} elseif (strpos($script, '/manager/') !== false || strpos($refererPath, '/manager/') !== false) {
    $session_name = 'HAPPY_MANAGER_SESS';
} elseif (strpos($script, '/dsr/') !== false || strpos($refererPath, '/dsr/') !== false) {
    $session_name = 'HAPPY_DSR_SESS';
} elseif (strpos($script, '/dealer/') !== false || strpos($refererPath, '/dealer/') !== false) {
    $session_name = 'HAPPY_DEALER_SESS';
}

if (session_status() === PHP_SESSION_NONE) {
    // Custom session save path to prevent deletion by OS temp cleanups
    $session_dir = __DIR__ . '/sessions';
    if (!is_dir($session_dir)) {
        mkdir($session_dir, 0755, true);
        file_put_contents($session_dir . '/.htaccess', "Deny from all\n");
    }
    ini_set('session.save_path', $session_dir);
    ini_set('session.gc_maxlifetime', $session_lifetime);
    
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
             || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    session_set_cookie_params([
        'lifetime' => $session_lifetime,
        'path' => '/',
        'secure' => $is_https, // Auto-detect HTTPS (required for secure cookies on production)
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_name($session_name);
    session_start();
}

/**
 * Require authentication — redirect to specific role login if not logged in
 */
function requireAuth(): void {
    if (empty($_SESSION['user_id'])) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $script = str_replace('\\', '/', $script);
        $base = rootUrl();
        
        if (strpos($script, '/admin/') !== false) {
            header('Location: ' . $base . '/admin/login');
        } elseif (strpos($script, '/manager/') !== false) {
            header('Location: ' . $base . '/manager/login');
        } elseif (strpos($script, '/dsr/') !== false) {
            header('Location: ' . $base . '/dsr/login');
        } else {
            header('Location: ' . $base . '/index');
        }
        exit;
    }
}

/**
 * Require specific role(s) — redirect to their dashboard if wrong role
 * @param string|array $roles
 */
function requireRole($roles): void {
    requireAuth();
    if (is_string($roles)) $roles = [$roles];
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        header('Location: ' . getDashboardUrl());
        exit;
    }
}

/**
 * Get dashboard URL for current role
 */
function getDashboardUrl(): string {
    $base = rootUrl();
    switch ($_SESSION['role'] ?? '') {
        case 'admin':   return $base . '/admin/index';
        case 'manager': return $base . '/manager/index';
        case 'dsr':     return $base . '/dsr/index';
        case 'dealer':  return $base . '/dealer/index';
        default:        return $base . '/index';
    }
}

/**
 * Get the root path of the app (e.g., /happycrm2 or empty string)
 */
function rootPath(): string {
    static $path = null;
    if ($path === null) {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        // Remove known subdirectories to find the root
        $path = str_replace(['/manager', '/admin', '/api', '/dsr', '/dealer', '/config', '/includes', '/eggland'], '', $scriptDir);
        $path = rtrim($path, '/');
    }
    return $path;
}

/**
 * Get the root URL of the app
 */
function rootUrl(): string {
    // Check direct HTTPS, and also X-Forwarded-Proto (set by cPanel/Nginx reverse proxies)
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $protocol = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host . rootPath();
}

/**
 * Get session value safely
 */
function sessionGet(string $key, $default = null) {
    return $_SESSION[$key] ?? $default;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}
