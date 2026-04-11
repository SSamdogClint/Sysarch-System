// includes/add_student.php
<?php
session_start();
require_once '../config/db_config.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ../login_page.php');
    exit;
}

$studentid  = trim($_POST['studentid']  ?? '');
$lastname   = trim($_POST['lastname']   ?? '');
$firstname  = trim($_POST['firstname']  ?? '');
$middlename = trim($_POST['middlename'] ?? '');
$course     = trim($_POST['course']     ?? '');
$yearlvl    = (int)($_POST['yearlvl']  ?? 1);
$email      = trim($_POST['email']      ?? '');
$addrs      = trim($_POST['addrs']      ?? '');
$pswd       = $_POST['pswd']            ?? '';
$conpswd    = $_POST['conpswd']         ?? '';

// Validate
$errors = [];
if (empty($studentid))  $errors[] = 'Student ID is required.';
if (empty($lastname))   $errors[] = 'Last name is required.';
if (empty($firstname))  $errors[] = 'First name is required.';
if (empty($course))     $errors[] = 'Course is required.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
                        $errors[] = 'A valid email is required.';
if (strlen($pswd) < 8) $errors[] = 'Password must be at least 8 characters.';
if ($pswd !== $conpswd) $errors[] = 'Passwords do not match.';

if (!empty($errors)) {
    $_SESSION['add_errors'] = $errors;
    header('Location: ../admin_module/Admin_StudentList.php');
    exit;
}

// Check duplicate
$stmt = $conn->prepare('SELECT id FROM students WHERE studentid = ? OR email = ? LIMIT 1');
$stmt->bind_param('ss', $studentid, $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $_SESSION['add_errors'] = ['Student ID or email already exists.'];
    $stmt->close();
    header('Location: ../admin_module/Admin_StudentList.php');
    exit;
}
$stmt->close();

// Insert
$hashed = password_hash($pswd, PASSWORD_BCRYPT);
$stmt = $conn->prepare(
    'INSERT INTO students (studentid, lastname, firstname, middlename, course, yearlvl, email, password, addrs)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('sssssisss', $studentid, $lastname, $firstname, $middlename, $course, $yearlvl, $email, $hashed, $addrs);

if ($stmt->execute()) {
    $_SESSION['add_success'] = 'Student added successfully!';
} else {
    $_SESSION['add_errors'] = ['Something went wrong. Please try again.'];
}
$stmt->close();

header('Location: ../admin_module/Admin_StudentList.php');
exit;