<?php
// controllers/auth/check_session.php

session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Optional: support type=admin or type=student
$type = $_GET['type'] ?? '';

if ($type === 'admin') {
    $logged_in = !empty($_SESSION['admin_logged_in']);
} else {
    $logged_in = !empty($_SESSION['logged_in']);
}

echo json_encode([
    'logged_in' => $logged_in
]);
exit;