<?php
// admin_module/Admin_SitinRecords.php
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

// Fetch active sit-in records
$result_active = $conn->query(
    "SELECT id, studentid, fullname, purpose, lab, session_at_sitin, login_time, status
     FROM sitin_records WHERE status = 'active' ORDER BY login_time DESC"
);
$active_records = $result_active->fetch_all(MYSQLI_ASSOC);

// Fetch done sit-in records
$result_done = $conn->query(
    "SELECT id, studentid, fullname, purpose, lab, session_at_sitin, login_time, status
     FROM sitin_records WHERE status = 'done' ORDER BY login_time DESC"
);
$done_records = $result_done->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>UC – Sit-in Records</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  
</head>
<body class="admin-sitin-records-page">

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
      <a class="nav-link" href="../controllers/auth/logout.php?type=admin">Log out</a>
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
      <a class="sidebar-link active" href="Admin_SitinRecords.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Sit-in Records
      </a>
      <a class="sidebar-link" href="Admin_Reservation.php">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Reservation
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
    </aside>

    <!-- MAIN -->
    <main class="admin-main">
      <div class="page-card">

        <!-- Header -->
        <div class="page-card-header">
          <h4>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Sit-in Records
          </h4>
        </div>

        <!-- Tabs -->
        <div class="tab-bar">
          <button class="tab-btn active" onclick="switchTab('current', this)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Current Sit-in
            <span class="tab-badge green" id="activeCount"><?= count($active_records) ?></span>
          </button>
          <button class="tab-btn" onclick="switchTab('history', this)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Sit-in History
            <span class="tab-badge gray" id="doneCount"><?= count($done_records) ?></span>
          </button>
        </div>

        <!-- ══ TAB: Current Sit-in ══ -->
        <div class="tab-panel active" id="tab-current">
          <div class="toolbar">
            <div class="toolbar-left">
              <label style="font-size:13px;color:#6b7280;">Show</label>
              <select class="entries-select" id="activeEntries" onchange="updateActive()">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
              <label style="font-size:13px;color:#6b7280;">entries</label>
            </div>
            <input type="text" class="search-input" id="activeSearch"
              placeholder="🔍 Search current sit-ins..."
              oninput="updateActive()">
          </div>

          <div style="overflow-x:auto;">
            <table class="records-table">
              <thead>
                <tr>
                  <th>Sit-in ID</th>
                  <th>Student ID</th>
                  <th>Full Name</th>
                  <th>Purpose</th>
                  <th>Lab</th>
                  <th>Session</th>
                  <th>Date & Time</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="activeBody"></tbody>
            </table>
          </div>

          <div class="pagination-bar">
            <span id="activeInfo">Showing 0 entries</span>
            <div class="pagination-btns" id="activePagination"></div>
          </div>
        </div>

        <!-- ══ TAB: Sit-in History ══ -->
        <div class="tab-panel" id="tab-history">
          <div class="toolbar">
            <div class="toolbar-left">
              <label style="font-size:13px;color:#6b7280;">Show</label>
              <select class="entries-select" id="historyEntries" onchange="updateHistory()">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
              <label style="font-size:13px;color:#6b7280;">entries</label>
            </div>
            <input type="text" class="search-input" id="historySearch"
              placeholder="🔍 Search history..."
              oninput="updateHistory()">
          </div>

          <div style="overflow-x:auto;">
            <table class="records-table">
              <thead>
                <tr>
                  <th>Sit-in ID</th>
                  <th>Student ID</th>
                  <th>Full Name</th>
                  <th>Purpose</th>
                  <th>Lab</th>
                  <th>Session</th>
                  <th>Date & Time</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="historyBody"></tbody>
            </table>
          </div>

          <div class="pagination-bar">
            <span id="historyInfo">Showing 0 entries</span>
            <div class="pagination-btns" id="historyPagination"></div>
          </div>
        </div>

      </div>
    </main>
  </div>

  <!-- ═══ DEACTIVATE CONFIRM MODAL ═══ -->
  <div id="deactivateModal" style="
    display:none; position:fixed; inset:0; z-index:999;
    background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div class="confirm-modal-box">
      <div style="background:#d97706;color:#fff;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:14px;font-weight:600;">⏹ Deactivate Sit-in</span>
        <button onclick="closeDeactivateModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;">✕</button>
      </div>
      <div style="padding:20px 24px;">
        <p style="font-size:13px;color:#374151;margin-bottom:6px;">Are you sure you want to deactivate:</p>
        <p id="deactivateInfo" style="font-size:14px;font-weight:700;color:#111827;margin-bottom:16px;"></p>
        <p style="font-size:12px;color:#6b7280;margin-bottom:20px;">This will mark the sit-in as done and move it to history.</p>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button onclick="closeDeactivateModal()" style="
            padding:9px 20px;border:1px solid #d1d5db;border-radius:8px;
            background:#fff;font-size:13px;font-weight:500;
            font-family:'Poppins',sans-serif;cursor:pointer;color:#374151;">
            Cancel
          </button>
          <button onclick="confirmDeactivate()" style="
            padding:9px 20px;background:#d97706;color:#fff;border:none;
            border-radius:8px;font-size:13px;font-weight:600;
            font-family:'Poppins',sans-serif;cursor:pointer;">
            Yes, Deactivate
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ DELETE CONFIRM MODAL ═══ -->
  <div id="deleteModal" style="
    display:none; position:fixed; inset:0; z-index:999;
    background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div class="confirm-modal-box">
      <div style="background:#dc2626;color:#fff;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:14px;font-weight:600;">⚠️ Delete Record</span>
        <button onclick="closeDeleteModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;">✕</button>
      </div>
      <div style="padding:20px 24px;">
        <p style="font-size:13px;color:#374151;margin-bottom:6px;">Are you sure you want to delete:</p>
        <p id="deleteRecordInfo" style="font-size:14px;font-weight:700;color:#111827;margin-bottom:16px;"></p>
        <p style="font-size:12px;color:#6b7280;margin-bottom:20px;">This action cannot be undone.</p>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button onclick="closeDeleteModal()" style="
            padding:9px 20px;border:1px solid #d1d5db;border-radius:8px;
            background:#fff;font-size:13px;font-weight:500;
            font-family:'Poppins',sans-serif;cursor:pointer;color:#374151;">
            Cancel
          </button>
          <button onclick="deleteRecord()" style="
            padding:9px 20px;background:#dc2626;color:#fff;border:none;
            border-radius:8px;font-size:13px;font-weight:600;
            font-family:'Poppins',sans-serif;cursor:pointer;">
            Yes, Delete
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- TOAST -->
  <div id="toast"></div>

  <!-- ═══ SEARCH STUDENT MODAL ═══ -->
  <div id="searchModal" style="display:none; position:fixed; inset:0; z-index:999; background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:100%; max-width:520px; margin:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.2); font-family:'Poppins',sans-serif; overflow:hidden;">
      <div style="background:#1d3a6e; color:#fff; padding:16px 24px; display:flex; align-items:center; justify-content:space-between;">
        <span style="font-size:14px; font-weight:600;">Search Student</span>
        <button onclick="closeSearchModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">✕</button>
      </div>
      <div style="padding:20px 24px; border-bottom:1px solid #f3f4f6;">
        <div style="display:flex; gap:10px;">
          <input type="text" id="searchInput" placeholder="Enter Student ID (e.g. 2024-00001)" style="flex:1; border:1px solid #e5e7eb; border-radius:8px; padding:10px 14px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; color:#111827;" onkeydown="if(event.key==='Enter') searchStudent()">
          <button onclick="searchStudent()" style="padding:10px 20px; background:#1d3a6e; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer;">Search</button>
        </div>
        <div id="searchError" style="display:none; margin-top:8px; font-size:12px; color:#b91c1c;"></div>
      </div>
      <div id="searchResult" style="display:none; padding:20px 24px;">
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid #f3f4f6;">
          <div id="resultAvatar" style="width:56px; height:56px; border-radius:50%; background:#1d3a6e; color:#fff; font-size:20px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:3px solid #e5e7eb;"></div>
          <div>
            <div id="resultName" style="font-size:15px; font-weight:700; color:#111827;"></div>
            <div id="resultId" style="font-size:12px; color:#6b7280; margin-top:2px;"></div>
          </div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;"><div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">COURSE</div><div id="resultCourse" style="font-size:13px; font-weight:600; color:#111827;"></div></div>
          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;"><div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">YEAR LEVEL</div><div id="resultYear" style="font-size:13px; font-weight:600; color:#111827;"></div></div>
          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;"><div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">EMAIL</div><div id="resultEmail" style="font-size:13px; font-weight:600; color:#111827; word-break:break-all;"></div></div>
          <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;"><div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">ADDRESS</div><div id="resultAddr" style="font-size:13px; font-weight:600; color:#111827;"></div></div>
          <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px; grid-column:span 2;">
            <div style="font-size:11px; font-weight:600; color:#1d4ed8; margin-bottom:3px;">SESSION CREDITS REMAINING</div>
            <div style="display:flex; align-items:center; gap:8px;">
              <div id="resultCredits" style="font-size:22px; font-weight:800; color:#1d3a6e;"></div>
              <div style="font-size:12px; color:#6b7280;">/ 30 credits this semester</div>
            </div>
          </div>
        </div>
      </div>
      <div id="searchLoading" style="display:none; padding:32px; text-align:center; font-size:13px; color:#6b7280;">Searching...</div>
    </div>
  </div>

  <!-- ═══ SIT-IN MODAL ═══ -->
  <div id="sitinModal" style="display:none; position:fixed; inset:0; z-index:999; background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:100%; max-width:520px; margin:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.2); font-family:'Poppins',sans-serif; overflow:hidden;">
      <div style="background:#1d3a6e; color:#fff; padding:16px 24px; display:flex; align-items:center; justify-content:space-between;">
        <span style="font-size:14px; font-weight:600;">Register Sit-in</span>
        <button onclick="closeSitinModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">✕</button>
      </div>
      <div style="padding:24px;">
        <div style="margin-bottom:14px;">
          <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Student ID</label>
          <div style="display:flex;gap:8px;">
            <input type="text" id="sitinIdInput" placeholder="e.g. 2024-00001" style="flex:1;border:1px solid #e5e7eb;border-radius:8px;padding:9px 13px;font-size:13px;font-family:'Poppins',sans-serif;outline:none;color:#111827;" oninput="resetSitinLookup()">
            <button onclick="lookupStudent()" style="padding:9px 16px;background:#1d3a6e;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;">Look up</button>
          </div>
          <div id="sitinLookupError" style="display:none;margin-top:6px;font-size:12px;color:#b91c1c;"></div>
        </div>
        <div id="sitinStudentInfo" style="display:none; background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; padding:12px 16px; margin-bottom:14px;">
          <div style="display:flex;align-items:center;gap:12px;">
            <div id="sitinAvatar" style="width:44px;height:44px;border-radius:50%;background:#1d3a6e;color:#fff;font-size:15px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"></div>
            <div>
              <div id="sitinStudentName" style="font-size:13px;font-weight:700;color:#111827;"></div>
              <div id="sitinStudentCourse" style="font-size:12px;color:#6b7280;margin-top:1px;"></div>
              <div style="display:flex;align-items:center;gap:6px;margin-top:4px;">
                <span style="font-size:11px;font-weight:600;color:#374151;">Remaining Sessions:</span>
                <span id="sitinSessionBadge" style="background:#1d3a6e;color:#fff;font-size:11px;font-weight:700;padding:2px 10px;border-radius:99px;"></span>
              </div>
            </div>
          </div>
        </div>
        <div id="sitinFormFields" style="display:none;">
          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Purpose</label>
            <input type="text" id="sitinPurpose" placeholder="e.g. C++ Programming" style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 13px;font-size:13px;font-family:'Poppins',sans-serif;outline:none;color:#111827;">
          </div>
          <div style="margin-bottom:20px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Lab Number</label>
            <select id="sitinLab" style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 13px;font-size:13px;font-family:'Poppins',sans-serif;outline:none;color:#111827;background:#fff;">
              <option value="">-- Select Lab --</option>
              <option value="Lab 1">Lab 524</option>
              <option value="Lab 2">Lab 526</option>
              <option value="Lab 3">Lab 528</option>
              <option value="Lab 4">Lab 530</option>
              <option value="Lab 5">Lab 542</option>
              <option value="Lab 6">Lab 544</option>
            </select>
          </div>
          <div id="sitinSubmitError" style="display:none;margin-bottom:10px;font-size:12px;color:#b91c1c;"></div>
          <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="closeSitinModal()" style="padding:9px 20px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:13px;font-weight:500;font-family:'Poppins',sans-serif;cursor:pointer;color:#374151;">Cancel</button>
            <button onclick="submitSitin()" style="padding:9px 24px;background:#059669;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;">Confirm Sit-in</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="sitinToast" style="display:none; position:fixed; bottom:24px; right:24px; z-index:9999; background:#059669; color:#fff; padding:12px 20px; border-radius:10px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; box-shadow:0 4px 16px rgba(0,0,0,0.15);">
    ✅ Sit-in registered successfully!
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('navToggler').addEventListener('click', () => {
      document.getElementById('navLinks').classList.toggle('open');
    });

    window.addEventListener('pageshow', function(e) {
      if (e.persisted) {
        fetch('../controllers/auth/check_session.php?type=admin', { cache: 'no-store' })
          .then(res => res.json())
          .then(data => { if (!data.logged_in) window.location.replace('../home.php'); });
      }
    });

    // ── Tab switching ──
    function switchTab(tab, btn) {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('tab-' + tab).classList.add('active');
    }

    // ── Data ──
    const activeRecords  = <?= json_encode($active_records) ?>;
    const historyRecords = <?= json_encode($done_records) ?>;

    let activePage   = 1;
    let historyPage  = 1;
    let filteredActive  = [...activeRecords];
    let filteredHistory = [...historyRecords];

    function formatDate(dt) {
      return new Date(dt).toLocaleString('en-PH', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
      });
    }

    // ── Active tab ──
    function updateActive() {
      const q = document.getElementById('activeSearch').value.toLowerCase();
      filteredActive = activeRecords.filter(r =>
        r.id.toString().includes(q) ||
        r.studentid.toLowerCase().includes(q) ||
        r.fullname.toLowerCase().includes(q) ||
        r.purpose.toLowerCase().includes(q) ||
        r.lab.toLowerCase().includes(q)
      );
      activePage = 1;
      renderActive();
    }

    function renderActive() {
      const perPage = parseInt(document.getElementById('activeEntries').value);
      const total   = filteredActive.length;
      const start   = (activePage - 1) * perPage;
      const end     = Math.min(start + perPage, total);
      const data    = filteredActive.slice(start, end);
      const tbody   = document.getElementById('activeBody');

      tbody.innerHTML = '';
      if (data.length === 0) {
        tbody.innerHTML = '<tr class="empty-row"><td colspan="9">No active sit-ins found.</td></tr>';
      } else {
        data.forEach(r => {
          tbody.innerHTML += `
            <tr>
              <td style="color:#9ca3af;font-weight:600;">#${r.id}</td>
              <td style="font-weight:600;color:#1d3a6e;">${r.studentid}</td>
              <td>${r.fullname}</td>
              <td>${r.purpose}</td>
              <td>${r.lab}</td>
              <td><span class="badge-session">${r.session_at_sitin}</span></td>
              <td style="font-size:12px;color:#6b7280;">${formatDate(r.login_time)}</td>
              <td><span class="badge-status active">active</span></td>
              <td>
                <button class="btn-deactivate" onclick="openDeactivateModal(${r.id}, '${r.fullname}')">
                  ⏹ Deactivate
                </button>
              </td>
            </tr>`;
        });
      }

      renderPagination('active', total, perPage);
      document.getElementById('activeInfo').textContent =
        total === 0 ? 'Showing 0 entries' : `Showing ${start + 1} to ${end} of ${total} entries`;
    }

    // ── History tab ──
    function updateHistory() {
      const q = document.getElementById('historySearch').value.toLowerCase();
      filteredHistory = historyRecords.filter(r =>
        r.id.toString().includes(q) ||
        r.studentid.toLowerCase().includes(q) ||
        r.fullname.toLowerCase().includes(q) ||
        r.purpose.toLowerCase().includes(q) ||
        r.lab.toLowerCase().includes(q)
      );
      historyPage = 1;
      renderHistory();
    }

    function renderHistory() {
      const perPage = parseInt(document.getElementById('historyEntries').value);
      const total   = filteredHistory.length;
      const start   = (historyPage - 1) * perPage;
      const end     = Math.min(start + perPage, total);
      const data    = filteredHistory.slice(start, end);
      const tbody   = document.getElementById('historyBody');

      tbody.innerHTML = '';
      if (data.length === 0) {
        tbody.innerHTML = '<tr class="empty-row"><td colspan="9">No history records found.</td></tr>';
      } else {
        data.forEach(r => {
          tbody.innerHTML += `
            <tr>
              <td style="color:#9ca3af;font-weight:600;">#${r.id}</td>
              <td style="font-weight:600;color:#1d3a6e;">${r.studentid}</td>
              <td>${r.fullname}</td>
              <td>${r.purpose}</td>
              <td>${r.lab}</td>
              <td><span class="badge-session">${r.session_at_sitin}</span></td>
              <td style="font-size:12px;color:#6b7280;">${formatDate(r.login_time)}</td>
              <td><span class="badge-status done">done</span></td>
              <td>
                <button class="btn-delete" onclick="openDeleteModal(${r.id}, '${r.fullname}')">
                  🗑️ Delete
                </button>
              </td>
            </tr>`;
        });
      }

      renderPagination('history', total, perPage);
      document.getElementById('historyInfo').textContent =
        total === 0 ? 'Showing 0 entries' : `Showing ${start + 1} to ${end} of ${total} entries`;
    }

    // ── Pagination ──
    function renderPagination(type, total, perPage) {
      const totalPages  = Math.ceil(total / perPage);
      const currentPage = type === 'active' ? activePage : historyPage;
      const container   = document.getElementById(type + 'Pagination');
      container.innerHTML = '';

      const prev = document.createElement('button');
      prev.className   = 'page-btn';
      prev.textContent = '← Prev';
      prev.disabled    = currentPage === 1;
      prev.onclick     = () => { type === 'active' ? activePage-- : historyPage--; type === 'active' ? renderActive() : renderHistory(); };
      container.appendChild(prev);

      for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('button');
        btn.className   = 'page-btn' + (i === currentPage ? ' active' : '');
        btn.textContent = i;
        btn.onclick     = ((p) => () => { type === 'active' ? activePage = p : historyPage = p; type === 'active' ? renderActive() : renderHistory(); })(i);
        container.appendChild(btn);
      }

      const next = document.createElement('button');
      next.className   = 'page-btn';
      next.textContent = 'Next →';
      next.disabled    = currentPage === totalPages || total === 0;
      next.onclick     = () => { type === 'active' ? activePage++ : historyPage++; type === 'active' ? renderActive() : renderHistory(); };
      container.appendChild(next);
    }

    // Initial render
    renderActive();
    renderHistory();

    // ── Deactivate ──
    let deactivateTargetId = null;

    function openDeactivateModal(id, name) {
      deactivateTargetId = id;
      document.getElementById('deactivateInfo').textContent = `Record #${id} — ${name}`;
      document.getElementById('deactivateModal').style.display = 'flex';
    }

    function closeDeactivateModal() {
      document.getElementById('deactivateModal').style.display = 'none';
      deactivateTargetId = null;
    }

    function confirmDeactivate() {
      if (!deactivateTargetId) return;

      const fd = new FormData();
      fd.append('sitin_id', deactivateTargetId);

      fetch('../controllers/sitin/deactivate_sitin.php', { 
        method: 'POST', 
        body: fd 
      })
        .then(res => res.json())
        .then(data => {
          closeDeactivateModal();

          if (data.success) {
            showToast('Sit-in deactivated. Time-out recorded.', '#d97706');
            setTimeout(() => location.reload(), 1200);
          } else {
            showToast(data.message || 'Failed to deactivate.', '#dc2626');
          }
        })
        .catch(() => showToast('Something went wrong.', '#dc2626'));
    }

    document.getElementById('deactivateModal').addEventListener('click', function(e) {
      if (e.target === this) closeDeactivateModal();
    });

    // ── Delete ──
    let deleteTargetId = null;

    function openDeleteModal(id, name) {
      deleteTargetId = id;
      document.getElementById('deleteRecordInfo').textContent = `Record #${id} — ${name}`;
      document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').style.display = 'none';
      deleteTargetId = null;
    }

    function deleteRecord() {
      if (!deleteTargetId) return;
      const fd = new FormData();
      fd.append('record_id', deleteTargetId);

      fetch('../controllers/sitin/delete_sitin.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
          closeDeleteModal();
          if (data.success) {
            showToast('Record deleted successfully.', '#059669');
            setTimeout(() => location.reload(), 1200);
          } else {
            showToast(data.message || 'Failed to delete.', '#dc2626');
          }
        })
        .catch(() => showToast('Something went wrong.', '#dc2626'));
    }

    document.getElementById('deleteModal').addEventListener('click', function(e) {
      if (e.target === this) closeDeleteModal();
    });

    // ── Toast ──
    function showToast(msg, color) {
      const t = document.getElementById('toast');
      t.textContent      = msg;
      t.style.background = color;
      t.style.display    = 'block';
      setTimeout(() => t.style.display = 'none', 3000);
    }

    // ── Search Modal ──
    function openSearchModal() { document.getElementById('searchModal').style.display = 'flex'; resetSearch(); }
    function closeSearchModal() { document.getElementById('searchModal').style.display = 'none'; resetSearch(); }
    function resetSearch() {
      document.getElementById('searchInput').value = '';
      document.getElementById('searchResult').style.display = 'none';
      document.getElementById('searchError').style.display = 'none';
      document.getElementById('searchLoading').style.display = 'none';
    }
    function searchStudent() {
      const id = document.getElementById('searchInput').value.trim();
      if (!id) { showSearchError('Please enter a Student ID.'); return; }
      document.getElementById('searchResult').style.display = 'none';
      document.getElementById('searchError').style.display = 'none';
      document.getElementById('searchLoading').style.display = 'block';
      fetch(`../controllers/student/search_student.php?studentid=${encodeURIComponent(id)}`)
        .then(res => res.json())
        .then(data => {
          document.getElementById('searchLoading').style.display = 'none';
          if (!data.found) { showSearchError('No student found with that ID.'); return; }
          const s = data.student;
          const yearLabels = { 1:'1st Year', 2:'2nd Year', 3:'3rd Year', 4:'4th Year' };
          document.getElementById('resultAvatar').textContent  = (s.firstname.charAt(0) + s.lastname.charAt(0)).toUpperCase();
          document.getElementById('resultName').textContent    = s.lastname + ', ' + s.firstname + ' ' + s.middlename;
          document.getElementById('resultId').textContent      = 'ID: ' + s.studentid;
          document.getElementById('resultCourse').textContent  = s.course;
          document.getElementById('resultYear').textContent    = yearLabels[s.yearlvl] || s.yearlvl;
          document.getElementById('resultEmail').textContent   = s.email;
          document.getElementById('resultAddr').textContent    = s.addrs;
          document.getElementById('resultCredits').textContent = s.session_credits;
          document.getElementById('searchResult').style.display = 'block';
        })
        .catch(() => { document.getElementById('searchLoading').style.display = 'none'; showSearchError('Something went wrong.'); });
    }
    function showSearchError(msg) { const el = document.getElementById('searchError'); el.textContent = msg; el.style.display = 'block'; }
    document.getElementById('searchModal').addEventListener('click', function(e) { if (e.target === this) closeSearchModal(); });

    // ── Sit-in Modal ──
    let currentStudent = null;
    function openSitinModal() { document.getElementById('sitinModal').style.display = 'flex'; resetSitinModal(); setTimeout(() => document.getElementById('sitinIdInput').focus(), 100); }
    function closeSitinModal() { document.getElementById('sitinModal').style.display = 'none'; resetSitinModal(); }
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
      if (!id) { showSitinError('sitinLookupError', 'Please enter a Student ID.'); return; }
      fetch(`../controllers/student/search_student.php?studentid=${encodeURIComponent(id)}`)
        .then(res => res.json())
        .then(data => {
          if (!data.found) { showSitinError('sitinLookupError', 'No student found with that ID.'); return; }
          const s = data.student;
          currentStudent = s;
          if (s.session_credits <= 0) { showSitinError('sitinLookupError', 'This student has no remaining session credits.'); return; }
          const yearLabels = {1:'1st Year', 2:'2nd Year', 3:'3rd Year', 4:'4th Year'};
          document.getElementById('sitinAvatar').textContent        = (s.firstname.charAt(0) + s.lastname.charAt(0)).toUpperCase();
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
      fetch('../controllers/sitin/register_sitin.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          if (!data.success) { showSitinError('sitinSubmitError', data.message || 'Failed to register sit-in.'); return; }
          closeSitinModal();
          document.getElementById('sitinToast').style.display = 'block';
          setTimeout(() => { document.getElementById('sitinToast').style.display = 'none'; location.reload(); }, 1500);
        })
        .catch(() => showSitinError('sitinSubmitError', 'Something went wrong. Try again.'));
    }
    function showSitinError(id, msg) { const el = document.getElementById(id); el.textContent = msg; el.style.display = 'block'; }
    document.getElementById('sitinModal').addEventListener('click', function(e) { if (e.target === this) closeSitinModal(); });
  </script>
</body>
</html>