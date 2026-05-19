<?php
// controllers/session/reset_sessions.php
// Reusable backend controller for resetting student session credits.
// Any admin page can POST reset_title to this controller.
//
// Required database:
// - students.session_credits
// - session_reset_logs table

session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json; charset=utf-8');

function resetSessionsRespond(bool $success, string $message): void
{
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

function resetSessionsHasColumn(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();

    $count = 0;
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return (int)$count > 0;
}

function resetSessionsTableExists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $table);
    $stmt->execute();

    $count = 0;
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return (int)$count > 0;
}

if (empty($_SESSION['admin_logged_in'])) {
    resetSessionsRespond(false, 'Unauthorized. Please log in as admin again.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    resetSessionsRespond(false, 'Invalid request method.');
}

$resetTitle = trim($_POST['reset_title'] ?? '');
$adminName = $_SESSION['admin_name'] ?? 'Administrator';
$defaultCredits = 30;

if ($resetTitle === '') {
    resetSessionsRespond(false, 'Please enter a reset title.');
}

if (!resetSessionsHasColumn($conn, 'students', 'session_credits')) {
    resetSessionsRespond(false, 'The students.session_credits column does not exist. Please run the database SQL first.');
}

if (!resetSessionsTableExists($conn, 'session_reset_logs')) {
    resetSessionsRespond(false, 'The session_reset_logs table does not exist. Please run database/session_reset_update.sql first.');
}

$conn->begin_transaction();

try {
    $summaryResult = $conn->query("
        SELECT
            COUNT(*) AS total_students,
            COALESCE(SUM(session_credits), 0) AS total_credits_before
        FROM students
    ");

    if (!$summaryResult) {
        throw new Exception('Unable to get student summary.');
    }

    $summary = $summaryResult->fetch_assoc();

    $totalStudents = (int)($summary['total_students'] ?? 0);
    $creditsBefore = (int)($summary['total_credits_before'] ?? 0);

    $updateResult = $conn->query("
        UPDATE students
        SET session_credits = {$defaultCredits}
    ");

    if (!$updateResult) {
        throw new Exception('Unable to reset student sessions.');
    }

    $afterResult = $conn->query("
        SELECT COALESCE(SUM(session_credits), 0) AS total_credits_after
        FROM students
    ");

    if (!$afterResult) {
        throw new Exception('Unable to get updated student summary.');
    }

    $after = $afterResult->fetch_assoc();
    $creditsAfter = (int)($after['total_credits_after'] ?? 0);

    $logStmt = $conn->prepare("
        INSERT INTO session_reset_logs
        (reset_title, total_students, total_credits_before, total_credits_after, reset_by)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$logStmt) {
        throw new Exception('Unable to prepare reset log.');
    }

    $logStmt->bind_param(
        'siiis',
        $resetTitle,
        $totalStudents,
        $creditsBefore,
        $creditsAfter,
        $adminName
    );

    $logStmt->execute();
    $logStmt->close();

    $conn->commit();

    resetSessionsRespond(
        true,
        'Sessions reset successfully. ' . $totalStudents . ' student(s) now have ' . $defaultCredits . ' sessions.'
    );
} catch (Exception $e) {
    $conn->rollback();

    resetSessionsRespond(false, 'Unable to reset sessions. Please check your database setup.');
}
