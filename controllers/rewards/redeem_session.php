<?php
// controllers/rewards/redeem_session.php
// Student redemption controller.
// Important: This deducts ONLY reward_points balance.
// It does NOT deduct reward_points_earned, so the leaderboard score will not go down.

session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json; charset=utf-8');

function jsonResponse(bool $success, string $message, string $type = 'info'): void
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'type' => $type
    ]);
    exit;
}

if (empty($_SESSION['logged_in'])) {
    jsonResponse(false, 'Your session has expired. Please log in again.', 'danger');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', 'danger');
}

$student_id = (int)($_SESSION['student_id'] ?? 0);
$pointsNeeded = 10.00;

if ($student_id <= 0) {
    jsonResponse(false, 'Invalid student account.', 'danger');
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("
        SELECT reward_points, reward_points_earned, session_credits
        FROM students
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $student_id);
    $stmt->execute();

    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) {
        throw new Exception('Student not found.');
    }

    $currentBalance = (float)$student['reward_points'];
    $currentSessions = (int)$student['session_credits'];

    if ($currentBalance < $pointsNeeded) {
        $conn->rollback();

        jsonResponse(
            false,
            'You need at least 10 spendable reward points to redeem 1 sit-in session.',
            'warning'
        );
    }

    $newBalance = $currentBalance - $pointsNeeded;
    $newSessions = $currentSessions + 1;

    $updateStmt = $conn->prepare("
        UPDATE students
        SET reward_points = ?,
            session_credits = ?
        WHERE id = ?
    ");

    if (!$updateStmt) {
        throw new Exception('Update prepare failed: ' . $conn->error);
    }

    $updateStmt->bind_param('dii', $newBalance, $newSessions, $student_id);
    $updateStmt->execute();
    $updateStmt->close();

    $logStmt = $conn->prepare("
        INSERT INTO reward_redemption_logs
        (student_id, points_used, sessions_added)
        VALUES (?, ?, 1)
    ");

    if (!$logStmt) {
        throw new Exception('Log prepare failed: ' . $conn->error);
    }

    $logStmt->bind_param('id', $student_id, $pointsNeeded);
    $logStmt->execute();
    $logStmt->close();

    $conn->commit();

    $_SESSION['session_credits'] = $newSessions;

    jsonResponse(
        true,
        'Successfully redeemed 10 reward points for 1 additional sit-in session. Your leaderboard score is not reduced.',
        'success'
    );

} catch (Exception $e) {
    $conn->rollback();

    jsonResponse(
        false,
        'Unable to redeem points. Please check your database setup.',
        'danger'
    );
}
