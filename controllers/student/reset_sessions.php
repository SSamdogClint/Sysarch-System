<?php
// controllers/student/reset_sessions.php

session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');

// Check admin session
if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

// Reset all students' session credits to 30
if ($conn->query('UPDATE students SET session_credits = 30')) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to reset sessions.'
    ]);
}

exit;