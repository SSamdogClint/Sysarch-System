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
  <link rel="stylesheet" href="../assets/css/student.css">
</head>
<body class="student-announcements-page">
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
        <span class="dark-toggle-icon" id="darkModeIcon">🌙</span>
        <span id="darkModeText">Dark</span>
      </button>

      <span class="student-nav-name">
        <?= $firstname . ' ' . $lastname ?>
      </span>

      <div class="nav-divider"></div>
      <a class="nav-link" href="../controllers/auth/logout.php">Log out</a>
    </div>
  </nav>

  <div class="admin-layout">
    <?php require __DIR__ . '/../includes/student_sidebar.php'; ?>

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

  <div id="editModal" class="profile-modal">
    <div class="profile-modal-dialog">
      <div class="profile-modal-header">
        <span>Edit Profile</span>
        <button type="button" class="profile-modal-close" onclick="closeModal()">✕</button>
      </div>

      <div class="profile-modal-body">
        <form action="../controllers/student/update_profile.php" method="POST">
          <input type="hidden" name="student_id" value="<?= (int)$student_id ?>">
          <input type="hidden" name="studentid" value="<?= htmlspecialchars($_SESSION['studentid'] ?? '') ?>">
          <input type="hidden" name="middlename" value="<?= htmlspecialchars($_SESSION['middlename'] ?? '') ?>">
          <input type="hidden" name="redirect" value="student">

          <div class="profile-form-group">
            <label>First Name</label>
            <input type="text" name="firstname" value="<?= $firstname ?>">
          </div>

          <div class="profile-form-group">
            <label>Last Name</label>
            <input type="text" name="lastname" value="<?= $lastname ?>">
          </div>

          <div class="profile-form-group">
            <label>Course</label>
            <input type="text" name="course" value="<?= $course ?>">
          </div>

          <div class="profile-form-group">
            <label>Year Level</label>
            <input type="text" name="yearlvl" value="<?= $yearlvl ?>">
          </div>

          <div class="profile-form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= $email ?>">
          </div>

          <div class="profile-form-group">
            <label>Address</label>
            <input type="text" name="addrs" value="<?= $addrs ?>">
          </div>

          <div class="profile-modal-actions">
            <button type="button" class="btn-profile-cancel" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn-profile-save">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function applyDarkMode() {
      const enabled = localStorage.getItem('uc_dark_mode') === 'enabled';
      document.body.classList.toggle('dark-mode', enabled);

      const icon = document.getElementById('darkModeIcon');
      const text = document.getElementById('darkModeText');
      const button = document.getElementById('darkModeToggle');

      if (button) {
        button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
      }

      if (icon && text) {
        icon.textContent = enabled ? '☀️' : '🌙';
        text.textContent = enabled ? 'Light' : 'Dark';
      }
    }

    function toggleDarkMode() {
      const enabled = !document.body.classList.contains('dark-mode');
      localStorage.setItem('uc_dark_mode', enabled ? 'enabled' : 'disabled');
      applyDarkMode();
    }

    applyDarkMode();

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