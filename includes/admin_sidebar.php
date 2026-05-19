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

  <a class="sidebar-link<?= admin_sidebar_active_open('Admin_StudentList.php', 'search') ?>" href="Admin_StudentList.php?open=search">
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
