<?php
session_start();
$old = $_SESSION['reg_old'] ?? [];
unset($_SESSION['reg_old']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UC – Register</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .auth-right { align-items: flex-start; overflow-y: auto; padding: 2rem; }
    .auth-card  { max-width: 520px; margin-top: 1rem; margin-bottom: 1rem; }

    .step-bar   { display:flex; align-items:center; gap:0; margin-bottom:2rem; }
    .step-item  { display:flex; align-items:center; gap:8px; flex:1; }
    .step-circle {
      width:28px; height:28px; border-radius:50%; border:2px solid #d1d5db;
      background:#fff; display:flex; align-items:center; justify-content:center;
      font-size:12px; font-weight:700; color:#9ca3af; flex-shrink:0; transition:all 0.2s;
    }
    .step-circle.active { border-color:#1d4ed8; background:#1d4ed8; color:#fff; }
    .step-circle.done   { border-color:#059669; background:#059669; color:#fff; }
    .step-label         { font-size:11px; font-weight:600; color:#9ca3af; }
    .step-label.active  { color:#1d4ed8; }
    .step-label.done    { color:#059669; }
    .step-line          { flex:1; height:2px; background:#e5e7eb; margin:0 6px; }
    .step-line.done     { background:#059669; }
    .step-panel         { display:none; }
    .step-panel.active  { display:block; }
    .step-nav           { display:flex; gap:10px; margin-top:1.25rem; }
    .btn-next, .btn-back {
      flex:1; padding:11px; border-radius:9px; font-size:14px; font-weight:600;
      font-family:'Poppins',sans-serif; cursor:pointer; border:none; transition:all 0.15s;
    }
    .btn-next { background:#1d4ed8; color:#fff; }
    .btn-next:hover { background:#1e40af; }
    .btn-back { background:transparent; color:#374151; border:1px solid #d1d5db; }
    .btn-back:hover { background:#f3f4f6; }
  </style>
</head>
<body>

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
      <a class="nav-link" href="home.php">Home</a>
      <a class="nav-link" href="#">Community</a>
      <a class="nav-link" href="#">About</a>
      <div class="nav-divider"></div>
      <a class="nav-link" href="login_page.php">Sign in</a>
    </div>
  </nav>

  <div class="auth-page">

    <div class="auth-left">
      <svg class="auth-left-deco" viewBox="0 0 400 600" xmlns="http://www.w3.org/2000/svg">
        <circle cx="350" cy="80"  r="130" fill="rgba(255,255,255,0.06)"/>
        <circle cx="40"  cy="500" r="170" fill="rgba(255,255,255,0.04)"/>
        <circle cx="200" cy="300" r="220" fill="rgba(255,255,255,0.025)"/>
      </svg>
      <img src="images/uclogo_nobg.png" alt="UC Logo" class="brand-mark">
      <h2>Join the CCS<br>Sit-in System</h2>
      <p>Create your student account to track credits, reserve seats, and manage your lab sessions.</p>
    </div>

    <div class="auth-right">
      <div class="auth-card">
        <h3>Create account</h3>
        <p class="subtitle">Fill in your student details to get started.</p>

        <?php if (!empty($_SESSION['reg_errors'])): ?>
          <div style="background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c;border-radius:9px;padding:10px 14px;font-size:13px;margin-bottom:1rem;">
            <strong>Please fix the following:</strong>
            <ul style="margin:6px 0 0;padding-left:18px;">
              <?php foreach ($_SESSION['reg_errors'] as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; unset($_SESSION['reg_errors']); ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- Step indicator -->
        <div class="step-bar">
          <div class="step-item">
            <div class="step-circle active" id="circ-1">1</div>
            <span class="step-label active" id="lbl-1">Student info</span>
          </div>
          <div class="step-line" id="line-1"></div>
          <div class="step-item">
            <div class="step-circle" id="circ-2">2</div>
            <span class="step-label" id="lbl-2">Account</span>
          </div>
          <div class="step-line" id="line-2"></div>
          <div class="step-item">
            <div class="step-circle" id="circ-3">3</div>
            <span class="step-label" id="lbl-3">Confirm</span>
          </div>
        </div>

        <form id="registerForm" action="register_handler.php" method="post" novalidate>

          <!-- Step 1: Student info -->
          <div class="step-panel active" id="step-1">
            <div class="field">
              <label for="studentid">Student ID</label>
              <input type="text" id="studentid" name="studentid" placeholder="e.g. 2024-00001" required
                     value="<?= htmlspecialchars($old['studentid'] ?? '') ?>">
            </div>
            <div class="field-row">
              <div class="field">
                <label for="lastname">Last name</label>
                <input type="text" id="lastname" name="lastname" placeholder="Dela Cruz" required
                       value="<?= htmlspecialchars($old['lastname'] ?? '') ?>">
              </div>
              <div class="field">
                <label for="firstname">First name</label>
                <input type="text" id="firstname" name="firstname" placeholder="Juan" required
                       value="<?= htmlspecialchars($old['firstname'] ?? '') ?>">
              </div>
            </div>
            <div class="field">
              <label for="middlename">Middle name</label>
              <input type="text" id="middlename" name="middlename" placeholder="Santos"
                     value="<?= htmlspecialchars($old['middlename'] ?? '') ?>">
            </div>
            <div class="field-row">
              <div class="field">
                <label for="course">Course</label>
                <input type="text" id="course" name="course" placeholder="BSIT, BSCS…" required
                       value="<?= htmlspecialchars($old['course'] ?? '') ?>">
              </div>
              <div class="field">
                <label for="yearlvl">Year level</label>
                <select id="yearlvl" name="yearlvl">
                  <?php foreach ([1=>'1st Year',2=>'2nd Year',3=>'3rd Year',4=>'4th Year'] as $v=>$lbl): ?>
                    <option value="<?= $v ?>" <?= ($old['yearlvl'] ?? 1) == $v ? 'selected' : '' ?>><?= $lbl ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="field">
              <label for="addrs">Address</label>
              <input type="text" id="addrs" name="addrs" placeholder="Cebu City" required
                     value="<?= htmlspecialchars($old['addrs'] ?? '') ?>">
            </div>
            <div class="step-nav">
              <button type="button" class="btn-next" onclick="goStep(2)">Continue →</button>
            </div>
          </div>

          <!-- Step 2: Account credentials -->
          <div class="step-panel" id="step-2">
            <div class="field">
              <label for="email">Email address</label>
              <input type="email" id="email" name="email" placeholder="you@uc.edu.ph" required
                     value="<?= htmlspecialchars($old['email'] ?? '') ?>">
            </div>
            <div class="field">
              <label for="pswd">Password</label>
              <input type="password" id="pswd" name="pswd" placeholder="At least 8 characters" required>
            </div>
            <div class="field">
              <label for="conpswd">Confirm password</label>
              <input type="password" id="conpswd" name="conpswd" placeholder="Re-enter your password" required>
            </div>
            <div class="step-nav">
              <button type="button" class="btn-back" onclick="goStep(1)">← Back</button>
              <button type="button" class="btn-next" onclick="goStep(3)">Continue →</button>
            </div>
          </div>

          <!-- Step 3: Review & confirm -->
          <div class="step-panel" id="step-3">
            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:1rem 1.25rem;font-size:13px;color:#374151;margin-bottom:1.25rem;line-height:2;">
              <strong>Review your details before submitting.</strong><br>
              Make sure your Student ID, name, course, and email are correct — these will be used by the admin to verify your account.
            </div>
            <div class="check-row">
              <input type="checkbox" id="terms" name="terms" required>
              <label for="terms">I agree to the <a href="#" style="color:#1d4ed8;">Terms &amp; Conditions</a></label>
            </div>
            <div class="step-nav">
              <button type="button" class="btn-back" onclick="goStep(2)">← Back</button>
              <button type="submit" class="btn-next">Create account</button>
            </div>
          </div>

        </form>

        <div class="auth-footer">
          Already have an account? <a href="login_page.php">Sign in</a>
        </div>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('navToggler').addEventListener('click', () => {
      document.getElementById('navLinks').classList.toggle('open');
    });

    function goStep(n) {
      document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
      document.getElementById('step-' + n).classList.add('active');
      for (let i = 1; i <= 3; i++) {
        const circ = document.getElementById('circ-' + i);
        const lbl  = document.getElementById('lbl-' + i);
        if (i < n)      { circ.className = 'step-circle done';   lbl.className = 'step-label done'; }
        else if (i ===n){ circ.className = 'step-circle active'; lbl.className = 'step-label active'; }
        else            { circ.className = 'step-circle';        lbl.className = 'step-label'; }
      }
      for (let i = 1; i <= 2; i++) {
        document.getElementById('line-' + i).className = 'step-line' + (i < n ? ' done' : '');
      }
    }
  </script>
</body>
</html>