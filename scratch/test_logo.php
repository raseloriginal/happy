<?php
$logoPath = 'c:/xampp/htdocs/happycrm2/assets/img/logo/logo.png';
if (file_exists($logoPath)) {
    $info = getimagesize($logoPath);
    echo "Dimensions: " . $info[0] . "x" . $info[1] . "\n";
    echo "Mime: " . $info['mime'] . "\n";
    echo "GD library loaded: " . (extension_loaded('gd') ? 'yes' : 'no') . "\n";
} else {
    echo "Logo file not found.\n";
}
