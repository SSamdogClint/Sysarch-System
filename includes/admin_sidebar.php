<?php
$currentAdminPage = basename($_SERVER['PHP_SELF'] ?? '');
$currentOpen = $_GET['open'] ?? '';

function admin_sidebar_active(string $page): string
{
    global $currentAdminPage, $currentOpen;

    if ($currentAdminPage !== $page) {
        return '';
    }

    if ($page === 'Admin_StudentList.php' && $currentOpen === 'search') {
        return '';
    }

    if ($page === 'Admin_SitinRecords.php' && $currentOpen === 'sitin') {
        return '';
    }

    return ' active';
}

function admin_sidebar_active_open(string $page, string $open): string
{
    global $currentAdminPage, $currentOpen;
    return ($currentAdminPage === $page && $currentOpen === $open) ? ' active' : '';
}
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-section" style="margin-top:0;">Main</div>

  <a class="sidebar-link<?= admin_sidebar_active('admin_dashboard.php') ?>" href="admin_dashboard.php">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
    Dashboard
  </a>

  <a class="sidebar-link<?= admin_sidebar_active_open('Admin_StudentList.php', 'search') ?>" href="#" data-admin-global-search>
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    Search Student
  </a>

  <a class="sidebar-link<?= admin_sidebar_active('Admin_StudentList.php') ?>" href="Admin_StudentList.php">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    Students
  </a>

  <a class="sidebar-link<?= admin_sidebar_active_open('Admin_SitinRecords.php', 'sitin') ?>" href="Admin_SitinRecords.php?open=sitin">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2M8 7V5a2 2 0 0 0-4 0v2"/></svg>
    Register Sit-in
  </a>

  <a class="sidebar-link<?= admin_sidebar_active('Admin_SitinRecords.php') ?>" href="Admin_SitinRecords.php">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    Sit-in Records
  </a>

  <a class="sidebar-link<?= admin_sidebar_active('Admin_Reservation.php') ?>" href="Admin_Reservation.php">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    Reservation
  </a>

  <hr class="sidebar-divider">

  <div class="sidebar-section">Analytics</div>

  <a class="sidebar-link<?= admin_sidebar_active('Admin_Analytics.php') ?>" href="Admin_Analytics.php">
    <i class="bi bi-graph-up-arrow"></i>
    <span>Analytics</span>
  </a>


  <a href="Admin_Rewards.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'Admin_Rewards.php' ? 'active' : '' ?>">
    <i class="bi bi-trophy"></i>
    <span>Rewards / Points</span>
  </a>

  <hr class="sidebar-divider">

  <div class="sidebar-section">Reports & Tools</div>

  <a class="sidebar-link<?= admin_sidebar_active('Admin_Reports.php') ?>" href="Admin_Reports.php">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    Report Summary
  </a>

  <a class="sidebar-link<?= admin_sidebar_active('Admin_Software.php') ?>" href="Admin_Software.php">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
    Software Import
  </a>

  <a class="sidebar-link<?= admin_sidebar_active('Admin_Testimonials.php') ?>" href="Admin_Testimonials.php">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    Testimonials
  </a>
</aside>


<!-- Global admin search modal: works from every admin page that includes this sidebar. -->
<div id="globalAdminSearchModal" style="display:none; position:fixed; inset:0; z-index:19990; background:rgba(0,0,0,0.45); align-items:center; justify-content:center;">
  <div style="background:#fff; border-radius:16px; width:100%; max-width:520px; margin:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.2); font-family:'Poppins',sans-serif; overflow:hidden;">
    <div style="background:#1d3a6e; color:#fff; padding:16px 24px; display:flex; align-items:center; justify-content:space-between;">
      <span style="font-size:14px; font-weight:600;">Search Student</span>
      <button type="button" onclick="closeGlobalAdminSearchModal()" style="background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">✕</button>
    </div>

    <div style="padding:20px 24px; border-bottom:1px solid #f3f4f6;">
      <div style="display:flex; gap:10px;">
        <input
          type="text"
          id="globalAdminSearchInput"
          placeholder="Enter Student ID (e.g. 1001)"
          style="flex:1; border:1px solid #e5e7eb; border-radius:8px; padding:10px 14px; font-size:13px; font-family:'Poppins',sans-serif; outline:none; color:#111827;"
          onkeydown="if(event.key==='Enter') globalAdminSearchStudent()"
        >
        <button type="button" onclick="globalAdminSearchStudent()" style="padding:10px 20px; background:#1d3a6e; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; font-family:'Poppins',sans-serif; cursor:pointer; white-space:nowrap;">
          Search
        </button>
      </div>
      <div id="globalAdminSearchError" style="display:none; margin-top:8px; font-size:12px; color:#b91c1c;"></div>
    </div>

    <div id="globalAdminSearchResult" style="display:none; padding:20px 24px;">
      <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid #f3f4f6;">
        <div id="globalAdminResultAvatar" style="width:56px; height:56px; border-radius:50%; background:#1d3a6e; color:#fff; font-size:20px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:3px solid #e5e7eb;"></div>
        <div>
          <div id="globalAdminResultName" style="font-size:15px; font-weight:700; color:#111827;"></div>
          <div id="globalAdminResultId" style="font-size:12px; color:#6b7280; margin-top:2px;"></div>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
          <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">COURSE</div>
          <div id="globalAdminResultCourse" style="font-size:13px; font-weight:600; color:#111827;"></div>
        </div>
        <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
          <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">YEAR LEVEL</div>
          <div id="globalAdminResultYear" style="font-size:13px; font-weight:600; color:#111827;"></div>
        </div>
        <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
          <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">EMAIL</div>
          <div id="globalAdminResultEmail" style="font-size:13px; font-weight:600; color:#111827; word-break:break-all;"></div>
        </div>
        <div style="background:#f9fafb; border-radius:8px; padding:10px 14px;">
          <div style="font-size:11px; font-weight:600; color:#6b7280; margin-bottom:3px;">ADDRESS</div>
          <div id="globalAdminResultAddr" style="font-size:13px; font-weight:600; color:#111827;"></div>
        </div>
        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px; grid-column: span 2;">
          <div style="font-size:11px; font-weight:600; color:#1d4ed8; margin-bottom:3px;">SESSION CREDITS REMAINING</div>
          <div style="display:flex; align-items:center; gap:8px;">
            <div id="globalAdminResultCredits" style="font-size:22px; font-weight:800; color:#1d3a6e;"></div>
            <div style="font-size:12px; color:#6b7280;">/ 30 credits this semester</div>
          </div>
        </div>
      </div>
    </div>

    <div id="globalAdminSearchLoading" style="display:none; padding:32px; text-align:center; font-size:13px; color:#6b7280;">
      Searching...
    </div>
  </div>
