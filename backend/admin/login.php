<?php
require_once __DIR__ . '/../dbconnect/db.php';
session_start();

// Derive project base dynamically (e.g., /t or root)
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$basePath = preg_replace('#/(admin_panel|backend)(/.*)?$#', '', $scriptDir);
$basePath = ($basePath === '') ? '' : $basePath;
$dashboardPath = $basePath . '/admin_panel/dashboard.php';
$loginPath = $basePath . '/admin_panel/login.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $loginPath);
    exit();
}

$identifier = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($identifier === '' || $password === '') {
    $_SESSION['admin_error'] = 'Missing credentials.';
    header('Location: ../../admin_panel/login.php');
    exit();
}

$query = "SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $identifier);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($res)) {
    if (password_verify($password, $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['admin_username'] = $row['username'];
        header('Location: ' . $dashboardPath);
        exit();
    }
}

$_SESSION['admin_error'] = 'Invalid username or password.';
    header('Location: ' . $loginPath);
exit();

?>
