<?php
// controllers/student/delete_student.php

session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$student_id = (int)($_POST['student_id'] ?? 0);

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid student.']);
    exit;
}

// Delete sit-in records first
$stmt = $conn->prepare('DELETE FROM sitin_records WHERE student_id = ?');
$stmt->bind_param('i', $student_id);
$stmt->execute();
$stmt->close();

// Delete student
$stmt = $conn->prepare('DELETE FROM students WHERE id = ?');
$stmt->bind_param('i', $student_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete student.']);
}

$stmt->close();
exit;