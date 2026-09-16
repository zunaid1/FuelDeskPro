<?php
/**
 * FuelDeskPro - Monthly Summary LPG API Endpoint
 * 
 * Generates daily breakdown and monthly aggregates for LPG operations.
 * Strictly uses $data = $objQuery->index($sql, $params) and returns PDO::FETCH_OBJ results.
 * 
 * @package FuelDeskPro
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Input parsing
$monthInput = trim($_REQUEST['month'] ?? $_REQUEST['month_year'] ?? date('Y-m'));

if (preg_match('/^(\d{4})-(\d{2})/', $monthInput, $matches)) {
    $yearMonth = $matches[1] . '-' . $matches[2];
} else {
    $time = strtotime($monthInput);
    $yearMonth = ($time !== false) ? date('Y-m', $time) : date('Y-m');
}

$startDate = $yearMonth . '-01';
$endDate   = date('Y-m-t', strtotime($startDate));

// Retrieve default LPG Commission Rate if not provided
$defaultCommission = 8.00;
$lpgType = $objQuery->index("SELECT CommissionRate, SellingRate FROM mst_fueltype WHERE FuelName='LPG' OR FuelCode='LPG' OR FuelTypeID=101 LIMIT 1");
if (!empty($lpgType)) {
    $defaultCommission = (float)$lpgType[0]->CommissionRate;
}

$commissionRate = isset($_REQUEST['commission_rate']) && $_REQUEST['commission_rate'] !== '' 
    ? (float)$_REQUEST['commission_rate'] 
    : $defaultCommission;

$adjPlus  = isset($_REQUEST['adj_plus'])  ? max(0, (float)$_REQUEST['adj_plus'])  : 0.00;
$adjMinus = isset($_REQUEST['adj_minus']) ? max(0, (float)$_REQUEST['adj_minus']) : 0.00;

// Fetch aggregated transaction data for the period using standard SELECT queries with $objQuery->index($sql, $params)

// 1. LPG Nozzle Sales
$sqlNozzle = "SELECT 
    nr.ReadingDate AS TxnDate,
    MIN(nr.PreviousGeneral) AS Opening,
    SUM(nr.SaleQuantity) AS ConsumedLiter,
    AVG(nr.SellingRate) AS Rate,
    SUM(nr.SalesAmt) AS SalesAmount
FROM trx_nozzlereading nr
JOIN mst_nozzle n ON nr.NozzleID = n.NozzleID
JOIN mst_fueltype ft ON n.FuelTypeID = ft.FuelTypeID
WHERE (ft.FuelName = 'LPG' OR ft.FuelCode = 'LPG' OR ft.FuelTypeID = 101)
  AND nr.ReadingDate BETWEEN ? AND ?
  AND nr.IsActive = 1 AND nr.IsDeleted = 0
GROUP BY nr.ReadingDate";
$nozzleRows = $objQuery->index($sqlNozzle, [$startDate, $endDate]);

// 2. Customer Due Collections
$sqlDueColl = "SELECT TxnDate, SUM(Amount) AS DueCollection
FROM trx_customercollection
WHERE TxnDate BETWEEN ? AND ? AND IsActive = 1 AND IsDeleted = 0
GROUP BY TxnDate";
$dueCollRows = $objQuery->index($sqlDueColl, [$startDate, $endDate]);

// 3. Cash Collections
$sqlCashColl = "SELECT CollectionDate AS TxnDate, SUM(Amount) AS CashCollection
FROM trx_cashcollection
WHERE CollectionDate BETWEEN ? AND ? AND IsActive = 1 AND IsDeleted = 0
GROUP BY CollectionDate";
$cashCollRows = $objQuery->index($sqlCashColl, [$startDate, $endDate]);

// 4. Others Collections
$sqlOthersColl = "SELECT CollectionDate AS TxnDate, SUM(Amount) AS OthersCollection
FROM trx_otherscollection
WHERE CollectionDate BETWEEN ? AND ? AND IsActive = 1 AND IsDeleted = 0
GROUP BY CollectionDate";
$othersCollRows = $objQuery->index($sqlOthersColl, [$startDate, $endDate]);

// 5. Customer Credit Sales
$sqlDueSales = "SELECT TxnDate, SUM(TotalAmount) AS DueSales
FROM trx_customerdue
WHERE TxnDate BETWEEN ? AND ? AND IsActive = 1 AND IsDeleted = 0
GROUP BY TxnDate";
$dueSalesRows = $objQuery->index($sqlDueSales, [$startDate, $endDate]);

// 6. Expenses
$sqlExpense = "SELECT ExpenseDate AS TxnDate, SUM(Amount) AS Expense
FROM trx_expense
WHERE ExpenseDate BETWEEN ? AND ? AND IsActive = 1 AND IsDeleted = 0
GROUP BY ExpenseDate";
$expenseRows = $objQuery->index($sqlExpense, [$startDate, $endDate]);

// 7. Supplier Payments / Bank Deposits
$sqlBank = "SELECT PaymentDate AS TxnDate, SUM(Amount) AS BankDeposit
FROM trx_supplierpayment
WHERE PaymentDate BETWEEN ? AND ? AND IsActive = 1 AND IsDeleted = 0
GROUP BY PaymentDate";
$bankRows = $objQuery->index($sqlBank, [$startDate, $endDate]);

// Index fetched rows by date for fast lookup
$nozzleMap = [];
foreach ($nozzleRows as $r) { $nozzleMap[$r->TxnDate] = $r; }

$dueCollMap = [];
foreach ($dueCollRows as $r) { $dueCollMap[$r->TxnDate] = (float)$r->DueCollection; }

$cashCollMap = [];
foreach ($cashCollRows as $r) { $cashCollMap[$r->TxnDate] = (float)$r->CashCollection; }

$othersCollMap = [];
foreach ($othersCollRows as $r) { $othersCollMap[$r->TxnDate] = (float)$r->OthersCollection; }

$dueSalesMap = [];
foreach ($dueSalesRows as $r) { $dueSalesMap[$r->TxnDate] = (float)$r->DueSales; }

$expenseMap = [];
foreach ($expenseRows as $r) { $expenseMap[$r->TxnDate] = (float)$r->Expense; }

$bankMap = [];
foreach ($bankRows as $r) { $bankMap[$r->TxnDate] = (float)$r->BankDeposit; }

// Generate daily breakdown for every calendar day in the month
$dailyData          = [];
$totalConsumedLiter = 0.0;
$totalSalesAmount   = 0.0;
$totalDueColl       = 0.0;
$totalCollection    = 0.0;
$totalDueSales      = 0.0;
$totalExpense       = 0.0;
$totalBankDeposit   = 0.0;

$daysInMonth = (int)date('t', strtotime($startDate));
for ($d = 1; $d <= $daysInMonth; $d++) {
    $dateStr = sprintf('%s-%02d', $yearMonth, $d);

    $opening       = isset($nozzleMap[$dateStr]) ? (float)$nozzleMap[$dateStr]->Opening : 0.0;
    $consumedLiter = isset($nozzleMap[$dateStr]) ? (float)$nozzleMap[$dateStr]->ConsumedLiter : 0.0;
    $rate          = isset($nozzleMap[$dateStr]) ? (float)$nozzleMap[$dateStr]->Rate : 0.0;
    $salesAmount   = isset($nozzleMap[$dateStr]) ? (float)$nozzleMap[$dateStr]->SalesAmount : 0.0;

    $dueColl       = $dueCollMap[$dateStr] ?? 0.0;
    $cashColl      = $cashCollMap[$dateStr] ?? 0.0;
    $othersColl    = $othersCollMap[$dateStr] ?? 0.0;
    $collection    = $cashColl + $dueColl + $othersColl;

    $dueSales      = $dueSalesMap[$dateStr] ?? 0.0;
    $expense       = $expenseMap[$dateStr] ?? 0.0;
    $bankDeposit   = $bankMap[$dateStr] ?? 0.0;

    $totalConsumedLiter += $consumedLiter;
    $totalSalesAmount   += $salesAmount;
    $totalDueColl       += $dueColl;
    $totalCollection    += $collection;
    $totalDueSales      += $dueSales;
    $totalExpense       += $expense;
    $totalBankDeposit   += $bankDeposit;

    $dailyData[] = (object)[
        'Date'            => $dateStr,
        'Opening'         => $opening,
        'ConsumedLiter'   => $consumedLiter,
        'Rate'            => $rate,
        'SalesAmount'     => $salesAmount,
        'DueColl'         => $dueColl,
        'TotalCollection' => $collection,
        'DueSales'        => $dueSales,
        'Expense'         => $expense,
        'BankDeposit'     => $bankDeposit
    ];
}

// Profit calculation logic
$salesCommission = round($totalConsumedLiter * $commissionRate, 2);
$netProfit       = round($salesCommission - $totalExpense + $adjPlus - $adjMinus, 2);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status'      => 'success',
    'month'       => $yearMonth,
    'month_label' => date('F Y', strtotime($startDate)),
    'data'        => $dailyData,
    'totals'      => [
        'total_consumed_liter' => round($totalConsumedLiter, 3),
        'total_sales_amount'   => round($totalSalesAmount, 2),
        'total_due_coll'       => round($totalDueColl, 2),
        'total_collection'     => round($totalCollection, 2),
        'total_due_sales'      => round($totalDueSales, 2),
        'total_expense'        => round($totalExpense, 2),
        'total_bank_deposit'   => round($totalBankDeposit, 2)
    ],
    'summary'     => [
        'commission_rate'  => round($commissionRate, 2),
        'sales_commission' => $salesCommission,
        'total_expense'    => round($totalExpense, 2),
        'adj_plus'         => round($adjPlus, 2),
        'adj_minus'        => round($adjMinus, 2),
        'net_profit'       => $netProfit
    ]
]);
exit;
