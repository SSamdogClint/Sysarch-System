<?php
// includes/search_student.php
session_start();
require_once '../config/db_config.php';
header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['found' => false]);
    exit;
}

$studentid = trim($_GET['studentid'] ?? '');

if (empty($studentid)) {
    echo json_encode(['found' => false]);
    exit;
}

$stmt = $conn->prepare(
    'SELECT studentid, firstname, lastname, middlename, course, yearlvl, email, addrs, session_credits
     FROM students WHERE studentid = ? LIMIT 1'
);
$stmt->bind_param('s', $studentid);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    echo json_encode(['found' => false]);
    exit;
}

echo json_encode(['found' => true, 'student' => $student]);