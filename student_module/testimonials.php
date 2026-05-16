<?php
// student_module/testimonials.php

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

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)($_POST['rating'] ?? 5);
    $testimonial = trim($_POST['message'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }

    if ($testimonial === '') {
        $message = 'Please write your testimonial first.';
        $messageType = 'danger';
    } else {
        $stmt = $conn->prepare("INSERT INTO testimonials (student_id, rating, message, status) VALUES (?, ?, ?, 'approved')");

        if ($stmt) {
            $stmt->bind_param('iis', $student_id, $rating, $testimonial);
            $stmt->execute();
            $stmt->close();

            $message = 'Your testimonial was submitted successfully.';
            $messageType = 'success';
        } else {
            $message = 'Unable to submit testimonial. Please check if the testimonials table exists.';
            $messageType = 'danger';
        }
    }
}

$myTestimonials = [];
$stmt = $conn->prepare("SELECT rating, message, created_at FROM testimonials WHERE student_id = ? ORDER BY created_at DESC");
if ($stmt) {
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $myTestimonials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$totalTestimonials = count($myTestimonials);
$averageRating = 0;
if ($totalTestimonials > 0) {
    $ratingTotal = 0;
    foreach ($myTestimonials as $item) {
        $ratingTotal += (int)$item['rating'];
    }
    $averageRating = round($ratingTotal / $totalTestimonials, 1);
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

  <title>UC – Testimonials</title>

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
      <a class="nav-link" href="../controllers/auth/logout.php">Log out</a>
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
                    <i class="bi bi-chat-square-heart fs-3"></i>
                  </div>
                  <div>
                    <h1 class="h3 mb-1">Student Testimonials</h1>
                    <p class="text-muted mb-0">Share your experience using the UC CCS sit-in monitoring system.</p>
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

        <?php if ($message): ?>
          <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi <?= $messageType === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <div class="row g-4">
          <div class="col-xl-5">
            <div class="card border shadow-sm h-100">
              <div class="card-header bg-transparent border-bottom p-4">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-pencil-square text-primary fs-4"></i>
                  <div>
                    <h2 class="h5 mb-0">Submit Testimonial</h2>
                    <small class="text-muted">Write a short feedback about your experience.</small>
                  </div>
                </div>
              </div>

              <div class="card-body p-4">
                <form method="POST">
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Rating</label>
                    <select class="form-select" name="rating" required>
                      <option value="5">5 - Excellent</option>
                      <option value="4">4 - Very Good</option>
                      <option value="3">3 - Good</option>
                      <option value="2">2 - Fair</option>
                      <option value="1">1 - Poor</option>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Message</label>
                    <textarea class="form-control" name="message" rows="7" placeholder="Example: The system helped me reserve a laboratory PC easily and monitor my remaining sessions." required></textarea>
                  </div>

                  <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Your testimonial will be saved immediately.
                  </div>

                  <button class="btn btn-primary w-100 py-2" type="submit">
                    <i class="bi bi-send me-1"></i>
                    Submit Testimonial
                  </button>
                </form>
              </div>
            </div>
          </div>

          <div class="col-xl-7">
            <div class="card border shadow-sm h-100">
              <div class="card-header bg-transparent border-bottom p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-card-list text-primary fs-4"></i>
                    <div>
                      <h2 class="h5 mb-0">My Testimonials</h2>
                      <small class="text-muted">Review the testimonials you submitted.</small>
                    </div>
                  </div>

                  <div class="d-flex flex-wrap gap-2">
                    <span class="badge text-bg-primary rounded-pill"><?= $totalTestimonials ?> submitted</span>
                    <span class="badge text-bg-warning rounded-pill"><?= $averageRating ?: '0' ?> avg rating</span>
                  </div>
                </div>
              </div>

              <div class="card-body p-4">
                <?php if (!$myTestimonials): ?>
                  <div class="text-center py-5">
                    <i class="bi bi-chat-square-text display-5 text-muted"></i>
                    <h3 class="h5 mt-3">No testimonials yet</h3>
                    <p class="text-muted mb-0">Submit your first testimonial using the form.</p>
                  </div>
                <?php else: ?>
                  <div class="list-group list-group-flush">
                    <?php foreach ($myTestimonials as $item): ?>
                      <div class="list-group-item px-0 py-3 bg-transparent">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                          <div>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                              <i class="bi <?= $i <= (int)$item['rating'] ? 'bi-star-fill text-warning' : 'bi-star text-muted' ?>"></i>
                            <?php endfor; ?>
                          </div>
                          <small class="text-muted">
                            <i class="bi bi-calendar-event me-1"></i>
                            <?= htmlspecialchars($item['created_at']) ?>
                          </small>
                        </div>

                        <p class="mb-0"><?= nl2br(htmlspecialchars($item['message'])) ?></p>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Edit Profile</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="../controllers/student/update_profile.php" method="POST">
          <div class="modal-body">
            <input type="hidden" name="student_id" value="<?= (int)$student_id ?>">
            <input type="hidden" name="studentid" value="<?= $studentid_no ?>">
            <input type="hidden" name="middlename" value="<?= $middlename ?>">

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">First Name</label>
                <input type="text" name="firstname" value="<?= $firstname ?>" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">Last Name</label>
                <input type="text" name="lastname" value="<?= $lastname ?>" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">Course</label>
                <input type="text" name="course" value="<?= $course ?>" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">Year Level</label>
                <input type="text" name="yearlvl" value="<?= $yearlvl ?>" class="form-control">
              </div>
              <div class="col-12">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="<?= $email ?>" class="form-control">
              </div>
              <div class="col-12">
                <label class="form-label">Address</label>
                <input type="text" name="addrs" value="<?= $addrs ?>" class="form-control">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
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
        if (navLinks) navLinks.classList.toggle('open');
        if (sidebar) sidebar.classList.toggle('open');
      });
    }

    function openModal() {
      const modal = new bootstrap.Modal(document.getElementById('editModal'));
      modal.show();
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
