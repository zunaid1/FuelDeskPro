<?php
/**
 * FuelDeskPro - Date To Date All Statement Report
 *
 * Comprehensive statement showing all transactions (sales, collections,
 * expenses, purchases, and stock) between two selected dates.
 *
 * @package FuelDeskPro
 */

$pageTitle = 'Date To Date All Statement';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// --- Input handling ---
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['get_date']   ?? date('Y-m-t');
$endDate   = $_GET['end_date']   ?? $endDate;

// Normalise to first/last day if only month-year given
if (strlen($startDate) === 7) { $startDate .= '-01'; }
if (strlen($endDate)   === 7) { $endDate   .= '-' . date('t', strtotime($endDate . '-01')); }

$generate = isset($_GET['generate']);

// --- Queries ---
$salesData     = [];
$collectionData = [];
$expenseData   = [];
$purchaseData  = [];
$stockData     = [];

if ($generate) {
    // 1. Fuel Sale Summary (per nozzle reading row)
    $sqlSales = "SELECT
        ft.FuelName,
        d.DisName AS TankPump,
        n.NozzleNo,
        nr.PreviousGeneral AS OpenG,
        nr.GeneralReading AS CloseG,
        nr.PreviousMaster AS OpenM,
        nr.MasterReading AS CloseM,
        nr.SaleQuantity AS Qty,
        CASE WHEN nr.SaleQuantity > 0 THEN ROUND(nr.SalesAmt / nr.SaleQuantity, 2) ELSE nr.SellingRate END AS Rate,
        nr.SalesAmt AS TotalAmount
    FROM trx_nozzlereading nr
    JOIN mst_nozzle n ON nr.NozzleID = n.NozzleID
    JOIN mst_dispenser d ON nr.DisID = d.DisID
    JOIN mst_fueltype ft ON n.FuelTypeID = ft.FuelTypeID
    WHERE nr.ReadingDate BETWEEN ? AND ?
      AND nr.IsActive = 1 AND nr.IsDeleted = 0
    ORDER BY ft.FuelName, d.DisName, n.NozzleNo";
    $salesData = $objQuery->index($sqlSales, [$startDate, $endDate]);

    // 2. Collection History / Withdraw from Station (Cash Collection detail rows)
    $sqlCash = "SELECT
        cc.CollectionDate AS TxnDate,
        cc.Amount,
        cc.Remarks,
        cc.CollectedByType AS Type,
        CASE
            WHEN cc.CollectedByType = 'Employee' THEN emp.NameEN
            WHEN cc.CollectedByType = 'Shareholder' THEN sh.NameEN
            ELSE 'Unknown'
        END AS CollectedByName
    FROM trx_cashcollection cc
    LEFT JOIN mst_employee emp ON cc.CollectedByType = 'Employee' AND cc.CollectedPersonID = emp.Id
    LEFT JOIN mst_shareholder sh ON cc.CollectedByType = 'Shareholder' AND cc.CollectedPersonID = sh.Id
    WHERE cc.CollectionDate BETWEEN ? AND ?
      AND cc.IsActive = 1 AND cc.IsDeleted = 0
    ORDER BY cc.CollectionDate ASC";
    $cashCollData = $objQuery->index($sqlCash, [$startDate, $endDate]);

    // 2b. Customer Collection (summary for totals)
    $sqlCustColl = "SELECT
        SUM(cc.Amount) AS TotalAmount,
        COUNT(*)       AS TotalRecords
    FROM trx_customercollection cc
    WHERE cc.TxnDate BETWEEN ? AND ?
      AND cc.IsActive = 1 AND cc.IsDeleted = 0";
    $custColl = $objQuery->index($sqlCustColl, [$startDate, $endDate]);

    // 2c. Others Collection (summary for totals)
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

    // 5. Tank Stock Summary (per tank: PrevStock, Purchase, Manual Stock IN, Sales, Manual Stock OUT, Current, percentages)
    $sqlStock = "SELECT
        t.TankName,
        ft.FuelName,
        t.Capacity,
        ROUND(t.Capacity * t.OpeningStockPercent / 100.0, 3) AS PrevStock,
        COALESCE((
            SELECT SUM(fp.Quantity)
            FROM trx_fuelpurchase fp
            WHERE fp.TankID = t.TankID
              AND fp.PurchaseDate BETWEEN ? AND ?
              AND fp.IsActive = 1 AND fp.IsDeleted = 0
        ), 0) AS Purchase,
        COALESCE((
            SELECT SUM(sa.Quantity)
            FROM trx_stockadjustment sa
            WHERE sa.TankID = t.TankID
              AND sa.AdjustmentType = 'Stock IN'
              AND sa.AdjustmentDate BETWEEN ? AND ?
              AND sa.IsActive = 1 AND sa.IsDeleted = 0
        ), 0) AS ManualStockIn,
        COALESCE((
            SELECT SUM(nr.SaleQuantity)
            FROM trx_nozzlereading nr
            JOIN mst_nozzle n ON nr.NozzleID = n.NozzleID
            WHERE n.TankGroupID = t.TankGroupID
              AND nr.ReadingDate BETWEEN ? AND ?
              AND nr.IsActive = 1 AND nr.IsDeleted = 0
        ), 0) AS Sales,
        COALESCE((
            SELECT SUM(sa.Quantity)
            FROM trx_stockadjustment sa
            WHERE sa.TankID = t.TankID
              AND sa.AdjustmentType = 'Stock OUT'
              AND sa.AdjustmentDate BETWEEN ? AND ?
              AND sa.IsActive = 1 AND sa.IsDeleted = 0
        ), 0) AS ManualStockOut
    FROM mst_tank t
    JOIN mst_fueltype ft ON t.FuelTypeID = ft.FuelTypeID
    WHERE t.IsActive = 1 AND t.IsDeleted = 0
    ORDER BY t.TankName";
    $stockData = $objQuery->index($sqlStock, [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate]);
}

