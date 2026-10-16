<?php
/**
 * Verify Code Handler - Production Version
 * Validates the code entered by user using database records
 */
require_once __DIR__ . '/../dbconnect/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['verify_message'] = 'Invalid request.';
    $_SESSION['verify_message_type'] = 'error';
    header('Location: ../../html/email_verify.php');
    exit();
}

// Validate CSRF token
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['verify_message'] = 'Invalid request. Please try again.';
    $_SESSION['verify_message_type'] = 'error';
    header('Location: ../../html/email_verify.php');
    exit();
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$code = isset($_POST['code']) ? trim($_POST['code']) : '';

// Validate inputs
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['verify_message'] = 'Invalid email address.';
    $_SESSION['verify_message_type'] = 'error';
    header('Location: ../../html/email_verify.php');
    exit();
}

if (empty($code) || !preg_match('/^[0-9]{6}$/', $code)) {
    $_SESSION['verify_message'] = 'Invalid verification code format.';
    $_SESSION['verify_message_type'] = 'error';
    header('Location: ../../html/email_verify.php?email=' . urlencode($email) . '&sent=1');
    exit();
}

// First, check if there's any UNVERIFIED code for this email
$check_query = "SELECT id, verification_code, is_verified, expires_at, 
                CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END as is_valid
                FROM email_verifications 
                WHERE email = ? AND is_verified = 0
                ORDER BY created_at DESC LIMIT 1";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, 's', $email);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$latest_record = mysqli_fetch_assoc($check_result);

// Debug: Check if code exists for this email
if (!$latest_record) {
    $_SESSION['verify_message'] = 'No verification code found for this email. Please request a new code.';
    $_SESSION['verify_message_type'] = 'error';
    header('Location: ../../html/email_verify.php');
    exit();
}

// Check if already verified (shouldn't happen since we filter above, but double-check)
if ($latest_record['is_verified'] == 1) {
    $_SESSION['verify_message'] = 'This code has already been used. Please request a new code.';
    $_SESSION['verify_message_type'] = 'error';
    header('Location: ../../html/email_verify.php?email=' . urlencode($email) . '&sent=1');
    exit();
}

// Check if expired
if ($latest_record['is_valid'] == 0) {
    $_SESSION['verify_message'] = 'This verification code has expired. Please request a new code.';
    $_SESSION['verify_message_type'] = 'error';
    header('Location: ../../html/email_verify.php?email=' . urlencode($email) . '&sent=1');
    exit();
}

// Check if code matches
if ($latest_record['verification_code'] !== $code) {
    $_SESSION['verify_message'] = 'Incorrect verification code. Please check and try again.';
    $_SESSION['verify_message_type'] = 'error';
    header('Location: ../../html/email_verify.php?email=' . urlencode($email) . '&sent=1');
    exit();
}

$record = $latest_record;

// Mark as verified in database
$update_query = "UPDATE email_verifications SET is_verified = 1, verified_at = NOW() WHERE id = ?";
$update_stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($update_stmt, 'i', $record['id']);

if (!mysqli_stmt_execute($update_stmt)) {
    $_SESSION['verify_message'] = 'Verification failed. Please try again.';
    $_SESSION['verify_message_type'] = 'error';
    header('Location: ../../html/email_verify.php');
    exit();
}

// Verification successful!
$_SESSION['verified_email'] = $email;

// Redirect to registration form
header('Location: ../../html/page2.php');
exit();
?>
