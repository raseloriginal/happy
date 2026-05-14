<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('admin');

$pageTitle = 'Database Sync';
$sqlFile = __DIR__ . '/../database/happy_bangladesh.sql';
$fileStatus = file_exists($sqlFile);
$fileSize = $fileStatus ? round(filesize($sqlFile) / 1024, 2) . ' KB' : 'N/A';
$fileModified = $fileStatus ? date("Y-m-d H:i:s", filemtime($sqlFile)) : 'N/A';

$message = '';
$status = '';
$logs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_db'])) {
    if (!$fileStatus) {
        $message = "Error: SQL file not found at $sqlFile";
        $status = 'error';
    } else {
        try {
            $pdo = getDB();
            $sqlContent = file_get_contents($sqlFile);
            
            // Remove comments and empty lines to clean up the logs
            $sqlContent = preg_replace('/--.*?\n/', '', $sqlContent);
            $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);
            
            // Split by semicolon, but be careful with triggers/procedures if any
            // For this project, simple semicolon split is likely enough as seen in the .sql file
            $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
            
            $count = 0;
            foreach ($statements as $stmt) {
                if (empty($stmt)) continue;

                // 1. Skip INSERT statements (Data Sync)
                if (stripos($stmt, 'INSERT ') === 0) {
                    $logs[] = "Skipped data statement: " . substr($stmt, 0, 30) . "...";
                    continue;
                }

                // 2. Skip environment-specific statements
                if (stripos($stmt, 'CREATE DATABASE') !== false || stripos($stmt, 'USE ') === 0) {
                    $logs[] = "Skipped environment statement: " . substr($stmt, 0, 30) . "...";
                    continue;
                }

                // 3. Handle CREATE TABLE (Table & Column Sync)
                if (stripos($stmt, 'CREATE TABLE') !== false) {
                    // Always try to run CREATE TABLE IF NOT EXISTS
                    $pdo->exec($stmt);
                    
                    // Intelligent Column Sync:
                    // Extract table name and column definitions
                    if (preg_match('/CREATE TABLE IF NOT EXISTS\s+`?(\w+)`?\s*\((.*)\)/is', $stmt, $matches)) {
                        $tableName = $matches[1];
                        $columnLines = explode(',', $matches[2]);
                        
                        // Get current columns in DB
                        $existingColumns = $pdo->query("DESCRIBE `$tableName`")->fetchAll(PDO::FETCH_COLUMN);
                        
                        foreach ($columnLines as $line) {
                            $line = trim($line);
                            // Simple regex to find column name (ignoring constraints like PRIMARY KEY, FOREIGN KEY)
                            if (preg_match('/^`?(\w+)`?\s+[\w\(]+/', $line, $colMatches)) {
                                $colName = $colMatches[1];
                                $keywords = ['PRIMARY', 'FOREIGN', 'KEY', 'UNIQUE', 'CONSTRAINT', 'INDEX'];
                                if (in_array(strtoupper($colName), $keywords)) continue;
                                
                                if (!in_array($colName, $existingColumns)) {
                                    try {
                                        $pdo->exec("ALTER TABLE `$tableName` ADD COLUMN $line");
                                        $logs[] = "Added missing column `$colName` to `$tableName`";
                                    } catch (Exception $colEx) {
                                        $logs[] = "Failed to add column `$colName`: " . $colEx->getMessage();
                                    }
                                }
                            }
                        }
                    }
                    
                    $count++;
                    if ($count < 15) {
                        $logs[] = "Processed table structure: $tableName";
                    }
                } else {
                    // For other structural statements (ALTER, etc)
                    $pdo->exec($stmt);
                    $count++;
                }
            }
            
            $message = "Schema synced successfully! $count structural blocks processed. Data was ignored.";
            $status = 'success';
        } catch (Exception $e) {
            $message = "Sync failed: " . $e->getMessage();
            $status = 'error';
            $logs[] = "Error at block $count: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../includes/navbar.php'; ?>
        
        <div class="page-body p-6">
            <div class="max-w-4xl mx-auto">
                <!-- Header Section -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Database Synchronizer</h1>
                    <p class="text-gray-500">Apply the master schema from <code>database/happy_bangladesh.sql</code> to your current environment.</p>
                </div>

                <!-- Status Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                        <div class="text-xs font-bold text-indigo-500 uppercase mb-1">Environment</div>
                        <div class="text-lg font-semibold text-gray-800"><?= DB_HOST ?></div>
                        <div class="text-sm text-gray-400">Database: <?= DB_NAME ?></div>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                        <div class="text-xs font-bold text-indigo-500 uppercase mb-1">SQL File</div>
                        <div class="text-lg font-semibold text-gray-800"><?= basename($sqlFile) ?></div>
                        <div class="text-sm text-gray-400">Size: <?= $fileSize ?></div>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                        <div class="text-xs font-bold text-indigo-500 uppercase mb-1">Last Updated</div>
                        <div class="text-lg font-semibold text-gray-800"><?= $fileStatus ? date("M d, Y", filemtime($sqlFile)) : 'N/A' ?></div>
                        <div class="text-sm text-gray-400"><?= $fileModified ?></div>
                    </div>
                </div>

                <!-- Sync Action Box -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden mb-8">
                    <div class="p-8">
                        <div class="flex items-start gap-6">
                            <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center flex-shrink-0">
                                <span class="text-3xl">🔄</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Synchronize Database</h3>
                                <p class="text-gray-600 mb-6 leading-relaxed">
                                    Clicking the button below will synchronize your database <strong>structure</strong> (tables and columns) with the master SQL file. 
                                    All <code>INSERT</code> statements (data) are automatically <strong>ignored</strong> to protect your existing records.
                                    New columns added to the SQL file will be automatically added to your database.
                                </p>
                                
                                <?php if ($message): ?>
                                    <div class="mb-6 p-4 rounded-lg <?= $status === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100' ?>">
                                        <div class="flex items-center gap-2 font-bold mb-1">
                                            <span><?= $status === 'success' ? '✅' : '❌' ?></span>
                                            <span><?= $status === 'success' ? 'Success' : 'Error' ?></span>
                                        </div>
                                        <p class="text-sm"><?= htmlspecialchars($message) ?></p>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" onsubmit="return confirm('Are you sure you want to sync the database? This will apply all changes from the SQL file.');">
                                    <button type="submit" name="sync_db" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg hover:shadow-indigo-200 active:scale-95 disabled:opacity-50" <?= !$fileStatus ? 'disabled' : '' ?>>
                                        <span>🚀</span>
                                        <span>Start Sync Process</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($logs)): ?>
                    <div class="bg-gray-900 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Execution Logs</h4>
                            <span class="text-[10px] bg-gray-800 text-gray-400 px-2 py-1 rounded">Real-time Feedback</span>
                        </div>
                        <div class="font-mono text-sm space-y-1 max-h-60 overflow-y-auto custom-scrollbar">
                            <?php foreach ($logs as $log): ?>
                                <div class="text-gray-300">
                                    <span class="text-indigo-400 mr-2">></span>
                                    <?= htmlspecialchars($log) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Help / Info -->
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-5 flex gap-4">
                    <div class="text-blue-500 text-xl">💡</div>
                    <div class="text-sm text-blue-700 leading-relaxed">
                        <strong>Why use this?</strong> When you push changes to GitHub, the SQL file is updated but your live database isn't. This page allows you to pull those changes into the system with one click, ensuring your environment always matches the codebase.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
