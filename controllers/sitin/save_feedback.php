<?php
// controllers/sitin/save_feedback.php

session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');

if (empty($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$student_id    = (int)($_SESSION['student_id'] ?? 0);
$sitin_id      = (int)($_POST['sitin_id'] ?? 0);
$issue_type    = trim($_POST['issue_type'] ?? '');
$feedback_text = trim($_POST['feedback_text'] ?? '');

if ($student_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Student session not found. Please log in again.']);
    exit;
}

if ($sitin_id <= 0 || $feedback_text === '') {
    echo json_encode(['success' => false, 'message' => 'Missing data.']);
    exit;
}

/*
  Security fix:
  Make sure the sit-in record belongs to the logged-in student.
  This prevents a student from submitting feedback for another student's sit-in.
*/
$verify = $conn->prepare("
    SELECT id
    FROM sitin_records
    WHERE id = ?
      AND student_id = ?
    LIMIT 1
");
$verify->bind_param('ii', $sitin_id, $student_id);
$verify->execute();
$ownRecord = $verify->get_result()->fetch_assoc();
$verify->close();

if (!$ownRecord) {
    echo json_encode(['success' => false, 'message' => 'Invalid sit-in record.']);
    exit;
}

$check = $conn->prepare('SELECT id FROM feedback WHERE sitin_id = ? LIMIT 1');
$check->bind_param('i', $sitin_id);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();

if ($existing) {
    $stmt = $conn->prepare("
        UPDATE feedback
        SET issue_type = ?, feedback_text = ?, created_at = NOW()
        WHERE sitin_id = ?
          AND student_id = ?
    ");
    $stmt->bind_param('ssii', $issue_type, $feedback_text, $sitin_id, $student_id);
} else {
    $stmt = $conn->prepare("
        INSERT INTO feedback (sitin_id, student_id, issue_type, feedback_text)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param('iiss', $sitin_id, $student_id, $issue_type, $feedback_text);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Feedback saved successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save feedback.']);
}

$stmt->close();
exit;
