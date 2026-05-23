<?php
$im = imagecreatefrompng('c:/xampp/htdocs/happycrm2/assets/img/logo/logo.png');
$w = imagesx($im);
$h = imagesy($im);
$hasColor = false;
$colorSamples = [];
for ($x = 0; $x < $w; $x++) {
    for ($y = 0; $y < $h; $y++) {
        $rgb = imagecolorat($im, $x, $y);
        $a = ($rgb >> 24) & 0x7F;
        if ($a < 100) {
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $diff = max(abs($r - $g), abs($g - $b), abs($b - $r));
            if ($diff > 15) {
                $hasColor = true;
                $colorSamples[sprintf("#%02x%02x%02x", $r, $g, $b)] = ($colorSamples[sprintf("#%02x%02x%02x", $r, $g, $b)] ?? 0) + 1;
            }
        }
    }
}
if ($hasColor) {
    echo "The logo HAS color. Top non-gray colors:\n";
    arsort($colorSamples);
    print_r(array_slice($colorSamples, 0, 10));
} else {
    echo "The logo is completely grayscale (black, white, gray).\n";
}
