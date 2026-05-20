<?php
// student_module/student_dashboard.php

session_start();
require_once __DIR__ . '/../config/db_config.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

if (empty($_SESSION['logged_in'])) {
    header('Location: ../home.php');
    exit;
}

$student_id = (int)($_SESSION['student_id'] ?? 0);

require_once __DIR__ . '/../controllers/announcements/student_notifications.php';

$firstname  = htmlspecialchars($_SESSION['firstname'] ?? '');
$lastname   = htmlspecialchars($_SESSION['lastname'] ?? '');
$middlename = htmlspecialchars($_SESSION['middlename'] ?? '');
$course     = htmlspecialchars($_SESSION['course'] ?? '');
$yearlvl    = htmlspecialchars($_SESSION['yearlvl'] ?? '');
$email      = htmlspecialchars($_SESSION['email'] ?? '');
$addrs      = htmlspecialchars($_SESSION['addrs'] ?? '');

$fullname = trim($lastname . ', ' . $firstname, ', ');
$initials = strtoupper(substr((string)$firstname, 0, 1) . substr((string)$lastname, 0, 1));

function tableExists(mysqli $conn, string $tableName): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $tableName);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;

    $stmt->close();

    return (int)($row['total'] ?? 0) > 0;
}

function columnExists(mysqli $conn, string $tableName, string $columnName): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $tableName, $columnName);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;

    $stmt->close();

    return (int)($row['total'] ?? 0) > 0;
}

function getStudentCount(mysqli $conn, string $sql, int $student_id): int
{
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $student_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;

    $stmt->close();

    return (int)($row['total'] ?? 0);
}

function statusBadgeClass(string $status): string
{
    $status = strtolower($status);

    if ($status === 'approved') return 'success';
    if ($status === 'pending') return 'warning';
    if ($status === 'rejected') return 'danger';
    if ($status === 'cancelled') return 'secondary';
    if ($status === 'done') return 'primary';

    return 'dark';
}

function formatDateTimeLabel(?string $dateTime): string
{
    if (!$dateTime) return '—';
    return date('M d, Y h:i A', strtotime($dateTime));
}

function formatReservationDateTime(?string $date, ?string $time): string
{
    if (!$date || !$time) return '—';
    return date('M d, Y', strtotime($date)) . ' · ' . date('h:i A', strtotime($time));
}

function testimonialInitials(?string $firstname, ?string $lastname): string
{
    $first = trim($firstname ?? '');
    $last = trim($lastname ?? '');
    return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
}

function shortText(string $text, int $limit = 140): string
{
    $text = trim($text);

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        return mb_substr($text, 0, $limit - 3) . '...';
    }

    if (strlen($text) <= $limit) {
        return $text;
    }

    return substr($text, 0, $limit - 3) . '...';
}

$hasStudentRewards = columnExists($conn, 'students', 'reward_points') && columnExists($conn, 'students', 'task_completed');
$hasReservationPc = columnExists($conn, 'lab_reservations', 'pc_number');
$hasSitinPc = columnExists($conn, 'sitin_records', 'pc_number');

/* Student values */
$session_credits = 0;
$reward_points = 0;
$task_completed = 0;

$studentSelect = 'session_credits';
if ($hasStudentRewards) {
    $studentSelect .= ', reward_points, task_completed';
}

$stmtCredits = $conn->prepare("SELECT $studentSelect FROM students WHERE id = ? LIMIT 1");
if ($stmtCredits) {
    $stmtCredits->bind_param('i', $student_id);
    $stmtCredits->execute();
    $resCredits = $stmtCredits->get_result();

    if ($rowCredits = $resCredits->fetch_assoc()) {
        $session_credits = (int)($rowCredits['session_credits'] ?? 0);
        $reward_points = (int)($rowCredits['reward_points'] ?? 0);
        $task_completed = (int)($rowCredits['task_completed'] ?? 0);
    }

    $stmtCredits->close();
}

/* Dashboard counts */
$totalReservations = tableExists($conn, 'lab_reservations')
    ? getStudentCount($conn, "SELECT COUNT(*) AS total FROM lab_reservations WHERE student_id = ?", $student_id)
    : 0;

