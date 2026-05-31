<?php
// TEMPORARY DEBUG — DELETE AFTER DIAGNOSIS
// Visit: https://happy.raseloriginal.digital/dsr/debug_session
header('Content-Type: text/plain');

$https_var     = $_SERVER['HTTPS'] ?? '(not set)';
$forwarded     = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '(not set)';
$port          = $_SERVER['SERVER_PORT'] ?? '(not set)';
$script_name   = $_SERVER['SCRIPT_NAME'] ?? '(not set)';
$referer       = $_SERVER['HTTP_REFERER'] ?? '(not set)';
$host          = $_SERVER['HTTP_HOST'] ?? '(not set)';

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
         || (($_SERVER['SERVER_PORT'] ?? '') == 443);

// Session name detection (mirrors session.php logic)
$script = str_replace('\\', '/', $script_name);
$refererPath = parse_url($referer, PHP_URL_PATH) ?: '';
$session_name = 'HAPPY_GENERAL_SESS';
if (strpos($script, '/admin/') !== false || strpos($refererPath, '/admin/') !== false) {
    $session_name = 'HAPPY_ADMIN_SESS';
} elseif (strpos($script, '/manager/') !== false || strpos($refererPath, '/manager/') !== false) {
    $session_name = 'HAPPY_MANAGER_SESS';
} elseif (strpos($script, '/dsr/') !== false || strpos($refererPath, '/dsr/') !== false) {
    $session_name = 'HAPPY_DSR_SESS';
}

// rootPath logic
$scriptDir = str_replace('\\', '/', dirname($script_name));
$rootPath = str_replace(['/manager', '/admin', '/api', '/dsr', '/dealer', '/config', '/includes', '/eggland'], '', $scriptDir);
$rootPath = rtrim($rootPath, '/');

echo "=== SERVER ENVIRONMENT ===\n";
echo "\$_SERVER['HTTPS']              = $https_var\n";
echo "HTTP_X_FORWARDED_PROTO         = $forwarded\n";
echo "SERVER_PORT                    = $port\n";
echo "HTTP_HOST                      = $host\n";
echo "SCRIPT_NAME                    = $script_name\n";
echo "HTTP_REFERER                   = $referer\n";
echo "\n=== COMPUTED VALUES ===\n";
echo "isHttps (detected)             = " . ($isHttps ? 'YES (https)' : 'NO (http)') . "\n";
echo "rootPath()                     = '$rootPath'\n";
echo "rootUrl()                      = " . ($isHttps ? 'https' : 'http') . "://$host$rootPath\n";
echo "session_name                   = $session_name\n";
echo "getDashboardUrl(dsr)           = " . ($isHttps ? 'https' : 'http') . "://$host$rootPath/dsr/index\n";
echo "\n=== COOKIES ===\n";
foreach ($_COOKIE as $k => $v) {
    echo "$k = " . substr($v, 0, 20) . "...\n";
}
