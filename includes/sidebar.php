<?php
/**
 * FuelDeskPro - Sidebar Navigation
 *
 * @package FuelDeskPro
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$langToggle = currentLang() === 'bn' ? 'en' : 'bn';
$langLabel = currentLang() === 'bn' ? 'English' : 'বাংলা';
?>
<!-- Sidebar -->
<nav id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <h5 class="mb-0"><?php echo $shortName ?? 'FDP'; ?></h5>
        <small class="text-muted"><?php echo t('Fuel Station Management'); ?></small>
    </div>

    <ul class="nav flex-column">
        <!-- Dashboard -->
        <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage == 'dashboard.php' || $currentPage == 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>dashboard.php">
                <i class="fas fa-tachometer-alt"></i> <span><?php echo t('Dashboard'); ?></span>
            </a>
        </li>
 <!-- Operations Dropdown -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#operationsMenu" role="button" aria-expanded="true">
                <i class="fas fa-cogs"></i> <span><?php echo t('Operations'); ?></span>
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse show" id="operationsMenu">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/operations/nozzle_reading.php"><i class="fas fa-tachometer-alt"></i> <?php echo t('Nozzle Meter Readings'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/operations/customer_due.php"><i class="fas fa-file-invoice"></i> <?php echo t('Customer Dues'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/operations/due_collection.php"><i class="fas fa-hand-holding-usd"></i> <?php echo t('Due Collections'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/operations/cash_collection.php"><i class="fas fa-money-bill-wave"></i> <?php echo t('Cash Collections'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/operations/others_collection.php"><i class="fas fa-coins"></i> <?php echo t('Others Collections'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/operations/expense.php"><i class="fas fa-receipt"></i> <?php echo t('Expense'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/operations/fuel_purchase.php"><i class="fas fa-shopping-cart"></i> <?php echo t('Fuel Purchase'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/operations/fuel_price_adjustment.php"><i class="fas fa-sliders-h"></i> <?php echo t('Fuel Price Adjustment'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/operations/supplier_payment.php"><i class="fas fa-credit-card"></i> <?php echo t('Supplier Payment'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/operations/tank_reading.php"><i class="fas fa-chart-line"></i> <?php echo t('Tank Readings'); ?></a></li>
                </ul>
            </div>
        </li>

        <!-- Master Data Dropdown -->
        <li class="nav-item">
          <a class="nav-link collapsed" data-bs-toggle="collapse" href="#masterDataMenu" role="button" aria-expanded="false">
                <i class="fas fa-database"></i> <span><?php echo t('Master Data'); ?></span>
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="masterDataMenu">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/master_data/shift.php"><i class="fas fa-clock"></i> <?php echo t('Shift'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/master_data/fuel_type.php"><i class="fas fa-oil-can"></i> <?php echo t('Fuel Type'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/master_data/tank_group.php"><i class="fas fa-layer-group"></i> <?php echo t('Tank Group'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/master_data/tank.php"><i class="fas fa-tint"></i> <?php echo t('Tanks'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/master_data/dispenser.php"><i class="fas fa-gas-pump"></i> <?php echo t('Dispenser'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/master_data/nozzle.php"><i class="fas fa-faucet"></i> <?php echo t('Nozzles'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/master_data/supplier.php"><i class="fas fa-truck"></i> <?php echo t('Supplier'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/master_data/shareholder.php"><i class="fas fa-users"></i> <?php echo t('Shareholders'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/master_data/employee.php"><i class="fas fa-user-tie"></i> <?php echo t('Employee'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/master_data/customer.php"><i class="fas fa-user-friends"></i> <?php echo t('Customer'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/master_data/particular_group.php"><i class="fas fa-tags"></i> <?php echo t('Particular Group'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/master_data/particular.php"><i class="fas fa-list"></i> <?php echo t('Particular'); ?></a></li>
                </ul>
            </div>
        </li>

        <!-- Reports Dropdown -->
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#reportsMenu" role="button" aria-expanded="false">
                <i class="fas fa-chart-bar"></i> <span><?php echo t('Reports'); ?></span>
                <i class="fas fa-chevron-down ms-auto"></i>
            </a>
            <div class="collapse" id="reportsMenu">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/reports/profit_loss.php"><i class="fas fa-chart-line"></i> <?php echo t('Profit / Loss Report'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/reports/date_to_date_statement.php"><i class="fas fa-file-invoice-dollar"></i> <?php echo t('Date To Date All Statement'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/reports/monthly_collection_expense_summary.php"><i class="fas fa-chart-line"></i> <?php echo t('Monthly Collection & Expense Summary'); ?></a></li>
                    <li class="nav-item"><a class="nav-link small" href="<?php echo BASE_URL; ?>modules/reports/monthly_expense_item_wise.php"><i class="fas fa-receipt"></i> <?php echo t('Monthly Expense Summary Item Wise'); ?></a></li>
                </ul>
            </div>
        </li>

        <!-- Settings -->
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="fas fa-cog"></i> <span><?php echo t('Settings'); ?></span>
            </a>
        </li>
    </ul>
</nav>

<!-- Main content wrapper -->
<div id="content-wrapper">
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg top-navbar">
        <div class="container-fluid">
            <button type="button" id="sidebarCollapse" class="btn btn-link">
                <i class="fas fa-bars"></i>
            </button>
            <span class="navbar-brand mb-0 h1"><?php echo t($pageTitle ?? 'Dashboard'); ?></span>
            <div class="ms-auto d-flex align-items-center">
                <span class="me-3 text-muted small">
                    <i class="fas fa-calendar-alt"></i> <?php echo date('d-m-Y'); ?>
                </span>
                <div class="dropdown">
                    <a class="btn btn-sm btn-outline-secondary me-2" href="?lang=<?php echo $langToggle; ?>"><?php echo $langLabel; ?></a>
                    <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo getUserName() ?? 'User'; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> <?php echo t('Profile'); ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo t('Logout'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div id="main-content" class="container-fluid">
