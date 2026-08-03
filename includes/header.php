<?php
/**
 * FuelDeskPro - Header Layout
 * 
 * @package FuelDeskPro
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base URL for absolute paths
define('BASE_URL', '/FuelDeskPro/');

// Load database and functions
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Get company info
$company = getCompanyInfo();
$companyName = $company ? $company->CompanyName : 'FuelDeskPro';
$shortName = $company ? $company->ShortName : 'FDP';
$currencySymbol = $company ? $company->CurrencySymbol : '৳';
$lang = currentLang();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $shortName; ?> - <?php echo t($pageTitle ?? 'Dashboard'); ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">

    <!-- jQuery must load before page-level CRUD scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>window.FUELDESK_BASE_URL = '<?php echo BASE_URL; ?>';</script>
</head>
<body data-lang="<?php echo $lang; ?>">
<div class="wrapper">
