<?php
// student_module/notifications.php

session_start();
require_once '../config/db_config.php';
require_once '../controllers/notifications/notification_helpers.php';
require_once '../controllers/reservation/reservation_helpers.php';

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
$initials   = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));

// Keep late-reservation cancellation notifications updated when this page is opened.
autoCancelLateReservations($conn);

$pageNotifications = [];

$announcementResult = $conn->query("\n    SELECT id, title, message, created_at\n    FROM announcements\n    ORDER BY created_at DESC\n    LIMIT 30\n");

if ($announcementResult) {
    while ($row = $announcementResult->fetch_assoc()) {
        $pageNotifications[] = [
            'type' => 'announcement',
            'label' => notificationLabel('announcement'),
            'title' => $row['title'] ?: 'New announcement',
            'message' => $row['message'] ?: '',
            'created_at' => $row['created_at'],
            'is_read' => 1,
            'url' => notificationTargetUrl('announcement')
        ];
    }
}

if ($student_id > 0 && notificationsTableExists($conn)) {
    $stmt = $conn->prepare("\n        SELECT id, type, title, message, is_read, created_at\n        FROM student_notifications\n        WHERE student_id = ?\n        ORDER BY created_at DESC\n        LIMIT 100\n    ");

    if ($stmt) {
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $type = $row['type'] ?: 'notification';
            $pageNotifications[] = [
                'type' => $type,
                'label' => notificationLabel($type),
                'title' => $row['title'] ?: 'New notification',
                'message' => $row['message'] ?: '',
                'created_at' => $row['created_at'],
                'is_read' => (int)($row['is_read'] ?? 0),
                'url' => notificationTargetUrl($type)
            ];
        }

        $stmt->close();
    }

    // Mark DB-backed notifications as read after listing them.
    $mark = $conn->prepare("UPDATE student_notifications SET is_read = 1 WHERE student_id = ?");
    if ($mark) {
        $mark->bind_param('i', $student_id);
        $mark->execute();
        $mark->close();
    }
}

usort($pageNotifications, function ($a, $b) {
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});

$pageNotifications = array_slice($pageNotifications, 0, 120);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>UC – Notifications</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/student.css">
  <style>
    .notification-page-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 18px;
      box-shadow: 0 12px 30px rgba(15,23,42,0.08);
      overflow: hidden;
    }

    .notification-list-item {
      display: block;
      color: inherit;
      text-decoration: none;
      border-bottom: 1px solid #eef2f7;
      padding: 16px 18px;
      transition: background 0.18s ease, transform 0.18s ease;
    }

    .notification-list-item:hover {
      color: inherit;
      text-decoration: none;
      background: #f8fafc;
    }

    .notification-list-item.unread {
      background: #eff6ff;
      border-left: 4px solid #2563eb;
    }

    .notification-label {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      font-weight: 700;
      padding: 4px 9px;
      border-radius: 999px;
      background: #dbeafe;
      color: #1d4ed8;
      margin-bottom: 8px;
    }

    .notification-title {
      font-weight: 800;
      color: #111827;
      margin-bottom: 4px;
    }

    .notification-message {
      font-size: 13px;
      color: #4b5563;
      margin-bottom: 8px;
      line-height: 1.5;
    }

    .notification-meta {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
      font-size: 12px;
      color: #6b7280;
    }

    body.dark-mode .notification-page-card,
    body.dark-mode .notification-list-item {
      background: #162033;
      border-color: #334155;
      color: #e5e7eb;
    }

    body.dark-mode .notification-list-item:hover {
      background: #1f2937;
    }

    body.dark-mode .notification-title {
      color: #f9fafb;
    }

    body.dark-mode .notification-message,
    body.dark-mode .notification-meta {
      color: #cbd5e1;
    }
  </style>
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
      <button type="button" class="dark-toggle" id="darkModeToggle" onclick="toggleDarkMode()" aria-label="Toggle dark mode" aria-pressed="false">
        <i class="bi bi-moon-stars"></i>
        <span>Dark</span>
      </button>
      <span class="student-nav-name"><?= $firstname . ' ' . $lastname ?></span>
      <div class="nav-divider"></div>
      <a class="nav-link" href="../controllers/auth/logout.php">Log out</a>
    </div>
  </nav>

  <div class="admin-layout">
    <?php require __DIR__ . '/../includes/student_sidebar.php'; ?>

    <main class="admin-main">
      <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
          <div>
            <h1 class="h3 fw-bold mb-1">Notifications</h1>
            <p class="text-muted mb-0">Open each notification to go to the exact page where it belongs.</p>
          </div>
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-weight:800;">
            <?= $initials ?>
          </div>
        </div>

        <div class="notification-page-card">
          <?php if (!empty($pageNotifications)): ?>
            <?php foreach ($pageNotifications as $notif): ?>
              <a class="notification-list-item <?= empty($notif['is_read']) ? 'unread' : '' ?>" href="<?= htmlspecialchars($notif['url'] ?? '#') ?>">
                <div class="notification-label">
                  <i class="bi bi-bell"></i>
                  <?= htmlspecialchars($notif['label'] ?? 'Notification') ?>
                </div>
                <div class="notification-title"><?= htmlspecialchars($notif['title'] ?? 'Notification') ?></div>
                <div class="notification-message"><?= htmlspecialchars($notif['message'] ?? '') ?></div>
                <div class="notification-meta">
                  <span><?= date('M d, Y h:i A', strtotime($notif['created_at'])) ?></span>
                  <span>Open related page <i class="bi bi-arrow-right-short"></i></span>
                </div>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center text-muted py-5">
              <i class="bi bi-bell fs-1 d-block mb-2"></i>
              No notifications yet.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const toggler = document.getElementById('navToggler');
    const navLinks = document.getElementById('navLinks');
    const sidebar = document.getElementById('sidebar');

    if (toggler) {
      toggler.addEventListener('click', () => {
        navLinks?.classList.toggle('open');
        sidebar?.classList.toggle('open');
      });
    }

    function toggleDarkMode() {
      document.body.classList.toggle('dark-mode');
      localStorage.setItem('uc_dark_mode', document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled');
    }

    // Keep old sidebar edit-profile link from throwing an error on this standalone page.
    function openModal() {
      window.location.href = 'student_dashboard.php';
    }

    localStorage.setItem('student_notif_last_seen_<?= (int)$student_id ?>', String(Date.now()));
  </script>
</body>
</html>
