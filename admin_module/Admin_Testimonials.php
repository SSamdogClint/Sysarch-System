<?php
// admin_module/Admin_Testimonials.php

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
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0 && $action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $message = 'Testimonial deleted successfully.';
        }
    }
}

$search = trim($_GET['search'] ?? '');
$ratingFilter = (int)($_GET['rating'] ?? 0);
if ($ratingFilter < 1 || $ratingFilter > 5) {
    $ratingFilter = 0;
}

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = '(s.studentid LIKE ? OR s.firstname LIKE ? OR s.lastname LIKE ? OR t.message LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssss';
}

if ($ratingFilter > 0) {
    $where[] = 't.rating = ?';
    $params[] = $ratingFilter;
    $types .= 'i';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "
    SELECT
      t.id,
      t.rating,
      t.message,
      t.created_at,
      s.studentid,
      CONCAT(s.lastname, ', ', s.firstname, ' ', s.middlename) AS student_name,
      s.course,
      s.yearlvl
    FROM testimonials t
    INNER JOIN students s ON s.id = t.student_id
    $whereSql
    ORDER BY t.created_at DESC
";
$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $testimonials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $testimonials = [];
}

$totalTestimonials = 0;
$averageRating = 0;
$uniqueStudents = 0;
$thisMonth = 0;
$result = $conn->query("SELECT COUNT(*) AS total, AVG(rating) AS avg_rating, COUNT(DISTINCT student_id) AS students, SUM(YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())) AS month_count FROM testimonials");
if ($result && $row = $result->fetch_assoc()) {
    $totalTestimonials = (int)$row['total'];
    $averageRating = $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 1) : 0;
    $uniqueStudents = (int)$row['students'];
    $thisMonth = (int)$row['month_count'];
}

function shortText($text, $limit = 120) {
    $text = trim((string)$text);
    return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UC – Testimonials</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/admin_table_tools.css">
</head>
<body class="admin-dashboard-page">
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
      <span style="font-size:13px; color:#6b7280; padding: 0 8px;"><?= $admin_name ?></span>
      <div class="nav-divider"></div>
      <a class="nav-link" href="../controllers/auth/logout.php">Log out</a>
    </div>
  </nav>

  <div class="admin-layout">
    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
      <div class="container-fluid py-4">
        <div class="row align-items-stretch g-3 mb-4">
          <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-circle bg-primary text-white p-3 d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-chat-square-heart fs-3"></i>
                  </div>
                  <div>
                    <h1 class="h3 mb-1">Student Testimonials</h1>
                    <p class="text-muted mb-0">View and manage testimonials submitted by students.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body p-4">
                <p class="text-muted mb-1">Approval</p>
                <h2 class="h5 mb-0">Not required</h2>
                <small class="text-muted">Student testimonials are saved immediately.</small>
              </div>
            </div>
          </div>
        </div>

        <?php if ($message): ?>
          <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
          <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Total</div><div class="h3 mb-0"><?= $totalTestimonials ?></div></div></div></div>
          <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Average Rating</div><div class="h3 mb-0"><?= $averageRating ?></div></div></div></div>
          <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Students</div><div class="h3 mb-0"><?= $uniqueStudents ?></div></div></div></div>
          <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">This Month</div><div class="h3 mb-0"><?= $thisMonth ?></div></div></div></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white border-0 pt-4 px-4">
            <h2 class="h5 mb-0"><i class="bi bi-funnel text-primary me-2"></i>Filter Testimonials</h2>
          </div>
          <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
              <div class="col-lg-7">
                <label class="form-label fw-semibold">Search</label>
                <input class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search student ID, name, or message">
              </div>
              <div class="col-lg-3">
                <label class="form-label fw-semibold">Rating</label>
                <select class="form-select" name="rating">
                  <option value="0" <?= $ratingFilter === 0 ? 'selected' : '' ?>>All Ratings</option>
                  <?php for ($r = 5; $r >= 1; $r--): ?>
                    <option value="<?= $r ?>" <?= $ratingFilter === $r ? 'selected' : '' ?>><?= $r ?> star<?= $r > 1 ? 's' : '' ?></option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="col-lg-2">
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search me-1"></i>Filter</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white border-0 pt-4 px-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
              <div>
                <h2 class="h5 mb-0">Testimonials List</h2>
                <small class="text-muted">Showing testimonials based on your filter.</small>
              </div>
              <span class="badge text-bg-primary rounded-pill"><?= count($testimonials) ?> result(s)</span>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 js-admin-table">
                <thead class="table-light">
                  <tr><th class="ps-4">#</th><th>Student</th><th>Rating</th><th>Message</th><th>Date</th><th class="pe-4">Action</th></tr>
                </thead>
                <tbody>
                  <?php if (!$testimonials): ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">No testimonials found.</td></tr>
                  <?php endif; ?>
                  <?php foreach ($testimonials as $index => $item): ?>
                    <tr>
                      <td class="ps-4"><?= $index + 1 ?></td>
                      <td>
                        <div class="fw-semibold"><?= htmlspecialchars($item['student_name']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($item['studentid']) ?> | <?= htmlspecialchars($item['course']) ?> <?= htmlspecialchars($item['yearlvl']) ?></div>
                      </td>
                      <td>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                          <i class="bi <?= $i <= (int)$item['rating'] ? 'bi-star-fill text-warning' : 'bi-star text-muted' ?>"></i>
                        <?php endfor; ?>
                      </td>
                      <td><?= nl2br(htmlspecialchars(shortText($item['message'], 140))) ?></td>
                      <td><?= htmlspecialchars($item['created_at']) ?></td>
                      <td class="pe-4">
                        <form method="POST" data-confirm-message="Delete this testimonial?" data-confirm-title="Delete Confirmation" data-confirm-type="danger" data-confirm-ok="Yes, Delete">
                          <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                          <input type="hidden" name="action" value="delete">
                          <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                        </form>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const toggler = document.getElementById('navToggler');
    const navLinks = document.getElementById('navLinks');
    const sidebar = document.getElementById('sidebar');
    if (toggler) {
      toggler.addEventListener('click', () => {
        navLinks.classList.toggle('open');
        sidebar.classList.toggle('open');
      });
    }
  </script>
  <script src="../assets/js/admin_table_tools.js"></script>
</body>
</html>
