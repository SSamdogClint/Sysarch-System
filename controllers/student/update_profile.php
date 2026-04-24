<?php
// controllers/student/update_profile.php

session_start();
require_once '../../config/db_config.php';

// Allow both student and admin
if (empty($_SESSION['logged_in']) && empty($_SESSION['admin_logged_in'])) {
    header('Location: ../../login_page.php');
    exit;
}

$student_id = (int)($_POST['student_id'] ?? $_SESSION['student_id'] ?? 0);
$studentid  = trim($_POST['studentid']  ?? ($_SESSION['studentid'] ?? ''));
$lastname   = trim($_POST['lastname']   ?? '');
$firstname  = trim($_POST['firstname']  ?? '');
$middlename = trim($_POST['middlename'] ?? '');
$course     = trim($_POST['course']     ?? '');
$yearlvl    = (int)($_POST['yearlvl']   ?? 1);
$email      = trim($_POST['email']      ?? '');
$addrs      = trim($_POST['addrs']      ?? '');

if (!$student_id) {
    header('Location: ../../login_page.php');
    exit;
}

$stmt = $conn->prepare(
    'UPDATE students 
     SET studentid=?, lastname=?, firstname=?, middlename=?, course=?, yearlvl=?, email=?, addrs=?
     WHERE id=?'
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
    if (!empty($_SESSION['logged_in']) && (int)$_SESSION['student_id'] === $student_id) {
        $_SESSION['studentid']  = $studentid;
        $_SESSION['lastname']   = $lastname;
        $_SESSION['firstname']  = $firstname;
        $_SESSION['middlename'] = $middlename;
        $_SESSION['course']     = $course;
        $_SESSION['yearlvl']    = $yearlvl;
        $_SESSION['email']      = $email;
        $_SESSION['addrs']      = $addrs;
    }
}

$stmt->close();

$redirect = $_POST['redirect'] ?? '';

if ($redirect === 'admin') {
    header('Location: ../../admin_module/Admin_StudentList.php');
} else {
    header('Location: ../../student_module/student_dashboard.php');
}

exit;