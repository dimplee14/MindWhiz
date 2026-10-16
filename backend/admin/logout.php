<?php
session_start();
// Clear session and redirect to login page
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
session_destroy();
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$basePath = preg_replace('#/(admin_panel|backend)(/.*)?$#', '', $scriptDir);
$basePath = ($basePath === '') ? '' : $basePath;
header('Location: ../../admin_panel/login.php');
exit();
?>