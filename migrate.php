<?php
/**
 * migrate.php — Database Migration System
 * Scans database/migrations/*.sql and executes unapplied scripts.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';

// Optional: Restrict to admins only
// requireRole('admin');

$pdo = getDB();

// 1. Ensure migrations table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) UNIQUE NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$migrationDir = __DIR__ . '/database/migrations';
if (!is_dir($migrationDir)) {
    mkdir($migrationDir, 0755, true);
}

$files = glob($migrationDir . '/*.sql');
sort($files); // Ensure they run in order

$executed = $pdo->query("SELECT migration_name FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

$results = [];

foreach ($files as $file) {
    $filename = basename($file);
    if (in_array($filename, $executed)) {
        $results[] = ['name' => $filename, 'status' => 'skipped', 'message' => 'Already applied'];
        continue;
    }

    try {
        $sql = file_get_contents($file);
        if (trim($sql)) {
            $pdo->exec($sql);
        }
        
        $stmt = $pdo->prepare("INSERT INTO migrations (migration_name) VALUES (?)");
        $stmt->execute([$filename]);
        
        $results[] = ['name' => $filename, 'status' => 'success', 'message' => 'Applied successfully'];
    } catch (Exception $e) {
        $results[] = ['name' => $filename, 'status' => 'error', 'message' => $e->getMessage()];
        // Break on error to prevent inconsistent state
        break;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration — Happy Bangladesh</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0F172A; color: #F8FAFC; }
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
        }
        .glow { box-shadow: 0 0 40px rgba(99, 102, 241, 0.2); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-2xl w-full glass-card p-8 glow">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">Database Migration</h1>
                <p class="text-slate-400 text-sm">Synchronizing schema changes across environments</p>
            </div>
        </div>

        <div class="space-y-3">
            <?php if (empty($results)): ?>
                <div class="bg-slate-800/50 border border-slate-700 p-4 rounded-lg text-center">
                    <p class="text-slate-400">No migration files found in <code class="bg-slate-900 px-1 rounded text-indigo-400">database/migrations/</code></p>
                </div>
            <?php else: ?>
                <?php foreach ($results as $res): ?>
                    <div class="flex items-center justify-between p-4 bg-slate-800/40 border border-slate-700/50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <?php if ($res['status'] === 'success'): ?>
                                <span class="flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            <?php elseif ($res['status'] === 'error'): ?>
                                <span class="flex h-2 w-2 rounded-full bg-rose-500"></span>
                            <?php else: ?>
                                <span class="flex h-2 w-2 rounded-full bg-slate-500"></span>
                            <?php endif; ?>
                            <span class="font-medium text-slate-200"><?= htmlspecialchars($res['name']) ?></span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs uppercase tracking-wider font-bold <?= $res['status'] === 'success' ? 'text-emerald-400' : ($res['status'] === 'error' ? 'text-rose-400' : 'text-slate-400') ?>">
                                <?= $res['status'] ?>
                            </span>
                            <p class="text-xs text-slate-500"><?= htmlspecialchars($res['message']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="mt-8 pt-6 border-t border-slate-700/50 flex justify-between items-center">
            <p class="text-xs text-slate-500 italic">Tip: Use 001_description.sql format for ordering.</p>
            <a href="index.php" class="text-indigo-400 hover:text-indigo-300 text-sm font-medium transition-colors">Return to System &rarr;</a>
        </div>
    </div>
</body>
</html>
