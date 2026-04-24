<?php
// controllers/sitin/save_feedback.php

session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');

if (empty($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$student_id     = (int)($_SESSION['student_id'] ?? 0);
$sitin_id       = (int)($_POST['sitin_id'] ?? 0);
$issue_type     = trim($_POST['issue_type'] ?? '');
$feedback_text  = trim($_POST['feedback_text'] ?? '');

if (!$sitin_id || !$feedback_text) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$check = $conn->prepare("SELECT id FROM feedback WHERE sitin_id = ?");
$check->bind_param('i', $sitin_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $stmt = $conn->prepare("
        UPDATE feedback
        SET issue_type = ?, feedback_text = ?, created_at = NOW()
        WHERE sitin_id = ?
    ");
    $stmt->bind_param('ssi', $issue_type, $feedback_text, $sitin_id);
} else {
    $stmt = $conn->prepare("
        INSERT INTO feedback (sitin_id, student_id, issue_type, feedback_text)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param('iiss', $sitin_id, $student_id, $issue_type, $feedback_text);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save feedback']);
}

$check->close();
$stmt->close();
exit;