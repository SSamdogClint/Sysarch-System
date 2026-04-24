<?php
// login_page.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UC – Sign In</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

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
      <a class="nav-link" href="home.php">Home</a>
      <a class="nav-link" href="#">Community</a>
      <a class="nav-link" href="#">About</a>
      <div class="nav-divider"></div>
      <a class="nav-cta" href="register_page.php">Sign up</a>
    </div>
  </nav>

  <div class="auth-page">

    <div class="auth-left">
      <svg class="auth-left-deco" viewBox="0 0 400 600" xmlns="http://www.w3.org/2000/svg">
        <circle cx="350" cy="80"  r="130" fill="rgba(255,255,255,0.06)"/>
        <circle cx="40"  cy="500" r="170" fill="rgba(255,255,255,0.04)"/>
        <circle cx="200" cy="300" r="220" fill="rgba(255,255,255,0.025)"/>
      </svg>
      <img src="assets/images/uclogo_nobg.png" alt="UC Logo" class="brand-mark">
      <h2>Welcome back</h2>
      <p>Log in to access your sit-in credits, session history, and seat reservations.</p>
    </div>

    <div class="auth-right">
      <div class="auth-card">
        <h3>Sign in</h3>
        <p class="subtitle">Enter your student credentials to continue.</p>

        <?php if (!empty($_SESSION['login_error'])): ?>
          <div style="background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c;border-radius:9px;padding:10px 14px;font-size:13px;margin-bottom:1rem;">
            <?= htmlspecialchars($_SESSION['login_error']) ?>
          </div>
          <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['reg_success'])): ?>
          <div style="background:#f0fdf4;border:1px solid #86efac;color:#166534;border-radius:9px;padding:10px 14px;font-size:13px;margin-bottom:1rem;">
            <?= htmlspecialchars($_SESSION['reg_success']) ?>
          </div>
          <?php unset($_SESSION['reg_success']); ?>
        <?php endif; ?>

        <form action="controllers/auth/login_handler.php" method="post">
          <div class="field">
            <label for="studentid">ID Number</label>
            <input type="text" id="studentid" name="studentid" placeholder="e.g. 2024-00001" required
                  value="<?= htmlspecialchars($_SESSION['login_old']['studentid'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="pswd">Password</label>
            <input type="password" id="pswd" name="pswd" placeholder="••••••••" required>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
            <label class="check-row" style="margin:0;">
              <input type="checkbox" name="remember" id="remember"> Remember me
            </label>
            <a href="#" style="font-size:13px;color:#1d4ed8;text-decoration:none;">Forgot password?</a>
          </div>
          <button type="submit" class="submit-btn">Sign in</button>
        </form>

        <div class="auth-footer">
          New student? <a href="register_page.php">Create account</a>
        </div>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('navToggler').addEventListener('click', () => {
      document.getElementById('navLinks').classList.toggle('open');
    });
  </script>
</body>
</html>