<?php
/**
 * FuelDeskPro - Monthly Expense Summary Item Wise Report
 *
 * Monthly expense report broken down by category and particular, showing
 * individual expense entries with date, particular, category, amount, and
 * remarks. Includes summary cards and per-category totals.
 *
 * @package FuelDeskPro
 */

$pageTitle = 'Monthly Expense Summary Item Wise';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// --- Input handling ---
$monthYear   = $_GET['month_year'] ?? date('Y-m');
$categoryId  = $_GET['category_id'] ?? '';
$generate    = isset($_GET['generate']);

// Derive start and end dates from month-year
$startDate = $monthYear . '-01';
$endDate   = $monthYear . '-' . date('t', strtotime($startDate));

// --- Queries ---
$categoryList = [];
$expenseData  = [];
$summaryData  = [];

// Load expense categories for filter dropdown
$categoryList = $objQuery->index(
    "SELECT ExpenseCategoryID, CategoryNameEN FROM mst_expensecategory WHERE IsActive = 1 AND IsDeleted = 0 ORDER BY CategoryNameEN"
);

if ($generate) {
    // 1. Summary by Category
    $sqlSummary = "SELECT
        ec.ExpenseCategoryID,
        ec.CategoryNameEN,
        SUM(e.Amount) AS TotalAmount,
        COUNT(*)      AS TotalRecords
    FROM trx_expense e
    LEFT JOIN mst_expenseparticular ep ON e.ParticularID = ep.ParticularID
    LEFT JOIN mst_expensecategory ec   ON ep.ExpenseCategoryID = ec.ExpenseCategoryID
    WHERE e.ExpenseDate BETWEEN ? AND ?
      AND e.IsActive = 1 AND e.IsDeleted = 0";
    $summaryParams = [$startDate, $endDate];

    if (!empty($categoryId)) {
        $sqlSummary .= " AND ec.ExpenseCategoryID = ?";
        $summaryParams[] = $categoryId;
    }
    $sqlSummary .= " GROUP BY ec.ExpenseCategoryID ORDER BY ec.CategoryNameEN";
    $summaryData = $objQuery->index($sqlSummary, $summaryParams);

    // 2. Detailed expense entries
    $sqlDetail = "SELECT
        e.ExpenseDate,
        e.ParticularID,
        ep.ParticularNameEN,
        ec.CategoryNameEN,
        e.Amount,
        pm.MethodName,
        e.ReferenceNo,
        e.Remarks
    FROM trx_expense e
    LEFT JOIN mst_expenseparticular ep ON e.ParticularID = ep.ParticularID
    LEFT JOIN mst_expensecategory ec   ON ep.ExpenseCategoryID = ec.ExpenseCategoryID
    LEFT JOIN cfg_paymentmethod pm     ON e.PaymentMethodID = pm.PaymentMethodID
    WHERE e.ExpenseDate BETWEEN ? AND ?
      AND e.IsActive = 1 AND e.IsDeleted = 0";
    $detailParams = [$startDate, $endDate];

    if (!empty($categoryId)) {
        $sqlDetail .= " AND ec.ExpenseCategoryID = ?";
        $detailParams[] = $categoryId;
    }
    $sqlDetail .= " ORDER BY e.ExpenseDate DESC, ec.CategoryNameEN";
    $expenseData = $objQuery->index($sqlDetail, $detailParams);
}

// Helper to safely get a value
function _val3($arr, $key = 'TotalAmount', $default = 0) {
    if (!empty($arr) && isset($arr[0]->$key)) {
        return (float)$arr[0]->$key;
    }
    return $default;
}

// Calculate grand total
$grandTotal = 0;
if (!empty($summaryData)) {
    foreach ($summaryData as $row) {
        $grandTotal += (float)$row->TotalAmount;
    }
}

