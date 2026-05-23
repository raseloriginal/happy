<?php
// index.php — Gateway & Session Router

// Define paths
function rootPath(): string {
    static $path = null;
    if ($path === null) {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        $path = str_replace(['/manager', '/admin', '/api', '/dsr', '/dealer', '/config', '/includes', '/eggland'], '', $scriptDir);
        $path = rtrim($path, '/');
    }
    return $path;
}

function rootUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host . rootPath();
}

function getDashboardUrl(string $role): string {
    $base = rootUrl();
    switch ($role) {
        case 'admin':   return $base . '/admin/index.php';
        case 'manager': return $base . '/manager/index.php';
        case 'dsr':     return $base . '/dsr/index.php';
        case 'dealer':  return $base . '/dealer/index.php';
        default:        return $base . '/index.php';
    }
}

// Check if already logged in to any session and redirect
$roles = ['admin', 'manager', 'dsr', 'dealer'];
foreach ($roles as $r) {
    $sessName = 'HAPPY_' . strtoupper($r) . '_SESS';
    if (isset($_COOKIE[$sessName])) {
        // Temporarily switch session to this name
        session_name($sessName);
        
        // Match the same settings as config/session.php
        $session_lifetime = 30 * 24 * 60 * 60;
        $session_dir = __DIR__ . '/config/sessions';
        ini_set('session.save_path', $session_dir);
        ini_set('session.gc_maxlifetime', $session_lifetime);
        session_set_cookie_params([
            'lifetime' => $session_lifetime,
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === $r) {
            header('Location: ' . getDashboardUrl($r));
            exit;
        }
        
        session_write_close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gateway Portal — Happy Bangladesh ERP</title>
  <meta name="description" content="Access gateway to Happy Bangladesh ERP enterprise platforms" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
  <style>
    :root {
      --bg: #09090b;
      --card-bg: #18181b;
      --border-color: #27272a;
      --text-main: #f4f4f5;
      --text-dim: #a1a1aa;
      --accent: #3b82f6;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      border-radius: 0 !important; /* STRICT REQUIREMENT: No border radius */
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #09090b 0%, #0f172a 50%, #1c1917 100%);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 2rem;
    }

    header {
      text-align: center;
      margin-top: 2rem;
      margin-bottom: 2rem;
    }

    header img {
      max-height: 70px;
      width: auto;
      object-fit: contain;
      margin-bottom: 1.25rem;
    }

    header h1 {
      font-size: 1.5rem;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      margin-bottom: 0.5rem;
    }

    header p {
      font-size: 0.85rem;
      color: var(--text-dim);
      letter-spacing: 0.05em;
    }

    .portal-grid {
      display: grid;
      grid-template-columns: repeat(1, 1fr);
      gap: 2rem;
      max-width: 1100px;
      width: 100%;
      margin: 0 auto;
    }

    @media (min-width: 768px) {
      .portal-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    .portal-card {
      background-color: var(--card-bg);
      border: 2px solid var(--border-color);
      padding: 3rem 2rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: all 0.25s ease;
      cursor: pointer;
      text-decoration: none;
      color: inherit;
    }

    /* Admin Portal Hover Card Styling (Blue) */
    .portal-card.admin:hover {
      border-color: #3b82f6;
      box-shadow: 0 0 15px rgba(59, 130, 246, 0.25);
    }
    .portal-card.admin .icon-box {
      color: #3b82f6;
    }
    .portal-card.admin .btn-portal {
      background-color: #2563eb;
    }
    .portal-card.admin .btn-portal:hover {
      background-color: #1d4ed8;
    }

    /* Manager Portal Hover Card Styling (Gray) */
    .portal-card.manager:hover {
      border-color: #e4e4e7;
      box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
    }
    .portal-card.manager .icon-box {
      color: #a1a1aa;
    }
    .portal-card.manager .btn-portal {
      background-color: #3f3f46;
    }
    .portal-card.manager .btn-portal:hover {
      background-color: #52525b;
    }

    /* DSR Portal Hover Card Styling (White) */
    .portal-card.dsr:hover {
      border-color: #ffffff;
      box-shadow: 0 0 15px rgba(255, 255, 255, 0.15);
    }
    .portal-card.dsr .icon-box {
      color: #ffffff;
    }
    .portal-card.dsr .btn-portal {
      background-color: #ffffff;
      color: #000000;
    }
    .portal-card.dsr .btn-portal:hover {
      background-color: #e4e4e7;
    }

    .card-top {
      margin-bottom: 2rem;
    }

    .icon-box {
      font-size: 2.25rem;
      margin-bottom: 1.5rem;
    }

    .portal-card h2 {
      font-size: 1.25rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.75rem;
    }

    .portal-card p {
      font-size: 0.85rem;
      color: var(--text-dim);
      line-height: 1.5;
    }

    .btn-portal {
      width: 100%;
      color: #ffffff;
      text-align: center;
      padding: 0.85rem;
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      border: none;
      transition: all 0.15s ease;
    }

    footer {
      text-align: center;
      margin-top: 3rem;
      padding-top: 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    footer p {
      font-size: 0.65rem;
      color: var(--text-dim);
      text-transform: uppercase;
      letter-spacing: 0.15em;
      font-weight: 600;
    }
  </style>
</head>
<body>

  <header>
    <img src="assets/img/logo/logo.png" alt="Happy Bangladesh ERP" />
    <h1>Enterprise Gateway</h1>
    <p>Select your operational portal to authenticate</p>
  </header>

  <main class="portal-grid">
    <!-- Admin Portal -->
    <a href="admin/login.php" class="portal-card admin" id="portal-admin">
      <div class="card-top">
        <div class="icon-box"><i class="fa-solid fa-user-shield"></i></div>
        <h2>Admin Portal</h2>
        <p>System configuration, database maintenance, user accounts control, master data parameters, and full auditing reports.</p>
      </div>
      <div class="btn-portal">Enter Admin Suite</div>
    </a>

    <!-- Manager Portal -->
    <a href="manager/login.php" class="portal-card manager" id="portal-manager">
      <div class="card-top">
        <div class="icon-box"><i class="fa-solid fa-user-tie"></i></div>
        <h2>Manager Portal</h2>
        <p>Lot production, QR generation, order dispatch flows, delivery validations, returns tracking, and live warehouse inventory control.</p>
      </div>
      <div class="btn-portal">Enter Manager Suite</div>
    </a>

    <!-- DSR Portal -->
    <a href="dsr/login.php" class="portal-card dsr" id="portal-dsr">
      <div class="card-top">
        <div class="icon-box"><i class="fa-solid fa-truck"></i></div>
        <h2>DSR Sheet App</h2>
        <p>Progressive Web App (PWA) built for field delivery operations. Daily dispatch sheets, cash collections, banknote audit, and expenses.</p>
      </div>
      <div class="btn-portal">Enter DSR Panel</div>
    </a>
  </main>

  <footer>
    <p>Happy Bangladesh Cloud Security Protocol &copy; 2026</p>
  </footer>

</body>
</html>
