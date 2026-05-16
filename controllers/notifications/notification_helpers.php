<?php
// controllers/notifications/notification_helpers.php

/**
 * Checks if the student_notifications table exists.
 * This prevents the system from crashing if the database has not been updated yet.
 */
function notificationsTableExists(mysqli $conn): bool
{
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    $result = $conn->query("SHOW TABLES LIKE 'student_notifications'");
    $exists = ($result && $result->num_rows > 0);

    if ($result) {
        $result->free();
    }

    return $exists;
}

/**
 * Creates a notification for one student.
 */
function createStudentNotification(mysqli $conn, int $studentId, string $type, string $title, string $message): bool
{
    if ($studentId <= 0 || !notificationsTableExists($conn)) {
        return false;
    }

    $stmt = $conn->prepare("
        INSERT INTO student_notifications (student_id, type, title, message)
        VALUES (?, ?, ?, ?)
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('isss', $studentId, $type, $title, $message);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

/**
 * Converts notification type into a clean label for the bell dropdown.
 */
function notificationLabel(string $type): string
{
    $labels = [
        'announcement' => 'Announcement',
        'reservation_submitted' => 'Reservation',
        'reservation_approved' => 'Reservation',
        'reservation_rejected' => 'Reservation',
        'reservation_cancelled' => 'Reservation',
        'reservation_late_cancelled' => 'Reservation',
        'reservation_done' => 'Reservation',
        'sitin_registered' => 'Session',
        'session' => 'Session'
    ];

    return $labels[$type] ?? 'Notification';
}
