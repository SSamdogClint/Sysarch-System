<?php
// admin_module/Admin_Reservation.php

session_start();
require_once '../config/db_config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ../login_page.php');
    exit;
}

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator');

$reservations = [];
$result = $conn->query("
    SELECT id, studentid, fullname, purpose, lab, pc_number, reservation_date, reservation_time,
           COALESCE(reservation_end_time, ADDTIME(reservation_time, '01:00:00')) AS reservation_end_time,
           status, created_at
    FROM lab_reservations
    ORDER BY reservation_date DESC, reservation_time DESC, created_at DESC
");
if ($result) {
    $reservations = $result->fetch_all(MYSQLI_ASSOC);
}

$now = time();
$pending_count = 0;
$current_approved_count = 0;
$history_count = 0;
foreach ($reservations as $r) {
    $end_ts = strtotime($r['reservation_date'] . ' ' . $r['reservation_end_time']);
    if ($r['status'] === 'pending') {
        $pending_count++;
    }
    if ($r['status'] === 'approved' && $end_ts >= $now) {
        $current_approved_count++;
    }
    if (($r['status'] === 'approved' && $end_ts < $now) || in_array($r['status'], ['rejected', 'cancelled', 'done'], true)) {
        $history_count++;
    }
}

$default_labs = ['Lab 524', 'Lab 526', 'Lab 528', 'Lab 530', 'Lab 542', 'Lab 544', 'Lab 1', 'Lab 2', 'Lab 3', 'Lab 4', 'Lab 5'];
$labs = $default_labs;
foreach ($reservations as $r) {
    if (!in_array($r['lab'], $labs, true)) {
        $labs[] = $r['lab'];
    }
}

$default_slot = null;
foreach ($reservations as $r) {
    $end_ts = strtotime($r['reservation_date'] . ' ' . $r['reservation_end_time']);
    if ($r['status'] === 'pending' || ($r['status'] === 'approved' && $end_ts >= $now)) {
        $default_slot = $r;
        break;
    }
}
if (!$default_slot && !empty($reservations)) {
    $default_slot = $reservations[0];
}

$default_lab = $default_slot['lab'] ?? $labs[0];
$default_date = $default_slot['reservation_date'] ?? date('Y-m-d');
$default_time = isset($default_slot['reservation_time']) ? substr($default_slot['reservation_time'], 0, 5) : '08:00';
$default_end_time = isset($default_slot['reservation_end_time']) ? substr($default_slot['reservation_end_time'], 0, 5) : '09:00';
$default_pc = isset($default_slot['pc_number']) ? (int)$default_slot['pc_number'] : 1;

$time_slots = [
    '07:30' => '07:30 AM',
    '08:00' => '08:00 AM',
    '08:30' => '08:30 AM',
    '09:00' => '09:00 AM',
    '09:30' => '09:30 AM',
    '10:00' => '10:00 AM',
    '10:30' => '10:30 AM',
    '11:00' => '11:00 AM',
    '11:30' => '11:30 AM',
    '12:00' => '12:00 PM',
    '13:00' => '01:00 PM',
    '13:30' => '01:30 PM',
    '14:00' => '02:00 PM',
    '14:30' => '02:30 PM',
    '15:00' => '03:00 PM',
    '15:30' => '03:30 PM',
    '16:00' => '04:00 PM',
    '16:30' => '04:30 PM',
    '17:00' => '05:00 PM',
    '18:00' => '06:00 PM'
];
if (!array_key_exists($default_time, $time_slots)) {
    $time_slots = [$default_time => date('h:i A', strtotime($default_time))] + $time_slots;
}
if (!array_key_exists($default_end_time, $time_slots)) {
    $time_slots = [$default_end_time => date('h:i A', strtotime($default_end_time))] + $time_slots;
}

$reservation_json = json_encode($reservations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>UC – Admin Reservation</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <style>
    body.admin-reservation-page { background:#f4f6fb; font-family:'Poppins',sans-serif; color:#172554; }
    body.admin-reservation-page .admin-main { flex:1; padding:18px 22px; }
    .reservation-shell { display:grid; gap:14px; }
    .reservation-management, .history-card { background:#fff; border:1px solid #e6ebf3; border-radius:14px; box-shadow:0 3px 12px rgba(15,23,42,.06); overflow:hidden; }
    .rm-header { min-height:54px; padding:0 22px; background:linear-gradient(90deg,#092d75,#0b3c9a); color:#fff; display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .rm-title { display:flex; align-items:center; gap:12px; font-size:19px; font-weight:800; letter-spacing:-.2px; }
    .rm-title .bi { font-size:24px; }
    .summary-mini { display:flex; align-items:center; gap:10px; font-size:12px; color:#cfe0ff; flex-wrap:wrap; }
    .mini-pill { background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18); color:#fff; padding:5px 10px; border-radius:999px; font-weight:700; }
    .rm-controls { padding:18px 22px 12px; display:grid; grid-template-columns:170px 190px 150px 150px 150px 1fr; gap:14px; align-items:end; }
    .filter-field label { display:block; color:#40557c; font-size:12px; font-weight:700; margin-bottom:7px; }
    .filter-control { width:100%; height:38px; border:1px solid #dce4f0; background:#fff; border-radius:8px; padding:0 12px; color:#1f3155; font-size:13px; font-family:'Poppins',sans-serif; outline:none; box-shadow:0 1px 2px rgba(15,23,42,.03); }
    .filter-control:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
    .search-field { position:relative; }
    .search-field .bi { position:absolute; left:13px; bottom:11px; color:#64748b; font-size:14px; }
    .search-field input { padding-left:38px; }
    .legend-row { padding:0 22px 14px; display:flex; justify-content:flex-end; gap:24px; font-size:12px; font-weight:700; color:#40557c; flex-wrap:wrap; }
    .legend-item { display:inline-flex; align-items:center; gap:7px; }
    .dot { width:11px; height:11px; border-radius:50%; display:inline-block; }
    .dot.available { background:#22c55e; } .dot.pending { background:#facc15; } .dot.reserved { background:#ef4444; } .dot.unavailable { background:#94a3b8; }
    .workspace { padding:0 20px 16px; display:grid; grid-template-columns:minmax(650px,2fr) minmax(320px,.95fr); gap:18px; align-items:stretch; }
    .layout-card, .details-card { background:#fff; border:1px solid #dfe7f2; border-radius:12px; box-shadow:0 2px 8px rgba(15,23,42,.04); }
    .layout-card { padding:13px 18px 16px; min-height:388px; }
    .layout-card-title { display:flex; align-items:center; gap:12px; color:#0f2f72; font-size:16px; font-weight:800; margin-bottom:10px; }
    .layout-card-title .bi { font-size:21px; color:#24436f; }
    .layout-card-title small { font-size:12px; color:#7588a8; font-weight:700; margin-left:8px; }
    .lab-frame { position:relative; border:4px solid #092d75; min-height:326px; border-radius:4px; background:#fff; padding:13px 58px 13px 20px; }
    .door-arc { position:absolute; right:-46px; width:48px; height:42px; border:2px solid #94a3b8; border-left:0; border-radius:0 40px 40px 0; background:#fff; }
    .door-arc.top { top:28px; } .door-arc.bottom { bottom:38px; }
    .pc-grid { display:grid; grid-template-columns:repeat(8,minmax(70px,1fr)); gap:10px 22px; align-items:center; }
    .pc-seat { height:34px; border-radius:5px; border:1px solid #bbf7d0; background:#dcfce7; color:#14532d; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family:'Poppins',sans-serif; cursor:pointer; transition:transform .12s,box-shadow .12s,outline .12s; position:relative; padding-top:2px; }
    .pc-seat:hover { transform:translateY(-1px); box-shadow:0 3px 8px rgba(15,23,42,.12); }
    .pc-seat strong { font-size:11px; font-weight:800; line-height:1; }
    .pc-seat .monitor { font-size:11px; line-height:1; margin-top:3px; color:#475569; }
    .pc-seat.pending { background:#fef3c7; color:#78350f; border-color:#facc15; }
    .pc-seat.reserved { background:#fee2e2; color:#991b1b; border-color:#f87171; }
    .pc-seat.unavailable { background:#e5e7eb; color:#475569; border-color:#cbd5e1; }
    .pc-seat.selected { outline:3px solid #3b82f6; box-shadow:0 0 0 4px rgba(59,130,246,.18); }
    .details-card { padding:18px; min-height:388px; }
    .details-title { display:flex; align-items:center; gap:12px; color:#0f2f72; font-size:17px; font-weight:800; padding-bottom:12px; border-bottom:1px solid #e4ebf5; margin-bottom:4px; }
    .details-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; border-bottom:1px solid #e4ebf5; padding:10px 2px; font-size:13px; color:#435577; font-weight:700; }
    .details-value { color:#203a68; font-weight:800; text-align:left; }
    .badge-status { display:inline-flex; align-items:center; gap:6px; padding:5px 10px; border-radius:6px; font-size:11px; font-weight:800; text-transform:capitalize; }
    .badge-status.available { background:#dcfce7; color:#166534; } .badge-status.pending { background:#fef3c7; color:#a16207; } .badge-status.approved { background:#dcfce7; color:#166534; } .badge-status.reserved { background:#fee2e2; color:#991b1b; } .badge-status.rejected { background:#fee2e2; color:#991b1b; } .badge-status.unavailable { background:#e5e7eb; color:#475569; } .badge-status.done, .badge-status.cancelled { background:#e5e7eb; color:#475569; }
    .detail-actions { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:18px; }
    .detail-actions .wide { grid-column:1/-1; }
    .btn-panel-action { height:40px; border:0; border-radius:7px; color:#fff; font-family:'Poppins',sans-serif; font-weight:800; font-size:14px; display:inline-flex; align-items:center; justify-content:center; gap:9px; cursor:pointer; transition:transform .12s,opacity .12s; }
    .btn-panel-action:hover { transform:translateY(-1px); } .btn-panel-action:disabled { opacity:.45; cursor:not-allowed; transform:none; }
    .btn-approve { background:linear-gradient(180deg,#2bbf5a,#16a34a); } .btn-reject { background:linear-gradient(180deg,#ff3b3b,#dc2626); } .btn-neutral { background:#fff; color:#111827; border:1px solid #cfd8e3; } .btn-done { background:linear-gradient(180deg,#2563eb,#1d4ed8); }
    .history-card { margin-top:10px; }
    .history-top { min-height:48px; padding:0 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e4ebf5; gap:14px; flex-wrap:wrap; }
    .history-title-tabs { display:flex; align-items:center; gap:24px; color:#14346d; font-size:15px; font-weight:800; }
    .section-name { display:inline-flex; align-items:center; gap:7px; white-space:nowrap; }
    .history-tab { height:48px; display:inline-flex; align-items:center; gap:8px; color:#52688e; border:0; border-bottom:3px solid transparent; background:transparent; font-size:13px; font-weight:800; font-family:'Poppins',sans-serif; cursor:pointer; }
    .history-tab.active { color:#0b3c9a; border-bottom-color:#0b3c9a; }
    .entry-control { display:flex; align-items:center; gap:10px; font-size:12px; color:#52688e; font-weight:700; }
    .entry-control select { width:64px; height:34px; border:1px solid #dce4f0; border-radius:8px; padding:0 8px; color:#1f3155; font-family:'Poppins',sans-serif; }
    .table-wrap { overflow-x:auto; }
    .reservation-table { width:100%; border-collapse:collapse; font-size:12px; }
    .reservation-table th, .reservation-table td { padding:11px 20px; border-bottom:1px solid #e9eef7; vertical-align:middle; white-space:nowrap; }
    .reservation-table th { background:#f8fafc; color:#637694; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; }
    .reservation-table td { color:#263d65; font-weight:600; }
    .reservation-table tr:hover td { background:#f8fbff; }
    .row-actions { display:flex; align-items:center; gap:8px; }
    .icon-btn { width:31px; height:26px; border-radius:7px; border:1px solid #dbeafe; background:#eff6ff; color:#2563eb; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; font-size:12px; }
    .icon-btn.success { border-color:#bbf7d0; background:#dcfce7; color:#166534; }
    .icon-btn.reject { border-color:#fecaca; background:#fee2e2; color:#dc2626; }
    .icon-btn.done { border-color:#bfdbfe; background:#dbeafe; color:#1d4ed8; }
    .icon-btn.delete { border-color:#fecaca; background:#fff1f2; color:#ef4444; }
    .history-bottom { display:flex; align-items:center; justify-content:space-between; padding:12px 20px; color:#64748b; font-size:12px; font-weight:600; }
    .pager { display:flex; gap:8px; align-items:center; }
    .pager button { height:30px; border-radius:8px; border:1px solid #e4ebf5; background:#fff; color:#8a99b1; padding:0 12px; font-family:'Poppins',sans-serif; font-size:12px; font-weight:700; }
    .pager .active-page { background:#0b3c9a; color:#fff; border-color:#0b3c9a; min-width:32px; }
    .toast-msg { display:none; position:fixed; right:18px; bottom:18px; background:#111827; color:#fff; padding:12px 15px; border-radius:10px; font-size:13px; z-index:9999; }
    .status-pill { display:inline-flex; align-items:center; gap:6px; padding:5px 10px; border-radius:7px; font-size:11px; font-weight:800; text-transform:capitalize; }
    .status-pill.pending { background:#fef3c7; color:#a16207; } .status-pill.approved { background:#dcfce7; color:#166534; } .status-pill.rejected { background:#fee2e2; color:#dc2626; } .status-pill.done, .status-pill.cancelled { background:#e5e7eb; color:#475569; }
    @media (max-width:1200px) { .rm-controls { grid-template-columns:repeat(3,1fr); } .workspace { grid-template-columns:1fr; } }
    @media (max-width:768px) { body.admin-reservation-page .sidebar { display:none; } body.admin-reservation-page .sidebar.open { display:block; width:100%; position:fixed; top:60px; left:0; bottom:0; z-index:99; overflow-y:auto; } body.admin-reservation-page .admin-main { padding:12px; } .rm-controls { grid-template-columns:1fr; } .pc-grid { grid-template-columns:repeat(4,1fr); } .lab-frame { padding-right:25px; } .door-arc { display:none; } .history-title-tabs { gap:10px; flex-wrap:wrap; } }
  </style>
</head>
<body class="admin-reservation-page">
  <nav class="uc-nav">
    <a class="nav-brand" href="admin_dashboard.php">
      <img src="../assets/images/ccsmainlog_nobg.png" alt="UC CCS Logo" class="nav-logo">
      <div>
        <div class="nav-title">UC Sit-in System</div>
        <div class="nav-sub">Admin Panel</div>
      </div>
    </a>
    <button class="nav-toggler" id="navToggler" aria-label="Toggle menu"><span></span><span></span><span></span></button>
    <div class="nav-links" id="navLinks">
      <span style="font-size:13px;color:#6b7280;padding:0 8px;"><?= $admin_name ?></span>
      <div class="nav-divider"></div>
      <a class="nav-link" href="../controllers/auth/logout.php?type=admin">Log out</a>
    </div>
  </nav>

  <div class="admin-layout">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-section" style="margin-top:0;">Main</div>
      <a class="sidebar-link" href="admin_dashboard.php"><i class="bi bi-grid"></i> Dashboard</a>
      <a class="sidebar-link" href="#"><i class="bi bi-search"></i> Search</a>
      <a class="sidebar-link" href="Admin_StudentList.php"><i class="bi bi-person"></i> Students</a>
      <a class="sidebar-link" href="#"><i class="bi bi-calendar2-week"></i> Sit-in</a>
      <a class="sidebar-link" href="Admin_SitinRecords.php"><i class="bi bi-file-earmark-text"></i> Sit-in Records</a>
      <hr class="sidebar-divider">
      <div class="sidebar-section">Reports</div>
      <a class="sidebar-link" href="#"><i class="bi bi-bar-chart"></i> Sit-in Reports</a>
      <a class="sidebar-link" href="#"><i class="bi bi-chat-square"></i> Feedback Reports</a>
      <a class="sidebar-link active" href="Admin_Reservation.php"><i class="bi bi-calendar2-check"></i> Reservation</a>
    </aside>

    <main class="admin-main">
      <div class="reservation-shell">
        <section class="reservation-management">
          <div class="rm-header">
            <div class="rm-title"><i class="bi bi-calendar2-check"></i> Reservation Management</div>
            <div class="summary-mini">
              <span class="mini-pill"><?= $pending_count ?> Pending</span>
              <span class="mini-pill"><?= $current_approved_count ?> Current Approved</span>
              <span class="mini-pill"><?= $history_count ?> History</span>
            </div>
          </div>

          <div class="rm-controls">
            <div class="filter-field">
              <label for="viewLab">Select Lab</label>
              <select class="filter-control" id="viewLab" onchange="loadSeats()">
                <?php foreach ($labs as $lab): ?>
                  <option value="<?= htmlspecialchars($lab) ?>" <?= $lab === $default_lab ? 'selected' : '' ?>><?= htmlspecialchars($lab) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filter-field">
              <label for="viewDate">Select Date</label>
              <input class="filter-control" type="date" id="viewDate" value="<?= htmlspecialchars($default_date) ?>" onchange="loadSeats()">
            </div>
            <div class="filter-field">
              <label for="viewTime">Start Time</label>
              <select class="filter-control" id="viewTime" onchange="syncDefaultEnd(); loadSeats();">
                <?php foreach ($time_slots as $value => $label): ?>
                  <option value="<?= htmlspecialchars($value) ?>" <?= $value === $default_time ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filter-field">
              <label for="viewEndTime">End Time</label>
              <select class="filter-control" id="viewEndTime" onchange="loadSeats()">
                <?php foreach ($time_slots as $value => $label): ?>
                  <option value="<?= htmlspecialchars($value) ?>" <?= $value === $default_end_time ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filter-field">
              <label for="statusFilter">Status</label>
              <select class="filter-control" id="statusFilter" onchange="renderTable()">
                <option value="all">All</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
                <option value="done">Done</option>
              </select>
            </div>
            <div class="filter-field search-field">
              <label for="searchInput">Search</label>
              <i class="bi bi-search"></i>
              <input class="filter-control" type="text" id="searchInput" placeholder="Search student, ID, lab, purpose..." oninput="renderTable()">
            </div>
          </div>

          <div class="legend-row">
            <span class="legend-item"><span class="dot available"></span> Available</span>
            <span class="legend-item"><span class="dot pending"></span> Pending</span>
            <span class="legend-item"><span class="dot reserved"></span> Reserved</span>
            <span class="legend-item"><span class="dot unavailable"></span> Unavailable</span>
          </div>

          <div class="workspace">
            <div class="layout-card">
              <div class="layout-card-title">
                <i class="bi bi-display"></i>
                <span id="labTitle"><?= htmlspecialchars($default_lab) ?> Layout</span>
                <small>7 Rows × 8 PCs = 56 PCs</small>
              </div>
              <div class="lab-frame">
                <span class="door-arc top"></span>
                <span class="door-arc bottom"></span>
                <div class="pc-grid" id="pcGrid"></div>
              </div>
            </div>

            <div class="details-card">
              <div class="details-title"><i class="bi bi-info-circle"></i> Selected PC Details</div>
              <div class="details-row"><span>Selected PC</span><span class="details-value" id="detailPc">PC <?= str_pad((string)$default_pc, 2, '0', STR_PAD_LEFT) ?></span></div>
              <div class="details-row"><span>Student ID</span><span class="details-value" id="detailStudentId">—</span></div>
              <div class="details-row"><span>Student Name</span><span class="details-value" id="detailName">—</span></div>
              <div class="details-row"><span>Purpose</span><span class="details-value" id="detailPurpose">—</span></div>
              <div class="details-row"><span>Date & Time</span><span class="details-value" id="detailDateTime">—</span></div>
              <div class="details-row"><span>Status</span><span class="details-value" id="detailStatus"><span class="badge-status available"><span class="dot available"></span>available</span></span></div>
              <div class="detail-actions">
                <button type="button" class="btn-panel-action btn-approve" id="btnApprove" onclick="reservationAction('approve')"><i class="bi bi-check-lg"></i> Approve</button>
                <button type="button" class="btn-panel-action btn-reject" id="btnReject" onclick="reservationAction('reject')"><i class="bi bi-x-circle"></i> Reject</button>
                <button type="button" class="btn-panel-action btn-done wide" id="btnDone" onclick="reservationAction('done')"><i class="bi bi-check2-square"></i> Mark Done</button>
                <button type="button" class="btn-panel-action btn-neutral wide" id="btnPcStatus" onclick="togglePcAvailability()"><i class="bi bi-ban"></i> Mark Unavailable</button>
              </div>
            </div>
          </div>
        </section>

        <section class="history-card">
          <div class="history-top">
            <div class="history-title-tabs">
              <span class="section-name"><i class="bi bi-card-list"></i> Reservation List</span>
              <button type="button" class="history-tab active" id="tabCurrent" onclick="setTab('current')"><i class="bi bi-clock"></i> Current Requests</button>
              <button type="button" class="history-tab" id="tabHistory" onclick="setTab('history')"><i class="bi bi-arrow-clockwise"></i> Reservation History</button>
            </div>
            <div class="entry-control">
              <span>Show</span>
              <select id="entryLimit" onchange="renderTable()">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="all">All</option>
              </select>
              <span>entries</span>
            </div>
          </div>

          <div class="table-wrap">
            <table class="reservation-table" id="reservationTable">
              <thead>
                <tr>
                  <th>Reservation ID</th>
                  <th>Student ID</th>
                  <th>Name</th>
                  <th>Lab</th>
                  <th>PC</th>
                  <th>Purpose</th>
                  <th>Date & Time</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="reservationTbody"></tbody>
            </table>
          </div>

          <div class="history-bottom">
            <span id="entriesInfo">Showing 0 entries</span>
            <div class="pager">
              <button disabled>← Prev</button>
              <button class="active-page">1</button>
              <button disabled>Next →</button>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>

  <div class="toast-msg" id="toastMsg"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const reservations = <?= $reservation_json ?: '[]' ?>;
    let activeTab = 'current';
    let selectedSeat = null;
    let pendingSelectPc = <?= (int)$default_pc ?>;

    const navToggler = document.getElementById('navToggler');
    if (navToggler) {
      navToggler.addEventListener('click', () => {
        document.getElementById('navLinks').classList.toggle('open');
        document.getElementById('sidebar').classList.toggle('open');
      });
    }

    function showToast(message) {
      const toast = document.getElementById('toastMsg');
      toast.textContent = message;
      toast.style.display = 'block';
      setTimeout(() => toast.style.display = 'none', 2500);
    }

    function pcLabel(pc) {
      return 'PC ' + String(pc).padStart(2, '0');
    }

    function timeToLabel(time) {
      if (!time) return '—';
      const [h, m] = time.substring(0, 5).split(':').map(Number);
      const suffix = h >= 12 ? 'PM' : 'AM';
      let hour = h % 12;
      if (hour === 0) hour = 12;
      return String(hour).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ' ' + suffix;
    }

    function dateToLabel(dateValue) {
      if (!dateValue) return '—';
      const date = new Date(dateValue + 'T00:00:00');
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function endDate(row) {
      const endTime = String(row.reservation_end_time || row.reservation_time || '00:00:00').substring(0, 8);
      return new Date(row.reservation_date + 'T' + endTime);
    }

    function isCurrent(row) {
      return row.status === 'pending' || (row.status === 'approved' && endDate(row) >= new Date());
    }

    function isHistory(row) {
      return (row.status === 'approved' && endDate(row) < new Date()) || ['rejected', 'cancelled', 'done'].includes(row.status);
    }

    function addOneHour(value) {
      if (!value) return '';
      const [h, m] = value.split(':').map(Number);
      const d = new Date(2000, 0, 1, h, m);
      d.setHours(d.getHours() + 1);
      return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    function syncDefaultEnd() {
      document.getElementById('viewEndTime').value = addOneHour(document.getElementById('viewTime').value);
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

    function setTab(tab) {
      activeTab = tab;
      document.getElementById('tabCurrent').classList.toggle('active', tab === 'current');
      document.getElementById('tabHistory').classList.toggle('active', tab === 'history');
      renderTable();
    }

    function loadSeats() {
      const lab = document.getElementById('viewLab').value;
      const date = document.getElementById('viewDate').value;
      const time = document.getElementById('viewTime').value;
      const endTime = document.getElementById('viewEndTime').value;
      const grid = document.getElementById('pcGrid');

      document.getElementById('labTitle').textContent = lab + ' Layout';
      if (endTime <= time) {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#b91c1c;padding:32px;">End time must be later than start time.</div>';
        return;
      }

      grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#94a3b8;padding:32px;">Loading seat layout...</div>';

      fetch(`../controllers/reservation/get_seats.php?lab=${encodeURIComponent(lab)}&date=${encodeURIComponent(date)}&time=${encodeURIComponent(time)}&end_time=${encodeURIComponent(endTime)}`)
        .then(res => res.json())
        .then(data => {
          if (!data.success) {
            grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;color:#b91c1c;padding:32px;">${data.message || 'Failed to load seats.'}</div>`;
            return;
          }

          const byPc = {};
          data.seats.forEach(seat => byPc[Number(seat.pc_number)] = seat);
          grid.innerHTML = '';

          let seatToSelect = null;
          seatOrder().forEach(pc => {
            const seat = byPc[pc] || { pc_number: pc, status: 'available', layout_status: 'available' };
            const visualStatus = seat.layout_status || seat.status;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'pc-seat ' + visualStatus;
            button.dataset.pc = pc;
            button.innerHTML = `<strong>${pcLabel(pc)}</strong><span class="monitor"><i class="bi bi-pc-display-horizontal"></i></span>`;
            button.title = seat.fullname ? `${seat.fullname} - ${seat.reservation_status}` : visualStatus;
            button.addEventListener('click', () => selectSeat(seat));
            grid.appendChild(button);

            if (pendingSelectPc && pc === Number(pendingSelectPc)) seatToSelect = seat;
            if (!seatToSelect && (visualStatus === 'pending' || visualStatus === 'reserved')) seatToSelect = seat;
          });

          selectSeat(seatToSelect || byPc[1] || { pc_number: 1, status: 'available' });
          pendingSelectPc = null;
        })
        .catch(() => {
          grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#b91c1c;padding:32px;">Something went wrong.</div>';
        });
    }

    function selectSeat(seat) {
      selectedSeat = seat;
      document.querySelectorAll('.pc-seat').forEach(el => {
        el.classList.toggle('selected', Number(el.dataset.pc) === Number(seat.pc_number));
      });
      updateDetails(seat);
    }

    function updateDetails(seat) {
      const visualStatus = seat.layout_status || seat.status || 'available';
      const status = seat.reservation_status || visualStatus || 'available';
      const shownStatus = status === 'reserved' ? 'approved' : status;
      const hasReservation = !!seat.reservation_id;
      const isPending = status === 'pending';
      const isApproved = status === 'approved' || visualStatus === 'reserved';
      const isUnavailable = visualStatus === 'unavailable';

      document.getElementById('detailPc').textContent = pcLabel(seat.pc_number);
      document.getElementById('detailStudentId').textContent = seat.studentid || '—';
      document.getElementById('detailName').textContent = seat.fullname || '—';
      document.getElementById('detailPurpose').textContent = seat.purpose || '—';
      document.getElementById('detailDateTime').textContent = seat.reservation_date ? `${dateToLabel(seat.reservation_date)}, ${timeToLabel(seat.reservation_time)} - ${timeToLabel(seat.reservation_end_time)}` : '—';
      document.getElementById('detailStatus').innerHTML = `<span class="badge-status ${shownStatus}"><span class="dot ${visualStatus}"></span>${shownStatus}</span>`;

      document.getElementById('btnApprove').disabled = !isPending;
      document.getElementById('btnReject').disabled = !hasReservation || (!isPending && !isApproved);
      document.getElementById('btnDone').disabled = !isApproved;
      document.getElementById('btnDone').style.display = isApproved ? 'inline-flex' : 'none';

      const pcStatusBtn = document.getElementById('btnPcStatus');
      pcStatusBtn.disabled = false;
      pcStatusBtn.innerHTML = isUnavailable ? '<i class="bi bi-check2-circle"></i> Mark Available' : '<i class="bi bi-ban"></i> Mark Unavailable';
    }

    function reservationAction(action) {
      if (!selectedSeat || !selectedSeat.reservation_id) {
        showToast('No reservation selected.');
        return;
      }
      if (action === 'reject' && selectedSeat.reservation_status === 'approved') action = 'cancel';
      const formData = new FormData();
      formData.append('reservation_id', selectedSeat.reservation_id);
      formData.append('action', action);
      postReservationAction(formData);
    }

    function tableAction(id, action) {
      if (action === 'delete') return deleteReservation(id);
      const formData = new FormData();
      formData.append('reservation_id', id);
      formData.append('action', action);
      postReservationAction(formData);
    }

    function deleteReservation(id) {
      if (!confirm('Delete this reservation?')) return;
      const formData = new FormData();
      formData.append('reservation_id', id);
      formData.append('action', 'delete');
      postReservationAction(formData);
    }

    function togglePcAvailability() {
      if (!selectedSeat) return;
      const visualStatus = selectedSeat.layout_status || selectedSeat.status;
      const formData = new FormData();
      formData.append('action', visualStatus === 'unavailable' ? 'mark_available' : 'mark_unavailable');
      formData.append('lab', document.getElementById('viewLab').value);
      formData.append('pc_number', selectedSeat.pc_number);
      postReservationAction(formData, true);
    }

    function postReservationAction(formData, noReload = false) {
      fetch('../controllers/reservation/admin_reservation_controller.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          if (!data.success) {
            showToast(data.message || 'Action failed.');
            return;
          }
          showToast(data.message || 'Reservation updated.');
          if (noReload) loadSeats(); else setTimeout(() => window.location.reload(), 650);
        })
        .catch(() => showToast('Something went wrong.'));
    }

    function viewReservation(id) {
      const row = reservations.find(r => Number(r.id) === Number(id));
      if (!row) return;
      document.getElementById('viewLab').value = row.lab;
      document.getElementById('viewDate').value = row.reservation_date;
      const timeValue = String(row.reservation_time).substring(0, 5);
      const endValue = String(row.reservation_end_time || row.reservation_time).substring(0, 5);
      ensureTimeOption('viewTime', timeValue);
      ensureTimeOption('viewEndTime', endValue);
      document.getElementById('viewTime').value = timeValue;
      document.getElementById('viewEndTime').value = endValue;
      pendingSelectPc = Number(row.pc_number);
      loadSeats();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function ensureTimeOption(selectId, value) {
      const select = document.getElementById(selectId);
      if (![...select.options].some(o => o.value === value)) {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = timeToLabel(value);
        select.prepend(option);
      }
    }

    function statusBadge(status) {
      return `<span class="status-pill ${status}"><span class="dot ${status === 'approved' ? 'available' : status === 'pending' ? 'pending' : status === 'rejected' ? 'reserved' : 'unavailable'}"></span>${status}</span>`;
    }

    function rowActions(row) {
      const id = Number(row.id);
      if (activeTab === 'history') {
        return `<div class="row-actions"><button type="button" class="icon-btn delete" title="Delete" onclick="deleteReservation(${id})"><i class="bi bi-trash"></i></button></div>`;
      }

      let buttons = `<button type="button" class="icon-btn" title="View" onclick="viewReservation(${id})"><i class="bi bi-eye"></i></button>`;
      if (row.status === 'pending') {
        buttons += `<button type="button" class="icon-btn success" title="Approve" onclick="tableAction(${id}, 'approve')"><i class="bi bi-check-lg"></i></button>`;
        buttons += `<button type="button" class="icon-btn reject" title="Reject" onclick="tableAction(${id}, 'reject')"><i class="bi bi-x-lg"></i></button>`;
      } else if (row.status === 'approved') {
        buttons += `<button type="button" class="icon-btn done" title="Mark Done" onclick="tableAction(${id}, 'done')"><i class="bi bi-check2-square"></i></button>`;
      }
      buttons += `<button type="button" class="icon-btn delete" title="Delete" onclick="deleteReservation(${id})"><i class="bi bi-trash"></i></button>`;
      return `<div class="row-actions">${buttons}</div>`;
    }

    function renderTable() {
      const q = document.getElementById('searchInput').value.toLowerCase().trim();
      const status = document.getElementById('statusFilter').value;
      const limitValue = document.getElementById('entryLimit').value;
      const limit = limitValue === 'all' ? Infinity : Number(limitValue);
      const tbody = document.getElementById('reservationTbody');

      let rows = reservations.filter(row => activeTab === 'current' ? isCurrent(row) : isHistory(row));
      rows = rows.filter(row => status === 'all' || row.status === status);
      rows = rows.filter(row => {
        const text = `${row.id} ${row.studentid} ${row.fullname} ${row.lab} ${row.pc_number} ${row.purpose} ${row.status}`.toLowerCase();
        return text.includes(q);
      });

      const total = rows.length;
      rows = rows.slice(0, limit);

      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:26px;">No reservations found.</td></tr>';
      } else {
        tbody.innerHTML = rows.map(row => {
          const rid = 'R-' + new Date(row.reservation_date + 'T00:00:00').getFullYear() + '-' + String(row.id).padStart(5, '0');
          return `
            <tr data-status="${row.status}">
              <td><strong>${rid}</strong></td>
              <td>${row.studentid || '—'}</td>
              <td>${row.fullname || '—'}</td>
              <td>${row.lab || '—'}</td>
              <td>${pcLabel(row.pc_number)}</td>
              <td>${row.purpose || '—'}</td>
              <td>${dateToLabel(row.reservation_date)}, ${timeToLabel(row.reservation_time)} - ${timeToLabel(row.reservation_end_time)}</td>
              <td>${statusBadge(row.status)}</td>
              <td>${rowActions(row)}</td>
            </tr>
          `;
        }).join('');
      }

      document.getElementById('entriesInfo').textContent = `Showing ${rows.length} of ${total} entries`;
    }

    loadSeats();
    renderTable();
  </script>
</body>
</html>
