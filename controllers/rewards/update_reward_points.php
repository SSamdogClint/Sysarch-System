<?php
// controllers/rewards/update_reward_points.php
// Admin reward rating controller.
// Adds points to both:
// reward_points = spendable points for redemption
// reward_points_earned = permanent earned points for leaderboard score

session_start();
require_once '../../config/db_config.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ../../login_page.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../admin_module/Admin_Rewards.php');
    exit;
}

$student_id = (int)($_POST['student_id'] ?? 0);
$reward_percent = (int)($_POST['reward_percent'] ?? 0);
$task_percent = (int)($_POST['task_percent'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';

$allowedPercentages = [0, 25, 50, 75, 100];
$maxRewardPerSession = 10;

if ($student_id <= 0) {
    $_SESSION['reward_message'] = 'Invalid student selected.';
    $_SESSION['reward_message_type'] = 'danger';
    header('Location: ../../admin_module/Admin_Rewards.php');
    exit;
}

if (!in_array($reward_percent, $allowedPercentages, true)) {
    $reward_percent = 0;
}

if (!in_array($task_percent, $allowedPercentages, true)) {
    $task_percent = 0;
}

$points_added = round($maxRewardPerSession * ($reward_percent / 100), 2);
$task_added = round($maxRewardPerSession * ($task_percent / 100), 2);

if ($reason === '') {
    $reason = 'Session reward assessment';
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("
        UPDATE students
        SET 
            reward_points = GREATEST(0, reward_points + ?),
            reward_points_earned = GREATEST(0, reward_points_earned + ?),
            task_completed = GREATEST(0, task_completed + ?)
        WHERE id = ?
    ");

    if (!$stmt) {
        throw new Exception('Unable to prepare student update: ' . $conn->error);
    }

    $stmt->bind_param('dddi', $points_added, $points_added, $task_added, $student_id);
    $stmt->execute();
    $stmt->close();

    $logStmt = $conn->prepare("
        INSERT INTO reward_point_logs
        (student_id, reward_percent, task_percent, points_added, task_added, reason, awarded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$logStmt) {
        throw new Exception('Unable to prepare reward log: ' . $conn->error);
    }

    $logStmt->bind_param(
        'iiiddss',
        $student_id,
        $reward_percent,
        $task_percent,
        $points_added,
        $task_added,
        $reason,
        $admin_name
    );

    $logStmt->execute();
    $logStmt->close();

    $conn->commit();

    $_SESSION['reward_message'] = 'Reward assessment saved. Reward points added: ' . $points_added . ', task points added: ' . $task_added . '.';
    $_SESSION['reward_message_type'] = 'success';
} catch (Exception $e) {
    $conn->rollback();

    $_SESSION['reward_message'] = 'Unable to update reward points. Please run the database SQL first.';
    $_SESSION['reward_message_type'] = 'danger';
}

header('Location: ../../admin_module/Admin_Rewards.php');
exit;
