<?php
/**
 * FuelDeskPro - Monthly Collection & Expense Summary Report
 *
 * Monthly summary showing total collections (cash, customer, others, fuel sales)
 * and total expenses (fuel purchase, operational expenses by category) with
 * a net balance calculation.
 *
 * @package FuelDeskPro
 */

$pageTitle = 'Monthly Collection & Expense Summary';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// --- Input handling ---
$monthYear = $_GET['month_year'] ?? date('Y-m');
$generate  = isset($_GET['generate']);

// Derive start and end dates from month-year
$startDate = $monthYear . '-01';
$endDate   = $monthYear . '-' . date('t', strtotime($startDate));

// --- Queries ---
$salesData      = [];
$cashColl       = [];
$custColl       = [];
$othersColl     = [];
$expenseData    = [];
$purchaseData   = [];

if ($generate) {
    // 1. Fuel Sales (from nozzle readings)
    $sqlSales = "SELECT
        ft.FuelName,
        SUM(nr.SaleQuantity) AS TotalQty,
        SUM(nr.SalesAmt)     AS TotalAmount,
        SUM(nr.CommissionAmt) AS TotalCommission
    FROM trx_nozzlereading nr
    JOIN mst_nozzle n ON nr.NozzleID = n.NozzleID
    JOIN mst_fueltype ft ON n.FuelTypeID = ft.FuelTypeID
    WHERE nr.ReadingDate BETWEEN ? AND ?
      AND nr.IsActive = 1 AND nr.IsDeleted = 0
    GROUP BY ft.FuelTypeID
    ORDER BY ft.FuelName";
    $salesData = $objQuery->index($sqlSales, [$startDate, $endDate]);

    // 2a. Cash Collection
    $sqlCash = "SELECT
        SUM(Amount) AS TotalAmount,
        COUNT(*)    AS TotalRecords
    FROM trx_cashcollection
    WHERE CollectionDate BETWEEN ? AND ?
      AND IsActive = 1 AND IsDeleted = 0";
    $cashColl = $objQuery->index($sqlCash, [$startDate, $endDate]);

    // 2b. Customer Collection
    $sqlCustColl = "SELECT
        SUM(cc.Amount) AS TotalAmount,
        COUNT(*)       AS TotalRecords
    FROM trx_customercollection cc
    WHERE cc.TxnDate BETWEEN ? AND ?
      AND cc.IsActive = 1 AND cc.IsDeleted = 0";
    $custColl = $objQuery->index($sqlCustColl, [$startDate, $endDate]);

    // 2c. Others Collection
    $sqlOthers = "SELECT
        SUM(oc.Amount) AS TotalAmount,
        COUNT(*)       AS TotalRecords
    FROM trx_otherscollection oc
    WHERE oc.CollectionDate BETWEEN ? AND ?
      AND oc.IsActive = 1 AND oc.IsDeleted = 0";
    $othersColl = $objQuery->index($sqlOthers, [$startDate, $endDate]);

    // 3. Expenses (grouped by category)
    $sqlExpense = "SELECT
        ec.CategoryNameEN,
        SUM(e.Amount) AS TotalAmount,
        COUNT(*)      AS TotalRecords
    FROM trx_expense e
    LEFT JOIN mst_expenseparticular ep ON e.ParticularID = ep.ParticularID
    LEFT JOIN mst_expensecategory ec   ON ep.ExpenseCategoryID = ec.ExpenseCategoryID
    WHERE e.ExpenseDate BETWEEN ? AND ?
      AND e.IsActive = 1 AND e.IsDeleted = 0
    GROUP BY ec.ExpenseCategoryID
    ORDER BY ec.CategoryNameEN";
    $expenseData = $objQuery->index($sqlExpense, [$startDate, $endDate]);

    // 4. Fuel Purchase
    $sqlPurchase = "SELECT
        ft.FuelName,
        s.SupplierName,
        SUM(fp.Quantity)   AS TotalQty,
        SUM(fp.Amount)     AS TotalAmount,
        SUM(fp.TotalAmount) AS GrandTotal
    FROM trx_fuelpurchase fp
    JOIN mst_fueltype ft ON fp.FuelTypeID = ft.FuelTypeID
    JOIN mst_supplier s  ON fp.SupplierID = s.SupplierID
    WHERE fp.PurchaseDate BETWEEN ? AND ?
      AND fp.IsActive = 1 AND fp.IsDeleted = 0
    GROUP BY ft.FuelTypeID, s.SupplierID
    ORDER BY ft.FuelName";
    $purchaseData = $objQuery->index($sqlPurchase, [$startDate, $endDate]);
}

// Helper to safely get a value
function _val2($arr, $key = 'TotalAmount', $default = 0) {
    if (!empty($arr) && isset($arr[0]->$key)) {
        return (float)$arr[0]->$key;
    }
    return $default;
}