$pendingReservations = tableExists($conn, 'lab_reservations')
    ? getStudentCount($conn, "SELECT COUNT(*) AS total FROM lab_reservations WHERE student_id = ? AND status = 'pending'", $student_id)
    : 0;

$approvedReservations = tableExists($conn, 'lab_reservations')
    ? getStudentCount($conn, "SELECT COUNT(*) AS total FROM lab_reservations WHERE student_id = ? AND status = 'approved'", $student_id)
    : 0;

$completedSitins = tableExists($conn, 'sitin_records')
    ? getStudentCount($conn, "SELECT COUNT(*) AS total FROM sitin_records WHERE student_id = ? AND status = 'done'", $student_id)
    : 0;

/* Latest reservation */
$latestReservation = null;
if (tableExists($conn, 'lab_reservations')) {
    $reservationPcSelect = $hasReservationPc ? 'pc_number' : 'NULL AS pc_number';

    $stmtLatestReservation = $conn->prepare("
        SELECT lab, $reservationPcSelect, purpose, reservation_date, reservation_time,
               COALESCE(reservation_end_time, ADDTIME(reservation_time, '01:00:00')) AS reservation_end_time,
               status
        FROM lab_reservations
        WHERE student_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");

    if ($stmtLatestReservation) {
        $stmtLatestReservation->bind_param('i', $student_id);
        $stmtLatestReservation->execute();
        $resultLatestReservation = $stmtLatestReservation->get_result();
        $latestReservation = $resultLatestReservation ? $resultLatestReservation->fetch_assoc() : null;
        $stmtLatestReservation->close();
    }
}

/* Active sit-in */
$activeSitin = null;
if (tableExists($conn, 'sitin_records')) {
    $sitinPcSelect = $hasSitinPc ? 'pc_number' : 'NULL AS pc_number';

    $stmtActiveSitin = $conn->prepare("
        SELECT purpose, lab, $sitinPcSelect, login_time, status
        FROM sitin_records
        WHERE student_id = ?
          AND status = 'active'
        ORDER BY login_time DESC
        LIMIT 1
    ");

    if ($stmtActiveSitin) {
        $stmtActiveSitin->bind_param('i', $student_id);
        $stmtActiveSitin->execute();
        $resultActiveSitin = $stmtActiveSitin->get_result();
        $activeSitin = $resultActiveSitin ? $resultActiveSitin->fetch_assoc() : null;
        $stmtActiveSitin->close();
    }
}

/* Announcements */
$announcements = [];
if (tableExists($conn, 'announcements')) {
    $announcement_feed_result = $conn->query("
        SELECT id, title, message, posted_by, created_at
        FROM announcements
        ORDER BY created_at DESC
        LIMIT 3
    ");

    if ($announcement_feed_result) {
        while ($row = $announcement_feed_result->fetch_assoc()) {
            $announcements[] = $row;
        }
    }
}

/* Testimonials */
$testimonials = [];
if (tableExists($conn, 'testimonials')) {
    $testimonialResult = $conn->query("
        SELECT 
            t.rating,
            t.message,
            t.created_at,
            s.firstname,
            s.lastname,
            s.course
        FROM testimonials t
        INNER JOIN students s ON s.id = t.student_id
        WHERE t.status = 'approved'
        ORDER BY t.created_at DESC
        LIMIT 4
    ");

    if ($testimonialResult) {
        while ($row = $testimonialResult->fetch_assoc()) {
            $testimonials[] = $row;
        }
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

  <title>UC – Student Dashboard</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/student.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
  </style>
</head>

<body class="student-dashboard-body student-dashboard-page">
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
      <div class="dashboard-shell">

        <section class="hero">
          <div class="hero-left">
            <h1>Welcome back, <?= $firstname ?> 👋</h1>
            <p>
              Maayo nga pag-abot sa UC Sit-in System. View your profile, monitor your sessions,
              check reservations, read announcements, and see student reviews.
            </p>
          </div>

          <div class="hero-badges">
            <div class="hero-pill"><?= $course ?: 'No Course' ?></div>
            <div class="hero-pill">Year <?= $yearlvl ?: '—' ?></div>
            <div class="hero-pill"><?= $session_credits ?> Sessions Left</div>
          </div>
        </section>

        <div class="stat-grid">
          <div class="stat-card">
            <div>
              <div class="stat-label">Reservations</div>
              <div class="stat-value"><?= $totalReservations ?></div>
            </div>
            <div class="stat-icon stat-blue">
              <i class="bi bi-calendar-check"></i>
            </div>
          </div>

          <div class="stat-card">
            <div>
              <div class="stat-label">Pending</div>
              <div class="stat-value"><?= $pendingReservations ?></div>
            </div>
            <div class="stat-icon stat-yellow">
              <i class="bi bi-hourglass-split"></i>
            </div>
          </div>

          <div class="stat-card">
            <div>
              <div class="stat-label">Approved</div>
              <div class="stat-value"><?= $approvedReservations ?></div>
            </div>
            <div class="stat-icon stat-green">
              <i class="bi bi-check-circle"></i>
            </div>
          </div>

          <div class="stat-card">
            <div>
              <div class="stat-label">Completed</div>
              <div class="stat-value"><?= $completedSitins ?></div>
            </div>
            <div class="stat-icon stat-purple">
              <i class="bi bi-pc-display"></i>
            </div>
          </div>
        </div>

        <div class="overview-grid">
          <div class="mini-card">
            <h5>
              <i class="bi bi-calendar-event text-primary"></i>
              Latest Reservation
            </h5>

            <?php if ($latestReservation): ?>
              <div class="detail-line">
                <span>Laboratory</span>
                <span><?= htmlspecialchars($latestReservation['lab']) ?></span>
              </div>

              <div class="detail-line">
                <span>PC No.</span>
                <span><?= !empty($latestReservation['pc_number']) ? 'PC ' . str_pad((string)(int)$latestReservation['pc_number'], 2, '0', STR_PAD_LEFT) : '—' ?></span>
              </div>

              <div class="detail-line">
                <span>Date</span>
                <span><?= formatReservationDateTime($latestReservation['reservation_date'], $latestReservation['reservation_time']) ?></span>
              </div>

              <div class="detail-line">
                <span>Status</span>
                <span>
                  <span class="badge text-bg-<?= statusBadgeClass($latestReservation['status']) ?>">
                    <?= htmlspecialchars(ucfirst($latestReservation['status'])) ?>
                  </span>
                </span>
              </div>
            <?php else: ?>
              <p class="mini-empty mb-0">No reservation yet. Create your first reservation to see details here.</p>
            <?php endif; ?>
          </div>

          <div class="mini-card">
            <h5>
              <i class="bi bi-activity text-success"></i>
              Current Sit-in
            </h5>

            <?php if ($activeSitin): ?>
              <div class="detail-line">
                <span>Laboratory</span>
                <span><?= htmlspecialchars($activeSitin['lab']) ?></span>
              </div>

              <div class="detail-line">
                <span>PC No.</span>
                <span><?= !empty($activeSitin['pc_number']) ? 'PC ' . str_pad((string)(int)$activeSitin['pc_number'], 2, '0', STR_PAD_LEFT) : '—' ?></span>
              </div>

              <div class="detail-line">
                <span>Purpose</span>
                <span><?= htmlspecialchars($activeSitin['purpose']) ?></span>
              </div>

              <div class="detail-line">
                <span>Time In</span>
                <span><?= formatDateTimeLabel($activeSitin['login_time']) ?></span>
              </div>
            <?php else: ?>
              <p class="mini-empty mb-0">You do not have an active sit-in session right now.</p>
            <?php endif; ?>
          </div>
        </div>

        <div class="dashboard-showcase">
          <div class="panel showcase-profile">
            <div class="panel-header">
              <div class="panel-header-title">
                <i class="bi bi-person"></i>
                Student Information
              </div>
            </div>

            <div class="panel-body">
              <div class="student-card-top">
                <div class="student-avatar">
                  <?php if (!empty($_SESSION['avatar'])): ?>
                    <img src="<?= htmlspecialchars($_SESSION['avatar']) ?>" alt="avatar">
                  <?php else: ?>
                    <?= $initials ?>
                  <?php endif; ?>
                </div>

                <div class="student-name"><?= $fullname ?: 'Student' ?></div>
                <div class="student-sub"><?= $course ?: 'No Course' ?> • <?= $yearlvl ?: '—' ?></div>
              </div>

              <div class="info-grid">
                <div class="info-row">
                  <i class="bi bi-mortarboard info-icon"></i>
                  <span><span class="info-label">Course:</span> <?= $course ?: '—' ?></span>
                </div>

                <div class="info-row">
                  <i class="bi bi-plus-lg info-icon"></i>
                  <span><span class="info-label">Year:</span> <?= $yearlvl ?: '—' ?></span>
                </div>

                <div class="info-row">
                  <i class="bi bi-envelope info-icon"></i>
                  <span><span class="info-label">Email:</span> <?= $email ?: '—' ?></span>
                </div>

                <div class="info-row">
                  <i class="bi bi-geo-alt info-icon"></i>
                  <span><span class="info-label">Address:</span> <?= $addrs ?: '—' ?></span>
                </div>
              </div>

              <div class="credit-badge">
                <small>Available Sessions</small>
                <strong><?= $session_credits ?></strong>
                <span>Remaining sit-in credits</span>
              </div>

              <?php if ($hasStudentRewards): ?>
                <div class="reward-mini">
                  <div class="reward-mini-card">
                    <small>Reward Points</small>
                    <strong><?= $reward_points ?></strong>
                  </div>

                  <div class="reward-mini-card">
                    <small>Tasks Done</small>
                    <strong><?= $task_completed ?></strong>
                  </div>
                </div>
              <?php endif; ?>

              <div class="quick-links">
                <a class="quick-link" href="reservation.php"><span>Make Reservation</span><span>→</span></a>
                <a class="quick-link" href="sitin_history.php"><span>View Sit-in History</span><span>→</span></a>
                <a class="quick-link" href="software_availability.php"><span>Software Availability</span><span>→</span></a>
                <a class="quick-link" href="rewards.php"><span>Rewards / Points</span><span>→</span></a>
              </div>
            </div>
          </div>

          <div class="panel showcase-testimonials">
            <div class="panel-header">
              <div class="panel-header-title">
                <i class="bi bi-star"></i>
                Student Reviews / Testimonials
              </div>
              <a href="testimonials.php">Write review</a>
            </div>

            <div class="panel-body">
              <?php if (!empty($testimonials)): ?>
                <div class="testimonial-grid">
                  <?php foreach ($testimonials as $review): ?>
                    <?php
                      $reviewFirstname = htmlspecialchars($review['firstname'] ?? '');
                      $reviewLastname = htmlspecialchars($review['lastname'] ?? '');
                      $reviewName = trim($reviewFirstname . ' ' . $reviewLastname);
                      $reviewCourse = htmlspecialchars($review['course'] ?? '');
                      $reviewInitials = testimonialInitials($review['firstname'] ?? '', $review['lastname'] ?? '');
                      $reviewRating = (int)($review['rating'] ?? 5);
                      $reviewMessage = htmlspecialchars(shortText($review['message'] ?? '', 110));
                      $reviewDate = !empty($review['created_at']) ? date('M d, Y', strtotime($review['created_at'])) : '—';
                    ?>

                    <div class="testimonial-card">
                      <div class="testimonial-top">
                        <div class="testimonial-avatar"><?= $reviewInitials ?: 'ST' ?></div>
                        <div>
                          <div class="testimonial-name"><?= $reviewName ?: 'Student' ?></div>
                          <div class="testimonial-course"><?= $reviewCourse ?: 'UC Student' ?></div>
                        </div>
                      </div>

                      <div class="testimonial-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                          <i class="bi <?= $i <= $reviewRating ? 'bi-star-fill' : 'bi-star' ?>"></i>
                        <?php endfor; ?>
                      </div>

                      <div class="testimonial-message">“<?= $reviewMessage ?>”</div>
                      <div class="testimonial-date"><?= $reviewDate ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="mini-card">
                  <h5><i class="bi bi-chat-square-heart text-primary"></i>No testimonials yet</h5>
                  <p class="mini-empty mb-3">Student reviews will appear here once testimonials are submitted.</p>
                  <a href="testimonials.php" class="btn btn-primary btn-sm rounded-pill">Write First Review</a>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="panel showcase-rules">
            <div class="panel-header">
              <div class="panel-header-title">
                <i class="bi bi-clipboard-check"></i>
                Laboratory Rules
              </div>
            </div>

            <div class="panel-body rules-body">
              <h2>University of Cebu</h2>
              <h3>Computer Laboratory Rules and Regulations</h3>

              <h4>General Rules</h4>
              <p>Students must maintain proper conduct while inside the laboratory at all times.</p>
              <p>Only authorized users are allowed to use the computer laboratory facilities.</p>
              <p>Eating, drinking, loud conversations, and disruptive behavior are not allowed.</p>

              <h4>Laboratory Use</h4>
              <p>Students must log in properly before using any computer unit.</p>
              <p>Use only the assigned computer and report any issue immediately to the laboratory personnel.</p>
              <p>Do not install, delete, or modify any software or system settings without permission.</p>

              <h4>Internet and Files</h4>
              <p>Internet access must be used only for academic and authorized purposes.</p>
              <p>Downloading malicious, illegal, or unauthorized content is strictly prohibited.</p>
              <p>Students are responsible for saving and backing up their files properly.</p>

              <h4>Respect for Equipment</h4>
              <p>Handle all equipment carefully. Any intentional damage will be subject to disciplinary action.</p>
              <p>Keep the area clean and organized before leaving the laboratory.</p>

              <h4>Penalty</h4>
              <p>Violation of these rules may result in suspension of laboratory privileges and other disciplinary measures.</p>
            </div>
          </div>

          <div class="panel showcase-announcements">
            <div class="panel-header">
              <div class="panel-header-title">
                <i class="bi bi-bookmark"></i>
                Latest Announcements
              </div>
              <a href="announcements.php">View all</a>
            </div>

            <div class="panel-body">
              <?php if (!empty($announcements)): ?>
                <div class="announce-feed">
                  <?php foreach ($announcements as $announcement): ?>
                    <div class="announce-item">
                      <div class="announce-title"><?= htmlspecialchars($announcement['title']) ?></div>
                      <div class="announce-meta">
                        <?= htmlspecialchars($announcement['posted_by']) ?> · <?= date('M d, Y h:i A', strtotime($announcement['created_at'])) ?>
                      </div>
                      <div class="announce-text"><?= htmlspecialchars(shortText($announcement['message'], 220)) ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="mini-card">
                  <h5><i class="bi bi-info-circle text-primary"></i>No announcements yet</h5>
                  <p class="mini-empty mb-0">Announcements posted by the administrator will appear here.</p>
                </div>
              <?php endif; ?>
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

        if (navLinks) navLinks.classList.toggle('open');
        if (sidebar) sidebar.classList.toggle('open');
      });
    }

    window.addEventListener('pageshow', function(e) {
      if (e.persisted) {
        fetch('../controllers/auth/check_session.php', { cache: 'no-store' })
          .then(res => res.json())
          .then(data => {
            if (!data.logged_in) {
              window.location.replace('../home.php');
            }
          })
          .catch(() => {});
      }
    });

    const notifications = <?= json_encode($notifications ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
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

    function openModal() {
      const modal = document.getElementById('editModal');
      if (modal) modal.style.display = 'flex';
    }

    function closeModal() {
      const modal = document.getElementById('editModal');
      if (modal) modal.style.display = 'none';
    }

    const editModal = document.getElementById('editModal');
    if (editModal) {
      editModal.addEventListener('click', function(e) {
        if (e.target === this) closeModal();
      });
    }
  </script>
</body>
</html>
