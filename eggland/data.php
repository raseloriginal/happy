<?php
// eggland/data.php — JSON Persistence & Seed Data layer

require_once __DIR__ . '/../config/session.php';
requireRole('manager');

define('EGGLAND_AGENTS_FILE', __DIR__ . '/agents.json');
define('EGGLAND_PRODUCTS_FILE', __DIR__ . '/products.json');
define('EGGLAND_ORDERS_FILE', __DIR__ . '/orders.json');
define('EGGLAND_ACCOUNTANT_FILE', __DIR__ . '/accountant.json');

// --- Helper Functions to Read/Write JSON safely ---
function eggReadJson(string $filePath): array {
    if (!file_exists($filePath)) {
        return [];
    }
    $content = file_get_contents($filePath);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function eggWriteJson(string $filePath, array $data): bool {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

// Generate simple auto-increment ID
function eggGetNextId(array $items): int {
    $max = 0;
    foreach ($items as $item) {
        $id = intval($item['id'] ?? 0);
        if ($id > $max) $max = $id;
    }
    return $max + 1;
}

// --- AGENTS CRUD ---
function eggGetAgents(): array {
    $agents = eggReadJson(EGGLAND_AGENTS_FILE);
    if (empty($agents)) {
        // Initialize Seed Agents
        $agents = [
            ['id' => 1, 'name' => 'Karim Egg Traders', 'phone' => '01711122233', 'address' => 'Kawran Bazar, Dhaka', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 2, 'name' => 'Bismillah Egg Store', 'phone' => '01822233344', 'address' => 'Halishahar, Chittagong', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 3, 'name' => 'Jamil & Brothers', 'phone' => '01933344455', 'address' => 'Zindabazar, Sylhet', 'created_at' => date('Y-m-d H:i:s')]
        ];
        eggSaveAgents($agents);
    }
    return $agents;
}

function eggSaveAgents(array $agents): bool {
    return eggWriteJson(EGGLAND_AGENTS_FILE, $agents);
}

function eggGetAgent(int $id): ?array {
    $agents = eggGetAgents();
    foreach ($agents as $agent) {
        if (intval($agent['id']) === $id) return $agent;
    }
    return null;
}

// --- PRODUCTS CRUD ---
function eggGetProducts(): array {
    $products = eggReadJson(EGGLAND_PRODUCTS_FILE);
    if (empty($products)) {
        // Initialize Seed Products
        $products = [
            ['id' => 1, 'name' => 'Red Poultry Eggs (Tray of 30)', 'price' => 320.00, 'unit' => 'Tray', 'description' => 'Standard red farm poultry eggs', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 2, 'name' => 'White Poultry Eggs (Tray of 30)', 'price' => 300.00, 'unit' => 'Tray', 'description' => 'Standard white farm poultry eggs', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 3, 'name' => 'Duck Eggs (Tray of 30)', 'price' => 420.00, 'unit' => 'Tray', 'description' => 'Fresh large size duck eggs', 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 4, 'name' => 'Organic Country Chicken Eggs (10 Pcs)', 'price' => 180.00, 'unit' => 'Pack', 'description' => 'Deshi free-range chicken eggs', 'created_at' => date('Y-m-d H:i:s')]
        ];
        eggSaveProducts($products);
    }
    return $products;
}

function eggSaveProducts(array $products): bool {
    return eggWriteJson(EGGLAND_PRODUCTS_FILE, $products);
}

function eggGetProduct(int $id): ?array {
    $products = eggGetProducts();
    foreach ($products as $product) {
        if (intval($product['id']) === $id) return $product;
    }
    return null;
}

// --- ORDERS CRUD ---
function eggGetOrders(): array {
    $orders = eggReadJson(EGGLAND_ORDERS_FILE);
    if (empty($orders)) {
        // Generate nice seed orders
        $orders = [
            [
                'id' => 1,
                'agent_id' => 1,
                'order_date' => date('Y-m-d', strtotime('-5 days')),
                'items' => [
                    ['product_id' => 1, 'product_name' => 'Red Poultry Eggs (Tray of 30)', 'quantity' => 50, 'price' => 320.00, 'subtotal' => 16000.00],
                    ['product_id' => 2, 'product_name' => 'White Poultry Eggs (Tray of 30)', 'quantity' => 30, 'price' => 300.00, 'subtotal' => 9000.00]
                ],
                'total_amount' => 25000.00,
                'notes' => 'Urgent delivery for Kawran Bazar wholesale shop.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ],
            [
                'id' => 2,
                'agent_id' => 2,
                'order_date' => date('Y-m-d', strtotime('-3 days')),
                'items' => [
                    ['product_id' => 3, 'product_name' => 'Duck Eggs (Tray of 30)', 'quantity' => 20, 'price' => 420.00, 'subtotal' => 8400.00]
                ],
                'total_amount' => 8400.00,
                'notes' => 'Standard weekly supply.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],
            [
                'id' => 3,
                'agent_id' => 1,
                'order_date' => date('Y-m-d', strtotime('-1 days')),
                'items' => [
                    ['product_id' => 4, 'product_name' => 'Organic Country Chicken Eggs (10 Pcs)', 'quantity' => 10, 'price' => 180.00, 'subtotal' => 1800.00]
                ],
                'total_amount' => 1800.00,
                'notes' => 'Trial pack.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 days'))
            ]
        ];
        eggSaveOrders($orders);
    }
    return $orders;
}

function eggSaveOrders(array $orders): bool {
    return eggWriteJson(EGGLAND_ORDERS_FILE, $orders);
}

function eggGetOrder(int $id): ?array {
    $orders = eggGetOrders();
    foreach ($orders as $order) {
        if (intval($order['id']) === $id) return $order;
    }
    return null;
}

// --- ACCOUNTANT DEPOSITS CRUD ---
function eggGetDeposits(): array {
    $deposits = eggReadJson(EGGLAND_ACCOUNTANT_FILE);
    if (empty($deposits)) {
        // Generate nice seed accountant deposits/receipts
        $deposits = [
            [
                'id' => 1,
                'agent_id' => 1,
                'amount' => 15000.00,
                'deposit_date' => date('Y-m-d', strtotime('-4 days')),
                'notes' => 'Received via bank transfer (DBBL).',
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))
            ],
            [
                'id' => 2,
                'agent_id' => 2,
                'amount' => 8400.00,
                'deposit_date' => date('Y-m-d', strtotime('-2 days')),
                'notes' => 'Cash received by delivery officer.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ]
        ];
        eggSaveDeposits($deposits);
    }
    return $deposits;
}

function eggSaveDeposits(array $deposits): bool {
    return eggWriteJson(EGGLAND_ACCOUNTANT_FILE, $deposits);
}

function eggGetDeposit(int $id): ?array {
    $deposits = eggGetDeposits();
    foreach ($deposits as $dep) {
        if (intval($dep['id']) === $id) return $dep;
    }
    return null;
}

// --- COMPLEX LEDGER CALCULATIONS ---
// Get Agent Ledger balances (Sum orders vs Sum deposits)
function eggGetLedgerData(): array {
    $agents = eggGetAgents();
    $orders = eggGetOrders();
    $deposits = eggGetDeposits();

    $ledger = [];
    foreach ($agents as $agent) {
        $aId = intval($agent['id']);
        $totalOrders = 0.0;
        $totalDeposits = 0.0;

        foreach ($orders as $o) {
            if (intval($o['agent_id']) === $aId) {
                $totalOrders += floatval($o['total_amount']);
            }
        }

        foreach ($deposits as $d) {
            if (intval($d['agent_id']) === $aId) {
                $totalDeposits += floatval($d['amount']);
            }
        }

        $ledger[$aId] = [
            'agent' => $agent,
            'total_orders' => $totalOrders,
            'total_deposits' => $totalDeposits,
            'balance' => $totalOrders - $totalDeposits
        ];
    }
    return $ledger;
}

// Chronological transaction statement for a single agent
function eggGetAgentStatement(int $agentId): array {
    $orders = eggGetOrders();
    $deposits = eggGetDeposits();
    $statement = [];

    // Filter orders
    foreach ($orders as $o) {
        if (intval($o['agent_id']) === $agentId) {
            $statement[] = [
                'type' => 'ORDER',
                'id' => $o['id'],
                'date' => $o['order_date'],
                'ref' => 'Order #' . str_pad($o['id'], 4, '0', STR_PAD_LEFT),
                'debit' => floatval($o['total_amount']),
                'credit' => 0.0,
                'notes' => $o['notes'],
                'timestamp' => strtotime($o['order_date'] . ' 00:00:00')
            ];
        }
    }

    // Filter deposits
    foreach ($deposits as $d) {
        if (intval($d['agent_id']) === $agentId) {
            $statement[] = [
                'type' => 'DEPOSIT',
                'id' => $d['id'],
                'date' => $d['deposit_date'],
                'ref' => 'Receipt #' . str_pad($d['id'], 4, '0', STR_PAD_LEFT),
                'debit' => 0.0,
                'credit' => floatval($d['amount']),
                'notes' => $d['notes'],
                'timestamp' => strtotime($d['deposit_date'] . ' 00:00:00')
            ];
        }
    }

    // Sort chronologically
    usort($statement, function ($a, $b) {
        if ($a['timestamp'] === $b['timestamp']) {
            return $a['id'] <=> $b['id'];
        }
        return $a['timestamp'] <=> $b['timestamp'];
    });

    return $statement;
}

// --- EXCEL THEME UI HELPERS ---

function getExcelStyles(): string {
    return '
    <style>
        /* Zero border-radius globally for the authentic sharp Excel look */
        *, .btn, .stat-card, .form-input, .badge, .modal-box, table, tr, th, td, input, select, textarea, .excel-tab {
            border-radius: 0px !important;
        }
        
        /* Excel Color Palette */
        :root {
            --excel-green: #107c41;
            --excel-green-dark: #0a5c30;
            --excel-green-light: #e2f0d9;
            --excel-border: #cbd5e1;
            --excel-row-num: #f3f4f6;
        }
        
        .bg-excel-green {
            background-color: var(--excel-green) !important;
        }
        .text-excel-green {
            color: var(--excel-green) !important;
        }
        .border-excel {
            border-color: var(--excel-border) !important;
        }
        .hover-bg-excel:hover {
            background-color: var(--excel-green-dark) !important;
        }
        
        /* Excel Sheet Tabs Navigation */
        .excel-tab-bar {
            display: flex;
            background-color: #e1dfdd;
            border-bottom: 2px solid var(--excel-green);
            margin-bottom: 1.5rem;
            overflow-x: auto;
            border-top: 1px solid #cbd5e1;
            border-left: 1px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
        }
        .excel-tab {
            padding: 0.5rem 1.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            background-color: #f3f2f1;
            border-right: 1px solid #cbd5e1;
            border-top: 3px solid transparent;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            transition: all 0.15s;
        }
        .excel-tab:hover {
            background-color: #edebe9;
            color: #1e293b;
        }
        .excel-tab.active {
            background-color: #ffffff;
            color: var(--excel-green);
            border-top: 3px solid var(--excel-green);
            border-bottom: 2px solid #ffffff;
            margin-bottom: -2px;
            z-index: 10;
        }
        
        /* Excel grid table styling */
        .excel-grid-container {
            border: 1px solid var(--excel-border);
            background-color: #ffffff;
            overflow-x: auto;
            margin-bottom: 1.5rem;
            width: 100%;
        }
        .excel-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.825rem;
        }
        .excel-table th {
            background-color: #f3f2f1;
            color: #323130;
            font-weight: 600;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--excel-border);
            text-align: left;
            border-bottom: 2px solid #a19f9d;
        }
        .excel-table td {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--excel-border);
            vertical-align: middle;
            color: #323130;
        }
        .excel-table tbody tr:nth-child(even) {
            background-color: #faf9f8;
        }
        .excel-table tbody tr:hover {
            background-color: #f3f2f1;
        }
        .excel-row-header {
            background-color: #f3f2f1;
            color: #605e5c;
            font-weight: bold;
            text-align: center !important;
            width: 40px;
            border-right: 2px solid #cbd5e1 !important;
            font-family: monospace;
            user-select: none;
        }
        
        /* Action buttons Excel styling */
        .btn-excel {
            background-color: var(--excel-green);
            color: #ffffff;
            font-weight: 500;
            border: 1px solid var(--excel-green-dark);
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            cursor: pointer;
            transition: background-color 0.15s;
            font-size: 0.875rem;
            text-decoration: none;
        }
        .btn-excel:hover {
            background-color: var(--excel-green-dark);
        }
        .btn-excel-ghost {
            background-color: #ffffff;
            color: #323130;
            border: 1px solid #cbd5e1;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            cursor: pointer;
            transition: background-color 0.15s;
            font-size: 0.875rem;
            text-decoration: none;
        }
        .btn-excel-ghost:hover {
            background-color: #faf9f8;
        }
        .btn-excel-danger {
            background-color: #a80000;
            color: #ffffff;
            border: 1px solid #8a0000;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            text-decoration: none;
        }
        .btn-excel-danger:hover {
            background-color: #8a0000;
        }
        .btn-excel-warning {
            background-color: #d83b01;
            color: #ffffff;
            border: 1px solid #b83201;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            text-decoration: none;
        }
        .btn-excel-warning:hover {
            background-color: #b83201;
        }
        
        /* Excel form elements */
        .excel-input {
            border: 1px solid var(--excel-border) !important;
            padding: 0.4rem 0.6rem;
            font-size: 0.85rem;
            outline: none;
            width: 100%;
            background-color: #ffffff;
        }
        .excel-input:focus {
            border-color: var(--excel-green) !important;
            box-shadow: 0 0 0 2px rgba(16, 124, 65, 0.15);
        }
        
        /* KPI Cards in Excel Theme */
        .excel-kpi-card {
            background: #ffffff;
            border: 1px solid var(--excel-border);
            padding: 1.25rem;
            border-left: 4px solid var(--excel-green) !important;
        }
    </style>';
}

