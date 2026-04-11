// includes/reset_sessions.php
<?php
session_start();
require_once '../config/db_config.php';
header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

// ONLY reset session credits — never touch sitin_records
$conn->query('UPDATE students SET session_credits = 30');

echo json_encode(['success' => true]);