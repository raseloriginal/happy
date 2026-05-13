<?php
// includes/header.php
$pageTitle = $pageTitle ?? 'Happy Bangladesh ERP';
$basePath  = '/happycrm2';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?> — Happy Bangladesh</title>
  <meta name="description" content="Happy Bangladesh ERP — Warehouse, Lot, Order & Delivery Management" />

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'sans-serif'] },
          colors: {
            primary: { DEFAULT: '#4F46E5', dark: '#4338CA', light: '#EEF2FF' }
          }
        }
      }
    }
  </script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- html5-qrcode -->
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

  <!-- QR Code Generator -->
  <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>

  <!-- Custom CSS -->
  <link rel="stylesheet" href="/happycrm2/assets/css/app.css" />
  <style>body { font-family: 'Inter', sans-serif; } * { box-sizing: border-box; }</style>
</head>
<body class="bg-gray-50">
