<?php
// admin_module/Admin_Reports.php

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

/*
  DEFAULT TAB IS NOW GENERATE REPORT.
*/
$tab = strtolower(trim($_GET['tab'] ?? 'generate'));

if (!in_array($tab, ['generate', 'summary'], true)) {
    $tab = 'generate';
}

/*
  Added reservation report.
*/
$report = strtolower(trim($_GET['report'] ?? 'sitin'));

if (!in_array($report, ['sitin', 'feedback', 'reservation'], true)) {
    $report = 'sitin';
}

$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');

function cleanDate($date)
{
    if ($date === '') {
        return '';
    }

    $dt = DateTime::createFromFormat('Y-m-d', $date);

    return $dt && $dt->format('Y-m-d') === $date ? $date : '';
}

function hasColumn(mysqli $conn, string $table, string $column): bool
{
    $sql = "
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ";

    $stmt = $conn->prepare($sql);

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

function durationLabel($minutes)
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

function shortText($text, $limit = 90)
{
    $text = trim((string)$text);

    return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
}

function reportTitle(string $report): string
{
    if ($report === 'feedback') {
        return 'Feedback Report';
    }

    if ($report === 'reservation') {
        return 'Reservation Report';
    }

    return 'Sit-in Report';
}

function fetchSitinRows(mysqli $conn, string $from, string $to): array
{
    $hasPcNumber = hasColumn($conn, 'sitin_records', 'pc_number');
    $pcSelect = $hasPcNumber ? 'sr.pc_number' : 'NULL AS pc_number';

    $where = [];
    $params = [];
    $types = '';

    if ($from !== '') {
        $where[] = 'DATE(sr.login_time) >= ?';
        $params[] = $from;
        $types .= 's';
    }

    if ($to !== '') {
        $where[] = 'DATE(sr.login_time) <= ?';
        $params[] = $to;
        $types .= 's';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
            sr.id,
            sr.studentid,
            sr.fullname,
            sr.purpose,
            sr.lab,
            $pcSelect,
            sr.login_time,
            sr.logout_time,
            sr.status,
            sr.session_at_sitin,
            TIMESTAMPDIFF(MINUTE, sr.login_time, COALESCE(sr.logout_time, NOW())) AS duration_minutes
        FROM sitin_records sr
        $whereSql
        ORDER BY sr.login_time DESC
        LIMIT 300
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    return $rows;
}

function fetchFeedbackRows(mysqli $conn, string $from, string $to): array
{
    $where = [];
    $params = [];
    $types = '';

    if ($from !== '') {
        $where[] = 'DATE(f.created_at) >= ?';
        $params[] = $from;
        $types .= 's';
    }

    if ($to !== '') {
        $where[] = 'DATE(f.created_at) <= ?';
        $params[] = $to;
        $types .= 's';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
            f.id,
            s.studentid,
            CONCAT(s.lastname, ', ', s.firstname, ' ', s.middlename) AS student_name,
            sr.purpose,
            sr.lab,
            f.issue_type,
            f.feedback_text,
            f.created_at
        FROM feedback f
        INNER JOIN students s ON s.id = f.student_id
        INNER JOIN sitin_records sr ON sr.id = f.sitin_id
        $whereSql
        ORDER BY f.created_at DESC
        LIMIT 300
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    return $rows;
}

function fetchReservationRows(mysqli $conn, string $from, string $to): array
{
    $where = [];
    $params = [];
    $types = '';

    if ($from !== '') {
        $where[] = 'lr.reservation_date >= ?';
        $params[] = $from;
        $types .= 's';
    }

    if ($to !== '') {
        $where[] = 'lr.reservation_date <= ?';
        $params[] = $to;
        $types .= 's';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
            lr.id,
            lr.studentid,
            COALESCE(NULLIF(lr.fullname, ''), CONCAT(s.lastname, ', ', s.firstname, ' ', s.middlename)) AS fullname,
            lr.purpose,
            lr.lab,
            lr.pc_number,
            lr.reservation_date,
            lr.reservation_time,
            lr.reservation_end_time,
            lr.status,
            lr.created_at,
            lr.updated_at
        FROM lab_reservations lr
        LEFT JOIN students s ON s.id = lr.student_id
        $whereSql
        ORDER BY lr.reservation_date DESC, lr.reservation_time DESC, lr.created_at DESC
        LIMIT 300
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    return $rows;
}

$from = cleanDate($from);
$to = cleanDate($to);

$sitinRows = fetchSitinRows($conn, $from, $to);
$feedbackRows = fetchFeedbackRows($conn, $from, $to);
$reservationRows = fetchReservationRows($conn, $from, $to);

if ($report === 'feedback') {
    $rows = $feedbackRows;
} elseif ($report === 'reservation') {
    $rows = $reservationRows;
} else {
    $rows = $sitinRows;
}

$totalSitins = count($sitinRows);
$activeSitins = 0;
$doneSitins = 0;
$totalMinutes = 0;
$uniqueStudents = [];
$labCount = [];
$purposeCount = [];
$sitinByDate = [];

foreach ($sitinRows as $row) {
    $status = strtolower($row['status'] ?? '');

    if ($status === 'active') {
        $activeSitins++;
    }

    if ($status === 'done') {
        $doneSitins++;
    }

    $totalMinutes += max(0, (int)($row['duration_minutes'] ?? 0));

    if (!empty($row['studentid'])) {
        $uniqueStudents[$row['studentid']] = true;
    }

    $lab = $row['lab'] ?: 'N/A';
    $purpose = $row['purpose'] ?: 'N/A';

    $labCount[$lab] = ($labCount[$lab] ?? 0) + 1;
    $purposeCount[$purpose] = ($purposeCount[$purpose] ?? 0) + 1;

    $date = !empty($row['login_time']) ? date('M d', strtotime($row['login_time'])) : 'N/A';
    $sitinByDate[$date] = ($sitinByDate[$date] ?? 0) + 1;
}

$feedbackByDate = [];
$feedbackIssueCount = [];
$feedbackStudents = [];

foreach ($feedbackRows as $row) {
    $date = !empty($row['created_at']) ? date('M d', strtotime($row['created_at'])) : 'N/A';
    $feedbackByDate[$date] = ($feedbackByDate[$date] ?? 0) + 1;

    $issue = $row['issue_type'] ?: 'General';
    $feedbackIssueCount[$issue] = ($feedbackIssueCount[$issue] ?? 0) + 1;

    if (!empty($row['studentid'])) {
        $feedbackStudents[$row['studentid']] = true;
    }
}

$reservationByDate = [];
$reservationStatusCount = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'cancelled' => 0,
    'done' => 0
];

$reservationStudents = [];
$reservationLabCount = [];

foreach ($reservationRows as $row) {
    $date = !empty($row['reservation_date']) ? date('M d', strtotime($row['reservation_date'])) : 'N/A';
    $reservationByDate[$date] = ($reservationByDate[$date] ?? 0) + 1;

    $status = strtolower($row['status'] ?? 'pending');

    if (!isset($reservationStatusCount[$status])) {
        $reservationStatusCount[$status] = 0;
    }

    $reservationStatusCount[$status]++;

    if (!empty($row['studentid'])) {
        $reservationStudents[$row['studentid']] = true;
    }

    $lab = $row['lab'] ?: 'N/A';
    $reservationLabCount[$lab] = ($reservationLabCount[$lab] ?? 0) + 1;
}

arsort($labCount);
arsort($purposeCount);
arsort($feedbackIssueCount);
arsort($reservationLabCount);

ksort($sitinByDate);
ksort($feedbackByDate);
ksort($reservationByDate);

$topLab = $labCount ? array_key_first($labCount) : 'N/A';
$topPurpose = $purposeCount ? array_key_first($purposeCount) : 'N/A';
$topIssue = $feedbackIssueCount ? array_key_first($feedbackIssueCount) : 'N/A';
$topReservationLab = $reservationLabCount ? array_key_first($reservationLabCount) : 'N/A';

if ($report === 'feedback') {
    $chartLabels = array_keys($feedbackByDate);
    $chartValues = array_values($feedbackByDate);
} elseif ($report === 'reservation') {
    $chartLabels = array_keys($reservationByDate);
    $chartValues = array_values($reservationByDate);
} else {
    $chartLabels = array_keys($sitinByDate);
    $chartValues = array_values($sitinByDate);
}

$queryString = http_build_query([
    'report' => $report,
    'from' => $from,
    'to' => $to
]);

$filterLabel = ($from ?: 'Start') . ' to ' . ($to ?: 'End');

$summaryCards = [];

if ($report === 'reservation') {
    $summaryCards = [
        ['label' => 'Total Reservations', 'value' => count($reservationRows), 'icon' => 'bi-calendar-check', 'color' => 'primary'],
        ['label' => 'Approved', 'value' => $reservationStatusCount['approved'] ?? 0, 'icon' => 'bi-check-circle', 'color' => 'success'],
        ['label' => 'Pending', 'value' => $reservationStatusCount['pending'] ?? 0, 'icon' => 'bi-hourglass-split', 'color' => 'warning'],
        ['label' => 'Cancelled / Rejected', 'value' => ($reservationStatusCount['cancelled'] ?? 0) + ($reservationStatusCount['rejected'] ?? 0), 'icon' => 'bi-x-circle', 'color' => 'danger'],
    ];
} elseif ($report === 'feedback') {
    $summaryCards = [
        ['label' => 'Feedback Records', 'value' => count($feedbackRows), 'icon' => 'bi-chat-square-text', 'color' => 'primary'],
        ['label' => 'Feedback Students', 'value' => count($feedbackStudents), 'icon' => 'bi-people', 'color' => 'success'],
        ['label' => 'Top Issue', 'value' => shortText($topIssue, 15), 'icon' => 'bi-exclamation-circle', 'color' => 'warning'],
        ['label' => 'Report Rows', 'value' => count($rows), 'icon' => 'bi-table', 'color' => 'info'],
    ];
} else {
    $summaryCards = [
        ['label' => 'Total Sit-ins', 'value' => $totalSitins, 'icon' => 'bi-pc-display', 'color' => 'primary'],
        ['label' => 'Completed', 'value' => $doneSitins, 'icon' => 'bi-check-circle', 'color' => 'success'],
        ['label' => 'Active', 'value' => $activeSitins, 'icon' => 'bi-activity', 'color' => 'warning'],
        ['label' => 'Total Hours', 'value' => round($totalMinutes / 60, 1), 'icon' => 'bi-clock-history', 'color' => 'info'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>UC – Report Summary</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    .report-hero {
      background: linear-gradient(135deg, #1e3a8a, #2563eb);
      color: #ffffff;
      border-radius: 22px;
      box-shadow: 0 18px 42px rgba(37, 99, 235, 0.22);
      overflow: hidden;
      position: relative;
    }

    .report-hero::after {
      content: "";
      position: absolute;
      right: -70px;
      top: -70px;
      width: 210px;
      height: 210px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.13);
    }

    .report-hero * {
      position: relative;
      z-index: 1;
    }

    .stat-card {
      border: 0;
      border-radius: 18px;
      box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    }

    .stat-icon {
      width: 46px;
      height: 46px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
    }

    .nav-pills .nav-link {
      border-radius: 999px;
      font-weight: 600;
    }

    .report-table-card {
      border-radius: 18px;
      overflow: hidden;
    }

    .table th {
      white-space: nowrap;
      font-size: 13px;
    }

    .table td {
      font-size: 13px;
      vertical-align: middle;
    }
  </style>
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
      <span style="font-size:13px; color:#6b7280; padding: 0 8px;">
        <?= $admin_name ?>
      </span>
      <div class="nav-divider"></div>
      <a class="nav-link" href="../controllers/auth/logout.php">Log out</a>
    </div>
  </nav>

  <div class="admin-layout">
    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
      <div class="container-fluid py-4">

        <div class="report-hero p-4 mb-4">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
              <h1 class="h3 fw-bold mb-2">
                <i class="bi bi-bar-chart-line me-2"></i>
                Report Summary
              </h1>
              <p class="mb-0 text-white-50">
                Generate sit-in, feedback, and reservation reports with cleaner PDF and CSV export.
              </p>
            </div>

            <div class="text-end">
              <div class="small text-white-50">Current Filter</div>
              <div class="fw-bold"><?= htmlspecialchars(reportTitle($report)) ?></div>
              <div class="small text-white-50"><?= htmlspecialchars($filterLabel) ?></div>
            </div>
          </div>
        </div>

        <ul class="nav nav-pills mb-4">
          <li class="nav-item">
            <a class="nav-link <?= $tab === 'generate' ? 'active' : '' ?>" href="Admin_Reports.php?<?= htmlspecialchars(http_build_query(['tab' => 'generate', 'report' => $report, 'from' => $from, 'to' => $to])) ?>">
              <i class="bi bi-file-earmark-arrow-down me-1"></i>
              Generate Report
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link <?= $tab === 'summary' ? 'active' : '' ?>" href="Admin_Reports.php?<?= htmlspecialchars(http_build_query(['tab' => 'summary', 'report' => $report, 'from' => $from, 'to' => $to])) ?>">
              <i class="bi bi-speedometer2 me-1"></i>
              Report Summary
            </a>
          </li>
        </ul>

        <?php if ($tab === 'summary'): ?>

          <div class="row g-3 mb-4">
            <?php foreach ($summaryCards as $card): ?>
              <div class="col-md-3">
                <div class="card stat-card h-100">
                  <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                      <div class="text-muted small"><?= htmlspecialchars($card['label']) ?></div>
                      <div class="h4 fw-bold mb-0"><?= htmlspecialchars((string)$card['value']) ?></div>
                    </div>

                    <div class="stat-icon bg-<?= htmlspecialchars($card['color']) ?>-subtle text-<?= htmlspecialchars($card['color']) ?>">
                      <i class="bi <?= htmlspecialchars($card['icon']) ?>"></i>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="row g-4">
            <div class="col-xl-8">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                      <h2 class="h5 mb-0"><?= htmlspecialchars(reportTitle($report)) ?> Trend</h2>
                      <small class="text-muted">
                        Graph is based on selected report type and date range.
                      </small>
                    </div>

                    <span class="badge text-bg-primary">
                      <?= count($rows) ?> record(s)
                    </span>
                  </div>
                </div>

                <div class="card-body p-4">
                  <canvas id="reportChart" height="120"></canvas>
                </div>
              </div>
            </div>

            <div class="col-xl-4">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                  <h2 class="h5 mb-0">Quick Insights</h2>
                </div>

                <div class="card-body p-4">
                  <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 d-flex justify-content-between">
                      <span>Top Sit-in Lab</span>
                      <strong><?= htmlspecialchars($topLab) ?></strong>
                    </div>

                    <div class="list-group-item px-0 d-flex justify-content-between">
                      <span>Top Purpose</span>
                      <strong><?= htmlspecialchars(shortText($topPurpose, 20)) ?></strong>
                    </div>

                    <div class="list-group-item px-0 d-flex justify-content-between">
                      <span>Top Feedback Issue</span>
                      <strong><?= htmlspecialchars(shortText($topIssue, 20)) ?></strong>
                    </div>

                    <div class="list-group-item px-0 d-flex justify-content-between">
                      <span>Top Reservation Lab</span>
                      <strong><?= htmlspecialchars($topReservationLab) ?></strong>
                    </div>

                    <div class="list-group-item px-0 d-flex justify-content-between">
                      <span>Reservation Students</span>
                      <strong><?= count($reservationStudents) ?></strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        <?php else: ?>

          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
              <h2 class="h5 mb-0">
                <i class="bi bi-funnel me-1 text-primary"></i>
                Generate Report
              </h2>
              <small class="text-muted">
                Choose report type and date range, then export as PDF or CSV.
              </small>
            </div>

            <div class="card-body p-4">
              <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="generate">

                <div class="col-lg-3">
                  <label class="form-label fw-semibold">Report Type</label>
                  <select class="form-select" name="report">
                    <option value="sitin" <?= $report === 'sitin' ? 'selected' : '' ?>>Sit-in Report</option>
                    <option value="reservation" <?= $report === 'reservation' ? 'selected' : '' ?>>Reservation Report</option>
                    <option value="feedback" <?= $report === 'feedback' ? 'selected' : '' ?>>Feedback Report</option>
                  </select>
                </div>

                <div class="col-lg-3">
                  <label class="form-label fw-semibold">Date From</label>
                  <input type="date" class="form-control" name="from" value="<?= htmlspecialchars($from) ?>">
                </div>

                <div class="col-lg-3">
                  <label class="form-label fw-semibold">Date To</label>
                  <input type="date" class="form-control" name="to" value="<?= htmlspecialchars($to) ?>">
                </div>

                <div class="col-lg-3">
                  <button class="btn btn-primary w-100" type="submit">
                    <i class="bi bi-funnel me-1"></i>
                    Apply Filter
                  </button>
                </div>
              </form>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <div class="card border-0 shadow-sm">
                <div class="card-body">
                  <div class="text-muted small">Report Type</div>
                  <div class="h5 mb-0"><?= htmlspecialchars(reportTitle($report)) ?></div>
                </div>
              </div>
            </div>

            <div class="col-md-4">
              <div class="card border-0 shadow-sm">
                <div class="card-body">
                  <div class="text-muted small">Records Shown</div>
                  <div class="h5 mb-0"><?= count($rows) ?></div>
                </div>
              </div>
            </div>

            <div class="col-md-4">
              <div class="card border-0 shadow-sm">
                <div class="card-body">
                  <div class="text-muted small">Filter</div>
                  <div class="h6 mb-0"><?= htmlspecialchars($filterLabel) ?></div>
                </div>
              </div>
            </div>
          </div>

          <div class="card border-0 shadow-sm report-table-card">
            <div class="card-header bg-white border-0 pt-4 px-4">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                  <h2 class="h5 mb-0">Report Table</h2>
                  <small class="text-muted">
                    Preview of records included in the report.
                  </small>
                </div>

                <div class="d-flex gap-2">
                  <a class="btn btn-outline-danger btn-sm" href="../controllers/reports/export_report.php?<?= $queryString ?>&format=pdf" target="_blank">
                    <i class="bi bi-file-earmark-pdf me-1"></i>
                    PDF
                  </a>

                  <a class="btn btn-outline-success btn-sm" href="../controllers/reports/export_report.php?<?= $queryString ?>&format=csv">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                    CSV
                  </a>
                </div>
              </div>
            </div>

            <div class="card-body p-0">
              <div class="table-responsive">
                <?php if ($report === 'feedback'): ?>

                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th class="ps-4">#</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Lab</th>
                        <th>Purpose</th>
                        <th>Issue</th>
                        <th>Feedback</th>
                        <th class="pe-4">Date</th>
                      </tr>
                    </thead>

                    <tbody>
                      <?php if (!$rows): ?>
                        <tr>
                          <td colspan="8" class="text-center text-muted py-5">
                            No feedback records found.
                          </td>
                        </tr>
                      <?php endif; ?>

                      <?php foreach ($rows as $index => $row): ?>
                        <tr>
                          <td class="ps-4"><?= $index + 1 ?></td>
                          <td><?= htmlspecialchars($row['studentid']) ?></td>
                          <td><?= htmlspecialchars($row['student_name']) ?></td>
                          <td><?= htmlspecialchars($row['lab']) ?></td>
                          <td><?= htmlspecialchars($row['purpose']) ?></td>
                          <td><?= htmlspecialchars($row['issue_type'] ?: 'General') ?></td>
                          <td><?= htmlspecialchars(shortText($row['feedback_text'], 80)) ?></td>
                          <td class="pe-4"><?= htmlspecialchars($row['created_at']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>

                <?php elseif ($report === 'reservation'): ?>

                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th class="ps-4">#</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Purpose</th>
                        <th>Lab</th>
                        <th>PC No.</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th class="pe-4">Created</th>
                      </tr>
                    </thead>

                    <tbody>
                      <?php if (!$rows): ?>
                        <tr>
                          <td colspan="10" class="text-center text-muted py-5">
                            No reservation records found.
                          </td>
                        </tr>
                      <?php endif; ?>

                      <?php foreach ($rows as $index => $row): ?>
                        <tr>
                          <td class="ps-4"><?= $index + 1 ?></td>
                          <td><?= htmlspecialchars($row['studentid']) ?></td>
                          <td><?= htmlspecialchars($row['fullname']) ?></td>
                          <td><?= htmlspecialchars($row['purpose']) ?></td>
                          <td><?= htmlspecialchars($row['lab']) ?></td>
                          <td><?= !empty($row['pc_number']) ? 'PC ' . htmlspecialchars($row['pc_number']) : 'N/A' ?></td>
                          <td><?= htmlspecialchars($row['reservation_date']) ?></td>
                          <td>
                            <?= htmlspecialchars(date('h:i A', strtotime($row['reservation_time']))) ?>
                            -
                            <?= htmlspecialchars(date('h:i A', strtotime($row['reservation_end_time']))) ?>
                          </td>
                          <td>
                            <span class="badge text-bg-primary">
                              <?= htmlspecialchars(ucfirst($row['status'])) ?>
                            </span>
                          </td>
                          <td class="pe-4"><?= htmlspecialchars($row['created_at']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>

                <?php else: ?>

                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th class="ps-4">#</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Purpose</th>
                        <th>Lab</th>
                        <th>PC No.</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Duration</th>
                        <th>Status</th>
                      </tr>
                    </thead>

                    <tbody>
                      <?php if (!$rows): ?>
                        <tr>
                          <td colspan="10" class="text-center text-muted py-5">
                            No sit-in records found.
                          </td>
                        </tr>
                      <?php endif; ?>

                      <?php foreach ($rows as $index => $row): ?>
                        <tr>
                          <td class="ps-4"><?= $index + 1 ?></td>
                          <td><?= htmlspecialchars($row['studentid']) ?></td>
                          <td><?= htmlspecialchars($row['fullname']) ?></td>
                          <td><?= htmlspecialchars($row['purpose']) ?></td>
                          <td><?= htmlspecialchars($row['lab']) ?></td>
                          <td><?= !empty($row['pc_number']) ? 'PC ' . htmlspecialchars($row['pc_number']) : 'N/A' ?></td>
                          <td><?= htmlspecialchars($row['login_time']) ?></td>
                          <td><?= htmlspecialchars($row['logout_time'] ?: 'N/A') ?></td>
                          <td><?= durationLabel($row['duration_minutes']) ?></td>
                          <td>
                            <span class="badge text-bg-<?= strtolower($row['status']) === 'active' ? 'success' : 'secondary' ?>">
                              <?= htmlspecialchars($row['status']) ?>
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>

                <?php endif; ?>
              </div>
            </div>
          </div>

        <?php endif; ?>

      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script>
    const toggler = document.getElementById('navToggler');
    const navLinks = document.getElementById('navLinks');
    const sidebar = document.getElementById('sidebar');

    if (toggler) {
      toggler.addEventListener('click', () => {
        if (navLinks) {
          navLinks.classList.toggle('open');
        }

        if (sidebar) {
          sidebar.classList.toggle('open');
        }
      });
    }

    const chartCanvas = document.getElementById('reportChart');

    if (chartCanvas) {
      new Chart(chartCanvas, {
        type: 'bar',
        data: {
          labels: <?= json_encode($chartLabels) ?>,
          datasets: [{
            label: <?= json_encode(reportTitle($report)) ?>,
            data: <?= json_encode($chartValues) ?>,
            borderWidth: 1,
            borderRadius: 8
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
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
  </script>
</body>
</html>