// Month name
$monthName = date('F Y', strtotime($startDate));
?>
<div class="table-container">
    <div class="table-header">
        <h5><i class="fas fa-receipt text-primary me-2"></i>Monthly Expense Summary Item Wise</h5>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Select Month <span class="text-danger">*</span></label>
            <input type="month" name="month_year" class="form-control" value="<?php echo $monthYear; ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Category (Optional)</label>
            <select name="category_id" class="form-select select2">
                <option value="">All Categories</option>
                <?php foreach ($categoryList as $cat): ?>
                    <option value="<?php echo $cat->ExpenseCategoryID; ?>" <?php echo ($categoryId == $cat->ExpenseCategoryID) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat->CategoryNameEN); ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
                        <h5>Monthly Expense Summary Item Wise</h5>
                        <p class="mb-0"><strong>Month:</strong> <?php echo $monthName; ?></p>
                        <p class="mb-0"><strong>Period:</strong> <?php echo formatDate($startDate); ?> to <?php echo formatDate($endDate); ?></p>
                        <p class="mb-0"><strong>Generated:</strong> <?php echo date('d-m-Y H:i:s'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Grand Total Expense</h6>
                        <h4 class="text-danger"><?php echo $currencySymbol . ' ' . formatCurrency($grandTotal); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Total Categories</h6>
                        <h4 class="text-primary"><?php echo count($summaryData); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h6 class="text-muted">Total Entries</h6>
                        <h4 class="text-info"><?php echo count($expenseData); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. Summary by Category -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-pie-chart me-2"></i>Expense Summary by Category</h6></div>
            <div class="card-body">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>SL</th>
                            <th>Expense Category</th>
                            <th>Total Amount</th>
                            <th>Records</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($summaryData)): $sl = 1; foreach ($summaryData as $row):
                            $percentage = $grandTotal > 0 ? ($row->TotalAmount / $grandTotal) * 100 : 0;
                            ?>
                            <tr>
                                <td><?php echo $sl++; ?></td>
                                <td><?php echo htmlspecialchars($row->CategoryNameEN ?? 'Uncategorized'); ?></td>
                                <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($row->TotalAmount); ?></td>
                                <td class="text-center"><?php echo $row->TotalRecords; ?></td>
                                <td class="text-end"><?php echo number_format($percentage, 2); ?>%</td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" class="text-center text-muted">No expense data found for this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="2" class="text-end">Grand Total:</th>
                            <th class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($grandTotal); ?></th>
                            <th class="text-center"><?php echo count($expenseData); ?></th>
                            <th class="text-end">100%</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- 2. Item Wise Breakdown -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-list me-2"></i>Expense Item Wise Breakdown</h6></div>
            <div class="card-body">
                <table class="table table-bordered table-hover datatable">
                    <thead class="table-light">
                        <tr>
                            <th>SL</th>
                            <th>Date</th>
                            <th>Particular</th>
                            <th>Category</th>
                            <th class="text-end">Amount</th>
                            <th>Payment Method</th>
                            <th>Reference No</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($expenseData)): $sl = 1; foreach ($expenseData as $row): ?>
                            <tr>
                                <td><?php echo $sl++; ?></td>
                                <td><?php echo formatDate($row->ExpenseDate); ?></td>
                                <td><?php echo htmlspecialchars($row->ParticularNameEN ?? $row->ParticularID); ?></td>
                                <td><?php echo htmlspecialchars($row->CategoryNameEN ?? 'Uncategorized'); ?></td>
                                <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($row->Amount); ?></td>
                                <td><?php echo htmlspecialchars($row->MethodName ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row->ReferenceNo ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row->Remarks ?? ''); ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center text-muted">No expense entries found for this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. Summary Footer (per category totals) -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Category Totals Summary</h6></div>
            <div class="card-body">
                <table class="table table-bordered">
                    <?php if (!empty($summaryData)): foreach ($summaryData as $row): ?>
                        <tr>
                            <th style="width: 30%;"><?php echo htmlspecialchars($row->CategoryNameEN ?? 'Uncategorized'); ?></th>
                            <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($row->TotalAmount); ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="2" class="text-center text-muted">No data available.</td></tr>
                    <?php endif; ?>
                    <tr class="table-light fw-bold">
                        <th>Grand Total Expense</th>
                        <td class="text-end"><?php echo $currencySymbol . ' ' . formatCurrency($grandTotal); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    <?php elseif (!$generate): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Please select a month (and optionally a category) and click "Generate Report" to view the expense summary.
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
