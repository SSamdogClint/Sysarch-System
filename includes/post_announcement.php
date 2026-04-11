<?php
session_start();
require_once '../config/db_config.php';
header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$message = trim($_POST['message'] ?? '');
$title   = trim($_POST['title'] ?? '');

if ($message === '') {
    echo json_encode(['success' => false, 'message' => 'Announcement message is required.']);
    exit;
}

$posted_by = $_SESSION['admin_name'] ?? 'CCS Admin';

$stmt = $conn->prepare("
    INSERT INTO announcements (title, message, posted_by)
    VALUES (?, ?, ?)
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('sss', $title, $message, $posted_by);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'announcement' => [
            'id' => $stmt->insert_id,
            'title' => $title,
            'message' => $message,
            'posted_by' => $posted_by,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
exit;