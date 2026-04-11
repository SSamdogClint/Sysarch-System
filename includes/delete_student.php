// includes/delete_student.php
<?php
session_start();
require_once '../config/db_config.php';
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

// Delete sit-in records first (foreign key)
$stmt = $conn->prepare('DELETE FROM sitin_records WHERE student_id = ?');
$stmt->bind_param('i', $student_id);
$stmt->execute();
$stmt->close();

// Delete student
$stmt = $conn->prepare('DELETE FROM students WHERE id = ?');
$stmt->bind_param('i', $student_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);