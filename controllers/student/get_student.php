<?php
// controllers/student/get_student.php

session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');

// Check admin session
if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get ID
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

// Query student
$stmt = $conn->prepare(
    'SELECT id, studentid, lastname, firstname, middlename, course, yearlvl, email, addrs
     FROM students WHERE id = ? LIMIT 1'
);

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();
$student = $result->fetch_assoc();

$stmt->close();

// Check result
if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Return success
echo json_encode([
    'success' => true,
    'student' => $student
]);
exit;