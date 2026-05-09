<?php
// student_module/sitin_history.php
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

$student_id = (int)($_SESSION['student_id'] ?? 0);
$firstname  = htmlspecialchars($_SESSION['firstname'] ?? '');
$lastname   = htmlspecialchars($_SESSION['lastname'] ?? '');
$course     = htmlspecialchars($_SESSION['course'] ?? '');
$yearlvl    = htmlspecialchars($_SESSION['yearlvl'] ?? '');
$email      = htmlspecialchars($_SESSION['email'] ?? '');
$addrs      = htmlspecialchars($_SESSION['addrs'] ?? '');
$initials   = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));

require_once '../controllers/announcements/student_notifications.php';

$table_columns = [];
$column_result = $conn->query("SHOW COLUMNS FROM sitin_records");
if ($column_result) {
    while ($col = $column_result->fetch_assoc()) {
        $table_columns[] = $col['Field'];
    }
}

$timeout_column = null;
foreach (['logout_time', 'time_out', 'end_time', 'ended_at', 'updated_at'] as $possible_column) {
    if (in_array($possible_column, $table_columns, true)) {
        $timeout_column = $possible_column;
        break;
    }
}

$timeout_select = $timeout_column ? ", sr.$timeout_column AS time_out" : ", NULL AS time_out";

$records = [];
$stmt = $conn->prepare("
    SELECT 
        sr.id,
        sr.purpose,
        sr.lab,
        sr.session_at_sitin,
        sr.login_time,
        sr.status
        $timeout_select,
        f.id AS feedback_id,
        f.issue_type,
        f.feedback_text,
        f.created_at AS feedback_created_at
    FROM sitin_records sr
    LEFT JOIN feedback f ON sr.id = f.sitin_id
    WHERE sr.student_id = ?
    ORDER BY sr.login_time DESC
");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $records[] = $row;
}
$stmt->close();

$total_records = count($records);
$active_count  = count(array_filter($records, fn($r) => $r['status'] === 'active'));
$done_count    = count(array_filter($records, fn($r) => $r['status'] === 'done'));

function minutesBetween($startDatetime, $endDatetime) {
    if (!$startDatetime || !$endDatetime) return 0;
    $start = strtotime($startDatetime);
    $end = strtotime($endDatetime);
    if (!$start || !$end || $end <= $start) return 0;
    return (int) floor(($end - $start) / 60);
}

function formatDurationSummary($minutes) {
    $minutes = (int)$minutes;
    if ($minutes <= 0) return '0 min';
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;
    if ($hours > 0 && $mins > 0) return $hours . ' hr ' . $mins . ' min';
    if ($hours > 0) return $hours . ' hr';
    return $mins . ' min';
}

$total_sitin_minutes = 0;
$duration_session_count = 0;
$longest_session_minutes = 0;

foreach ($records as $record) {
    if (($record['status'] ?? '') !== 'done') continue;
    $minutes = minutesBetween($record['login_time'] ?? null, $record['time_out'] ?? null);
    if ($minutes > 0) {
        $total_sitin_minutes += $minutes;
        $duration_session_count++;
        $longest_session_minutes = max($longest_session_minutes, $minutes);
    }
}

$average_session_minutes = $duration_session_count > 0
    ? (int) round($total_sitin_minutes / $duration_session_count)
    : 0;

$total_sitin_hours_label   = formatDurationSummary($total_sitin_minutes);
$average_session_label     = formatDurationSummary($average_session_minutes);
$longest_session_label     = formatDurationSummary($longest_session_minutes);
$number_of_sessions        = $total_records;

$lab_counts     = [];
$purpose_counts = [];
$month_counts   = [];
$duration_labels  = [];
$duration_minutes = [];

foreach ($records as $record) {
    $labName = trim($record['lab'] ?? 'Unknown Lab');
    if ($labName === '') $labName = 'Unknown Lab';
    $lab_counts[$labName] = ($lab_counts[$labName] ?? 0) + 1;

    $purposeName = trim($record['purpose'] ?? 'Other');
    if ($purposeName === '') $purposeName = 'Other';
    $purpose_counts[$purposeName] = ($purpose_counts[$purposeName] ?? 0) + 1;

    if (!empty($record['login_time'])) {
        $monthKey   = date('Y-m', strtotime($record['login_time']));
        $monthLabel = date('M Y', strtotime($record['login_time']));
        if (!isset($month_counts[$monthKey])) {
            $month_counts[$monthKey] = ['label' => $monthLabel, 'count' => 0];
        }
        $month_counts[$monthKey]['count']++;
    }

    if (($record['status'] ?? '') === 'done') {
        $minutes = minutesBetween($record['login_time'] ?? null, $record['time_out'] ?? null);
        if ($minutes > 0) {
            $duration_labels[]  = 'Session #' . ($record['id'] ?? '');
            $duration_minutes[] = $minutes;
        }
    }
}

ksort($month_counts);

