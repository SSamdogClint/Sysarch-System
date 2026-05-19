<?php
// controllers/reservation/get_seats.php

session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');

if (empty($_SESSION['logged_in']) && empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$lab       = trim($_GET['lab'] ?? '');  
$date      = trim($_GET['date'] ?? '');
$time      = trim($_GET['time'] ?? '');
$end_time  = trim($_GET['end_time'] ?? '');

// Normalize time coming from <input type="time"> or <select>
if ($time !== '' && strlen($time) === 5) {
    $time .= ':00';
}
if ($end_time !== '' && strlen($end_time) === 5) {
    $end_time .= ':00';
}

// Backward fallback: if no end time is provided, use one hour after start.
if ($time !== '' && $end_time === '') {
    $end_time = date('H:i:s', strtotime($time) + 3600);
}

if ($lab === '' || $date === '' || $time === '' || $end_time === '') {
    echo json_encode(['success' => false, 'message' => 'Lab, date, start time, and end time are required.']);
    exit;
}

if (strtotime($end_time) <= strtotime($time)) {
    echo json_encode(['success' => false, 'message' => 'End time must be later than start time.']);
    exit;
}

$total_pc = 56; // 8 columns x 7 rows
$seats = [];

for ($i = 1; $i <= $total_pc; $i++) {
    $seats[$i] = [
        'pc_number' => $i,
        'status' => 'available', // available | reserved | unavailable (kept compatible with student page)
        'layout_status' => 'available', // available | pending | reserved | unavailable (used by admin layout)
        'reservation_id' => null,
        'studentid' => null,
        'fullname' => null,
        'purpose' => null,
        'reservation_status' => null,
        'reservation_date' => $date,
        'reservation_time' => $time,
        'reservation_end_time' => $end_time
    ];
}

// Mark unavailable PCs from maintenance table.
$stmt = $conn->prepare("SELECT pc_number FROM lab_computers WHERE lab = ? AND status = 'unavailable'");
$stmt->bind_param('s', $lab);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $pc = (int)$row['pc_number'];
    if (isset($seats[$pc])) {
        $seats[$pc]['status'] = 'unavailable';
        $seats[$pc]['layout_status'] = 'unavailable';
    }
}
$stmt->close();

// Mark pending / approved reservations that overlap the selected time range.
// Time overlap rule: existing_start < selected_end AND existing_end > selected_start.
$stmt = $conn->prepare("
    SELECT id, pc_number, studentid, fullname, purpose, status, reservation_date, reservation_time,
           COALESCE(reservation_end_time, ADDTIME(reservation_time, '01:00:00')) AS reservation_end_time
    FROM lab_reservations
    WHERE lab = ?
      AND reservation_date = ?
      AND status IN ('pending', 'approved')
      AND reservation_time < ?
      AND COALESCE(reservation_end_time, ADDTIME(reservation_time, '01:00:00')) > ?
    ORDER BY FIELD(status, 'approved', 'pending')
");
$stmt->bind_param('ssss', $lab, $date, $end_time, $time);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $pc = (int)$row['pc_number'];
    if (isset($seats[$pc])) {
        if ($row['status'] === 'pending') {
            $seats[$pc]['status'] = 'pending';
            $seats[$pc]['layout_status'] = 'pending';
        } else {
            $seats[$pc]['status'] = 'reserved';
            $seats[$pc]['layout_status'] = 'reserved';
        }
        $seats[$pc]['reservation_id'] = (int)$row['id'];
        $seats[$pc]['studentid'] = $row['studentid'];
        $seats[$pc]['fullname'] = $row['fullname'];
        $seats[$pc]['purpose'] = $row['purpose'];
        $seats[$pc]['reservation_status'] = $row['status'];
        $seats[$pc]['reservation_date'] = $row['reservation_date'];
        $seats[$pc]['reservation_time'] = $row['reservation_time'];
        $seats[$pc]['reservation_end_time'] = $row['reservation_end_time'];
    }
}
$stmt->close();

echo json_encode([
    'success' => true,
    'lab' => $lab,
    'date' => $date,
    'time' => $time,
    'end_time' => $end_time,
    'seats' => array_values($seats)
]);
exit;
