<?php
// includes/check_session.php
session_start();
require_once '../config/db_config.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

echo json_encode([
    'logged_in' => !empty($_SESSION['logged_in'])
]);