<?php
// student_module/rewards.php

session_start();
require_once '../config/db_config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

if (empty($_SESSION['logged_in'])) {
    header('Location: ../home.php');
    exit;
}

$student_id   = (int)($_SESSION['student_id'] ?? 0);
$studentid_no = htmlspecialchars($_SESSION['studentid'] ?? '');
$middlename   = htmlspecialchars($_SESSION['middlename'] ?? '');
$firstname    = htmlspecialchars($_SESSION['firstname'] ?? '');
$lastname     = htmlspecialchars($_SESSION['lastname'] ?? '');
$course       = htmlspecialchars($_SESSION['course'] ?? '');
$yearlvl      = htmlspecialchars($_SESSION['yearlvl'] ?? '');
$email        = htmlspecialchars($_SESSION['email'] ?? '');
$addrs        = htmlspecialchars($_SESSION['addrs'] ?? '');
$initials     = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));

require_once '../controllers/announcements/student_notifications.php';

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

function computeRewardScore(float $rewardPoints, int $assessmentCount): float
{
    if ($assessmentCount <= 0) {
        return 0;
    }

    return clampScore(($rewardPoints / ($assessmentCount * 10)) * 100);
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
$hasTaskCompleted = columnExists($conn, 'students', 'task_completed');
$hasSessionCredits = columnExists($conn, 'students', 'session_credits');
$hasRewardLogs = tableExists($conn, 'reward_point_logs');
$hasRedemptionLogs = tableExists($conn, 'reward_redemption_logs');

$rewardColumn = $hasRewardPoints ? 'COALESCE(s.reward_points, 0)' : '0';
$taskColumn = $hasTaskCompleted ? 'COALESCE(s.task_completed, 0)' : '0';

$rewardLogJoin = $hasRewardLogs
    ? "
        LEFT JOIN (
            SELECT student_id, COUNT(*) AS assessment_count
            FROM reward_point_logs
            GROUP BY student_id
        ) rpl ON rpl.student_id = s.id
      "
    : "
        LEFT JOIN (
            SELECT NULL AS student_id, 0 AS assessment_count
        ) rpl ON rpl.student_id = s.id
      ";

$leaderboard = [];

$result = $conn->query("
    SELECT
        s.id,
        s.studentid,
        s.lastname,
        s.firstname,
        s.middlename,
        s.course,
        s.yearlvl,
        $rewardColumn AS reward_points,
        $taskColumn AS task_completed,
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
        GROUP BY student_id
    ) sr ON sr.student_id = s.id
    $rewardLogJoin
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rewardPoints = (float)$row['reward_points'];
        $taskPoints = (float)$row['task_completed'];
        $assessmentCount = (int)$row['assessment_count'];
        $totalMinutes = (int)$row['total_minutes'];

        $rewardScore = computeRewardScore($rewardPoints, $assessmentCount);
        $hourScore = computeHourScore($totalMinutes);
        $taskScore = computeTaskScore($taskPoints, $assessmentCount);
        $finalScore = computeFinalScore($rewardScore, $hourScore, $taskScore);

        $row['reward_score'] = $rewardScore;
        $row['hour_score'] = $hourScore;
        $row['task_score'] = $taskScore;
        $row['leaderboard_score'] = $finalScore;

        $leaderboard[] = $row;
    }
}

usort($leaderboard, function ($a, $b) {
    return $b['leaderboard_score'] <=> $a['leaderboard_score'];
});

$myRank = 0;
$myData = null;

foreach ($leaderboard as $index => $student) {
    if ((int)$student['id'] === $student_id) {
        $myRank = $index + 1;
        $myData = $student;
        break;
    }
}

