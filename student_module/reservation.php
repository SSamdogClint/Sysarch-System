<?php
// student_module/reservation.php

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

$reservations = [];
$stmt = $conn->prepare("
    SELECT id, purpose, lab, pc_number, reservation_date, reservation_time,
           COALESCE(reservation_end_time, ADDTIME(reservation_time, '01:00:00')) AS reservation_end_time,
           status, created_at
    FROM lab_reservations
    WHERE student_id = ?
    ORDER BY reservation_date DESC, reservation_time DESC, created_at DESC
");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>UC – Reservation</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/student.css">
  <style>
  
  </style>
</head>
<body class="student-reservation-page student-dashboard-page">

  <nav class="uc-nav">
    <a class="nav-brand" href="student_dashboard.php">
      <img src="../assets/images/ccsmainlog_nobg.png" alt="UC CCS Logo" class="nav-logo">
      <div>
        <div class="nav-title">UC Sit-in System</div>
        <div class="nav-sub">Main Campus · CCS</div>
      </div>
    </a>
    <button class="nav-toggler" id="navToggler" aria-label="Toggle menu"><span></span><span></span><span></span></button>
    <div class="nav-links" id="navLinks">
      <div class="notif-dropdown" id="notifDropdown">
        <button type="button" class="notif-bell-btn" id="notifBellBtn" aria-label="Notifications">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"></path><path d="M10 21a2 2 0 0 0 4 0"></path></svg>
          <span class="notif-dot <?= !empty($notifications) ? 'show' : '' ?>" id="notifDot"></span>
        </button>
        <div class="notif-menu" id="notifMenu">
          <div class="notif-menu-header">Notifications</div>
          <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notif): ?>
              <div class="notif-menu-item">
                <div class="notif-type <?= htmlspecialchars($notif['type']) ?>"><?= $notif['type'] === 'announcement' ? 'Announcement' : 'Session' ?></div>
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
      <span style="font-size:13px;color:#6b7280;padding:0 4px;"><?= $firstname . ' ' . $lastname ?></span>
      <div class="nav-divider"></div>
      <a class="nav-link" href="../controllers/auth/logout.php">Log out</a>
    </div>
  </nav>

  <div class="admin-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-section" style="margin-top:0;">Main</div>

      <a class="sidebar-link" href="student_dashboard.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="7"/>
          <rect x="14" y="3" width="7" height="7"/>
          <rect x="3" y="14" width="7" height="7"/>
          <rect x="14" y="14" width="7" height="7"/>
        </svg>
        Dashboard
      </a>

      <a class="sidebar-link" href="#" onclick="openModal(); return false;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
        Edit Profile
      </a>

      <a class="sidebar-link active" href="reservation.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2"/>
          <line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/>
          <line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
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

      <a class="sidebar-link" href="session_table.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
        Session Table
      </a>

      <a class="sidebar-link" href="sitin_history.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        Sit-in History
      </a>

      <a class="sidebar-link" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        Feedback
      </a>
    </aside>

    <main class="admin-main" style="flex:1;">
      <div class="reservation-grid">
        <section class="reservation-panel">
          <div class="reservation-panel-header">
            <h4>📅 Create Reservation</h4>
            <span style="font-size:11px;color:#bfdbfe;">Select slot first</span>
          </div>
          <div class="reservation-body">
            <div class="message-box" id="messageBox"></div>

            <div class="field">
              <label for="lab">Laboratory</label>
              <select id="lab" name="lab" onchange="loadSeats()">
                <option value="Lab 524">Lab 524</option>
                <option value="Lab 526">Lab 526</option>
                <option value="Lab 528">Lab 528</option>
                <option value="Lab 530">Lab 530</option>
                <option value="Lab 542">Lab 542</option>
                <option value="Lab 544">Lab 544</option>
              </select>
            </div>

            <div class="field">
              <label for="reservationDate">Date</label>
              <input type="date" id="reservationDate" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" onchange="loadSeats()">
            </div>

            <div class="time-row">
              <div class="field">
                <label for="reservationTime">Start Time</label>
                <input type="time" id="reservationTime" value="08:00" onchange="syncEndTime(); loadSeats();">
              </div>
              <div class="field">
                <label for="reservationEndTime">End Time</label>
                <input type="time" id="reservationEndTime" value="09:00" onchange="loadSeats()">
              </div>
            </div>

            <div class="field">
              <label for="purpose">Purpose</label>
              <textarea id="purpose" placeholder="Example: Java programming practice" required></textarea>
            </div>

            <input type="hidden" id="selectedPc">
            <button class="btn-reserve" id="reserveBtn" onclick="submitReservation()" disabled>Choose a PC first</button>
          </div>
        </section>

        <section class="reservation-panel">
          <div class="reservation-panel-header">
            <h4>🖥️ Available PCs</h4>
            <span style="font-size:11px;color:#bfdbfe;">8 PCs × 7 rows · two doors at right</span>
          </div>
          <div class="reservation-body">
            <div class="lab-wrap">
              <div class="lab-title" id="labTitle">Lab 524 Layout</div>
              <div class="teacher-area">Counter / Front</div>
              <div class="door top">DOOR</div>
              <div class="door bottom">DOOR</div>
              <div class="pc-grid" id="pcGrid"></div>
            </div>
            <div class="legend">
              <div class="legend-item"><span class="legend-dot green"></span> Available</div>
              <div class="legend-item"><span class="legend-dot yellow"></span> Pending</div>
              <div class="legend-item"><span class="legend-dot red"></span> Reserved</div>
              <div class="legend-item"><span class="legend-dot gray"></span> Unavailable</div>
              <div class="legend-item"><span class="legend-dot blue"></span> Selected</div>
            </div>
          </div>
        </section>
      </div>

      <div style="padding:0 20px 20px;">
        <section class="reservation-panel">
          <div class="reservation-panel-header"><h4>📋 My Reservation History</h4><span style="font-size:11px;color:#bfdbfe;"><?= count($reservations) ?> records</span></div>
          <div style="overflow-x:auto;">
            <table class="history-table">
              <thead><tr><th>#</th><th>Lab</th><th>PC</th><th>Purpose</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
              <tbody>
                <?php if (empty($reservations)): ?>
                  <tr><td colspan="7" style="text-align:center;color:#9ca3af;padding:24px;">No reservations yet.</td></tr>
                <?php else: ?>
                  <?php foreach ($reservations as $i => $r): ?>
                    <tr>
                      <td><?= $i + 1 ?></td>
                      <td><?= htmlspecialchars($r['lab']) ?></td>
                      <td>PC <?= str_pad((string)(int)$r['pc_number'], 2, '0', STR_PAD_LEFT) ?></td>
                      <td><?= htmlspecialchars($r['purpose']) ?></td>
                      <td><?= date('M d, Y', strtotime($r['reservation_date'])) ?></td>
                      <td><?= date('h:i A', strtotime($r['reservation_time'])) ?> - <?= date('h:i A', strtotime($r['reservation_end_time'])) ?></td>
                      <td><span class="status-pill status-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
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
  const pcGrid = document.getElementById('pcGrid');
  const reserveBtn = document.getElementById('reserveBtn');

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

  function addOneHour(timeValue) {
    if (!timeValue) return '';
    const [h, m] = timeValue.split(':').map(Number);
    const date = new Date(2000, 0, 1, h, m);
    date.setHours(date.getHours() + 1);
    return String(date.getHours()).padStart(2, '0') + ':' + String(date.getMinutes()).padStart(2, '0');
  }

  function syncEndTime() {
    document.getElementById('reservationEndTime').value = addOneHour(document.getElementById('reservationTime').value);
  }

  function seatOrder() {
    const order = [];
    for (let row = 0; row < 7; row++) {
      for (let col = 8; col >= 1; col--) {
        order.push(row * 8 + col);
      }
    }
    return order;
  }

  function showMessage(type, text) {
    const box = document.getElementById('messageBox');
    box.className = 'message-box ' + type;
    box.textContent = text;
    box.style.display = 'block';
  }

  function clearSelection() {
    document.getElementById('selectedPc').value = '';
    reserveBtn.disabled = true;
    reserveBtn.textContent = 'Choose a PC first';
  }

  function loadSeats() {
    clearSelection();
    const lab = document.getElementById('lab').value;
    const date = document.getElementById('reservationDate').value;
    const time = document.getElementById('reservationTime').value;
    const endTime = document.getElementById('reservationEndTime').value;
    document.getElementById('labTitle').textContent = lab + ' Layout';

    if (!lab || !date || !time || !endTime) return;

    if (endTime <= time) {
      pcGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#b91c1c;padding:25px;">End time must be later than start time.</div>';
      return;
    }

    pcGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#9ca3af;padding:25px;">Loading seats...</div>';

    fetch(`../controllers/reservation/get_seats.php?lab=${encodeURIComponent(lab)}&date=${encodeURIComponent(date)}&time=${encodeURIComponent(time)}&end_time=${encodeURIComponent(endTime)}`)
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          pcGrid.innerHTML = `<div style="grid-column:1/-1;text-align:center;color:#b91c1c;padding:25px;">${data.message || 'Failed to load seats.'}</div>`;
          return;
        }

        const byPc = {};
        data.seats.forEach(seat => byPc[seat.pc_number] = seat);
        pcGrid.innerHTML = '';

        seatOrder().forEach(pc => {
          const seat = byPc[pc];
          const visualStatus = seat.layout_status || seat.status;
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'pc-seat ' + visualStatus;
          btn.innerHTML = `<strong>PC ${String(pc).padStart(2, '0')}</strong><span>${visualStatus}</span>`;
          btn.title = seat.fullname ? `${seat.fullname} (${seat.reservation_status})` : visualStatus;

          if (visualStatus === 'available') {
            btn.addEventListener('click', () => selectPc(pc, btn));
          }
          pcGrid.appendChild(btn);
        });
      })
      .catch(() => {
        pcGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#b91c1c;padding:25px;">Something went wrong while loading seats.</div>';
      });
  }

  function selectPc(pc, btn) {
    document.querySelectorAll('.pc-seat.selected').forEach(el => el.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('selectedPc').value = pc;
    reserveBtn.disabled = false;
    reserveBtn.textContent = 'Reserve PC ' + String(pc).padStart(2, '0');
  }

  function submitReservation() {
    const startTime = document.getElementById('reservationTime').value;
    const endTime = document.getElementById('reservationEndTime').value;

    if (endTime <= startTime) {
      showMessage('error', 'End time must be later than start time.');
      return;
    }

    const formData = new FormData();
    formData.append('lab', document.getElementById('lab').value);
    formData.append('reservation_date', document.getElementById('reservationDate').value);
    formData.append('reservation_time', startTime);
    formData.append('reservation_end_time', endTime);
    formData.append('purpose', document.getElementById('purpose').value.trim());
    formData.append('pc_number', document.getElementById('selectedPc').value);

    if (!formData.get('purpose')) {
      showMessage('error', 'Please enter the purpose of your reservation.');
      return;
    }

    fetch('../controllers/reservation/create_reservation.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          showMessage('error', data.message || 'Reservation failed.');
          return;
        }
        showMessage('success', data.message || 'Reservation submitted.');
        setTimeout(() => window.location.reload(), 900);
      })
      .catch(() => showMessage('error', 'Something went wrong while saving reservation.'));
  }

  loadSeats();
</script>
</body>
</html>
