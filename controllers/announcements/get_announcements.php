<?php
// controllers/announcements/get_announcements.php

session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');

// Allow both student and admin
if (empty($_SESSION['logged_in']) && empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$announcements = [];

$result = $conn->query("
    SELECT id, title, message, posted_by, created_at
    FROM announcements
    ORDER BY created_at DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// Return JSON
echo json_encode($announcements);
exit;