<?php
// Only start session if not already active (bootstrap may have started it)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Derive the project base (works regardless of folder name like /t or root)
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$basePath = preg_replace('#/(admin_panel|backend)(/.*)?$#', '', $scriptDir);
$loginPath = ($basePath === '' ? '' : $basePath) . '/admin_panel/login.php';

if (empty($_SESSION['admin_id'])) {
    // If request is XHR/JSON, return 401 JSON, otherwise redirect to login
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    if ($isAjax) {
        header('Content-Type: application/json', true, 401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    } else {
        header('Location: ' . $loginPath);
        exit();
    }
}
?>