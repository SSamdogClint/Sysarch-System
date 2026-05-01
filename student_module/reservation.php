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
    body.student-reservation-page { background:#eef0f5; font-family:'Poppins',sans-serif; }
    .reservation-grid { display:grid; grid-template-columns: 330px 1fr; gap:20px; padding:20px; }
    .reservation-panel { background:#fff; border-radius:14px; box-shadow:0 1px 4px rgba(0,0,0,.06); overflow:hidden; }
    .reservation-panel-header { background:#1d3a6e; color:#fff; padding:14px 18px; display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .reservation-panel-header h4 { margin:0; font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px; }
    .reservation-body { padding:18px; }
    .field { margin-bottom:13px; }
    .field label { display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px; }
    .field input, .field select, .field textarea { width:100%; border:1px solid #e5e7eb; border-radius:9px; padding:10px 12px; font-size:13px; outline:none; font-family:'Poppins',sans-serif; }
    .field textarea { min-height:86px; resize:vertical; }
    .field input:focus, .field select:focus, .field textarea:focus { border-color:#1d4ed8; box-shadow:0 0 0 3px rgba(29,78,216,.08); }
    .time-row { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .btn-reserve { width:100%; border:0; border-radius:10px; padding:11px 14px; background:#1d4ed8; color:#fff; font-weight:700; font-size:13px; font-family:'Poppins',sans-serif; cursor:pointer; }
    .btn-reserve:hover { background:#1e40af; }
    .btn-reserve:disabled { background:#9ca3af; cursor:not-allowed; }
    .lab-wrap { position:relative; background:#f8fafc; border:1px solid #e5e7eb; border-radius:16px; padding:52px 58px 52px 22px; min-height:520px; }
    .lab-title { position:absolute; top:14px; left:18px; font-size:13px; font-weight:800; color:#111827; }
    .teacher-area { position:absolute; top:12px; right:58px; background:#111827; color:#fff; font-size:11px; font-weight:700; padding:8px 12px; border-radius:10px; }
    .door { position:absolute; right:10px; width:38px; height:72px; border:2px solid #94a3b8; border-right:0; border-radius:12px 0 0 12px; background:#e0f2fe; color:#075985; font-size:10px; font-weight:800; display:flex; align-items:center; justify-content:center; writing-mode:vertical-rl; text-orientation:mixed; }
    .door.top { top:82px; }
    .door.bottom { bottom:34px; }
    .pc-grid { display:grid; grid-template-columns:repeat(8, minmax(58px, 1fr)); gap:12px; }
    .pc-seat { border:0; border-radius:14px; min-height:58px; padding:8px 6px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px; font-family:'Poppins',sans-serif; cursor:pointer; transition:transform .12s, box-shadow .12s; }
    .pc-seat strong { font-size:13px; }
    .pc-seat span { font-size:10px; font-weight:700; }
    .pc-seat.available { background:#dcfce7; color:#166534; border:1px solid #86efac; }
    .pc-seat.reserved, .pc-seat.pending { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; cursor:not-allowed; }
    .pc-seat.unavailable { background:#e5e7eb; color:#6b7280; border:1px solid #d1d5db; cursor:not-allowed; }
    .pc-seat.selected { background:#dbeafe; color:#1d4ed8; border:2px solid #1d4ed8; box-shadow:0 6px 14px rgba(29,78,216,.18); transform:translateY(-1px); }
    .legend { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; font-size:12px; color:#4b5563; }
    .legend-item { display:flex; align-items:center; gap:6px; }
    .legend-dot { width:13px; height:13px; border-radius:4px; display:inline-block; }
    .legend-dot.green { background:#22c55e; } .legend-dot.red { background:#ef4444; } .legend-dot.gray { background:#9ca3af; } .legend-dot.blue { background:#3b82f6; }
    .message-box { display:none; padding:10px 12px; border-radius:9px; font-size:12px; margin-bottom:12px; }
    .message-box.success { background:#f0fdf4; border:1px solid #86efac; color:#166534; }
    .message-box.error { background:#fef2f2; border:1px solid #fca5a5; color:#b91c1c; }
    .history-table { width:100%; border-collapse:collapse; font-size:12px; }
    .history-table th, .history-table td { padding:11px 12px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
    .history-table th { color:#6b7280; font-size:11px; text-transform:uppercase; background:#f9fafb; }
    .status-pill { display:inline-block; padding:4px 9px; border-radius:999px; font-size:10px; font-weight:800; text-transform:uppercase; }
    .status-pending { background:#fef3c7; color:#92400e; }
    .status-approved { background:#dcfce7; color:#166534; }
    .status-rejected { background:#fee2e2; color:#991b1b; }
    .status-cancelled { background:#e5e7eb; color:#374151; }
    .status-done { background:#dbeafe; color:#1d4ed8; }
    @media (max-width: 1050px) { .reservation-grid { grid-template-columns:1fr; } .pc-grid { grid-template-columns:repeat(4, 1fr); } }
    @media (max-width: 768px) { .sidebar { display:none; } .sidebar.open { display:block; width:100%; position:fixed; top:60px; left:0; bottom:0; z-index:99; overflow-y:auto; } .admin-main { padding:0; } .reservation-grid { padding:12px; } .lab-wrap { padding-right:50px; } .time-row { grid-template-columns:1fr; } }
  </style>
</head>
<body class="student-reservation-page">

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
      <a class="sidebar-link" href="student_dashboard.php">Dashboard</a>
      <a class="sidebar-link active" href="reservation.php">Reservation</a>
      <hr class="sidebar-divider">
      <div class="sidebar-section">Records</div>
      <a class="sidebar-link" href="announcements.php">Announcements</a>
      <a class="sidebar-link" href="sitin_history.php">Sit-in History</a>
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
              <div class="legend-item"><span class="legend-dot red"></span> Reserved/Pending</div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const pcGrid = document.getElementById('pcGrid');
  const reserveBtn = document.getElementById('reserveBtn');

  document.getElementById('navToggler').addEventListener('click', () => {
    document.getElementById('navLinks').classList.toggle('open');
    document.getElementById('sidebar').classList.toggle('open');
  });

  const bell = document.getElementById('notifBellBtn');
  if (bell) {
    bell.addEventListener('click', () => document.getElementById('notifMenu').classList.toggle('open'));
  }

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
