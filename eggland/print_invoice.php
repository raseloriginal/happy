<?php
// eggland/print_invoice.php — Printable Invoice (Excel / Ready Sale style)

require_once __DIR__ . '/data.php';
requireRole(['manager', 'admin']);

$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px; color:#ef4444;'><strong>Error:</strong> Invalid Eggland Order ID provided.</div>");
}

// Fetch order info from JSON database
$orderInfo = eggGetOrder($orderId);
if (!$orderInfo) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px; color:#ef4444;'><strong>Error:</strong> Eggland Order #{$orderId} not found in database.</div>");
}

// Fetch agent info
$agentInfo = eggGetAgent(intval($orderInfo['agent_id']));
if (!$agentInfo) {
    $agentInfo = [
        'name' => 'Walk-in Wholesaler / General Agent',
        'phone' => 'N/A',
        'address' => 'Eggland Business District'
    ];
}

$items = $orderInfo['items'] ?? [];
$grandTotal = floatval($orderInfo['total_amount'] ?? 0);

$invDate = date('M d, Y', strtotime($orderInfo['order_date']));
$dueDate = date('M d, Y', strtotime($orderInfo['order_date'])); // Due immediately on delivery desk
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #INV-EL-<?= str_pad($orderId, 5, '0', STR_PAD_LEFT) ?> — Eggland Bangladesh</title>
    <style>
        /* Print & Page Setup */
        *, *::before, *::after {
            box-sizing: border-box;
            border-radius: 0px !important; /* Zero border radius globally */
        }
        @page {
            size: A4;
            margin: 10mm; /* Fixed margins for A4 standard printer */
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
            border: 1px solid #cbd5e1;
            text-align: right;
        }
        .btn-print {
            background-color: #107c41; /* Excel green primary */
            color: #ffffff;
            border: 1px solid #0a5c30;
            padding: 10px 24px;
            font-size: 10pt;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-print:hover {
            background-color: #0a5c30;
        }
        
        /* Main Page Wrapper for Screen preview */
        .page-wrapper {
            max-width: 800px;
            margin: 15px auto;
            background: #ffffff;
            padding: 5px 15px;
            border: 1px solid #cbd5e1;
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
            font-size: 16pt;
            font-weight: bold;
            color: #107c41; /* Excel Theme Green */
            margin: 0;
            letter-spacing: 0.5px;
        }
        .copy-badge {
            display: inline-block;
            padding: 3px 8px;
            font-weight: bold;
            font-size: 7.5pt;
            text-transform: uppercase;
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
            background-color: #107c41; /* Excel green */
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
            background-color: #107c41; /* Excel green */
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
            border-left: 3px solid #107c41; /* Excel green border */
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
            background-color: #107c41; /* Excel green */
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

        /* Hides elements on paper */
        @media print {
            body {
                background-color: #ffffff;
                margin: 0;
                padding: 0;
            }
            .action-bar {
                display: none !important;
            }
            .page-wrapper {
                max-width: 100%;
                margin: 0;
                padding: 0;
                border: none;
            }
            .divider span {
                background: #ffffff !important;
            }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <button class="btn-print" onclick="window.print()">🖨️ Print Eggland Invoice</button>
    </div>

    <div class="page-wrapper">
        
        <!-- COPY 1: OFFICE COPY -->
        <div class="invoice-container">
            <table class="header-table">
                <tr>
                    <td class="brand-section">
                        <h1 class="brand-title">EGGLAND BANGLADESH</h1>
                        <span class="copy-badge" style="background-color: #107c41; color: #ffffff; border: 1px solid #0a5c30;">OFFICE COPY</span>
                    </td>
                    <td class="company-details">
                        <strong>HQ:</strong> Holding 02, Charghat Bazar, Rajshahi, Bangladesh<br>
                        <strong>Licence:</strong> 00158-02 | <strong>ID:</strong> 06-021-00158-02<br>
                        <strong>Contact:</strong> +88 01300-888822 | info@egglandbangladesh.com
                    </td>
                </tr>
            </table>

            <table class="meta-table">
                <tr>
                    <td class="meta-label">INVOICE #</td>
                    <td>EG-INV-<?= str_pad($orderId, 5, '0', STR_PAD_LEFT) ?></td>
                    <td class="meta-label">INVOICE DATE</td>
                    <td><?= $invDate ?></td>
                    <td class="meta-label">DUE DATE</td>
                    <td><?= $dueDate ?></td>
                </tr>
            </table>

            <table class="client-table">
                <tr>
                    <th>BILL TO (Egg Wholesaler / Agent)</th>
                    <th>SHIP TO / OPERATIONS DESK</th>
                </tr>
                <tr>
                    <td>
                        <strong>Client/Store:</strong> <?= htmlspecialchars($agentInfo['name']) ?><br>
                        <strong>Proprietor:</strong> Representative Owner<br>
                        <strong>Address:</strong> <?= htmlspecialchars($agentInfo['address'] ?: 'Dhaka, Bangladesh') ?>
                    </td>
                    <td>
                        <strong>Delivery Location:</strong> Same as Billing Address<br>
                        <strong>Contact No:</strong> <?= htmlspecialchars($agentInfo['phone'] ?: 'N/A') ?><br>
                        <strong>Route/Area:</strong> Direct Wholesaler Channel
                    </td>
                </tr>
            </table>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-center" style="width: 14%;">Qty ordered</th>
                        <th class="text-right" style="width: 18%;">Unit Rate</th>
                        <th class="text-center" style="width: 10%;">VAT %</th>
                        <th class="text-right" style="width: 18%;">Total (Excl. VAT)</th>
                        <th class="text-right" style="width: 14%;">VAT Amt</th>
                        <th class="text-right" style="width: 18%;">Total (Incl. VAT)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $itemCount1 = 0;
                    foreach ($items as $item): 
                        $itemCount1++;
                        $rowTotal = floatval($item['subtotal']);
                        $rate = floatval($item['price']);
                        
                        // Detect unit
                        $unit = 'pcs';
                        if (strpos($item['product_name'], '(Tray') !== false) {
                            $unit = 'trays';
                        } elseif (strpos($item['product_name'], '(Pack') !== false) {
                            $unit = 'packs';
                        }
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                        </td>
                        <td class="text-center"><?= htmlspecialchars($item['quantity']) ?> <?= $unit ?></td>
                        <td class="text-right">৳ <?= number_format($rate, 2) ?></td>
                        <td class="text-center">0%</td>
                        <td class="text-right">৳ <?= number_format($rowTotal, 2) ?></td>
                        <td class="text-right">৳ 0.00</td>
                        <td class="text-right">৳ <?= number_format($rowTotal, 2) ?></td>
                    </tr>
                    <?php endforeach; 
                    // Render spacer rows to keep consistent layout
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
                            <strong>Terms:</strong> All Eggland Wholesale orders are finalized instantly on ledger sheets. Egg counts and breakages must be verified at the desk.
                        </div>
                        <div class="notes-box">
                            <strong>Payment:</strong> Recorded on Agent Ledger Account. Net cumulative outstanding balance recalculated in real-time.
                        </div>
                    </td>
                    <td class="summary-td">
                        <table class="summary-table">
                            <tr>
                                <td class="summary-label">Subtotal (Gross cost):</td>
                                <td class="text-right">৳ <?= number_format($grandTotal, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="summary-label">VAT / Tax Amount:</td>
                                <td class="text-right">৳ 0.00</td>
                            </tr>
                            <tr>
                                <td class="summary-label">Transportation/Loading:</td>
                                <td class="text-right">৳ 0.00</td>
                            </tr>
                            <tr class="total-row">
                                <td class="summary-label" style="color: #fff;">TOTAL BILLING BDT:</td>
                                <td class="text-right">৳ <?= number_format($grandTotal, 2) ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table class="signatures-table">
                <tr>
                    <td><div class="sig-line"></div>Authorized Officer</td>
                    <td><div class="sig-line"></div>Loading Officer</td>
                    <td><div class="sig-line"></div>Agent Wholesaler Signature</td>
                </tr>
            </table>

            <div class="footer-text">
                Eggland Bangladesh | Operations Desk: Holding 02, Charghat, Rajshahi | Licence No: 00158-02 | +88 01300-888822
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
                        <h1 class="brand-title">EGGLAND BANGLADESH</h1>
                        <span class="copy-badge" style="background-color: #ffffff; color: #107c41; border: 1px solid #107c41;">RETAILER RECEIVE COPY</span>
                    </td>
                    <td class="company-details">
                        <strong>HQ:</strong> Holding 02, Charghat Bazar, Rajshahi, Bangladesh<br>
                        <strong>Licence:</strong> 00158-02 | <strong>ID:</strong> 06-021-00158-02<br>
                        <strong>Contact:</strong> +88 01300-888822 | info@egglandbangladesh.com
                    </td>
                </tr>
            </table>

            <table class="meta-table">
                <tr>
                    <td class="meta-label">INVOICE #</td>
                    <td>EG-INV-<?= str_pad($orderId, 5, '0', STR_PAD_LEFT) ?></td>
                    <td class="meta-label">INVOICE DATE</td>
                    <td><?= $invDate ?></td>
                    <td class="meta-label">DUE DATE</td>
                    <td><?= $dueDate ?></td>
                </tr>
            </table>

            <table class="client-table">
                <tr>
                    <th>BILL TO (Egg Wholesaler / Agent)</th>
                    <th>SHIP TO / OPERATIONS DESK</th>
                </tr>
                <tr>
                    <td>
                        <strong>Client/Store:</strong> <?= htmlspecialchars($agentInfo['name']) ?><br>
                        <strong>Proprietor:</strong> Representative Owner<br>
                        <strong>Address:</strong> <?= htmlspecialchars($agentInfo['address'] ?: 'Dhaka, Bangladesh') ?>
                    </td>
                    <td>
                        <strong>Delivery Location:</strong> Same as Billing Address<br>
                        <strong>Contact No:</strong> <?= htmlspecialchars($agentInfo['phone'] ?: 'N/A') ?><br>
                        <strong>Route/Area:</strong> Direct Wholesaler Channel
                    </td>
                </tr>
            </table>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-center" style="width: 14%;">Qty ordered</th>
                        <th class="text-right" style="width: 18%;">Unit Rate</th>
                        <th class="text-center" style="width: 10%;">VAT %</th>
                        <th class="text-right" style="width: 18%;">Total (Excl. VAT)</th>
                        <th class="text-right" style="width: 14%;">VAT Amt</th>
                        <th class="text-right" style="width: 18%;">Total (Incl. VAT)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $itemCount2 = 0;
                    foreach ($items as $item): 
                        $itemCount2++;
                        $rowTotal = floatval($item['subtotal']);
                        $rate = floatval($item['price']);
                        
                        // Detect unit
                        $unit = 'pcs';
                        if (strpos($item['product_name'], '(Tray') !== false) {
                            $unit = 'trays';
                        } elseif (strpos($item['product_name'], '(Pack') !== false) {
                            $unit = 'packs';
                        }
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                        </td>
                        <td class="text-center"><?= htmlspecialchars($item['quantity']) ?> <?= $unit ?></td>
                        <td class="text-right">৳ <?= number_format($rate, 2) ?></td>
                        <td class="text-center">0%</td>
                        <td class="text-right">৳ <?= number_format($rowTotal, 2) ?></td>
                        <td class="text-right">৳ 0.00</td>
                        <td class="text-right">৳ <?= number_format($rowTotal, 2) ?></td>
                    </tr>
                    <?php endforeach; 
                    // Render spacer rows to keep consistent layout
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
                            <strong>Terms:</strong> All Eggland Wholesale orders are finalized instantly on ledger sheets. Egg counts and breakages must be verified at the desk.
                        </div>
                        <div class="notes-box">
                            <strong>Payment:</strong> Recorded on Agent Ledger Account. Net cumulative outstanding balance recalculated in real-time.
                        </div>
                    </td>
                    <td class="summary-td">
                        <table class="summary-table">
                            <tr>
                                <td class="summary-label">Subtotal (Gross cost):</td>
                                <td class="text-right">৳ <?= number_format($grandTotal, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="summary-label">VAT / Tax Amount:</td>
                                <td class="text-right">৳ 0.00</td>
                            </tr>
                            <tr>
                                <td class="summary-label">Transportation/Loading:</td>
                                <td class="text-right">৳ 0.00</td>
                            </tr>
                            <tr class="total-row">
                                <td class="summary-label" style="color: #fff;">TOTAL BILLING BDT:</td>
                                <td class="text-right">৳ <?= number_format($grandTotal, 2) ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table class="signatures-table">
                <tr>
                    <td><div class="sig-line"></div>Authorized Officer</td>
                    <td><div class="sig-line"></div>Loading Officer</td>
                    <td><div class="sig-line"></div>Authorized Signatory</td>
                </tr>
            </table>

            <div class="footer-text">
                Eggland Bangladesh | Operations Desk: Holding 02, Charghat, Rajshahi | Licence No: 00158-02 | +88 01300-888822
            </div>
        </div>
    </div>

</body>
</html>