// Helper to safely get a value
function _val($arr, $key = 'TotalAmount', $default = 0) {
    if (!empty($arr) && isset($arr[0]->$key)) {
        return (float)$arr[0]->$key;
    }
    return $default;
}
?>
<div class="table-container">
    <div class="table-header">
        <h5><i class="fas fa-file-invoice-dollar text-primary me-2"></i>Date To Date All Statement</h5>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Start Date <span class="text-danger">*</span></label>
            <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">End Date <span class="text-danger">*</span></label>
            <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>" required>
        </div>
        <div class="col-md-4 d-flex align-items-end">
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
                        <h5>Date To Date All Statement</h5>
                        <p class="mb-0"><strong>From:</strong> <?php echo formatDate($startDate); ?></p>
                        <p class="mb-0"><strong>To:</strong> <?php echo formatDate($endDate); ?></p>
                        <p class="mb-0"><strong>Generated:</strong> <?php echo date('d-m-Y H:i:s'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <?php
        // Sum all rows for multi-row result sets
        $totalSales = 0;
        $totalSalesQty = 0;
        if (!empty($salesData)) {
            foreach ($salesData as $row) {
                $totalSales += (float)$row->TotalAmount;
                $totalSalesQty += (float)$row->Qty;
            }
        }
        $avgRate = $totalSalesQty > 0 ? $totalSales / $totalSalesQty : 0;

        // Total cash withdrawals (sum of individual rows)
        $totalCashColl = 0;
        if (!empty($cashCollData)) {
            foreach ($cashCollData as $row) { $totalCashColl += (float)$row->Amount; }
        }
        $totalCustColl    = _val($custColl);
        $totalOthersColl  = _val($othersColl);
        $totalCollections = $totalCashColl + $totalCustColl + $totalOthersColl;

        $totalExpenses = 0;
        if (!empty($expenseData)) {
            foreach ($expenseData as $row) { $totalExpenses += (float)$row->TotalAmount; }
        }

        $totalPurchases = 0;
        if (!empty($purchaseData)) {
            foreach ($purchaseData as $row) { $totalPurchases += (float)$row->GrandTotal; }
        }

        $netBalance       = $totalSales - $totalCashColl - $totalExpenses - $totalPurchases;
        ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Total Sales</h6>
                        <h4 class="text-primary"><?php echo $currencySymbol . ' ' . formatCurrency($totalSales); ?></h4>
                    </div>
                </div>
            </div>
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
                        <h4 class="text-danger"><?php echo $currencySymbol . ' ' . formatCurrency($totalExpenses); ?></h4>
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

        <!-- 1. Fuel Sale Summary -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-gas-pump me-2"></i>Fuel Sale Summary</h6></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>SL</th>
                            <th>Fuel</th>
                            <th>Tank / Pump</th>
                            <th>Nozzle</th>
                            <th>Open G</th>
                            <th>Close G</th>
                            <th>Open M</th>
                            <th>Close M</th>
                            <th>Qty (L)</th>
                            <th>Rate</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($salesData)): $sl = 1; foreach ($salesData as $row): ?>
                            <tr>
                                <td><?php echo $sl++; ?></td>
                                <td><?php echo htmlspecialchars($row->FuelName); ?></td>
                                <td><?php echo htmlspecialchars($row->TankPump); ?></td>
                                <td class="text-center"><?php echo $row->NozzleNo; ?></td>
                                <td class="text-end"><?php echo formatCurrency($row->OpenG, 3); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row->CloseG, 3); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row->OpenM, 3); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row->CloseM, 3); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row->Qty, 3); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row->Rate, 2); ?></td>
                                <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($row->TotalAmount); ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="11" class="text-center text-muted">No sales data found in this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="8" class="text-end">Total:</th>
                            <th class="text-end"><?php echo formatCurrency($totalSalesQty, 3); ?></th>
                            <th class="text-end"><?php echo formatCurrency($avgRate, 2); ?></th>
                            <th class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($totalSales); ?></th>
                        </tr>
                    </tfoot>
                </table>
                </div>
            </div>
        </div>

        <!-- 2. Collection History / Withdraw from Station -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-wallet me-2"></i>Collection History / Withdraw from Station</h6></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>SL</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Collected By</th>
                            <th>Remarks</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cashCollData)): $sl = 1; foreach ($cashCollData as $row): ?>
                            <tr>
                                <td><?php echo $sl++; ?></td>
                                <td><?php echo formatDate($row->TxnDate); ?></td>
                                <td><?php echo htmlspecialchars($row->Type); ?></td>
                                <td><?php echo htmlspecialchars($row->CollectedByName); ?></td>
                                <td><?php echo htmlspecialchars($row->Remarks ?? ''); ?></td>
                                <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($row->Amount); ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" class="text-center text-muted">No cash withdrawal records found in this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="5" class="text-end">Total Withdrawn:</th>
                            <th class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($totalCashColl); ?></th>
                        </tr>
                    </tfoot>
                </table>
                </div>
            </div>
        </div>

        <!-- 3. Expenses -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Expenses Statement</h6></div>
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
                            <tr><td colspan="4" class="text-center text-muted">No expense data found in this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="2" class="text-end">Total Expenses:</th>
                            <th class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($totalExpenses); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- 4. Fuel Purchase -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Fuel Purchase Statement</h6></div>
            <div class="card-body">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>SL</th>
                            <th>Fuel Type</th>
                            <th>Supplier</th>
                            <th>Quantity (L)</th>
                            <th>Purchase Amount</th>
                            <th>Total (With Tax)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($purchaseData)): $sl = 1; foreach ($purchaseData as $row): ?>
                            <tr>
                                <td><?php echo $sl++; ?></td>
                                <td><?php echo htmlspecialchars($row->FuelName); ?></td>
                                <td><?php echo htmlspecialchars($row->SupplierName); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row->TotalQty, 3); ?></td>
                                <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($row->TotalAmount); ?></td>
                                <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($row->GrandTotal); ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" class="text-center text-muted">No purchase data found in this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="4" class="text-end">Total Purchases:</th>
                            <th class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($totalPurchases); ?></th>
                            <th class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($totalPurchases); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- 5. Tank Stock Summary -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-tint me-2"></i>Tank Stock Summary</h6></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>SL</th>
                            <th>Tank</th>
                            <th>Fuel</th>
                            <th>Opening (L)</th>
                            <th>Purchase IN (L)</th>
                            <th>Manual IN (L)</th>
                            <th>Sales OUT (L)</th>
                            <th>Manual OUT (L)</th>
                            <th>Current Stock (L)</th>
                            <th>Stock %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stockData)): $sl = 1; foreach ($stockData as $row): 
                            $prevStock   = (float)$row->PrevStock;
                            $purchase    = (float)$row->Purchase;
                            $manualIn    = (float)($row->ManualStockIn ?? 0);
                            $sales       = (float)$row->Sales;
                            $manualOut   = (float)($row->ManualStockOut ?? 0);
                            $current     = $prevStock + $purchase + $manualIn - $sales - $manualOut;
                            $stockPct    = $row->Capacity > 0 ? round(($current / $row->Capacity) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td><?php echo $sl++; ?></td>
                                <td><?php echo htmlspecialchars($row->TankName); ?></td>
                                <td><?php echo htmlspecialchars($row->FuelName); ?></td>
                                <td class="text-end"><?php echo formatCurrency($prevStock, 3); ?></td>
                                <td class="text-end text-success">+<?php echo formatCurrency($purchase, 3); ?></td>
                                <td class="text-end text-info">+<?php echo formatCurrency($manualIn, 3); ?></td>
                                <td class="text-end text-danger">-<?php echo formatCurrency($sales, 3); ?></td>
                                <td class="text-end text-warning">-<?php echo formatCurrency($manualOut, 3); ?></td>
                                <td class="text-end fw-bold"><?php echo formatCurrency($current, 3); ?></td>
                                <td class="text-center"><?php echo $stockPct; ?>%</td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="10" class="text-center text-muted">No stock data found in this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Net Balance Summary -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Net Position Summary</h6></div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 30%;">Total Sales</th>
                        <td class="text-end text-primary fw-bold"><?php echo $currencySymbol . ' ' . formatCurrency($totalSales); ?></td>
                    </tr>
                    <tr>
                        <th>Less: Cash Collection</th>
                        <td class="text-end text-danger">(-) <?php echo $currencySymbol . ' ' . formatCurrency($totalCashColl); ?></td>
                    </tr>
                    <tr>
                        <th>Less: Total Expenses</th>
                        <td class="text-end text-danger">(-) <?php echo $currencySymbol . ' ' . formatCurrency($totalExpenses); ?></td>
                    </tr>
                    <tr>
                        <th>Less: Total Purchases</th>
                        <td class="text-end text-danger">(-) <?php echo $currencySymbol . ' ' . formatCurrency($totalPurchases); ?></td>
                    </tr>
                    <tr class="table-light fw-bold">
                        <th>Net Balance (Sales - Cash Collection - Expenses - Purchases)</th>
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
            Please select a date range and click "Generate Report" to view the statement.
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
