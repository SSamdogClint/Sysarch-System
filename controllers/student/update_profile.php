<?php
// controllers/student/update_profile.php

session_start();
require_once '../../config/db_config.php';

// Allow both student and admin.
if (empty($_SESSION['logged_in']) && empty($_SESSION['admin_logged_in'])) {
    header('Location: ../../login_page.php');
    exit;
}

$isAdmin = !empty($_SESSION['admin_logged_in']);

/*
  Security fix:
  - Admin can update any student using POST student_id.
  - Student can only update their own profile using the session student_id.
*/
if ($isAdmin) {
    $student_id = (int)($_POST['student_id'] ?? 0);
} else {
    $student_id = (int)($_SESSION['student_id'] ?? 0);
}

$studentid  = trim($_POST['studentid'] ?? ($_SESSION['studentid'] ?? ''));
$lastname   = trim($_POST['lastname'] ?? '');
$firstname  = trim($_POST['firstname'] ?? '');
$middlename = trim($_POST['middlename'] ?? '');
$course     = trim($_POST['course'] ?? '');
$yearlvl    = (int)($_POST['yearlvl'] ?? 1);
$email      = trim($_POST['email'] ?? '');
$addrs      = trim($_POST['addrs'] ?? '');

if ($student_id <= 0) {
    header('Location: ../../login_page.php');
    exit;
}

if ($studentid === '' || $lastname === '' || $firstname === '' || $course === '' || $email === '') {
    $_SESSION['profile_error'] = 'Please complete all required fields.';
    redirectBack($isAdmin);
}

if ($yearlvl < 1 || $yearlvl > 4) {
    $yearlvl = 1;
}

$stmt = $conn->prepare(
    'UPDATE students
     SET studentid = ?, lastname = ?, firstname = ?, middlename = ?, course = ?, yearlvl = ?, email = ?, addrs = ?
     WHERE id = ?'
);

$stmt->bind_param(
    'sssssissi',
    $studentid,
    $lastname,
    $firstname,
    $middlename,
    $course,
    $yearlvl,
    $email,
    $addrs,
    $student_id
);

if ($stmt->execute()) {
    // Refresh session data only when the logged-in student updated their own profile.
    if (!$isAdmin && !empty($_SESSION['logged_in']) && (int)$_SESSION['student_id'] === $student_id) {
        $_SESSION['studentid']  = $studentid;
        $_SESSION['lastname']   = $lastname;
        $_SESSION['firstname']  = $firstname;
        $_SESSION['middlename'] = $middlename;
        $_SESSION['course']     = $course;
        $_SESSION['yearlvl']    = $yearlvl;
        $_SESSION['email']      = $email;
        $_SESSION['addrs']      = $addrs;
    }

    $_SESSION['profile_success'] = 'Profile updated successfully.';
} else {
    $_SESSION['profile_error'] = 'Failed to update profile: ' . $stmt->error;
}

$stmt->close();
redirectBack($isAdmin);

function redirectBack(bool $isAdmin): void
{
    $redirect = $_POST['redirect'] ?? '';

    if ($isAdmin || $redirect === 'admin') {
        header('Location: ../../admin_module/Admin_StudentList.php');
    } else {
        header('Location: ../../student_module/student_dashboard.php');
    }

    exit;
}
