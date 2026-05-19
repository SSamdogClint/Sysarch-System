<?php
// admin_module/Admin_Rewards.php
// Rewards / Leaderboard page with Current Leaderboard and Past Leaderboards tabs in the SAME page.

session_start();
require_once '../config/db_config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ../login_page.php');
    exit;
}

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator');

$message = $_SESSION['reward_message'] ?? ($_SESSION['leaderboard_archive_message'] ?? '');
$messageType = $_SESSION['reward_message_type'] ?? ($_SESSION['leaderboard_archive_message_type'] ?? 'success');
unset(
    $_SESSION['reward_message'],
    $_SESSION['reward_message_type'],
    $_SESSION['leaderboard_archive_message'],
    $_SESSION['leaderboard_archive_message_type']
);

function tableExists(mysqli $conn, string $tableName): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $tableName);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($row['total'] ?? 0) > 0;
}

function columnExists(mysqli $conn, string $tableName, string $columnName): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $tableName, $columnName);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($row['total'] ?? 0) > 0;
}

function formatHours($minutes)
{
    $minutes = max(0, (int)$minutes);
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;

    if ($hours > 0 && $mins > 0) {
        return $hours . ' hr ' . $mins . ' min';
    }

    if ($hours > 0) {
        return $hours . ' hr';
    }

    return $mins . ' min';
}

function formatNumber($value)
{
    $value = (float)$value;

    if (abs($value - round($value)) < 0.001) {
        return (string)(int)round($value);
    }

    return number_format($value, 1);
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

$hasRewardPoints = columnExists($conn, 'students', 'reward_points');
$hasRewardPointsEarned = columnExists($conn, 'students', 'reward_points_earned');
$hasTaskCompleted = columnExists($conn, 'students', 'task_completed');
$hasSessionCredits = columnExists($conn, 'students', 'session_credits');
$hasRewardLogs = tableExists($conn, 'reward_point_logs');
$hasRedemptionLogs = tableExists($conn, 'reward_redemption_logs');
$hasSeasonSettings = tableExists($conn, 'reward_season_settings');
$hasArchiveTables = tableExists($conn, 'leaderboard_archives') && tableExists($conn, 'leaderboard_archive_entries');

$currentSeasonStart = '2000-01-01 00:00:00';

if ($hasSeasonSettings) {
    $seasonRes = $conn->query("
        SELECT current_started_at
        FROM reward_season_settings
        WHERE id = 1
        LIMIT 1
    ");

    if ($seasonRes && $seasonRow = $seasonRes->fetch_assoc()) {
        $currentSeasonStart = $seasonRow['current_started_at'];
    }
}

$rewardBalanceColumn = $hasRewardPoints ? 'COALESCE(s.reward_points, 0)' : '0';
$rewardEarnedColumn = $hasRewardPointsEarned
    ? 'COALESCE(s.reward_points_earned, 0)'
    : ($hasRewardPoints ? 'COALESCE(s.reward_points, 0)' : '0');
$taskColumn = $hasTaskCompleted ? 'COALESCE(s.task_completed, 0)' : '0';

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
        $rewardBalanceColumn AS reward_points_balance,
        $rewardEarnedColumn AS reward_points_earned,
        $taskColumn AS task_points,
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
        GROUP BY student_id
    ) sr ON sr.student_id = s.id
    LEFT JOIN (
        SELECT student_id, COUNT(*) AS assessment_count
        FROM reward_point_logs
        WHERE created_at >= ?
        GROUP BY student_id
    ) rpl ON rpl.student_id = s.id
    ORDER BY s.lastname ASC, s.firstname ASC
");

if ($stmt) {
    $stmt->bind_param('ss', $currentSeasonStart, $currentSeasonStart);
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
        $row['leaderboard_score'] = $finalScore;

        $students[] = $row;
    }

    $stmt->close();
}

$leaderboard = $students;

usort($leaderboard, function ($a, $b) {
    return $b['leaderboard_score'] <=> $a['leaderboard_score'];
});

$logs = [];

