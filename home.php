<?php
// home.php
session_start();
require_once __DIR__ . '/config/db_config.php';
$loggedIn  = !empty($_SESSION['logged_in']);
$firstname = htmlspecialchars($_SESSION['firstname'] ?? '');
$lastname  = htmlspecialchars($_SESSION['lastname']  ?? '');


$totalStudents = 0;
$totalSeats = 56 * 6;
$totalLabs = 6;
$homeTestimonials = [];
$homeLeaderboard = [];

$result = $conn->query("SELECT COUNT(*) AS total FROM students");
if ($result && $row = $result->fetch_assoc()) {
    $totalStudents = (int)$row['total'];
}

$result = $conn->query("SELECT COUNT(DISTINCT lab) AS total FROM lab_computers");
if ($result && $row = $result->fetch_assoc()) {
    $totalLabs = max(6, (int)$row['total']);
}

$result = $conn->query("SELECT COUNT(*) AS total FROM lab_computers");
if ($result && $row = $result->fetch_assoc()) {
    $totalSeats = max(0, (int)$row['total']);
}

$result = $conn->query("
    SELECT t.rating, t.message, t.created_at, s.firstname, s.lastname, s.course
    FROM testimonials t
    INNER JOIN students s ON s.id = t.student_id
    WHERE t.status = 'approved'
    ORDER BY t.created_at DESC
    LIMIT 3
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $homeTestimonials[] = $row;
    }
}

