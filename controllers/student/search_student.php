<?php
// controllers/student/search_student.php

session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');

// Check admin session
if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['found' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get student ID
$studentid = trim($_GET['studentid'] ?? '');

if (empty($studentid)) {
    echo json_encode(['found' => false, 'message' => 'No student ID provided']);
    exit;
}

// Query student
$stmt = $conn->prepare(
    'SELECT studentid, firstname, lastname, middlename, course, yearlvl, email, addrs, session_credits
     FROM students WHERE studentid = ? LIMIT 1'
);

$stmt->bind_param('s', $studentid);
$stmt->execute();

$result = $stmt->get_result();
$student = $result->fetch_assoc();

$stmt->close();

// Check result
if (!$student) {
    echo json_encode(['found' => false, 'message' => 'Student not found']);
    exit;
}

// Return success
echo json_encode([
    'found' => true,
    'student' => $student
]);
exit;