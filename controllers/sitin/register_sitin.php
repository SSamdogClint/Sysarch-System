<?php
// controllers/sitin/register_sitin.php

session_start();
require_once '../../config/db_config.php';
require_once '../notifications/notification_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$studentid = trim($_POST['studentid'] ?? '');
$purpose   = trim($_POST['purpose'] ?? '');
$lab       = trim($_POST['lab'] ?? '');
$pc_number = trim($_POST['pc_number'] ?? '');

if ($studentid === '' || $purpose === '' || $lab === '') {
    echo json_encode(['success' => false, 'message' => 'Student ID, purpose, and lab are required.']);
    exit;
}

$pc_number_value = null;
if ($pc_number !== '') {
    $pc_number_value = (int)$pc_number;
    if ($pc_number_value <= 0) {
        echo json_encode(['success' => false, 'message' => 'PC number must be a valid number.']);
        exit;
    }
}

function sitinHasColumn(mysqli $conn, string $column): bool {
    $sql = "
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'sitin_records'
          AND COLUMN_NAME = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $column);
    $stmt->execute();

    $count = 0;
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return (int)$count > 0;
}

$stmt = $conn->prepare(
    'SELECT id, studentid, firstname, lastname, session_credits
     FROM students
     WHERE studentid = ?
     LIMIT 1'
);
$stmt->bind_param('s', $studentid);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found.']);
    exit;
}

if ((int)$student['session_credits'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Student has no remaining session credits.']);
    exit;
}

$checkActive = $conn->prepare("
    SELECT id
    FROM sitin_records
    WHERE student_id = ?
      AND status = 'active'
    LIMIT 1
");
$checkActive->bind_param('i', $student['id']);
$checkActive->execute();
$activeSitin = $checkActive->get_result()->fetch_assoc();
$checkActive->close();

if ($activeSitin) {
    echo json_encode([
        'success' => false,
        'message' => 'This student already has an active sit-in session. Please deactivate the current session first.'
    ]);
    exit;
}

$fullname = trim($student['lastname'] . ', ' . $student['firstname']);
$hasPcNumberColumn = sitinHasColumn($conn, 'pc_number');

if ($hasPcNumberColumn) {
    $stmt = $conn->prepare(
        'INSERT INTO sitin_records (student_id, studentid, fullname, purpose, lab, pc_number, session_at_sitin)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'issssii',
        $student['id'],
        $student['studentid'],
        $fullname,
        $purpose,
        $lab,
        $pc_number_value,
        $student['session_credits']
    );
} else {
    $stmt = $conn->prepare(
        'INSERT INTO sitin_records (student_id, studentid, fullname, purpose, lab, session_at_sitin)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'issssi',
        $student['id'],
        $student['studentid'],
        $fullname,
        $purpose,
        $lab,
        $student['session_credits']
    );
}

if ($stmt->execute()) {
    $pcText = $pc_number_value ? ' using PC ' . $pc_number_value : '';
    createStudentNotification(
        $conn,
        (int)$student['id'],
        'sitin_registered',
        'Sit-in Session Registered',
        'Your sit-in session for ' . $purpose . ' in ' . $lab . $pcText . ' is now active.'
    );

    echo json_encode(['success' => true, 'message' => 'Sit-in registered successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to register sit-in.']);
}

$stmt->close();
exit;
