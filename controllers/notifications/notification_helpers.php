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
        'session' => 'Session',
        'reward' => 'Reward',
        'software' => 'Software',
        'testimonial' => 'Testimonial',
        'feedback' => 'Feedback',
        'notification' => 'Notification'
    ];

    return $labels[$type] ?? 'Notification';
}

/**
 * Returns the best student page to open when a notification is clicked.
 * This keeps the notification bell/page useful without storing extra URLs in the database.
 */
function notificationTargetUrl(string $type): string
{
    $type = strtolower(trim($type));

    if ($type === 'announcement') {
        return 'announcements.php';
    }

    if (in_array($type, ['reservation_submitted'], true)) {
        return 'reservation.php#pending-reservations';
    }

    if (in_array($type, ['reservation_approved', 'reservation_done'], true)) {
        return 'reservation.php#approved-reservations';
    }

    if (in_array($type, ['reservation_cancelled', 'reservation_late_cancelled'], true)) {
        return 'reservation.php#cancelled-reservations';
    }

    if ($type === 'reservation_rejected') {
        return 'reservation.php#all-reservations';
    }

    if (in_array($type, ['sitin_registered', 'session'], true)) {
        return 'session_table.php';
    }

    if ($type === 'reward') {
        return 'rewards.php';
    }

    if ($type === 'software') {
        return 'software_availability.php';
    }

    if ($type === 'testimonial') {
        return 'testimonials.php';
    }

    if ($type === 'feedback') {
        return 'sitin_history.php';
    }

    return 'notifications.php';
}
