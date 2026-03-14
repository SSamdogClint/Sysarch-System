<?php
// ============================================================
//  register.php  —  Handles student registration form POST
//  Place this in your project root (same level as register.html)
// ============================================================

session_start();
require_once '../config/db_config.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register_page.php');
    exit;
}

// ── 1. Collect & sanitize inputs ────────────────────────────
$studentid  = trim($_POST['studentid']  ?? '');
$lastname   = trim($_POST['lastname']   ?? '');
$firstname  = trim($_POST['firstname']  ?? '');
$middlename = trim($_POST['middlename'] ?? '');
$course     = trim($_POST['course']     ?? '');
$yearlvl    = (int)($_POST['yearlvl']   ?? 1);
$email      = trim($_POST['email']      ?? '');
$pswd       = $_POST['pswd']            ?? '';
$conpswd    = $_POST['conpswd']         ?? '';
$addrs      = trim($_POST['addrs']      ?? '');

// ── 2. Validate ──────────────────────────────────────────────
$errors = [];

if (empty($studentid))               $errors[] = 'Student ID is required.';
if (empty($lastname))                $errors[] = 'Last name is required.';
if (empty($firstname))               $errors[] = 'First name is required.';
if (empty($course))                  $errors[] = 'Course is required.';
if (!in_array($yearlvl, [1,2,3,4])) $errors[] = 'Invalid year level.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
                                     $errors[] = 'A valid email address is required.';
if (strlen($pswd) < 8)              $errors[] = 'Password must be at least 8 characters.';
if ($pswd !== $conpswd)             $errors[] = 'Passwords do not match.';

if (!empty($errors)) {
    $_SESSION['reg_errors'] = $errors;
    $_SESSION['reg_old']    = compact('studentid','lastname','firstname','middlename','course','yearlvl','email','addrs');
    header('Location: register_page.php');
    exit;
}

// ── 3. Check for duplicate studentid or email ────────────────
$stmt = $conn->prepare('SELECT id FROM students WHERE studentid = ? OR email = ? LIMIT 1');
$stmt->bind_param('ss', $studentid, $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $_SESSION['reg_errors'] = ['Student ID or email is already registered.'];
    $_SESSION['reg_old']    = compact('studentid','lastname','firstname','middlename','course','yearlvl','email','addrs');
    $stmt->close();
    header('Location: register_page.php');
    exit;
}
$stmt->close();

// ── 4. Hash password & insert ────────────────────────────────
$hashed = password_hash($pswd, PASSWORD_BCRYPT);

$stmt = $conn->prepare(
    'INSERT INTO students (studentid, lastname, firstname, middlename, course, yearlvl, email, password, addrs)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param(
    'sssssisis',
    $studentid, $lastname, $firstname, $middlename,
    $course, $yearlvl, $email, $hashed, $addrs
);

if ($stmt->execute()) {
    $stmt->close();
    $_SESSION['reg_success'] = 'Account created! You can now log in.';
    header('Location: login_page.php');
    exit;
} else {
    $stmt->close();
    $_SESSION['reg_errors'] = ['Something went wrong. Please try again.'];
    header('Location: register_page.php');
    exit;
}