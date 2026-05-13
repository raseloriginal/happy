<?php
// logout.php
require_once __DIR__ . '/config/session.php';
session_destroy();
header('Location: <?= rootPath() ?>/index.php');
exit;
