<?php
/**
 * FuelDeskPro - Final Submit Entry Handler (AJAX)
 * 
 * Handles Final Submit (Day Closing) and Re-open operations for daily statements.
 * 
 * @package FuelDeskPro
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(false, 'Authentication required!');
}

ensureFinalSubmitTableExists();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'final_submit':
        handleFinalSubmit();
        break;
    case 'reopen':
        handleReopen();
        break;
    default:
        jsonResponse(false, 'Invalid action!');
}

/**
 * Handle Final Submit for a date
 */
function handleFinalSubmit()
{
    global $objQuery;

    $statementDate = sanitize($_POST['statement_date'] ?? '');
    $remarks       = sanitize($_POST['remarks'] ?? '');

    if (empty($statementDate)) {
        jsonResponse(false, 'Statement Date is required!');
    }

    $formattedDate = date('Y-m-d', strtotime($statementDate));
    $userId = getUserId() ?: 1;

    // Check if record exists
    $existing = $objQuery->index("SELECT * FROM trx_finalsubmit WHERE StatementDate = ?", [$formattedDate]);

    if (!empty($existing)) {
        if (intval($existing[0]->IsClosed) === 1) {
            jsonResponse(false, 'এই তারিখের হিসাবটি ইতোমধ্যে ক্লোজ করা হয়েছে!');
        }
    }

    // 1. Validation: Check if nozzle readings exist for statement date
    $nozzleCheck = $objQuery->index("SELECT COUNT(*) AS cnt FROM trx_nozzlereading WHERE ReadingDate = ? AND IsDeleted = 0", [$formattedDate]);
    $nozzleCount = !empty($nozzleCheck) ? intval($nozzleCheck[0]->cnt) : 0;

    if ($nozzleCount === 0) {
        jsonResponse(false, 'নজেল রিডিং খালি রয়েছে! নজেল রিডিং এন্ট্রি না করা পর্যন্ত ফাইন্যাল সাবমিট করা যাবে না!');
    }

    // 2. Validation: Check if tank readings exist for statement date
    $tankCount = 0;
    $checkReadingTable = $objQuery->index("SHOW TABLES LIKE 'trx_tankreading'");
    if (!empty($checkReadingTable)) {
        $tankCheck = $objQuery->index("SELECT COUNT(*) AS cnt FROM trx_tankreading WHERE ReadingDate = ? AND IsDeleted = 0", [$formattedDate]);
        if (!empty($tankCheck)) {
            $tankCount += intval($tankCheck[0]->cnt);
        }
    }

    $checkDipTable = $objQuery->index("SHOW TABLES LIKE 'trx_tankdip'");
    if (!empty($checkDipTable)) {
        $tankDipCheck = $objQuery->index("SELECT COUNT(*) AS cnt FROM trx_tankdip WHERE DipDate = ? AND IsDeleted = 0", [$formattedDate]);
        if (!empty($tankDipCheck)) {
            $tankCount += intval($tankDipCheck[0]->cnt);
        }
    }

    if ($tankCount === 0) {
        jsonResponse(false, 'ট্যাংক রিডিং খালি রয়েছে! ট্যাংক রিডিং এন্ট্রি না করা পর্যন্ত ফাইন্যাল সাবমিট করা যাবে না!');
    }

    // 3. Validation: Check if Closing Balance is 0
    $salesData = $objQuery->index("SELECT SUM(SalesAmt) AS TotalSales FROM trx_nozzlereading WHERE ReadingDate = ? AND IsDeleted = 0", [$formattedDate]);
    $totalSalesAmount = !empty($salesData) ? floatval($salesData[0]->TotalSales) : 0;

    $custCollData = $objQuery->index("SELECT SUM(Amount) AS TotalCustColl FROM trx_customercollection WHERE TxnDate = ? AND IsDeleted = 0", [$formattedDate]);
    $totalCustColl = !empty($custCollData) ? floatval($custCollData[0]->TotalCustColl) : 0;

    $othersCollData = $objQuery->index("SELECT SUM(Amount) AS TotalOthersColl FROM trx_otherscollection WHERE CollectionDate = ? AND IsDeleted = 0", [$formattedDate]);
    $totalOthersColl = !empty($othersCollData) ? floatval($othersCollData[0]->TotalOthersColl) : 0;

    $totalDailyCollection = $totalSalesAmount + $totalCustColl + $totalOthersColl;

    $expData = $objQuery->index("SELECT SUM(Amount) AS TotalExp FROM trx_expense WHERE ExpenseDate = ? AND IsDeleted = 0", [$formattedDate]);
    $totalExpenses = !empty($expData) ? floatval($expData[0]->TotalExp) : 0;

    $cashData = $objQuery->index("SELECT SUM(Amount) AS TotalCash FROM trx_cashcollection WHERE CollectionDate = ? AND IsDeleted = 0", [$formattedDate]);
    $totalCashCollected = !empty($cashData) ? floatval($cashData[0]->TotalCash) : 0;

    $creditSalesList = $objQuery->index("SELECT DueAmount, TotalAmount FROM trx_customerdue WHERE TxnDate = ? AND IsDeleted = 0", [$formattedDate]);
    $totalCreditSales = 0;
    foreach ($creditSalesList as $cs) {
        $totalCreditSales += floatval($cs->DueAmount > 0 ? $cs->DueAmount : $cs->TotalAmount);
    }

    $closingBalance = round($totalDailyCollection - $totalExpenses - $totalCreditSales - $totalCashCollected);

    if (abs($closingBalance) > 0.001) {
        jsonResponse(false, 'অবশিষ্ট স্থিত টাকা (Closing Balance) ৳০.০০ না হওয়া পর্যন্ত ফাইন্যাল সাবমিট করা যাবে না!');
    }

    if (!empty($existing)) {
        $sql = "UPDATE trx_finalsubmit SET 
                    IsClosed = 1, 
                    SubmittedAt = NOW(), 
                    SubmittedBy = ?, 
                    Remarks = ?,
                    UpdatedAt = NOW()
                WHERE StatementDate = ?";
        $params = [$userId, $remarks, $formattedDate];
        $objQuery->inUpDel($sql, $params);
    } else {
        $sql = "INSERT INTO trx_finalsubmit (StatementDate, IsClosed, SubmittedAt, SubmittedBy, Remarks) 
                VALUES (?, 1, NOW(), ?, ?)";
        $params = [$formattedDate, $userId, $remarks];
        $objQuery->inUpDel($sql, $params);
    }

    jsonResponse(true, 'হিসাবটি সফলভাবে ফাইন্যাল সাবমিট (ক্লোজ) করা হয়েছে!', [
        'statement_date' => $formattedDate,
        'submitted_at'   => date('d-m-Y h:i A')
    ]);
}

/**
 * Handle Re-open for a date (Admin action)
 */
function handleReopen()
{
    global $objQuery;

    $statementDate = sanitize($_POST['statement_date'] ?? '');

    if (empty($statementDate)) {
        jsonResponse(false, 'Statement Date is required!');
    }

    $formattedDate = date('Y-m-d', strtotime($statementDate));
    $userId = getUserId() ?: 1;

    $existing = $objQuery->index("SELECT * FROM trx_finalsubmit WHERE StatementDate = ?", [$formattedDate]);

    if (empty($existing)) {
        jsonResponse(false, 'এই তারিখের কোন ফাইন্যাল সাবমিট ডাটা পাওয়া যায়নি!');
    }

    $sql = "UPDATE trx_finalsubmit SET 
                IsClosed = 0, 
                ReopenedAt = NOW(), 
                ReopenedBy = ?,
                UpdatedAt = NOW()
            WHERE StatementDate = ?";
    $params = [$userId, $formattedDate];
    $objQuery->inUpDel($sql, $params);

    jsonResponse(true, 'হিসাবটি সফলভাবে পুনরায় Open করা হয়েছে!', [
        'statement_date' => $formattedDate,
        'reopened_at'    => date('d-m-Y h:i A')
    ]);
}
