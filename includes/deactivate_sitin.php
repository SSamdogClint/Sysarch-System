<?php
// includes/deactivate_sitin.php
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

// Get the sit-in record to find student_id
$stmt = $conn->prepare("SELECT student_id FROM sitin_records WHERE id = ? AND status = 'active' LIMIT 1");
$stmt->bind_param('i', $record_id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record) {
    echo json_encode(['success' => false, 'message' => 'Record not found or already done.']);
    exit;
}

// Set record to done
$stmt = $conn->prepare("UPDATE sitin_records SET status = 'done' WHERE id = ?");
$stmt->bind_param('i', $record_id);
$stmt->execute();
$stmt->close();

// Deduct 1 session credit NOW (when session ends)
$stmt = $conn->prepare('UPDATE students SET session_credits = session_credits - 1 WHERE id = ?');
$stmt->bind_param('i', $record['student_id']);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);