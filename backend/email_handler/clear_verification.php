<?php
/**
 * Clear Verification Handler
 * Clears expired verification codes from database
 * Can be called via API for cleanup tasks
 */

require_once __DIR__ . '/../dbconnect/db.php';
session_start();

// Check if user has permission to clear (admin only or via cron)
$api_key = isset($_GET['api_key']) ? $_GET['api_key'] : '';
$is_admin = isset($_SESSION['admin_id']);

// Allow admin or valid API key
if (!$is_admin && $api_key !== 'your-secret-api-key') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Determine what to clear
$action = isset($_GET['action']) ? $_GET['action'] : 'expired';
$email = isset($_GET['email']) ? $_GET['email'] : null;

try {
    if ($action === 'all' && $is_admin) {
        // Clear all unverified codes
        $query = "DELETE FROM email_verifications WHERE is_verified = 0";
        $result = mysqli_query($conn, $query);
        $affected = mysqli_affected_rows($conn);
        echo json_encode([
            'success' => true,
            'message' => "Cleared $affected unverified codes",
            'affected_rows' => $affected
        ]);
    } elseif ($action === 'expired') {
        // Clear expired codes (older than 10 minutes)
        $query = "DELETE FROM email_verifications 
                  WHERE (is_verified = 0 AND expires_at < NOW())
                  OR (created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR))";
        $result = mysqli_query($conn, $query);
        $affected = mysqli_affected_rows($conn);
        echo json_encode([
            'success' => true,
            'message' => "Cleared $affected expired codes",
            'affected_rows' => $affected
        ]);
    } elseif ($action === 'email' && $email && $is_admin) {
        // Clear codes for specific email
        $stmt = mysqli_prepare($conn, "DELETE FROM email_verifications WHERE email = ? AND is_verified = 0");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_affected_rows($conn);
        echo json_encode([
            'success' => true,
            'message' => "Cleared verification codes for $email",
            'affected_rows' => $affected
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action or insufficient permissions'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
