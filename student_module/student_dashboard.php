<?php
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

$fullname = $lastname . ', ' . $firstname;
$initials = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));

/* FIX: get latest session credits from DB, not from session */
$session_credits = 0;
$stmtCredits = $conn->prepare("SELECT session_credits FROM students WHERE id = ? LIMIT 1");
$stmtCredits->bind_param('i', $student_id);
$stmtCredits->execute();
$resCredits = $stmtCredits->get_result();
if ($rowCredits = $resCredits->fetch_assoc()) {
    $session_credits = (int)$rowCredits['session_credits'];
}
$stmtCredits->close();

require_once '../includes/student_notifications.php';

$announcements = [];
$announcement_feed_result = $conn->query("
    SELECT id, title, message, posted_by, created_at
    FROM announcements
    ORDER BY created_at DESC
    LIMIT 10
");

if ($announcement_feed_result) {
    while ($row = $announcement_feed_result->fetch_assoc()) {
        $announcements[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>UC – Student Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    * { box-sizing: border-box; }
    body {
      background:
        radial-gradient(circle at top left, #dbeafe 0%, transparent 30%),
        radial-gradient(circle at top right, #e0e7ff 0%, transparent 35%),
        #eef2f7;
      font-family: 'Poppins', sans-serif;
      margin: 0;
      color: #111827;
    }

    @media (max-width: 768px) {
      .sidebar { display: none; }
      .sidebar.open {
        display: block;
        width: 100%;
        position: fixed;
        top: 60px;
        left: 0;
        bottom: 0;
        z-index: 99;
        overflow-y: auto;
      }
      .admin-main { padding: 1rem; }
      .dashboard-shell { padding: 1rem; }
    }

    .dashboard-shell {
      max-width: 1400px;
      margin: 0 auto;
      padding: 18px;
    }

    .hero {
      background: linear-gradient(135deg, #1d3a6e 0%, #1d4ed8 100%);
      color: #fff;
      border-radius: 22px;
      padding: 24px 26px;
      margin-bottom: 18px;
      box-shadow: 0 16px 40px rgba(29, 78, 216, 0.18);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 18px;
      flex-wrap: wrap;
    }

    .hero-left h1 {
      margin: 0 0 8px;
      font-size: 28px;
      font-weight: 800;
      letter-spacing: -0.5px;
    }

    .hero-left p {
      margin: 0;
      font-size: 13px;
      color: #dbeafe;
      max-width: 650px;
      line-height: 1.7;
    }

    .hero-badges {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .hero-pill {
      background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.18);
      color: #fff;
      padding: 9px 14px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
      backdrop-filter: blur(8px);
    }

    .dashboard {
      display: grid;
      grid-template-columns: 340px 1fr 320px;
      gap: 18px;
      align-items: start;
    }

    @media (max-width: 1180px) {
      .dashboard {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media (max-width: 760px) {
      .dashboard {
        grid-template-columns: 1fr;
      }
    }

    .panel {
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
      border: 1px solid rgba(255,255,255,0.7);
    }

    .panel-header {
      background: linear-gradient(135deg, #1d3a6e 0%, #274a86 100%);
      color: #fff;
      padding: 12px 16px;
      font-size: 13px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .panel-body {
      padding: 18px;
    }

    .student-card-top {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      padding-bottom: 16px;
      margin-bottom: 16px;
      border-bottom: 1px solid #eef2f7;
    }

    .student-avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background: linear-gradient(135deg, #1d3a6e, #2563eb);
      color: #fff;
      font-size: 30px;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 4px solid #fff;
      box-shadow: 0 10px 24px rgba(37, 99, 235, 0.20);
      overflow: hidden;
      margin-bottom: 12px;
    }

    .student-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .student-name {
      font-size: 18px;
      font-weight: 800;
      color: #111827;
      margin-bottom: 4px;
    }

    .student-sub {
      font-size: 12px;
      color: #6b7280;
    }

    .info-grid {
      display: grid;
      gap: 10px;
    }

    .info-row {
      display: flex;
      gap: 10px;
      align-items: flex-start;
      background: #f8fafc;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 12px 13px;
      font-size: 13px;
      color: #374151;
    }

    .info-icon {
      flex-shrink: 0;
      width: 16px;
      color: #1d4ed8;
      margin-top: 2px;
    }

    .info-label {
      font-weight: 700;
      color: #111827;
      margin-right: 4px;
    }

    .credit-badge {
      margin-top: 16px;
      background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
      border: 1px solid #bfdbfe;
      border-radius: 18px;
      padding: 16px;
      text-align: center;
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
    }

    .credit-badge small {
      display: block;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .6px;
      color: #1d4ed8;
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .credit-badge strong {
      display: block;
      font-size: 34px;
      line-height: 1;
      font-weight: 800;
      color: #1d3a6e;
      margin-bottom: 6px;
    }

    .credit-badge span {
      font-size: 13px;
      color: #374151;
      font-weight: 600;
    }

    .quick-links {
      display: grid;
      gap: 10px;
      margin-top: 16px;
    }

    .quick-link {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      text-decoration: none;
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 12px 14px;
      color: #111827;
      font-size: 13px;
      font-weight: 600;
      transition: all .15s ease;
    }

    .quick-link:hover {
      background: #eff6ff;
      border-color: #bfdbfe;
      transform: translateY(-1px);
      color: #1d4ed8;
    }

    .announce-feed {
      max-height: 560px;
      overflow-y: auto;
      padding-right: 4px;
    }

    .announce-item {
      background: #f8fafc;
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 14px;
      margin-bottom: 12px;
    }

    .announce-item:last-child {
      margin-bottom: 0;
    }

    .announce-title {
      font-size: 14px;
      font-weight: 800;
      color: #111827;
      margin-bottom: 6px;
    }

    .announce-meta {
      font-size: 11px;
      font-weight: 700;
      color: #1d4ed8;
      margin-bottom: 8px;
    }

    .announce-text {
      font-size: 13px;
      color: #4b5563;
      line-height: 1.7;
      white-space: pre-wrap;
      word-break: break-word;
    }

    .rules-body {
      max-height: 560px;
      overflow-y: auto;
      font-size: 13px;
      color: #374151;
      line-height: 1.8;
    }

    .rules-body h2 {
      font-size: 16px;
      font-weight: 800;
      text-align: center;
      color: #111827;
      margin-bottom: 4px;
    }

    .rules-body h3 {
      font-size: 13px;
      font-weight: 700;
      text-align: center;
      color: #6b7280;
      margin-bottom: 1rem;
    }

    .rules-body h4 {
      font-size: 13px;
      font-weight: 800;
      color: #111827;
      margin: 1rem 0 0.45rem;
    }

    .rules-body p {
      margin-bottom: 0.7rem;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .notif-dropdown {
      position: relative;
    }

    .notif-bell-btn {
      position: relative;
      width: 38px;
      height: 38px;
      border: 1px solid #e5e7eb;
      background: #fff;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.18s ease;
    }

    .notif-bell-btn:hover {
      background: #f8fafc;
      border-color: #cbd5e1;
      transform: translateY(-1px);
    }

    .notif-bell-btn svg {
      width: 18px;
      height: 18px;
      color: #1d3a6e;
    }

    .notif-bell-btn.has-new svg {
      animation: bellRing 1.2s ease-in-out infinite;
      transform-origin: top center;
    }

    @keyframes bellRing {
      0%, 100% { transform: rotate(0deg); }
      10% { transform: rotate(12deg); }
      20% { transform: rotate(-10deg); }
      30% { transform: rotate(8deg); }
      40% { transform: rotate(-6deg); }
      50% { transform: rotate(4deg); }
      60% { transform: rotate(-2deg); }
      70% { transform: rotate(0deg); }
    }

    .notif-dot {
      position: absolute;
      top: 5px;
      right: 6px;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: #ef4444;
      border: 2px solid #fff;
      display: none;
    }

    .notif-dot.show {
      display: block;
      animation: bellPulse 1.2s infinite;
    }

    @keyframes bellPulse {
      0%   { transform: scale(1); box-shadow: 0 0 0 0 rgba(239,68,68,0.7); }
      70%  { transform: scale(1.15); box-shadow: 0 0 0 10px rgba(239,68,68,0); }
      100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239,68,68,0); }
    }

    .notif-menu {
      display: none;
      position: absolute;
      top: calc(100% + 8px);
      right: 0;
      width: 320px;
      max-height: 360px;
      overflow-y: auto;
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.14);
      z-index: 3000;
    }

    .notif-menu.open {
      display: block;
    }

    .notif-menu-header {
      padding: 12px 14px;
      font-size: 13px;
      font-weight: 700;
      color: #1d3a6e;
      border-bottom: 1px solid #f3f4f6;
      background: #f8fafc;
    }

    .notif-menu-item {
      padding: 12px 14px;
      border-bottom: 1px solid #f3f4f6;
    }

    .notif-menu-item:last-child {
      border-bottom: none;
    }

    .notif-menu-item:hover {
      background: #f9fafb;
    }

    .notif-type {
      display: inline-block;
      font-size: 10px;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 999px;
      margin-bottom: 6px;
    }

    .notif-type.announcement {
      background: #dbeafe;
      color: #1d4ed8;
    }

    .notif-type.session {
      background: #dcfce7;
      color: #166534;
    }

    .notif-title {
      font-size: 12px;
      font-weight: 700;
      color: #111827;
      margin-bottom: 4px;
    }

    .notif-text {
      font-size: 12px;
      color: #4b5563;
      line-height: 1.5;
      margin-bottom: 5px;
    }

    .notif-time {
      font-size: 11px;
      color: #9ca3af;
    }

    .notif-empty {
      padding: 18px 14px;
      text-align: center;
      font-size: 12px;
      color: #9ca3af;
    }

    .announce-feed::-webkit-scrollbar,
    .rules-body::-webkit-scrollbar,
    .notif-menu::-webkit-scrollbar {
      width: 4px;
    }

    .announce-feed::-webkit-scrollbar-track,
    .rules-body::-webkit-scrollbar-track,
    .notif-menu::-webkit-scrollbar-track {
      background: #f3f4f6;
    }

    .announce-feed::-webkit-scrollbar-thumb,
    .rules-body::-webkit-scrollbar-thumb,
    .notif-menu::-webkit-scrollbar-thumb {
      background: #d1d5db;
      border-radius: 4px;
    }
  </style>
</head>
<body>

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

      <span style="font-size:13px; color:#6b7280; padding:0 4px;">
        <?= $firstname . ' ' . $lastname ?>
      </span>

      <div class="nav-divider"></div>
      <a class="nav-link" href="../logout.php">Log out</a>
    </div>
  </nav>

  <div class="admin-layout">

    <aside class="sidebar" id="sidebar">
      <div class="sidebar-section" style="margin-top:0;">Main</div>

      <a class="sidebar-link active" href="student_dashboard.php">
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
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
        </svg>
        Announcements
      </a>

      <a class="sidebar-link" href="sitin_history.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Sit-in History
      </a>

      <a class="sidebar-link" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Feedback
      </a>
    </aside>

    <main class="admin-main">
      <div class="dashboard-shell">
        <section class="hero">
          <div class="hero-left">
            <h1>Welcome back, <?= $firstname ?> 👋</h1>
            <p>
              Maayo nga pag-abot sa UC Sit-in System. Dinhi nimo makita ang imong profile,
              available sessions, latest announcements, ug important laboratory reminders.
            </p>
          </div>

          <div class="hero-badges">
            <div class="hero-pill"><?= $course ?: 'No Course' ?></div>
            <div class="hero-pill"><?= $yearlvl ?: 'No Year Level' ?></div>
            <div class="hero-pill"><?= $session_credits ?> Sessions Left</div>
          </div>
        </section>

        <div class="dashboard">

          <div class="panel">
            <div class="panel-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              Student Information
            </div>

            <div class="panel-body">
              <div class="student-card-top">
                <div class="student-avatar">
                  <?php if (!empty($_SESSION['avatar'])): ?>
                    <img src="<?= htmlspecialchars($_SESSION['avatar']) ?>" alt="avatar">
                  <?php else: ?>
                    <?= $initials ?>
                  <?php endif; ?>
                </div>
                <div class="student-name"><?= $fullname ?></div>
                <div class="student-sub"><?= $course ?> • <?= $yearlvl ?></div>
              </div>

              <div class="info-grid">
                <div class="info-row">
                  <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                  <span><span class="info-label">Course:</span> <?= $course ?></span>
                </div>

                <div class="info-row">
                  <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  <span><span class="info-label">Year:</span> <?= $yearlvl ?></span>
                </div>

                <div class="info-row">
                  <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                  <span><span class="info-label">Email:</span> <?= $email ?></span>
                </div>

                <div class="info-row">
                  <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  <span><span class="info-label">Address:</span> <?= $addrs ?></span>
                </div>
              </div>

              <div class="credit-badge">
                <small>Available Sessions</small>
                <strong><?= $session_credits ?></strong>
                <span>Remaining sit-in credits</span>
              </div>

              <div class="quick-links">
                <a class="quick-link" href="reservation.php">
                  <span>Make Reservation</span>
                  <span>→</span>
                </a>
                <a class="quick-link" href="sitin_history.php">
                  <span>View Sit-in History</span>
                  <span>→</span>
                </a>
                <a class="quick-link" href="announcements.php">
                  <span>Read Announcements</span>
                  <span>→</span>
                </a>
              </div>
            </div>
          </div>

          <div class="panel">
            <div class="panel-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
              Latest Announcements
            </div>

            <div class="panel-body announce-feed">
              <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $announcement): ?>
                  <div class="announce-item">
                    <div class="announce-title"><?= htmlspecialchars($announcement['title']) ?></div>
                    <div class="announce-meta">
                      <?= htmlspecialchars($announcement['posted_by']) ?> · <?= date('M d, Y h:i A', strtotime($announcement['created_at'])) ?>
                    </div>
                    <div class="announce-text"><?= htmlspecialchars($announcement['message']) ?></div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div style="font-size:13px;color:#9ca3af;">No announcements available.</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="panel">
            <div class="panel-header">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
              Laboratory Rules
            </div>

            <div class="panel-body rules-body">
              <h2>University of Cebu</h2>
              <h3>Computer Laboratory Rules and Regulations</h3>

              <h4>General Rules</h4>
              <p>Students must maintain proper conduct while inside the laboratory at all times.</p>
              <p>Only authorized users are allowed to use the computer laboratory facilities.</p>
              <p>Eating, drinking, loud conversations, and disruptive behavior are not allowed.</p>

              <h4>Laboratory Use</h4>
              <p>Students must log in properly before using any computer unit.</p>
              <p>Use only the assigned computer and report any issue immediately to the laboratory personnel.</p>
              <p>Do not install, delete, or modify any software or system settings without permission.</p>

              <h4>Internet and Files</h4>
              <p>Internet access must be used only for academic and authorized purposes.</p>
              <p>Downloading malicious, illegal, or unauthorized content is strictly prohibited.</p>
              <p>Students are responsible for saving and backing up their files properly.</p>

              <h4>Respect for Equipment</h4>
              <p>Handle all equipment carefully. Any intentional damage will be subject to disciplinary action.</p>
              <p>Keep the area clean and organized before leaving the laboratory.</p>

              <h4>Penalty</h4>
              <p>Violation of these rules may result in suspension of laboratory privileges and other disciplinary measures.</p>
            </div>
          </div>

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
        <form action="edit_profile.php" method="POST">
          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">First Name</label>
            <input type="text" name="firstname" value="<?= $firstname ?>" style="
              width:100%; border:1px solid #e5e7eb; border-radius:8px;
              padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
              outline:none; color:#111827;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Last Name</label>
            <input type="text" name="lastname" value="<?= $lastname ?>" style="
              width:100%; border:1px solid #e5e7eb; border-radius:8px;
              padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
              outline:none; color:#111827;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Course</label>
            <input type="text" name="course" value="<?= $course ?>" style="
              width:100%; border:1px solid #e5e7eb; border-radius:8px;
              padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
              outline:none; color:#111827;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Year Level</label>
            <input type="text" name="yearlvl" value="<?= $yearlvl ?>" style="
              width:100%; border:1px solid #e5e7eb; border-radius:8px;
              padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
              outline:none; color:#111827;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Email</label>
            <input type="email" name="email" value="<?= $email ?>" style="
              width:100%; border:1px solid #e5e7eb; border-radius:8px;
              padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
              outline:none; color:#111827;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Address</label>
            <input type="text" name="addrs" value="<?= $addrs ?>" style="
              width:100%; border:1px solid #e5e7eb; border-radius:8px;
              padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
              outline:none; color:#111827;">
          </div>

          <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" onclick="closeModal()" style="
              padding:9px 20px; border:1px solid #d1d5db; border-radius:8px;
              background:#fff; font-size:13px; font-weight:500;
              font-family:'Poppins',sans-serif; cursor:pointer; color:#374151;">
              Cancel
            </button>
            <button type="submit" style="
              padding:9px 24px; background:#1d3a6e; color:#fff;
              border:none; border-radius:8px; font-size:13px; font-weight:600;
              font-family:'Poppins',sans-serif; cursor:pointer;">
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('navToggler').addEventListener('click', () => {
      document.getElementById('navLinks').classList.toggle('open');
      document.getElementById('sidebar').classList.toggle('open');
    });

    window.addEventListener('pageshow', function(e) {
      if (e.persisted) {
        fetch('../includes/check_session.php', { cache: 'no-store' })
          .then(res => res.json())
          .then(data => {
            if (!data.logged_in) window.location.replace('../home.php');
          });
      }
    });

    const notifications = <?= json_encode($notifications, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
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

    if (notifBellBtn) {
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
      if (notifDropdown && !notifDropdown.contains(e.target)) {
        notifMenu.classList.remove('open');
      }
    });

    updateNotifState();

    function openModal() {
      document.getElementById('editModal').style.display = 'flex';
    }

    function closeModal() {
      document.getElementById('editModal').style.display = 'none';
    }

    document.getElementById('editModal').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });
  </script>
</body>
</html>