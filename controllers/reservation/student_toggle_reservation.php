<?php
// controllers/reservation/student_toggle_reservation.php

session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');

if (empty($_SESSION['logged_in'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit;
}

$student_id = (int)($_SESSION['student_id'] ?? 0);
$reservation_id = (int)($_POST['reservation_id'] ?? 0);
$action = strtolower(trim($_POST['action'] ?? ''));

if ($student_id <= 0 || $reservation_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid reservation request.'
    ]);
    exit;
}

if (!in_array($action, ['enable', 'disable'], true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid reservation action.'
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, student_id, lab, pc_number, reservation_date, reservation_time,
           COALESCE(reservation_end_time, ADDTIME(reservation_time, '01:00:00')) AS reservation_end_time,
           status
    FROM lab_reservations
    WHERE id = ?
      AND student_id = ?
    LIMIT 1
");

$stmt->bind_param('ii', $reservation_id, $student_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reservation) {
    echo json_encode([
        'success' => false,
        'message' => 'Reservation not found.'
    ]);
    exit;
}

$status = strtolower($reservation['status']);
$today = date('Y-m-d');

if ($reservation['reservation_date'] < $today) {
    echo json_encode([
        'success' => false,
        'message' => 'Past reservations can no longer be changed.'
    ]);
    exit;
}

function addStudentNotification(mysqli $conn, int $student_id, string $type, string $title, string $message): void
{
    $tableCheck = $conn->query("SHOW TABLES LIKE 'student_notifications'");

    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return;
    }

    $stmt = $conn->prepare("
        INSERT INTO student_notifications (student_id, type, title, message)
        VALUES (?, ?, ?, ?)
    ");

    if (!$stmt) {
        return;
    }

    $stmt->bind_param('isss', $student_id, $type, $title, $message);
    $stmt->execute();
    $stmt->close();
}

if ($action === 'disable') {
    if (!in_array($status, ['pending', 'approved'], true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Only pending or approved reservations can be disabled.'
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE lab_reservations
        SET status = 'cancelled',
            updated_at = NOW()
        WHERE id = ?
          AND student_id = ?
    ");

    $stmt->bind_param('ii', $reservation_id, $student_id);
    $stmt->execute();
    $stmt->close();

    addStudentNotification(
        $conn,
        $student_id,
        'reservation',
        'Reservation Disabled',
        'Your reservation for ' . $reservation['lab'] . ' PC ' . $reservation['pc_number'] . ' has been disabled/cancelled.'
    );

    echo json_encode([
        'success' => true,
        'message' => 'Reservation disabled successfully.'
    ]);
    exit;
}

if ($action === 'enable') {
    if ($status !== 'cancelled') {
        echo json_encode([
            'success' => false,
            'message' => 'Only cancelled reservations can be enabled again.'
        ]);
        exit;
    }

    $lab = $reservation['lab'];
    $pc_number = (int)$reservation['pc_number'];
    $reservation_date = $reservation['reservation_date'];
    $reservation_time = $reservation['reservation_time'];
    $reservation_end_time = $reservation['reservation_end_time'];

    $stmt = $conn->prepare("
        SELECT id
        FROM lab_computers
        WHERE lab = ?
          AND pc_number = ?
          AND status = 'unavailable'
        LIMIT 1
    ");

    $stmt->bind_param('si', $lab, $pc_number);
    $stmt->execute();
    $unavailablePc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($unavailablePc) {
        echo json_encode([
            'success' => false,
            'message' => 'This PC is currently unavailable and cannot be enabled.'
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT id
        FROM lab_reservations
        WHERE id <> ?
          AND lab = ?
          AND pc_number = ?
          AND reservation_date = ?
          AND status IN ('pending', 'approved')
          AND reservation_time < ?
          AND reservation_end_time > ?
        LIMIT 1
    ");

    $stmt->bind_param(
        'isisss',
        $reservation_id,
        $lab,
        $pc_number,
        $reservation_date,
        $reservation_end_time,
        $reservation_time
    );

    $stmt->execute();
    $pcConflict = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($pcConflict) {
        echo json_encode([
            'success' => false,
            'message' => 'This PC and time slot is already taken.'
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT id
        FROM lab_reservations
        WHERE id <> ?
          AND student_id = ?
          AND reservation_date = ?
          AND status IN ('pending', 'approved')
          AND reservation_time < ?
          AND reservation_end_time > ?
        LIMIT 1
    ");

    $stmt->bind_param(
        'iisss',
        $reservation_id,
        $student_id,
        $reservation_date,
        $reservation_end_time,
        $reservation_time
    );

    $stmt->execute();
    $studentConflict = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($studentConflict) {
        echo json_encode([
            'success' => false,
            'message' => 'You already have another reservation during this time.'
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE lab_reservations
        SET status = 'pending',
            updated_at = NOW()
        WHERE id = ?
          AND student_id = ?
    ");

    $stmt->bind_param('ii', $reservation_id, $student_id);
    $stmt->execute();
    $stmt->close();

    addStudentNotification(
        $conn,
        $student_id,
        'reservation',
        'Reservation Enabled',
        'Your reservation for ' . $lab . ' PC ' . $pc_number . ' has been enabled again and is now pending admin approval.'
    );

    echo json_encode([
        'success' => true,
        'message' => 'Reservation enabled successfully. It is now pending admin approval.'
    ]);
    exit;
}