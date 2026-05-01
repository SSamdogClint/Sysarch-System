<?php
// controllers/reservation/admin_reservation_controller.php

session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$action = trim($_POST['action'] ?? '');
$id     = (int)($_POST['reservation_id'] ?? 0);

if ($action === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Manage PC availability from the layout viewer.
if ($action === 'mark_unavailable' || $action === 'mark_available') {
    $lab = trim($_POST['lab'] ?? '');
    $pc  = (int)($_POST['pc_number'] ?? 0);

    if ($lab === '' || $pc < 1 || $pc > 56) {
        echo json_encode(['success' => false, 'message' => 'Invalid lab or PC number.']);
        exit;
    }

    if ($action === 'mark_unavailable') {
        $stmt = $conn->prepare("
            INSERT INTO lab_pc_status (lab, pc_number, status, note)
            VALUES (?, ?, 'unavailable', 'Marked unavailable by admin')
            ON DUPLICATE KEY UPDATE status = 'unavailable', note = VALUES(note)
        ");
        $stmt->bind_param('si', $lab, $pc);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'PC marked unavailable.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark PC unavailable.']);
        }
        $stmt->close();
        exit;
    }

    $stmt = $conn->prepare('DELETE FROM lab_pc_status WHERE lab = ? AND pc_number = ?');
    $stmt->bind_param('si', $lab, $pc);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'PC marked available.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark PC available.']);
    }
    $stmt->close();
    exit;
}

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation.']);
    exit;
}

if ($action === 'delete') {
    $stmt = $conn->prepare('DELETE FROM lab_reservations WHERE id = ?');
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reservation deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete reservation.']);
    }

    $stmt->close();
    exit;
}

$status_map = [
    'approve' => 'approved',
    'reject'  => 'rejected',
    'cancel'  => 'cancelled',
    'done'    => 'done'
];

if (!isset($status_map[$action])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

$new_status = $status_map[$action];

// If approving, make sure another approved reservation does not already own an overlapping PC slot.
if ($action === 'approve') {
    $stmt = $conn->prepare("
        SELECT lab, reservation_date, reservation_time,
               COALESCE(reservation_end_time, ADDTIME(reservation_time, '01:00:00')) AS reservation_end_time,
               pc_number
        FROM lab_reservations
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$current) {
        echo json_encode(['success' => false, 'message' => 'Reservation not found.']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT id FROM lab_reservations
        WHERE id <> ?
          AND lab = ?
          AND reservation_date = ?
          AND pc_number = ?
          AND status = 'approved'
          AND reservation_time < ?
          AND COALESCE(reservation_end_time, ADDTIME(reservation_time, '01:00:00')) > ?
        LIMIT 1
    ");
    $stmt->bind_param(
        'ississ',
        $id,
        $current['lab'],
        $current['reservation_date'],
        $current['pc_number'],
        $current['reservation_end_time'],
        $current['reservation_time']
    );
    $stmt->execute();
    $conflict = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($conflict) {
        echo json_encode(['success' => false, 'message' => 'This PC already has an approved reservation during that time range.']);
        exit;
    }
}

$stmt = $conn->prepare('UPDATE lab_reservations SET status = ? WHERE id = ?');
$stmt->bind_param('si', $new_status, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Reservation updated.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update reservation.']);
}

$stmt->close();
exit;
