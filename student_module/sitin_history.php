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

$records = [];
$stmt = $conn->prepare("
    SELECT 
        sr.id,
        sr.purpose,
        sr.lab,
        sr.session_at_sitin,
        sr.login_time,
        sr.status,
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
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    * { box-sizing: border-box; }
    body { background: #eef0f5; font-family: 'Poppins', sans-serif; margin: 0; }

    @media (max-width: 768px) {
      .sidebar { display: none; }
      .sidebar.open {
        display: block; width: 100%; position: fixed;
        top: 60px; left: 0; bottom: 0; z-index: 99; overflow-y: auto;
      }
      .admin-main { padding: 1.25rem; }
    }

    .page-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.06);
      overflow: hidden;
    }

    .page-card-header {
      background: #1d3a6e;
      color: #fff;
      padding: 14px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
    }

    .page-card-header h4 {
      font-size: 14px;
      font-weight: 700;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .header-right {
      font-size: 12px;
      color: #bfdbfe;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .header-avatar {
      width: 28px; height: 28px;
      border-radius: 50%;
      background: rgba(255,255,255,0.2);
      color: #fff;
      font-size: 11px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
    }

    .stats-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      padding: 16px 20px;
      border-bottom: 1px solid #f3f4f6;
    }

    @media (max-width: 500px) {
      .stats-row { grid-template-columns: 1fr; }
    }

    .stat-box {
      background: #f8fafc;
      border-radius: 12px;
      padding: 14px 16px;
      border: 1px solid #e5e7eb;
    }

    .stat-num {
      font-size: 24px;
      font-weight: 800;
      color: #1d3a6e;
      line-height: 1;
      margin-bottom: 4px;
    }

    .stat-lbl {
      font-size: 12px;
      color: #6b7280;
      font-weight: 500;
    }

    .toolbar {
      padding: 14px 20px;
      border-bottom: 1px solid #f3f4f6;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .toolbar-left {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .entries-select {
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 7px 12px;
      font-size: 13px;
      font-family: 'Poppins', sans-serif;
      outline: none;
      background: #fff;
      color: #111827;
      cursor: pointer;
    }

    .search-input {
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 7px 14px;
      font-size: 13px;
      font-family: 'Poppins', sans-serif;
      outline: none;
      color: #111827;
      width: 240px;
    }

    .entries-select:focus,
    .search-input:focus {
      border-color: #1d4ed8;
      box-shadow: 0 0 0 3px rgba(29,78,216,0.08);
    }

    .timeline-wrap {
      padding: 24px 28px;
      background: #fff;
    }

    .timeline {
      position: relative;
      padding-left: 36px;
    }

    .timeline::before {
      content: '';
      position: absolute;
      left: 10px;
      top: 0;
      bottom: 0;
      width: 2px;
      background: #e5e7eb;
      border-radius: 2px;
    }

    .timeline-date-group {
      margin-bottom: 28px;
    }

    .timeline-date-label {
      position: relative;
      margin-left: -36px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .timeline-date-label::before {
      content: '';
      width: 10px; height: 10px;
      border-radius: 50%;
      background: #1d3a6e;
      border: 2px solid #fff;
      box-shadow: 0 0 0 2px #1d3a6e;
      flex-shrink: 0;
      margin-left: 6px;
    }

    .date-badge {
      background: #1d3a6e;
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      padding: 4px 14px;
      border-radius: 99px;
      letter-spacing: 0.3px;
    }

    .timeline-item {
      position: relative;
      margin-bottom: 12px;
    }

    .timeline-item::before {
      content: '';
      position: absolute;
      left: -30px;
      top: 18px;
      width: 8px; height: 8px;
      border-radius: 50%;
      background: #fff;
      border: 2px solid #d1d5db;
    }

    .timeline-item.active-item::before {
      border-color: #059669;
      background: #059669;
      box-shadow: 0 0 0 3px rgba(5,150,105,0.15);
    }

    .timeline-card {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 14px 16px;
      transition: box-shadow 0.15s;
    }

    .timeline-card:hover {
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .timeline-item.active-item .timeline-card {
      background: #f0fdf4;
      border-color: #bbf7d0;
    }

    .timeline-card-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 10px;
      flex-wrap: wrap;
    }

    .timeline-time {
      font-size: 11px;
      font-weight: 700;
      color: #1d3a6e;
      background: #eff6ff;
      padding: 3px 10px;
      border-radius: 6px;
      white-space: nowrap;
    }

    .timeline-item.active-item .timeline-time {
      background: #dcfce7;
      color: #166534;
    }

    .badge-status {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 99px;
      font-size: 11px;
      font-weight: 700;
    }

    .badge-status.active { background: #dcfce7; color: #166534; }
    .badge-status.done   { background: #f3f4f6; color: #6b7280; }

    .timeline-card-body {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
    }

    @media (max-width: 500px) {
      .timeline-card-body { grid-template-columns: 1fr 1fr; }
    }

    .timeline-field {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .timeline-field-label {
      font-size: 10px;
      font-weight: 700;
      color: #9ca3af;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .timeline-field-value {
      font-size: 13px;
      font-weight: 600;
      color: #111827;
    }

    .badge-session {
      background: #eff6ff;
      color: #1d3a6e;
      padding: 2px 8px;
      border-radius: 99px;
      font-size: 11px;
      font-weight: 700;
      display: inline-block;
    }

    .timeline-feedback-row {
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid #e5e7eb;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
      flex-wrap: wrap;
    }

    .timeline-feedback-info {
      flex: 1;
      min-width: 220px;
    }

    .timeline-feedback-badge {
      display: inline-block;
      background: #eff6ff;
      color: #1d4ed8;
      font-size: 11px;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 999px;
      margin-bottom: 6px;
    }

    .timeline-feedback-text {
      font-size: 12px;
      color: #374151;
      line-height: 1.6;
      white-space: pre-wrap;
      word-break: break-word;
    }

    .timeline-feedback-empty {
      font-size: 12px;
      color: #9ca3af;
      font-style: italic;
    }

    .timeline-feedback-action {
      flex-shrink: 0;
    }

    .feedback-btn {
      padding: 7px 14px;
      border: none;
      border-radius: 8px;
      background: #1d4ed8;
      color: #fff;
      font-size: 12px;
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      transition: background 0.15s;
    }

    .feedback-btn:hover {
      background: #1e40af;
    }

    .empty-state {
      padding: 3rem 20px;
      text-align: center;
      color: #9ca3af;
      font-size: 13px;
    }

    .pagination-bar {
      padding: 14px 20px;
      background: #f8fafc;
      border-top: 1px solid #e5e7eb;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      font-size: 13px;
      color: #6b7280;
    }

    .pagination-btns {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
    }

    .page-btn {
      border: 1px solid #e5e7eb;
      background: #fff;
      color: #374151;
      border-radius: 6px;
      padding: 5px 11px;
      font-size: 12px;
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.13s;
    }

    .page-btn:hover { background: #f3f4f6; }
    .page-btn.active { background: #1d3a6e; color: #fff; border-color: #1d3a6e; }
    .page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

    .no-results {
      padding: 2rem;
      text-align: center;
      font-size: 13px;
      color: #9ca3af;
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

    .notif-menu.open { display: block; }

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

    .notif-menu-item:last-child { border-bottom: none; }

    .notif-menu-item:hover { background: #f9fafb; }

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

        <div class="stats-row">
          <div class="stat-box">
            <div class="stat-num"><?= $total_records ?></div>
            <div class="stat-lbl">Total Records</div>
          </div>
          <div class="stat-box">
            <div class="stat-num"><?= $active_count ?></div>
            <div class="stat-lbl">Active Sessions</div>
          </div>
          <div class="stat-box">
            <div class="stat-num"><?= $done_count ?></div>
            <div class="stat-lbl">Completed Sessions</div>
          </div>
        </div>

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
          <div class="empty-state">
            📋 No sit-in history found yet.
          </div>
        <?php else: ?>
          <div class="timeline-wrap">
            <div class="timeline" id="timelineContainer"></div>
            <div id="noResults" class="no-results" style="display:none;">
              No records match your search.
            </div>
          </div>

          <div class="pagination-bar">
            <span id="tableInfo">Showing 0 entries</span>
            <div class="pagination-btns" id="paginationBtns"></div>
          </div>
        <?php endif; ?>

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
        <button type="button" onclick="closeModal()" style="background:transparent; border:none; color:#fff; font-size:20px; cursor:pointer; line-height:1;">✕</button>
      </div>

      <div style="padding:24px;">
        <form action="../controllers/student/update_profile.php" method="POST">
          <input type="hidden" name="student_id" value="<?= (int)$student_id ?>">
          <input type="hidden" name="studentid" value="<?= htmlspecialchars($_SESSION['studentid'] ?? '') ?>">
          <input type="hidden" name="middlename" value="<?= htmlspecialchars($_SESSION['middlename'] ?? '') ?>">
          <input type="hidden" name="redirect" value="student">
          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">First Name</label>
            <input type="text" name="firstname" value="<?= $firstname ?>" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; color:#111827;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Last Name</label>
            <input type="text" name="lastname" value="<?= $lastname ?>" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; color:#111827;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Course</label>
            <input type="text" name="course" value="<?= $course ?>" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; color:#111827;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Year Level</label>
            <input type="text" name="yearlvl" value="<?= $yearlvl ?>" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; color:#111827;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Email</label>
            <input type="email" name="email" value="<?= $email ?>" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; color:#111827;">
          </div>

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Address</label>
            <input type="text" name="addrs" value="<?= $addrs ?>" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; color:#111827;">
          </div>

          <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" onclick="closeModal()" style="padding:9px 20px; border:1px solid #d1d5db; border-radius:8px; background:#fff; font-size:13px; font-weight:500; font-family:'Poppins',sans-serif; cursor:pointer; color:#374151;">Cancel</button>
            <button type="submit" style="padding:9px 24px; background:#1d3a6e; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer;">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div id="feedbackModal" style="
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">

    <div style="
      background:#fff; border-radius:16px; width:100%; max-width:560px;
      margin:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.2);
      font-family:'Poppins',sans-serif; overflow:hidden;">

      <div style="
        background:#1d3a6e; color:#fff; padding:16px 24px;
        display:flex; align-items:center; justify-content:space-between;">
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

        <div id="feedbackError" style="display:none; margin-bottom:10px; font-size:12px; color:#b91c1c;"></div>
        <div id="feedbackSuccess" style="display:none; margin-bottom:10px; font-size:12px; color:#166534;"></div>

        <div style="display:flex; gap:10px; justify-content:flex-end;">
          <button type="button" onclick="closeFeedbackModal()" style="padding:9px 20px; border:1px solid #d1d5db; border-radius:8px; background:#fff; font-size:13px; font-weight:500; font-family:'Poppins',sans-serif; cursor:pointer; color:#374151;">Cancel</button>
          <button type="button" onclick="submitFeedback()" style="padding:9px 24px; background:#1d4ed8; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer;">Save Feedback</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    window.addEventListener("pageshow", function (event) {
      if (event.persisted) {
        window.location.reload();
      }
    });
    
    document.getElementById('navToggler').addEventListener('click', () => {
      document.getElementById('navLinks').classList.toggle('open');
      document.getElementById('sidebar').classList.toggle('open');
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

    const allRecords = <?= json_encode(array_values($records), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    let currentPage = 1;

    function escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function formatTime(dt) {
      return new Date(dt).toLocaleString('en-PH', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      });
    }

    function formatDateLabel(dt) {
      return new Date(dt).toLocaleDateString('en-PH', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    }

    function formatFeedbackDate(dt) {
      if (!dt) return '';
      const d = new Date(dt);
      if (isNaN(d.getTime())) return '';
      return d.toLocaleString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      });
    }

    function getFiltered() {
      const q = document.getElementById('searchInput').value.trim().toLowerCase();
      if (!q) return allRecords;

      return allRecords.filter(r =>
        (r.purpose || '').toLowerCase().includes(q) ||
        (r.lab || '').toLowerCase().includes(q) ||
        (r.status || '').toLowerCase().includes(q) ||
        (r.feedback_text || '').toLowerCase().includes(q) ||
        (r.issue_type || '').toLowerCase().includes(q)
      );
    }

    function renderTimeline() {
      const container = document.getElementById('timelineContainer');
      const noResults = document.getElementById('noResults');
      const tableInfo = document.getElementById('tableInfo');
      const paginBtns = document.getElementById('paginationBtns');
      const perPage = parseInt(document.getElementById('entriesSelect').value, 10);
      const filtered = getFiltered();
      const total = filtered.length;
      const totalPages = Math.max(1, Math.ceil(total / perPage));

      if (currentPage > totalPages) currentPage = 1;

      const start = (currentPage - 1) * perPage;
      const end = Math.min(start + perPage, total);
      const pageData = filtered.slice(start, end);

      container.innerHTML = '';

      if (pageData.length === 0) {
        noResults.style.display = 'block';
        tableInfo.textContent = 'Showing 0 entries';
        paginBtns.innerHTML = '';
        return;
      }

      noResults.style.display = 'none';

      const groups = {};
      pageData.forEach(r => {
        const dateKey = new Date(r.login_time).toDateString();
        if (!groups[dateKey]) groups[dateKey] = [];
        groups[dateKey].push(r);
      });

      Object.entries(groups).forEach(([dateKey, items]) => {
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
              <span class="badge-status ${isActive ? 'active' : 'done'}">
                ${isActive ? 'Active' : 'Done'}
              </span>
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
                <span class="timeline-field-value">
                  <span class="badge-session">${escapeHtml(r.session_at_sitin)}</span>
                </span>
              </div>
            </div>
          `;

          const feedbackRow = document.createElement('div');
          feedbackRow.className = 'timeline-feedback-row';

          const feedbackInfo = document.createElement('div');
          feedbackInfo.className = 'timeline-feedback-info';

          if (r.feedback_text) {
            const when = formatFeedbackDate(r.feedback_created_at);
            feedbackInfo.innerHTML = `
              <div class="timeline-feedback-badge">${escapeHtml(r.issue_type || 'General')}</div>
              <div class="timeline-feedback-text">${escapeHtml(r.feedback_text)}</div>
              ${when ? `<div style="margin-top:6px;font-size:11px;color:#9ca3af;">Submitted: ${escapeHtml(when)}</div>` : ''}
            `;
          } else {
            feedbackInfo.innerHTML = `
              <div class="timeline-feedback-empty">No feedback submitted yet.</div>
            `;
          }

          const btnWrapper = document.createElement('div');
          btnWrapper.className = 'timeline-feedback-action';

          const btn = document.createElement('button');
          btn.className = 'feedback-btn';
          btn.textContent = r.feedback_text ? 'Edit Feedback' : 'Give Feedback';
          btn.addEventListener('click', () => {
            openFeedbackModal(
              r.id,
              r.issue_type || '',
              r.feedback_text || ''
            );
          });

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

      paginBtns.innerHTML = '';

      const prev = document.createElement('button');
      prev.className = 'page-btn';
      prev.textContent = '← Prev';
      prev.disabled = currentPage === 1;
      prev.onclick = () => {
        currentPage--;
        renderTimeline();
      };
      paginBtns.appendChild(prev);

      for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.className = 'page-btn' + (i === currentPage ? ' active' : '');
        btn.textContent = i;
        btn.onclick = () => {
          currentPage = i;
          renderTimeline();
        };
        paginBtns.appendChild(btn);
      }

      const next = document.createElement('button');
      next.className = 'page-btn';
      next.textContent = 'Next →';
      next.disabled = currentPage === totalPages || total === 0;
      next.onclick = () => {
        currentPage++;
        renderTimeline();
      };
      paginBtns.appendChild(next);
    }

    document.getElementById('searchInput').addEventListener('input', () => {
      currentPage = 1;
      renderTimeline();
    });

    document.getElementById('entriesSelect').addEventListener('change', () => {
      currentPage = 1;
      renderTimeline();
    });

    <?php if (!empty($records)): ?>
    renderTimeline();
    <?php endif; ?>

    function openModal() {
      document.getElementById('editModal').style.display = 'flex';
    }

    function closeModal() {
      document.getElementById('editModal').style.display = 'none';
    }

    document.getElementById('editModal').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });

    function openFeedbackModal(sitinId, issueType = '', feedbackText = '') {
      document.getElementById('feedbackSitinId').value = sitinId;
      document.getElementById('feedbackIssueType').value = issueType || '';
      document.getElementById('feedbackText').value = feedbackText || '';
      document.getElementById('feedbackError').style.display = 'none';
      document.getElementById('feedbackSuccess').style.display = 'none';
      document.getElementById('feedbackModal').style.display = 'flex';
    }

    function closeFeedbackModal() {
      document.getElementById('feedbackModal').style.display = 'none';
    }

    document.getElementById('feedbackModal').addEventListener('click', function(e) {
      if (e.target === this) closeFeedbackModal();
    });

    function submitFeedback() {
      const sitinId = document.getElementById('feedbackSitinId').value;
      const issueType = document.getElementById('feedbackIssueType').value;
      const feedbackText = document.getElementById('feedbackText').value.trim();

      const errorBox = document.getElementById('feedbackError');
      const successBox = document.getElementById('feedbackSuccess');

      errorBox.style.display = 'none';
      successBox.style.display = 'none';

      if (!issueType) {
        errorBox.textContent = 'Please select an issue type.';
        errorBox.style.display = 'block';
        return;
      }

      if (!feedbackText) {
        errorBox.textContent = 'Please enter your feedback.';
        errorBox.style.display = 'block';
        return;
      }

      const formData = new FormData();
      formData.append('sitin_id', sitinId);
      formData.append('issue_type', issueType);
      formData.append('feedback_text', feedbackText);

      fetch('../controllers/sitin/save_feedback.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          errorBox.textContent = data.message || 'Failed to save feedback.';
          errorBox.style.display = 'block';
          return;
        }

        successBox.textContent = 'Feedback saved successfully.';
        successBox.style.display = 'block';

        const rec = allRecords.find(x => String(x.id) === String(sitinId));
        if (rec) {
          rec.issue_type = issueType;
          rec.feedback_text = feedbackText;
          rec.feedback_created_at = new Date().toISOString();
        }

        setTimeout(() => {
          closeFeedbackModal();
          renderTimeline();
        }, 700);
      })
      .catch(() => {
        errorBox.textContent = 'Something went wrong. Please try again.';
        errorBox.style.display = 'block';
      });
    }
  </script>
</body>
</html>