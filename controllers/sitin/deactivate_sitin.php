<?php
session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized.'
    ]);
    exit;
}

$sitin_id = (int)($_POST['sitin_id'] ?? $_POST['id'] ?? $_POST['record_id'] ?? 0);

if ($sitin_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid sit-in record.'
    ]);
    exit;
}

/*
  Get the active sit-in first.
  This makes sure we only deactivate active records.
*/
$stmt = $conn->prepare("
    SELECT id, student_id
    FROM sitin_records
    WHERE id = ?
      AND status = 'active'
    LIMIT 1
");
$stmt->bind_param('i', $sitin_id);
$stmt->execute();
$result = $stmt->get_result();
$sitin = $result->fetch_assoc();
$stmt->close();

if (!$sitin) {
    echo json_encode([
        'success' => false,
        'message' => 'Active sit-in record not found.'
    ]);
    exit;
}

/*
  Deactivate sit-in and generate time-out.
*/
$stmt = $conn->prepare("
    UPDATE sitin_records
    SET status = 'done',
        logout_time = NOW()
    WHERE id = ?
");
$stmt->bind_param('i', $sitin_id);

if (!$stmt->execute()) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to deactivate sit-in.'
    ]);
    exit;
}
$stmt->close();

/*
  Deduct 1 session credit from the student.
*/
$student_id = (int)$sitin['student_id'];

$stmt = $conn->prepare("
    UPDATE students
    SET session_credits = GREATEST(session_credits - 1, 0)
    WHERE id = ?
");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$stmt->close();

echo json_encode([
    'success' => true,
    'message' => 'Sit-in deactivated successfully. Time-out has been recorded.'
]);
exit;