<?php
session_start();

// If email is already verified, go directly to page2
if (!empty($_SESSION['verified_email'])) {
    header('Location: html/page2.php');
    exit();
}

// Otherwise start from page1
header('Location: html/page1.php');
exit();
?>
