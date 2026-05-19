<?php
// student_module/software_availability.php

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

$student_id   = (int)($_SESSION['student_id'] ?? 0);
$studentid_no = htmlspecialchars($_SESSION['studentid'] ?? '');
$middlename   = htmlspecialchars($_SESSION['middlename'] ?? '');
$firstname    = htmlspecialchars($_SESSION['firstname'] ?? '');
$lastname     = htmlspecialchars($_SESSION['lastname'] ?? '');
$course       = htmlspecialchars($_SESSION['course'] ?? '');
$yearlvl      = htmlspecialchars($_SESSION['yearlvl'] ?? '');
$email        = htmlspecialchars($_SESSION['email'] ?? '');
$addrs        = htmlspecialchars($_SESSION['addrs'] ?? '');
$initials     = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));

require_once '../controllers/announcements/student_notifications.php';

$search = trim($_GET['search'] ?? '');
$labFilter = trim($_GET['lab'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

if (!in_array($statusFilter, ['', 'installed', 'unavailable'], true)) {
    $statusFilter = '';
}

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = '(software_name LIKE ? OR category LIKE ? OR version LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($labFilter !== '') {
    $where[] = 'lab = ?';
    $params[] = $labFilter;
    $types .= 's';
}

if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$softwareList = [];

$stmt = $conn->prepare("
    SELECT * 
    FROM software_availability 
    $whereSql 
    ORDER BY lab ASC, software_name ASC
");

if ($stmt) {
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $softwareList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$labs = [];

$result = $conn->query("
    SELECT DISTINCT lab 
    FROM software_availability 
    ORDER BY lab ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $labs[] = $row['lab'];
    }
}

$totalApps = 0;
$totalLabs = 0;
$installedApps = 0;
$unavailableApps = 0;

$result = $conn->query("
    SELECT 
        COUNT(*) AS total, 
        COUNT(DISTINCT lab) AS labs, 
        SUM(status = 'installed') AS installed, 
        SUM(status = 'unavailable') AS unavailable 
    FROM software_availability
");

if ($result && $row = $result->fetch_assoc()) {
    $totalApps = (int)$row['total'];
    $totalLabs = (int)$row['labs'];
    $installedApps = (int)$row['installed'];
    $unavailableApps = (int)$row['unavailable'];
}

$labCards = [];

$result = $conn->query("
    SELECT 
        lab, 
        COUNT(*) AS total, 
        SUM(status = 'installed') AS installed 
    FROM software_availability 
    GROUP BY lab 
    ORDER BY lab ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $labCards[] = $row;
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

  <title>UC – Software Availability</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/student.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body class="student-dashboard-page">
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
        <i class="bi bi-moon-stars"></i>
        <span>Dark</span>
      </button>

      <span style="font-size:13px; color:#6b7280; padding:0 4px;">
        <?= $firstname . ' ' . $lastname ?>
      </span>

      <div class="nav-divider"></div>

      <a class="nav-link" href="../controllers/auth/logout.php">
        Log out
      </a>
    </div>
  </nav>

  <div class="admin-layout">
    <?php require __DIR__ . '/../includes/student_sidebar.php'; ?>

    <main class="admin-main">
      <div class="container-fluid py-4">

        <div class="row align-items-stretch g-3 mb-4">
          <div class="col-lg-8">
            <div class="card border shadow-sm h-100">
              <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-center gap-3">
                  <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center p-3">
                    <i class="bi bi-pc-display-horizontal fs-3"></i>
                  </div>

                  <div>
                    <h1 class="h3 mb-1">Software Availability</h1>
                    <p class="text-muted mb-0">
                      Check available software applications per laboratory before making a reservation.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="card border shadow-sm h-100">
              <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <p class="text-muted mb-1">Logged in as</p>
                    <h2 class="h5 mb-0"><?= $firstname ?> <?= $lastname ?></h2>
                    <small class="text-muted"><?= $studentid_no ?></small>
                  </div>

                  <div class="rounded-circle bg-body-secondary d-inline-flex align-items-center justify-content-center p-3">
                    <strong><?= $initials ?></strong>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-3">
            <div class="card border shadow-sm h-100">
              <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                  <p class="text-muted mb-1">Total Apps</p>
                  <h3 class="mb-0"><?= $totalApps ?></h3>
                </div>
                <i class="bi bi-window-stack fs-2 text-primary"></i>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="card border shadow-sm h-100">
              <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                  <p class="text-muted mb-1">Labs</p>
                  <h3 class="mb-0"><?= $totalLabs ?></h3>
                </div>
                <i class="bi bi-building fs-2 text-info"></i>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="card border shadow-sm h-100">
              <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                  <p class="text-muted mb-1">Installed</p>
                  <h3 class="mb-0"><?= $installedApps ?></h3>
                </div>
                <i class="bi bi-check-circle fs-2 text-success"></i>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="card border shadow-sm h-100">
              <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                  <p class="text-muted mb-1">Unavailable</p>
                  <h3 class="mb-0"><?= $unavailableApps ?></h3>
                </div>
                <i class="bi bi-x-circle fs-2 text-danger"></i>
              </div>
            </div>
          </div>
        </div>

        <?php if ($labCards): ?>
          <div class="row g-3 mb-4">
            <?php foreach ($labCards as $labCard): ?>
              <div class="col-md-4 col-xl-3">
                <div class="card border shadow-sm h-100">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <h2 class="h6 mb-0"><?= htmlspecialchars($labCard['lab']) ?></h2>
                      <span class="badge text-bg-primary">
                        <?= (int)$labCard['total'] ?> apps
                      </span>
                    </div>

                    <p class="small text-muted mb-0">
                      <?= (int)$labCard['installed'] ?> installed application(s)
                    </p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="card border shadow-sm mb-4">
          <div class="card-header bg-transparent border-bottom p-4">
            <h2 class="h5 mb-0">
              <i class="bi bi-funnel text-primary me-2"></i>
              Filter Software
            </h2>
          </div>

          <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
              <div class="col-lg-4">
                <label class="form-label fw-semibold">Search</label>
                <input 
                  type="text" 
                  class="form-control" 
                  name="search" 
                  value="<?= htmlspecialchars($search) ?>" 
                  placeholder="Search software, category, or version">
              </div>

              <div class="col-lg-3">
                <label class="form-label fw-semibold">Lab</label>
                <select class="form-select" name="lab">
                  <option value="">All Labs</option>
                  <?php foreach ($labs as $lab): ?>
                    <option value="<?= htmlspecialchars($lab) ?>" <?= $labFilter === $lab ? 'selected' : '' ?>>
                      <?= htmlspecialchars($lab) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-lg-3">
                <label class="form-label fw-semibold">Status</label>
                <select class="form-select" name="status">
                  <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All Status</option>
                  <option value="installed" <?= $statusFilter === 'installed' ? 'selected' : '' ?>>Installed</option>
                  <option value="unavailable" <?= $statusFilter === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                </select>
              </div>

              <div class="col-lg-2">
                <button class="btn btn-primary w-100" type="submit">
                  <i class="bi bi-search me-1"></i>
                  Filter
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="card border shadow-sm">
          <div class="card-header bg-transparent border-bottom p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
              <div>
                <h2 class="h5 mb-0">
                  <i class="bi bi-list-check text-primary me-2"></i>
                  Available Applications
                </h2>
                <small class="text-muted">
                  Use this list to choose a lab that has the software you need.
                </small>
              </div>

              <span class="badge text-bg-primary rounded-pill">
                <?= count($softwareList) ?> record(s)
              </span>
            </div>
          </div>

          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="ps-4">#</th>
                    <th>Lab</th>
                    <th>Software Name</th>
                    <th>Category</th>
                    <th>Version</th>
                    <th class="pe-4">Status</th>
                  </tr>
                </thead>

                <tbody>
                  <?php if (!$softwareList): ?>
                    <tr>
                      <td colspan="6" class="text-center text-muted py-5">
                        No software records found.
                      </td>
                    </tr>
                  <?php endif; ?>

                  <?php foreach ($softwareList as $index => $software): ?>
                    <tr>
                      <td class="ps-4"><?= $index + 1 ?></td>

                      <td>
                        <span class="badge text-bg-secondary">
                          <?= htmlspecialchars($software['lab']) ?>
                        </span>
                      </td>

                      <td class="fw-semibold">
                        <?= htmlspecialchars($software['software_name']) ?>
                      </td>

                      <td>
                        <?= htmlspecialchars($software['category'] ?: 'N/A') ?>
                      </td>

                      <td>
                        <?= htmlspecialchars($software['version'] ?: 'N/A') ?>
                      </td>

                      <td class="pe-4">
                        <span class="badge text-bg-<?= $software['status'] === 'installed' ? 'success' : 'danger' ?>">
                          <?= htmlspecialchars(ucfirst($software['status'])) ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
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
        <form action="../controllers/student/update_profile.php" method="POST">
          <input type="hidden" name="student_id" value="<?= (int)$student_id ?>">
          <input type="hidden" name="studentid" value="<?= $studentid_no ?>">
          <input type="hidden" name="middlename" value="<?= $middlename ?>">

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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function applyDarkMode() {
      const enabled = localStorage.getItem('uc_dark_mode') === 'enabled';
      document.body.classList.toggle('dark-mode', enabled);

      const btn = document.getElementById('darkModeToggle');

      if (btn) {
        btn.setAttribute('aria-pressed', enabled ? 'true' : 'false');

        btn.innerHTML = enabled
          ? '<i class="bi bi-sun"></i><span>Light</span>'
          : '<i class="bi bi-moon-stars"></i><span>Dark</span>';
      }
    }

    function toggleDarkMode() {
      const enabled = !document.body.classList.contains('dark-mode');
      localStorage.setItem('uc_dark_mode', enabled ? 'enabled' : 'disabled');
      applyDarkMode();
    }

    applyDarkMode();

    const navToggler = document.getElementById('navToggler');

    if (navToggler) {
      navToggler.addEventListener('click', () => {
        const navLinks = document.getElementById('navLinks');
        const sidebar = document.getElementById('sidebar');

        if (navLinks) {
          navLinks.classList.toggle('open');
        }

        if (sidebar) {
          sidebar.classList.toggle('open');
        }
      });
    }

    function openModal() {
      const modal = document.getElementById('editModal');

      if (modal) {
        modal.style.display = 'flex';
      }
    }

    function closeModal() {
      const modal = document.getElementById('editModal');

      if (modal) {
        modal.style.display = 'none';
      }
    }

    const editModal = document.getElementById('editModal');

    if (editModal) {
      editModal.addEventListener('click', function(e) {
        if (e.target === this) {
          closeModal();
        }
      });
    }

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
      if (!notifDot || !notifBellBtn) return;

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

    if (notifBellBtn && notifMenu) {
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
      if (notifDropdown && notifMenu && !notifDropdown.contains(e.target)) {
        notifMenu.classList.remove('open');
      }
    });

    updateNotifState();
  </script>
</body>
</html>