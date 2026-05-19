<?php
// admin_module/Admin_Reservation.php

session_start();
require_once '../config/db_config.php';
require_once '../controllers/reservation/reservation_helpers.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ../login_page.php');
    exit;
}

/*
  Auto-cancel approved reservations when the student is already
  more than 15 minutes late from the reservation start time.
*/
autoCancelLateReservations($conn);

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
$total_count = count($reservations);
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;
$history_count = 0;
$expired_pending_count = 0;

foreach ($reservations as $r) {
    $end_ts = strtotime($r['reservation_date'] . ' ' . $r['reservation_end_time']);
    $is_expired = $end_ts < $now;

    if ($r['status'] === 'pending' && !$is_expired) {
        $pending_count++;
    }

    if ($r['status'] === 'pending' && $is_expired) {
        $expired_pending_count++;
    }

    if ($r['status'] === 'approved' && !$is_expired) {
        $approved_count++;
    }

    if (in_array($r['status'], ['rejected', 'cancelled'], true)) {
        $rejected_count++;
    }

    if (($r['status'] === 'pending' && $is_expired) || ($r['status'] === 'approved' && $is_expired) || in_array($r['status'], ['rejected', 'cancelled', 'done'], true)) {
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
    .reservation-summary-cards {
      display: grid;
      grid-template-columns: repeat(4, minmax(150px, 1fr));
      gap: 14px;
      padding: 16px 18px;
      background: #f8fafc;
      border-bottom: 1px solid #e5e7eb;
    }

    .summary-card {
      border: 1px solid #e5e7eb;
      background: #fff;
      border-radius: 14px;
      padding: 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      cursor: default;
      transition: all 0.16s ease;
      text-align: left;
      font-family: 'Poppins', sans-serif;
    }

    .summary-card:hover {
      transform: none;
      border-color: #e5e7eb;
      box-shadow: none;
    }

    .summary-card .summary-label {
      font-size: 12px;
      font-weight: 800;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: .4px;
      margin-bottom: 5px;
    }

    .summary-card .summary-value {
      font-size: 28px;
      font-weight: 900;
      color: #0f2d63;
      line-height: 1;
    }

    .summary-card .summary-icon {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      flex-shrink: 0;
    }

    .summary-card.total .summary-icon { background: #eff6ff; color: #1d4ed8; }
    .summary-card.approved .summary-icon { background: #dcfce7; color: #15803d; }
    .summary-card.rejected .summary-icon { background: #fee2e2; color: #dc2626; }
    .summary-card.pending .summary-icon { background: #fff7ed; color: #d97706; }

    .badge-status.expired {
      background: #f1f5f9;
      color: #64748b;
      border: 1px solid #cbd5e1;
    }

    .dot.expired {
      background: #94a3b8;
    }

    .badge-status.approved_not_registered,
    .status-pill.approved_not_registered {
      background: #fff7ed;
      color: #c2410c;
      border: 1px solid #fed7aa;
    }

    .badge-status.approved_registered,
    .status-pill.approved_registered {
      background: #dcfce7;
      color: #15803d;
      border: 1px solid #86efac;
    }

    .dot.approved_not_registered {
      background: #f97316;
    }

    .dot.approved_registered {
      background: #22c55e;
    }


    .confirm-toast {
      display: none;
      position: fixed;
      top: 18px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 10050;
      width: min(460px, calc(100% - 28px));
      font-family: 'Poppins', sans-serif;
    }

    .confirm-toast.show {
      display: block;
      animation: confirmSlide .18s ease;
    }

    @keyframes confirmSlide {
      from { opacity: 0; transform: translate(-50%, -10px); }
      to { opacity: 1; transform: translate(-50%, 0); }
    }

    .confirm-toast-box {
      background: #fff;
      border: 1px solid #dbe3ef;
      border-radius: 16px;
      box-shadow: 0 18px 50px rgba(15, 23, 42, .20);
      padding: 16px;
    }

    .confirm-toast-title {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      font-weight: 800;
      color: #0f2d63;
      margin-bottom: 6px;
    }

    .confirm-toast-message {
      font-size: 13px;
      color: #475569;
      line-height: 1.55;
      margin-bottom: 14px;
    }

    .confirm-toast-actions {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }

    .confirm-toast-actions button {
      border: none;
      border-radius: 9px;
      padding: 8px 16px;
      font-size: 12px;
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
    }

    .confirm-cancel-btn {
      background: #f1f5f9;
      color: #334155;
    }

    .confirm-ok-btn {
      background: #1d4ed8;
      color: #fff;
    }

    @media (max-width: 980px) {
      .reservation-summary-cards {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 520px) {
      .reservation-summary-cards {
        grid-template-columns: 1fr;
      }
    }
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
    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
      <div class="reservation-shell">
        <section class="reservation-management">
          <div class="rm-header">
            <div class="rm-title"><i class="bi bi-calendar2-check"></i> Reservation Management</div>
            <div class="summary-mini">
              <span class="mini-pill"><?= $pending_count ?> Pending</span>
              <span class="mini-pill"><?= $approved_count ?> Approved</span>
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
                <option value="pending">Pending Request</option>
                <option value="approved">Post-Approved Request</option>
                <option value="approved_not_registered">Approved Not Registered Sit-In</option>
                <option value="approved_registered">Approved Registered Sit-In</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
                <option value="expired">Expired Request</option>
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
                <button type="button" class="btn-panel-action btn-done wide" id="btnRegisterSitin" onclick="openSitinModalFromReservation()"><i class="bi bi-pc-display-horizontal"></i> Register Sit-in</button>
                <button type="button" class="btn-panel-action btn-neutral wide" id="btnPcStatus" onclick="togglePcAvailability()"><i class="bi bi-ban"></i> Mark Unavailable</button>
                <button type="button" class="btn-panel-action btn-reject wide" id="btnMarkAllUnavailable" onclick="markAllUnavailable()"><i class="bi bi-ban-fill"></i> Mark All Unavailable</button>
              </div>
            </div>
          </div>
        </section>

        <section class="history-card">
          <div class="reservation-summary-cards">
            <div class="summary-card total" id="summaryTotal">
              <div>
                <div class="summary-label">Total Reservations</div>
                <div class="summary-value"><?= $total_count ?></div>
              </div>
              <div class="summary-icon"><i class="bi bi-calendar-check"></i></div>
            </div>

            <div class="summary-card approved" id="summaryApproved">
              <div>
                <div class="summary-label">Post-Approved Request</div>
                <div class="summary-value"><?= $approved_count ?></div>
              </div>
              <div class="summary-icon"><i class="bi bi-check-circle"></i></div>
            </div>

            <div class="summary-card rejected" id="summaryRejected">
              <div>
                <div class="summary-label">Rejected Request</div>
                <div class="summary-value"><?= $rejected_count ?></div>
              </div>
              <div class="summary-icon"><i class="bi bi-x-circle"></i></div>
            </div>

            <div class="summary-card pending" id="summaryPending">
              <div>
                <div class="summary-label">Pending Request</div>
                <div class="summary-value"><?= $pending_count ?></div>
              </div>
              <div class="summary-icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
          </div>

          <div class="history-top">
            <div class="history-title-tabs">
              <span class="section-name"><i class="bi bi-card-list"></i> Reservation List</span>
              <button type="button" class="history-tab active" id="tabPending" onclick="setTab('pending')"><i class="bi bi-hourglass-split"></i> Pending Request</button>
              <button type="button" class="history-tab" id="tabPostApproved" onclick="setTab('postApproved')"><i class="bi bi-check2-circle"></i> Post-Approved Request</button>
              <button type="button" class="history-tab" id="tabHistory" onclick="setTab('history')"><i class="bi bi-arrow-clockwise"></i> Request History</button>
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



  <!-- SEARCH STUDENT MODAL -->
  <div id="searchModal" style="display:none; position:fixed; inset:0; z-index:9998; background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:100%; max-width:520px; margin:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.2); font-family:'Poppins',sans-serif; overflow:hidden;">
      <div style="background:#1d3a6e; color:#fff; padding:16px 24px; display:flex; align-items:center; justify-content:space-between;">
        <span style="font-size:14px; font-weight:600;">Search Student</span>
        <button type="button" onclick="closeSearchModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">✕</button>
      </div>

      <div style="padding:20px 24px; border-bottom:1px solid #f3f4f6;">
        <div style="display:flex; gap:10px;">
          <input
            type="text"
            id="adminSearchInput"
            placeholder="Enter Student ID (e.g. 2024-00001)"
            style="flex:1; border:1px solid #e5e7eb; border-radius:8px; padding:10px 14px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; color:#111827;"
            onkeydown="if(event.key==='Enter') searchStudentFromModal()"
          >

          <button type="button" onclick="searchStudentFromModal()" style="padding:10px 20px; background:#1d3a6e; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer; white-space:nowrap;">
            Search
          </button>
        </div>

        <div id="adminSearchError" style="display:none; margin-top:8px; font-size:12px; color:#b91c1c;"></div>
      </div>

      <div id="adminSearchResult" style="display:none; padding:20px 24px;">
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid #f3f4f6;">
          <div id="adminResultAvatar" style="width:56px; height:56px; border-radius:50%; background:#1d3a6e; color:#fff; font-size:20px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:3px solid #e5e7eb;"></div>

          <div>
            <div id="adminResultName" style="font-size:15px; font-weight:700; color:#111827;"></div>
            <div id="adminResultId" style="font-size:12px; color:#6b7280; margin-top:2px;"></div>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">COURSE</div>
            <div id="adminResultCourse" style="font-size:13px; font-weight:600; color:#111827;"></div>
          </div>

          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">YEAR LEVEL</div>
            <div id="adminResultYear" style="font-size:13px; font-weight:600; color:#111827;"></div>
          </div>

          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">EMAIL</div>
            <div id="adminResultEmail" style="font-size:13px; font-weight:600; color:#111827; word-break:break-all;"></div>
          </div>

          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">ADDRESS</div>
            <div id="adminResultAddr" style="font-size:13px; font-weight:600; color:#111827;"></div>
          </div>

          <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px; grid-column: span 2;">
            <div style="font-size:11px; font-weight:600; color:#1d4ed8; margin-bottom:3px;">SESSION CREDITS REMAINING</div>
            <div style="display:flex; align-items:center; gap:8px;">
              <div id="adminResultCredits" style="font-size:22px; font-weight:800; color:#1d3a6e;"></div>
              <div style="font-size:12px; color:#6b7280;">/ 30 credits this semester</div>
            </div>
          </div>
        </div>
      </div>

      <div id="adminSearchLoading" style="display:none; padding:32px; text-align:center; font-size:13px; color:#6b7280;">
        Searching...
      </div>
    </div>
  </div>

  <!-- REGISTER SIT-IN MODAL -->
  <div id="sitinModal" style="display:none; position:fixed; inset:0; z-index:9998; background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:100%; max-width:520px; margin:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.2); font-family:'Poppins',sans-serif; overflow:hidden;">
      <div style="background:#1d3a6e; color:#fff; padding:16px 24px; display:flex; align-items:center; justify-content:space-between;">
        <span style="font-size:14px; font-weight:600;">Register Sit-in</span>
        <button type="button" onclick="closeSitinModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">✕</button>
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
              onkeydown="if(event.key==='Enter') lookupStudent()"
            >

            <button type="button" onclick="lookupStudent()" style="padding:9px 16px;background:#1d3a6e;color:#fff;border:none; border-radius:8px;font-size:13px;font-weight:600; font-family:'Poppins',sans-serif;cursor:pointer;white-space:nowrap;">
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
              <option value="Lab 524">Lab 524</option>
              <option value="Lab 526">Lab 526</option>
              <option value="Lab 528">Lab 528</option>
              <option value="Lab 530">Lab 530</option>
              <option value="Lab 542">Lab 542</option>
              <option value="Lab 544">Lab 544</option>
            </select>
          </div>

          <div id="sitinSubmitError" style="display:none;margin-bottom:10px;font-size:12px;color:#b91c1c;"></div>

          <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" onclick="closeSitinModal()" style="padding:9px 20px;border:1px solid #d1d5db;border-radius:8px; background:#fff;font-size:13px;font-weight:500; font-family:'Poppins',sans-serif;cursor:pointer;color:#374151;">
              Cancel
            </button>

            <button type="button" onclick="submitSitin()" style="padding:9px 24px;background:#059669;color:#fff;border:none; border-radius:8px;font-size:13px;font-weight:600; font-family:'Poppins',sans-serif;cursor:pointer;">
              Confirm Sit-in
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>


  <div class="toast-msg" id="toastMsg"></div>

  <div class="confirm-toast" id="confirmToast">
    <div class="confirm-toast-box">
      <div class="confirm-toast-title"><i class="bi bi-question-circle"></i> Confirm Action</div>
      <div class="confirm-toast-message" id="confirmToastMessage">Are you sure?</div>
      <div class="confirm-toast-actions">
        <button type="button" class="confirm-cancel-btn" id="confirmToastCancel">Cancel</button>
        <button type="button" class="confirm-ok-btn" id="confirmToastOk">Continue</button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const reservations = <?= $reservation_json ?: '[]' ?>;
    let activeTab = 'pending';
    let selectedSeat = null;
    let pendingSelectPc = <?= (int)$default_pc ?>;
    let pendingReservationForDetails = null;

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

    function askConfirm(message, onConfirm) {
      const box = document.getElementById('confirmToast');
      const msg = document.getElementById('confirmToastMessage');
      const okBtn = document.getElementById('confirmToastOk');
      const cancelBtn = document.getElementById('confirmToastCancel');

      msg.textContent = message;
      box.classList.add('show');

      okBtn.onclick = function () {
        box.classList.remove('show');
        if (typeof onConfirm === 'function') onConfirm();
      };

      cancelBtn.onclick = function () {
        box.classList.remove('show');
      };
    }



    // Admin sidebar modals: Search Student + Register Sit-in
    let currentStudent = null;
    let selectedReservationIdForSitin = null;

    function openSearchModal() {
      const modal = document.getElementById('searchModal');
      if (!modal) return;
      modal.style.display = 'flex';
      resetSearchModal();
      setTimeout(() => document.getElementById('adminSearchInput')?.focus(), 100);
    }

    function closeSearchModal() {
      const modal = document.getElementById('searchModal');
      if (!modal) return;
      modal.style.display = 'none';
      resetSearchModal();
    }

    function resetSearchModal() {
      const input = document.getElementById('adminSearchInput');
      if (input) input.value = '';
      document.getElementById('adminSearchResult').style.display = 'none';
      document.getElementById('adminSearchError').style.display = 'none';
      document.getElementById('adminSearchLoading').style.display = 'none';
    }

    function showAdminSearchError(message) {
      const error = document.getElementById('adminSearchError');
      error.textContent = message;
      error.style.display = 'block';
    }

    function searchStudentFromModal() {
      const id = document.getElementById('adminSearchInput').value.trim();

      if (!id) {
        showAdminSearchError('Please enter a Student ID.');
        return;
      }

      document.getElementById('adminSearchResult').style.display = 'none';
      document.getElementById('adminSearchError').style.display = 'none';
      document.getElementById('adminSearchLoading').style.display = 'block';

      fetch(`../controllers/student/search_student.php?studentid=${encodeURIComponent(id)}`)
        .then(res => res.json())
        .then(data => {
          document.getElementById('adminSearchLoading').style.display = 'none';

          if (!data.found) {
            showAdminSearchError(data.message || 'No student found with that ID.');
            return;
          }

          const s = data.student;
          const initials = ((s.firstname || '').charAt(0) + (s.lastname || '').charAt(0)).toUpperCase();
          const yearLabels = { 1: '1st Year', 2: '2nd Year', 3: '3rd Year', 4: '4th Year' };

          document.getElementById('adminResultAvatar').textContent = initials || 'ST';
          document.getElementById('adminResultName').textContent = `${s.lastname || ''}, ${s.firstname || ''} ${s.middlename || ''}`.trim();
          document.getElementById('adminResultId').textContent = 'ID: ' + (s.studentid || '—');
          document.getElementById('adminResultCourse').textContent = s.course || '—';
          document.getElementById('adminResultYear').textContent = yearLabels[s.yearlvl] || s.yearlvl || '—';
          document.getElementById('adminResultEmail').textContent = s.email || '—';
          document.getElementById('adminResultAddr').textContent = s.addrs || '—';
          document.getElementById('adminResultCredits').textContent = s.session_credits ?? '0';

          document.getElementById('adminSearchResult').style.display = 'block';
        })
        .catch(() => {
          document.getElementById('adminSearchLoading').style.display = 'none';
          showAdminSearchError('Something went wrong. Please try again.');
        });
    }

    function openSitinModal() {
      const modal = document.getElementById('sitinModal');
      if (!modal) return;
      modal.style.display = 'flex';
      resetSitinModal();
      setTimeout(() => document.getElementById('sitinIdInput')?.focus(), 100);
    }

    function closeSitinModal() {
      const modal = document.getElementById('sitinModal');
      if (!modal) return;
      modal.style.display = 'none';
      resetSitinModal();
    }

    function resetSitinModal() {
      currentStudent = null;
      selectedReservationIdForSitin = null;
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
      document.getElementById('sitinSubmitError').style.display = 'none';
    }

    function showSitinError(elementId, message) {
      const error = document.getElementById(elementId);
      error.textContent = message;
      error.style.display = 'block';
    }

    function lookupStudent() {
      const id = document.getElementById('sitinIdInput').value.trim();

      if (!id) {
        showSitinError('sitinLookupError', 'Please enter a Student ID.');
        return;
      }

      document.getElementById('sitinLookupError').style.display = 'none';
      document.getElementById('sitinSubmitError').style.display = 'none';

      fetch(`../controllers/student/search_student.php?studentid=${encodeURIComponent(id)}`)
        .then(res => res.json())
        .then(data => {
          if (!data.found) {
            showSitinError('sitinLookupError', data.message || 'No student found with that ID.');
            return;
          }

          const s = data.student;
          currentStudent = s;

          if (Number(s.session_credits) <= 0) {
            showSitinError('sitinLookupError', 'This student has no remaining session credits.');
            return;
          }

          const initials = ((s.firstname || '').charAt(0) + (s.lastname || '').charAt(0)).toUpperCase();
          document.getElementById('sitinAvatar').textContent = initials || 'ST';
          document.getElementById('sitinStudentName').textContent = `${s.lastname || ''}, ${s.firstname || ''} ${s.middlename || ''}`.trim();
          document.getElementById('sitinStudentCourse').textContent = `${s.course || 'No course'} • Year ${s.yearlvl || '—'}`;
          document.getElementById('sitinSessionBadge').textContent = s.session_credits;
          document.getElementById('sitinStudentInfo').style.display = 'block';
          document.getElementById('sitinFormFields').style.display = 'block';
        })
        .catch(() => showSitinError('sitinLookupError', 'Something went wrong. Please try again.'));
    }

    function submitSitin() {
      if (!currentStudent) {
        showSitinError('sitinSubmitError', 'Please look up a student first.');
        return;
      }

      const purpose = document.getElementById('sitinPurpose').value.trim();
      const lab = document.getElementById('sitinLab').value.trim();

      if (!purpose || !lab) {
        showSitinError('sitinSubmitError', 'Purpose and lab are required.');
        return;
      }

      const message = selectedReservationIdForSitin
        ? 'Register this approved reservation as a sit-in session?'
        : 'Register this student for sit-in?';

      askConfirm(message, function () {
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

            if (selectedReservationIdForSitin) {
              const doneData = new FormData();
              doneData.append('reservation_id', selectedReservationIdForSitin);
              doneData.append('action', 'done');

              fetch('../controllers/reservation/admin_reservation_controller.php', {
                method: 'POST',
                body: doneData
              }).finally(() => {
                closeSitinModal();
                showToast('Sit-in registered successfully.');
                setTimeout(() => window.location.reload(), 900);
              });
            } else {
              closeSitinModal();
              showToast('Sit-in registered successfully.');
              setTimeout(() => window.location.reload(), 900);
            }
          })
          .catch(() => showSitinError('sitinSubmitError', 'Something went wrong. Please try again.'));
      });
    }

    const searchModal = document.getElementById('searchModal');
    if (searchModal) {
      searchModal.addEventListener('click', function(e) {
        if (e.target === this) closeSearchModal();
      });
    }

    const sitinModal = document.getElementById('sitinModal');
    if (sitinModal) {
      sitinModal.addEventListener('click', function(e) {
        if (e.target === this) closeSitinModal();
      });
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

    function isExpiredRequest(row) {
      return row.status === 'pending' && endDate(row) < new Date();
    }

    function isPendingRequest(row) {
      return row.status === 'pending' && endDate(row) >= new Date();
    }

    function isPostApprovedRequest(row) {
      return row.status === 'approved' && endDate(row) >= new Date();
    }

    function isApprovedNotRegistered(row) {
      return row.status === 'approved' && endDate(row) < new Date();
    }

    function isApprovedRegistered(row) {
      return row.status === 'done';
    }

    function isHistory(row) {
      return isExpiredRequest(row)
        || isApprovedNotRegistered(row)
        || isApprovedRegistered(row)
        || ['rejected', 'cancelled'].includes(row.status);
    }

    function effectiveStatus(row) {
      if (isExpiredRequest(row)) return 'expired';
      if (isApprovedNotRegistered(row)) return 'approved_not_registered';
      if (isApprovedRegistered(row)) return 'approved_registered';
      return row.status;
    }


    function reservationToSeat(row, baseSeat = {}) {
      const shownStatus = effectiveStatus(row);
      let layoutStatus = baseSeat.layout_status || baseSeat.status || 'available';

      if (row.status === 'pending') layoutStatus = 'pending';
      if (row.status === 'approved') layoutStatus = 'reserved';
      if (['rejected', 'cancelled', 'done'].includes(row.status) || shownStatus === 'expired') {
        layoutStatus = 'unavailable';
      }

      return {
        ...baseSeat,
        pc_number: Number(row.pc_number),
        status: shownStatus,
        layout_status: layoutStatus,
        reservation_status: shownStatus,
        reservation_id: row.id,
        studentid: row.studentid,
        fullname: row.fullname,
        purpose: row.purpose,
        lab: row.lab,
        reservation_date: row.reservation_date,
        reservation_time: row.reservation_time,
        reservation_end_time: row.reservation_end_time
      };
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

    function updateTabButtons() {
      document.getElementById('tabPending').classList.toggle('active', activeTab === 'pending');
      document.getElementById('tabPostApproved').classList.toggle('active', activeTab === 'postApproved');
      document.getElementById('tabHistory').classList.toggle('active', activeTab === 'history');
    }

    function setTab(tab) {
      activeTab = tab;
      const statusSelect = document.getElementById('statusFilter');
      if (statusSelect) statusSelect.value = 'all';
      updateTabButtons();
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

          if (pendingReservationForDetails) {
            const pc = Number(pendingReservationForDetails.pc_number);
            selectSeat(reservationToSeat(pendingReservationForDetails, byPc[pc] || {}));
            pendingReservationForDetails = null;
          } else {
            selectSeat(seatToSelect || byPc[1] || { pc_number: 1, status: 'available', layout_status: 'available' });
          }
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
      document.getElementById('detailStatus').innerHTML = statusBadge(shownStatus);

      document.getElementById('btnApprove').disabled = !isPending;
      document.getElementById('btnReject').disabled = !hasReservation || (!isPending && !isApproved);
      const registerBtn = document.getElementById('btnRegisterSitin');
      registerBtn.disabled = !isApproved;
      registerBtn.style.display = isApproved ? 'inline-flex' : 'none';

      const pcStatusBtn = document.getElementById('btnPcStatus');
      pcStatusBtn.disabled = false;
      pcStatusBtn.innerHTML = isUnavailable ? '<i class="bi bi-check2-circle"></i> Mark Available' : '<i class="bi bi-ban"></i> Mark Unavailable';
    }

    function ensureSelectOption(selectId, value, label = null) {
      const select = document.getElementById(selectId);
      if (!select || !value) return;
      if (![...select.options].some(option => option.value === value)) {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label || value;
        select.appendChild(option);
      }
    }

    function nameInitials(name) {
      const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
      if (!parts.length) return 'ST';
      if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
      return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    function openSitinModalFromReservation() {
      if (!selectedSeat || !selectedSeat.reservation_id || !(selectedSeat.reservation_status === 'approved' || selectedSeat.status === 'approved' || selectedSeat.layout_status === 'reserved')) {
        showToast('Please select an approved reservation first.');
        return;
      }

      openSitinModal();
      selectedReservationIdForSitin = selectedSeat.reservation_id;

      currentStudent = {
        studentid: selectedSeat.studentid || '',
        firstname: selectedSeat.fullname || '',
        lastname: '',
        middlename: '',
        course: '',
        yearlvl: '',
        session_credits: 'Approved'
      };

      document.getElementById('sitinIdInput').value = selectedSeat.studentid || '';
      document.getElementById('sitinAvatar').textContent = nameInitials(selectedSeat.fullname || selectedSeat.studentid);
      document.getElementById('sitinStudentName').textContent = selectedSeat.fullname || 'Selected student';
      document.getElementById('sitinStudentCourse').textContent = 'Approved reservation · ' + pcLabel(selectedSeat.pc_number);
      document.getElementById('sitinSessionBadge').textContent = 'Approved';
      document.getElementById('sitinStudentInfo').style.display = 'block';
      document.getElementById('sitinFormFields').style.display = 'block';

      document.getElementById('sitinPurpose').value = selectedSeat.purpose || '';
      ensureSelectOption('sitinLab', selectedSeat.lab || document.getElementById('viewLab').value);
      document.getElementById('sitinLab').value = selectedSeat.lab || document.getElementById('viewLab').value;
    }

    function confirmMessage(action) {
      const messages = {
        approve: 'Approve this reservation request?',
        reject: 'Reject this reservation request?',
        cancel: 'Cancel this approved reservation?',
        done: 'Mark this reservation as done?',
        delete: 'Delete this reservation request? This action cannot be undone.',
        mark_unavailable: 'Mark this PC as unavailable?',
        mark_available: 'Mark this PC as available again?',
        mark_all_unavailable: 'Mark all 56 PCs in this laboratory as unavailable?'
      };
      return messages[action] || 'Continue this action?';
    }

    function reservationAction(action) {
      if (!selectedSeat || !selectedSeat.reservation_id) {
        showToast('No reservation selected.');
        return;
      }
      if (action === 'reject' && selectedSeat.reservation_status === 'approved') action = 'cancel';

      askConfirm(confirmMessage(action), function () {
        const formData = new FormData();
        formData.append('reservation_id', selectedSeat.reservation_id);
        formData.append('action', action);
        postReservationAction(formData);
      });
    }

    function tableAction(id, action) {
      if (action === 'delete') return deleteReservation(id);

      askConfirm(confirmMessage(action), function () {
        const formData = new FormData();
        formData.append('reservation_id', id);
        formData.append('action', action);
        postReservationAction(formData);
      });
    }

    function deleteReservation(id) {
      askConfirm(confirmMessage('delete'), function () {
        const formData = new FormData();
        formData.append('reservation_id', id);
        formData.append('action', 'delete');
        postReservationAction(formData);
      });
    }

    function togglePcAvailability() {
      if (!selectedSeat) return;
      const visualStatus = selectedSeat.layout_status || selectedSeat.status;
      const action = visualStatus === 'unavailable' ? 'mark_available' : 'mark_unavailable';

      askConfirm(confirmMessage(action), function () {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('lab', document.getElementById('viewLab').value);
        formData.append('pc_number', selectedSeat.pc_number);
        postReservationAction(formData, true);
      });
    }

    function markAllUnavailable() {
      const lab = document.getElementById('viewLab').value;

      if (!lab) {
        showToast('Please select a laboratory first.');
        return;
      }

      askConfirm(confirmMessage('mark_all_unavailable'), function () {
        const formData = new FormData();
        formData.append('action', 'mark_all_unavailable');
        formData.append('lab', lab);
        postReservationAction(formData, true);
      });
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
      if (!row) {
        showToast('Reservation not found.');
        return;
      }

      const seatFromRow = reservationToSeat(row);
      selectedSeat = seatFromRow;
      updateDetails(seatFromRow);

      document.getElementById('viewLab').value = row.lab;
      document.getElementById('viewDate').value = row.reservation_date;
      const timeValue = String(row.reservation_time).substring(0, 5);
      const endValue = String(row.reservation_end_time || row.reservation_time).substring(0, 5);
      ensureTimeOption('viewTime', timeValue);
      ensureTimeOption('viewEndTime', endValue);
      document.getElementById('viewTime').value = timeValue;
      document.getElementById('viewEndTime').value = endValue;
      pendingSelectPc = Number(row.pc_number);
      pendingReservationForDetails = row;
      loadSeats();
      showToast('Reservation details loaded above.');
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
      const dotClass = status === 'approved' ? 'available'
        : status === 'approved_not_registered' ? 'approved_not_registered'
        : status === 'approved_registered' ? 'approved_registered'
        : status === 'pending' ? 'pending'
        : status === 'rejected' ? 'reserved'
        : status === 'expired' ? 'expired'
        : 'unavailable';

      const labels = {
        pending: 'pending request',
        approved: 'post-approved request',
        approved_not_registered: 'approved not registered sit-in',
        approved_registered: 'approved registered sit-in',
        rejected: 'rejected',
        cancelled: 'cancelled',
        expired: 'expired request',
        available: 'available'
      };

      const label = labels[status] || status;
      return `<span class="status-pill ${status}"><span class="dot ${dotClass}"></span>${label}</span>`;
    }

    function rowActions(row) {
      const id = Number(row.id);
      const shownStatus = effectiveStatus(row);
      let viewTitle = 'View details above';
      if (activeTab === 'postApproved' || row.status === 'approved') {
        viewTitle = 'View details above to register sit-in';
      }

      let buttons = `<button type="button" class="icon-btn" title="${viewTitle}" onclick="viewReservation(${id})"><i class="bi bi-eye"></i></button>`;
      buttons += `<button type="button" class="icon-btn delete" title="Delete" onclick="deleteReservation(${id})"><i class="bi bi-trash"></i></button>`;
      return `<div class="row-actions">${buttons}</div>`;
    }

    function renderTable() {
      const q = document.getElementById('searchInput').value.toLowerCase().trim();
      const status = document.getElementById('statusFilter').value;
      const limitValue = document.getElementById('entryLimit').value;
      const limit = limitValue === 'all' ? Infinity : Number(limitValue);
      const tbody = document.getElementById('reservationTbody');

      let rows = [];

      if (activeTab === 'pending') {
        rows = reservations.filter(row => isPendingRequest(row));
      } else if (activeTab === 'postApproved') {
        rows = reservations.filter(row => isPostApprovedRequest(row));
      } else {
        rows = reservations.filter(row => isHistory(row));
      }

      rows = rows.filter(row => status === 'all' || effectiveStatus(row) === status || row.status === status);
      rows = rows.filter(row => {
        const shownStatus = effectiveStatus(row);
        const text = `${row.id} ${row.studentid} ${row.fullname} ${row.lab} ${row.pc_number} ${row.purpose} ${row.status} ${shownStatus}`.toLowerCase();
        return text.includes(q);
      });

      const total = rows.length;
      rows = rows.slice(0, limit);

      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:26px;">No reservations found.</td></tr>';
      } else {
        tbody.innerHTML = rows.map(row => {
          const rid = 'R-' + new Date(row.reservation_date + 'T00:00:00').getFullYear() + '-' + String(row.id).padStart(5, '0');
          const shownStatus = effectiveStatus(row);
          return `
            <tr data-status="${shownStatus}">
              <td><strong>${rid}</strong></td>
              <td>${row.studentid || '—'}</td>
              <td>${row.fullname || '—'}</td>
              <td>${row.lab || '—'}</td>
              <td>${pcLabel(row.pc_number)}</td>
              <td>${row.purpose || '—'}</td>
              <td>${dateToLabel(row.reservation_date)}, ${timeToLabel(row.reservation_time)} - ${timeToLabel(row.reservation_end_time)}</td>
              <td>${statusBadge(shownStatus)}</td>
              <td>${rowActions(row)}</td>
            </tr>
          `;
        }).join('');
      }

      document.getElementById('entriesInfo').textContent = `Showing ${rows.length} of ${total} entries`;
    }

    updateTabButtons();
    loadSeats();
    renderTable();
  </script>
</body>
</html>