$chart_data = [
    'labs'      => ['labels' => array_keys($lab_counts),    'values' => array_values($lab_counts)],
    'purposes'  => ['labels' => array_keys($purpose_counts),'values' => array_values($purpose_counts)],
    'months'    => ['labels' => array_column($month_counts, 'label'), 'values' => array_column($month_counts, 'count')],
    'durations' => ['labels' => $duration_labels, 'values' => $duration_minutes],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>UC – Sit-in History</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.30.0/dist/tabler-icons.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/student.css">

  <style>
    /* ── Tab bar ─────────────────────────────────────────────── */
    .sitin-history-tabs {
      padding: 0 20px;
      border-bottom: 1px solid #e5e7eb;
    }
    .history-tab-panel { display: none; }
    .history-tab-panel.active { display: block; }
    .sitin-history-tabs .tab-btn {
      border: none; background: transparent; color: #64748b;
      padding: 13px 18px; font-size: 13px; font-weight: 800;
      font-family: 'Poppins', sans-serif; cursor: pointer;
      display: inline-flex; align-items: center; gap: 7px;
      border-bottom: 3px solid transparent; margin-bottom: -1px;
      transition: all .15s ease;
    }
    .sitin-history-tabs .tab-btn:hover { color: #1d3a6e; background: #f8fafc; }
    .sitin-history-tabs .tab-btn.active { color: #1d3a6e; border-bottom-color: #1d3a6e; }

    /* ── Summary panel wrapper ───────────────────────────────── */
    .ss-wrap { padding: 0; font-family: 'Poppins', sans-serif; }

    /* ── Hero stat cards ─────────────────────────────────────── */
    .ss-hero {
      display: grid;
      grid-template-columns: repeat(4, minmax(150px, 1fr));
      gap: 14px;
      padding: 20px;
    }
    .ss-stat {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 20px 18px 16px;
      position: relative;
      overflow: hidden;
      transition: transform .18s ease, box-shadow .18s ease;
    }
    .ss-stat:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.07);
    }
    .ss-stat-icon {
      font-size: 22px;
      margin-bottom: 12px;
      display: block;
      opacity: .75;
    }
    .ss-stat-val {
      font-size: 26px;
      font-weight: 800;
      color: #0f172a;
      line-height: 1;
      letter-spacing: -.5px;
    }
    .ss-stat-label {
      font-size: 10.5px;
      font-weight: 700;
      color: #94a3b8;
      margin-top: 7px;
      text-transform: uppercase;
      letter-spacing: .6px;
    }
    .ss-stat-sub {
      font-size: 12px;
      color: #64748b;
      margin-top: 4px;
      line-height: 1.5;
    }
    .ss-stat-accent {
      position: absolute;
      bottom: 0; left: 0;
      height: 4px; width: 100%;
      border-radius: 0 0 16px 16px;
    }
    .ss-stat-bg-glow {
      position: absolute;
      top: -20px; right: -20px;
      width: 80px; height: 80px;
      border-radius: 50%;
      opacity: .06;
    }

    /* ── Info note ───────────────────────────────────────────── */
    .ss-note {
      margin: 0 20px 16px;
      padding: 12px 16px;
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      border-radius: 12px;
      font-size: 12.5px;
      font-weight: 500;
      color: #1d4ed8;
      display: flex;
      align-items: flex-start;
      gap: 9px;
      line-height: 1.6;
    }
    .ss-note i { flex-shrink: 0; margin-top: 1px; font-size: 16px; }

    /* ── Chart grid ──────────────────────────────────────────── */
    .ss-body {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      padding: 0 20px 24px;
    }
    .ss-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 18px;
      transition: box-shadow .18s ease;
    }
    .ss-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.05); }
    .ss-card-full { grid-column: 1 / -1; }

    .ss-card-title {
      font-size: 11.5px;
      font-weight: 800;
      color: #475569;
      text-transform: uppercase;
      letter-spacing: .6px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 7px;
    }
    .ss-card-title i { font-size: 16px; }

    /* ── Animated horizontal bar list ────────────────────────── */
    .ss-bar-list { display: flex; flex-direction: column; gap: 13px; }
    .ss-bar-row { display: flex; flex-direction: column; gap: 5px; }
    .ss-bar-meta {
      display: flex;
      justify-content: space-between;
      font-size: 12.5px;
    }
    .ss-bar-name { font-weight: 700; color: #1e293b; }
    .ss-bar-val  { color: #64748b; font-weight: 600; }
    .ss-bar-track {
      height: 8px;
      border-radius: 99px;
      background: #f1f5f9;
      overflow: hidden;
    }
    .ss-bar-fill {
      height: 100%;
      border-radius: 99px;
      width: 0%;
      transition: width 1.1s cubic-bezier(.4,0,.2,1);
    }

    /* ── Doughnut legend ─────────────────────────────────────── */
    .ss-legend {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 12px;
    }
    .ss-legend-item {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 12px;
      font-weight: 600;
      color: #334155;
    }
    .ss-legend-dot {
      width: 10px; height: 10px;
      border-radius: 3px;
      flex-shrink: 0;
    }

    /* ── Chart canvas wrappers ───────────────────────────────── */
    .ss-chart-wrap { position: relative; height: 220px; width: 100%; }
    .ss-chart-wrap-tall { position: relative; height: 240px; width: 100%; }

    /* ── Empty state for charts ──────────────────────────────── */
    .ss-chart-empty {
      height: 220px;
      display: flex; align-items: center; justify-content: center;
      flex-direction: column; gap: 8px;
      color: #94a3b8;
      font-size: 13px;
      font-weight: 600;
      background: #f8fafc;
      border-radius: 12px;
      text-align: center;
    }
    .ss-chart-empty i { font-size: 28px; opacity: .5; }

    /* ── Donut centre label ──────────────────────────────────── */
    .ss-donut-wrap { position: relative; }
    .ss-donut-centre {
      position: absolute;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      text-align: center;
      pointer-events: none;
    }
    .ss-donut-centre-val {
      font-size: 22px;
      font-weight: 800;
      color: #0f172a;
      line-height: 1;
    }
    .ss-donut-centre-lbl {
      font-size: 10px;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: .5px;
      margin-top: 3px;
    }


    /* ── Dark mode toggle ───────────────────────────────────── */
    .dark-toggle {
      border: 1px solid #e5e7eb;
      background: #fff;
      color: #1d3a6e;
      border-radius: 999px;
      height: 38px;
      padding: 0 13px;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      font-size: 12px;
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      transition: all .18s ease;
    }

    .dark-toggle:hover {
      background: #eff6ff;
      transform: translateY(-1px);
    }

    .dark-toggle i {
      font-size: 16px;
    }

    /* ── Dark mode page colours ─────────────────────────────── */
    body.dark-mode {
      background: #0f172a !important;
      color: #e5e7eb;
    }

    body.dark-mode .uc-nav,
    body.dark-mode .sidebar,
    body.dark-mode .page-card,
    body.dark-mode .toolbar,
    body.dark-mode .timeline-wrap,
    body.dark-mode .pagination-bar,
    body.dark-mode .ss-stat,
    body.dark-mode .ss-card,
    body.dark-mode .notif-menu {
      background: #111827 !important;
      border-color: #263244 !important;
      color: #e5e7eb !important;
      box-shadow: 0 10px 30px rgba(0,0,0,.25);
    }

    body.dark-mode .admin-main,
    body.dark-mode .admin-layout {
      background: #0f172a !important;
    }

    body.dark-mode .nav-title,
    body.dark-mode .nav-link,
    body.dark-mode .sidebar-link,
    body.dark-mode .timeline-field-value,
    body.dark-mode .ss-stat-val,
    body.dark-mode .ss-donut-centre-val,
    body.dark-mode .ss-bar-name,
    body.dark-mode .header-right {
      color: #e5e7eb !important;
    }

    body.dark-mode .nav-sub,
    body.dark-mode .sidebar-section,
    body.dark-mode .timeline-field-label,
    body.dark-mode .ss-stat-label,
    body.dark-mode .ss-stat-sub,
    body.dark-mode .ss-card-title,
    body.dark-mode .ss-bar-val,
    body.dark-mode .ss-legend-item,
    body.dark-mode .timeline-feedback-empty,
    body.dark-mode .timeline-time,
    body.dark-mode label {
      color: #94a3b8 !important;
    }

    body.dark-mode .sidebar-link:hover,
    body.dark-mode .sidebar-link.active,
    body.dark-mode .sitin-history-tabs .tab-btn:hover {
      background: #1e293b !important;
      color: #60a5fa !important;
    }

    body.dark-mode .sitin-history-tabs,
    body.dark-mode .tab-bar,
    body.dark-mode .timeline-card,
    body.dark-mode .timeline-feedback-row,
    body.dark-mode .ss-note,
    body.dark-mode .ss-chart-empty,
    body.dark-mode .notif-menu-header,
    body.dark-mode .notif-menu-item {
      background: #162033 !important;
      border-color: #334155 !important;
      color: #dbeafe !important;
    }

    body.dark-mode .timeline-card:hover,
    body.dark-mode .ss-card:hover,
    body.dark-mode .ss-stat:hover {
      box-shadow: 0 12px 30px rgba(0,0,0,.35);
    }

    body.dark-mode .sitin-history-tabs .tab-btn {
      color: #94a3b8;
    }

    body.dark-mode .sitin-history-tabs .tab-btn.active {
      color: #60a5fa !important;
      border-bottom-color: #60a5fa !important;
    }

    body.dark-mode .timeline::before {
      background: #334155 !important;
    }

    body.dark-mode .timeline-item::before {
      background: #0f172a !important;
      border-color: #475569 !important;
    }

    body.dark-mode .date-badge,
    body.dark-mode .page-card-header {
      background: linear-gradient(135deg, #0b2f6b, #1d4ed8) !important;
      color: #fff !important;
    }

    body.dark-mode .search-input,
    body.dark-mode .entries-select,
    body.dark-mode input,
    body.dark-mode select,
    body.dark-mode textarea {
      background: #0f172a !important;
      color: #e5e7eb !important;
      border-color: #334155 !important;
    }

    body.dark-mode .search-input::placeholder,
    body.dark-mode textarea::placeholder,
    body.dark-mode input::placeholder {
      color: #64748b !important;
    }

    body.dark-mode .ss-bar-track {
      background: #263244 !important;
    }

    body.dark-mode .dark-toggle {
      background: #1e293b;
      color: #facc15;
      border-color: #334155;
    }

    body.dark-mode .notif-bell-btn {
      background: #1e293b !important;
      border-color: #334155 !important;
    }

    body.dark-mode .notif-bell-btn svg {
      color: #60a5fa !important;
    }

    body.dark-mode #editModal > div,
    body.dark-mode #feedbackModal > div {
      background: #111827 !important;
      color: #e5e7eb !important;
    }

    body.dark-mode #editModal form div,
    body.dark-mode #feedbackModal div {
      color: #e5e7eb !important;
    }

    body.dark-mode .feedback-btn,
    body.dark-mode button[onclick="submitFeedback()"] {
      background: #2563eb !important;
      color: #fff !important;
    }

    /* ── Responsive ──────────────────────────────────────────── */
    @media (max-width: 900px) {
      .ss-body { grid-template-columns: 1fr; }
      .ss-card-full { grid-column: 1; }
    }
    @media (max-width: 900px) {
      .ss-hero { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 480px) {
      .ss-hero { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body class="student-sitin-history-page">
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
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"></path>
            <path d="M10 21a2 2 0 0 0 4 0"></path>
          </svg>
          <span class="notif-dot" id="notifDot"></span>
        </button>

        <div class="notif-menu" id="notifMenu">
          <div class="notif-menu-header">Notifications</div>
          <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notif): ?>
              <div class="notif-menu-item">
                <div class="notif-type <?= htmlspecialchars($notif['type']) ?>">
                  <?= $notif['type'] === 'announcement' ? 'Announcement' : 'Session' ?>
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
        <i class="ti ti-moon"></i>
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

    <aside class="sidebar" id="sidebar">
      <div class="sidebar-section" style="margin-top:0;">Main</div>

      <a class="sidebar-link" href="student_dashboard.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>

      <a class="sidebar-link" href="#" onclick="openModal(); return false;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Edit Profile
      </a>

      <a class="sidebar-link" href="reservation.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Reservation
      </a>

      <hr class="sidebar-divider">
      <div class="sidebar-section">Records</div>

      <a class="sidebar-link" href="announcements.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
        Announcements
      </a>

      <a class="sidebar-link" href="session_table.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
        Session Table
      </a>

      <a class="sidebar-link active" href="sitin_history.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Sit-in History
      </a>
    </aside>

    <main class="admin-main">
      <div class="page-card">

        <div class="page-card-header">
          <h4>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            My Sit-in History
          </h4>
          <div class="header-right">
            <div class="header-avatar"><?= $initials ?></div>
            <?= $firstname . ' ' . $lastname ?>
          </div>
        </div>

        <!-- Tab bar -->
        <div class="tab-bar sitin-history-tabs">
          <button type="button" class="tab-btn active" id="tabHistoryBtn" onclick="switchHistoryTab('history')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
            </svg>
            Sit-in History
          </button>

          <button type="button" class="tab-btn" id="tabSummaryBtn" onclick="switchHistoryTab('summary')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="20" x2="18" y2="10"/>
              <line x1="12" y1="20" x2="12" y2="4"/>
              <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            Sit-in Summary
          </button>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             HISTORY TAB
        ════════════════════════════════════════════════════════ -->
        <div class="history-tab-panel active" id="historyPanel">
          <div class="toolbar">
            <div class="toolbar-left">
              <label style="font-size:13px;color:#6b7280;">Show</label>
              <select class="entries-select" id="entriesSelect">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="20">20</option>
                <option value="50">50</option>
              </select>
              <label style="font-size:13px;color:#6b7280;">entries</label>
            </div>
            <input type="text" class="search-input" id="searchInput" placeholder="🔍 Search purpose, lab, status, feedback...">
          </div>

          <?php if (empty($records)): ?>
            <div class="empty-state">📋 No sit-in history found yet.</div>
          <?php else: ?>
            <div class="timeline-wrap">
              <div class="timeline" id="timelineContainer"></div>
              <div id="noResults" class="no-results" style="display:none;">No records match your search.</div>
            </div>
            <div class="pagination-bar">
              <span id="tableInfo">Showing 0 entries</span>
              <div class="pagination-btns" id="paginationBtns"></div>
            </div>
          <?php endif; ?>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             SUMMARY TAB  (redesigned)
        ════════════════════════════════════════════════════════ -->
        <div class="history-tab-panel" id="summaryPanel">
          <div class="ss-wrap">

            <!-- Hero stat cards -->
            <div class="ss-hero">

              <div class="ss-stat">
                <div class="ss-stat-bg-glow" style="background:#3266ad;"></div>
                <i class="ti ti-clock ss-stat-icon" style="color:#3266ad;" aria-hidden="true"></i>
                <div class="ss-stat-val"><?= htmlspecialchars($total_sitin_hours_label) ?></div>
                <div class="ss-stat-label">Total sit-in hours</div>
                <div class="ss-stat-sub">Across all completed sessions</div>
                <div class="ss-stat-accent" style="background:linear-gradient(90deg,#3266ad,#60a5fa);"></div>
              </div>

              <div class="ss-stat">
                <div class="ss-stat-bg-glow" style="background:#1d9e75;"></div>
                <i class="ti ti-calendar-event ss-stat-icon" style="color:#1d9e75;" aria-hidden="true"></i>
                <div class="ss-stat-val"><?= $number_of_sessions ?></div>
                <div class="ss-stat-label">Total sessions</div>
                <div class="ss-stat-sub"><?= $done_count ?> completed · <?= $active_count ?> active</div>
                <div class="ss-stat-accent" style="background:linear-gradient(90deg,#1d9e75,#6ee7b7);"></div>
              </div>

              <div class="ss-stat">
                <div class="ss-stat-bg-glow" style="background:#ba7517;"></div>
                <i class="ti ti-activity ss-stat-icon" style="color:#ba7517;" aria-hidden="true"></i>
                <div class="ss-stat-val"><?= htmlspecialchars($average_session_label) ?></div>
                <div class="ss-stat-label">Average session</div>
                <div class="ss-stat-sub">Based on <?= $duration_session_count ?> session<?= $duration_session_count === 1 ? '' : 's' ?></div>
                <div class="ss-stat-accent" style="background:linear-gradient(90deg,#ba7517,#fbbf24);"></div>
              </div>

              <div class="ss-stat">
                <div class="ss-stat-bg-glow" style="background:#d4537e;"></div>
                <i class="ti ti-trophy ss-stat-icon" style="color:#d4537e;" aria-hidden="true"></i>
                <div class="ss-stat-val"><?= htmlspecialchars($longest_session_label) ?></div>
                <div class="ss-stat-label">Longest session</div>
                <div class="ss-stat-sub">Personal best record</div>
                <div class="ss-stat-accent" style="background:linear-gradient(90deg,#d4537e,#f9a8d4);"></div>
              </div>

            </div>

            <!-- Info note -->
            <div class="ss-note">
              <i class="ti ti-info-circle" aria-hidden="true"></i>
              Duration statistics only count sessions with a recorded <strong>logout_time</strong>.
              Active or incomplete sessions are excluded from hour calculations.
            </div>

            <!-- Chart grid -->
            <div class="ss-body">

              <!-- Sessions per lab — animated horizontal bars -->
              <div class="ss-card">
                <div class="ss-card-title">
                  <i class="ti ti-building" aria-hidden="true"></i> Sessions per lab
                </div>
                <?php if (empty($lab_counts)): ?>
                  <div class="ss-chart-empty">
                    <i class="ti ti-building-off" aria-hidden="true"></i>
                    No lab data yet.
                  </div>
                <?php else: ?>
                  <div class="ss-bar-list" id="labBars"></div>
                <?php endif; ?>
              </div>

              <!-- Sessions by purpose — doughnut -->
              <div class="ss-card">
                <div class="ss-card-title">
                  <i class="ti ti-target" aria-hidden="true"></i> Sessions by purpose
                </div>
                <?php if (empty($purpose_counts)): ?>
                  <div class="ss-chart-empty">
                    <i class="ti ti-chart-pie-off" aria-hidden="true"></i>
                    No purpose data yet.
                  </div>
                <?php else: ?>
                  <div class="ss-legend" id="purposeLegend"></div>
                  <div class="ss-donut-wrap" style="position:relative;">
                    <div class="ss-chart-wrap" style="height:190px;">
                      <canvas id="purposeChart"
                        role="img"
                        aria-label="Doughnut chart showing sit-in sessions grouped by purpose">
                        Sessions by purpose.
                      </canvas>
                    </div>
                    <div class="ss-donut-centre" id="donutCentre">
                      <div class="ss-donut-centre-val" id="donutVal"><?= array_sum($purpose_counts) ?></div>
                      <div class="ss-donut-centre-lbl">sessions</div>
                    </div>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Monthly trend — area line -->
              <div class="ss-card ss-card-full">
                <div class="ss-card-title">
                  <i class="ti ti-chart-line" aria-hidden="true"></i> Monthly activity trend
                </div>
                <?php if (empty($month_counts)): ?>
                  <div class="ss-chart-empty">
                    <i class="ti ti-chart-line-off" aria-hidden="true"></i>
                    No monthly data yet.
                  </div>
                <?php else: ?>
                  <div class="ss-chart-wrap-tall">
                    <canvas id="monthChart"
                      role="img"
                      aria-label="Line chart of monthly sit-in sessions over time">
                      Monthly sessions.
                    </canvas>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Completed session durations — coloured bar -->
              <div class="ss-card ss-card-full">
                <div class="ss-card-title">
                  <i class="ti ti-ruler-2" aria-hidden="true"></i> Completed session durations
                </div>
                <?php if (empty($duration_minutes)): ?>
                  <div class="ss-chart-empty">
                    <i class="ti ti-hourglass-off" aria-hidden="true"></i>
                    No completed sessions with time-out recorded yet.
                  </div>
                <?php else: ?>
                  <div class="ss-chart-wrap-tall">
                    <canvas id="durationChart"
                      role="img"
                      aria-label="Bar chart of individual completed session durations in minutes">
                      Session durations in minutes.
                    </canvas>
                  </div>
                <?php endif; ?>
              </div>

            </div><!-- /.ss-body -->
          </div><!-- /.ss-wrap -->
        </div><!-- /#summaryPanel -->

      </div><!-- /.page-card -->
    </main>
  </div><!-- /.admin-layout -->

  <!-- ═══════════════════════════════════════════════════════════
       EDIT PROFILE MODAL
  ════════════════════════════════════════════════════════════ -->
  <div id="editModal" style="display:none; position:fixed; inset:0; z-index:9998; background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:100%; max-width:540px; max-height:90vh; overflow-y:auto; margin:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.2); font-family:'Poppins',sans-serif; overflow:hidden;">
      <div style="background:#1d3a6e; color:#fff; padding:16px 24px; display:flex; align-items:center; justify-content:space-between;">
        <span style="font-size:14px; font-weight:600;">Edit Profile</span>
        <button type="button" onclick="closeModal()" style="background:transparent; border:none; color:#fff; font-size:20px; cursor:pointer; line-height:1;">✕</button>
      </div>
      <div style="padding:24px;">
        <form action="../controllers/student/update_profile.php" method="POST">
          <input type="hidden" name="student_id" value="<?= (int)$student_id ?>">
          <input type="hidden" name="studentid"  value="<?= htmlspecialchars($_SESSION['studentid'] ?? '') ?>">
          <input type="hidden" name="middlename"  value="<?= htmlspecialchars($_SESSION['middlename'] ?? '') ?>">
          <input type="hidden" name="redirect"    value="student">
          <?php foreach ([
            ['firstname','First Name','text',$firstname],
            ['lastname','Last Name','text',$lastname],
            ['course','Course','text',$course],
            ['yearlvl','Year Level','text',$yearlvl],
            ['email','Email','email',$email],
            ['addrs','Address','text',$addrs],
          ] as [$name,$label,$type,$val]): ?>
          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;"><?= $label ?></label>
            <input type="<?= $type ?>" name="<?= $name ?>" value="<?= $val ?>" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; color:#111827;">
          </div>
          <?php endforeach; ?>
          <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" onclick="closeModal()" style="padding:9px 20px; border:1px solid #d1d5db; border-radius:8px; background:#fff; font-size:13px; font-weight:500; font-family:'Poppins',sans-serif; cursor:pointer; color:#374151;">Cancel</button>
            <button type="submit" style="padding:9px 24px; background:#1d3a6e; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer;">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════
       FEEDBACK MODAL
  ════════════════════════════════════════════════════════════ -->
  <div id="feedbackModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:100%; max-width:560px; margin:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.2); font-family:'Poppins',sans-serif; overflow:hidden;">
      <div style="background:#1d3a6e; color:#fff; padding:16px 24px; display:flex; align-items:center; justify-content:space-between;">
        <span style="font-size:14px; font-weight:600;">
          <svg style="margin-right:6px; vertical-align:-3px;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          </svg>
          Session Feedback
        </span>
        <button type="button" onclick="closeFeedbackModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">✕</button>
      </div>
      <div style="padding:24px;">
        <input type="hidden" id="feedbackSitinId">
        <div style="margin-bottom:14px;">
          <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Issue Type</label>
          <select id="feedbackIssueType" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:10px 13px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; background:#fff; color:#111827;">
            <option value="">-- Select issue type --</option>
            <option value="None">None</option>
            <option value="Hardware">Hardware</option>
            <option value="Software">Software</option>
            <option value="Network">Network</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div style="margin-bottom:14px;">
          <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Feedback</label>
          <textarea id="feedbackText" rows="5" placeholder="Write your feedback about this session..." style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:10px 13px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; color:#111827; resize:vertical;"></textarea>
        </div>
        <div id="feedbackError"   style="display:none; margin-bottom:10px; font-size:12px; color:#b91c1c;"></div>
        <div id="feedbackSuccess" style="display:none; margin-bottom:10px; font-size:12px; color:#166534;"></div>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
          <button type="button" onclick="closeFeedbackModal()" style="padding:9px 20px; border:1px solid #d1d5db; border-radius:8px; background:#fff; font-size:13px; font-weight:500; font-family:'Poppins',sans-serif; cursor:pointer; color:#374151;">Cancel</button>
          <button type="button" onclick="submitFeedback()" style="padding:9px 24px; background:#1d4ed8; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer;">Save Feedback</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script>
  /* ──────────────────────────────────────────────────────────
     DATA FROM PHP
  ────────────────────────────────────────────────────────── */
  const summaryChartData = <?= json_encode($chart_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  const allRecords       = <?= json_encode(array_values($records), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  const notifications    = <?= json_encode($notifications, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

  /* ──────────────────────────────────────────────────────────
     COLOUR PALETTE
  ────────────────────────────────────────────────────────── */
  const PALETTE = [
    '#3266ad','#1d9e75','#ba7517','#d4537e',
    '#7f77dd','#d85a30','#0f6e56','#993556'
  ];

  /* ──────────────────────────────────────────────────────────
     DARK MODE
  ────────────────────────────────────────────────────────── */
  function applyDarkMode() {
    const enabled = localStorage.getItem('uc_dark_mode') === 'enabled';
    document.body.classList.toggle('dark-mode', enabled);

    const btn = document.getElementById('darkModeToggle');
    if (btn) {
      btn.setAttribute('aria-pressed', enabled ? 'true' : 'false');
      btn.innerHTML = enabled
        ? '<i class="ti ti-sun"></i><span>Light</span>'
        : '<i class="ti ti-moon"></i><span>Dark</span>';
    }
  }

  function toggleDarkMode() {
    const enabled = !document.body.classList.contains('dark-mode');
    localStorage.setItem('uc_dark_mode', enabled ? 'enabled' : 'disabled');
    applyDarkMode();
  }

  applyDarkMode();

  /* ──────────────────────────────────────────────────────────
     SUMMARY CHARTS — only initialised once on first tab open
  ────────────────────────────────────────────────────────── */
  let summaryChartsInitialized = false;

  function initSummaryCharts() {
    if (summaryChartsInitialized || typeof Chart === 'undefined') return;
    summaryChartsInitialized = true;

    buildLabBars();
    buildPurposeChart();
    buildMonthChart();
    buildDurationChart();
  }

  /* Animated horizontal bar list for labs */
  function buildLabBars() {
    const wrap = document.getElementById('labBars');
    if (!wrap) return;
    const labels = summaryChartData.labs.labels;
    const values = summaryChartData.labs.values;
    if (!labels.length) return;
    const max = Math.max(...values, 1);

    labels.forEach((name, i) => {
      const pct = Math.round((values[i] / max) * 100);
      const color = PALETTE[i % PALETTE.length];
      const row = document.createElement('div');
      row.className = 'ss-bar-row';
      row.innerHTML = `
        <div class="ss-bar-meta">
          <span class="ss-bar-name">${escapeHtml(name)}</span>
          <span class="ss-bar-val">${values[i]} session${values[i] !== 1 ? 's' : ''}</span>
        </div>
        <div class="ss-bar-track">
          <div class="ss-bar-fill" id="ssbar${i}" style="background:${color};"></div>
        </div>`;
      wrap.appendChild(row);
    });

    /* Animate after paint */
    requestAnimationFrame(() => {
      setTimeout(() => {
        labels.forEach((_, i) => {
          const pct = Math.round((values[i] / Math.max(...values, 1)) * 100);
          const el = document.getElementById('ssbar' + i);
          if (el) el.style.width = pct + '%';
        });
      }, 80);
    });
  }

  /* Doughnut for purposes with custom legend */
  function buildPurposeChart() {
    const canvas = document.getElementById('purposeChart');
    const legend = document.getElementById('purposeLegend');
    if (!canvas) return;

    const labels = summaryChartData.purposes.labels;
    const values = summaryChartData.purposes.values;
    if (!labels.length) return;

    const total = values.reduce((a, b) => a + b, 0);

    /* Build custom legend */
    if (legend) {
      labels.forEach((lbl, i) => {
        const pct = Math.round(values[i] / total * 100);
        const item = document.createElement('span');
        item.className = 'ss-legend-item';
        item.innerHTML = `<span class="ss-legend-dot" style="background:${PALETTE[i % PALETTE.length]};"></span>${escapeHtml(lbl)} <span style="color:#94a3b8;margin-left:2px;">${pct}%</span>`;
        legend.appendChild(item);
      });
    }

    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: labels.map((_, i) => PALETTE[i % PALETTE.length]),
          borderWidth: 0,
          hoverOffset: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => ` ${ctx.label}: ${ctx.parsed} session${ctx.parsed !== 1 ? 's' : ''}`
            }
          }
        }
      }
    });
  }

  /* Area line chart for monthly trend */
  function buildMonthChart() {
    const canvas = document.getElementById('monthChart');
    if (!canvas) return;
    const labels = summaryChartData.months.labels;
    const values = summaryChartData.months.values;
    if (!labels.length) return;

    new Chart(canvas, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Sessions',
          data: values,
          borderColor: '#3266ad',
          backgroundColor: 'rgba(50,102,173,0.09)',
          borderWidth: 2.5,
          tension: 0.42,
          fill: true,
          pointBackgroundColor: '#3266ad',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { precision: 0, color: '#94a3b8', font: { family: 'Poppins', size: 11 } },
            grid: { color: 'rgba(0,0,0,0.05)' }
          },
          x: {
            ticks: { color: '#94a3b8', font: { family: 'Poppins', size: 11 } },
            grid: { display: false }
          }
        }
      }
    });
  }

  /* Coloured bar chart for session durations */
  function buildDurationChart() {
    const canvas = document.getElementById('durationChart');
    if (!canvas) return;
    const labels = summaryChartData.durations.labels;
    const values = summaryChartData.durations.values;
    if (!labels.length) return;

    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Minutes',
          data: values,
          backgroundColor: values.map((_, i) => PALETTE[i % PALETTE.length]),
          borderWidth: 0,
          borderRadius: 8,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => {
                const mins = ctx.parsed.y;
                const h = Math.floor(mins / 60);
                const m = mins % 60;
                const label = h > 0 ? (m > 0 ? `${h}h ${m}m` : `${h}h`) : `${m}m`;
                return ` ${label}`;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0,
              color: '#94a3b8',
              font: { family: 'Poppins', size: 11 },
              callback: v => v + ' min'
            },
            grid: { color: 'rgba(0,0,0,0.05)' }
          },
          x: {
            ticks: { color: '#94a3b8', font: { family: 'Poppins', size: 10 } },
            grid: { display: false }
          }
        }
      }
    });
  }

  /* ──────────────────────────────────────────────────────────
     TAB SWITCHING
  ────────────────────────────────────────────────────────── */
  function switchHistoryTab(tab) {
    document.getElementById('historyPanel').classList.toggle('active', tab === 'history');
    document.getElementById('summaryPanel').classList.toggle('active', tab === 'summary');
    document.getElementById('tabHistoryBtn').classList.toggle('active', tab === 'history');
    document.getElementById('tabSummaryBtn').classList.toggle('active', tab === 'summary');
    if (tab === 'summary') setTimeout(initSummaryCharts, 80);
  }

  /* ──────────────────────────────────────────────────────────
     TIMELINE / HISTORY PANEL
  ────────────────────────────────────────────────────────── */
  let currentPage = 1;

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatTime(dt) {
    return new Date(dt).toLocaleString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true });
  }

  function formatDateLabel(dt) {
    return new Date(dt).toLocaleDateString('en-PH', {
      weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });
  }

  function formatFeedbackDate(dt) {
    if (!dt) return '';
    const d = new Date(dt);
    if (isNaN(d.getTime())) return '';
    return d.toLocaleString('en-PH', {
      year: 'numeric', month: 'short', day: '2-digit',
      hour: '2-digit', minute: '2-digit', hour12: true
    });
  }

  function getFiltered() {
    const q = (document.getElementById('searchInput')?.value ?? '').trim().toLowerCase();
    if (!q) return allRecords;
    return allRecords.filter(r =>
      (r.purpose    || '').toLowerCase().includes(q) ||
      (r.lab        || '').toLowerCase().includes(q) ||
      (r.status     || '').toLowerCase().includes(q) ||
      (r.feedback_text || '').toLowerCase().includes(q) ||
      (r.issue_type || '').toLowerCase().includes(q)
    );
  }

  function renderTimeline() {
    const container  = document.getElementById('timelineContainer');
    const noResults  = document.getElementById('noResults');
    const tableInfo  = document.getElementById('tableInfo');
    const paginBtns  = document.getElementById('paginationBtns');
    if (!container) return;

    const perPage  = parseInt(document.getElementById('entriesSelect')?.value ?? 10, 10);
    const filtered = getFiltered();
    const total    = filtered.length;
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    if (currentPage > totalPages) currentPage = 1;

    const start    = (currentPage - 1) * perPage;
    const end      = Math.min(start + perPage, total);
    const pageData = filtered.slice(start, end);

    container.innerHTML = '';

    if (pageData.length === 0) {
      noResults.style.display = 'block';
      tableInfo.textContent   = 'Showing 0 entries';
      paginBtns.innerHTML     = '';
      return;
    }

    noResults.style.display = 'none';

    const groups = {};
    pageData.forEach(r => {
      const key = new Date(r.login_time).toDateString();
      if (!groups[key]) groups[key] = [];
      groups[key].push(r);
    });

    Object.entries(groups).forEach(([, items]) => {
      const group = document.createElement('div');
      group.className = 'timeline-date-group';

      const dateLabel = document.createElement('div');
      dateLabel.className = 'timeline-date-label';
      dateLabel.innerHTML = `<span class="date-badge">${escapeHtml(formatDateLabel(items[0].login_time))}</span>`;
      group.appendChild(dateLabel);

      items.forEach(r => {
        const isActive = r.status === 'active';
        const item = document.createElement('div');
        item.className = 'timeline-item' + (isActive ? ' active-item' : '');

        const card = document.createElement('div');
        card.className = 'timeline-card';

        card.innerHTML = `
          <div class="timeline-card-top">
            <span class="timeline-time">🕐 ${escapeHtml(formatTime(r.login_time))}</span>
            <span class="badge-status ${isActive ? 'active' : 'done'}">${isActive ? 'Active' : 'Done'}</span>
          </div>
          <div class="timeline-card-body">
            <div class="timeline-field">
              <span class="timeline-field-label">Purpose</span>
              <span class="timeline-field-value">${escapeHtml(r.purpose)}</span>
            </div>
            <div class="timeline-field">
              <span class="timeline-field-label">Lab</span>
              <span class="timeline-field-value">${escapeHtml(r.lab)}</span>
            </div>
            <div class="timeline-field">
              <span class="timeline-field-label">Session</span>
              <span class="timeline-field-value"><span class="badge-session">${escapeHtml(r.session_at_sitin)}</span></span>
            </div>
          </div>`;

        const feedbackRow  = document.createElement('div');
        feedbackRow.className = 'timeline-feedback-row';

        const feedbackInfo = document.createElement('div');
        feedbackInfo.className = 'timeline-feedback-info';

        if (r.feedback_text) {
          const when = formatFeedbackDate(r.feedback_created_at);
          feedbackInfo.innerHTML = `
            <div class="timeline-feedback-badge">${escapeHtml(r.issue_type || 'General')}</div>
            <div class="timeline-feedback-text">${escapeHtml(r.feedback_text)}</div>
            ${when ? `<div style="margin-top:6px;font-size:11px;color:#9ca3af;">Submitted: ${escapeHtml(when)}</div>` : ''}`;
        } else {
          feedbackInfo.innerHTML = `<div class="timeline-feedback-empty">No feedback submitted yet.</div>`;
        }

        const btnWrapper = document.createElement('div');
        btnWrapper.className = 'timeline-feedback-action';
        const btn = document.createElement('button');
        btn.className   = 'feedback-btn';
        btn.textContent = r.feedback_text ? 'Edit Feedback' : 'Give Feedback';
        btn.addEventListener('click', () => openFeedbackModal(r.id, r.issue_type || '', r.feedback_text || ''));
        btnWrapper.appendChild(btn);

        feedbackRow.appendChild(feedbackInfo);
        feedbackRow.appendChild(btnWrapper);
        card.appendChild(feedbackRow);
        item.appendChild(card);
        group.appendChild(item);
      });

      container.appendChild(group);
    });

    tableInfo.textContent = `Showing ${start + 1} to ${end} of ${total} entries`;
    paginBtns.innerHTML   = '';

    const prev = document.createElement('button');
    prev.className = 'page-btn';
    prev.textContent = '← Prev';
    prev.disabled = currentPage === 1;
    prev.onclick = () => { currentPage--; renderTimeline(); };
    paginBtns.appendChild(prev);

    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement('button');
      btn.className   = 'page-btn' + (i === currentPage ? ' active' : '');
      btn.textContent = i;
      btn.onclick     = () => { currentPage = i; renderTimeline(); };
      paginBtns.appendChild(btn);
    }

    const next = document.createElement('button');
    next.className   = 'page-btn';
    next.textContent = 'Next →';
    next.disabled    = currentPage === totalPages || total === 0;
    next.onclick     = () => { currentPage++; renderTimeline(); };
    paginBtns.appendChild(next);
  }

  document.getElementById('searchInput')?.addEventListener('input', () => { currentPage = 1; renderTimeline(); });
  document.getElementById('entriesSelect')?.addEventListener('change', () => { currentPage = 1; renderTimeline(); });

  <?php if (!empty($records)): ?>
  renderTimeline();
  <?php endif; ?>

  /* ──────────────────────────────────────────────────────────
     MODALS
  ────────────────────────────────────────────────────────── */
  function openModal()  { document.getElementById('editModal').style.display = 'flex'; }
  function closeModal() { document.getElementById('editModal').style.display = 'none'; }
  document.getElementById('editModal').addEventListener('click', e => { if (e.target === document.getElementById('editModal')) closeModal(); });

  function openFeedbackModal(sitinId, issueType = '', feedbackText = '') {
    document.getElementById('feedbackSitinId').value  = sitinId;
    document.getElementById('feedbackIssueType').value = issueType;
    document.getElementById('feedbackText').value      = feedbackText;
    document.getElementById('feedbackError').style.display   = 'none';
    document.getElementById('feedbackSuccess').style.display = 'none';
    document.getElementById('feedbackModal').style.display   = 'flex';
  }
  function closeFeedbackModal() { document.getElementById('feedbackModal').style.display = 'none'; }
  document.getElementById('feedbackModal').addEventListener('click', e => { if (e.target === document.getElementById('feedbackModal')) closeFeedbackModal(); });

  function submitFeedback() {
    const sitinId     = document.getElementById('feedbackSitinId').value;
    const issueType   = document.getElementById('feedbackIssueType').value;
    const feedbackText = document.getElementById('feedbackText').value.trim();
    const errorBox    = document.getElementById('feedbackError');
    const successBox  = document.getElementById('feedbackSuccess');

    errorBox.style.display = successBox.style.display = 'none';

    if (!issueType) { errorBox.textContent = 'Please select an issue type.'; errorBox.style.display = 'block'; return; }
    if (!feedbackText) { errorBox.textContent = 'Please enter your feedback.'; errorBox.style.display = 'block'; return; }

    const fd = new FormData();
    fd.append('sitin_id', sitinId);
    fd.append('issue_type', issueType);
    fd.append('feedback_text', feedbackText);

    fetch('../controllers/sitin/save_feedback.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.success) { errorBox.textContent = data.message || 'Failed to save feedback.'; errorBox.style.display = 'block'; return; }
        successBox.textContent = 'Feedback saved successfully.';
        successBox.style.display = 'block';
        const rec = allRecords.find(x => String(x.id) === String(sitinId));
        if (rec) { rec.issue_type = issueType; rec.feedback_text = feedbackText; rec.feedback_created_at = new Date().toISOString(); }
        setTimeout(() => { closeFeedbackModal(); renderTimeline(); }, 700);
      })
      .catch(() => { errorBox.textContent = 'Something went wrong. Please try again.'; errorBox.style.display = 'block'; });
  }

  /* ──────────────────────────────────────────────────────────
     NAV / NOTIFICATIONS
  ────────────────────────────────────────────────────────── */
  window.addEventListener('pageshow', e => { if (e.persisted) window.location.reload(); });
  document.getElementById('navToggler').addEventListener('click', () => {
    document.getElementById('navLinks').classList.toggle('open');
    document.getElementById('sidebar').classList.toggle('open');
  });

  const notifBellBtn  = document.getElementById('notifBellBtn');
  const notifMenu     = document.getElementById('notifMenu');
  const notifDot      = document.getElementById('notifDot');
  const notifDropdown = document.getElementById('notifDropdown');
  const notifKey      = 'student_notif_last_seen_<?= (int)$student_id ?>';

  function latestNotifTime() {
    return notifications.length ? Math.max(...notifications.map(n => new Date(n.created_at).getTime() || 0)) : 0;
  }

  function updateNotifState() {
    const seen   = parseInt(localStorage.getItem(notifKey) || '0', 10);
    const latest = latestNotifTime();
    notifDot.classList.toggle('show', latest > seen);
    notifBellBtn.classList.toggle('has-new', latest > seen);
  }

  notifBellBtn?.addEventListener('click', e => {
    e.stopPropagation();
    notifMenu.classList.toggle('open');
    if (notifMenu.classList.contains('open')) {
      localStorage.setItem(notifKey, String(latestNotifTime()));
      updateNotifState();
    }
  });

  document.addEventListener('click', e => {
    if (notifDropdown && !notifDropdown.contains(e.target)) notifMenu.classList.remove('open');
  });

  updateNotifState();
  </script>
</body>
</html>