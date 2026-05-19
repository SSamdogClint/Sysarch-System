<?php
// controllers/auth/forgot_password_handler.php

session_start();
require_once '../../config/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../forgot_password.php');
    exit;
}

$studentid = trim($_POST['studentid'] ?? '');
$email = trim($_POST['email'] ?? '');
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

$errors = [];

if ($studentid === '') {
    $errors[] = 'Student ID is required.';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid registered email is required.';
}

if (strlen($newPassword) < 8) {
    $errors[] = 'New password must be at least 8 characters.';
}

if ($newPassword !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
}

if ($errors) {
    $_SESSION['reset_errors'] = $errors;
    $_SESSION['reset_old'] = ['studentid' => $studentid, 'email' => $email];
    header('Location: ../../forgot_password.php');
    exit;
}

$stmt = $conn->prepare('SELECT id FROM students WHERE studentid = ? AND email = ? LIMIT 1');

if (!$stmt) {
    $_SESSION['reset_errors'] = ['Unable to process reset request. Please check the database connection.'];
    header('Location: ../../forgot_password.php');
    exit;
}

$stmt->bind_param('ss', $studentid, $email);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    $_SESSION['reset_errors'] = ['No account matches that Student ID and registered email.'];
    $_SESSION['reset_old'] = ['studentid' => $studentid, 'email' => $email];
    header('Location: ../../forgot_password.php');
    exit;
}

$hashed = password_hash($newPassword, PASSWORD_BCRYPT);
$studentDbId = (int)$student['id'];

$updateStmt = $conn->prepare('UPDATE students SET password = ? WHERE id = ?');

if (!$updateStmt) {
    $_SESSION['reset_errors'] = ['Unable to update password.'];
    header('Location: ../../forgot_password.php');
    exit;
}

$updateStmt->bind_param('si', $hashed, $studentDbId);
$ok = $updateStmt->execute();
$updateStmt->close();

if (!$ok) {
    $_SESSION['reset_errors'] = ['Unable to update password. Please try again.'];
    header('Location: ../../forgot_password.php');
    exit;
}

$_SESSION['reset_success'] = 'Password reset successfully. You can now sign in with your new password.';
header('Location: ../../login_page.php');
exit;
