<?php
// controllers/rewards/archive_reset_rewards.php
// FIXED REDIRECT VERSION:
// After archiving/resetting, this redirects back to:
// admin_module/Admin_Rewards.php

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

function clampScore($value)
{
    return max(0, min(100, (float)$value));
}

function computeRewardScore(float $rewardPointsEarned, int $assessmentCount): float
{
    if ($assessmentCount <= 0) {
        return 0;
    }

    return clampScore(($rewardPointsEarned / ($assessmentCount * 10)) * 100);
}

function computeHourScore(int $totalMinutes): float
{
    $targetHours = 30;
    $totalHours = $totalMinutes / 60;

    return clampScore(($totalHours / $targetHours) * 100);
}

function computeTaskScore(float $taskPoints, int $assessmentCount): float
{
    if ($assessmentCount <= 0) {
        return 0;
    }

    return clampScore(($taskPoints / ($assessmentCount * 10)) * 100);
}

function computeFinalScore(float $rewardScore, float $hourScore, float $taskScore): float
{
    return round(($rewardScore * 0.60) + ($hourScore * 0.20) + ($taskScore * 0.20), 2);
}

$archiveTitle = trim($_POST['archive_title'] ?? '');
$adminName = $_SESSION['admin_name'] ?? 'Administrator';

if ($archiveTitle === '') {
    $archiveTitle = 'Leaderboard Archive - ' . date('M d, Y h:i A');
}

$archiveId = 0;

$conn->begin_transaction();