</div>

<script src="../assets/js/app_modal.js"></script>
<script>
(function () {
  function $(id) { return document.getElementById(id); }

  window.openGlobalAdminSearchModal = function () {
    const modal = $('globalAdminSearchModal');
    if (!modal) return;
    modal.style.display = 'flex';
    resetGlobalAdminSearch();
    setTimeout(() => $('globalAdminSearchInput')?.focus(), 80);
  };

  window.closeGlobalAdminSearchModal = function () {
    const modal = $('globalAdminSearchModal');
    if (!modal) return;
    modal.style.display = 'none';
    resetGlobalAdminSearch();
  };

  window.resetGlobalAdminSearch = function () {
    const input = $('globalAdminSearchInput');
    if (input) input.value = '';
    ['globalAdminSearchResult','globalAdminSearchError','globalAdminSearchLoading'].forEach(id => {
      const el = $(id);
      if (el) el.style.display = 'none';
    });
  };

  function showGlobalAdminSearchError(message) {
    const error = $('globalAdminSearchError');
    if (!error) return;
    error.textContent = message;
    error.style.display = 'block';
  }

  function safeText(value) {
    return String(value ?? '');
  }

  window.globalAdminSearchStudent = function () {
    const input = $('globalAdminSearchInput');
    const id = input ? input.value.trim() : '';

    if (!id) {
      showGlobalAdminSearchError('Please enter a Student ID.');
      return;
    }

    $('globalAdminSearchResult').style.display = 'none';
    $('globalAdminSearchError').style.display = 'none';
    $('globalAdminSearchLoading').style.display = 'block';

    fetch(`../controllers/student/search_student.php?studentid=${encodeURIComponent(id)}`)
      .then(res => res.json())
      .then(data => {
        $('globalAdminSearchLoading').style.display = 'none';

        if (!data.found) {
          showGlobalAdminSearchError(data.message || 'No student found with that ID.');
          return;
        }

        const s = data.student || {};
        const initials = (safeText(s.firstname).charAt(0) + safeText(s.lastname).charAt(0)).toUpperCase() || 'ST';
        const yearLabels = { 1: '1st Year', 2: '2nd Year', 3: '3rd Year', 4: '4th Year' };

        $('globalAdminResultAvatar').textContent = initials;
        $('globalAdminResultName').textContent = `${safeText(s.lastname)}, ${safeText(s.firstname)} ${safeText(s.middlename)}`.trim();
        $('globalAdminResultId').textContent = 'ID: ' + safeText(s.studentid);
        $('globalAdminResultCourse').textContent = safeText(s.course) || '—';
        $('globalAdminResultYear').textContent = yearLabels[s.yearlvl] || safeText(s.yearlvl) || '—';
        $('globalAdminResultEmail').textContent = safeText(s.email) || '—';
        $('globalAdminResultAddr').textContent = safeText(s.addrs) || '—';
        $('globalAdminResultCredits').textContent = safeText(s.session_credits) || '0';
        $('globalAdminSearchResult').style.display = 'block';
      })
      .catch(() => {
        $('globalAdminSearchLoading').style.display = 'none';
        showGlobalAdminSearchError('Something went wrong. Please try again.');
      });
  };

  document.addEventListener('click', function (e) {
    const link = e.target.closest('[data-admin-global-search]');
    if (link) {
      e.preventDefault();
      window.openGlobalAdminSearchModal();
    }
  });

  const modal = $('globalAdminSearchModal');
  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) window.closeGlobalAdminSearchModal();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('open') === 'search') {
      window.openGlobalAdminSearchModal();
    }
  });
})();
</script>
