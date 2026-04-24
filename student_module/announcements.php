<?php
// student_module/announcements.php
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>UC – Announcements</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    * { box-sizing: border-box; }
    body { background: #eef0f5; font-family: 'Poppins', sans-serif; margin: 0; }

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
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: rgba(255,255,255,0.2);
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .toolbar {
      padding: 14px 20px;
      border-bottom: 1px solid #f3f4f6;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      background: #fff;
    }

    .toolbar-left {
      font-size: 13px;
      color: #6b7280;
      font-weight: 500;
    }

    .search-input {
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 8px 14px;
      font-size: 13px;
      font-family: 'Poppins', sans-serif;
      outline: none;
      color: #111827;
      width: 270px;
      background: #fff;
    }

    .search-input:focus {
      border-color: #1d4ed8;
      box-shadow: 0 0 0 3px rgba(29,78,216,0.08);
    }

    .announce-wrap {
      padding: 20px;
      background: #fff;
    }

    .announce-list {
      display: grid;
      gap: 14px;
    }

    .announce-item {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 16px;
      transition: box-shadow .15s ease;
    }

    .announce-item:hover {
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    .announce-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 10px;
      margin-bottom: 8px;
      flex-wrap: wrap;
    }

    .announce-title {
      font-size: 15px;
      font-weight: 700;
      color: #111827;
      margin: 0 0 4px 0;
    }

    .announce-meta {
      font-size: 12px;
      color: #6b7280;
      line-height: 1.5;
    }

    .announce-badge {
      display: inline-block;
      background: #dbeafe;
      color: #1d4ed8;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
      padding: 4px 10px;
      white-space: nowrap;
    }

    .announce-message {
      font-size: 13px;
      color: #374151;
      line-height: 1.75;
      white-space: pre-wrap;
      word-break: break-word;
    }

    .empty-state,
    .loading-state,
    .error-state {
      padding: 2.5rem 1rem;
      text-align: center;
      font-size: 13px;
      color: #9ca3af;
    }

    .error-state {
      color: #b91c1c;
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
      <a class="nav-link"href="../controllers/auth/logout.php">Log out</a>
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
      
      <a class="sidebar-link active" href="announcements.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
        Announcements
      </a>
      
      <a class="sidebar-link" href="sitin_history.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Sit-in History
      </a>
    </aside>

    <main class="admin-main">
      <div class="page-card">
        <div class="page-card-header">
          <h4>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
            Announcements
          </h4>
          <div class="header-right">
            <div class="header-avatar"><?= $initials ?></div>
            <?= $firstname . ' ' . $lastname ?>
          </div>
        </div>

        <div class="toolbar">
          <div class="toolbar-left">Latest school and lab announcements</div>
          <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search announcements...">
        </div>

        <div class="announce-wrap">
          <div id="announceState" class="loading-state">Loading announcements...</div>
          <div id="announceList" class="announce-list" style="display:none;"></div>
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
            <button type="button" onclick="closeModal()" style="padding:9px 20px; border:1px solid #d1d5db; border-radius:8px; background:#fff; font-size:13px; font-weight:500; font-family:'Poppins',sans-serif; cursor:pointer; color:#374151;">
              Cancel
            </button>
            <button type="submit" style="padding:9px 24px; background:#1d3a6e; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer;">
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

    const announceList = document.getElementById('announceList');
    const announceState = document.getElementById('announceState');
    const searchInput = document.getElementById('searchInput');
    let allAnnouncements = [];

    function escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function formatDate(dateStr) {
      const d = new Date(dateStr);
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

    function renderAnnouncements(list) {
      announceList.innerHTML = '';

      if (!list.length) {
        announceList.style.display = 'none';
        announceState.className = 'empty-state';
        announceState.textContent = 'No announcements found.';
        announceState.style.display = 'block';
        return;
      }

      announceState.style.display = 'none';
      announceList.style.display = 'grid';

      list.forEach(item => {
        const card = document.createElement('div');
        card.className = 'announce-item';

        const title = item.title && item.title.trim() ? item.title : 'Untitled Announcement';
        const postedBy = item.posted_by && item.posted_by.trim() ? item.posted_by : 'Administrator';
        const message = item.message || '';

        card.innerHTML = `
          <div class="announce-top">
            <div>
              <div class="announce-title">${escapeHtml(title)}</div>
              <div class="announce-meta">
                Posted by ${escapeHtml(postedBy)}<br>
                ${escapeHtml(formatDate(item.created_at))}
              </div>
            </div>
            <div class="announce-badge">Announcement</div>
          </div>
          <div class="announce-message">${escapeHtml(message)}</div>
        `;

        announceList.appendChild(card);
      });
    }

    function filteredAnnouncements() {
      const q = searchInput.value.trim().toLowerCase();
      if (!q) return allAnnouncements;

      return allAnnouncements.filter(item =>
        (item.title || '').toLowerCase().includes(q) ||
        (item.message || '').toLowerCase().includes(q) ||
        (item.posted_by || '').toLowerCase().includes(q)
      );
    }

    function loadAnnouncements() {
      announceState.className = 'loading-state';
      announceState.textContent = 'Loading announcements...';
      announceState.style.display = 'block';
      announceList.style.display = 'none';

      fetch('../controllers/announcements/get_announcements.php', { cache: 'no-store' })
        .then(res => res.json())
        .then(data => {
          if (!Array.isArray(data)) {
            throw new Error('Invalid announcement response.');
          }

          allAnnouncements = data;
          renderAnnouncements(filteredAnnouncements());
        })
        .catch(() => {
          announceList.style.display = 'none';
          announceState.className = 'error-state';
          announceState.textContent = 'Failed to load announcements.';
          announceState.style.display = 'block';
        });
    }

    searchInput.addEventListener('input', () => {
      renderAnnouncements(filteredAnnouncements());
    });

    loadAnnouncements();
  </script>
</body>
</html>