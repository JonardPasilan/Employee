<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the hardcoded access key here
if (!defined('ACCESS_KEY')) {
    define('ACCESS_KEY', 'hso@2026');
}

// Only redirect if we are not already on the access page
if (basename($_SERVER['PHP_SELF']) !== 'access.php') {
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        header("Location: access.php");
        exit;
    }
}
?>
