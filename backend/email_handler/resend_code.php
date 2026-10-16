<?php
/**
 * Resend Verification Code Handler
 * Allows users to request a new code if they didn't receive the first one
 */
require_once __DIR__ . '/../dbconnect/db.php';
require_once __DIR__ . '/MailSender.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Validate CSRF token
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please try again.']);
    exit();
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit();
}

// Check if user can resend (max 1 resend per 2 minutes)
$check_query = "SELECT id, created_at FROM email_verifications 
                WHERE email = ? AND is_verified = 0 
                ORDER BY created_at DESC LIMIT 1";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, 's', $email);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$last_attempt = mysqli_fetch_assoc($check_result);

if (!$last_attempt) {
    echo json_encode(['success' => false, 'message' => 'No verification record found']);
    exit();
}

// Check cooldown (2 minutes between resends)
$time_diff = time() - strtotime($last_attempt['created_at']);
if ($time_diff < 120) {
    $wait_time = 120 - $time_diff;
    echo json_encode(['success' => false, 'message' => "Please wait {$wait_time} seconds before resending", 'wait' => $wait_time]);
    exit();
}

// Generate new code
$verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', time() + (10 * 60));

// Insert new verification record
$insert_query = "INSERT INTO email_verifications (email, verification_code, expires_at) 
                 VALUES (?, ?, ?)";
$insert_stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($insert_stmt, 'sss', $email, $verification_code, $expires_at);

if (!mysqli_stmt_execute($insert_stmt)) {
    echo json_encode(['success' => false, 'message' => 'Error resending code']);
    exit();
}

// Send email
$emailSent = MailSender::sendVerificationCode($email, $verification_code);

if ($emailSent) {
    echo json_encode(['success' => true, 'message' => 'New code sent to your email!']);
} else {
    echo json_encode(['success' => true, 'message' => "Code: $verification_code (sent to email or shown here if email failed)"]);
}
exit();
?>