function renderExcelTabs(string $activeTab): string {
    $r = rootPath();
    $tabs = [
        'dashboard' => ['href' => $r . '/eggland/index.php', 'icon' => 'fa-solid fa-table-cells', 'label' => 'Dashboard'],
        'agents'    => ['href' => $r . '/eggland/agents.php', 'icon' => 'fa-solid fa-users', 'label' => 'Agents Ledger'],
        'products'  => ['href' => $r . '/eggland/products.php', 'icon' => 'fa-solid fa-box', 'label' => 'Products List'],
        'orders'    => ['href' => $r . '/eggland/orders.php', 'icon' => 'fa-solid fa-list-check', 'label' => 'Orders Ledger'],
        'accountant'=> ['href' => $r . '/eggland/accountant.php', 'icon' => 'fa-solid fa-calculator', 'label' => 'Accountant (Deposits)']
    ];
    
    $html = '<div class="excel-tab-bar">';
    foreach ($tabs as $key => $tab) {
        $activeClass = ($activeTab === $key) ? 'active' : '';
        $html .= '<a href="' . $tab['href'] . '" class="excel-tab ' . $activeClass . '">';
        $html .= '<i class="' . $tab['icon'] . '"></i>' . $tab['label'];
        $html .= '</a>';
    }
    $html .= '</div>';
    return $html;
}

