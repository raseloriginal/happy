<?php
$logoPath = 'c:/xampp/htdocs/happycrm2/assets/img/logo/logo.png';
$outputPath = 'c:/xampp/htdocs/happycrm2/assets/img/logo/logo-icon.png';

if (!file_exists($logoPath)) {
    die("Logo not found.\n");
}

$im = imagecreatefrompng($logoPath);
if (!$im) {
    die("Failed to load PNG.\n");
}

$width = imagesx($im);
$height = imagesy($im);

// Find column opacity
$columnHasPixels = [];
for ($x = 0; $x < $width; $x++) {
    $hasPixel = false;
    for ($y = 0; $y < $height; $y++) {
        $rgba = imagecolorat($im, $x, $y);
        $alpha = ($rgba >> 24) & 0x7F; // 0 = opaque, 127 = transparent
        if ($alpha < 120) { // non-transparent pixel found
            $hasPixel = true;
            break;
        }
    }
    $columnHasPixels[$x] = $hasPixel;
}

// Find the "H" boundaries
// It starts when we find the first non-transparent column
$hStart = 0;
for ($x = 0; $x < $width; $x++) {
    if ($columnHasPixels[$x]) {
        $hStart = $x;
        break;
    }
}

// It ends when we find a transparent gap after the start
$hEnd = $width;
$foundGap = false;
$gapStart = 0;
for ($x = $hStart + 10; $x < $width; $x++) {
    if (!$columnHasPixels[$x]) {
        if (!$foundGap) {
            $gapStart = $x;
            $foundGap = true;
        }
    } else {
        if ($foundGap) {
            // We found the next letter! So the gap ends here.
            $hEnd = $x;
            break;
        }
    }
}

// If we found a gap, crop up to the gap start plus some padding
$cropWidth = $foundGap ? ($gapStart + 15) : 350;
echo "H starts at: $hStart, Gap starts at: $gapStart, Crop Width: $cropWidth\n";

// Crop to a square-ish aspect ratio based on the height
$cropHeight = $height;
$cropX = max(0, $hStart - 10);
$cropW = $cropWidth - $cropX;

$cropped = imagecreatetruecolor($cropW, $cropHeight);
imagealphablending($cropped, false);
imagesavealpha($cropped, true);

// Fill with transparent background
$transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
imagefill($cropped, 0, 0, $transparent);

imagecopyresampled($cropped, $im, 0, 0, $cropX, 0, $cropW, $cropHeight, $cropW, $cropHeight);

imagepng($cropped, $outputPath);
imagedestroy($im);
imagedestroy($cropped);

echo "Icon cropped successfully and saved to $outputPath\n";
