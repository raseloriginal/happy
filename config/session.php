<?php
// config/session.php — Session management & role guards

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require authentication — redirect to login if not logged in
 */
function requireAuth(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . rootUrl() . '/index.php');
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
        case 'admin':   return $base . '/admin/index.php';
        case 'manager': return $base . '/manager/index.php';
        case 'dsr':     return $base . '/dsr/index.php';
        case 'dealer':  return $base . '/dealer/index.php';
        default:        return $base . '/index.php';
    }
}

/**
 * Get the root path of the app (e.g., /happycrm2 or empty string)
 */
function rootPath(): string {
    $is_localhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']);
    return $is_localhost ? '/happycrm2' : '';
}

/**
 * Get the root URL of the app
 */
function rootUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
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
