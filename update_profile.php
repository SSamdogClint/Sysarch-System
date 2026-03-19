<?php
session_start();
require_once 'config/db_config.php';

if (empty($_SESSION['logged_in'])) {
    header('Location: home.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$studentid  = trim($_POST['studentid']  ?? '');
$lastname   = trim($_POST['lastname']   ?? '');
$firstname  = trim($_POST['firstname']  ?? '');
$middlename = trim($_POST['middlename'] ?? '');
$course     = trim($_POST['course']     ?? '');
$yearlvl    = (int)($_POST['yearlvl']  ?? 1);
$email      = trim($_POST['email']      ?? '');
$addrs      = trim($_POST['addrs']      ?? '');

$stmt = $conn->prepare(
    'UPDATE students SET studentid=?, lastname=?, firstname=?, middlename=?, course=?, yearlvl=?, email=?, addrs=?
     WHERE id=?'
);
$stmt->bind_param('sssssissi', $studentid, $lastname, $firstname, $middlename, $course, $yearlvl, $email, $addrs, $student_id);

if ($stmt->execute()) {
    // Update session with new values
    $_SESSION['studentid']  = $studentid;
    $_SESSION['lastname']   = $lastname;
    $_SESSION['firstname']  = $firstname;
    $_SESSION['middlename'] = $middlename;
    $_SESSION['course']     = $course;
    $_SESSION['yearlvl']    = $yearlvl;
    $_SESSION['email']      = $email;
    $_SESSION['addrs']      = $addrs;
}

$stmt->close();
header('Location: student_module/student_dashboard.php');
exit;