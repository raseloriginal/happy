<?php
// scratch/generate_pwa_icons.php

$logoPath = 'c:/xampp/htdocs/happycrm2/assets/img/logo/logo-icon-black.png';
$output512 = 'c:/xampp/htdocs/happycrm2/assets/img/logo/pwa-icon-512.png';
$output192 = 'c:/xampp/htdocs/happycrm2/assets/img/logo/pwa-icon-192.png';

if (!file_exists($logoPath)) {
    die("Logo not found at $logoPath\n");
}

// 1. Create a 512x512 base canvas (with white background)
$canvasSize = 512;
$canvas = imagecreatetruecolor($canvasSize, $canvasSize);

// Allocate colors
$white = imagecolorallocate($canvas, 255, 255, 255);
$textColor = imagecolorallocate($canvas, 15, 23, 42); // slate-900: #0f172a (dark premium slate)

// Fill background with white
imagefill($canvas, 0, 0, $white);

// 2. Load the black logo icon (262x350)
$logo = imagecreatefrompng($logoPath);
$logoW = imagesx($logo);
$logoH = imagesy($logo);

// Calculate new logo size to fit nicely in the upper half
// Let's make the logo height 220px (width: 220 * 262/350 = 165px)
$newLogoH = 220;
$newLogoW = round($newLogoH * ($logoW / $logoH));

// Center the logo horizontally
$logoX = ($canvasSize - $newLogoW) / 2;
$logoY = 100; // y-coordinate for logo top

// Copy and resample logo onto canvas
imagecopyresampled(
    $canvas,
    $logo,
    $logoX,
    $logoY,
    0,
    0,
    $newLogoW,
    $newLogoH,
    $logoW,
    $logoH
);

// 3. Draw the text "DSR PANEL" using imagettftext
$fontPath = 'C:\\Windows\\Fonts\\seguib.ttf'; // Segoe UI Bold
if (!file_exists($fontPath)) {
    $fontPath = 'C:\\Windows\\Fonts\\arialbd.ttf'; // Fallback to Arial Bold
}

if (file_exists($fontPath)) {
    $fontSize = 32; // font size in points
    $text = "DSR PANEL";
    
    // Get text bounding box to center it
    $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
    $textW = $bbox[2] - $bbox[0];
    
    $textX = ($canvasSize - $textW) / 2;
    $textY = 390; // y-coordinate for text baseline
    
    imagettftext($canvas, $fontSize, 0, $textX, $textY, $textColor, $fontPath, $text);
    echo "Text drawn using TTF font: $fontPath\n";
} else {
    // Fallback if no TTF font is found
    $fontSize = 5;
    $text = "DSR PANEL";
    $textW = imagefontwidth($fontSize) * strlen($text);
    $textX = ($canvasSize - $textW) / 2;
    $textY = 370;
    imagestring($canvas, $fontSize, $textX, $textY, $text, $textColor);
    echo "Text drawn using GD built-in font (TTF not found)\n";
}

// 4. Save the 512x512 icon
imagepng($canvas, $output512);
echo "Saved 512x512 PWA icon to $output512\n";

// 5. Create and save the 192x192 icon
$icon192 = imagecreatetruecolor(192, 192);
imagecopyresampled(
    $icon192,
    $canvas,
    0,
    0,
    0,
    0,
    192,
    192,
    $canvasSize,
    $canvasSize
);
imagepng($icon192, $output192);
echo "Saved 192x192 PWA icon to $output192\n";

// Clean up
imagedestroy($canvas);
imagedestroy($logo);
imagedestroy($icon192);
?>
