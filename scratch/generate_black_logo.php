<?php
function convertToBlack($sourcePath, $outputPath) {
    if (!file_exists($sourcePath)) {
        die("Source file $sourcePath not found.\n");
    }

    $im = imagecreatefrompng($sourcePath);
    if (!$im) {
        die("Failed to load PNG from $sourcePath.\n");
    }

    $width = imagesx($im);
    $height = imagesy($im);

    $out = imagecreatetruecolor($width, $height);
    imagealphablending($out, false);
    imagesavealpha($out, true);

    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $rgba = imagecolorat($im, $x, $y);
            $alpha = ($rgba >> 24) & 0x7F; // 0 = opaque, 127 = transparent
            
            // Keep the exact same alpha/transparency, but change color channels to black (0, 0, 0)
            $blackColor = imagecolorallocatealpha($out, 0, 0, 0, $alpha);
            imagesetpixel($out, $x, $y, $blackColor);
        }
    }

    imagepng($out, $outputPath);
    imagedestroy($im);
    imagedestroy($out);
    echo "Converted $sourcePath to black and saved to $outputPath\n";
}

convertToBlack(
    'c:/xampp/htdocs/happycrm2/assets/img/logo/logo.png',
    'c:/xampp/htdocs/happycrm2/assets/img/logo/logo-black.png'
);

convertToBlack(
    'c:/xampp/htdocs/happycrm2/assets/img/logo/logo-icon.png',
    'c:/xampp/htdocs/happycrm2/assets/img/logo/logo-icon-black.png'
);
