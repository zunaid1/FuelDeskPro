<?php
/**
 * FuelDeskPro - Database Configuration
 * 
 * Database connection constants and initialization.
 * 
 * @package FuelDeskPro
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'fueldesk_pro');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Load the Query class
require_once __DIR__ . '/../includes/Query.php';

// Create global database object
$objQuery = Query::getInstance();