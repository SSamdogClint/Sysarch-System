<?php
session_start();
require_once '../config/db_config.php';
header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$studentid = trim($_POST['studentid'] ?? '');
$purpose   = trim($_POST['purpose']   ?? '');
$lab       = trim($_POST['lab']       ?? '');

if (empty($studentid) || empty($purpose) || empty($lab)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Get student
$stmt = $conn->prepare('SELECT id, firstname, lastname, session_credits FROM students WHERE studentid = ? LIMIT 1');
$stmt->bind_param('s', $studentid);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found.']);
    exit;
}

if ($student['session_credits'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Student has no remaining session credits.']);
    exit;
}

$fullname = $student['lastname'] . ', ' . $student['firstname'];

// Insert sit-in record
$stmt = $conn->prepare('INSERT INTO sitin_records (student_id, studentid, fullname, purpose, lab) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('issss', $student['id'], $studentid, $fullname, $purpose, $lab);
$stmt->execute();
$stmt->close();

// Deduct 1 session credit
$stmt = $conn->prepare('UPDATE students SET session_credits = session_credits - 1 WHERE id = ?');
$stmt->bind_param('i', $student['id']);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);