// Calculate totals
$totalSales       = _val2($salesData);
$totalCashColl    = _val2($cashColl);
$totalCustColl    = _val2($custColl);
$totalOthersColl  = _val2($othersColl);
$totalCollections = $totalCashColl + $totalCustColl + $totalOthersColl + $totalSales;
$totalExpenses    = _val2($expenseData);
$totalPurchases   = _val2($purchaseData, 'GrandTotal');
$grandTotalExpenses = $totalExpenses + $totalPurchases;
$netBalance       = $totalCollections - $grandTotalExpenses;

// Month name
$monthName = date('F Y', strtotime($startDate));
?>
<div class="table-container">
    <div class="table-header">
        <h5><i class="fas fa-chart-line text-primary me-2"></i>Monthly Collection & Expense Summary</h5>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-5">
            <label class="form-label">Select Month <span class="text-danger">*</span></label>
            <input type="month" name="month_year" class="form-control" value="<?php echo $monthYear; ?>" required>
        </div>
        <div class="col-md-7 d-flex align-items-end">
            <button type="submit" name="generate" class="btn btn-primary me-2"><i class="fas fa-search"></i> Generate Report</button>
            <button type="button" class="btn btn-success" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
        </div>
    </form>

    <?php if ($generate): ?>
        <!-- Report Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4><?php echo htmlspecialchars($companyName); ?></h4>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($company->Address ?? ''); ?></p>
                        <p class="text-muted mb-0">Mobile: <?php echo htmlspecialchars($company->MobileNo ?? ''); ?></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <h5>Monthly Collection & Expense Summary</h5>
                        <p class="mb-0"><strong>Month:</strong> <?php echo $monthName; ?></p>
                        <p class="mb-0"><strong>Period:</strong> <?php echo formatDate($startDate); ?> to <?php echo formatDate($endDate); ?></p>
                        <p class="mb-0"><strong>Generated:</strong> <?php echo date('d-m-Y H:i:s'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Total Collections</h6>
                        <h4 class="text-success"><?php echo $currencySymbol . ' ' . formatCurrency($totalCollections); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Total Expenses</h6>
                        <h4 class="text-danger"><?php echo $currencySymbol . ' ' . formatCurrency($grandTotalExpenses); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Fuel Sales</h6>
                        <h4 class="text-primary"><?php echo $currencySymbol . ' ' . formatCurrency($totalSales); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Net Balance</h6>
                        <h4 class="<?php echo $netBalance >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $currencySymbol . ' ' . formatCurrency($netBalance); ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. Collection Details -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-wallet me-2"></i>Collection Details</h6></div>
            <div class="card-body">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>SL</th>
                            <th>Collection Type</th>
                            <th>Total Amount</th>
                            <th>Records</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Fuel Sales (Nozzle Reading)</td>
                            <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($totalSales); ?></td>
                            <td class="text-center">-</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Cash Collection</td>
                            <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($totalCashColl); ?></td>
                            <td class="text-center"><?php echo $cashColl[0]->TotalRecords ?? 0; ?></td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Customer Due Collection</td>
                            <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($totalCustColl); ?></td>
                            <td class="text-center"><?php echo $custColl[0]->TotalRecords ?? 0; ?></td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>Others Collection</td>
                            <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($totalOthersColl); ?></td>
                            <td class="text-center"><?php echo $othersColl[0]->TotalRecords ?? 0; ?></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="2" class="text-end">Total Collections:</th>
                            <th class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($totalCollections); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- 2. Expense Details -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Expense Details</h6></div>
            <div class="card-body">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>SL</th>
                            <th>Expense Category</th>
                            <th>Total Amount</th>
                            <th>Records</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($expenseData)): $sl = 1; foreach ($expenseData as $row): ?>
                            <tr>
                                <td><?php echo $sl++; ?></td>
                                <td><?php echo htmlspecialchars($row->CategoryNameEN ?? 'Uncategorized'); ?></td>
                                <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($row->TotalAmount); ?></td>
                                <td class="text-center"><?php echo $row->TotalRecords; ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" class="text-center text-muted">No operational expense data found.</td></tr>
                        <?php endif; ?>
                        <tr>
                            <td>-</td>
                            <td>Fuel Purchase</td>
                            <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($totalPurchases); ?></td>
                            <td class="text-center">-</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="2" class="text-end">Total Expenses:</th>
                            <th class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($grandTotalExpenses); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- 3. Balance Summary -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Balance Summary</h6></div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 30%;">Total Collections</th>
                        <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($totalCollections); ?></td>
                    </tr>
                    <tr>
                        <th>Total Expenses (Operational + Purchase)</th>
                        <td class="text-end text-danger">(-) <?php echo $currencySymbol . ' ' . formatCurrency($grandTotalExpenses); ?></td>
                    </tr>
                    <tr class="table-light fw-bold">
                        <th>Net Balance (Collections - Expenses)</th>
                        <td class="text-end <?php echo $netBalance >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $currencySymbol . ' ' . formatCurrency($netBalance); ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    <?php elseif (!$generate): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Please select a month and click "Generate Report" to view the summary.
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
