<?php
// controllers/announcements/student_notifications.php

if (!isset($conn)) {
    require_once __DIR__ . '/../../config/db_config.php';
}

require_once __DIR__ . '/../notifications/notification_helpers.php';
require_once __DIR__ . '/../reservation/reservation_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$student_id = (int)($_SESSION['student_id'] ?? 0);
$notifications = [];

/*
  Trigger late-reservation auto-cancellation whenever a student page with the
  notification bell is loaded. This makes the late-cancel notification appear
  even if the admin is not currently pressing an action button.
*/
autoCancelLateReservations($conn);

/* Latest announcements */
$announcement_result = $conn->query("
    SELECT id, title, message, created_at
    FROM announcements
    ORDER BY created_at DESC
    LIMIT 5
");

if ($announcement_result) {
    while ($row = $announcement_result->fetch_assoc()) {
        $notifications[] = [
            'type' => 'announcement',
            'label' => notificationLabel('announcement'),
            'title' => $row['title'] ?: 'New announcement',
            'message' => $row['message'] ?: '',
            'created_at' => $row['created_at']
        ];
    }
}

/* Real student notifications: reservation approve/reject/cancel, sit-in registered, late cancellation, etc. */
if ($student_id > 0 && notificationsTableExists($conn)) {
    $notif_stmt = $conn->prepare("
        SELECT id, type, title, message, created_at
        FROM student_notifications
        WHERE student_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");

    if ($notif_stmt) {
        $notif_stmt->bind_param('i', $student_id);
        $notif_stmt->execute();
        $notif_result = $notif_stmt->get_result();

        while ($row = $notif_result->fetch_assoc()) {
            $notifications[] = [
                'type' => $row['type'] ?: 'notification',
                'label' => notificationLabel($row['type'] ?: 'notification'),
                'title' => $row['title'] ?: 'New notification',
                'message' => $row['message'] ?: '',
                'created_at' => $row['created_at']
            ];
        }

        $notif_stmt->close();
    }
} else {
    /*
      Fallback for older databases that do not have student_notifications yet.
      This keeps the old active-session notification working.
    */
    $notif_stmt = $conn->prepare("
        SELECT purpose, lab, login_time
        FROM sitin_records
        WHERE student_id = ? AND status = 'active'
        ORDER BY login_time DESC
        LIMIT 5
    ");

    if ($notif_stmt) {
        $notif_stmt->bind_param('i', $student_id);
        $notif_stmt->execute();
        $notif_result = $notif_stmt->get_result();

        while ($row = $notif_result->fetch_assoc()) {
            $notifications[] = [
                'type' => 'session',
                'label' => notificationLabel('session'),
                'title' => 'New session assigned',
                'message' => 'A sit-in session for ' . ($row['purpose'] ?? 'Unknown Purpose') .
                             ' in ' . ($row['lab'] ?? 'Unknown Lab') . ' is now active.',
                'created_at' => $row['login_time']
            ];
        }

        $notif_stmt->close();
    }
}

/* Sort newest first */
usort($notifications, function ($a, $b) {
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});

/* Limit to 8 notifications in the dropdown */
$notifications = array_slice($notifications, 0, 8);
