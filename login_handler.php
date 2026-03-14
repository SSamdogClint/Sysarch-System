<?php
// ============================================================
//  login_handler.php  —  Handles student login form POST
//  Rename to: login.php
// ============================================================

session_start();
require_once '../config/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$pswd  = $_POST['pswd']       ?? '';

if (empty($email) || empty($pswd)) {
    $_SESSION['login_error'] = 'Email and password are required.';
    header('Location: login.php');
    exit;
}

// Fetch student — include email and addrs for session
$stmt = $conn->prepare(
    'SELECT id, studentid, firstname, lastname, course, yearlvl, email, addrs, password
     FROM students WHERE email = ? LIMIT 1'
);
$stmt->bind_param('s', $email);
$stmt->execute();
$result  = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student || !password_verify($pswd, $student['password'])) {
    $_SESSION['login_error'] = 'Invalid email or password.';
    header('Location: login.php');
    exit;
}

session_regenerate_id(true);

$_SESSION['logged_in']        = true;
$_SESSION['student_id']       = $student['id'];
$_SESSION['studentid']        = $student['studentid'];
$_SESSION['firstname']        = $student['firstname'];
$_SESSION['lastname']         = $student['lastname'];
$_SESSION['course']           = $student['course'];
$_SESSION['yearlvl']          = $student['yearlvl'];
$_SESSION['email']            = $student['email'];
$_SESSION['addrs']            = $student['addrs'];
$_SESSION['session_credits']  = 30;   // default credits; replace with DB value when available

header('Location: student_dashboard.php');
exit;