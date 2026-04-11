<?php
// includes/delete_sitin.php
session_start();
require_once '../config/db_config.php';
header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$record_id = (int)($_POST['record_id'] ?? 0);

if (!$record_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid record.']);
    exit;
}

$stmt = $conn->prepare('DELETE FROM sitin_records WHERE id = ?');
$stmt->bind_param('i', $record_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete record.']);
}
$stmt->close();