try {
    $seasonRes = $conn->query("
        SELECT current_started_at
        FROM reward_season_settings
        WHERE id = 1
        LIMIT 1
        FOR UPDATE
    ");

    $seasonRow = $seasonRes ? $seasonRes->fetch_assoc() : null;
    $seasonStartedAt = $seasonRow['current_started_at'] ?? '2000-01-01 00:00:00';
    $seasonEndedAt = date('Y-m-d H:i:s');

    $archiveStmt = $conn->prepare("
        INSERT INTO leaderboard_archives
        (title, season_started_at, season_ended_at, created_by)
        VALUES (?, ?, ?, ?)
    ");

    if (!$archiveStmt) {
        throw new Exception('Unable to prepare archive: ' . $conn->error);
    }

    $archiveStmt->bind_param('ssss', $archiveTitle, $seasonStartedAt, $seasonEndedAt, $adminName);
    $archiveStmt->execute();

    $archiveId = (int)$archiveStmt->insert_id;

    $archiveStmt->close();

    $students = [];

    $stmt = $conn->prepare("
        SELECT
            s.id,
            s.studentid,
            s.lastname,
            s.firstname,
            s.middlename,
            s.course,
            s.yearlvl,
            COALESCE(s.reward_points, 0) AS reward_points_balance,
            COALESCE(s.reward_points_earned, 0) AS reward_points_earned,
            COALESCE(s.task_completed, 0) AS task_points,
            COALESCE(sr.total_sessions, 0) AS total_sessions,
            COALESCE(sr.total_minutes, 0) AS total_minutes,
            COALESCE(rpl.assessment_count, 0) AS assessment_count
        FROM students s
        LEFT JOIN (
            SELECT
                student_id,
                COUNT(*) AS total_sessions,
                COALESCE(SUM(
                    CASE
                        WHEN logout_time IS NOT NULL
                        THEN TIMESTAMPDIFF(MINUTE, login_time, logout_time)
                        ELSE 0
                    END
                ), 0) AS total_minutes
            FROM sitin_records
            WHERE login_time >= ?
              AND login_time <= ?
            GROUP BY student_id
        ) sr ON sr.student_id = s.id
        LEFT JOIN (
            SELECT student_id, COUNT(*) AS assessment_count
            FROM reward_point_logs
            WHERE created_at >= ?
              AND created_at <= ?
            GROUP BY student_id
        ) rpl ON rpl.student_id = s.id
    ");

    if (!$stmt) {
        throw new Exception('Unable to prepare leaderboard: ' . $conn->error);
    }

    $stmt->bind_param('ssss', $seasonStartedAt, $seasonEndedAt, $seasonStartedAt, $seasonEndedAt);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $rewardScore = computeRewardScore((float)$row['reward_points_earned'], (int)$row['assessment_count']);
        $hourScore = computeHourScore((int)$row['total_minutes']);
        $taskScore = computeTaskScore((float)$row['task_points'], (int)$row['assessment_count']);
        $finalScore = computeFinalScore($rewardScore, $hourScore, $taskScore);

        $row['reward_score'] = $rewardScore;
        $row['hour_score'] = $hourScore;
        $row['task_score'] = $taskScore;
        $row['final_score'] = $finalScore;

        $students[] = $row;
    }

    $stmt->close();

    usort($students, function ($a, $b) {
        return $b['final_score'] <=> $a['final_score'];
    });

    $entryStmt = $conn->prepare("
        INSERT INTO leaderboard_archive_entries
        (
            archive_id, student_id, studentid, fullname, course, yearlvl, rank_no,
            reward_points_earned, reward_points_balance, task_points,
            total_sessions, total_minutes, assessment_count,
            reward_score, hour_score, task_score, final_score
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$entryStmt) {
        throw new Exception('Unable to prepare archive entries: ' . $conn->error);
    }

    foreach ($students as $index => $s) {
        $rankNo = $index + 1;
        $fullname = trim($s['lastname'] . ', ' . $s['firstname'] . ' ' . $s['middlename']);

        $studentIdInt = (int)$s['id'];
        $studentid = (string)$s['studentid'];
        $course = (string)($s['course'] ?? '');
        $yearlvl = (string)($s['yearlvl'] ?? '');

        $rewardEarned = (float)$s['reward_points_earned'];
        $rewardBalance = (float)$s['reward_points_balance'];
        $taskPoints = (float)$s['task_points'];

        $totalSessions = (int)$s['total_sessions'];
        $totalMinutes = (int)$s['total_minutes'];
        $assessmentCount = (int)$s['assessment_count'];

        $rewardScore = (float)$s['reward_score'];
        $hourScore = (float)$s['hour_score'];
        $taskScore = (float)$s['task_score'];
        $finalScore = (float)$s['final_score'];

        $entryStmt->bind_param(
            'iissssidddiiidddd',
            $archiveId,
            $studentIdInt,
            $studentid,
            $fullname,
            $course,
            $yearlvl,
            $rankNo,
            $rewardEarned,
            $rewardBalance,
            $taskPoints,
            $totalSessions,
            $totalMinutes,
            $assessmentCount,
            $rewardScore,
            $hourScore,
            $taskScore,
            $finalScore
        );

        $entryStmt->execute();
    }

    $entryStmt->close();

    /*
      Reset current reward season:
      Existing sit-in records remain in the database.
      New leaderboard counts only after current_started_at is updated.
    */
    $resetResult = $conn->query("
        UPDATE students
        SET reward_points = 0,
            reward_points_earned = 0,
            task_completed = 0,
            session_credits = 30
    ");

    if (!$resetResult) {
        throw new Exception('Unable to reset students: ' . $conn->error);
    }

    $newStart = date('Y-m-d H:i:s');

    $settingsStmt = $conn->prepare("
        UPDATE reward_season_settings
        SET current_started_at = ?
        WHERE id = 1
    ");

    if (!$settingsStmt) {
        throw new Exception('Unable to update season settings: ' . $conn->error);
    }

    $settingsStmt->bind_param('s', $newStart);
    $settingsStmt->execute();
    $settingsStmt->close();

    $conn->commit();

    $_SESSION['leaderboard_archive_message'] = 'Current leaderboard was archived and the new reward season was reset successfully.';
    $_SESSION['leaderboard_archive_message_type'] = 'success';

    /*
      IMPORTANT:
      Go back to the SAME admin rewards page and open the Past Leaderboards tab.
    */
    header('Location: ../../admin_module/Admin_Rewards.php?archive_id=' . $archiveId);
    exit;

} catch (Exception $e) {
    $conn->rollback();

    $_SESSION['leaderboard_archive_message'] = 'Unable to archive/reset leaderboard. Please check your database setup.';
    $_SESSION['leaderboard_archive_message_type'] = 'danger';

    header('Location: ../../admin_module/Admin_Rewards.php');
    exit;
}
