<?php
session_start();

// If admin is already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $basePath = preg_replace('#/(admin_panel|backend)(/.*)?$#', '', $scriptDir);
    $basePath = ($basePath === '') ? '' : $basePath;
    header('Location: ' . $basePath . '/admin_panel/dashboard.php');
    exit;
}

// Otherwise, load the login page
include 'login.php';
?>
