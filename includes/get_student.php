<?php
session_start();
require_once '../config/db_config.php';
header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false]);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare(
    'SELECT id, studentid, lastname, firstname, middlename, course, yearlvl, email, addrs
     FROM students WHERE id = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    echo json_encode(['success' => false]);
    exit;
}

echo json_encode(['success' => true, 'student' => $student]);