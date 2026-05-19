<?php
// admin_module/Admin_Analytics.php

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

function analyticsTableExists(mysqli $conn, string $tableName): bool
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

    $stmt->bind_param('s', $tableName);
    $stmt->execute();

    $count = 0;
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return (int)$count > 0;
}

function analyticsColumnExists(mysqli $conn, string $tableName, string $columnName): bool
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

    $stmt->bind_param('ss', $tableName, $columnName);
    $stmt->execute();

    $count = 0;
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return (int)$count > 0;
}

function analyticsScalar(mysqli $conn, string $sql, array $params = [], string $types = ''): float
{
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_row() : null;

    $stmt->close();

    return (float)($row[0] ?? 0);
}

function analyticsRows(mysqli $conn, string $sql, array $params = [], string $types = ''): array
{
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $stmt->close();

    return $rows;
}

function formatAnalyticsNumber($value): string
{
    $value = (float)$value;

    if (abs($value - round($value)) < 0.001) {
        return number_format((int)round($value));
    }

    return number_format($value, 1);
}

function formatAnalyticsHours($minutes): string
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

$hasStudents = analyticsTableExists($conn, 'students');
$hasSitin = analyticsTableExists($conn, 'sitin_records');
$hasReservations = analyticsTableExists($conn, 'lab_reservations');
$hasFeedback = analyticsTableExists($conn, 'feedback');
$hasTestimonials = analyticsTableExists($conn, 'testimonials');
$hasRewardLogs = analyticsTableExists($conn, 'reward_point_logs');
$hasRedemptionLogs = analyticsTableExists($conn, 'reward_redemption_logs');

$softwareTable = '';
if (analyticsTableExists($conn, 'software_applications')) {
    $softwareTable = 'software_applications';
} elseif (analyticsTableExists($conn, 'software_availability')) {
    $softwareTable = 'software_availability';
}

$totalStudents = $hasStudents ? analyticsScalar($conn, "SELECT COUNT(*) FROM students") : 0;
$totalActiveSitins = $hasSitin ? analyticsScalar($conn, "SELECT COUNT(*) FROM sitin_records WHERE status = 'active'") : 0;
$totalCompletedSitins = $hasSitin ? analyticsScalar($conn, "SELECT COUNT(*) FROM sitin_records WHERE status IN ('done', 'completed')") : 0;
$totalReservations = $hasReservations ? analyticsScalar($conn, "SELECT COUNT(*) FROM lab_reservations") : 0;
$pendingReservations = $hasReservations ? analyticsScalar($conn, "SELECT COUNT(*) FROM lab_reservations WHERE status = 'pending'") : 0;
$approvedReservations = $hasReservations ? analyticsScalar($conn, "SELECT COUNT(*) FROM lab_reservations WHERE status = 'approved'") : 0;
$totalFeedback = $hasFeedback ? analyticsScalar($conn, "SELECT COUNT(*) FROM feedback") : 0;
$totalTestimonials = $hasTestimonials ? analyticsScalar($conn, "SELECT COUNT(*) FROM testimonials") : 0;

$todaySitins = $hasSitin ? analyticsScalar($conn, "SELECT COUNT(*) FROM sitin_records WHERE DATE(login_time) = CURDATE()") : 0;
$thisWeekSitins = $hasSitin ? analyticsScalar($conn, "SELECT COUNT(*) FROM sitin_records WHERE YEARWEEK(login_time, 1) = YEARWEEK(CURDATE(), 1)") : 0;
$thisMonthSitins = $hasSitin ? analyticsScalar($conn, "SELECT COUNT(*) FROM sitin_records WHERE YEAR(login_time) = YEAR(CURDATE()) AND MONTH(login_time) = MONTH(CURDATE())") : 0;

$totalMinutes = 0;
$avgMinutes = 0;

if ($hasSitin) {
    $hasDuration = analyticsColumnExists($conn, 'sitin_records', 'duration_minutes');

    if ($hasDuration) {
        $totalMinutes = analyticsScalar($conn, "
            SELECT COALESCE(SUM(duration_minutes), 0)
            FROM sitin_records
            WHERE status IN ('done', 'completed')
        ");

        $avgMinutes = analyticsScalar($conn, "
            SELECT COALESCE(AVG(NULLIF(duration_minutes, 0)), 0)
            FROM sitin_records
            WHERE status IN ('done', 'completed')
        ");
    } else {
        $totalMinutes = analyticsScalar($conn, "
            SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, login_time, logout_time)), 0)
            FROM sitin_records
            WHERE logout_time IS NOT NULL
        ");

        $avgMinutes = analyticsScalar($conn, "
            SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE, login_time, logout_time)), 0)
            FROM sitin_records
            WHERE logout_time IS NOT NULL
        ");
    }
}

