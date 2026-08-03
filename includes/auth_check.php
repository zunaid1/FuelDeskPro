<?php
/**
 * FuelDeskPro - Authentication Check
 * 
 * Verifies user is logged in before accessing protected pages.
 * Include this file at the top of all protected pages.
 * 
 * @package FuelDeskPro
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    // Not logged in - redirect to login page
    header("Location: login.php");
    exit;
}

// Optional: Check if session has expired (30 minutes inactivity)
$sessionTimeout = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $sessionTimeout) {
    session_unset();
    session_destroy();
    header("Location: login.php?expired=1");
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();