$myRewardPoints = (float)($myData['reward_points'] ?? 0);
$myTaskPoints = (float)($myData['task_completed'] ?? 0);
$myTotalMinutes = (int)($myData['total_minutes'] ?? 0);
$mySessions = (int)($myData['total_sessions'] ?? 0);
$myAssessmentCount = (int)($myData['assessment_count'] ?? 0);
$myScore = (float)($myData['leaderboard_score'] ?? 0);
$myRewardScore = (float)($myData['reward_score'] ?? 0);
$myHourScore = (float)($myData['hour_score'] ?? 0);
$myTaskScore = (float)($myData['task_score'] ?? 0);

$mySessionCredits = 0;

if ($hasSessionCredits) {
    $creditStmt = $conn->prepare("
        SELECT session_credits
        FROM students
        WHERE id = ?
        LIMIT 1
    ");

    if ($creditStmt) {
        $creditStmt->bind_param('i', $student_id);
        $creditStmt->execute();

        $creditRow = $creditStmt->get_result()->fetch_assoc();
        $creditStmt->close();

        $mySessionCredits = (int)($creditRow['session_credits'] ?? 0);
    }
}

$pointsPerSession = 10;
$redeemableSessions = (int)floor($myRewardPoints / $pointsPerSession);

$myLogs = [];

if ($hasRewardLogs) {
    $logStmt = $conn->prepare("
        SELECT reward_percent, task_percent, points_added, task_added, reason, awarded_by, created_at
        FROM reward_point_logs
        WHERE student_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");

    if ($logStmt) {
        $logStmt->bind_param('i', $student_id);
        $logStmt->execute();
        $myLogs = $logStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $logStmt->close();
    }
}

$myRedemptionLogs = [];

if ($hasRedemptionLogs) {
    $redemptionStmt = $conn->prepare("
        SELECT points_used, sessions_added, created_at
        FROM reward_redemption_logs
        WHERE student_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");

    if ($redemptionStmt) {
        $redemptionStmt->bind_param('i', $student_id);
        $redemptionStmt->execute();
        $myRedemptionLogs = $redemptionStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $redemptionStmt->close();
    }
}

$redemptionReady = $hasRewardPoints && $hasSessionCredits && $hasRedemptionLogs;
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
  <link rel="stylesheet" href="../assets/css/student.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    body { font-family: 'Poppins', sans-serif; }

    .reward-layout {
      display: grid;
      grid-template-columns: 340px minmax(0, 1fr);
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
      min-height: 88px;
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

    .reward-stat-card .sub {
      font-size: 11px;
      color: #64748b;
      margin-top: 5px;
      display: block;
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
      max-height: 310px;
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

    @media (max-width: 1200px) {
      .reward-layout {
        grid-template-columns: 1fr;
      }
    }

    body.dark-mode .reward-stat-card,
    body.dark-mode .card,
    body.dark-mode .table {
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
    body.dark-mode .reward-stat-card .sub,
    body.dark-mode .score-small {
      color: #94a3b8 !important;
    }

    body.dark-mode .formula-box {
      background: #172554;
      color: #bfdbfe;
      border-left-color: #60a5fa;
    }
  </style>
</head>

<body class="student-dashboard-page">
  <script>
    if (localStorage.getItem('uc_dark_mode') === 'enabled') {
      document.body.classList.add('dark-mode');
    }
  </script>

  <nav class="uc-nav">
    <a class="nav-brand" href="student_dashboard.php">
      <img src="../assets/images/ccsmainlog_nobg.png" alt="UC CCS Logo" class="nav-logo">
      <div>
        <div class="nav-title">UC Sit-in System</div>
        <div class="nav-sub">Main Campus · CCS</div>
      </div>
    </a>

    <button class="nav-toggler" id="navToggler" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>

    <div class="nav-links" id="navLinks">
      <div class="notif-dropdown" id="notifDropdown">
        <button type="button" class="notif-bell-btn" id="notifBellBtn" aria-label="Notifications">
          <i class="bi bi-bell"></i>
          <span class="notif-dot" id="notifDot"></span>
        </button>

        <div class="notif-menu" id="notifMenu">
          <div class="notif-menu-header">Notifications</div>

          <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notif): ?>
              <div class="notif-menu-item">
                <div class="notif-type <?= htmlspecialchars($notif['type']) ?>">
                  <?= htmlspecialchars($notif['label'] ?? ($notif['type'] === 'announcement' ? 'Announcement' : 'Session')) ?>
                </div>
                <div class="notif-title"><?= htmlspecialchars($notif['title']) ?></div>
                <div class="notif-text"><?= htmlspecialchars($notif['message']) ?></div>
                <div class="notif-time"><?= date('M d, Y h:i A', strtotime($notif['created_at'])) ?></div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="notif-empty">No notifications yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <button type="button" class="dark-toggle" id="darkModeToggle" onclick="toggleDarkMode()" aria-label="Toggle dark mode" aria-pressed="false">
        <i class="bi bi-moon-stars"></i>
        <span>Dark</span>
      </button>

      <span style="font-size:13px; color:#6b7280; padding:0 4px;">
        <?= $firstname . ' ' . $lastname ?>
      </span>

      <div class="nav-divider"></div>
      <a class="nav-link" href="../controllers/auth/logout.php">Log out</a>
    </div>
  </nav>

  <div class="admin-layout">
    <?php require __DIR__ . '/../includes/student_sidebar.php'; ?>

    <main class="admin-main">
      <div class="container-fluid py-4">

        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
              <div>
                <h1 class="h3 fw-bold mb-1">
                  <i class="bi bi-trophy text-warning me-2"></i>
                  My Rewards & Leaderboard
                </h1>
                <p class="text-muted mb-0">
                  View your reward points, redeem sessions, reward logs, and ranking.
                </p>
              </div>

              <div class="text-end">
                <div class="text-muted small">Current Rank</div>
                <div class="display-6 fw-bold text-primary">#<?= $myRank ?: '—' ?></div>
              </div>
            </div>
          </div>
        </div>

        <?php if (!$redemptionReady): ?>
          <div class="alert alert-warning shadow-sm">
            <strong>Database setup needed:</strong>
            Please run <code>database/reward_redemption_update.sql</code> first to enable reward redemption.
          </div>
        <?php endif; ?>

        <div class="reward-layout">
          <aside class="reward-left">
            <div class="reward-stats">
              <div class="reward-stat-card">
                <div>
                  <div class="label">Reward Points</div>
                  <div class="value"><?= formatNumber($myRewardPoints) ?></div>
                  <span class="sub">Reward score: <?= formatNumber($myRewardScore) ?>%</span>
                </div>
                <i class="bi bi-star text-warning"></i>
              </div>

              <div class="reward-stat-card">
                <div>
                  <div class="label">Sit-in Hours</div>
                  <div class="value"><?= formatHours($myTotalMinutes) ?></div>
                  <span class="sub">Hour score: <?= formatNumber($myHourScore) ?>%</span>
                </div>
                <i class="bi bi-clock-history text-success"></i>
              </div>

              <div class="reward-stat-card">
                <div>
                  <div class="label">Task Completion</div>
                  <div class="value"><?= formatNumber($myTaskPoints) ?></div>
                  <span class="sub">Task score: <?= formatNumber($myTaskScore) ?>%</span>
                </div>
                <i class="bi bi-check2-square text-info"></i>
              </div>

              <div class="reward-stat-card">
                <div>
                  <div class="label">Final Score</div>
                  <div class="value"><?= formatNumber($myScore) ?></div>
                  <span class="sub"><?= $myAssessmentCount ?> assessment/s</span>
                </div>
                <i class="bi bi-bar-chart-line text-primary"></i>
              </div>
            </div>

            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h5 mb-0">
                  <i class="bi bi-gift text-success me-1"></i>
                  Redeem Sit-in Session
                </h2>
                <small class="text-muted">10 reward points = 1 additional sit-in session.</small>
              </div>

              <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div>
                    <div class="text-muted small">Available Sessions</div>
                    <div class="h3 fw-bold mb-0"><?= $mySessionCredits ?></div>
                  </div>

                  <div class="text-end">
                    <div class="text-muted small">Redeemable</div>
                    <div class="h3 fw-bold text-success mb-0"><?= $redeemableSessions ?></div>
                  </div>
                </div>

                <div class="formula-box mb-3">
                  Your current reward points: <strong id="currentRewardPoints"><?= formatNumber($myRewardPoints) ?></strong><br>
                  You need <strong>10 points</strong> to redeem <strong>1 sit-in session</strong>.
                </div>

                <form id="redeemForm" action="../controllers/rewards/redeem_session.php" method="POST">
                  <button
                    type="button"
                    class="btn btn-success w-100"
                    id="redeemOpenBtn"
                    <?= ($redeemableSessions < 1 || !$redemptionReady) ? 'disabled' : '' ?>>
                    <i class="bi bi-arrow-repeat me-1"></i>
                    Redeem 10 Points
                  </button>
                </form>

                <?php if ($redeemableSessions < 1): ?>
                  <small class="text-muted d-block mt-2">
                    Earn at least 10 reward points to enable redemption.
                  </small>
                <?php endif; ?>
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
                  Reward Score × 60%<br>
                  + Sit-in Hour Score × 20%<br>
                  + Task Score × 20%<br><br>
                  <strong>Hour Rule:</strong> 30 sit-in hours = 100%.<br>
                  <strong>Reward Rule:</strong> 100% rating = 10 points per session.<br>
                  <strong>Redeem Rule:</strong> 10 reward points = 1 sit-in session.
                </div>
              </div>
            </div>

            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h5 mb-0">
                  <i class="bi bi-clock-history text-primary me-1"></i>
                  My Reward Logs
                </h2>
                <small class="text-muted">Recent reward and redemption updates.</small>
              </div>

              <div class="card-body p-4 logs-box">
                <h3 class="h6 fw-bold mb-3">Reward Ratings</h3>

                <?php if (!$myLogs): ?>
                  <p class="text-muted mb-0">No reward logs yet.</p>
                <?php else: ?>
                  <div class="list-group list-group-flush">
                    <?php foreach ($myLogs as $log): ?>
                      <div class="list-group-item px-0 bg-transparent">
                        <div class="d-flex justify-content-between gap-2">
                          <strong><?= formatNumber($log['points_added']) ?> reward pts</strong>
                          <small class="text-muted"><?= htmlspecialchars($log['created_at']) ?></small>
                        </div>

                        <small class="text-muted">
                          Reward: <?= (int)$log['reward_percent'] ?>%
                          · Task: <?= (int)$log['task_percent'] ?>%
                          · Task points: <?= formatNumber($log['task_added']) ?>
                        </small>

                        <div class="small mt-1"><?= htmlspecialchars($log['reason']) ?></div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <hr>

                <h3 class="h6 fw-bold mb-3">
                  <i class="bi bi-arrow-repeat text-success me-1"></i>
                  Redemption Logs
                </h3>

                <?php if (!$myRedemptionLogs): ?>
                  <p class="text-muted mb-0">No redemption logs yet.</p>
                <?php else: ?>
                  <div class="list-group list-group-flush">
                    <?php foreach ($myRedemptionLogs as $redeemLog): ?>
                      <div class="list-group-item px-0 bg-transparent">
                        <div class="d-flex justify-content-between gap-2">
                          <strong>+<?= (int)$redeemLog['sessions_added'] ?> session</strong>
                          <small class="text-muted"><?= htmlspecialchars($redeemLog['created_at']) ?></small>
                        </div>

                        <small class="text-muted">
                          Used <?= formatNumber($redeemLog['points_used']) ?> reward points
                        </small>
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
                <h2 class="h5 mb-0">
                  <i class="bi bi-list-ol text-primary me-1"></i>
                  Student Leaderboard
                </h2>
                <small class="text-muted">Top students based on overall reward score.</small>
              </div>

              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th class="ps-4">Rank</th>
                        <th>Student</th>
                        <th>Sessions</th>
                        <th>Sit-in Hours</th>
                        <th>Reward Points</th>
                        <th>Task Points</th>
                        <th>Assessments</th>
                        <th class="pe-4">Final Score</th>
                      </tr>
                    </thead>

                    <tbody>
                      <?php if (!$leaderboard): ?>
                        <tr>
                          <td colspan="8" class="text-center text-muted py-5">
                            No students found.
                          </td>
                        </tr>
                      <?php endif; ?>

                      <?php foreach ($leaderboard as $index => $student): ?>
                        <?php
                          $rank = $index + 1;
                          $isMe = (int)$student['id'] === $student_id;
                        ?>

                        <tr class="<?= $isMe ? 'table-primary' : '' ?>">
                          <td class="ps-4">
                            <span class="badge text-bg-<?= $rank === 1 ? 'warning' : 'secondary' ?>">
                              #<?= $rank ?>
                            </span>
                          </td>

                          <td>
                            <div class="fw-semibold">
                              <?= htmlspecialchars($student['lastname'] . ', ' . $student['firstname']) ?>
                              <?= $isMe ? '<span class="badge text-bg-primary ms-1">You</span>' : '' ?>
                            </div>
                            <small class="text-muted"><?= htmlspecialchars($student['studentid']) ?></small>
                          </td>

                          <td><?= (int)$student['total_sessions'] ?></td>

                          <td>
                            <?= formatHours($student['total_minutes']) ?>
                            <span class="score-small"><?= formatNumber($student['hour_score']) ?>%</span>
                          </td>

                          <td>
                            <?= formatNumber($student['reward_points']) ?>
                            <span class="score-small"><?= formatNumber($student['reward_score']) ?>%</span>
                          </td>

                          <td>
                            <?= formatNumber($student['task_completed']) ?>
                            <span class="score-small"><?= formatNumber($student['task_score']) ?>%</span>
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
          </section>
        </div>

      </div>
    </main>
  </div>

  <div id="editModal" style="
    display:none; position:fixed; inset:0; z-index:9998;
    background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">

    <div style="
      background:#fff; border-radius:16px; width:100%; max-width:540px;
      max-height:90vh; overflow-y:auto; margin:1rem;
      box-shadow:0 20px 60px rgba(0,0,0,0.2);
      font-family:'Poppins',sans-serif; overflow:hidden;">

      <div style="
        background:#1d3a6e; color:#fff; padding:16px 24px;
        display:flex; align-items:center; justify-content:space-between;">
        <span style="font-size:14px; font-weight:600;">Edit Profile</span>

        <button type="button" onclick="closeModal()" style="
          background:transparent; border:none; color:#fff;
          font-size:20px; cursor:pointer; line-height:1;">✕</button>
      </div>

      <div style="padding:24px;">
        <form action="../controllers/student/update_profile.php" method="POST">
          <input type="hidden" name="student_id" value="<?= (int)$student_id ?>">
          <input type="hidden" name="studentid" value="<?= $studentid_no ?>">
          <input type="hidden" name="middlename" value="<?= $middlename ?>">

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">First Name</label>
            <input type="text" name="firstname" value="<?= $firstname ?>" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:9px 13px;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Last Name</label>
            <input type="text" name="lastname" value="<?= $lastname ?>" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:9px 13px;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Course</label>
            <input type="text" name="course" value="<?= $course ?>" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:9px 13px;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Year Level</label>
            <input type="text" name="yearlvl" value="<?= $yearlvl ?>" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:9px 13px;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Email</label>
            <input type="email" name="email" value="<?= $email ?>" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:9px 13px;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Address</label>
            <input type="text" name="addrs" value="<?= $addrs ?>" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:9px 13px;">
          </div>

          <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" onclick="closeModal()" style="padding:9px 20px; border:1px solid #d1d5db; border-radius:8px; background:#fff;">
              Cancel
            </button>

            <button type="submit" style="padding:9px 24px; background:#1d3a6e; color:#fff; border:none; border-radius:8px;">
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Redeem Confirmation Modal -->
  <div class="modal fade" id="redeemConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-body p-5 text-center">
          <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success" style="width:70px;height:70px;">
            <i class="bi bi-gift fs-1"></i>
          </div>

          <h5 class="fw-bold mb-2">Redeem Sit-in Session?</h5>

          <p class="text-muted mb-4">
            This will deduct <strong>10 reward points</strong> and add
            <strong>1 additional sit-in session</strong> to your account.
          </p>

          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-light border px-4 rounded-pill" data-bs-dismiss="modal">
              Cancel
            </button>

            <button type="button" class="btn btn-success px-4 rounded-pill" id="redeemConfirmBtn">
              Yes, Redeem
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Redeem Result Modal -->
  <div class="modal fade" id="redeemResultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-body p-5 text-center">
          <div id="redeemResultIcon" class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width:70px;height:70px;">
            <i class="bi bi-check-circle fs-1"></i>
          </div>

          <h5 class="fw-bold mb-2" id="redeemResultTitle">Success</h5>
          <p class="text-muted mb-4" id="redeemResultMessage">Action completed successfully.</p>

          <button type="button" class="btn btn-primary px-4 rounded-pill" data-bs-dismiss="modal">
            Okay
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function applyDarkMode() {
      const enabled = localStorage.getItem('uc_dark_mode') === 'enabled';
      document.body.classList.toggle('dark-mode', enabled);

      const btn = document.getElementById('darkModeToggle');

      if (btn) {
        btn.setAttribute('aria-pressed', enabled ? 'true' : 'false');

        btn.innerHTML = enabled
          ? '<i class="bi bi-sun"></i><span>Light</span>'
          : '<i class="bi bi-moon-stars"></i><span>Dark</span>';
      }
    }

    function toggleDarkMode() {
      const enabled = !document.body.classList.contains('dark-mode');
      localStorage.setItem('uc_dark_mode', enabled ? 'enabled' : 'disabled');
      applyDarkMode();
    }

    applyDarkMode();

    const navToggler = document.getElementById('navToggler');

    if (navToggler) {
      navToggler.addEventListener('click', () => {
        const navLinks = document.getElementById('navLinks');
        const sidebar = document.getElementById('sidebar');

        if (navLinks) navLinks.classList.toggle('open');
        if (sidebar) sidebar.classList.toggle('open');
      });
    }

    function openModal() {
      const modal = document.getElementById('editModal');
      if (modal) modal.style.display = 'flex';
    }

    function closeModal() {
      const modal = document.getElementById('editModal');
      if (modal) modal.style.display = 'none';
    }

    const editModal = document.getElementById('editModal');
    if (editModal) {
      editModal.addEventListener('click', function(e) {
        if (e.target === this) closeModal();
      });
    }

    const notifications = <?= json_encode($notifications ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const notifBellBtn = document.getElementById('notifBellBtn');
    const notifMenu = document.getElementById('notifMenu');
    const notifDot = document.getElementById('notifDot');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifStorageKey = 'student_notif_last_seen_<?= (int)$student_id ?>';

    function getLatestNotifTime() {
      if (!notifications.length) return 0;
      return Math.max(...notifications.map(n => new Date(n.created_at).getTime() || 0));
    }

    function updateNotifState() {
      if (!notifDot || !notifBellBtn) return;

      const lastSeen = parseInt(localStorage.getItem(notifStorageKey) || '0', 10);
      const latest = getLatestNotifTime();

      if (latest > lastSeen) {
        notifDot.classList.add('show');
        notifBellBtn.classList.add('has-new');
      } else {
        notifDot.classList.remove('show');
        notifBellBtn.classList.remove('has-new');
      }
    }

    if (notifBellBtn && notifMenu) {
      notifBellBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        notifMenu.classList.toggle('open');

        if (notifMenu.classList.contains('open')) {
          localStorage.setItem(notifStorageKey, String(getLatestNotifTime()));
          updateNotifState();
        }
      });
    }

    document.addEventListener('click', function (e) {
      if (notifDropdown && notifMenu && !notifDropdown.contains(e.target)) {
        notifMenu.classList.remove('open');
      }
    });

    updateNotifState();

    const redeemForm = document.getElementById('redeemForm');
    const redeemOpenBtn = document.getElementById('redeemOpenBtn');
    const redeemConfirmBtn = document.getElementById('redeemConfirmBtn');

    const redeemConfirmModalEl = document.getElementById('redeemConfirmModal');
    const redeemResultModalEl = document.getElementById('redeemResultModal');

    const redeemConfirmModal = redeemConfirmModalEl ? new bootstrap.Modal(redeemConfirmModalEl) : null;
    const redeemResultModal = redeemResultModalEl ? new bootstrap.Modal(redeemResultModalEl) : null;

    let reloadAfterRedeem = false;

    function showRedeemResult(type, message) {
      const iconBox = document.getElementById('redeemResultIcon');
      const titleEl = document.getElementById('redeemResultTitle');
      const messageEl = document.getElementById('redeemResultMessage');

      if (!iconBox || !titleEl || !messageEl || !redeemResultModal) {
        alert(message);
        return;
      }

      iconBox.className = 'mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle';

      if (type === 'success') {
        iconBox.classList.add('bg-success-subtle', 'text-success');
        iconBox.innerHTML = '<i class="bi bi-check-circle fs-1"></i>';
        titleEl.textContent = 'Redeemed Successfully';
      } else if (type === 'warning') {
        iconBox.classList.add('bg-warning-subtle', 'text-warning');
        iconBox.innerHTML = '<i class="bi bi-exclamation-triangle fs-1"></i>';
        titleEl.textContent = 'Not Enough Points';
      } else {
        iconBox.classList.add('bg-danger-subtle', 'text-danger');
        iconBox.innerHTML = '<i class="bi bi-x-circle fs-1"></i>';
        titleEl.textContent = 'Redeem Failed';
      }

      messageEl.textContent = message;
      redeemResultModal.show();
    }

    if (redeemOpenBtn && redeemConfirmModal) {
      redeemOpenBtn.addEventListener('click', function () {
        redeemConfirmModal.show();
      });
    }

    if (redeemConfirmBtn && redeemForm) {
      redeemConfirmBtn.addEventListener('click', function () {
        redeemConfirmBtn.disabled = true;
        redeemConfirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Redeeming...';

        fetch(redeemForm.action, {
          method: 'POST',
          body: new FormData(redeemForm),
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
          .then(res => res.json())
          .then(data => {
            if (redeemConfirmModal) {
              redeemConfirmModal.hide();
            }

            reloadAfterRedeem = !!data.success;

            showRedeemResult(
              data.type || (data.success ? 'success' : 'danger'),
              data.message || 'Action completed.'
            );
          })
          .catch(() => {
            if (redeemConfirmModal) {
              redeemConfirmModal.hide();
            }

            reloadAfterRedeem = false;
            showRedeemResult('danger', 'Something went wrong while redeeming points.');
          })
          .finally(() => {
            redeemConfirmBtn.disabled = false;
            redeemConfirmBtn.innerHTML = 'Yes, Redeem';
          });
      });
    }

    if (redeemResultModalEl) {
      redeemResultModalEl.addEventListener('hidden.bs.modal', function () {
        if (reloadAfterRedeem) {
          window.location.reload();
        }
      });
    }
  </script>
</body>
</html>
