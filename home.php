<?php
session_start();
$loggedIn  = !empty($_SESSION['logged_in']);
$firstname = htmlspecialchars($_SESSION['firstname'] ?? '');
$lastname  = htmlspecialchars($_SESSION['lastname']  ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UC – Home</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="css/style.css">
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
      <img src="images/ccsmainlog_nobg.png" alt="UC CCS Logo" class="nav-logo">
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
        <a class="nav-link" href="logout.php">Log out</a>
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
          <a href="dashboard.php" class="btn-hero-primary">Go to Dashboard</a>
          <a href="logout.php"    class="btn-hero-outline">Log out</a>
        <?php else: ?>
          <a href="login_page.php"    class="btn-hero-primary">Sign in</a>
          <a href="register_page.php" class="btn-hero-outline">Create account</a>
        <?php endif; ?>
      </div>

      <div class="stats-row">
        <div class="stat">
          <div class="stat-num">1,200+</div>
          <div class="stat-lbl">Registered students</div>
        </div>
        <div class="stat">
          <div class="stat-num">48</div>
          <div class="stat-lbl">Available seats</div>
        </div>
        <div class="stat">
          <div class="stat-num">6</div>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('navToggler').addEventListener('click', () => {
      document.getElementById('navLinks').classList.toggle('open');
    });
  </script>
</body>
</html>