<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ../login_page.php');
    exit;
}

require_once '../config/db_config.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator');

// Fetch all students
$result   = $conn->query('SELECT id, studentid, lastname, firstname, middlename, course, yearlvl, session_credits FROM students ORDER BY lastname ASC');
$students = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>UC – Student List</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body { background: #eef0f5; font-family: 'Poppins', sans-serif; }

    @media (max-width: 768px) {
      .sidebar { display: none; }
      .sidebar.open { display: block; width: 100%; position: fixed; top: 60px; left: 0; bottom: 0; z-index: 99; overflow-y: auto; }
    }

    .page-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.06);
      overflow: hidden;
    }

    .page-card-header {
      background: #1d3a6e;
      color: #fff;
      padding: 14px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .page-card-header h4 {
      font-size: 14px;
      font-weight: 700;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .page-card-header span {
      font-size: 12px;
      color: #93c5fd;
    }

    /* Table */
    .student-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }

    .student-table thead tr {
      background: #f8fafc;
      border-bottom: 2px solid #e5e7eb;
    }

    .student-table thead th {
      padding: 11px 16px;
      font-size: 11px;
      font-weight: 700;
      color: #6b7280;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      text-align: left;
      white-space: nowrap;
    }

    .student-table tbody tr {
      border-bottom: 1px solid #f3f4f6;
      transition: background 0.1s;
    }

    .student-table tbody tr:last-child { border-bottom: none; }
    .student-table tbody tr:hover { background: #f9fafb; }

    .student-table tbody td {
      padding: 11px 16px;
      color: #374151;
      vertical-align: middle;
    }

    .badge-credits {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 99px;
      font-size: 11px;
      font-weight: 700;
    }

    .badge-credits.high  { background: #dcfce7; color: #166534; }
    .badge-credits.mid   { background: #fef9c3; color: #854d0e; }
    .badge-credits.low   { background: #fee2e2; color: #991b1b; }

    .btn-edit {
      padding: 5px 12px;
      background: #eff6ff;
      color: #1d4ed8;
      border: 1px solid #bfdbfe;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      transition: all 0.13s;
    }

    .btn-edit:hover { background: #dbeafe; }

    .btn-delete {
      padding: 5px 12px;
      background: #fef2f2;
      color: #dc2626;
      border: 1px solid #fca5a5;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      transition: all 0.13s;
    }

    .btn-delete:hover { background: #fee2e2; }

    /* Bottom action bar */
    .action-bar {
      padding: 14px 20px;
      background: #f8fafc;
      border-top: 1px solid #e5e7eb;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
    }

    .btn-add {
      padding: 9px 20px;
      background: #1d3a6e;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: background 0.13s;
    }

    .btn-add:hover { background: #162d56; }

    .btn-reset {
      padding: 9px 20px;
      background: #fff;
      color: #d97706;
      border: 1px solid #fcd34d;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.13s;
    }

    .btn-reset:hover { background: #fffbeb; }

    /* Search bar */
    .search-bar {
      padding: 14px 20px;
      border-bottom: 1px solid #f3f4f6;
      background: #fff;
    }

    .search-input {
      width: 100%;
      max-width: 320px;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 8px 14px;
      font-size: 13px;
      font-family: 'Poppins', sans-serif;
      outline: none;
      color: #111827;
    }

    .search-input:focus { border-color: #1d4ed8; box-shadow: 0 0 0 3px rgba(29,78,216,0.08); }

    .empty-row td {
      text-align: center;
      padding: 2.5rem;
      color: #9ca3af;
      font-size: 13px;
    }

    /* Confirm delete modal */
    .confirm-modal-box {
      background: #fff;
      border-radius: 14px;
      width: 100%;
      max-width: 400px;
      margin: 1rem;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2);
      font-family: 'Poppins', sans-serif;
      overflow: hidden;
    }

    /* Toast */
    #toast {
      display: none;
      position: fixed;
      bottom: 24px; right: 24px;
      z-index: 9999;
      padding: 12px 20px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
      box-shadow: 0 4px 16px rgba(0,0,0,0.15);
      color: #fff;
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="uc-nav">
    <a class="nav-brand" href="admin_dashboard.php">
      <img src="../assets/images/ccsmainlog_nobg.png" alt="UC CCS Logo" class="nav-logo">
      <div>
        <div class="nav-title">UC Sit-in System</div>
        <div class="nav-sub">Admin Panel</div>
      </div>
    </a>
    <button class="nav-toggler" id="navToggler" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>
    <div class="nav-links" id="navLinks">
      <span style="font-size:13px;color:#6b7280;padding:0 8px;"><?= $admin_name ?></span>
      <div class="nav-divider"></div>
      <a class="nav-link" href="../logout.php?type=admin">Log out</a>
    </div>
  </nav>

  <div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-section" style="margin-top:0;">Main</div>
      <a class="sidebar-link" href="admin_dashboard.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>
      <a class="sidebar-link" href="#" onclick="openSearchModal(); return false;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Search
      </a>
      <a class="sidebar-link" href="Admin_StudentList.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Students
      </a>
      <a class="sidebar-link" href="#" onclick="openSitinModal(); return false;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2M8 7V5a2 2 0 0 0-4 0v2"/></svg>
        Sit-in
      </a>
      <a class="sidebar-link" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Sit-in Records
      </a>
      <hr class="sidebar-divider">
      <div class="sidebar-section">Reports</div>
      <a class="sidebar-link" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Sit-in Reports
      </a>
      <a class="sidebar-link" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Feedback Reports
      </a>
      <a class="sidebar-link" href="#">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Reservation
      </a>
    </aside>

    <!-- MAIN -->
    <main class="admin-main">
      <div class="page-card">

        <!-- Header -->
        <div class="page-card-header">
          <h4>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Student List
          </h4>
          <span><?= count($students) ?> students registered</span>
        </div>

        <!-- Search bar -->
        <div class="search-bar">
          <input type="text" class="search-input" id="searchBar"
            placeholder="🔍  Search by name, ID, or course..."
            oninput="filterTable()">
        </div>

        <!-- Table -->
        <div style="overflow-x:auto;">
          <table class="student-table" id="studentTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Full Name</th>
                <th>Course</th>
                <th>Year</th>
                <th>Session Credits</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="studentBody">
              <?php if (empty($students)): ?>
                <tr class="empty-row"><td colspan="7">No students registered yet.</td></tr>
              <?php else: ?>
                <?php foreach ($students as $i => $s): ?>
                  <?php
                    $fullname    = htmlspecialchars($s['lastname'] . ', ' . $s['firstname'] . ' ' . $s['middlename']);
                    $credits     = (int)$s['session_credits'];
                    $creditClass = $credits >= 20 ? 'high' : ($credits >= 10 ? 'mid' : 'low');
                    $yearLabels  = [1=>'1st',2=>'2nd',3=>'3rd',4=>'4th'];
                    $year        = $yearLabels[$s['yearlvl']] ?? $s['yearlvl'];
                  ?>
                  <tr>
                    <td style="color:#9ca3af;"><?= $i + 1 ?></td>
                    <td style="font-weight:600; color:#1d3a6e;"><?= htmlspecialchars($s['studentid']) ?></td>
                    <td><?= $fullname ?></td>
                    <td><?= htmlspecialchars($s['course']) ?></td>
                    <td><?= $year ?></td>
                    <td>
                      <span class="badge-credits <?= $creditClass ?>">
                        <?= $credits ?> / 30
                      </span>
                    </td>
                    <td>
                      <div style="display:flex; gap:6px;">
                        <button class="btn-edit" onclick="editStudent(<?= $s['id'] ?>)">
                          ✏️ Edit
                        </button>
                        <button class="btn-delete" onclick="confirmDelete(<?= $s['id'] ?>, '<?= addslashes($fullname) ?>')">
                          🗑️ Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Action bar -->
        <div class="action-bar">
          <button class="btn-add" onclick="alert('Add student — coming soon!')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Student
          </button>
          <button class="btn-reset" onclick="openResetConfirm()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.63"/></svg>
            Reset All Sessions
          </button>
        </div>

      </div>
    </main>
  </div>

  <!-- ═══ DELETE CONFIRM MODAL ═══ -->
  <div id="deleteModal" style="
    display:none; position:fixed; inset:0; z-index:999;
    background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div class="confirm-modal-box">
      <div style="background:#dc2626;color:#fff;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:14px;font-weight:600;">⚠️ Delete Student</span>
        <button onclick="closeDeleteModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;">✕</button>
      </div>
      <div style="padding:20px 24px;">
        <p style="font-size:13px;color:#374151;margin-bottom:6px;">Are you sure you want to delete:</p>
        <p id="deleteStudentName" style="font-size:14px;font-weight:700;color:#111827;margin-bottom:16px;"></p>
        <p style="font-size:12px;color:#6b7280;margin-bottom:20px;">This action cannot be undone. All sit-in records of this student will also be removed.</p>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button onclick="closeDeleteModal()" style="
            padding:9px 20px;border:1px solid #d1d5db;border-radius:8px;
            background:#fff;font-size:13px;font-weight:500;
            font-family:'Poppins',sans-serif;cursor:pointer;color:#374151;">
            Cancel
          </button>
          <button onclick="deleteStudent()" style="
            padding:9px 20px;background:#dc2626;color:#fff;border:none;
            border-radius:8px;font-size:13px;font-weight:600;
            font-family:'Poppins',sans-serif;cursor:pointer;">
            Yes, Delete
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ RESET CONFIRM MODAL ═══ -->
  <div id="resetModal" style="
    display:none; position:fixed; inset:0; z-index:999;
    background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div class="confirm-modal-box">
      <div style="background:#d97706;color:#fff;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:14px;font-weight:600;">🔄 Reset All Sessions</span>
        <button onclick="closeResetModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;">✕</button>
      </div>
      <div style="padding:20px 24px;">
        <p style="font-size:13px;color:#374151;margin-bottom:16px;">This will reset all students' session credits back to <strong>30</strong>. Use this at the start of every new semester.</p>
        <p style="font-size:12px;color:#6b7280;margin-bottom:20px;">Are you sure you want to continue?</p>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button onclick="closeResetModal()" style="
            padding:9px 20px;border:1px solid #d1d5db;border-radius:8px;
            background:#fff;font-size:13px;font-weight:500;
            font-family:'Poppins',sans-serif;cursor:pointer;color:#374151;">
            Cancel
          </button>
          <button onclick="resetAllSessions()" style="
            padding:9px 20px;background:#d97706;color:#fff;border:none;
            border-radius:8px;font-size:13px;font-weight:600;
            font-family:'Poppins',sans-serif;cursor:pointer;">
            Yes, Reset All
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- TOAST -->
  <div id="toast"></div>

  <!-- ═══ SEARCH STUDENT MODAL ═══ -->
  <div id="searchModal" style="
    display:none; position:fixed; inset:0; z-index:999;
    background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">

    <div style="
      background:#fff; border-radius:16px; width:100%; max-width:520px;
      margin:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.2);
      font-family:'Poppins',sans-serif; overflow:hidden;">

      <!-- Header -->
      <div style="
        background:#1d3a6e; color:#fff; padding:16px 24px;
        display:flex; align-items:center; justify-content:space-between;">
        <span style="font-size:14px; font-weight:600;">
          <svg style="margin-right:6px; vertical-align:-3px;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          Search Student
        </span>
        <button onclick="closeSearchModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">✕</button>
      </div>

      <!-- Search input -->
      <div style="padding:20px 24px; border-bottom:1px solid #f3f4f6;">
        <div style="display:flex; gap:10px;">
          <input type="text" id="searchInput" placeholder="Enter Student ID (e.g. 2024-00001)"
            style="flex:1; border:1px solid #e5e7eb; border-radius:8px; padding:10px 14px;
                  font-size:13px; font-family:'Poppins',sans-serif; outline:none; color:#111827;"
            onkeydown="if(event.key==='Enter') searchStudent()">
          <button onclick="searchStudent()" style="
            padding:10px 20px; background:#1d3a6e; color:#fff; border:none;
            border-radius:8px; font-size:13px; font-weight:600;
            font-family:'Poppins',sans-serif; cursor:pointer; white-space:nowrap;">
            Search
          </button>
        </div>
        <div id="searchError" style="display:none; margin-top:8px; font-size:12px; color:#b91c1c;"></div>
      </div>

      <!-- Result -->
      <div id="searchResult" style="display:none; padding:20px 24px;">

        <!-- Avatar + name -->
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid #f3f4f6;">
          <div id="resultAvatar" style="
            width:56px; height:56px; border-radius:50%;
            background:#1d3a6e; color:#fff;
            font-size:20px; font-weight:700;
            display:flex; align-items:center; justify-content:center;
            flex-shrink:0; border:3px solid #e5e7eb;">
          </div>
          <div>
            <div id="resultName" style="font-size:15px; font-weight:700; color:#111827;"></div>
            <div id="resultId" style="font-size:12px; color:#6b7280; margin-top:2px;"></div>
          </div>
        </div>

        <!-- Info rows -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">COURSE</div>
            <div id="resultCourse" style="font-size:13px; font-weight:600; color:#111827;"></div>
          </div>
          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">YEAR LEVEL</div>
            <div id="resultYear" style="font-size:13px; font-weight:600; color:#111827;"></div>
          </div>
          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">EMAIL</div>
            <div id="resultEmail" style="font-size:13px; font-weight:600; color:#111827; word-break:break-all;"></div>
          </div>
          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">ADDRESS</div>
            <div id="resultAddr" style="font-size:13px; font-weight:600; color:#111827;"></div>
          </div>
          <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px; grid-column: span 2;">
          <div style="font-size:11px; font-weight:600; color:#1d4ed8; margin-bottom:3px;">SESSION CREDITS REMAINING</div>
          <div style="display:flex; align-items:center; gap:8px;">
            <div id="resultCredits" style="font-size:22px; font-weight:800; color:#1d3a6e;"></div>
            <div style="font-size:12px; color:#6b7280;">/ 30 credits this semester</div>
          </div>
        </div>
        </div>

      </div>

      <!-- Loading -->
      <div id="searchLoading" style="display:none; padding:32px; text-align:center; font-size:13px; color:#6b7280;">
        Searching...
      </div>

    </div>
  </div>
  <!-- ═══ SIT-IN MODAL ═══ -->
  <div id="sitinModal" style="
    display:none; position:fixed; inset:0; z-index:999;
    background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">

    <div style="
      background:#fff; border-radius:16px; width:100%; max-width:520px;
      margin:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.2);
      font-family:'Poppins',sans-serif; overflow:hidden;">

      <!-- Header -->
      <div style="
        background:#1d3a6e; color:#fff; padding:16px 24px;
        display:flex; align-items:center; justify-content:space-between;">
        <span style="font-size:14px; font-weight:600;">
          <svg style="margin-right:6px;vertical-align:-3px;" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2M8 7V5a2 2 0 0 0-4 0v2"/></svg>
          Register Sit-in
        </span>
        <button onclick="closeSitinModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">✕</button>
      </div>

      <div style="padding:24px;">

        <!-- Step 1: Lookup student -->
        <div style="margin-bottom:14px;">
          <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Student ID</label>
          <div style="display:flex;gap:8px;">
            <input type="text" id="sitinIdInput" placeholder="e.g. 2024-00001"
              style="flex:1;border:1px solid #e5e7eb;border-radius:8px;padding:9px 13px;
                    font-size:13px;font-family:'Poppins',sans-serif;outline:none;color:#111827;"
              oninput="resetSitinLookup()">
            <button onclick="lookupStudent()" style="
              padding:9px 16px;background:#1d3a6e;color:#fff;border:none;
              border-radius:8px;font-size:13px;font-weight:600;
              font-family:'Poppins',sans-serif;cursor:pointer;white-space:nowrap;">
              Look up
            </button>
          </div>
          <div id="sitinLookupError" style="display:none;margin-top:6px;font-size:12px;color:#b91c1c;"></div>
        </div>

        <!-- Student info preview -->
        <div id="sitinStudentInfo" style="
          display:none; background:#f0f9ff; border:1px solid #bae6fd;
          border-radius:10px; padding:12px 16px; margin-bottom:14px;">
          <div style="display:flex;align-items:center;gap:12px;">
            <div id="sitinAvatar" style="
              width:44px;height:44px;border-radius:50%;
              background:#1d3a6e;color:#fff;font-size:15px;font-weight:700;
              display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            </div>
            <div>
              <div id="sitinStudentName" style="font-size:13px;font-weight:700;color:#111827;"></div>
              <div id="sitinStudentCourse" style="font-size:12px;color:#6b7280;margin-top:1px;"></div>
              <div style="display:flex;align-items:center;gap:6px;margin-top:4px;">
                <span style="font-size:11px;font-weight:600;color:#374151;">Remaining Sessions:</span>
                <span id="sitinSessionBadge" style="
                  background:#1d3a6e;color:#fff;font-size:11px;font-weight:700;
                  padding:2px 10px;border-radius:99px;"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Sit-in form fields (shown after lookup) -->
        <div id="sitinFormFields" style="display:none;">

          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Purpose</label>
            <input type="text" id="sitinPurpose" placeholder="e.g. C++ Programming, Web Development"
              style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 13px;
                    font-size:13px;font-family:'Poppins',sans-serif;outline:none;color:#111827;">
          </div>

          <div style="margin-bottom:20px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Lab Number</label>
            <select id="sitinLab" style="
              width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 13px;
              font-size:13px;font-family:'Poppins',sans-serif;outline:none;
              color:#111827;background:#fff;">
              <option value="">-- Select Lab --</option>
              <option value="Lab 1">Lab 1</option>
              <option value="Lab 2">Lab 2</option>
              <option value="Lab 3">Lab 3</option>
              <option value="Lab 4">Lab 4</option>
              <option value="Lab 5">Lab 5</option>
              <option value="Lab 6">Lab 6</option>
            </select>
          </div>

          <div id="sitinSubmitError" style="display:none;margin-bottom:10px;font-size:12px;color:#b91c1c;"></div>

          <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="closeSitinModal()" style="
              padding:9px 20px;border:1px solid #d1d5db;border-radius:8px;
              background:#fff;font-size:13px;font-weight:500;
              font-family:'Poppins',sans-serif;cursor:pointer;color:#374151;">
              Cancel
            </button>
            <button onclick="submitSitin()" style="
              padding:9px 24px;background:#059669;color:#fff;border:none;
              border-radius:8px;font-size:13px;font-weight:600;
              font-family:'Poppins',sans-serif;cursor:pointer;">
              Confirm Sit-in
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- ═══ SUCCESS TOAST ═══ -->
  <div id="sitinToast" style="
    display:none; position:fixed; bottom:24px; right:24px; z-index:9999;
    background:#059669; color:#fff; padding:12px 20px; border-radius:10px;
    font-size:13px; font-weight:600; font-family:'Poppins',sans-serif;
    box-shadow:0 4px 16px rgba(0,0,0,0.15);">
    ✅ Sit-in registered successfully!
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Back button protection
    window.addEventListener('pageshow', function(e) {
      if (e.persisted) {
        fetch('../includes/check_session.php?type=admin', { cache: 'no-store' })
          .then(res => res.json())
          .then(data => {
            if (!data.logged_in) window.location.replace('../home.php');
          });
      }
    });
    
    document.getElementById('navToggler').addEventListener('click', () => {
      document.getElementById('navLinks').classList.toggle('open');
    });

    // ── Table search filter ──
    function filterTable() {
      const q = document.getElementById('searchBar').value.toLowerCase();
      document.querySelectorAll('#studentBody tr:not(.empty-row)').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    }

    // ── Edit ──
    function editStudent(id) {
      alert('Edit student ID ' + id + ' — coming soon!');
    }

    // ── Delete ──
    let deleteTargetId = null;

    function confirmDelete(id, name) {
      deleteTargetId = id;
      document.getElementById('deleteStudentName').textContent = name;
      document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').style.display = 'none';
      deleteTargetId = null;
    }

    function deleteStudent() {
      if (!deleteTargetId) return;
      const fd = new FormData();
      fd.append('student_id', deleteTargetId);

      fetch('/Sysarch-System/includes/delete_student.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
          closeDeleteModal();
          if (data.success) {
            showToast('Student deleted successfully.', '#059669');
            setTimeout(() => location.reload(), 1200);
          } else {
            showToast(data.message || 'Failed to delete.', '#dc2626');
          }
        })
        .catch(() => showToast('Something went wrong.', '#dc2626'));
    }

    // ── Reset sessions ──
    function openResetConfirm() {
      document.getElementById('resetModal').style.display = 'flex';
    }

    function closeResetModal() {
      document.getElementById('resetModal').style.display = 'none';
    }

    function resetAllSessions() {
      fetch('/Sysarch-System/includes/reset_sessions.php', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
          closeResetModal();
          if (data.success) {
            showToast('All sessions reset to 30!', '#d97706');
            setTimeout(() => location.reload(), 1200);
          } else {
            showToast(data.message || 'Failed to reset.', '#dc2626');
          }
        })
        .catch(() => showToast('Something went wrong.', '#dc2626'));
    }

    // ── Toast ──
    function showToast(msg, color) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.style.background = color;
      t.style.display = 'block';
      setTimeout(() => t.style.display = 'none', 3000);
    }

    // Close modals on outside click
    document.getElementById('deleteModal').addEventListener('click', function(e) {
      if (e.target === this) closeDeleteModal();
    });
    document.getElementById('resetModal').addEventListener('click', function(e) {
      if (e.target === this) closeResetModal();
    });

    //search modal
    function openSearchModal() {
      document.getElementById('searchModal').style.display = 'flex';
      document.getElementById('searchInput').focus();
      resetSearch();
    }

    function closeSearchModal() {
      document.getElementById('searchModal').style.display = 'none';
      resetSearch();
    }

    function resetSearch() {
      document.getElementById('searchInput').value = '';
      document.getElementById('searchResult').style.display = 'none';
      document.getElementById('searchError').style.display = 'none';
      document.getElementById('searchLoading').style.display = 'none';
    }

    function searchStudent() {
      const id = document.getElementById('searchInput').value.trim();
      if (!id) {
        showSearchError('Please enter a Student ID.');
        return;
      }

      document.getElementById('searchResult').style.display = 'none';
      document.getElementById('searchError').style.display = 'none';
      document.getElementById('searchLoading').style.display = 'block';

      fetch(`../includes/search_student.php?studentid=${encodeURIComponent(id)}`)
        .then(res => res.json())
        .then(data => {
          document.getElementById('searchLoading').style.display = 'none';
          if (!data.found) {
            showSearchError('No student found with that ID.');
            return;
          }
          const s = data.student;
          const initials = (s.firstname.charAt(0) + s.lastname.charAt(0)).toUpperCase();
          const yearLabels = { 1:'1st Year', 2:'2nd Year', 3:'3rd Year', 4:'4th Year' };

          document.getElementById('resultAvatar').textContent = initials;
          document.getElementById('resultName').textContent   = s.lastname + ', ' + s.firstname + ' ' + s.middlename;
          document.getElementById('resultId').textContent     = 'ID: ' + s.studentid;
          document.getElementById('resultCourse').textContent = s.course;
          document.getElementById('resultYear').textContent   = yearLabels[s.yearlvl] || s.yearlvl;
          document.getElementById('resultEmail').textContent  = s.email;
          document.getElementById('resultAddr').textContent   = s.addrs;
          document.getElementById('resultCredits').textContent = s.session_credits;
          document.getElementById('searchResult').style.display = 'block';
        })
        .catch(() => {
          document.getElementById('searchLoading').style.display = 'none';
          showSearchError('Something went wrong. Please try again.');
        });
    }

    function showSearchError(msg) {
      const el = document.getElementById('searchError');
      el.textContent = msg;
      el.style.display = 'block';
    }

    // Close when clicking outside
    document.getElementById('searchModal').addEventListener('click', function(e) {
      if (e.target === this) closeSearchModal();
    });

    // Sitin Modal functionalities
    let currentStudent = null;

    function openSitinModal() {
      document.getElementById('sitinModal').style.display = 'flex';
      resetSitinModal();
      setTimeout(() => document.getElementById('sitinIdInput').focus(), 100);
    }

    function closeSitinModal() {
      document.getElementById('sitinModal').style.display = 'none';
      resetSitinModal();
    }

    function resetSitinModal() {
      currentStudent = null;
      document.getElementById('sitinIdInput').value = '';
      document.getElementById('sitinPurpose').value = '';
      document.getElementById('sitinLab').value = '';
      document.getElementById('sitinStudentInfo').style.display = 'none';
      document.getElementById('sitinFormFields').style.display = 'none';
      document.getElementById('sitinLookupError').style.display = 'none';
      document.getElementById('sitinSubmitError').style.display = 'none';
    }

    function resetSitinLookup() {
      currentStudent = null;
      document.getElementById('sitinStudentInfo').style.display = 'none';
      document.getElementById('sitinFormFields').style.display = 'none';
      document.getElementById('sitinLookupError').style.display = 'none';
    }

    function lookupStudent() {
      const id = document.getElementById('sitinIdInput').value.trim();
      if (!id) {
        showSitinError('sitinLookupError', 'Please enter a Student ID.');
        return;
      }

      fetch(`../includes/search_student.php?studentid=${encodeURIComponent(id)}`)
        .then(res => res.json())
        .then(data => {
          if (!data.found) {
            showSitinError('sitinLookupError', 'No student found with that ID.');
            return;
          }

          const s = data.student;
          currentStudent = s;

          if (s.session_credits <= 0) {
            showSitinError('sitinLookupError', 'This student has no remaining session credits.');
            return;
          }

          const initials = (s.firstname.charAt(0) + s.lastname.charAt(0)).toUpperCase();
          const yearLabels = {1:'1st Year', 2:'2nd Year', 3:'3rd Year', 4:'4th Year'};

          document.getElementById('sitinAvatar').textContent        = initials;
          document.getElementById('sitinStudentName').textContent   = s.lastname + ', ' + s.firstname + ' ' + s.middlename;
          document.getElementById('sitinStudentCourse').textContent = s.course + ' — ' + (yearLabels[s.yearlvl] || s.yearlvl);
          document.getElementById('sitinSessionBadge').textContent  = s.session_credits;

          document.getElementById('sitinStudentInfo').style.display = 'block';
          document.getElementById('sitinFormFields').style.display  = 'block';
          document.getElementById('sitinLookupError').style.display = 'none';
        })
        .catch(() => showSitinError('sitinLookupError', 'Something went wrong. Try again.'));
    }

    function submitSitin() {
      const purpose = document.getElementById('sitinPurpose').value.trim();
      const lab     = document.getElementById('sitinLab').value;

      if (!purpose) { showSitinError('sitinSubmitError', 'Please enter the purpose.'); return; }
      if (!lab)     { showSitinError('sitinSubmitError', 'Please select a lab.'); return; }

      const formData = new FormData();
      formData.append('studentid', currentStudent.studentid);
      formData.append('purpose',   purpose);
      formData.append('lab',       lab);

      fetch('../includes/register_sitin.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          if (!data.success) {
            showSitinError('sitinSubmitError', data.message || 'Failed to register sit-in.');
            return;
          }
          closeSitinModal();
          showToast();
        })
        .catch(() => showSitinError('sitinSubmitError', 'Something went wrong. Try again.'));
    }

    function showSitinError(id, msg) {
      const el = document.getElementById(id);
      el.textContent = msg;
      el.style.display = 'block';
    }

    function showToast() {
      const toast = document.getElementById('sitinToast');
      toast.style.display = 'block';
      setTimeout(() => toast.style.display = 'none', 3000);
    }

    // Close modal when clicking outside
    document.getElementById('sitinModal').addEventListener('click', function(e) {
      if (e.target === this) closeSitinModal();
    });
  </script>
</body>
</html>