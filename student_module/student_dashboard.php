<?php
// ============================================================
//  student_dashboard.php
//  Redirect here after successful login
// ============================================================
session_start();

// Guard — must be logged in
if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

// Pull session data
$firstname  = htmlspecialchars($_SESSION['firstname'] ?? '');
$lastname   = htmlspecialchars($_SESSION['lastname']  ?? '');
$course     = htmlspecialchars($_SESSION['course']    ?? '');
$yearlvl    = htmlspecialchars($_SESSION['yearlvl']   ?? '');
$email      = htmlspecialchars($_SESSION['email']     ?? '');
$addrs      = htmlspecialchars($_SESSION['addrs']     ?? '');
$session_credits = $_SESSION['session_credits'] ?? 30;

$fullname   = $lastname . ', ' . $firstname;
$initials   = strtoupper(substr($firstname,0,1) . substr($lastname,0,1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UC – Student Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="css/style.css">
  <style>
    * { box-sizing: border-box; }
    body { background: #eef0f5; font-family: 'Poppins', sans-serif; margin: 0; }

    /* ── Topnav ── */
    .topnav {
      background: #1d3a6e;
      height: 52px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 1.5rem;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .topnav-brand {
      font-size: 15px;
      font-weight: 700;
      color: #fff;
      letter-spacing: 0.2px;
    }

    .topnav-links {
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .topnav-link {
      font-size: 13px;
      color: rgba(255,255,255,0.85);
      padding: 6px 12px;
      border-radius: 6px;
      text-decoration: none;
      transition: all 0.13s;
      background: transparent;
      border: none;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
    }

    .topnav-link:hover { background: rgba(255,255,255,0.12); color: #fff; }

    .topnav-logout {
      padding: 6px 16px;
      background: #e6a817;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.13s;
    }

    .topnav-logout:hover { background: #ca8f0e; color: #fff; }

    /* Notification dropdown */
    .notif-dropdown {
      position: relative;
    }

    .notif-menu {
      display: none;
      position: absolute;
      top: calc(100% + 6px);
      right: 0;
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      min-width: 220px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
      z-index: 200;
      overflow: hidden;
    }

    .notif-menu.open { display: block; }

    .notif-menu-item {
      padding: 10px 16px;
      font-size: 13px;
      color: #374151;
      border-bottom: 1px solid #f3f4f6;
      cursor: pointer;
    }

    .notif-menu-item:last-child { border-bottom: none; }
    .notif-menu-item:hover { background: #f9fafb; }

    /* ── Dashboard layout ── */
    .dashboard {
      display: grid;
      grid-template-columns: 220px 1fr 1fr;
      gap: 16px;
      padding: 16px;
      max-width: 1300px;
      margin: 0 auto;
      align-items: start;
    }

    @media (max-width: 960px) {
      .dashboard { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 620px) {
      .dashboard { grid-template-columns: 1fr; }
    }

    /* ── Panel ── */
    .panel {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      overflow: hidden;
    }

    .panel-header {
      background: #1d3a6e;
      color: #fff;
      padding: 10px 16px;
      font-size: 13px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 7px;
    }

    .panel-body { padding: 1rem; }

    /* ── Student info panel ── */
    .student-avatar-wrap {
      display: flex;
      justify-content: center;
      margin-bottom: 1rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #f3f4f6;
    }

    .student-avatar {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      background: #1d3a6e;
      color: #fff;
      font-size: 28px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 3px solid #e5e7eb;
      overflow: hidden;
    }

    .student-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .info-row {
      display: flex;
      align-items: flex-start;
      gap: 8px;
      font-size: 13px;
      color: #374151;
      margin-bottom: 10px;
    }

    .info-icon {
      flex-shrink: 0;
      width: 16px;
      color: #1d3a6e;
      margin-top: 1px;
    }

    .info-label { font-weight: 600; margin-right: 3px; }

    /* ── Announcement panel ── */
    .announce-feed {
      max-height: 420px;
      overflow-y: auto;
    }

    .announce-item {
      padding: 12px 0;
      border-bottom: 1px solid #f3f4f6;
    }

    .announce-item:last-child { border-bottom: none; }

    .announce-meta {
      font-size: 12px;
      font-weight: 600;
      color: #1d3a6e;
      margin-bottom: 6px;
    }

    .announce-text {
      font-size: 13px;
      color: #4b5563;
      line-height: 1.65;
    }

    /* ── Rules panel ── */
    .rules-body {
      max-height: 460px;
      overflow-y: auto;
      padding: 1rem 1.25rem;
      font-size: 13px;
      color: #374151;
      line-height: 1.75;
    }

    .rules-body h2 {
      font-size: 15px;
      font-weight: 700;
      text-align: center;
      color: #111827;
      margin-bottom: 4px;
    }

    .rules-body h3 {
      font-size: 13px;
      font-weight: 700;
      text-align: center;
      color: #374151;
      margin-bottom: 1rem;
    }

    .rules-body h4 {
      font-size: 13px;
      font-weight: 700;
      color: #111827;
      margin: 1rem 0 0.5rem;
    }

    .rules-body p { margin-bottom: 0.75rem; }

    /* Scrollbar styling */
    .announce-feed::-webkit-scrollbar,
    .rules-body::-webkit-scrollbar { width: 4px; }
    .announce-feed::-webkit-scrollbar-track,
    .rules-body::-webkit-scrollbar-track { background: #f3f4f6; }
    .announce-feed::-webkit-scrollbar-thumb,
    .rules-body::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
  </style>
</head>
<body>

  <!-- ═══ TOP NAVBAR ═══ -->
  <nav class="topnav">
    <span class="topnav-brand">Dashboard</span>

    <div class="topnav-links">

      <!-- Notification dropdown -->
      <div class="notif-dropdown">
        <button class="topnav-link" onclick="toggleNotif(event)">
          Notification ▾
        </button>
        <div class="notif-menu" id="notifMenu">
          <div class="notif-menu-item">No new notifications</div>
        </div>
      </div>

      <a class="topnav-link" href="home.php">Home</a>
      <a class="topnav-link" href="edit_profile.php">Edit Profile</a>
      <a class="topnav-link" href="history.php">History</a>
      <a class="topnav-link" href="reservation.php">Reservation</a>
      <a class="topnav-logout" href="logout.php">Log out</a>
    </div>
  </nav>

  <!-- ═══ DASHBOARD GRID ═══ -->
  <div class="dashboard">

    <!-- ── Col 1: Student Info ── -->
    <div class="panel">
      <div class="panel-header">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Student Information
      </div>
      <div class="panel-body">

        <div class="student-avatar-wrap">
          <div class="student-avatar">
            <?php if (!empty($_SESSION['avatar'])): ?>
              <img src="<?= htmlspecialchars($_SESSION['avatar']) ?>" alt="avatar">
            <?php else: ?>
              <?= $initials ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="info-row">
          <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span><span class="info-label">Name:</span> <?= $fullname ?></span>
        </div>

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

        <div class="info-row">
          <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <span><span class="info-label">Session:</span> <?= $session_credits ?></span>
        </div>

      </div>
    </div>

    <!-- ── Col 2: Announcements ── -->
    <div class="panel">
      <div class="panel-header">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 17H2a3 3 0 0 0 3-3V9a7 7 0 0 1 14 0v5a3 3 0 0 0 3 3z"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        Announcement
      </div>
      <div class="announce-feed panel-body">

        <div class="announce-item">
          <div class="announce-meta">CCS Admin | 2026-Feb-11</div>
          <div class="announce-text"></div>
        </div>

        <div class="announce-item">
          <div class="announce-meta">CCS Admin | 2024-May-08</div>
          <div class="announce-text">Important Announcement: We are excited to announce the launch of our new website! 🎉 Explore our latest products and services now!</div>
        </div>

      </div>
    </div>

    <!-- ── Col 3: Rules and Regulations ── -->
    <div class="panel">
      <div class="panel-header">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Rules and Regulation
      </div>
      <div class="rules-body">
        <h2>University of Cebu</h2>
        <h3>COLLEGE OF INFORMATION &amp; COMPUTER STUDIES</h3>

        <h4>LABORATORY RULES AND REGULATIONS</h4>

        <p>To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>

        <p>1. Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans and other personal pieces of equipment must be switched off.</p>

        <p>2. Games are not allowed inside the lab. This includes computer-related games, card games and other games that may disturb the operation of the lab.</p>

        <p>3. Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing of software are strictly prohibited.</p>

        <p>4. Getting access to other websites not related to the course is strictly prohibited.</p>

        <p>5. Deleting computer files, changing the computer settings and installing games and other software are strictly prohibited.</p>

        <p>6. Observe proper dress code at all times inside the lab. Laboratory gown is required.</p>

        <p>7. Always clean up after yourself. Return all equipment, tools, and materials to their proper places after use.</p>

        <p>8. Food, drinks, and smoking are strictly prohibited inside the laboratory.</p>

        <p>9. Students are not allowed to go to the laboratory without a teacher.</p>

        <p>10. Vandalism of any form is strictly prohibited. Any student caught vandalizing school property will be penalized accordingly.</p>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function toggleNotif(e) {
      e.stopPropagation();
      document.getElementById('notifMenu').classList.toggle('open');
    }
    document.addEventListener('click', () => {
      document.getElementById('notifMenu').classList.remove('open');
    });
  </script>
</body>
</html>