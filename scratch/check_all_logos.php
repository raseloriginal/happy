<?php
foreach(glob('c:/xampp/htdocs/happycrm2/assets/img/logo/*.png') as $f) {
    $sz = getimagesize($f);
    echo basename($f) . ": " . $sz[0] . "x" . $sz[1] . " (" . $sz['mime'] . ")\n";
}
