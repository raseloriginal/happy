<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole(['manager', 'admin']);

$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px; color:#ef4444;'><strong>Error:</strong> Invalid Ready Sale ID provided.</div>");
}

$pdo = getDB();

// Fetch specific ready sale order info
$order = $pdo->prepare("
    SELECT o.*, u.name as sr_name, u.phone as sr_phone,
           c.name as company_name, c.contact as company_phone, c.address as company_address,
           d.name as dealer_name, d.phone as dealer_phone
    FROM orders o
    JOIN sr s ON s.id = o.sr_id
    JOIN users u ON u.id = s.user_id
    JOIN companies c ON c.id = o.company_id
    LEFT JOIN dealers d ON d.id = c.dealer_id
    WHERE o.id = ? AND o.status = 'ready_sale'
");
$order->execute([$orderId]);
$orderInfo = $order->fetch();

if (!$orderInfo) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px; color:#ef4444;'><strong>Error:</strong> Finalized Ready Sale Order #{$orderId} not found.</div>");
}

// Fetch order items
$itemsStmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.pieces_per_box
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
    ORDER BY oi.id
");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

// Calculate total
$grandTotal = 0;
foreach ($items as $item) {
    $grandTotal += $item['qty_pieces'] * $item['unit_price'];
}

// Fetch dynamic Warehouse details
$wid = $_SESSION['warehouse_id'] ?? 1;
$whStmt = $pdo->prepare("SELECT * FROM warehouses WHERE id = ?");
$whStmt->execute([$wid]);
$warehouse = $whStmt->fetch() ?: [
    'name' => 'Main Warehouse',
    'address' => 'Dhaka, Bangladesh',
    'area' => 'Dhaka'
];