$topLabs = $hasSitin ? analyticsRows($conn, "
    SELECT COALESCE(NULLIF(lab, ''), 'Unknown') AS label, COUNT(*) AS total
    FROM sitin_records
    GROUP BY COALESCE(NULLIF(lab, ''), 'Unknown')
    ORDER BY total DESC
    LIMIT 8
") : [];

$purposeRows = $hasSitin ? analyticsRows($conn, "
    SELECT COALESCE(NULLIF(purpose, ''), 'Unknown') AS label, COUNT(*) AS total
    FROM sitin_records
    GROUP BY COALESCE(NULLIF(purpose, ''), 'Unknown')
    ORDER BY total DESC
    LIMIT 8
") : [];

$courseRows = $hasStudents ? analyticsRows($conn, "
    SELECT COALESCE(NULLIF(course, ''), 'No Course') AS label, COUNT(*) AS total
    FROM students
    GROUP BY COALESCE(NULLIF(course, ''), 'No Course')
    ORDER BY total DESC
    LIMIT 8
") : [];

$reservationStatusRows = $hasReservations ? analyticsRows($conn, "
    SELECT COALESCE(NULLIF(status, ''), 'Unknown') AS label, COUNT(*) AS total
    FROM lab_reservations
    GROUP BY COALESCE(NULLIF(status, ''), 'Unknown')
    ORDER BY total DESC
") : [];

$hourlyRows = $hasSitin ? analyticsRows($conn, "
    SELECT HOUR(login_time) AS hour_no, COUNT(*) AS total
    FROM sitin_records
    GROUP BY HOUR(login_time)
    ORDER BY hour_no ASC
") : [];

$hourlyMap = array_fill(0, 24, 0);

foreach ($hourlyRows as $row) {
    $hour = (int)($row['hour_no'] ?? 0);

    if ($hour >= 0 && $hour <= 23) {
        $hourlyMap[$hour] = (int)$row['total'];
    }
}

$hourlyLabels = [];
$hourlyData = [];

for ($i = 0; $i < 24; $i++) {
    $hourlyLabels[] = date('g A', strtotime(sprintf('%02d:00:00', $i)));
    $hourlyData[] = $hourlyMap[$i];
}

$monthlyRows = $hasSitin ? analyticsRows($conn, "
    SELECT DATE_FORMAT(login_time, '%Y-%m') AS month_key, COUNT(*) AS total
    FROM sitin_records
    WHERE login_time >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(login_time, '%Y-%m')
    ORDER BY month_key ASC
") : [];

$monthlyLabels = [];
$monthlyData = [];

foreach ($monthlyRows as $row) {
    $monthlyLabels[] = date('M Y', strtotime(($row['month_key'] ?? date('Y-m')) . '-01'));
    $monthlyData[] = (int)$row['total'];
}

$topStudents = $hasSitin ? analyticsRows($conn, "
    SELECT
        COALESCE(NULLIF(fullname, ''), studentid, 'Unknown') AS fullname,
        COALESCE(NULLIF(studentid, ''), 'N/A') AS studentid,
        COUNT(*) AS total_sessions,
        COALESCE(SUM(duration_minutes), 0) AS total_minutes
    FROM sitin_records
    WHERE status IN ('done', 'completed')
    GROUP BY COALESCE(NULLIF(fullname, ''), studentid, 'Unknown'), COALESCE(NULLIF(studentid, ''), 'N/A')
    ORDER BY total_sessions DESC, total_minutes DESC
    LIMIT 10
") : [];

$rewardRows = $hasRewardLogs ? analyticsRows($conn, "
    SELECT DATE(created_at) AS log_date, COALESCE(SUM(points_added), 0) AS total
    FROM reward_point_logs
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at)
    ORDER BY log_date ASC
") : [];

$rewardLabels = [];
$rewardData = [];

foreach ($rewardRows as $row) {
    $rewardLabels[] = date('M d', strtotime($row['log_date']));
    $rewardData[] = (float)$row['total'];
}

$softwareRows = [];

if ($softwareTable !== '') {
    $softwareRows = analyticsRows($conn, "
        SELECT COALESCE(NULLIF(lab, ''), 'Unknown') AS label, COUNT(*) AS total
        FROM {$softwareTable}
        GROUP BY COALESCE(NULLIF(lab, ''), 'Unknown')
        ORDER BY total DESC
        LIMIT 8
    ");
}

$labLabels = array_column($topLabs, 'label');
$labData = array_map('intval', array_column($topLabs, 'total'));

$purposeLabels = array_column($purposeRows, 'label');
$purposeData = array_map('intval', array_column($purposeRows, 'total'));

$courseLabels = array_column($courseRows, 'label');
$courseData = array_map('intval', array_column($courseRows, 'total'));

$reservationStatusLabels = array_column($reservationStatusRows, 'label');
$reservationStatusData = array_map('intval', array_column($reservationStatusRows, 'total'));

$softwareLabels = array_column($softwareRows, 'label');
$softwareData = array_map('intval', array_column($softwareRows, 'total'));

$lastUpdated = date('M d, Y h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>UC – Analytics</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }

    .analytics-shell {
      width: min(1500px, 100%);
      margin: 0 auto;
      padding: 28px;
    }

    .analytics-hero {
      background: linear-gradient(135deg, #1e3a8a, #2563eb);
      color: #ffffff;
      border-radius: 24px;
      padding: 26px 30px;
      box-shadow: 0 20px 45px rgba(37, 99, 235, 0.25);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 18px;
      margin-bottom: 22px;
      overflow: hidden;
      position: relative;
    }

    .analytics-hero::after {
      content: "";
      position: absolute;
      width: 220px;
      height: 220px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.14);
      right: -80px;
      top: -90px;
    }

    .analytics-hero h1 {
      font-size: clamp(24px, 3vw, 36px);
      font-weight: 800;
      margin: 0 0 8px;
      letter-spacing: -0.8px;
      position: relative;
      z-index: 1;
    }

    .analytics-hero p {
      color: rgba(255, 255, 255, 0.9);
      margin: 0;
      font-size: 13px;
      line-height: 1.7;
      position: relative;
      z-index: 1;
    }

    .hero-meta {
      position: relative;
      z-index: 1;
      text-align: right;
      font-size: 12px;
      color: rgba(255, 255, 255, 0.88);
      min-width: 190px;
    }

    .hero-meta strong {
      display: block;
      font-size: 14px;
      color: #ffffff;
      margin-top: 4px;
    }

    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 16px;
      margin-bottom: 22px;
    }

    .kpi-card {
      background: #ffffff;
      border: 1px solid rgba(226, 232, 240, 0.95);
      border-radius: 20px;
      padding: 18px;
      box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      min-height: 112px;
    }

    .kpi-label {
      color: #64748b;
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .kpi-value {
      color: #111827;
      font-size: 28px;
      font-weight: 800;
      line-height: 1;
    }

    .kpi-sub {
      display: block;
      margin-top: 7px;
      color: #94a3b8;
      font-size: 11px;
      font-weight: 600;
    }

    .kpi-icon {
      width: 48px;
      height: 48px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      flex: 0 0 auto;
    }

    .icon-blue { background: #dbeafe; color: #1d4ed8; }
    .icon-green { background: #dcfce7; color: #15803d; }
    .icon-yellow { background: #fef3c7; color: #b45309; }
    .icon-purple { background: #ede9fe; color: #6d28d9; }
    .icon-red { background: #fee2e2; color: #b91c1c; }
    .icon-cyan { background: #cffafe; color: #0e7490; }

    .analytics-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
      align-items: start;
    }

    .analytics-card {
      background: #ffffff;
      border: 1px solid rgba(226, 232, 240, 0.95);
      border-radius: 22px;
      box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07);
      overflow: hidden;
    }

    .analytics-card.full {
      grid-column: 1 / -1;
    }

    .analytics-card-header {
      padding: 18px 20px;
      border-bottom: 1px solid #e5e7eb;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
    }

    .analytics-card-header h2 {
      margin: 0;
      color: #111827;
      font-size: 16px;
      font-weight: 800;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .analytics-card-header small {
      color: #64748b;
      font-size: 11px;
      font-weight: 600;
    }

    .analytics-card-body {
      padding: 20px;
    }

    .chart-box {
      height: 310px;
      position: relative;
    }

    .chart-box.short {
      height: 260px;
    }

    .table-wrap {
      overflow-x: auto;
    }

    .analytics-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }

    .analytics-table th {
      background: #f8fafc;
      color: #475569;
      font-size: 12px;
      font-weight: 800;
      padding: 12px;
      border-bottom: 1px solid #e5e7eb;
      white-space: nowrap;
    }

    .analytics-table td {
      padding: 12px;
      border-bottom: 1px solid #f1f5f9;
      color: #334155;
      vertical-align: middle;
      white-space: nowrap;
    }

    .rank-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 34px;
      height: 24px;
      border-radius: 999px;
      background: #eff6ff;
      color: #1d4ed8;
      font-size: 12px;
      font-weight: 800;
    }

    .empty-state {
      color: #94a3b8;
      font-size: 13px;
      text-align: center;
      padding: 36px 16px;
      line-height: 1.7;
    }

    @media (max-width: 1200px) {
      .kpi-grid,
      .analytics-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 768px) {
      .analytics-shell {
        padding: 18px;
      }

      .analytics-hero {
        flex-direction: column;
        align-items: flex-start;
      }

      .hero-meta {
        text-align: left;
      }

      .kpi-grid,
      .analytics-grid {
        grid-template-columns: 1fr;
      }

      .chart-box {
        height: 280px;
      }
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
      <div class="analytics-shell">
        <section class="analytics-hero">
          <div>
            <h1>System Analytics</h1>
            <p>
              Monitor sit-in activity, reservations, student usage, lab demand, reward activity,
              and system engagement in one dashboard.
            </p>
          </div>

          <div class="hero-meta">
            Last updated
            <strong><?= htmlspecialchars($lastUpdated) ?></strong>
          </div>
        </section>

        <section class="kpi-grid">
          <div class="kpi-card">
            <div>
              <div class="kpi-label">Total Students</div>
              <div class="kpi-value"><?= formatAnalyticsNumber($totalStudents) ?></div>
              <span class="kpi-sub">Registered accounts</span>
            </div>
            <div class="kpi-icon icon-blue"><i class="bi bi-people"></i></div>
          </div>

          <div class="kpi-card">
            <div>
              <div class="kpi-label">Active Sit-ins</div>
              <div class="kpi-value"><?= formatAnalyticsNumber($totalActiveSitins) ?></div>
              <span class="kpi-sub">Currently using labs</span>
            </div>
            <div class="kpi-icon icon-green"><i class="bi bi-pc-display"></i></div>
          </div>

          <div class="kpi-card">
            <div>
              <div class="kpi-label">Completed Sit-ins</div>
              <div class="kpi-value"><?= formatAnalyticsNumber($totalCompletedSitins) ?></div>
              <span class="kpi-sub">Total finished sessions</span>
            </div>
            <div class="kpi-icon icon-purple"><i class="bi bi-check2-circle"></i></div>
          </div>

          <div class="kpi-card">
            <div>
              <div class="kpi-label">Total Lab Hours</div>
              <div class="kpi-value"><?= formatAnalyticsHours($totalMinutes) ?></div>
              <span class="kpi-sub">Average: <?= formatAnalyticsHours((int)$avgMinutes) ?></span>
            </div>
            <div class="kpi-icon icon-yellow"><i class="bi bi-clock-history"></i></div>
          </div>

          <div class="kpi-card">
            <div>
              <div class="kpi-label">Today Sit-ins</div>
              <div class="kpi-value"><?= formatAnalyticsNumber($todaySitins) ?></div>
              <span class="kpi-sub">This week: <?= formatAnalyticsNumber($thisWeekSitins) ?></span>
            </div>
            <div class="kpi-icon icon-cyan"><i class="bi bi-calendar-day"></i></div>
          </div>

          <div class="kpi-card">
            <div>
              <div class="kpi-label">This Month</div>
              <div class="kpi-value"><?= formatAnalyticsNumber($thisMonthSitins) ?></div>
              <span class="kpi-sub">Monthly sit-in activity</span>
            </div>
            <div class="kpi-icon icon-blue"><i class="bi bi-graph-up-arrow"></i></div>
          </div>

          <div class="kpi-card">
            <div>
              <div class="kpi-label">Reservations</div>
              <div class="kpi-value"><?= formatAnalyticsNumber($totalReservations) ?></div>
              <span class="kpi-sub"><?= formatAnalyticsNumber($pendingReservations) ?> pending · <?= formatAnalyticsNumber($approvedReservations) ?> approved</span>
            </div>
            <div class="kpi-icon icon-red"><i class="bi bi-calendar-check"></i></div>
          </div>

          <div class="kpi-card">
            <div>
              <div class="kpi-label">Feedback / Reviews</div>
              <div class="kpi-value"><?= formatAnalyticsNumber($totalFeedback + $totalTestimonials) ?></div>
              <span class="kpi-sub"><?= formatAnalyticsNumber($totalFeedback) ?> feedback · <?= formatAnalyticsNumber($totalTestimonials) ?> testimonials</span>
            </div>
            <div class="kpi-icon icon-purple"><i class="bi bi-chat-square-heart"></i></div>
          </div>
        </section>

        <section class="analytics-grid">
          <div class="analytics-card">
            <div class="analytics-card-header">
              <h2><i class="bi bi-building"></i> Most Used Laboratories</h2>
              <small>Based on sit-in records</small>
            </div>
            <div class="analytics-card-body">
              <?php if ($labData): ?>
                <div class="chart-box short"><canvas id="labsChart"></canvas></div>
              <?php else: ?>
                <div class="empty-state">No lab usage data yet.</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="analytics-card">
            <div class="analytics-card-header">
              <h2><i class="bi bi-bullseye"></i> Common Purposes</h2>
              <small>Why students use the lab</small>
            </div>
            <div class="analytics-card-body">
              <?php if ($purposeData): ?>
                <div class="chart-box short"><canvas id="purposeChart"></canvas></div>
              <?php else: ?>
                <div class="empty-state">No purpose data yet.</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="analytics-card full">
            <div class="analytics-card-header">
              <h2><i class="bi bi-activity"></i> Sit-in Trend</h2>
              <small>Last 6 months</small>
            </div>
            <div class="analytics-card-body">
              <?php if ($monthlyData): ?>
                <div class="chart-box"><canvas id="monthlyChart"></canvas></div>
              <?php else: ?>
                <div class="empty-state">No monthly sit-in trend available yet.</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="analytics-card">
            <div class="analytics-card-header">
              <h2><i class="bi bi-clock"></i> Peak Usage Hours</h2>
              <small>Activity by hour</small>
            </div>
            <div class="analytics-card-body">
              <?php if (array_sum($hourlyData) > 0): ?>
                <div class="chart-box"><canvas id="hourlyChart"></canvas></div>
              <?php else: ?>
                <div class="empty-state">No hourly usage data yet.</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="analytics-card">
            <div class="analytics-card-header">
              <h2><i class="bi bi-calendar-check"></i> Reservation Status</h2>
              <small>Pending, approved, rejected, etc.</small>
            </div>
            <div class="analytics-card-body">
              <?php if ($reservationStatusData): ?>
                <div class="chart-box"><canvas id="reservationChart"></canvas></div>
              <?php else: ?>
                <div class="empty-state">No reservation data yet.</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="analytics-card">
            <div class="analytics-card-header">
              <h2><i class="bi bi-mortarboard"></i> Students by Course</h2>
              <small>Registered student distribution</small>
            </div>
            <div class="analytics-card-body">
              <?php if ($courseData): ?>
                <div class="chart-box"><canvas id="courseChart"></canvas></div>
              <?php else: ?>
                <div class="empty-state">No course data yet.</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="analytics-card">
            <div class="analytics-card-header">
              <h2><i class="bi bi-award"></i> Reward Points Trend</h2>
              <small>Last 14 days</small>
            </div>
            <div class="analytics-card-body">
              <?php if ($rewardData): ?>
                <div class="chart-box"><canvas id="rewardChart"></canvas></div>
              <?php else: ?>
                <div class="empty-state">No reward activity yet.</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="analytics-card">
            <div class="analytics-card-header">
              <h2><i class="bi bi-window-desktop"></i> Software by Lab</h2>
              <small>Installed software records</small>
            </div>
            <div class="analytics-card-body">
              <?php if ($softwareData): ?>
                <div class="chart-box"><canvas id="softwareChart"></canvas></div>
              <?php else: ?>
                <div class="empty-state">No software availability data yet.</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="analytics-card full">
            <div class="analytics-card-header">
              <h2><i class="bi bi-trophy"></i> Top Student Users</h2>
              <small>Based on completed sit-in sessions</small>
            </div>
            <div class="analytics-card-body">
              <div class="table-wrap">
                <table class="analytics-table js-admin-table">
                  <thead>
                    <tr>
                      <th>Rank</th>
                      <th>Student</th>
                      <th>Student ID</th>
                      <th>Completed Sessions</th>
                      <th>Total Duration</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!$topStudents): ?>
                      <tr>
                        <td colspan="5">
                          <div class="empty-state">No completed sit-in records yet.</div>
                        </td>
                      </tr>
                    <?php endif; ?>

                    <?php foreach ($topStudents as $index => $student): ?>
                      <tr>
                        <td><span class="rank-badge">#<?= $index + 1 ?></span></td>
                        <td><strong><?= htmlspecialchars($student['fullname']) ?></strong></td>
                        <td><?= htmlspecialchars($student['studentid']) ?></td>
                        <td><?= formatAnalyticsNumber($student['total_sessions']) ?></td>
                        <td><?= formatAnalyticsHours($student['total_minutes']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script>
    const navToggler = document.getElementById('navToggler');

    if (navToggler) {
      navToggler.addEventListener('click', () => {
        const navLinks = document.getElementById('navLinks');
        const sidebar = document.getElementById('sidebar');

        if (navLinks) navLinks.classList.toggle('open');
        if (sidebar) sidebar.classList.toggle('open');
      });
    }

    const chartData = {
      labs: {
        labels: <?= json_encode($labLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        data: <?= json_encode($labData, JSON_NUMERIC_CHECK) ?>
      },
      purpose: {
        labels: <?= json_encode($purposeLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        data: <?= json_encode($purposeData, JSON_NUMERIC_CHECK) ?>
      },
      monthly: {
        labels: <?= json_encode($monthlyLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        data: <?= json_encode($monthlyData, JSON_NUMERIC_CHECK) ?>
      },
      hourly: {
        labels: <?= json_encode($hourlyLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        data: <?= json_encode($hourlyData, JSON_NUMERIC_CHECK) ?>
      },
      reservation: {
        labels: <?= json_encode($reservationStatusLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        data: <?= json_encode($reservationStatusData, JSON_NUMERIC_CHECK) ?>
      },
      course: {
        labels: <?= json_encode($courseLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        data: <?= json_encode($courseData, JSON_NUMERIC_CHECK) ?>
      },
      reward: {
        labels: <?= json_encode($rewardLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        data: <?= json_encode($rewardData, JSON_NUMERIC_CHECK) ?>
      },
      software: {
        labels: <?= json_encode($softwareLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        data: <?= json_encode($softwareData, JSON_NUMERIC_CHECK) ?>
      }
    };

    function makeChart(canvasId, type, labels, data, label) {
      const canvas = document.getElementById(canvasId);

      if (!canvas) {
        return;
      }

      new Chart(canvas, {
        type: type,
        data: {
          labels: labels,
          datasets: [{
            label: label,
            data: data,
            borderWidth: 2,
            tension: 0.35,
            fill: type === 'line'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: type === 'doughnut' || type === 'pie'
            }
          },
          scales: type === 'doughnut' || type === 'pie'
            ? {}
            : {
                y: {
                  beginAtZero: true,
                  ticks: {
                    precision: 0
                  }
                }
              }
        }
      });
    }

    makeChart('labsChart', 'bar', chartData.labs.labels, chartData.labs.data, 'Sit-ins');
    makeChart('purposeChart', 'doughnut', chartData.purpose.labels, chartData.purpose.data, 'Purpose');
    makeChart('monthlyChart', 'line', chartData.monthly.labels, chartData.monthly.data, 'Sit-ins');
    makeChart('hourlyChart', 'bar', chartData.hourly.labels, chartData.hourly.data, 'Sit-ins');
    makeChart('reservationChart', 'pie', chartData.reservation.labels, chartData.reservation.data, 'Reservations');
    makeChart('courseChart', 'doughnut', chartData.course.labels, chartData.course.data, 'Students');
    makeChart('rewardChart', 'line', chartData.reward.labels, chartData.reward.data, 'Reward Points');
    makeChart('softwareChart', 'bar', chartData.software.labels, chartData.software.data, 'Software');
  </script>
  <script src="../assets/js/admin_table_tools.js"></script>
</body>
</html>