if ($hasRewardLogs) {
    $logStmt = $conn->prepare("
        SELECT
            rpl.reward_percent,
            rpl.task_percent,
            rpl.points_added,
            rpl.task_added,
            rpl.reason,
            rpl.awarded_by,
            rpl.created_at,
            s.studentid,
            CONCAT(s.lastname, ', ', s.firstname, ' ', s.middlename) AS fullname
        FROM reward_point_logs rpl
        INNER JOIN students s ON s.id = rpl.student_id
        WHERE rpl.created_at >= ?
        ORDER BY rpl.created_at DESC
        LIMIT 20
    ");

    if ($logStmt) {
        $logStmt->bind_param('s', $currentSeasonStart);
        $logStmt->execute();
        $logs = $logStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $logStmt->close();
    }
}

$redemptionLogs = [];

if ($hasRedemptionLogs) {
    $redemptionStmt = $conn->prepare("
        SELECT
            rrl.points_used,
            rrl.sessions_added,
            rrl.created_at,
            s.studentid,
            CONCAT(s.lastname, ', ', s.firstname, ' ', s.middlename) AS fullname
        FROM reward_redemption_logs rrl
        INNER JOIN students s ON s.id = rrl.student_id
        WHERE rrl.created_at >= ?
        ORDER BY rrl.created_at DESC
        LIMIT 20
    ");

    if ($redemptionStmt) {
        $redemptionStmt->bind_param('s', $currentSeasonStart);
        $redemptionStmt->execute();
        $redemptionLogs = $redemptionStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $redemptionStmt->close();
    }
}

$totalEarnedPoints = 0;
$totalSpendablePoints = 0;
$totalTaskPoints = 0;
$totalMinutes = 0;

foreach ($students as $student) {
    $totalEarnedPoints += (float)$student['reward_points_earned'];
    $totalSpendablePoints += (float)$student['reward_points_balance'];
    $totalTaskPoints += (float)$student['task_points'];
    $totalMinutes += (int)$student['total_minutes'];
}

$archives = [];
$selectedArchive = null;
$pastEntries = [];

if ($hasArchiveTables) {
    $archiveResult = $conn->query("
        SELECT id, title, season_started_at, season_ended_at, created_by, created_at
        FROM leaderboard_archives
        ORDER BY created_at DESC
    ");

    if ($archiveResult) {
        while ($row = $archiveResult->fetch_assoc()) {
            $archives[] = $row;
        }
    }

    $selectedArchiveId = (int)($_GET['archive_id'] ?? ($archives[0]['id'] ?? 0));

    foreach ($archives as $archive) {
        if ((int)$archive['id'] === $selectedArchiveId) {
            $selectedArchive = $archive;
            break;
        }
    }

    if ($selectedArchiveId > 0) {
        $pastStmt = $conn->prepare("
            SELECT *
            FROM leaderboard_archive_entries
            WHERE archive_id = ?
            ORDER BY rank_no ASC
        ");

        if ($pastStmt) {
            $pastStmt->bind_param('i', $selectedArchiveId);
            $pastStmt->execute();
            $pastEntries = $pastStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $pastStmt->close();
        }
    }
}

$activeMainTab = isset($_GET['archive_id']) ? 'past' : 'current';
$setupReady = $hasRewardPoints && $hasRewardPointsEarned && $hasTaskCompleted && $hasSessionCredits && $hasRewardLogs && $hasRedemptionLogs && $hasSeasonSettings && $hasArchiveTables;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>UC – Rewards & Leaderboard</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    body { font-family: 'Poppins', sans-serif; }

    .reward-layout {
      display: grid;
      grid-template-columns: 360px minmax(0, 1fr);
      gap: 22px;
      align-items: start;
    }

    .reward-left {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .reward-stats {
      display: grid;
      grid-template-columns: 1fr;
      gap: 12px;
    }

    .reward-stat-card {
      border: 1px solid rgba(148, 163, 184, 0.35);
      border-radius: 16px;
      padding: 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #ffffff;
    }

    .reward-stat-card .label {
      color: #64748b;
      font-weight: 600;
      font-size: 13px;
      margin-bottom: 4px;
    }

    .reward-stat-card .value {
      font-size: 28px;
      font-weight: 800;
      color: #111827;
      line-height: 1;
    }

    .reward-stat-card i {
      font-size: 28px;
    }

    .formula-box {
      border-left: 4px solid #2563eb;
      background: #eff6ff;
      border-radius: 12px;
      padding: 14px 16px;
      font-size: 13px;
      color: #1e3a8a;
      line-height: 1.7;
    }

    .logs-box {
      max-height: 330px;
      overflow-y: auto;
      padding-right: 4px;
    }

    .logs-box::-webkit-scrollbar,
    .table-responsive::-webkit-scrollbar {
      width: 7px;
      height: 7px;
    }

    .logs-box::-webkit-scrollbar-thumb,
    .table-responsive::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 999px;
    }

    .score-small {
      font-size: 11px;
      color: #64748b;
      display: block;
      margin-top: 2px;
    }


    .reward-flow-help {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 20px;
      height: 20px;
      border-radius: 999px;
      background: #eff6ff;
      color: #1d4ed8;
      font-size: 12px;
      font-weight: 800;
      cursor: help;
      border: 1px solid #bfdbfe;
      margin-left: 6px;
    }

    .reward-flow-note {
      margin-top: 10px;
      background: #f8fafc;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 10px 12px;
      color: #475569;
      font-size: 12px;
      line-height: 1.55;
    }

    .archive-select-box {
      background: #f8fafc;
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 16px;
    }

    @media (max-width: 1200px) {
      .reward-layout {
        grid-template-columns: 1fr;
      }
    }

    body.dark-mode .reward-stat-card,
    body.dark-mode .card,
    body.dark-mode .table,
    body.dark-mode .archive-select-box {
      background: #111827 !important;
      color: #e5e7eb !important;
      border-color: #334155 !important;
    }

    body.dark-mode .reward-stat-card .value,
    body.dark-mode .table td,
    body.dark-mode .table th {
      color: #e5e7eb !important;
    }

    body.dark-mode .reward-stat-card .label,
    body.dark-mode .score-small {
      color: #94a3b8 !important;
    }

    body.dark-mode .formula-box {
      background: #172554;
      color: #bfdbfe;
      border-left-color: #60a5fa;
    }
  </style>
  <link rel="stylesheet" href="../assets/css/admin_table_tools.css">
</head>

<body class="admin-dashboard-page">
  <nav class="uc-nav">
    <a class="nav-brand" href="admin_dashboard.php">
      <img src="../assets/images/ccsmainlog_nobg.png" alt="UC CCS Logo" class="nav-logo">
      <div>
        <div class="nav-title">UC Sit-in System</div>
        <div class="nav-sub">Admin Panel</div>
      </div>
    </a>

    <button class="nav-toggler" id="navToggler" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>

    <div class="nav-links" id="navLinks">
      <span style="font-size:13px; color:#6b7280; padding:0 8px;"><?= $admin_name ?></span>
      <div class="nav-divider"></div>
      <a class="nav-link" href="../controllers/auth/logout.php">Log out</a>
    </div>
  </nav>

  <div class="admin-layout">
    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
      <div class="container-fluid py-4">

        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
              <div>
                <h1 class="h3 fw-bold mb-1">
                  <i class="bi bi-trophy text-warning me-2"></i>
                  Rewards & Leaderboard
                </h1>
                <p class="text-muted mb-0">
                  Manage rewards, view current leaderboard, archive/reset, and view past leaderboards on this same page.
                </p>
              </div>

              <div class="d-flex flex-wrap gap-2">
                <span class="badge text-bg-primary p-2">Max 10 pts/session</span>
                <span class="badge text-bg-success p-2">30 hrs = 100%</span>
                <span class="badge text-bg-warning p-2">60/20/20 scoring</span>
              </div>
            </div>
          </div>
        </div>

        <?php if ($message): ?>
          <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <?php if (!$setupReady): ?>
          <div class="alert alert-warning shadow-sm">
            <strong>Database setup needed:</strong>
            Run <code>database/rewards_no_score_minus_reset_archive.sql</code> first.
          </div>
        <?php endif; ?>

        <div class="reward-layout">
          <aside class="reward-left">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h5 mb-0">
                  <i class="bi bi-plus-circle text-primary me-1"></i>
                  Add Session Rating
                  <span
                    class="reward-flow-help"
                    title="Flow: select a student, rate reward performance and task completion, then save. Reward points are added to spendable balance and earned leaderboard points. Students can redeem 10 spendable points for 1 sit-in session. Redeeming will not lower earned leaderboard points."
                  >?</span>
                </h2>
                <small class="text-muted">Adds to spendable and earned points.</small>
                <div class="reward-flow-note">
                  <strong>Flow:</strong> Select student → choose reward/task percentage → save rating → student earns points. 10 spendable points = 1 extra sit-in session.
                </div>
              </div>

              <div class="card-body p-4">
                <form action="../controllers/rewards/update_reward_points.php" method="POST">
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Student</label>
                    <select class="form-select" name="student_id" required>
                      <option value="">Select student</option>
                      <?php foreach ($students as $student): ?>
                        <option value="<?= (int)$student['id'] ?>">
                          <?= htmlspecialchars($student['studentid'] . ' - ' . $student['lastname'] . ', ' . $student['firstname']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Reward Performance</label>
                    <select class="form-select" name="reward_percent" required>
                      <option value="0">0% - 0 points</option>
                      <option value="25">25% - 2.5 points</option>
                      <option value="50">50% - 5 points</option>
                      <option value="75">75% - 7.5 points</option>
                      <option value="100">100% - 10 points</option>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Task Completion</label>
                    <select class="form-select" name="task_percent" required>
                      <option value="0">0% - 0 task points</option>
                      <option value="25">25% - 2.5 task points</option>
                      <option value="50">50% - 5 task points</option>
                      <option value="75">75% - 7.5 task points</option>
                      <option value="100">100% - 10 task points</option>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Reason</label>
                    <textarea class="form-control" name="reason" rows="3" placeholder="Example: Completed assigned laboratory task"></textarea>
                  </div>

                  <button class="btn btn-primary w-100" type="submit">
                    <i class="bi bi-save me-1"></i>
                    Save Rating
                  </button>
                </form>
              </div>
            </div>

            <div class="reward-stats">
              <div class="reward-stat-card">
                <div>
                  <div class="label">Earned Points</div>
                  <div class="value"><?= formatNumber($totalEarnedPoints) ?></div>
                </div>
                <i class="bi bi-star text-warning"></i>
              </div>

              <div class="reward-stat-card">
                <div>
                  <div class="label">Spendable Balance</div>
                  <div class="value"><?= formatNumber($totalSpendablePoints) ?></div>
                </div>
                <i class="bi bi-wallet2 text-success"></i>
              </div>

              <div class="reward-stat-card">
                <div>
                  <div class="label">Total Sit-in Hours</div>
                  <div class="value"><?= formatHours($totalMinutes) ?></div>
                </div>
                <i class="bi bi-clock-history text-success"></i>
              </div>

              <div class="reward-stat-card">
                <div>
                  <div class="label">Students Ranked</div>
                  <div class="value"><?= count($leaderboard) ?></div>
                </div>
                <i class="bi bi-bar-chart-line text-primary"></i>
              </div>
            </div>

            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h5 mb-0">
                  <i class="bi bi-info-circle text-primary me-1"></i>
                  Score Formula
                </h2>
              </div>

              <div class="card-body p-4">
                <div class="formula-box">
                  <strong>Final Score</strong><br>
                  Earned Reward Score × 60%<br>
                  + Sit-in Hour Score × 20%<br>
                  + Task Score × 20%<br><br>
                  <strong>Redeem:</strong> Spendable points decrease, earned points stay for score.<br>
                  <strong>Reset:</strong> Archive first, then current season resets.
                </div>
              </div>
            </div>

            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h5 mb-0">
                  <i class="bi bi-clock-history text-primary me-1"></i>
                  Recent Reward Logs
                </h2>
              </div>

              <div class="card-body p-4 logs-box">
                <?php if (!$logs): ?>
                  <p class="text-muted mb-0">No reward logs yet.</p>
                <?php else: ?>
                  <div class="list-group list-group-flush">
                    <?php foreach ($logs as $log): ?>
                      <div class="list-group-item px-0 bg-transparent">
                        <div class="fw-semibold"><?= htmlspecialchars($log['fullname']) ?></div>
                        <small class="text-muted">
                          Reward: <?= (int)$log['reward_percent'] ?>%
                          (<?= formatNumber($log['points_added']) ?> pts)
                          · Task: <?= (int)$log['task_percent'] ?>%
                          (<?= formatNumber($log['task_added']) ?> pts)
                        </small>
                        <div class="small mt-1"><?= htmlspecialchars($log['reason']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($log['created_at']) ?></small>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <hr>

                <h3 class="h6 fw-bold mb-3">Recent Redemptions</h3>

                <?php if (!$redemptionLogs): ?>
                  <p class="text-muted mb-0">No redemption logs yet.</p>
                <?php else: ?>
                  <div class="list-group list-group-flush">
                    <?php foreach ($redemptionLogs as $redeem): ?>
                      <div class="list-group-item px-0 bg-transparent">
                        <div class="fw-semibold"><?= htmlspecialchars($redeem['fullname']) ?></div>
                        <small class="text-muted">
                          Used <?= formatNumber($redeem['points_used']) ?> points
                          · Added <?= (int)$redeem['sessions_added'] ?> session
                        </small>
                        <div><small class="text-muted"><?= htmlspecialchars($redeem['created_at']) ?></small></div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </aside>

          <section>
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0 pt-4 px-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                  <div>
                    <h2 class="h5 mb-1">
                      <i class="bi bi-list-ol text-primary me-1"></i>
                      Student Leaderboards
                    </h2>
                    <small class="text-muted">
                      Current and past leaderboards are now on this same Rewards page.
                    </small>
                  </div>

                  <div class="text-end">
                    <small class="text-muted d-block">Current season started</small>
                    <strong class="small"><?= htmlspecialchars($currentSeasonStart) ?></strong>
                  </div>
                </div>

                <ul class="nav nav-pills mt-4" id="adminLeaderboardTabs" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button
                      class="nav-link <?= $activeMainTab === 'current' ? 'active' : '' ?>"
                      id="current-tab"
                      data-bs-toggle="pill"
                      data-bs-target="#current-pane"
                      type="button"
                      role="tab">
                      <i class="bi bi-trophy me-1"></i>
                      Current Leaderboard
                    </button>
                  </li>

                  <li class="nav-item" role="presentation">
                    <button
                      class="nav-link <?= $activeMainTab === 'past' ? 'active' : '' ?>"
                      id="past-tab"
                      data-bs-toggle="pill"
                      data-bs-target="#past-pane"
                      type="button"
                      role="tab">
                      <i class="bi bi-archive me-1"></i>
                      Past Leaderboards
                    </button>
                  </li>

                  <li class="nav-item" role="presentation">
                    <button
                      class="nav-link"
                      id="reset-tab"
                      data-bs-toggle="pill"
                      data-bs-target="#reset-pane"
                      type="button"
                      role="tab">
                      <i class="bi bi-arrow-clockwise me-1"></i>
                      Archive & Reset
                    </button>
                  </li>
                </ul>
              </div>

              <div class="tab-content">
                <div class="tab-pane fade <?= $activeMainTab === 'current' ? 'show active' : '' ?>" id="current-pane" role="tabpanel" tabindex="0">
                  <div class="card-body p-0">
                    <div class="table-responsive">
                      <table class="table table-hover align-middle mb-0 js-admin-table">
                        <thead class="table-light">
                          <tr>
                            <th class="ps-4">Rank</th>
                            <th>Student</th>
                            <th>Sessions</th>
                            <th>Sit-in Hours</th>
                            <th>Earned Points</th>
                            <th>Balance</th>
                            <th>Task Points</th>
                            <th>Assessments</th>
                            <th class="pe-4">Final Score</th>
                          </tr>
                        </thead>

                        <tbody>
                          <?php if (!$leaderboard): ?>
                            <tr>
                              <td colspan="9" class="text-center text-muted py-5">
                                No students found.
                              </td>
                            </tr>
                          <?php endif; ?>

                          <?php foreach ($leaderboard as $index => $student): ?>
                            <?php
                              $rank = $index + 1;
                              $rankBadge = 'secondary';
                              if ($rank === 1) $rankBadge = 'warning';
                              elseif ($rank === 2) $rankBadge = 'secondary';
                              elseif ($rank === 3) $rankBadge = 'danger';
                            ?>

                            <tr>
                              <td class="ps-4">
                                <span class="badge text-bg-<?= $rankBadge ?>">#<?= $rank ?></span>
                              </td>

                              <td>
                                <div class="fw-semibold">
                                  <?= htmlspecialchars($student['lastname'] . ', ' . $student['firstname'] . ' ' . $student['middlename']) ?>
                                </div>
                                <small class="text-muted"><?= htmlspecialchars($student['studentid']) ?></small>
                              </td>

                              <td><?= (int)$student['total_sessions'] ?></td>

                              <td>
                                <?= formatHours($student['total_minutes']) ?>
                                <span class="score-small">Hour score: <?= formatNumber($student['hour_score']) ?>%</span>
                              </td>

                              <td>
                                <?= formatNumber($student['reward_points_earned']) ?>
                                <span class="score-small">Reward score: <?= formatNumber($student['reward_score']) ?>%</span>
                              </td>

                              <td><?= formatNumber($student['reward_points_balance']) ?></td>

                              <td>
                                <?= formatNumber($student['task_points']) ?>
                                <span class="score-small">Task score: <?= formatNumber($student['task_score']) ?>%</span>
                              </td>

                              <td><?= (int)$student['assessment_count'] ?></td>

                              <td class="pe-4">
                                <strong><?= formatNumber($student['leaderboard_score']) ?></strong>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <div class="tab-pane fade <?= $activeMainTab === 'past' ? 'show active' : '' ?>" id="past-pane" role="tabpanel" tabindex="0">
                  <div class="card-body p-4 border-bottom">
                    <?php if (!$hasArchiveTables): ?>
                      <div class="alert alert-warning mb-0">
                        Past leaderboard tables are not yet available. Run
                        <code>database/rewards_no_score_minus_reset_archive.sql</code> first.
                      </div>
                    <?php elseif (!$archives): ?>
                      <div class="alert alert-info mb-0">
                        No past leaderboards yet. Use the <strong>Archive & Reset</strong> tab after a grading period.
                      </div>
                    <?php else: ?>
                      <div class="archive-select-box">
                        <div class="row g-3 align-items-end">
                          <div class="col-lg-7">
                            <label class="form-label fw-semibold">Select Past Leaderboard</label>
                            <select class="form-select" id="archiveSelect">
                              <?php foreach ($archives as $archive): ?>
                                <option
                                  value="<?= (int)$archive['id'] ?>"
                                  <?= $selectedArchive && (int)$selectedArchive['id'] === (int)$archive['id'] ? 'selected' : '' ?>>
                                  <?= htmlspecialchars($archive['title']) ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>

                          <div class="col-lg-5">
                            <?php if ($selectedArchive): ?>
                              <div class="text-muted small">
                                Season:
                                <strong><?= htmlspecialchars($selectedArchive['season_started_at']) ?></strong>
                                to
                                <strong><?= htmlspecialchars($selectedArchive['season_ended_at']) ?></strong>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="card-body p-0">
                    <div class="table-responsive">
                      <table class="table table-hover align-middle mb-0 js-admin-table">
                        <thead class="table-light">
                          <tr>
                            <th class="ps-4">Rank</th>
                            <th>Student</th>
                            <th>Sessions</th>
                            <th>Sit-in Hours</th>
                            <th>Earned Points</th>
                            <th>Balance</th>
                            <th>Task Points</th>
                            <th>Assessments</th>
                            <th class="pe-4">Final Score</th>
                          </tr>
                        </thead>

                        <tbody>
                          <?php if (!$pastEntries): ?>
                            <tr>
                              <td colspan="9" class="text-center text-muted py-5">
                                No past leaderboard entries found.
                              </td>
                            </tr>
                          <?php endif; ?>

                          <?php foreach ($pastEntries as $entry): ?>
                            <tr>
                              <td class="ps-4">
                                <span class="badge text-bg-<?= (int)$entry['rank_no'] === 1 ? 'warning' : 'secondary' ?>">
                                  #<?= (int)$entry['rank_no'] ?>
                                </span>
                              </td>

                              <td>
                                <div class="fw-semibold"><?= htmlspecialchars($entry['fullname']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($entry['studentid']) ?></small>
                              </td>

                              <td><?= (int)$entry['total_sessions'] ?></td>

                              <td>
                                <?= formatHours($entry['total_minutes']) ?>
                                <span class="score-small"><?= formatNumber($entry['hour_score']) ?>%</span>
                              </td>

                              <td>
                                <?= formatNumber($entry['reward_points_earned']) ?>
                                <span class="score-small"><?= formatNumber($entry['reward_score']) ?>%</span>
                              </td>

                              <td><?= formatNumber($entry['reward_points_balance']) ?></td>

                              <td>
                                <?= formatNumber($entry['task_points']) ?>
                                <span class="score-small"><?= formatNumber($entry['task_score']) ?>%</span>
                              </td>

                              <td><?= (int)$entry['assessment_count'] ?></td>

                              <td class="pe-4">
                                <strong><?= formatNumber($entry['final_score']) ?></strong>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <div class="tab-pane fade" id="reset-pane" role="tabpanel" tabindex="0">
                  <div class="card-body p-4">
                    <div class="row g-4">
                      <div class="col-lg-7">
                        <h3 class="h5 fw-bold">Archive Current Leaderboard & Reset Season</h3>
                        <p class="text-muted">
                          This saves the current leaderboard into the Past Leaderboards tab first, then resets the current reward season.
                        </p>

                        <div class="alert alert-warning">
                          This will reset current:
                          <strong>spendable points, earned points, task points, and session credits back to 30</strong>.
                          Existing sit-in records stay in the database, but the new leaderboard will count the next season only.
                        </div>

                        <form action="../controllers/rewards/archive_reset_rewards.php" method="POST" id="archiveResetForm">
                          <div class="mb-3">
                            <label class="form-label fw-semibold">Archive Title</label>
                            <input
                              type="text"
                              class="form-control"
                              name="archive_title"
                              placeholder="Example: Pre-Final Leaderboard">
                          </div>

                          <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#archiveResetModal">
                            <i class="bi bi-archive me-1"></i>
                            Archive Current & Reset
                          </button>
                        </form>
                      </div>

                      <div class="col-lg-5">
                        <div class="formula-box">
                          <strong>Current season started:</strong><br>
                          <?= htmlspecialchars($currentSeasonStart) ?><br><br>

                          <strong>What will be saved?</strong><br>
                          Rank, student info, earned points, balance, task points, sessions, hours, scores, and final score.
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </section>
        </div>

      </div>
    </main>
  </div>

  <!-- Archive Reset Modal -->
  <div class="modal fade" id="archiveResetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-body p-5 text-center">
          <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger" style="width:70px;height:70px;">
            <i class="bi bi-archive fs-1"></i>
          </div>

          <h5 class="fw-bold mb-2">Archive and Reset?</h5>
          <p class="text-muted mb-4">
            The current leaderboard will be saved under Past Leaderboards, then the current season will reset.
          </p>

          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-light border px-4 rounded-pill" data-bs-dismiss="modal">
              Cancel
            </button>

            <button type="button" class="btn btn-danger px-4 rounded-pill" id="confirmArchiveResetBtn">
              Yes, Archive & Reset
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    const toggler = document.getElementById('navToggler');

    if (toggler) {
      toggler.addEventListener('click', () => {
        const navLinks = document.getElementById('navLinks');
        const sidebar = document.getElementById('sidebar');

        if (navLinks) navLinks.classList.toggle('open');
        if (sidebar) sidebar.classList.toggle('open');
      });
    }

    const archiveSelect = document.getElementById('archiveSelect');

    if (archiveSelect) {
      archiveSelect.addEventListener('change', function () {
        const archiveId = this.value;
        window.location.href = 'Admin_Rewards.php?archive_id=' + encodeURIComponent(archiveId);
      });
    }

    const confirmArchiveResetBtn = document.getElementById('confirmArchiveResetBtn');
    const archiveResetForm = document.getElementById('archiveResetForm');

    if (confirmArchiveResetBtn && archiveResetForm) {
      confirmArchiveResetBtn.addEventListener('click', function () {
        confirmArchiveResetBtn.disabled = true;
        confirmArchiveResetBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';
        archiveResetForm.submit();
      });
    }
  </script>
  <script src="../assets/js/admin_table_tools.js"></script>
</body>
</html>