$invDate = date('M d, Y', strtotime($orderInfo['order_date']));
$dueDate = date('M d, Y', strtotime($orderInfo['order_date'])); // Due immediately on counter ready-sale scan
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #INV-RS-<?= str_pad($orderId, 5, '0', STR_PAD_LEFT) ?> — Happy Bangladesh</title>
    <style>
        /* Print & Page Setup */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        @page {
            size: A4;
            margin: 10mm; /* Fixed margins for standard printers */
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #000000;
            margin: 0;
            padding: 0;
            font-size: 8.5pt; /* Compact font size to fit 2 copies perfectly */
            line-height: 1.3;
            background-color: #f4f6f9;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Action Bar / Floating Print Button Layout */
        .action-bar {
            max-width: 800px;
            margin: 15px auto 0 auto;
            padding: 10px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-align: right;
        }
        .btn-print {
            background-color: #000000;
            color: #ffffff;
            border: none;
            padding: 10px 24px;
            font-size: 10pt;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: background 0.2s;
        }
        .btn-print:hover {
            background-color: #333333;
        }
        
        /* Main Page Wrapper for Screen preview */
        .page-wrapper {
            max-width: 800px;
            margin: 15px auto;
            background: #ffffff;
            padding: 5px 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
        }

        /* Container for each invoice copy */
        .invoice-container {
            width: 100%;
            height: 132mm; /* Exact height to ensure no overflow to page 2 */
            position: relative;
            padding: 2mm 0;
        }
        
        /* Dashed Cut Line Divider */
        .divider {
            width: 100%;
            border-top: 1px dashed #000000;
            text-align: center;
            margin: 2mm 0;
            position: relative;
        }
        .divider span {
            background: #ffffff;
            padding: 0 10px;
            font-size: 8pt;
            color: #000000;
            position: relative;
            top: -9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }
        
        /* Layout Tables */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
        }
        .brand-section {
            width: 50%;
            vertical-align: top;
        }
        .brand-title {
            font-size: 15pt;
            font-weight: bold;
            color: #000000;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .copy-badge {
            display: inline-block;
            padding: 3px 8px;
            font-weight: bold;
            font-size: 7.5pt;
            text-transform: uppercase;
            border-radius: 3px;
            margin-top: 1.5mm;
        }
        .company-details {
            width: 50%;
            text-align: right;
            font-size: 7.5pt;
            color: #000000;
            line-height: 1.3;
            vertical-align: top;
        }
        
        /* Invoice Metadata Box */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
            border: 1px solid #000000;
            margin-bottom: 2mm;
        }
        .meta-table td {
            padding: 4px 6px;
            width: 16.66%;
            border-right: 1px solid #000000;
        }
        .meta-table td:last-child {
            border-right: none;
        }
        .meta-label {
            font-weight: bold;
            color: #000000;
            font-size: 7.5pt;
        }
        
        /* Customer Shipping & Billing info */
        .client-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
        }
        .client-table th {
            background-color: #000000;
            color: #ffffff;
            text-align: left;
            padding: 3px 6px;
            font-size: 8pt;
            font-weight: 600;
            border: 1px solid #000000;
        }
        .client-table td {
            width: 50%;
            border: 1px solid #000000;
            padding: 4px 6px;
            vertical-align: top;
            font-size: 7.5pt;
            line-height: 1.3;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
        }
        .items-table th {
            background-color: #000000;
            color: #ffffff;
            padding: 4px 6px;
            font-size: 7.5pt;
            text-align: left;
            font-weight: 600;
            border: 1px solid #000000;
        }
        .items-table td {
            padding: 3px 6px;
            border: 1px solid #000000;
            font-size: 7.5pt;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        /* Notes and Financial Totals */
        .bottom-section {
            width: 100%;
            border-collapse: collapse;
        }
        .notes-td {
            width: 55%;
            vertical-align: top;
            padding-right: 4mm;
        }
        .notes-box {
            background-color: #ffffff;
            border: 1px solid #000000;
            border-left: 3px solid #000000;
            padding: 4px 6px;
            font-size: 7pt;
            color: #000000;
            margin-bottom: 1.5mm;
        }
        .summary-td {
            width: 45%;
            vertical-align: top;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
        }
        .summary-table td {
            padding: 3px 6px;
            border-bottom: 1px solid #000000;
            font-size: 7.5pt;
        }
        .summary-table tr:last-child td {
            border-bottom: none;
        }
        .summary-label {
            font-weight: bold;
            color: #000000;
        }
        .total-row td {
            background-color: #000000;
            color: #ffffff;
            font-weight: bold;
            font-size: 8.5pt;
            border: none;
        }
        
        /* Signature Lines */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3mm;
        }
        .signatures-table td {
            width: 33.33%;
            text-align: center;
            font-size: 7pt;
            color: #000000;
            vertical-align: bottom;
            padding-top: 6mm;
        }
        .sig-line {
            border-top: 1px dashed #000000;
            width: 75%;
            margin: 0 auto 1mm auto;
        }
        .footer-text {
            text-align: center;
            font-size: 6.5pt;
            color: #000000;
            margin-top: 2mm;
            border-top: 1px solid #000000;
            padding-top: 1mm;
        }

        /* CRITICAL: Hides the print button and backgrounds on the actual paper */
        @media print {
            body {
                background-color: #ffffff;
                margin: 0;
                padding: 0;
            }
            .action-bar {
                display: none !important; /* Hides the button bar completely */
            }
            .page-wrapper {
                max-width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }
            .divider span {
                background: #ffffff !important;
            }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <button class="btn-print" onclick="window.print()">🖨️ Print Invoice</button>
    </div>

    <div class="page-wrapper">
        
        <!-- COPY 1: OFFICE COPY -->
        <div class="invoice-container">
            <table class="header-table">
                <tr>
                    <td class="brand-section">
                        <h1 class="brand-title">HAPPY BANGLADESH</h1>
                        <span class="copy-badge" style="background-color: #000000; color: #ffffff; border: 1px solid #000000;">OFFICE COPY</span>
                    </td>
                    <td class="company-details">
                        <strong>HQ:</strong> Holding 01, Office 158-01, Charghat Bazar, Rajshahi<br>
                        <strong>Licence:</strong> 00158-01 | <strong>ID:</strong> 06-021-00158-01<br>
                        <strong>Contact:</strong> +88 01300-888811 | info@happybangladesh.com
                    </td>
                </tr>
            </table>

            <table class="meta-table">
                <tr>
                    <td class="meta-label">INVOICE #</td>
                    <td>HB-RS-<?= str_pad($orderId, 5, '0', STR_PAD_LEFT) ?></td>
                    <td class="meta-label">INVOICE DATE</td>
                    <td><?= $invDate ?></td>
                    <td class="meta-label">DUE DATE</td>
                    <td><?= $dueDate ?></td>
                </tr>
            </table>

            <table class="client-table">
                <tr>
                    <th>BILL TO (Mudi Dokan / Retailer)</th>
                    <th>SHIP TO</th>
                </tr>
                <tr>
                    <td>
                        <strong>Client/Store:</strong> <?= htmlspecialchars($orderInfo['company_name']) ?><br>
                        <strong>Proprietor:</strong> <?= htmlspecialchars($orderInfo['dealer_name'] ?: 'N/A') ?><br>
                        <strong>Address:</strong> <?= htmlspecialchars($orderInfo['company_address'] ?: 'Dhaka, Bangladesh') ?>
                    </td>
                    <td>
                        <strong>Delivery Location:</strong> Same as Billing Address<br>
                        <strong>Contact No:</strong> <?= htmlspecialchars($orderInfo['company_phone'] ?: 'N/A') ?><br>
                        <strong>Route/Area:</strong> <?= htmlspecialchars($orderInfo['sr_name']) ?> (SR)
                    </td>
                </tr>
            </table>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-center" style="width: 14%;">Qty</th>
                        <th class="text-right" style="width: 14%;">Rate</th>
                        <th class="text-center" style="width: 10%;">Tax %</th>
                        <th class="text-right" style="width: 18%;">Total (Excl. Tax)</th>
                        <th class="text-right" style="width: 14%;">Tax Amt</th>
                        <th class="text-right" style="width: 18%;">Total (Incl. Tax)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $itemCount1 = 0;
                    foreach ($items as $item): 
                        $itemCount1++;
                        $rowTotal = $item['qty_pieces'] * $item['unit_price'];
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                            <?php if ($item['pieces_per_box'] > 1): 
                                $boxes = floor($item['qty_pieces'] / $item['pieces_per_box']);
                                $rem = $item['qty_pieces'] % $item['pieces_per_box'];
                                echo '<span style="font-size: 7pt; color: #555555; display: block; margin-top: 1px;">';
                                if ($boxes > 0) echo $boxes . ' box';
                                if ($boxes > 0 && $rem > 0) echo ' + ';
                                if ($rem > 0 || $boxes == 0) echo $rem . ' pcs';
                                echo '</span>';
                            endif; ?>
                        </td>
                        <td class="text-center"><?= htmlspecialchars($item['qty_pieces']) ?> pcs</td>
                        <td class="text-right">৳ <?= number_format($item['unit_price'], 2) ?></td>
                        <td class="text-center">0%</td>
                        <td class="text-right">৳ <?= number_format($rowTotal, 2) ?></td>
                        <td class="text-right">৳ 0.00</td>
                        <td class="text-right">৳ <?= number_format($rowTotal, 2) ?></td>
                    </tr>
                    <?php endforeach; 
                    // Render empty spacer rows if item count is small to preserve vertical alignment
                    for ($i = $itemCount1; $i < 4; $i++):
                    ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td class="text-center">&nbsp;</td>
                        <td class="text-right">&nbsp;</td>
                        <td class="text-center">&nbsp;</td>
                        <td class="text-right">&nbsp;</td>
                        <td class="text-right">&nbsp;</td>
                        <td class="text-right">&nbsp;</td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <table class="bottom-section">
                <tr>
                    <td class="notes-td">
                        <div class="notes-box">
                            <strong>Terms:</strong> All ready sale invoices are processed instantly. Goods received in fully undamaged condition.
                        </div>
                        <div class="notes-box">
                            <strong>Payment:</strong> Fully Paid in cash/MFS at warehouse desk. Thank you!
                        </div>
                    </td>
                    <td class="summary-td">
                        <table class="summary-table">
                            <tr>
                                <td class="summary-label">Subtotal (Base Cost):</td>
                                <td class="text-right">৳ <?= number_format($grandTotal, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="summary-label">Total Tax Amount:</td>
                                <td class="text-right">৳ 0.00</td>
                            </tr>
                            <tr>
                                <td class="summary-label">Delivery Fee:</td>
                                <td class="text-right">৳ 0.00</td>
                            </tr>
                            <tr class="total-row">
                                <td class="summary-label" style="color: #fff;">TOTAL DUE:</td>
                                <td class="text-right">৳ <?= number_format($grandTotal, 2) ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table class="signatures-table">
                <tr>
                    <td><div class="sig-line"></div>Prepared By</td>
                    <td><div class="sig-line"></div>Delivery Officer</td>
                    <td><div class="sig-line"></div>Retailer Signature & Seal</td>
                </tr>
            </table>

            <div class="footer-text">
                Happy Bangladesh | Rajshahi, Bangladesh | Licence No: 00158-01 | +88 01300-888811
            </div>
        </div>

        <div class="divider">
            <span>✂ CUT HERE TO SEPARATE COPIES ✂</span>
        </div>

        <!-- COPY 2: RETAILER RECEIVE COPY -->
        <div class="invoice-container">
            <table class="header-table">
                <tr>
                    <td class="brand-section">
                        <h1 class="brand-title">HAPPY BANGLADESH</h1>
                        <span class="copy-badge" style="background-color: #ffffff; color: #000000; border: 1px solid #000000;">RETAILER RECEIVE COPY</span>
                    </td>
                    <td class="company-details">
                        <strong>HQ:</strong> Holding 01, Office 158-01, Charghat Bazar, Rajshahi<br>
                        <strong>Licence:</strong> 00158-01 | <strong>ID:</strong> 06-021-00158-01<br>
                        <strong>Contact:</strong> +88 01300-888811 | info@happybangladesh.com
                    </td>
                </tr>
            </table>

            <table class="meta-table">
                <tr>
                    <td class="meta-label">INVOICE #</td>
                    <td>HB-RS-<?= str_pad($orderId, 5, '0', STR_PAD_LEFT) ?></td>
                    <td class="meta-label">INVOICE DATE</td>
                    <td><?= $invDate ?></td>
                    <td class="meta-label">DUE DATE</td>
                    <td><?= $dueDate ?></td>
                </tr>
            </table>

            <table class="client-table">
                <tr>
                    <th>BILL TO (Mudi Dokan / Retailer)</th>
                    <th>SHIP TO</th>
                </tr>
                <tr>
                    <td>
                        <strong>Client/Store:</strong> <?= htmlspecialchars($orderInfo['company_name']) ?><br>
                        <strong>Proprietor:</strong> <?= htmlspecialchars($orderInfo['dealer_name'] ?: 'N/A') ?><br>
                        <strong>Address:</strong> <?= htmlspecialchars($orderInfo['company_address'] ?: 'Dhaka, Bangladesh') ?>
                    </td>
                    <td>
                        <strong>Delivery Location:</strong> Same as Billing Address<br>
                        <strong>Contact No:</strong> <?= htmlspecialchars($orderInfo['company_phone'] ?: 'N/A') ?><br>
                        <strong>Route/Area:</strong> <?= htmlspecialchars($orderInfo['sr_name']) ?> (SR)
                    </td>
                </tr>
            </table>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-center" style="width: 14%;">Qty</th>
                        <th class="text-right" style="width: 14%;">Rate</th>
                        <th class="text-center" style="width: 10%;">Tax %</th>
                        <th class="text-right" style="width: 18%;">Total (Excl. Tax)</th>
                        <th class="text-right" style="width: 14%;">Tax Amt</th>
                        <th class="text-right" style="width: 18%;">Total (Incl. Tax)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $itemCount2 = 0;
                    foreach ($items as $item): 
                        $itemCount2++;
                        $rowTotal = $item['qty_pieces'] * $item['unit_price'];
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                            <?php if ($item['pieces_per_box'] > 1): 
                                $boxes = floor($item['qty_pieces'] / $item['pieces_per_box']);
                                $rem = $item['qty_pieces'] % $item['pieces_per_box'];
                                echo '<span style="font-size: 7pt; color: #555555; display: block; margin-top: 1px;">';
                                if ($boxes > 0) echo $boxes . ' box';
                                if ($boxes > 0 && $rem > 0) echo ' + ';
                                if ($rem > 0 || $boxes == 0) echo $rem . ' pcs';
                                echo '</span>';
                            endif; ?>
                        </td>
                        <td class="text-center"><?= htmlspecialchars($item['qty_pieces']) ?> pcs</td>
                        <td class="text-right">৳ <?= number_format($item['unit_price'], 2) ?></td>
                        <td class="text-center">0%</td>
                        <td class="text-right">৳ <?= number_format($rowTotal, 2) ?></td>
                        <td class="text-right">৳ 0.00</td>
                        <td class="text-right">৳ <?= number_format($rowTotal, 2) ?></td>
                    </tr>
                    <?php endforeach; 
                    // Render empty spacer rows if item count is small to preserve vertical alignment
                    for ($i = $itemCount2; $i < 4; $i++):
                    ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td class="text-center">&nbsp;</td>
                        <td class="text-right">&nbsp;</td>
                        <td class="text-center">&nbsp;</td>
                        <td class="text-right">&nbsp;</td>
                        <td class="text-right">&nbsp;</td>
                        <td class="text-right">&nbsp;</td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <table class="bottom-section">
                <tr>
                    <td class="notes-td">
                        <div class="notes-box">
                            <strong>Terms:</strong> All ready sale invoices are processed instantly. Goods received in fully undamaged condition.
                        </div>
                        <div class="notes-box">
                            <strong>Payment:</strong> Fully Paid in cash/MFS at warehouse desk. Thank you!
                        </div>
                    </td>
                    <td class="summary-td">
                        <table class="summary-table">
                            <tr>
                                <td class="summary-label">Subtotal (Base Cost):</td>
                                <td class="text-right">৳ <?= number_format($grandTotal, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="summary-label">Total Tax Amount:</td>
                                <td class="text-right">৳ 0.00</td>
                            </tr>
                            <tr>
                                <td class="summary-label">Delivery Fee:</td>
                                <td class="text-right">৳ 0.00</td>
                            </tr>
                            <tr class="total-row">
                                <td class="summary-label" style="color: #fff;">TOTAL DUE:</td>
                                <td class="text-right">৳ <?= number_format($grandTotal, 2) ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table class="signatures-table">
                <tr>
                    <td><div class="sig-line"></div>Prepared By</td>
                    <td><div class="sig-line"></div>Delivery Officer</td>
                    <td><div class="sig-line"></div>Authorized Signatory</td>
                </tr>
            </table>

            <div class="footer-text">
                Happy Bangladesh | Rajshahi, Bangladesh | Licence No: 00158-01 | +88 01300-888811
            </div>
        </div>
    </div>

</body>
</html>
