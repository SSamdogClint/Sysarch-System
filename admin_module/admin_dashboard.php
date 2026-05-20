<?php
// admin_module/admin_dashboard.php

session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ../login_page.php');
    exit;
}

require_once '../controllers/dashboard/dashboard_stats.php';

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">

  <title>UC – Admin Dashboard</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  
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

    <button class="nav-toggler" id="navToggler" aria-label="Toggle menu">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <div class="nav-links" id="navLinks">
      <span style="font-size:13px; color:#6b7280; padding: 0 8px;">
        <?= $admin_name ?>
      </span>
      <div class="nav-divider"></div>
      <a class="nav-link" href="../controllers/auth/logout.php">Log out</a>
    </div>
  </nav>

  <div class="admin-layout">
    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
      <div class="dashboard-grid">
        <div>
          <div class="stat-counters">
            <div class="stat-counter-card">
              <div class="stat-counter-val"><?= $total_students ?></div>
              <div class="stat-counter-lbl">Students Registered</div>
            </div>

            <div class="stat-counter-card green">
              <div class="stat-counter-val"><?= $current_sitin ?></div>
              <div class="stat-counter-lbl">Currently Sit-in</div>
            </div>

            <div class="stat-counter-card amber">
              <div class="stat-counter-val"><?= $total_sitin ?></div>
              <div class="stat-counter-lbl">Total Sit-in</div>
            </div>
          </div>

          <div class="chart-card">
            <div class="chart-card-title">Sit-ins by Programming Language</div>
            <div class="chart-wrapper">
              <canvas id="languageChart"></canvas>
            </div>
          </div>
        </div>

        <div class="announce-card">
          <div class="announce-card-header">
            <h4>Announcements</h4>
            <span id="postCountLabel" style="font-size:12px; color:#9ca3af;">
              <?= $total_posts ?> posted
            </span>
          </div>

          <div class="compose-area">
            <span class="compose-label">New announcement</span>

            <input
              type="text"
              id="announceTitle"
              class="announce-textarea"
              placeholder="Announcement title (optional)"
              style="height: 46px; margin-bottom: 10px;"
            >

            <textarea
              class="announce-textarea"
              id="announceText"
              placeholder="Write something for students to see…"
            ></textarea>

            <div class="compose-footer">
              <button class="btn-post" onclick="postAnnouncement()">
                Post
              </button>
            </div>
          </div>

          <div class="announce-feed" id="announceList">
            <?php if (!empty($latest_announcements)): ?>
              <?php foreach ($latest_announcements as $announcement): ?>
                <div class="feed-item">
                  <div class="feed-top">
                    <div class="feed-avatar">CA</div>
                    <span class="feed-author"><?= htmlspecialchars($announcement['posted_by'] ?: 'CCS Admin') ?></span>
                    <span class="feed-date"><?= date('M d, Y', strtotime($announcement['created_at'])) ?></span>
                  </div>

                  <?php if (!empty($announcement['title'])): ?>
                    <div style="font-weight:700; margin-bottom:4px; color:#111827;">
                      <?= htmlspecialchars($announcement['title']) ?>
                    </div>
                  <?php endif; ?>

                  <div class="feed-body">
                    <?= nl2br(htmlspecialchars($announcement['message'])) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="feed-item">
                <div class="feed-top">
                  <div class="feed-avatar">CA</div>
                  <span class="feed-author">CCS Admin</span>
                  <span class="feed-date">Today</span>
                </div>
                <div class="feed-body empty-body">
                  No announcements yet. Posted announcements from the database will appear here.
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div id="searchModal" style="display:none; position:fixed; inset:0; z-index:999; background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:100%; max-width:520px; margin:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.2); font-family:'Poppins',sans-serif; overflow:hidden;">
      <div style="background:#1d3a6e; color:#fff; padding:16px 24px; display:flex; align-items:center; justify-content:space-between;">
        <span style="font-size:14px; font-weight:600;">Search Student</span>
        <button onclick="closeSearchModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">✕</button>
      </div>

      <div style="padding:20px 24px; border-bottom:1px solid #f3f4f6;">
        <div style="display:flex; gap:10px;">
          <input
            type="text"
            id="searchInput"
            placeholder="Enter Student ID (e.g. 2024-00001)"
            style="flex:1; border:1px solid #e5e7eb; border-radius:8px; padding:10px 14px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; color:#111827;"
            onkeydown="if(event.key==='Enter') searchStudent()"
          >

          <button onclick="searchStudent()" style="padding:10px 20px; background:#1d3a6e; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer; white-space:nowrap;">
            Search
          </button>
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

      <div id="searchLoading" style="display:none; padding:32px; text-align:center; font-size:13px; color:#6b7280;">
        Searching...
      </div>
    </div>
  </div>

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
            <input
              type="text"
              id="sitinIdInput"
              placeholder="e.g. 2024-00001"
              style="flex:1;border:1px solid #e5e7eb;border-radius:8px;padding:9px 13px; font-size:13px;font-family:'Poppins',sans-serif;outline:none;color:#111827;"
              oninput="resetSitinLookup()"
            >

            <button onclick="lookupStudent()" style="padding:9px 16px;background:#1d3a6e;color:#fff;border:none; border-radius:8px;font-size:13px;font-weight:600; font-family:'Poppins',sans-serif;cursor:pointer;white-space:nowrap;">
              Look up
            </button>
          </div>

          <div id="sitinLookupError" style="display:none;margin-top:6px;font-size:12px;color:#b91c1c;"></div>
        </div>

        <div id="sitinStudentInfo" style="display:none; background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; padding:12px 16px; margin-bottom:14px;">
          <div style="display:flex;align-items:center;gap:12px;">
            <div id="sitinAvatar" style="width:44px;height:44px;border-radius:50%; background:#1d3a6e;color:#fff;font-size:15px;font-weight:700; display:flex;align-items:center;justify-content:center;flex-shrink:0;"></div>

            <div>
              <div id="sitinStudentName" style="font-size:13px;font-weight:700;color:#111827;"></div>
              <div id="sitinStudentCourse" style="font-size:12px;color:#6b7280;margin-top:1px;"></div>

              <div style="display:flex;align-items:center;gap:6px;margin-top:4px;">
                <span style="font-size:11px;font-weight:600;color:#374151;">Remaining Sessions:</span>
                <span id="sitinSessionBadge" style="background:#1d3a6e;color:#fff;font-size:11px;font-weight:700; padding:2px 10px;border-radius:99px;"></span>
              </div>
            </div>
          </div>
        </div>

        <div id="sitinFormFields" style="display:none;">
          <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Purpose</label>
            <input
              type="text"
              id="sitinPurpose"
              placeholder="e.g. C++ Programming, Web Development"
              style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 13px; font-size:13px;font-family:'Poppins',sans-serif;outline:none;color:#111827;"
            >
          </div>

          <div style="margin-bottom:20px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Lab Number</label>
            <select
              id="sitinLab"
              style="width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:9px 13px; font-size:13px;font-family:'Poppins',sans-serif;outline:none; color:#111827;background:#fff;"
            >
              <option value="">-- Select Lab --</option>
              <option value="Lab 524">Lab 524</option>
              <option value="Lab 526">Lab 526</option>
              <option value="Lab 528">Lab 528</option>
              <option value="Lab 530">Lab 530</option>
              <option value="Lab 542">Lab 542</option>
              <option value="Lab 544">Lab 544</option>
            </select>
          </div>

          <div id="sitinSubmitError" style="display:none;margin-bottom:10px;font-size:12px;color:#b91c1c;"></div>

          <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="closeSitinModal()" style="padding:9px 20px;border:1px solid #d1d5db;border-radius:8px; background:#fff;font-size:13px;font-weight:500; font-family:'Poppins',sans-serif;cursor:pointer;color:#374151;">
              Cancel
            </button>

            <button onclick="submitSitin()" style="padding:9px 24px;background:#059669;color:#fff;border:none; border-radius:8px;font-size:13px;font-weight:600; font-family:'Poppins',sans-serif;cursor:pointer;">
              Confirm Sit-in
            </button>
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
          .then(data => {
            if (!data.logged_in) {
              window.location.replace('../home.php');
            }
          });
      }
    });

    const ctx = document.getElementById('languageChart').getContext('2d');

    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
          data: <?= json_encode($chart_values) ?>,
          backgroundColor: ['#3b82f6', '#f97316', '#ec4899', '#eab308', '#06b6d4'],
          borderColor: '#fff',
          borderWidth: 3,
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '55%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              font: {
                family: 'Poppins',
                size: 12
              },
              boxWidth: 12,
              padding: 14
            }
          },
          tooltip: {
            bodyFont: {
              family: 'Poppins'
            },
            titleFont: {
              family: 'Poppins',
              weight: '600'
            }
          }
        }
      }
    });

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function formatDate(dateStr) {
      const d = new Date(dateStr);

      return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    }

    function postAnnouncement() {
      const title = document.getElementById('announceTitle').value.trim();
      const text = document.getElementById('announceText').value.trim();

      if (!text) {
        appAlert('Please enter an announcement.', 'Announcement', 'warning');
        return;
      }

      const formData = new FormData();
      formData.append('title', title);
      formData.append('message', text);

      fetch('../controllers/announcements/post_announcement.php', {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          if (!data.success) {
            appAlert(data.message || 'Failed to post announcement.', 'Announcement', 'danger');
            return;
          }

          const a = data.announcement;
          const item = document.createElement('div');
          item.className = 'feed-item';

          item.innerHTML = `
            <div class="feed-top">
              <div class="feed-avatar">CA</div>
              <span class="feed-author">${escapeHtml(a.posted_by)}</span>
              <span class="feed-date">${formatDate(a.created_at)}</span>
            </div>
            ${a.title ? `<div style="font-weight:700; margin-bottom:4px;">${escapeHtml(a.title)}</div>` : ''}
            <div class="feed-body">${escapeHtml(a.message)}</div>
          `;

          const list = document.getElementById('announceList');

          if (list.querySelector('.empty-body')) {
            list.innerHTML = '';
          }

          list.insertBefore(item, list.firstChild);

          document.getElementById('announceTitle').value = '';
          document.getElementById('announceText').value = '';

          const currentCount = list.querySelectorAll('.feed-item').length;
          document.getElementById('postCountLabel').textContent = `${currentCount} posted`;
        })
        .catch(() => {
          appAlert('Something went wrong. Please try again.', 'Announcement', 'danger');
        });
    }

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

      fetch(`../controllers/student/search_student.php?studentid=${encodeURIComponent(id)}`)
        .then(res => res.json())
        .then(data => {
          document.getElementById('searchLoading').style.display = 'none';

          if (!data.found) {
            showSearchError(data.message || 'No student found with that ID.');
            return;
          }

          const s = data.student;
          const initials = (s.firstname.charAt(0) + s.lastname.charAt(0)).toUpperCase();
          const yearLabels = {
            1: '1st Year',
            2: '2nd Year',
            3: '3rd Year',
            4: '4th Year'
          };

          document.getElementById('resultAvatar').textContent = initials;
          document.getElementById('resultName').textContent = s.lastname + ', ' + s.firstname + ' ' + s.middlename;
          document.getElementById('resultId').textContent = 'ID: ' + s.studentid;
          document.getElementById('resultCourse').textContent = s.course;
          document.getElementById('resultYear').textContent = yearLabels[s.yearlvl] || s.yearlvl;
          document.getElementById('resultEmail').textContent = s.email;
          document.getElementById('resultAddr').textContent = s.addrs;
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

    document.getElementById('searchModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeSearchModal();
      }
    });

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

      fetch(`../controllers/student/search_student.php?studentid=${encodeURIComponent(id)}`)
        .then(res => res.json())
        .then(data => {
          if (!data.found) {
            showSitinError('sitinLookupError', data.message || 'No student found with that ID.');
            return;
          }

          const s = data.student;
          currentStudent = s;

          if (s.session_credits <= 0) {
            showSitinError('sitinLookupError', 'This student has no remaining session credits.');
            return;
          }

          const initials = (s.firstname.charAt(0) + s.lastname.charAt(0)).toUpperCase();
          const yearLabels = {
            1: '1st Year',
            2: '2nd Year',
            3: '3rd Year',
            4: '4th Year'
          };

          document.getElementById('sitinAvatar').textContent = initials;
          document.getElementById('sitinStudentName').textContent = s.lastname + ', ' + s.firstname + ' ' + s.middlename;
          document.getElementById('sitinStudentCourse').textContent = s.course + ' — ' + (yearLabels[s.yearlvl] || s.yearlvl);
          document.getElementById('sitinSessionBadge').textContent = s.session_credits;

          document.getElementById('sitinStudentInfo').style.display = 'block';
          document.getElementById('sitinFormFields').style.display = 'block';
          document.getElementById('sitinLookupError').style.display = 'none';
        })
        .catch(() => {
          showSitinError('sitinLookupError', 'Something went wrong. Try again.');
        });
    }

    function submitSitin() {
      const purpose = document.getElementById('sitinPurpose').value.trim();
      const lab = document.getElementById('sitinLab').value;

      if (!currentStudent) {
        showSitinError('sitinSubmitError', 'Please look up a student first.');
        return;
      }

      if (!purpose) {
        showSitinError('sitinSubmitError', 'Please enter the purpose.');
        return;
      }

      if (!lab) {
        showSitinError('sitinSubmitError', 'Please select a lab.');
        return;
      }

      const formData = new FormData();
      formData.append('studentid', currentStudent.studentid);
      formData.append('purpose', purpose);
      formData.append('lab', lab);

      fetch('../controllers/sitin/register_sitin.php', {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          if (!data.success) {
            showSitinError('sitinSubmitError', data.message || 'Failed to register sit-in.');
            return;
          }

          closeSitinModal();
          showToast();
        })
        .catch(() => {
          showSitinError('sitinSubmitError', 'Something went wrong. Try again.');
        });
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

    document.getElementById('sitinModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeSitinModal();
      }
    });
  </script>
</body>
</html>