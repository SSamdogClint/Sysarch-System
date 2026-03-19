<?php
session_start();
session_unset();
session_destroy();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

// Check type before session is destroyed
$type = $_GET['type'] ?? 'student';

if ($type === 'admin') {
    header('Location: home.php');
} else {
    header('Location: home.php');
}
exit;