<?php
// student_module/session_table.php

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
$studentid_no = htmlspecialchars($_SESSION['studentid'] ?? '');
$middlename = htmlspecialchars($_SESSION['middlename'] ?? '');
$firstname  = htmlspecialchars($_SESSION['firstname'] ?? '');
$lastname   = htmlspecialchars($_SESSION['lastname'] ?? '');
$course     = htmlspecialchars($_SESSION['course'] ?? '');
$yearlvl    = htmlspecialchars($_SESSION['yearlvl'] ?? '');
$email      = htmlspecialchars($_SESSION['email'] ?? '');
$addrs      = htmlspecialchars($_SESSION['addrs'] ?? '');
$initials   = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));

require_once '../controllers/announcements/student_notifications.php';

/*
  This page is based on sitin_records, not lab_reservations.
  It will automatically use a time-out column if your sitin_records table has one.
  Supported possible time-out columns:
  - logout_time
  - time_out
  - end_time
  - ended_at
  - updated_at
*/

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

$timeout_select = $timeout_column ? ", $timeout_column AS time_out" : ", NULL AS time_out";
$pc_column_select = in_array('pc_number', $table_columns, true) ? ', pc_number' : ', NULL AS pc_number';

$sessions = [];
$stmt = $conn->prepare("
    SELECT id, purpose, lab, login_time, status, session_at_sitin
           $pc_column_select
           $timeout_select
    FROM sitin_records
    WHERE student_id = ?
    ORDER BY login_time DESC
");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}
$stmt->close();

function formatDateLabel($datetime) {
    if (!$datetime) return '—';
    return date('M d, Y', strtotime($datetime));
}

function formatTimeLabel($datetime) {
    if (!$datetime) return '—';
    return date('h:i A', strtotime($datetime));
}

function formatDurationLabel($startDatetime, $endDatetime) {
    if (!$startDatetime || !$endDatetime) return '—';

    $start = strtotime($startDatetime);
    $end   = strtotime($endDatetime);

    if (!$start || !$end || $end <= $start) return '—';

    $minutes = max(0, (int)(($end - $start) / 60));
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

function statusLabel($status) {
    $status = strtolower(trim($status ?? ''));

    if ($status === 'active') {
        return 'active';
    }

    if ($status === 'done') {
        return 'done';
    }

    return $status ?: 'unknown';
}

$done_count = 0;
$active_count = 0;

foreach ($sessions as $s) {
    if (($s['status'] ?? '') === 'active') {
        $active_count++;
    }

    if (($s['status'] ?? '') === 'done') {
        $done_count++;
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
  <title>UC – Session Table</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/student.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    
  </style>
</head>
<body class="student-session-table-page student-dashboard-page">
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
      <a class="nav-link" href="../controllers/auth/logout.php">Log out</a>
    </div>
  </nav>

  <div class="admin-layout">
    <?php require __DIR__ . '/../includes/student_sidebar.php'; ?>

    <main class="admin-main">
      <section class="session-card">
        <div class="session-header">
          <h4><i class="bi bi-clock-history"></i> My Session Table</h4>
          <span><?= count($sessions) ?> overall sit-in session records</span>
        </div>

        <div class="session-summary">
          <div class="summary-box">
            <strong><?= count($sessions) ?></strong>
            <span>Total Sit-in Sessions</span>
          </div>
          <div class="summary-box">
            <strong><?= $active_count ?></strong>
            <span>Active Sessions</span>
          </div>
          <div class="summary-box">
            <strong><?= $done_count ?></strong>
            <span>Completed Sessions</span>
          </div>
        </div>

        <div class="session-toolbar">
          <div class="filter-field">
            <label for="searchInput">Search</label>
            <input class="filter-control" type="text" id="searchInput" placeholder="Search lab, purpose, or status..." oninput="filterRows()">
          </div>

          <div class="filter-field">
            <label for="statusFilter">Status</label>
            <select class="filter-control" id="statusFilter" onchange="filterRows()">
              <option value="all">All Status</option>
              <option value="active">Active</option>
              <option value="done">Done</option>
            </select>
          </div>

          <div class="filter-field">
            <label for="dateFilter">Date</label>
            <input class="filter-control" type="date" id="dateFilter" onchange="filterRows()">
          </div>
        </div>

        <div class="table-wrap">
          <table class="session-table" id="sessionTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Lab</th>
                <th>PC No.</th>
                <th>Purpose</th>
                <th>Date</th>
                <th>Time-In</th>
                <th>Time-Out</th>
                <th>Duration</th>
                <th>Session Credit</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="sessionBody">
              <?php if (empty($sessions)): ?>
                <tr class="empty-default">
                  <td colspan="10" class="empty-row">No sit-in session records found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($sessions as $i => $s): ?>
                  <?php
                    $computed = statusLabel($s['status']);
                    $dateFilter = $s['login_time'] ? date('Y-m-d', strtotime($s['login_time'])) : '';
                    $duration = formatDurationLabel($s['login_time'], $s['time_out']);
                    $searchText = strtolower(
                        ($s['lab'] ?? '') . ' ' .
                        ($s['purpose'] ?? '') . ' ' .
                        $computed
                    );
                  ?>
                  <tr
                    data-search="<?= htmlspecialchars($searchText) ?>"
                    data-status="<?= htmlspecialchars($computed) ?>"
                    data-date="<?= htmlspecialchars($dateFilter) ?>"
                  >
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($s['lab'] ?? '—') ?></td>
                    <td><?= !empty($s['pc_number']) ? 'PC ' . htmlspecialchars($s['pc_number']) : '—' ?></td>
                    <td><?= htmlspecialchars($s['purpose'] ?? '—') ?></td>
                    <td><?= formatDateLabel($s['login_time']) ?></td>
                    <td><?= formatTimeLabel($s['login_time']) ?></td>
                    <td><?= $s['time_out'] ? formatTimeLabel($s['time_out']) : '—' ?></td>
                    <td><?= htmlspecialchars($duration) ?></td>
                    <td><?= htmlspecialchars($s['session_at_sitin'] ?? '—') ?></td>
                    <td>
                      <span class="status-pill status-<?= htmlspecialchars($computed) ?>">
                        <?= htmlspecialchars($computed) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="session-footer">
          <span id="rowCountLabel">Showing <?= count($sessions) ?> records</span>
          <span>
            <?php if ($timeout_column): ?>
              Time-out and duration are based on <strong><?= htmlspecialchars($timeout_column) ?></strong>.
            <?php else: ?>
              Time-out and duration will show once a time-out column is added to sit-in records.
            <?php endif; ?>
          </span>
        </div>
      </section>
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

    document.getElementById('navToggler').addEventListener('click', () => {
      document.getElementById('navLinks').classList.toggle('open');
      document.getElementById('sidebar').classList.toggle('open');
    });

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

    function filterRows() {
      const search = document.getElementById('searchInput').value.trim().toLowerCase();
      const status = document.getElementById('statusFilter').value;
      const date = document.getElementById('dateFilter').value;
      const rows = document.querySelectorAll('#sessionBody tr[data-search]');
      const emptyDefault = document.querySelector('.empty-default');
      let shown = 0;

      if (emptyDefault) {
        document.getElementById('rowCountLabel').textContent = 'Showing 0 records';
        return;
      }

      rows.forEach(row => {
        const matchesSearch = !search || row.dataset.search.includes(search);
        const matchesStatus = status === 'all' || row.dataset.status === status;
        const matchesDate = !date || row.dataset.date === date;

        if (matchesSearch && matchesStatus && matchesDate) {
          row.style.display = '';
          shown++;
        } else {
          row.style.display = 'none';
        }
      });

      let noResultsRow = document.getElementById('noResultsRow');
      if (!noResultsRow) {
        noResultsRow = document.createElement('tr');
        noResultsRow.id = 'noResultsRow';
        noResultsRow.innerHTML = '<td colspan="10" class="empty-row">No matching sit-in session records found.</td>';
        document.getElementById('sessionBody').appendChild(noResultsRow);
      }

      noResultsRow.style.display = shown === 0 ? '' : 'none';
      document.getElementById('rowCountLabel').textContent = 'Showing ' + shown + ' records';
    }
  </script>
</body>
</html>
