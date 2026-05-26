<?php
// logout.php
require_once __DIR__ . '/config/session.php';

$role = $_SESSION['role'] ?? '';

// Clear session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session data
session_destroy();

// Redirect back to specific portal login page
switch ($role) {
    case 'admin':
        header('Location: ' . rootPath() . '/admin/login');
        break;
    case 'manager':
        header('Location: ' . rootPath() . '/manager/login');
        break;
    case 'dsr':
        header('Location: ' . rootPath() . '/dsr/login');
        break;
    default:
        header('Location: ' . rootPath() . '/index');
        break;
}
exit;
