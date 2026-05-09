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

function handlePcAvailability(mysqli $conn, string $action): void
{
    $lab = trim($_POST['lab'] ?? '');
    $pc = (int)($_POST['pc_number'] ?? 0);

    if ($lab === '' || $pc < 1 || $pc > 56) {
        jsonResponse(false, 'Invalid lab or PC number.');
    }

    if ($action === 'mark_unavailable') {
        $stmt = $conn->prepare("
            INSERT INTO lab_pc_status (lab, pc_number, status, note)
            VALUES (?, ?, 'unavailable', 'Marked unavailable by admin')
            ON DUPLICATE KEY UPDATE 
                status = 'unavailable',
                note = VALUES(note)
        ");
        $stmt->bind_param('si', $lab, $pc);
        $ok = $stmt->execute();
        $stmt->close();

        jsonResponse($ok, $ok ? 'PC marked unavailable.' : 'Failed to mark PC unavailable.');
    }

    $stmt = $conn->prepare("
        DELETE FROM lab_pc_status
        WHERE lab = ? AND pc_number = ?
    ");
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
    $stmt = $conn->prepare("
        UPDATE lab_reservations
        SET status = ?
        WHERE id = ?
    ");
    $stmt->bind_param('si', $newStatus, $id);
    $ok = $stmt->execute();
    $stmt->close();

    $messages = [
        'approved'  => 'Reservation approved.',
        'rejected'  => 'Reservation rejected.',
        'cancelled' => 'Reservation cancelled.',
        'done'      => 'Reservation marked as done.'
    ];

    jsonResponse($ok, $ok ? ($messages[$newStatus] ?? 'Reservation updated.') : 'Failed to update reservation.');
}