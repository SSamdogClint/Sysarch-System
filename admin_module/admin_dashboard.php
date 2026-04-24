<?php
// admin_module/admin_dashboard.php

session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ../login_page.php');
    exit;
}

require_once '../controllers/dashboard/dashboard_stats.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">

  <title>UC – Admin Dashboard</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    body {
      background: #eef0f5;
    }

    @media (max-width: 768px) {
      .sidebar {
        display: none;
      }

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

      .admin-main {
        padding: 1.25rem;
      }
    }

    .dashboard-grid {
      display: grid;
      grid-template-columns: 380px 1fr;
      gap: 24px;
      align-items: start;
    }

    @media (max-width: 960px) {
      .dashboard-grid {
        grid-template-columns: 1fr;
      }
    }

    .stat-counters {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 20px;
    }

    .stat-counter-card {
      background: #fff;
      border-radius: 12px;
      padding: 1rem;
      text-align: center;
      border-top: 3px solid #1d4ed8;
    }

    .stat-counter-card.green {
      border-top-color: #059669;
    }

    .stat-counter-card.amber {
      border-top-color: #d97706;
    }

    .stat-counter-val {
      font-size: 28px;
      font-weight: 800;
      color: #111827;
      line-height: 1;
      margin-bottom: 4px;
    }

    .stat-counter-lbl {
      font-size: 11px;
      font-weight: 500;
      color: #6b7280;
      line-height: 1.3;
    }

    .chart-card {
      background: #fff;
      border-radius: 14px;
      padding: 1.25rem 1.5rem;
      box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    }

    .chart-card-title {
      font-size: 13px;
      font-weight: 600;
      color: #374151;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 7px;
    }

    .chart-card-title::before {
      content: '';
      display: inline-block;
      width: 4px;
      height: 16px;
      background: #1d4ed8;
      border-radius: 2px;
    }

    .chart-wrapper {
      position: relative;
      width: 100%;
      height: 230px;
    }

    .announce-card {
      background: #fff;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    }

    .announce-card-header {
      padding: 1rem 1.5rem;
      border-bottom: 1px solid #f3f4f6;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .announce-card-header h4 {
      font-size: 14px;
      font-weight: 700;
      color: #111827;
      margin: 0;
    }

    .compose-area {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #f3f4f6;
      background: #f9fafb;
    }

    .compose-label {
      font-size: 11px;
      font-weight: 700;
      color: #6b7280;
      letter-spacing: 0.6px;
      text-transform: uppercase;
      margin-bottom: 8px;
      display: block;
    }

    .announce-textarea {
      width: 100%;
      border: 1px solid #e5e7eb;
      border-radius: 9px;
      padding: 10px 13px;
      font-size: 13px;
      font-family: 'Poppins', sans-serif;
      color: #111827;
      background: #fff;
      resize: none;
      height: 80px;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s;
    }

    .announce-textarea:focus {
      border-color: #1d4ed8;
      box-shadow: 0 0 0 3px rgba(29,78,216,0.1);
    }

    .compose-footer {
      display: flex;
      justify-content: flex-end;
      margin-top: 10px;
    }

    .btn-post {
      padding: 8px 20px;
      background: #1d4ed8;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      transition: background 0.15s;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .btn-post:hover {
      background: #1e40af;
    }

    .announce-feed {
      padding: 0 1.5rem;
      max-height: 340px;
      overflow-y: auto;
    }

    .feed-item {
      padding: 1rem 0;
      border-bottom: 1px solid #f3f4f6;
      animation: slideIn 0.2s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-6px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .feed-item:last-child {
      border-bottom: none;
    }

    .feed-top {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 6px;
    }

    .feed-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: #eff6ff;
      color: #1d4ed8;
      font-size: 11px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .feed-author {
      font-size: 13px;
      font-weight: 600;
      color: #111827;
    }

    .feed-date {
      font-size: 11px;
      color: #9ca3af;
      margin-left: auto;
    }

    .feed-body {
      font-size: 13px;
      color: #4b5563;
      line-height: 1.65;
      padding-left: 42px;
    }

    .feed-body.empty-body {
      font-style: italic;
      color: #9ca3af;
    }
  </style>
</head>

<body>
  <nav class="uc-nav">
    <a class="nav-brand" href="admin_dashboard.php">
      <img src="../assets/images/ccsmainlog_nobg.png" alt="UC CCS Logo" class="nav-logo">
      <div>
        <div class="nav-title">UC Sit-in System</div>
        <div class="nav-sub">Admin Panel</div>
      </div>
    </a>

    <button class="nav-toggler" id="navToggler" aria-label="Toggle menu">
      <span></span>
      <span></span>
      <span></span>
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
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-section" style="margin-top:0;">Main</div>

      <a class="sidebar-link active" href="admin_dashboard.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>
      <a class="sidebar-link" href="#" onclick="openSearchModal(); return false;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Search
      </a>
      <a class="sidebar-link" href="Admin_StudentList.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Students
      </a>
      <a class="sidebar-link" href="#" onclick="openSitinModal(); return false;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2M8 7V5a2 2 0 0 0-4 0v2"/></svg>
        Sit-in
      </a>
      <a class="sidebar-link" href="Admin_SitinRecords.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Sit-in Records
      </a>
      <hr class="sidebar-divider">
      <div class="sidebar-section">Reports</div>
      <a class="sidebar-link" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Sit-in Reports
      </a>
      <a class="sidebar-link" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Feedback Reports
      </a>
      <a class="sidebar-link" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Reservation
      </a>
    </aside>

    <main class="admin-main">
      <div class="dashboard-grid">
        <div>
          <div class="stat-counters">
            <div class="stat-counter-card">
              <div class="stat-counter-val"><?= $total_students ?></div>
              <div class="stat-counter-lbl">Students Registered</div>
            </div>

            <div class="stat-counter-card green">
              <div class="stat-counter-val"><?= $current_sitin ?></div>
              <div class="stat-counter-lbl">Currently Sit-in</div>
            </div>

            <div class="stat-counter-card amber">
              <div class="stat-counter-val"><?= $total_sitin ?></div>
              <div class="stat-counter-lbl">Total Sit-in</div>
            </div>
          </div>

          <div class="chart-card">
            <div class="chart-card-title">Sit-ins by Programming Language</div>
            <div class="chart-wrapper">
              <canvas id="languageChart"></canvas>
            </div>
          </div>
        </div>

        <div class="announce-card">
          <div class="announce-card-header">
            <h4>Announcements</h4>
            <span id="postCountLabel" style="font-size:12px; color:#9ca3af;">
              <?= $total_posts ?> posted
            </span>
          </div>

          <div class="compose-area">
            <span class="compose-label">New announcement</span>

            <input
              type="text"
              id="announceTitle"
              class="announce-textarea"
              placeholder="Announcement title (optional)"
              style="height: 46px; margin-bottom: 10px;"
            >

            <textarea
              class="announce-textarea"
              id="announceText"
              placeholder="Write something for students to see…"
            ></textarea>

            <div class="compose-footer">
              <button class="btn-post" onclick="postAnnouncement()">
                Post
              </button>
            </div>
          </div>

          <div class="announce-feed" id="announceList">
            <div class="feed-item">
              <div class="feed-top">
                <div class="feed-avatar">CA</div>
                <span class="feed-author">CCS Admin</span>
                <span class="feed-date">Feb 11, 2026</span>
              </div>
              <div class="feed-body empty-body">No message content.</div>
            </div>

            <div class="feed-item">
              <div class="feed-top">
                <div class="feed-avatar">CA</div>
                <span class="feed-author">CCS Admin</span>
                <span class="feed-date">May 8, 2024</span>
              </div>
              <div class="feed-body">
                Important Announcement: We are excited to announce the launch of our new website! 🌐 Explore our latest products and services now!
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div id="searchModal" style="display:none; position:fixed; inset:0; z-index:999; background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:100%; max-width:520px; margin:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.2); font-family:'Poppins',sans-serif; overflow:hidden;">
      <div style="background:#1d3a6e; color:#fff; padding:16px 24px; display:flex; align-items:center; justify-content:space-between;">
        <span style="font-size:14px; font-weight:600;">Search Student</span>
        <button onclick="closeSearchModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">✕</button>
      </div>

      <div style="padding:20px 24px; border-bottom:1px solid #f3f4f6;">
        <div style="display:flex; gap:10px;">
          <input
            type="text"
            id="searchInput"
            placeholder="Enter Student ID (e.g. 2024-00001)"
            style="flex:1; border:1px solid #e5e7eb; border-radius:8px; padding:10px 14px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; color:#111827;"
            onkeydown="if(event.key==='Enter') searchStudent()"
          >

          <button onclick="searchStudent()" style="padding:10px 20px; background:#1d3a6e; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer; white-space:nowrap;">
            Search
          </button>
        </div>

        <div id="searchError" style="display:none; margin-top:8px; font-size:12px; color:#b91c1c;"></div>
      </div>

      <div id="searchResult" style="display:none; padding:20px 24px;">
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid #f3f4f6;">
          <div id="resultAvatar" style="width:56px; height:56px; border-radius:50%; background:#1d3a6e; color:#fff; font-size:20px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:3px solid #e5e7eb;"></div>

          <div>
            <div id="resultName" style="font-size:15px; font-weight:700; color:#111827;"></div>
            <div id="resultId" style="font-size:12px; color:#6b7280; margin-top:2px;"></div>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">COURSE</div>
            <div id="resultCourse" style="font-size:13px; font-weight:600; color:#111827;"></div>
          </div>

          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">YEAR LEVEL</div>
            <div id="resultYear" style="font-size:13px; font-weight:600; color:#111827;"></div>
          </div>

          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">EMAIL</div>
            <div id="resultEmail" style="font-size:13px; font-weight:600; color:#111827; word-break:break-all;"></div>
          </div>

          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">ADDRESS</div>
            <div id="resultAddr" style="font-size:13px; font-weight:600; color:#111827;"></div>
          </div>

          <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px; grid-column: span 2;">
            <div style="font-size:11px; font-weight:600; color:#1d4ed8; margin-bottom:3px;">SESSION CREDITS REMAINING</div>
            <div style="display:flex; align-items:center; gap:8px;">
              <div id="resultCredits" style="font-size:22px; font-weight:800; color:#1d3a6e;"></div>
              <div style="font-size:12px; color:#6b7280;">/ 30 credits this semester</div>
            </div>
          </div>
        </div>
      </div>

      <div id="searchLoading" style="display:none; padding:32px; text-align:center; font-size:13px; color:#6b7280;">
        Searching...
      </div>
    </div>
  </div>

  <div id="sitinModal" style="display:none; position:fixed; inset:0; z-index:999; background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:100%; max-width:520px; margin:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.2); font-family:'Poppins',sans-serif; overflow:hidden;">
      <div style="background:#1d3a6e; color:#fff; padding:16px 24px; display:flex; align-items:center; justify-content:space-between;">
        <span style="font-size:14px; font-weight:600;">Register Sit-in</span>
        <button onclick="closeSitinModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">✕</button>
      </div>

      <div style="padding:24px;">
        <div style="margin-bottom:14px;">
          <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Student ID</label>

          <div style="display:flex;gap:8px;">
            <input
              type="text"
              id="sitinIdInput"
              placeholder="e.g. 2024-00001"
              style="flex:1;border:1px solid #e5e7eb;border-radius:8px;padding:9px 13px; font-size:13px;font-family:'Poppins',sans-serif;outline:none;color:#111827;"
              oninput="resetSitinLookup()"
            >

            <button onclick="lookupStudent()" style="padding:9px 16px;background:#1d3a6e;color:#fff;border:none; border-radius:8px;font-size:13px;font-weight:600; font-family:'Poppins',sans-serif;cursor:pointer;white-space:nowrap;">
              Look up
            </button>
          </div>

          <div id="sitinLookupError" style="display:none;margin-top:6px;font-size:12px;color:#b91c1c;"></div>
        </div>

        <div id="sitinStudentInfo" style="display:none; background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; padding:12px 16px; margin-bottom:14px;">
          <div style="display:flex;align-items:center;gap:12px;">
            <div id="sitinAvatar" style="width:44px;height:44px;border-radius:50%; background:#1d3a6e;color:#fff;font-size:15px;font-weight:700; display:flex;align-items:center;justify-content:center;flex-shrink:0;"></div>

            <div>
              <div id="sitinStudentName" style="font-size:13px;font-weight:700;color:#111827;"></div>
              <div id="sitinStudentCourse" style="font-size:12px;color:#6b7280;margin-top:1px;"></div>

              <div style="display:flex;align-items:center;gap:6px;margin-top:4px;">
                <span style="font-size:11px;font-weight:600;color:#374151;">Remaining Sessions:</span>
                <span id="sitinSessionBadge" style="background:#1d3a6e;color:#fff;font-size:11px;font-weight:700; padding:2px 10px;border-radius:99px;"></span>
              </div>
            </div>
          </div>
        </div>

        <div id="sitinFormFields" style="display:none;">
          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Purpose</label>
            <input
              type="text"
              id="sitinPurpose"
              placeholder="e.g. C++ Programming, Web Development"
              style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 13px; font-size:13px;font-family:'Poppins',sans-serif;outline:none;color:#111827;"
            >
          </div>

          <div style="margin-bottom:20px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Lab Number</label>
            <select
              id="sitinLab"
              style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 13px; font-size:13px;font-family:'Poppins',sans-serif;outline:none; color:#111827;background:#fff;"
            >
              <option value="">-- Select Lab --</option>
              <option value="Lab 1">Lab 1</option>
              <option value="Lab 2">Lab 2</option>
              <option value="Lab 3">Lab 3</option>
              <option value="Lab 4">Lab 4</option>
              <option value="Lab 5">Lab 5</option>
              <option value="Lab 6">Lab 6</option>
            </select>
          </div>

          <div id="sitinSubmitError" style="display:none;margin-bottom:10px;font-size:12px;color:#b91c1c;"></div>

          <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="closeSitinModal()" style="padding:9px 20px;border:1px solid #d1d5db;border-radius:8px; background:#fff;font-size:13px;font-weight:500; font-family:'Poppins',sans-serif;cursor:pointer;color:#374151;">
              Cancel
            </button>

            <button onclick="submitSitin()" style="padding:9px 24px;background:#059669;color:#fff;border:none; border-radius:8px;font-size:13px;font-weight:600; font-family:'Poppins',sans-serif;cursor:pointer;">
              Confirm Sit-in
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="sitinToast" style="display:none; position:fixed; bottom:24px; right:24px; z-index:9999; background:#059669; color:#fff; padding:12px 20px; border-radius:10px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; box-shadow:0 4px 16px rgba(0,0,0,0.15);">
    ✅ Sit-in registered successfully!
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.getElementById('navToggler').addEventListener('click', () => {
      document.getElementById('navLinks').classList.toggle('open');
    });

    window.addEventListener('pageshow', function(e) {
      if (e.persisted) {
        fetch('../controllers/auth/check_session.php?type=admin', { cache: 'no-store' })
          .then(res => res.json())
          .then(data => {
            if (!data.logged_in) {
              window.location.replace('../home.php');
            }
          });
      }
    });

    const ctx = document.getElementById('languageChart').getContext('2d');

    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
          data: <?= json_encode($chart_values) ?>,
          backgroundColor: ['#3b82f6', '#f97316', '#ec4899', '#eab308', '#06b6d4'],
          borderColor: '#fff',
          borderWidth: 3,
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '55%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              font: {
                family: 'Poppins',
                size: 12
              },
              boxWidth: 12,
              padding: 14
            }
          },
          tooltip: {
            bodyFont: {
              family: 'Poppins'
            },
            titleFont: {
              family: 'Poppins',
              weight: '600'
            }
          }
        }
      }
    });

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function formatDate(dateStr) {
      const d = new Date(dateStr);

      return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    }

    function postAnnouncement() {
      const title = document.getElementById('announceTitle').value.trim();
      const text = document.getElementById('announceText').value.trim();

      if (!text) {
        alert('Please enter an announcement.');
        return;
      }

      const formData = new FormData();
      formData.append('title', title);
      formData.append('message', text);

      fetch('../controllers/announcements/post_announcement.php', {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          if (!data.success) {
            alert(data.message || 'Failed to post announcement.');
            return;
          }

          const a = data.announcement;
          const item = document.createElement('div');
          item.className = 'feed-item';

          item.innerHTML = `
            <div class="feed-top">
              <div class="feed-avatar">CA</div>
              <span class="feed-author">${escapeHtml(a.posted_by)}</span>
              <span class="feed-date">${formatDate(a.created_at)}</span>
            </div>
            ${a.title ? `<div style="font-weight:700; margin-bottom:4px;">${escapeHtml(a.title)}</div>` : ''}
            <div class="feed-body">${escapeHtml(a.message)}</div>
          `;

          const list = document.getElementById('announceList');
          list.insertBefore(item, list.firstChild);

          document.getElementById('announceTitle').value = '';
          document.getElementById('announceText').value = '';

          const currentCount = list.querySelectorAll('.feed-item').length;
          document.getElementById('postCountLabel').textContent = `${currentCount} posted`;
        })
        .catch(() => {
          alert('Something went wrong. Please try again.');
        });
    }

    function openSearchModal() {
      document.getElementById('searchModal').style.display = 'flex';
      document.getElementById('searchInput').focus();
      resetSearch();
    }

    function closeSearchModal() {
      document.getElementById('searchModal').style.display = 'none';
      resetSearch();
    }

    function resetSearch() {
      document.getElementById('searchInput').value = '';
      document.getElementById('searchResult').style.display = 'none';
      document.getElementById('searchError').style.display = 'none';
      document.getElementById('searchLoading').style.display = 'none';
    }

    function searchStudent() {
      const id = document.getElementById('searchInput').value.trim();

      if (!id) {
        showSearchError('Please enter a Student ID.');
        return;
      }

      document.getElementById('searchResult').style.display = 'none';
      document.getElementById('searchError').style.display = 'none';
      document.getElementById('searchLoading').style.display = 'block';

      fetch(`../controllers/student/search_student.php?studentid=${encodeURIComponent(id)}`)
        .then(res => res.json())
        .then(data => {
          document.getElementById('searchLoading').style.display = 'none';

          if (!data.found) {
            showSearchError(data.message || 'No student found with that ID.');
            return;
          }

          const s = data.student;
          const initials = (s.firstname.charAt(0) + s.lastname.charAt(0)).toUpperCase();
          const yearLabels = {
            1: '1st Year',
            2: '2nd Year',
            3: '3rd Year',
            4: '4th Year'
          };

          document.getElementById('resultAvatar').textContent = initials;
          document.getElementById('resultName').textContent = s.lastname + ', ' + s.firstname + ' ' + s.middlename;
          document.getElementById('resultId').textContent = 'ID: ' + s.studentid;
          document.getElementById('resultCourse').textContent = s.course;
          document.getElementById('resultYear').textContent = yearLabels[s.yearlvl] || s.yearlvl;
          document.getElementById('resultEmail').textContent = s.email;
          document.getElementById('resultAddr').textContent = s.addrs;
          document.getElementById('resultCredits').textContent = s.session_credits;

          document.getElementById('searchResult').style.display = 'block';
        })
        .catch(() => {
          document.getElementById('searchLoading').style.display = 'none';
          showSearchError('Something went wrong. Please try again.');
        });
    }

    function showSearchError(msg) {
      const el = document.getElementById('searchError');
      el.textContent = msg;
      el.style.display = 'block';
    }

    document.getElementById('searchModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeSearchModal();
      }
    });

    let currentStudent = null;

    function openSitinModal() {
      document.getElementById('sitinModal').style.display = 'flex';
      resetSitinModal();
      setTimeout(() => document.getElementById('sitinIdInput').focus(), 100);
    }

    function closeSitinModal() {
      document.getElementById('sitinModal').style.display = 'none';
      resetSitinModal();
    }

    function resetSitinModal() {
      currentStudent = null;
      document.getElementById('sitinIdInput').value = '';
      document.getElementById('sitinPurpose').value = '';
      document.getElementById('sitinLab').value = '';
      document.getElementById('sitinStudentInfo').style.display = 'none';
      document.getElementById('sitinFormFields').style.display = 'none';
      document.getElementById('sitinLookupError').style.display = 'none';
      document.getElementById('sitinSubmitError').style.display = 'none';
    }

    function resetSitinLookup() {
      currentStudent = null;
      document.getElementById('sitinStudentInfo').style.display = 'none';
      document.getElementById('sitinFormFields').style.display = 'none';
      document.getElementById('sitinLookupError').style.display = 'none';
    }

    function lookupStudent() {
      const id = document.getElementById('sitinIdInput').value.trim();

      if (!id) {
        showSitinError('sitinLookupError', 'Please enter a Student ID.');
        return;
      }

      fetch(`../controllers/student/search_student.php?studentid=${encodeURIComponent(id)}`)
        .then(res => res.json())
        .then(data => {
          if (!data.found) {
            showSitinError('sitinLookupError', data.message || 'No student found with that ID.');
            return;
          }

          const s = data.student;
          currentStudent = s;

          if (s.session_credits <= 0) {
            showSitinError('sitinLookupError', 'This student has no remaining session credits.');
            return;
          }

          const initials = (s.firstname.charAt(0) + s.lastname.charAt(0)).toUpperCase();
          const yearLabels = {
            1: '1st Year',
            2: '2nd Year',
            3: '3rd Year',
            4: '4th Year'
          };

          document.getElementById('sitinAvatar').textContent = initials;
          document.getElementById('sitinStudentName').textContent = s.lastname + ', ' + s.firstname + ' ' + s.middlename;
          document.getElementById('sitinStudentCourse').textContent = s.course + ' — ' + (yearLabels[s.yearlvl] || s.yearlvl);
          document.getElementById('sitinSessionBadge').textContent = s.session_credits;

          document.getElementById('sitinStudentInfo').style.display = 'block';
          document.getElementById('sitinFormFields').style.display = 'block';
          document.getElementById('sitinLookupError').style.display = 'none';
        })
        .catch(() => {
          showSitinError('sitinLookupError', 'Something went wrong. Try again.');
        });
    }

    function submitSitin() {
      const purpose = document.getElementById('sitinPurpose').value.trim();
      const lab = document.getElementById('sitinLab').value;

      if (!currentStudent) {
        showSitinError('sitinSubmitError', 'Please look up a student first.');
        return;
      }

      if (!purpose) {
        showSitinError('sitinSubmitError', 'Please enter the purpose.');
        return;
      }

      if (!lab) {
        showSitinError('sitinSubmitError', 'Please select a lab.');
        return;
      }

      const formData = new FormData();
      formData.append('studentid', currentStudent.studentid);
      formData.append('purpose', purpose);
      formData.append('lab', lab);

      fetch('../controllers/sitin/register_sitin.php', {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          if (!data.success) {
            showSitinError('sitinSubmitError', data.message || 'Failed to register sit-in.');
            return;
          }

          closeSitinModal();
          showToast();
        })
        .catch(() => {
          showSitinError('sitinSubmitError', 'Something went wrong. Try again.');
        });
    }

    function showSitinError(id, msg) {
      const el = document.getElementById(id);
      el.textContent = msg;
      el.style.display = 'block';
    }

    function showToast() {
      const toast = document.getElementById('sitinToast');
      toast.style.display = 'block';
      setTimeout(() => toast.style.display = 'none', 3000);
    }

    document.getElementById('sitinModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeSitinModal();
      }
    });
  </script>
</body>
</html>