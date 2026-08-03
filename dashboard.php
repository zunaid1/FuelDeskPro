<?php
/**
 * FuelDeskPro - Dashboard
 * 
 * @package FuelDeskPro
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Get counts for dashboard widgets
$totalEmployees   = $objQuery->index("SELECT COUNT(*) AS cnt FROM mst_employee WHERE IsActive = 1 AND IsDeleted = 0");
$totalCustomers   = $objQuery->index("SELECT COUNT(*) AS cnt FROM mst_customer WHERE IsActive = 1 AND IsDeleted = 0");
$totalSuppliers   = $objQuery->index("SELECT COUNT(*) AS cnt FROM mst_supplier WHERE IsActive = 1 AND IsDeleted = 0");
$totalShareholders = $objQuery->index("SELECT COUNT(*) AS cnt FROM mst_shareholder WHERE IsActive = 1 AND IsDeleted = 0");
$totalNozzles     = $objQuery->index("SELECT COUNT(*) AS cnt FROM mst_nozzle WHERE IsActive = 1 AND IsDeleted = 0");
$totalFuelTypes   = $objQuery->index("SELECT COUNT(*) AS cnt FROM mst_fueltype WHERE IsActive = 1 AND IsDeleted = 0");

$empCount     = $totalEmployees[0]->cnt ?? 0;
$custCount    = $totalCustomers[0]->cnt ?? 0;
$suppCount    = $totalSuppliers[0]->cnt ?? 0;
$shCount      = $totalShareholders[0]->cnt ?? 0;
$nozCount     = $totalNozzles[0]->cnt ?? 0;
$fuelCount    = $totalFuelTypes[0]->cnt ?? 0;

$today = today();
$todaySales = $objQuery->index("SELECT COALESCE(SUM(SalesAmt),0) AS total FROM trx_nozzlereading WHERE ReadingDate=? AND IsDeleted=0", [$today]);
$todayDueCollections = $objQuery->index("SELECT COALESCE(SUM(Amount),0) AS total FROM trx_customercollection WHERE TxnDate=? AND IsDeleted=0", [$today]);
$todayCashCollections = $objQuery->index("SELECT COALESCE(SUM(Amount),0) AS total FROM trx_cashcollection WHERE CollectionDate=? AND IsDeleted=0", [$today]);
$todayOthersCollections = $objQuery->index("SELECT COALESCE(SUM(Amount),0) AS total FROM trx_otherscollection WHERE CollectionDate=? AND IsDeleted=0", [$today]);
$todayExpense = $objQuery->index("SELECT COALESCE(SUM(Amount),0) AS total FROM trx_expense WHERE ExpenseDate=? AND IsDeleted=0", [$today]);
$todayCustomerDue = $objQuery->index("SELECT COALESCE(SUM(DueAmount),0) AS total FROM trx_customerdue WHERE TxnDate=? AND IsDeleted=0", [$today]);
$todaySupplierPaid = $objQuery->index("SELECT COALESCE(SUM(Amount),0) AS total FROM trx_supplierpayment WHERE PaymentDate=? AND IsDeleted=0", [$today]);
$todayFuelPurchased = $objQuery->index("SELECT COALESCE(SUM(TotalAmount),0) AS total FROM trx_fuelpurchase WHERE PurchaseDate=? AND IsDeleted=0", [$today]);

$collectionTotal = ($todayDueCollections[0]->total ?? 0) + ($todayCashCollections[0]->total ?? 0) + ($todayOthersCollections[0]->total ?? 0);
$recentTransactions = $objQuery->index("
    SELECT TxnDate, TxnType, Details, Amount FROM (
        SELECT ReadingDate AS TxnDate, 'Nozzle Sale' AS TxnType, CONCAT('Nozzle #', NozzleID) AS Details, SalesAmt AS Amount, CreatedAt FROM trx_nozzlereading WHERE IsDeleted=0
        UNION ALL
        SELECT TxnDate, 'Customer Due', CONCAT('Customer #', CustomerID), DueAmount, CreatedAt FROM trx_customerdue WHERE IsDeleted=0
        UNION ALL
        SELECT TxnDate, 'Due Collection', CONCAT('Customer #', CustomerID), Amount, CreatedAt FROM trx_customercollection WHERE IsDeleted=0
        UNION ALL
        SELECT ExpenseDate, 'Expense', COALESCE(ParticularID, 'Expense'), Amount, CreatedAt FROM trx_expense WHERE IsDeleted=0
        UNION ALL
        SELECT PurchaseDate, 'Fuel Purchase', COALESCE(InvoiceNo, 'Purchase'), TotalAmount, CreatedAt FROM trx_fuelpurchase WHERE IsDeleted=0
    ) recent
    ORDER BY CreatedAt DESC
    LIMIT 8
");

// Get company info
$company = getCompanyInfo();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h4 class="card-title mb-1">
                    <i class="fas fa-tachometer-alt text-primary me-2"></i>
                    Welcome to <?php echo $company->CompanyName ?? 'FuelDeskPro'; ?>
                </h4>
                <p class="text-muted mb-0">
                    <i class="fas fa-map-marker-alt me-1"></i> <?php echo $company->Address ?? 'Amilaish, Satkania, Chattogram.'; ?>
                    <span class="ms-3"><i class="fas fa-phone me-1"></i> <?php echo $company->MobileNo ?? ''; ?></span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body text-center">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-gas-pump"></i>
                </div>
                <h3 class="mb-0"><?php echo $fuelCount; ?></h3>
                <small class="text-muted">Fuel Types</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body text-center">
                <div class="stat-icon bg-success">
                    <i class="fas fa-faucet"></i>
                </div>
                <h3 class="mb-0"><?php echo $nozCount; ?></h3>
                <small class="text-muted">Nozzles</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body text-center">
                <div class="stat-icon bg-info">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h3 class="mb-0"><?php echo $empCount; ?></h3>
                <small class="text-muted">Employees</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body text-center">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-user-friends"></i>
                </div>
                <h3 class="mb-0"><?php echo $custCount; ?></h3>
                <small class="text-muted">Customers</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body text-center">
                <div class="stat-icon bg-danger">
                    <i class="fas fa-truck"></i>
                </div>
                <h3 class="mb-0"><?php echo $suppCount; ?></h3>
                <small class="text-muted">Suppliers</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body text-center">
                <div class="stat-icon bg-secondary">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="mb-0"><?php echo $shCount; ?></h3>
                <small class="text-muted">Shareholders</small>
            </div>
        </div>
    </div>
</div>

<!-- Basic Reports -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body">
                <small class="text-muted"><?php echo t('Today Sales'); ?></small>
                <h5 class="mb-0"><?php echo $currencySymbol . ' ' . formatCurrency($todaySales[0]->total ?? 0); ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body">
                <small class="text-muted"><?php echo t('Today Collections'); ?></small>
                <h5 class="mb-0"><?php echo $currencySymbol . ' ' . formatCurrency($collectionTotal); ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body">
                <small class="text-muted"><?php echo t('Today Expense'); ?></small>
                <h5 class="mb-0"><?php echo $currencySymbol . ' ' . formatCurrency($todayExpense[0]->total ?? 0); ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body">
                <small class="text-muted"><?php echo t('Customer Due'); ?></small>
                <h5 class="mb-0"><?php echo $currencySymbol . ' ' . formatCurrency($todayCustomerDue[0]->total ?? 0); ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body">
                <small class="text-muted"><?php echo t('Supplier Paid'); ?></small>
                <h5 class="mb-0"><?php echo $currencySymbol . ' ' . formatCurrency($todaySupplierPaid[0]->total ?? 0); ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body">
                <small class="text-muted"><?php echo t('Fuel Purchased'); ?></small>
                <h5 class="mb-0"><?php echo $currencySymbol . ' ' . formatCurrency($todayFuelPurchased[0]->total ?? 0); ?></h5>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
        <h6 class="mb-0"><i class="fas fa-chart-line text-primary me-2"></i><?php echo t('Reports'); ?> - Recent Activity</h6>
    </div>
    <div class="card-body">
        <table class="table table-sm table-hover datatable">
            <thead><tr><th>Date</th><th>Type</th><th>Details</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                <?php foreach ($recentTransactions as $txn): ?>
                    <tr>
                        <td><?php echo formatDate($txn->TxnDate); ?></td>
                        <td><?php echo htmlspecialchars($txn->TxnType); ?></td>
                        <td><?php echo htmlspecialchars($txn->Details); ?></td>
                        <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($txn->Amount); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Links -->
 <!--
<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0"><i class="fas fa-database text-primary me-2"></i><?php echo t('Master Data'); ?></h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6"><a href="modules/master_data/shift.php" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-clock"></i> <?php echo t('Shift'); ?></a></div>
                    <div class="col-6"><a href="modules/master_data/fuel_type.php" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-oil-can"></i> <?php echo t('Fuel Type'); ?></a></div>
                    <div class="col-6"><a href="modules/master_data/tank.php" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-tint"></i> <?php echo t('Tanks'); ?></a></div>
                    <div class="col-6"><a href="modules/master_data/dispenser.php" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-gas-pump"></i> <?php echo t('Dispenser'); ?></a></div>
                    <div class="col-6"><a href="modules/master_data/nozzle.php" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-faucet"></i> <?php echo t('Nozzles'); ?></a></div>
                    <div class="col-6"><a href="modules/master_data/employee.php" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-user-tie"></i> <?php echo t('Employee'); ?></a></div>
                    <div class="col-6"><a href="modules/master_data/customer.php" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-user-friends"></i> <?php echo t('Customer'); ?></a></div>
                    <div class="col-6"><a href="modules/master_data/supplier.php" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-truck"></i> <?php echo t('Supplier'); ?></a></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0"><i class="fas fa-cogs text-success me-2"></i><?php echo t('Operations'); ?></h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6"><a href="modules/operations/nozzle_reading.php" class="btn btn-outline-success btn-sm w-100"><i class="fas fa-tachometer-alt"></i> <?php echo t('Nozzle Readings'); ?></a></div>
                    <div class="col-6"><a href="modules/operations/customer_due.php" class="btn btn-outline-success btn-sm w-100"><i class="fas fa-file-invoice"></i> <?php echo t('Customer Dues'); ?></a></div>
                    <div class="col-6"><a href="modules/operations/due_collection.php" class="btn btn-outline-success btn-sm w-100"><i class="fas fa-hand-holding-usd"></i> <?php echo t('Due Collections'); ?></a></div>
                    <div class="col-6"><a href="modules/operations/cash_collection.php" class="btn btn-outline-success btn-sm w-100"><i class="fas fa-money-bill-wave"></i> <?php echo t('Cash Collections'); ?></a></div>
                    <div class="col-6"><a href="modules/operations/others_collection.php" class="btn btn-outline-success btn-sm w-100"><i class="fas fa-coins"></i> <?php echo t('Others Collections'); ?></a></div>
                    <div class="col-6"><a href="modules/operations/expense.php" class="btn btn-outline-success btn-sm w-100"><i class="fas fa-receipt"></i> <?php echo t('Expense'); ?></a></div>
                    <div class="col-6"><a href="modules/operations/fuel_purchase.php" class="btn btn-outline-success btn-sm w-100"><i class="fas fa-shopping-cart"></i> <?php echo t('Fuel Purchase'); ?></a></div>
                    <div class="col-6"><a href="modules/operations/fuel_price_adjustment.php" class="btn btn-outline-success btn-sm w-100"><i class="fas fa-sliders-h"></i> <?php echo t('Fuel Price Adjustment'); ?></a></div>
                    <div class="col-6"><a href="modules/operations/supplier_payment.php" class="btn btn-outline-success btn-sm w-100"><i class="fas fa-credit-card"></i> <?php echo t('Supplier Payment'); ?></a></div>
                    <div class="col-6"><a href="modules/operations/tank_reading.php" class="btn btn-outline-success btn-sm w-100"><i class="fas fa-chart-line"></i> <?php echo t('Tank Readings'); ?></a></div>
                </div>
            </div>
        </div>
    </div>
</div>
-->
<?php require_once __DIR__ . '/includes/footer.php'; ?>
