<?php
/**
 * FuelDeskPro - Index Page
 * 
 * Redirects to login or dashboard based on session.
 * 
 * @package FuelDeskPro
 */

session_start();

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit;