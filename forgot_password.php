<?php
// forgot_password.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UC – Forgot Password</title>
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
    <div class="nav-links">
      <a class="nav-link" href="login_page.php">Back to Sign in</a>
    </div>
  </nav>

  <div class="auth-page">
    <div class="auth-left">
      <img src="assets/images/uclogo_nobg.png" alt="UC Logo" class="brand-mark">
      <h2>Password recovery</h2>
      <p>Verify your Student ID and registered email, then create a new password.</p>
    </div>

    <div class="auth-right">
      <div class="auth-card">
        <h3>Reset password</h3>
        <p class="subtitle">Enter your registered details to continue.</p>

        <?php if (!empty($_SESSION['reset_errors'])): ?>
          <div style="background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c;border-radius:9px;padding:10px 14px;font-size:13px;margin-bottom:1rem;">
            <?php foreach ($_SESSION['reset_errors'] as $error): ?>
              <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
          </div>
          <?php unset($_SESSION['reset_errors']); ?>
        <?php endif; ?>

        <form action="controllers/auth/forgot_password_handler.php" method="post">
          <div class="field">
            <label for="studentid">Student ID</label>
            <input type="text" id="studentid" name="studentid" placeholder="e.g. 1001" required value="<?= htmlspecialchars($_SESSION['reset_old']['studentid'] ?? '') ?>">
          </div>

          <div class="field">
            <label for="email">Registered Email</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required value="<?= htmlspecialchars($_SESSION['reset_old']['email'] ?? '') ?>">
          </div>

          <div class="field">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" placeholder="At least 8 characters" required>
          </div>

          <div class="field">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
          </div>

          <button type="submit" class="submit-btn">Reset Password</button>
        </form>

        <div class="auth-footer">
          Remembered it? <a href="login_page.php">Sign in</a>
        </div>
      </div>
    </div>
  </div>
  <?php unset($_SESSION['reset_old']); ?>
</body>
</html>
