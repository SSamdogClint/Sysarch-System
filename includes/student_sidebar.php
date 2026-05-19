<?php
$currentStudentPage = basename($_SERVER['PHP_SELF'] ?? '');

function student_sidebar_active(string $page): string
{
    global $currentStudentPage;
    return $currentStudentPage === $page ? ' active' : '';
}
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-section" style="margin-top:0;">Main</div>

  <a class="sidebar-link<?= student_sidebar_active('student_dashboard.php') ?>" href="student_dashboard.php">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
    Dashboard
  </a>

  <a class="sidebar-link" href="#" onclick="openModal(); return false;">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    Edit Profile
  </a>

  <a class="sidebar-link<?= student_sidebar_active('reservation.php') ?>" href="reservation.php">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    Reservation
  </a>

  <hr class="sidebar-divider">

  <div class="sidebar-section">Records</div>

  <a class="sidebar-link<?= student_sidebar_active('announcements.php') ?>" href="announcements.php">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
    Announcements
  </a>

  <a class="sidebar-link<?= student_sidebar_active('session_table.php') ?>" href="session_table.php">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    Session Table
  </a>

  <a class="sidebar-link<?= student_sidebar_active('sitin_history.php') ?>" href="sitin_history.php">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    Sit-in History
  </a>

  <hr class="sidebar-divider">

  <div class="sidebar-section">Extra</div>

  <a href="rewards.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'rewards.php' ? 'active' : '' ?>">
    <i class="bi bi-trophy"></i>
    <span>Rewards / Points</span>
  </a>

  <a class="sidebar-link<?= student_sidebar_active('software_availability.php') ?>" href="software_availability.php">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
    Software Availability
  </a>

  <a class="sidebar-link<?= student_sidebar_active('testimonials.php') ?>" href="testimonials.php">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    Testimonials
  </a>
</aside>
