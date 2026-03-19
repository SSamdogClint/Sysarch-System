<?php
// ============================================================
//  student_dashboard.php
// ============================================================
session_start();

// Prevent browser from caching this page
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

if (empty($_SESSION['logged_in'])) {
    header('Location: ../home.php');  // redirect to home, not login
    exit;
}

$firstname       = htmlspecialchars($_SESSION['firstname'] ?? '');
$lastname        = htmlspecialchars($_SESSION['lastname']  ?? '');
$course          = htmlspecialchars($_SESSION['course']    ?? '');
$yearlvl         = htmlspecialchars($_SESSION['yearlvl']   ?? '');
$email           = htmlspecialchars($_SESSION['email']     ?? '');
$addrs           = htmlspecialchars($_SESSION['addrs']     ?? '');
$session_credits = $_SESSION['session_credits'] ?? 30;

$fullname = $lastname . ', ' . $firstname;
$initials = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));
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
  <link rel="stylesheet" href="../css/style.css">
  <style>
    * { box-sizing: border-box; }
    body { background: #eef0f5; font-family: 'Poppins', sans-serif; margin: 0; }

    /* ══ Responsive sidebar ══ */
    @media (max-width: 768px) {
      .sidebar { display: none; }
      .sidebar.open {
        display: block; width: 100%; position: fixed;
        top: 60px; left: 0; bottom: 0; z-index: 99; overflow-y: auto;
      }
      .admin-main { padding: 1.25rem; }
    }

    /* ══ Dashboard 3-col grid ══ */
    .dashboard {
      display: grid;
      grid-template-columns: 220px 1fr 1fr;
      gap: 16px;
      padding: 16px;
      max-width: 1300px;
      margin: 0 auto;
      align-items: start;
    }

    @media (max-width: 960px) { .dashboard { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 620px) { .dashboard { grid-template-columns: 1fr; } }

    /* ══ Panel — admin card style ══ */
    .panel {
      background: #fff;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 1px 4px rgba(0,0,0,0.06);
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

    .panel-body { padding: 1rem 1.25rem; }

    /* ══ Student info ══ */
    .student-avatar-wrap {
      display: flex;
      justify-content: center;
      margin-bottom: 1rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #f3f4f6;
    }

    .student-avatar {
      width: 90px; height: 90px;
      border-radius: 50%;
      background: #1d3a6e;
      color: #fff;
      font-size: 28px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      border: 3px solid #e5e7eb;
      overflow: hidden;
    }

    .student-avatar img { width: 100%; height: 100%; object-fit: cover; }

    .info-row {
      display: flex; align-items: flex-start;
      gap: 8px; font-size: 13px; color: #374151; margin-bottom: 10px;
    }

    .info-icon { flex-shrink: 0; width: 16px; color: #1d3a6e; margin-top: 1px; }
    .info-label { font-weight: 600; margin-right: 3px; }

    /* ══ Announcements ══ */
    .announce-feed { max-height: 420px; overflow-y: auto; }

    .announce-item { padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
    .announce-item:last-child { border-bottom: none; }

    .announce-meta { font-size: 12px; font-weight: 600; color: #1d3a6e; margin-bottom: 6px; }
    .announce-text { font-size: 13px; color: #4b5563; line-height: 1.65; }

    /* ══ Rules ══ */
    .rules-body {
      max-height: 460px; overflow-y: auto;
      padding: 1rem 1.25rem;
      font-size: 13px; color: #374151; line-height: 1.75;
    }

    .rules-body h2 { font-size: 15px; font-weight: 700; text-align: center; color: #111827; margin-bottom: 4px; }
    .rules-body h3 { font-size: 13px; font-weight: 700; text-align: center; color: #374151; margin-bottom: 1rem; }
    .rules-body h4 { font-size: 13px; font-weight: 700; color: #111827; margin: 1rem 0 0.5rem; }
    .rules-body p  { margin-bottom: 0.75rem; }

    /* ══ Notification dropdown ══ */
    .notif-dropdown { position: relative; }

    .notif-menu {
      display: none; position: absolute;
      top: calc(100% + 6px); right: 0;
      background: #fff; border: 1px solid #e5e7eb;
      border-radius: 10px; min-width: 220px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
      z-index: 200; overflow: hidden;
    }

    .notif-menu.open { display: block; }

    .notif-menu-item {
      padding: 10px 16px; font-size: 13px; color: #374151;
      border-bottom: 1px solid #f3f4f6; cursor: pointer;
    }

    .notif-menu-item:last-child { border-bottom: none; }
    .notif-menu-item:hover { background: #f9fafb; }

    /* ══ Scrollbars ══ */
    .announce-feed::-webkit-scrollbar,
    .rules-body::-webkit-scrollbar { width: 4px; }
    .announce-feed::-webkit-scrollbar-track,
    .rules-body::-webkit-scrollbar-track { background: #f3f4f6; }
    .announce-feed::-webkit-scrollbar-thumb,
    .rules-body::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
  </style>
</head>
<body>

  <!-- ═══ NAVBAR — admin style ═══ -->
  <nav class="uc-nav">
    <a class="nav-brand" href="student_dashboard.php">
      <img src="../images/ccsmainlog_nobg.png" alt="UC CCS Logo" class="nav-logo">
      <div>
        <div class="nav-title">UC Sit-in System</div>
        <div class="nav-sub">Main Campus · CCS</div>
      </div>
    </a>

    <button class="nav-toggler" id="navToggler" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>

    <div class="nav-links" id="navLinks">
      <span style="font-size:13px; color:#6b7280; padding: 0 8px;">
        <?= $firstname . ' ' . $lastname ?>
      </span>
      <div class="nav-divider"></div>
      <a class="nav-link" href="../logout.php">Log out</a>
    </div>
  </nav>

  <!-- ═══ LAYOUT WITH SIDEBAR ═══ -->
  <div class="admin-layout">

    <!-- ── Sidebar ── -->
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
      <a class="sidebar-link" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Reservation
      </a>

      <hr class="sidebar-divider">
      <div class="sidebar-section">Records</div>

      <a class="sidebar-link" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Sit-in History
      </a>
      <a class="sidebar-link" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Feedback
      </a>
    </aside>

    <!-- ── Main content ── -->
    <main class="admin-main">

      <!-- ═══ ORIGINAL 3-COL GRID ═══ -->
      <div class="dashboard">

        <!-- Col 1: Student Info -->
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

        <!-- Col 2: Announcements -->
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

        <!-- Col 3: Rules and Regulations -->
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
    </main>
  </div>
  <!-- ═══ EDIT PROFILE MODAL ═══ -->
  <div id="editModal" style="
    display:none; position:fixed; inset:0; z-index:999;
    background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">

    <div style="
      background:#fff; border-radius:16px; width:100%; max-width:540px;
      max-height:90vh; overflow-y:auto; margin:1rem;
      box-shadow:0 20px 60px rgba(0,0,0,0.2); font-family:'Poppins',sans-serif;">

      <!-- Header -->
      <div style="
        background:#1d3a6e; color:#fff; padding:16px 24px;
        border-radius:16px 16px 0 0; display:flex;
        align-items:center; justify-content:space-between;">
        <span style="font-size:14px; font-weight:600;">Edit Profile</span>
        <button onclick="closeModal()" style="
          background:transparent; border:none; color:#fff;
          font-size:20px; cursor:pointer; line-height:1;">✕</button>
      </div>

      <!-- Form -->
      <form action="../update_profile.php" method="post" style="padding:24px;">

        <!-- Student ID -->
        <div style="margin-bottom:14px;">
          <label style="font-size:12px; font-weight:600; color:#374151; display:block; margin-bottom:5px;">Student ID</label>
          <input type="text" name="studentid" value="<?= htmlspecialchars($_SESSION['studentid'] ?? '') ?>" style="
            width:100%; border:1px solid #e5e7eb; border-radius:8px;
            padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
            outline:none; color:#111827;">
        </div>

        <!-- Last & First name -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
          <div>
            <label style="font-size:12px; font-weight:600; color:#374151; display:block; margin-bottom:5px;">Last Name</label>
            <input type="text" name="lastname" value="<?= $lastname ?>" style="
              width:100%; border:1px solid #e5e7eb; border-radius:8px;
              padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
              outline:none; color:#111827;">
          </div>
          <div>
            <label style="font-size:12px; font-weight:600; color:#374151; display:block; margin-bottom:5px;">First Name</label>
            <input type="text" name="firstname" value="<?= $firstname ?>" style="
              width:100%; border:1px solid #e5e7eb; border-radius:8px;
              padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
              outline:none; color:#111827;">
          </div>
        </div>

        <!-- Middle Name -->
        <div style="margin-bottom:14px;">
          <label style="font-size:12px; font-weight:600; color:#374151; display:block; margin-bottom:5px;">Middle Name</label>
          <input type="text" name="middlename" value="<?= htmlspecialchars($_SESSION['middlename'] ?? '') ?>" style="
            width:100%; border:1px solid #e5e7eb; border-radius:8px;
            padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
            outline:none; color:#111827;">
        </div>

        <!-- Course & Year -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
          <div>
            <label style="font-size:12px; font-weight:600; color:#374151; display:block; margin-bottom:5px;">Course</label>
            <input type="text" name="course" value="<?= $course ?>" style="
              width:100%; border:1px solid #e5e7eb; border-radius:8px;
              padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
              outline:none; color:#111827;">
          </div>
          <div>
            <label style="font-size:12px; font-weight:600; color:#374151; display:block; margin-bottom:5px;">Year Level</label>
            <select name="yearlvl" style="
              width:100%; border:1px solid #e5e7eb; border-radius:8px;
              padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
              outline:none; color:#111827; background:#fff;">
              <?php foreach([1=>'1st Year',2=>'2nd Year',3=>'3rd Year',4=>'4th Year'] as $v=>$lbl): ?>
                <option value="<?= $v ?>" <?= $yearlvl == $v ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Email -->
        <div style="margin-bottom:14px;">
          <label style="font-size:12px; font-weight:600; color:#374151; display:block; margin-bottom:5px;">Email</label>
          <input type="email" name="email" value="<?= $email ?>" style="
            width:100%; border:1px solid #e5e7eb; border-radius:8px;
            padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
            outline:none; color:#111827;">
        </div>

        <!-- Address -->
        <div style="margin-bottom:20px;">
          <label style="font-size:12px; font-weight:600; color:#374151; display:block; margin-bottom:5px;">Address</label>
          <input type="text" name="addrs" value="<?= $addrs ?>" style="
            width:100%; border:1px solid #e5e7eb; border-radius:8px;
            padding:9px 13px; font-size:13px; font-family:'Poppins',sans-serif;
            outline:none; color:#111827;">
        </div>

        <!-- Buttons -->
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('navToggler').addEventListener('click', () => {
      document.getElementById('navLinks').classList.toggle('open');
    });

    // Check session on every page show (including back button)
    window.addEventListener('pageshow', function(e) {
      fetch('../check_session.php', { cache: 'no-store' })
        .then(res => res.json())
        .then(data => {
          if (!data.logged_in) {
            window.location.replace('../home.php');
          }
        });
    });
    function openModal() {
      const m = document.getElementById('editModal');
      m.style.display = 'flex';
    }

    function closeModal() {
      const m = document.getElementById('editModal');
      m.style.display = 'none';
    }

    // Close when clicking outside the modal box
    document.getElementById('editModal').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });
  </script>
</body>
</html>