<?php
// controllers/auth/login_handler.php
session_start();
require_once '../../config/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../login_page.php');
    exit;
}

$studentid = trim($_POST['studentid'] ?? '');
$pswd      = $_POST['pswd'] ?? '';

if ($studentid === '' || $pswd === '') {
    $_SESSION['login_error'] = 'ID Number and password are required.';
    header('Location: ../../login_page.php');
    exit;
}

// Check admin credentials first.
// Note: For production, it is better to move this to an admins table with hashed passwords.
if ($studentid === 'admin' && $pswd === 'admin123') {
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_name']      = 'Administrator';

    header('Location: ../../admin_module/admin_dashboard.php');
    exit;
}

// Check student account in database.
$stmt = $conn->prepare(
    'SELECT id, studentid, firstname, lastname, middlename, course, yearlvl, email, addrs, password, session_credits
     FROM students
     WHERE studentid = ?
     LIMIT 1'
);

$stmt->bind_param('s', $studentid);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student || !password_verify($pswd, $student['password'])) {
    $_SESSION['login_error'] = 'Invalid ID number or password.';
    header('Location: ../../login_page.php');
    exit;
}

session_regenerate_id(true);
$_SESSION['logged_in']       = true;
$_SESSION['student_id']      = (int)$student['id'];
$_SESSION['studentid']       = $student['studentid'];
$_SESSION['firstname']       = $student['firstname'];
$_SESSION['lastname']        = $student['lastname'];
$_SESSION['middlename']      = $student['middlename'] ?? '';
$_SESSION['course']          = $student['course'];
$_SESSION['yearlvl']         = $student['yearlvl'];
$_SESSION['email']           = $student['email'];
$_SESSION['addrs']           = $student['addrs'];
$_SESSION['session_credits'] = (int)$student['session_credits'];

header('Location: ../../student_module/student_dashboard.php');
exit;
