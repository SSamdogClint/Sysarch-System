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
$middlename = htmlspecialchars($_SESSION['middlename'] ?? '');
$course     = htmlspecialchars($_SESSION['course'] ?? '');
$yearlvl    = htmlspecialchars($_SESSION['yearlvl'] ?? '');
$email      = htmlspecialchars($_SESSION['email'] ?? '');
$addrs      = htmlspecialchars($_SESSION['addrs'] ?? '');
$initials   = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));

require_once '../controllers/announcements/student_notifications.php';

$softwareByLab = [];

$softwareTableCheck = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'software_availability'
");

if ($softwareTableCheck) {
    $softwareTableCheck->execute();
    $softwareTableResult = $softwareTableCheck->get_result();
    $softwareTableRow = $softwareTableResult ? $softwareTableResult->fetch_assoc() : null;
    $softwareTableCheck->close();

    if ((int)($softwareTableRow['total'] ?? 0) > 0) {
        $softwareResult = $conn->query("
            SELECT lab, software_name, category, version, status
            FROM software_availability
            ORDER BY lab ASC, software_name ASC
        ");

        if ($softwareResult) {
            while ($software = $softwareResult->fetch_assoc()) {
                $labName = $software['lab'];

                if (!isset($softwareByLab[$labName])) {
                    $softwareByLab[$labName] = [];
                }

                $softwareByLab[$labName][] = [
                    'software_name' => $software['software_name'],
                    'category' => $software['category'],
                    'version' => $software['version'],
                    'status' => $software['status']
                ];
            }
        }
    }
}


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

$today = date('Y-m-d');

$allReservations = $reservations;
$pendingReservations = [];
$approvedReservations = [];
$cancelledReservations = [];

foreach ($reservations as $reservation) {
    $status = strtolower($reservation['status'] ?? '');

    if ($status === 'pending') {
        $pendingReservations[] = $reservation;
    }

    /*
      Approved Reservation tab:
      Only reservations already approved by admin or already completed.
      Cancelled reservations are separated into their own tab.
      Progress labels:
      - Scheduled: approved and still upcoming
      - Ongoing Schedule: approved and currently within the reserved time
      - Completed: completed/done reservation
      - Uncompleted: approved schedule already passed but was not completed
    */
    if (in_array($status, ['approved', 'completed', 'done'], true)) {
        $approvedReservations[] = $reservation;
    }

    /*
      Cancelled Reservation tab:
      All cancelled reservations go here, whether they were cancelled while pending
      or cancelled after approval.
    */
    if ($status === 'cancelled') {
        $cancelledReservations[] = $reservation;
    }
}

function reservationStatusBadge(string $status): string
{
    $status = strtolower(trim($status));

    if ($status === 'approved') {
        return 'success';
    }

    if ($status === 'pending') {
        return 'warning';
    }

    if ($status === 'rejected') {
        return 'danger';
    }

    if ($status === 'cancelled') {
        return 'secondary';
    }

    if ($status === 'done' || $status === 'completed') {
        return 'primary';
    }

    return 'dark';
}

function reservationProgressBadge(array $reservation): array
{
    $status = strtolower(trim($reservation['status'] ?? ''));
    $date = $reservation['reservation_date'] ?? '';
    $start = $reservation['reservation_time'] ?? '';
    $end = $reservation['reservation_end_time'] ?? '';

    if ($status === 'done' || $status === 'completed') {
        return ['Completed', 'success'];
    }

    if ($status === 'cancelled') {
        return ['Cancelled', 'secondary'];
    }

    if ($status === 'pending') {
        return ['Pending Approval', 'warning'];
    }

    if ($status === 'rejected') {
        return ['Rejected', 'danger'];
    }

    if ($status === 'approved') {
        $startTimestamp = ($date && $start) ? strtotime($date . ' ' . $start) : 0;
        $endTimestamp = ($date && $end) ? strtotime($date . ' ' . $end) : 0;
        $now = time();

        if ($startTimestamp && $startTimestamp > $now) {
            return ['Scheduled', 'info'];
        }

        if ($startTimestamp && $endTimestamp && $startTimestamp <= $now && $endTimestamp >= $now) {
            return ['Ongoing Schedule', 'primary'];
        }

        return ['Uncompleted', 'danger'];
    }

    return ['Unknown', 'dark'];
}

function formatReservationDate(?string $date): string
{
    if (!$date) {
        return '—';
    }

    return date('M d, Y', strtotime($date));
}

function formatReservationTime(?string $start, ?string $end): string
{
    if (!$start) {
        return '—';
    }

    $startLabel = date('h:i A', strtotime($start));
    $endLabel = $end ? date('h:i A', strtotime($end)) : date('h:i A', strtotime($start . ' +1 hour'));

    return $startLabel . ' - ' . $endLabel;
}

function renderReservationRows(array $reservations, string $tabType, string $today): void
{
    if (empty($reservations)) {
        echo '
            <tr>
                <td colspan="9" class="text-center text-muted py-4">
                    No reservation records found.
                </td>
            </tr>
        ';
        return;
    }

    foreach ($reservations as $i => $r) {
        $id = (int)$r['id'];
        $status = strtolower($r['status'] ?? '');
        $statusClass = reservationStatusBadge($status);
        $reservationDate = $r['reservation_date'] ?? '';
        $isFutureOrToday = $reservationDate >= $today;

        $progress = reservationProgressBadge($r);
        $progressText = $progress[0];
        $progressClass = $progress[1];

        $lab = htmlspecialchars($r['lab'] ?? '—');
        $pcNumber = !empty($r['pc_number']) ? 'PC ' . str_pad((string)(int)$r['pc_number'], 2, '0', STR_PAD_LEFT) : '—';
        $purpose = htmlspecialchars($r['purpose'] ?? '—');
        $date = formatReservationDate($r['reservation_date'] ?? null);
        $time = formatReservationTime($r['reservation_time'] ?? null, $r['reservation_end_time'] ?? null);
        $statusText = htmlspecialchars(ucfirst($status));
        $createdAt = !empty($r['created_at']) ? date('M d, Y h:i A', strtotime($r['created_at'])) : '—';

        echo '<tr>';
        echo '<td>' . ($i + 1) . '</td>';
        echo '<td>' . $lab . '</td>';
        echo '<td>' . htmlspecialchars($pcNumber) . '</td>';
        echo '<td>' . $purpose . '</td>';
        echo '<td>' . htmlspecialchars($date) . '</td>';
        echo '<td>' . htmlspecialchars($time) . '</td>';
        echo '<td><span class="badge text-bg-' . $statusClass . '">' . $statusText . '</span></td>';
        echo '<td><span class="badge text-bg-' . $progressClass . '">' . htmlspecialchars($progressText) . '</span></td>';

        echo '<td>';

        if ($tabType === 'approved') {
            if ($status === 'approved' && $isFutureOrToday) {
                echo '
                    <button 
                        type="button" 
                        class="btn btn-sm btn-outline-danger"
                        onclick="toggleReservation(' . $id . ', \'disable\')">
                        Disable
                    </button>
                ';
            } else {
                echo '<span class="text-muted small">No action</span>';
            }
        } elseif ($tabType === 'pending') {
            if ($status === 'pending' && $isFutureOrToday) {
                echo '
                    <button 
                        type="button" 
                        class="btn btn-sm btn-outline-danger"
                        onclick="toggleReservation(' . $id . ', \'disable\')">
                        Cancel
                    </button>
                ';
            } else {
                echo '<span class="text-muted small">No action</span>';
            }
        } elseif ($tabType === 'cancelled') {
            echo '<span class="text-muted small">Cancelled</span>';
        } else {
            echo '<span class="text-muted small">' . htmlspecialchars($createdAt) . '</span>';
        }

        echo '</td>';
        echo '</tr>';
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

  <title>UC – Reservation</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/student.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body class="student-reservation-page student-dashboard-page">
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
          <span class="notif-dot <?= !empty($notifications) ? 'show' : '' ?>" id="notifDot"></span>
        </button>

        <div class="notif-menu" id="notifMenu">
          <div class="notif-menu-header">Notifications</div>

          <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notif): ?>
              <a class="notif-menu-item" href="<?= htmlspecialchars($notif['url'] ?? 'notifications.php') ?>">
                <div class="notif-type <?= htmlspecialchars($notif['type']) ?>">
                  <?= htmlspecialchars($notif['label'] ?? ($notif['type'] === 'announcement' ? 'Announcement' : 'Session')) ?>
                </div>
                <div class="notif-title"><?= htmlspecialchars($notif['title']) ?></div>
                <div class="notif-text"><?= htmlspecialchars($notif['message']) ?></div>
                <div class="notif-time"><?= date('M d, Y h:i A', strtotime($notif['created_at'])) ?></div>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="notif-empty">No notifications yet.</div>
          <?php endif; ?>

          <a class="notif-menu-footer" href="notifications.php">
            View all notifications
            <i class="bi bi-arrow-right-short"></i>
          </a>
        </div>
      </div>

      <button type="button" class="dark-toggle" id="darkModeToggle" onclick="toggleDarkMode()" aria-label="Toggle dark mode" aria-pressed="false">
        <i class="bi bi-moon-stars"></i>
        <span>Dark</span>
      </button>

      <span style="font-size:13px;color:#6b7280;padding:0 4px;">
        <?= $firstname . ' ' . $lastname ?>
      </span>

      <div class="nav-divider"></div>

      <a class="nav-link" href="../controllers/auth/logout.php">Log out</a>
    </div>
  </nav>

  <div class="admin-layout">
    <?php require __DIR__ . '/../includes/student_sidebar.php'; ?>

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
              <select id="lab" name="lab" onchange="handleLabChange()">
                <option value="Lab 524">Lab 524</option>
                <option value="Lab 526">Lab 526</option>
                <option value="Lab 528">Lab 528</option>
                <option value="Lab 530">Lab 530</option>
                <option value="Lab 542">Lab 542</option>
                <option value="Lab 544">Lab 544</option>
              </select>
            </div>

            <div class="field">
              <div class="card border shadow-sm">
                <div class="card-header bg-white py-2 px-3">
                  <div class="d-flex justify-content-between align-items-center">
                    <strong style="font-size:13px;">
                      <i class="bi bi-window-stack me-1 text-primary"></i>
                      Software Availability
                    </strong>
                    <span class="badge text-bg-primary" id="softwareCountBadge">0 apps</span>
                  </div>
                </div>

                <div class="card-body p-3">
                  <div id="selectedLabSoftware" class="d-grid gap-2" style="max-height:220px; overflow-y:auto; padding-right:4px;">
                    <div class="text-muted small">Select a laboratory to view available software.</div>
                  </div>
                </div>
              </div>
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

            <button class="btn-reserve" id="reserveBtn" onclick="submitReservation()" disabled>
              Choose a PC first
            </button>
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

      <div class="container-fluid px-4 pb-4">
        <section class="card border-0 shadow-sm">
          <div class="card-header bg-white border-0 pt-4 px-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
              <div>
                <h4 class="mb-1">
                  <i class="bi bi-calendar-check me-1"></i>
                  Reservation Records
                </h4>
                <p class="text-muted mb-0">
                  View all, pending, approved, and cancelled reservations.
                </p>
              </div>

              <span class="badge text-bg-primary rounded-pill">
                <?= count($allReservations) ?> total records
              </span>
            </div>
          </div>

          <div class="card-body p-4">
            <div class="alert alert-info">
              <i class="bi bi-info-circle me-1"></i>
              In <strong>Approved Reservation</strong>, the progress will show as <strong>Scheduled</strong>, <strong>Ongoing Schedule</strong>, <strong>Completed</strong>, or <strong>Uncompleted</strong>. Cancelled reservations are separated in the <strong>Cancelled Reservation</strong> tab.
            </div>

            <ul class="nav nav-tabs mt-3" id="reservationTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button
                  class="nav-link active"
                  id="all-tab"
                  data-bs-toggle="tab"
                  data-bs-target="#all-reservations"
                  type="button"
                  role="tab">
                  All Reservation
                  <span class="badge text-bg-secondary ms-1"><?= count($allReservations) ?></span>
                </button>
              </li>

              <li class="nav-item" role="presentation">
                <button
                  class="nav-link"
                  id="pending-tab"
                  data-bs-toggle="tab"
                  data-bs-target="#pending-reservations"
                  type="button"
                  role="tab">
                  Pending Reservation
                  <span class="badge text-bg-secondary ms-1"><?= count($pendingReservations) ?></span>
                </button>
              </li>

              <li class="nav-item" role="presentation">
                <button
                  class="nav-link"
                  id="approved-tab"
                  data-bs-toggle="tab"
                  data-bs-target="#approved-reservations"
                  type="button"
                  role="tab">
                  Approved Reservation
                  <span class="badge text-bg-secondary ms-1"><?= count($approvedReservations) ?></span>
                </button>
              </li>

              <li class="nav-item" role="presentation">
                <button
                  class="nav-link"
                  id="cancelled-tab"
                  data-bs-toggle="tab"
                  data-bs-target="#cancelled-reservations"
                  type="button"
                  role="tab">
                  Cancelled Reservation
                  <span class="badge text-bg-secondary ms-1"><?= count($cancelledReservations) ?></span>
                </button>
              </li>
            </ul>

            <div class="tab-content border border-top-0 rounded-bottom p-3 bg-white" id="reservationTabsContent">
              <div class="tab-pane fade show active" id="all-reservations" role="tabpanel" aria-labelledby="all-tab">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>#</th>
                        <th>Lab</th>
                        <th>PC</th>
                        <th>Purpose</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Created At</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php renderReservationRows($allReservations, 'all', $today); ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="tab-pane fade" id="pending-reservations" role="tabpanel" aria-labelledby="pending-tab">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>#</th>
                        <th>Lab</th>
                        <th>PC</th>
                        <th>Purpose</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Control</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php renderReservationRows($pendingReservations, 'pending', $today); ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="tab-pane fade" id="approved-reservations" role="tabpanel" aria-labelledby="approved-tab">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>#</th>
                        <th>Lab</th>
                        <th>PC</th>
                        <th>Purpose</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Control</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php renderReservationRows($approvedReservations, 'approved', $today); ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="tab-pane fade" id="cancelled-reservations" role="tabpanel" aria-labelledby="cancelled-tab">
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>#</th>
                        <th>Lab</th>
                        <th>PC</th>
                        <th>Purpose</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php renderReservationRows($cancelledReservations, 'cancelled', $today); ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
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
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Middle Name</label>
            <input type="text" name="middlename" value="<?= $middlename ?>" style="
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

  <!-- Beautiful Message Modal -->
  <div class="modal fade" id="appMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-body text-center p-5">
          <div id="appMessageIcon" class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width:70px;height:70px;">
            <i class="bi bi-check-circle fs-1"></i>
          </div>

          <h5 class="fw-bold mb-2" id="appMessageTitle">Success</h5>
          <p class="text-muted mb-4" id="appMessageText">Action completed successfully.</p>

          <button type="button" class="btn btn-primary px-4 rounded-pill" data-bs-dismiss="modal">
            Okay
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Beautiful Confirm Modal -->
  <div class="modal fade" id="appConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-body p-5 text-center">
          <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning" style="width:70px;height:70px;">
            <i class="bi bi-question-circle fs-1"></i>
          </div>

          <h5 class="fw-bold mb-2" id="appConfirmTitle">Confirm Action</h5>
          <p class="text-muted mb-4" id="appConfirmText">Are you sure you want to continue?</p>

          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-light border px-4 rounded-pill" data-bs-dismiss="modal">
              Cancel
            </button>

            <button type="button" class="btn btn-primary px-4 rounded-pill" id="appConfirmYesBtn">
              Yes, Continue
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  if (localStorage.getItem('uc_dark_mode') === 'enabled') {
    document.body.classList.add('dark-mode');
  }

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
    renderSoftwareAvailability();
  }

  applyDarkMode();

  const pcGrid = document.getElementById('pcGrid');
  const reserveBtn = document.getElementById('reserveBtn');
  const softwareByLab = <?= json_encode($softwareByLab, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

  function renderSoftwareAvailability() {
    const labSelect = document.getElementById('lab');
    const softwareContainer = document.getElementById('selectedLabSoftware');
    const softwareCountBadge = document.getElementById('softwareCountBadge');

    if (!labSelect || !softwareContainer || !softwareCountBadge) {
      return;
    }

    const selectedLab = labSelect.value;
    const softwareList = softwareByLab[selectedLab] || [];

    softwareContainer.innerHTML = '';
    softwareCountBadge.textContent = softwareList.length + ' apps';

    if (!softwareList.length) {
      const empty = document.createElement('div');
      empty.className = 'text-muted small';
      empty.textContent = 'No software records found for ' + selectedLab + '.';
      softwareContainer.appendChild(empty);
      return;
    }

    softwareList.forEach(app => {
      const item = document.createElement('div');
      item.className = 'border rounded-3 p-2 software-item';
      item.style.background = document.body.classList.contains('dark-mode') ? '#0f172a' : '#f8fafc';

      const top = document.createElement('div');
      top.className = 'd-flex justify-content-between align-items-start gap-2';

      const nameBox = document.createElement('div');

      const name = document.createElement('div');
      name.className = 'fw-semibold';
      name.style.fontSize = '13px';
      name.textContent = app.software_name || 'Unnamed Software';

      const details = document.createElement('div');
      details.className = 'text-muted small';
      details.textContent = (app.category || 'No category') + ' · Version: ' + (app.version || 'N/A');

      nameBox.appendChild(name);
      nameBox.appendChild(details);

      const badge = document.createElement('span');
      const status = (app.status || 'installed').toLowerCase();

      badge.className = status === 'installed'
        ? 'badge text-bg-success'
        : 'badge text-bg-danger';

      badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);

      top.appendChild(nameBox);
      top.appendChild(badge);

      item.appendChild(top);
      softwareContainer.appendChild(item);
    });
  }

  function handleLabChange() {
    renderSoftwareAvailability();
    loadSeats();
  }


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

    if (!box) return;

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

        data.seats.forEach(seat => {
          byPc[seat.pc_number] = seat;
        });

        pcGrid.innerHTML = '';

        seatOrder().forEach(pc => {
          const seat = byPc[pc] || {
            pc_number: pc,
            status: 'available',
            layout_status: 'available'
          };

          const visualStatus = seat.layout_status || seat.status || 'available';

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

  function showAppModal(type, title, message, callback = null) {
    const modalEl = document.getElementById('appMessageModal');
    const iconBox = document.getElementById('appMessageIcon');
    const icon = iconBox.querySelector('i');
    const titleEl = document.getElementById('appMessageTitle');
    const textEl = document.getElementById('appMessageText');

    iconBox.className = 'mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle';

    if (type === 'success') {
      iconBox.classList.add('bg-success-subtle', 'text-success');
      icon.className = 'bi bi-check-circle fs-1';
    } else if (type === 'error') {
      iconBox.classList.add('bg-danger-subtle', 'text-danger');
      icon.className = 'bi bi-x-circle fs-1';
    } else if (type === 'warning') {
      iconBox.classList.add('bg-warning-subtle', 'text-warning');
      icon.className = 'bi bi-exclamation-triangle fs-1';
    } else {
      iconBox.classList.add('bg-primary-subtle', 'text-primary');
      icon.className = 'bi bi-info-circle fs-1';
    }

    titleEl.textContent = title;
    textEl.textContent = message;

    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    if (callback) {
      modalEl.addEventListener('hidden.bs.modal', callback, { once: true });
    }
  }

  function showConfirmModal(title, message, confirmText = 'Yes, Continue') {
    return new Promise((resolve) => {
      const modalEl = document.getElementById('appConfirmModal');
      const titleEl = document.getElementById('appConfirmTitle');
      const textEl = document.getElementById('appConfirmText');
      const yesBtn = document.getElementById('appConfirmYesBtn');

      titleEl.textContent = title;
      textEl.textContent = message;
      yesBtn.textContent = confirmText;

      const modal = new bootstrap.Modal(modalEl);

      let resolved = false;

      const yesHandler = () => {
        resolved = true;
        yesBtn.removeEventListener('click', yesHandler);
        modal.hide();
        resolve(true);
      };

      const hiddenHandler = () => {
        yesBtn.removeEventListener('click', yesHandler);
        modalEl.removeEventListener('hidden.bs.modal', hiddenHandler);

        if (!resolved) {
          resolve(false);
        }
      };

      yesBtn.addEventListener('click', yesHandler);
      modalEl.addEventListener('hidden.bs.modal', hiddenHandler, { once: true });

      modal.show();
    });
  }

  async function toggleReservation(reservationId, action) {
    let confirmTitle = '';
    let confirmMessage = '';
    let confirmButton = '';

    if (action === 'disable') {
      confirmTitle = 'Disable Reservation?';
      confirmMessage = 'This will cancel your selected reservation. You can enable it again later if the slot is still available.';
      confirmButton = 'Yes, Disable';
    } else {
      confirmTitle = 'Enable Reservation?';
      confirmMessage = 'This will return your cancelled reservation to pending status. The admin still needs to approve it again.';
      confirmButton = 'Yes, Enable';
    }

    const confirmed = await showConfirmModal(confirmTitle, confirmMessage, confirmButton);

    if (!confirmed) {
      return;
    }

    const formData = new FormData();

    formData.append('reservation_id', reservationId);
    formData.append('action', action);

    fetch('../controllers/reservation/student_toggle_reservation.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          showAppModal(
            'error',
            'Action Failed',
            data.message || 'Unable to update the reservation.'
          );
          return;
        }

        showAppModal(
          'success',
          'Reservation Updated',
          data.message || 'Reservation updated successfully.',
          function () {
            window.location.reload();
          }
        );
      })
      .catch(() => {
        showAppModal(
          'error',
          'Something Went Wrong',
          'Unable to update the reservation. Please try again.'
        );
      });
  }

  function openReservationTabFromHash() {
    const hash = window.location.hash;
    if (!hash) return;

    const targetMap = {
      '#pending-reservations': '#pending-tab',
      '#approved-reservations': '#approved-tab',
      '#cancelled-reservations': '#cancelled-tab',
      '#all-reservations': '#all-tab'
    };

    const tabSelector = targetMap[hash];
    if (!tabSelector) return;

    const tabButton = document.querySelector(tabSelector);
    if (tabButton && window.bootstrap) {
      const tab = new bootstrap.Tab(tabButton);
      tab.show();
      setTimeout(() => tabButton.scrollIntoView({ behavior: 'smooth', block: 'center' }), 150);
    }
  }

  openReservationTabFromHash();
  window.addEventListener('hashchange', openReservationTabFromHash);

  handleLabChange();
</script>
</body>
</html>