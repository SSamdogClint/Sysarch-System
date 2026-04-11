<?php
if (!isset($conn)) {
    die('Database connection is required in student_notifications.php');
}

if (!isset($student_id)) {
    $student_id = (int)($_SESSION['student_id'] ?? 0);
}

$notifications = [];

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
            'title' => $row['title'] ?: 'New announcement',
            'message' => $row['message'] ?: '',
            'created_at' => $row['created_at']
        ];
    }
}

/* Student session notifications */
$notif_stmt = $conn->prepare("
    SELECT purpose, lab, login_time, status
    FROM sitin_records
    WHERE student_id = ?
      AND status = 'active'
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
            'title' => 'New session assigned',
            'message' => 'A sit-in session for ' . ($row['purpose'] ?? 'Unknown Purpose') . ' in ' . ($row['lab'] ?? 'Unknown Lab') . ' is now active.',
            'created_at' => $row['login_time']
        ];
    }

    $notif_stmt->close();
}

/* Sort newest first */
usort($notifications, function ($a, $b) {
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});

$notifications = array_slice($notifications, 0, 8);
?>