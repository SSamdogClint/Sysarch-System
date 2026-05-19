<?php
// controllers/reservation/admin_reservation_controller.php

session_start();
require_once '../../config/db_config.php';
require_once 'reservation_helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

/* Auto-cancel late approved reservations first */
autoCancelLateReservations($conn);

$action = trim($_POST['action'] ?? '');
$id = (int)($_POST['reservation_id'] ?? 0);

if ($action === '') {
    jsonResponse(false, 'Invalid request.');
}

/* PC availability actions */
if ($action === 'mark_all_unavailable') {
    handleAllPcUnavailable($conn);
}

if (in_array($action, ['mark_unavailable', 'mark_available'], true)) {
    handlePcAvailability($conn, $action);
}

/* Reservation actions need reservation id */
if ($id <= 0) {
    jsonResponse(false, 'Invalid reservation.');
}

if ($action === 'delete') {
    deleteReservation($conn, $id);
}

$statusMap = [
    'approve' => 'approved',
    'reject'  => 'rejected',
    'cancel'  => 'cancelled',
    'done'    => 'done'
];

if (!isset($statusMap[$action])) {
    jsonResponse(false, 'Invalid action.');
}

if ($action === 'approve') {
    checkApprovalConflict($conn, $id);
}

updateReservationStatus($conn, $id, $statusMap[$action]);


/* =========================
   FUNCTIONS
========================= */

