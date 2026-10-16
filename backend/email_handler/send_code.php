<?php
/**
 * Send Verification Code Handler - Production Version
 * Generates a 6-digit code, stores in database with rate limiting
 */

// Start output buffering to prevent any output before headers
ob_start();

require_once __DIR__ . '/../dbconnect/db.php';
require_once __DIR__ . '/MailSender.php';
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

// Validate email is not empty
if (empty($email)) {
    $_SESSION['verify_message'] = 'Email address is required.';
    $_SESSION['verify_message_type'] = 'error';
    header('Location: ../../html/email_verify.php');
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['verify_message'] = 'Please enter a valid email address.';
    $_SESSION['verify_message_type'] = 'error';
    header('Location: ../../html/email_verify.php');
    exit();
}

// Validate email domain exists (DNS check)
$domain = substr($email, strpos($email, '@') + 1);
if (!checkdnsrr($domain, 'MX')) {
    $_SESSION['verify_message'] = 'This email domain does not exist. Please check and try again.';
    $_SESSION['verify_message_type'] = 'error';
    header('Location: ../../html/email_verify.php');
    exit();
}

// REMOVED: Previously verified email auto-skip check
// Now every device/session must verify independently
// This ensures fairness across all devices

// Rate limiting: Check if email has sent more than 10 codes in last hour
$query = "SELECT COUNT(*) as count FROM email_verifications 
          WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if ($row['count'] >= 10) {
    $_SESSION['verify_message'] = 'Too many verification codes sent. Please try again after 1 hour.';
    $_SESSION['verify_message_type'] = 'error';
    header('Location: ../../html/email_verify.php');
    exit();
}

// Generate 6-digit verification code
$verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

// Store code in database (10 minutes expiry using MySQL NOW() to avoid timezone issues)
$insert_query = "INSERT INTO email_verifications (email, verification_code, expires_at) 
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))";
$insert_stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($insert_stmt, 'ss', $email, $verification_code);

if (!mysqli_stmt_execute($insert_stmt)) {
    $_SESSION['verify_message'] = 'An error occurred. Please try again.';
    $_SESSION['verify_message_type'] = 'error';
    header('Location: ../../html/email_verify.php');
    exit();
}

// Store in session for reference
$_SESSION['verification_email'] = $email;
$_SESSION['verification_code_id'] = mysqli_insert_id($conn);

// Try to send verification code email
$emailSent = MailSender::sendVerificationCode($email, $verification_code);

if (!$emailSent) {
    // Email failed - inform user and delete the stored code
    $delete_query = "DELETE FROM email_verifications WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, 'i', $_SESSION['verification_code_id']);
    mysqli_stmt_execute($delete_stmt);
    
    $_SESSION['verify_message'] = 'Failed to send verification email. Please check your email address and try again.';
    $_SESSION['verify_message_type'] = 'error';
    unset($_SESSION['verification_email']);
    unset($_SESSION['verification_code_id']);
    
    ob_end_clean();
    header('Location: ../../html/email_verify.php');
    exit();
}

// Email sent successfully
$_SESSION['verify_message'] = 'Verification code has been sent to your email. Please check your inbox.';
$_SESSION['verify_message_type'] = 'success';

// Clear output buffer and send redirect
ob_end_clean();
header('Location: ../../html/email_verify.php?email=' . urlencode($email) . '&sent=1');
exit();
?>