$result = $conn->query("
    SELECT
        s.studentid,
        s.firstname,
        s.lastname,
        s.course,
        COALESCE(s.reward_points_earned, s.reward_points, 0) AS earned_points,
        COALESCE(s.task_completed, 0) AS task_points,
        COUNT(sr.id) AS total_sessions,
        COALESCE(SUM(sr.duration_minutes), 0) AS total_minutes,
        (
          COALESCE(s.reward_points_earned, s.reward_points, 0)
          + COALESCE(s.task_completed, 0)
          + (COUNT(sr.id) * 2)
        ) AS home_score
    FROM students s
    LEFT JOIN sitin_records sr ON sr.student_id = s.id AND sr.status IN ('done', 'completed')
    GROUP BY s.id
    ORDER BY home_score DESC, earned_points DESC, total_sessions DESC
    LIMIT 3
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $homeLeaderboard[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UC – Home</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .hero {
      min-height: calc(100vh - 60px);
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fff;
      position: relative;
      overflow: hidden;
    }

    .hero-grid-bg {
      position: absolute;
      inset: 0;
      pointer-events: none;
      opacity: 0.035;
      background-image:
        repeating-linear-gradient(0deg,  #1d4ed8 0, #1d4ed8 1px, transparent 0, transparent 64px),
        repeating-linear-gradient(90deg, #1d4ed8 0, #1d4ed8 1px, transparent 0, transparent 64px);
    }

    .hero-blob {
      position: absolute;
      width: 520px; height: 520px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(29,78,216,0.07) 0%, transparent 70%);
      top: -100px; right: -100px;
      pointer-events: none;
    }

    .hero-inner {
      position: relative;
      text-align: center;
      max-width: 600px;
      padding: 2rem 1.5rem;
      animation: fadeUp 0.6s ease both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .hero-badge {
      display: inline-block;
      padding: 5px 16px;
      border-radius: 99px;
      background: #eff6ff;
      color: #1d4ed8;
      font-size: 12px;
      font-weight: 600;
      border: 1px solid #bfdbfe;
      margin-bottom: 1.5rem;
    }

    .hero h1 {
      font-size: 40px;
      font-weight: 800;
      line-height: 1.12;
      margin-bottom: 1.1rem;
      letter-spacing: -1px;
      color: #111827;
    }

    .hero h1 span { color: #1d4ed8; }

    .hero p {
      font-size: 15px;
      color: #6b7280;
      line-height: 1.75;
      margin-bottom: 2rem;
      max-width: 480px;
      margin-left: auto;
      margin-right: auto;
    }

    .hero-btns {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
      margin-bottom: 3rem;
    }

    .btn-hero-primary {
      padding: 13px 28px;
      background: #1d4ed8; color: #fff;
      border: none; border-radius: 10px;
      font-size: 14px; font-weight: 600;
      font-family: 'Poppins', sans-serif;
      cursor: pointer; transition: all 0.15s;
      text-decoration: none; display: inline-block;
    }
    .btn-hero-primary:hover { background: #1e40af; color: #fff; transform: translateY(-1px); }

    .btn-hero-outline {
      padding: 13px 28px;
      background: transparent; color: #374151;
      border: 1px solid #d1d5db; border-radius: 10px;
      font-size: 14px; font-weight: 500;
      font-family: 'Poppins', sans-serif;
      cursor: pointer; transition: all 0.15s;
      text-decoration: none; display: inline-block;
    }
    .btn-hero-outline:hover { border-color: #9ca3af; background: #f9fafb; color: #111827; }

    /* Logged-in welcome banner */
    .welcome-banner {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      border-radius: 99px;
      padding: 7px 18px;
      font-size: 13px;
      font-weight: 500;
      color: #1e40af;
      margin-bottom: 1.5rem;
    }

    .welcome-avatar {
      width: 26px; height: 26px;
      border-radius: 50%;
      background: #1d4ed8;
      color: #fff;
      font-size: 11px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
    }

    .stats-row {
      display: flex;
      gap: 2.5rem;
      justify-content: center;
      flex-wrap: wrap;
      padding-top: 2rem;
      border-top: 1px solid #f3f4f6;
    }

    .stat-num { font-size: 26px; font-weight: 800; color: #1d4ed8; }
    .stat-lbl { font-size: 12px; color: #9ca3af; margin-top: 2px; }

    .features { background: #f5f6fa; padding: 5rem 1.5rem; }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      max-width: 900px;
      margin: 0 auto;
    }

    .feature-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 1.75rem 1.5rem;
    }

    .feature-icon {
      width: 44px; height: 44px;
      background: #eff6ff; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; margin-bottom: 1rem;
    }

    .feature-card h4 { font-size: 15px; font-weight: 600; margin-bottom: 6px; }
    .feature-card p  { font-size: 13px; color: #6b7280; line-height: 1.6; }

    .section-label {
      text-align: center; font-size: 12px; font-weight: 700;
      color: #1d4ed8; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 0.75rem;
    }

    .section-title {
      text-align: center; font-size: 28px; font-weight: 700;
      margin-bottom: 2.5rem; letter-spacing: -0.5px;
    }

    /* Logged-in nav user chip */
    .nav-user {
      display: flex; align-items: center; gap: 8px;
      font-size: 13px; color: #374151; padding: 0 4px;
    }

    .nav-user-avatar {
      width: 28px; height: 28px; border-radius: 50%;
      background: #1d4ed8; color: #fff;
      font-size: 11px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
    }



    .public-showcase {
      background: #ffffff;
      padding: 5rem 1.5rem;
    }

    .showcase-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
      max-width: 1000px;
      margin: 0 auto;
    }

    .showcase-card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 18px;
      padding: 1.5rem;
      box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
    }

    .showcase-top {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
    }

    .showcase-avatar {
      width: 42px;
      height: 42px;
      border-radius: 999px;
      background: #1d4ed8;
      color: #ffffff;
      font-size: 13px;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 auto;
    }

    .showcase-name {
      font-size: 14px;
      font-weight: 800;
      color: #111827;
    }

    .showcase-sub {
      font-size: 11px;
      color: #6b7280;
      font-weight: 600;
    }

    .showcase-stars {
      color: #f59e0b;
      font-size: 13px;
      margin-bottom: 10px;
    }

    .showcase-text {
      color: #475569;
      font-size: 13px;
      line-height: 1.7;
      margin: 0;
    }

    .leader-rank {
      width: 34px;
      height: 34px;
      border-radius: 999px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fef3c7;
      color: #92400e;
      font-size: 12px;
      font-weight: 800;
      flex: 0 0 auto;
    }

    .leader-row {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 0;
      border-bottom: 1px solid #f1f5f9;
    }

    .leader-row:last-child {
      border-bottom: 0;
    }

    .leader-score {
      margin-left: auto;
      text-align: right;
      color: #1d4ed8;
      font-size: 13px;
      font-weight: 800;
    }

    @media (max-width: 600px) {
      .hero h1 { font-size: 28px; }
      .stats-row { gap: 1.5rem; }
    }
  </style>
</head>
<body>

  <!-- ═══ NAVBAR ═══ -->
  <nav class="uc-nav">
    <a class="nav-brand" href="home.php">
      <img src="assets/images/ccsmainlog_nobg.png" alt="UC CCS Logo" class="nav-logo">
      <div>
        <div class="nav-title">UC Sit-in System</div>
        <div class="nav-sub">Main Campus · CCS</div>
      </div>
    </a>

    <button class="nav-toggler" id="navToggler" aria-label="Toggle navigation">
      <span></span><span></span><span></span>
    </button>

    <div class="nav-links" id="navLinks">
      <a class="nav-link active" href="home.php">Home</a>
      <a class="nav-link" href="#">Community</a>
      <a class="nav-link" href="#">About</a>
      <div class="nav-divider"></div>

      <?php if ($loggedIn): ?>
        <div class="nav-user">
          <div class="nav-user-avatar"><?= strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1)) ?></div>
          <?= $firstname . ' ' . $lastname ?>
        </div>
        <a class="nav-link" href="controllers/auth/logout.php">Log out</a>
      <?php else: ?>
        <a class="nav-link" href="login_page.php">Sign in</a>
        <a class="nav-cta"  href="register_page.php">Sign up</a>
      <?php endif; ?>
    </div>
  </nav>

  <!-- ═══ HERO ═══ -->
  <section class="hero">
    <div class="hero-grid-bg"></div>
    <div class="hero-blob"></div>
    <div class="hero-inner">

      <?php if ($loggedIn): ?>
        <div class="welcome-banner">
          <div class="welcome-avatar"><?= strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1)) ?></div>
          Welcome back, <?= $firstname ?>!
        </div>
      <?php else: ?>
        <div class="hero-badge">University of Cebu — Main Campus</div>
      <?php endif; ?>

      <h1>College of <span>Computer Studies</span><br>Sit-in System</h1>
      <p>Monitor, manage, and track sit-in sessions for CCS students. Log in with your student account to check your credits and reserve a seat.</p>

      <div class="hero-btns">
        <?php if ($loggedIn): ?>
          <a href="student_module/student_dashboard.php" class="btn-hero-primary">Go to Dashboard</a>
          <a href="controllers/auth/logout.php"    class="btn-hero-outline">Log out</a>
        <?php else: ?>
          <a href="login_page.php"    class="btn-hero-primary">Sign in</a>
          <a href="register_page.php" class="btn-hero-outline">Create account</a>
        <?php endif; ?>
      </div>

      <div class="stats-row">
        <div class="stat">
          <div class="stat-num"><?= number_format($totalStudents) ?></div>
          <div class="stat-lbl">Registered students</div>
        </div>
        <div class="stat">
          <div class="stat-num"><?= number_format($totalSeats) ?></div>
          <div class="stat-lbl">Laboratory PCs</div>
        </div>
        <div class="stat">
          <div class="stat-num"><?= number_format($totalLabs) ?></div>
          <div class="stat-lbl">Labs monitored</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══ FEATURES ═══ -->
  <section class="features">
    <p class="section-label">What you can do</p>
    <h2 class="section-title">Everything in one place</h2>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">🪑</div>
        <h4>Sit-in credits</h4>
        <p>Track your remaining and used sit-in session credits in real time.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📅</div>
        <h4>Reservations</h4>
        <p>Reserve a lab seat ahead of time and avoid waiting in line.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📊</div>
        <h4>Session history</h4>
        <p>View a complete record of all your past sit-in sessions by lab and date.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">💬</div>
        <h4>Feedback</h4>
        <p>Submit feedback after your session to help improve CCS lab services.</p>
      </div>
    </div>
  </section>



  <!-- ═══ TESTIMONIALS ═══ -->
  <section class="public-showcase">
    <p class="section-label">Student testimonials</p>
    <h2 class="section-title">What students say</h2>

    <div class="showcase-grid">
      <?php if (!empty($homeTestimonials)): ?>
        <?php foreach ($homeTestimonials as $review): ?>
          <?php
            $reviewName = trim(($review['firstname'] ?? '') . ' ' . ($review['lastname'] ?? ''));
            $reviewInitials = strtoupper(substr($review['firstname'] ?? 'S', 0, 1) . substr($review['lastname'] ?? 'T', 0, 1));
            $rating = max(1, min(5, (int)($review['rating'] ?? 5)));
          ?>
          <div class="showcase-card">
            <div class="showcase-top">
              <div class="showcase-avatar"><?= htmlspecialchars($reviewInitials) ?></div>
              <div>
                <div class="showcase-name"><?= htmlspecialchars($reviewName ?: 'UC Student') ?></div>
                <div class="showcase-sub"><?= htmlspecialchars($review['course'] ?? 'Student') ?></div>
              </div>
            </div>

            <div class="showcase-stars">
              <?= str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) ?>
            </div>

            <p class="showcase-text">“<?= htmlspecialchars($review['message']) ?>”</p>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="showcase-card">
          <p class="showcase-text">No approved testimonials yet. Approved student reviews will appear here.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ═══ LEADERBOARD TOP 3 ═══ -->
  <section class="features">
    <p class="section-label">Top 3 Leaderboard</p>
    <h2 class="section-title">Current top students</h2>

    <div class="showcase-grid" style="max-width:760px;">
      <div class="showcase-card" style="grid-column:1/-1;">
        <?php if (!empty($homeLeaderboard)): ?>
          <?php foreach ($homeLeaderboard as $index => $leader): ?>
            <div class="leader-row">
              <div class="leader-rank">#<?= $index + 1 ?></div>
              <div>
                <div class="showcase-name">
                  <?= htmlspecialchars(trim(($leader['lastname'] ?? '') . ', ' . ($leader['firstname'] ?? ''))) ?>
                </div>
                <div class="showcase-sub">
                  <?= htmlspecialchars($leader['studentid'] ?? '') ?> · <?= htmlspecialchars($leader['course'] ?? '') ?> · <?= (int)($leader['total_sessions'] ?? 0) ?> sessions
                </div>
              </div>
              <div class="leader-score">
                <?= number_format((float)($leader['earned_points'] ?? 0), 1) ?> pts
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="showcase-text">No leaderboard data yet. Students with reward points and completed sit-ins will appear here.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('navToggler').addEventListener('click', () => {
      document.getElementById('navLinks').classList.toggle('open');
    });
  </script>
</body>
</html>