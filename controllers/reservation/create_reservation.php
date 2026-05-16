<?php
// controllers/reservation/create_reservation.php

session_start();
require_once '../../config/db_config.php';
require_once '../notifications/notification_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$student_id = (int)($_SESSION['student_id'] ?? 0);
$studentid  = trim($_SESSION['studentid'] ?? '');
$firstname  = trim($_SESSION['firstname'] ?? '');
$lastname   = trim($_SESSION['lastname'] ?? '');
$fullname   = trim($lastname . ', ' . $firstname);

$lab       = trim($_POST['lab'] ?? '');
$date      = trim($_POST['reservation_date'] ?? '');
$time      = trim($_POST['reservation_time'] ?? '');
$end_time  = trim($_POST['reservation_end_time'] ?? '');
$purpose   = trim($_POST['purpose'] ?? '');
$pc        = (int)($_POST['pc_number'] ?? 0);

$allowed_labs = ['Lab 524', 'Lab 526', 'Lab 528', 'Lab 530', 'Lab 542', 'Lab 544'];

if (!$student_id || $studentid === '') {
    echo json_encode(['success' => false, 'message' => 'Student session not found. Please log in again.']);
    exit;
}

if ($lab === '' || $date === '' || $time === '' || $end_time === '' || $purpose === '' || !$pc) {
    echo json_encode(['success' => false, 'message' => 'Please complete all reservation fields.']);
    exit;
}

if (!in_array($lab, $allowed_labs, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid lab selected.']);
    exit;
}

if ($pc < 1 || $pc > 56) {
    echo json_encode(['success' => false, 'message' => 'Invalid PC number.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
    exit;
}

if (strlen($time) === 5) {
    $time .= ':00';
}
if (strlen($end_time) === 5) {
    $end_time .= ':00';
}

if (strtotime($end_time) <= strtotime($time)) {
    echo json_encode(['success' => false, 'message' => 'End time must be later than start time.']);
    exit;
}

if ($date < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'Reservation date cannot be in the past.']);
    exit;
}

if ($date === date('Y-m-d') && strtotime($end_time) <= strtotime(date('H:i:s'))) {
    echo json_encode(['success' => false, 'message' => 'Reservation end time has already passed.']);
    exit;
}

// Check if student still has session credits
$stmt = $conn->prepare('SELECT session_credits FROM students WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student || (int)$student['session_credits'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'You have no remaining session credits.']);
    exit;
}

// Check if PC is unavailable
$stmt = $conn->prepare("SELECT id FROM lab_pc_status WHERE lab = ? AND pc_number = ? AND status = 'unavailable' LIMIT 1");
$stmt->bind_param('si', $lab, $pc);
$stmt->execute();
$unavailable = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($unavailable) {
    echo json_encode(['success' => false, 'message' => 'This PC is currently unavailable.']);
    exit;
}

// Check if PC has an overlapping pending/approved reservation.
$stmt = $conn->prepare("
    SELECT id FROM lab_reservations
    WHERE lab = ?
      AND reservation_date = ?
      AND pc_number = ?
      AND status IN ('pending', 'approved')
      AND reservation_time < ?
      AND COALESCE(reservation_end_time, ADDTIME(reservation_time, '01:00:00')) > ?
    LIMIT 1
");
$stmt->bind_param('ssiss', $lab, $date, $pc, $end_time, $time);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    echo json_encode(['success' => false, 'message' => 'This PC is already reserved during the selected time range.']);
    exit;
}

// Prevent student from making two active reservations with overlapping time ranges.
$stmt = $conn->prepare("
    SELECT id FROM lab_reservations
    WHERE student_id = ?
      AND reservation_date = ?
      AND status IN ('pending', 'approved')
      AND reservation_time < ?
      AND COALESCE(reservation_end_time, ADDTIME(reservation_time, '01:00:00')) > ?
    LIMIT 1
");
$stmt->bind_param('isss', $student_id, $date, $end_time, $time);
$stmt->execute();
$student_existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($student_existing) {
    echo json_encode(['success' => false, 'message' => 'You already have a reservation that overlaps this time range.']);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO lab_reservations
    (student_id, studentid, fullname, purpose, lab, pc_number, reservation_date, reservation_time, reservation_end_time, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
");
$stmt->bind_param('issssisss', $student_id, $studentid, $fullname, $purpose, $lab, $pc, $date, $time, $end_time);

if ($stmt->execute()) {
    $dateLabel = date('M d, Y', strtotime($date));
    $timeLabel = date('h:i A', strtotime($time));

    createStudentNotification(
        $conn,
        $student_id,
        'reservation_submitted',
        'Reservation Submitted',
        'Your reservation request for ' . $lab . ' PC ' . $pc . ' on ' . $dateLabel . ' at ' . $timeLabel . ' was submitted and is waiting for admin approval.'
    );

    echo json_encode(['success' => true, 'message' => 'Reservation submitted. Please wait for admin approval.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save reservation: ' . $stmt->error]);
}

$stmt->close();
exit;