function jsonResponse(bool $success, string $message): void
{
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

function handleAllPcUnavailable(mysqli $conn): void
{
    $lab = trim($_POST['lab'] ?? '');

    if ($lab === '') {
        jsonResponse(false, 'Invalid laboratory.');
    }

    $stmt = $conn->prepare("
        INSERT INTO lab_computers (lab, pc_number, status, notes)
        VALUES (?, ?, 'unavailable', 'Marked all unavailable by admin')
        ON DUPLICATE KEY UPDATE
            status = 'unavailable',
            notes = VALUES(notes)
    ");

    if (!$stmt) {
        jsonResponse(false, 'Failed to prepare mark all unavailable update.');
    }

    $conn->begin_transaction();

    try {
        for ($pc = 1; $pc <= 56; $pc++) {
            $stmt->bind_param('si', $lab, $pc);

            if (!$stmt->execute()) {
                throw new Exception('Failed to update PC ' . $pc);
            }
        }

        $stmt->close();
        $conn->commit();

        jsonResponse(true, 'All PCs in ' . $lab . ' were marked unavailable.');
    } catch (Exception $e) {
        $stmt->close();
        $conn->rollback();

        jsonResponse(false, 'Failed to mark all PCs unavailable.');
    }
}

function handlePcAvailability(mysqli $conn, string $action): void
{
    $lab = trim($_POST['lab'] ?? '');
    $pc = (int)($_POST['pc_number'] ?? 0);

    if ($lab === '' || $pc < 1 || $pc > 56) {
        jsonResponse(false, 'Invalid lab or PC number.');
    }

    if ($action === 'mark_unavailable') {
        $stmt = $conn->prepare("
            INSERT INTO lab_computers (lab, pc_number, status, notes)
            VALUES (?, ?, 'unavailable', 'Marked unavailable by admin')
            ON DUPLICATE KEY UPDATE 
                status = 'unavailable',
                notes = VALUES(notes)
        ");

        if (!$stmt) {
            jsonResponse(false, 'Failed to prepare PC unavailable update.');
        }

        $stmt->bind_param('si', $lab, $pc);
        $ok = $stmt->execute();
        $stmt->close();

        jsonResponse($ok, $ok ? 'PC marked unavailable.' : 'Failed to mark PC unavailable.');
    }

    $stmt = $conn->prepare("
        INSERT INTO lab_computers (lab, pc_number, status, notes)
        VALUES (?, ?, 'available', NULL)
        ON DUPLICATE KEY UPDATE
            status = 'available',
            notes = NULL
    ");

    if (!$stmt) {
        jsonResponse(false, 'Failed to prepare PC available update.');
    }

    $stmt->bind_param('si', $lab, $pc);
    $ok = $stmt->execute();
    $stmt->close();

    jsonResponse($ok, $ok ? 'PC marked available.' : 'Failed to mark PC available.');
}

function deleteReservation(mysqli $conn, int $id): void
{
    $stmt = $conn->prepare("
        DELETE FROM lab_reservations
        WHERE id = ?
    ");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();

    jsonResponse($ok, $ok ? 'Reservation deleted.' : 'Failed to delete reservation.');
}

function getReservation(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare("
        SELECT 
            id,
            student_id,
            studentid,
            fullname,
            purpose,
            lab,
            reservation_date,
            reservation_time,
            COALESCE(reservation_end_time, ADDTIME(reservation_time, '01:00:00')) AS reservation_end_time,
            pc_number,
            status
        FROM lab_reservations
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function checkApprovalConflict(mysqli $conn, int $id): void
{
    $current = getReservation($conn, $id);

    if (!$current) {
        jsonResponse(false, 'Reservation not found.');
    }

    if ($current['status'] !== 'pending') {
        jsonResponse(false, 'Only pending reservations can be approved.');
    }

    $stmt = $conn->prepare("
        SELECT id
        FROM lab_reservations
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
        jsonResponse(false, 'This PC already has an approved reservation during that time range.');
    }
}

function updateReservationStatus(mysqli $conn, int $id, string $newStatus): void
{
    $reservation = getReservation($conn, $id);

    if (!$reservation) {
        jsonResponse(false, 'Reservation not found.');
    }

    $oldStatus = $reservation['status'];

    $stmt = $conn->prepare("
        UPDATE lab_reservations
        SET status = ?
        WHERE id = ?
    ");
    $stmt->bind_param('si', $newStatus, $id);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok && $oldStatus !== $newStatus) {
        createReservationStatusNotification($conn, $reservation, $newStatus);
    }

    $messages = [
        'approved'  => 'Reservation approved.',
        'rejected'  => 'Reservation rejected.',
        'cancelled' => 'Reservation cancelled.',
        'done'      => 'Reservation marked as done.'
    ];

    jsonResponse($ok, $ok ? ($messages[$newStatus] ?? 'Reservation updated.') : 'Failed to update reservation.');
}

function createReservationStatusNotification(mysqli $conn, array $reservation, string $newStatus): void
{
    $studentId = (int)($reservation['student_id'] ?? 0);

    if ($studentId <= 0) {
        return;
    }

    $lab = $reservation['lab'] ?? 'selected lab';
    $pc = 'PC ' . (int)($reservation['pc_number'] ?? 0);
    $dateLabel = !empty($reservation['reservation_date']) ? date('M d, Y', strtotime($reservation['reservation_date'])) : 'your selected date';
    $timeLabel = !empty($reservation['reservation_time']) ? date('h:i A', strtotime($reservation['reservation_time'])) : 'your selected time';

    $details = $lab . ' ' . $pc . ' on ' . $dateLabel . ' at ' . $timeLabel;

    if ($newStatus === 'approved') {
        createStudentNotification(
            $conn,
            $studentId,
            'reservation_approved',
            'Reservation Approved',
            'Your reservation for ' . $details . ' has been approved. Please arrive on time. Reservations may be cancelled if you are more than 15 minutes late.'
        );
        return;
    }

    if ($newStatus === 'rejected') {
        createStudentNotification(
            $conn,
            $studentId,
            'reservation_rejected',
            'Reservation Rejected',
            'Your reservation request for ' . $details . ' has been rejected by the admin.'
        );
        return;
    }

    if ($newStatus === 'cancelled') {
        createStudentNotification(
            $conn,
            $studentId,
            'reservation_cancelled',
            'Reservation Cancelled',
            'Your reservation for ' . $details . ' has been cancelled by the admin.'
        );
    }
}
