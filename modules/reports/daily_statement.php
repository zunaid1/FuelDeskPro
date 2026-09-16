<?php
/**
 * FuelDeskPro - Daily Statement Report (দৈনিক আয়-ব্যয়ের বিবরণ)
 *
 * Daily Income & Expense Statement matching official station voucher format.
 * Features Bangla / English bilingual support.
 *
 * @package FuelDeskPro
 */

// --- Input & Language handling ---
$targetDate = $_GET['statement_date'] ?? date('Y-m-d');
$lang = $_GET['lang'] ?? 'bn';
if (!in_array($lang, ['bn', 'en'])) {
    $lang = 'bn';
}

$pageTitle = ($lang === 'en') ? 'Daily Statement' : 'দৈনিক আয়-ব্যয়ের বিবরণ';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Statement Close check
$isClosed = isStatementClosed($targetDate);
$closeInfo = getStatementCloseInfo($targetDate);

// Time handling: If statement is Final Submitted, use SubmittedAt time, otherwise fallback to GET param or current time
if ($isClosed && !empty($closeInfo->SubmittedAt)) {
    $targetTime = date('h:i A', strtotime($closeInfo->SubmittedAt));
} else {
    $targetTime = $_GET['statement_time'] ?? date('h:i A');
}

// Helper function for Bangla Date formatting
if (!function_exists('formatDateBangla')) {
    function formatDateBangla($dateStr) {
        if (empty($dateStr)) return '';
        $timestamp = strtotime($dateStr);
        $day = date('d', $timestamp);
        $monthNum = date('n', $timestamp);
        $year = date('Y', $timestamp);

        $banglaMonths = [
            1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
            5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
            9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর'
        ];

        $engDigits = ['0','1','2','3','4','5','6','7','8','9'];
        $bnDigits  = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];

        $dayBn = str_replace($engDigits, $bnDigits, $day);
        $yearBn = str_replace($engDigits, $bnDigits, $year);
        $monthBn = $banglaMonths[$monthNum] ?? '';

        return "{$dayBn}-{$monthBn}-{$yearBn}";
    }
}

if ($lang === 'en') {
    $formattedDateDisplay = date('d-M-Y', strtotime($targetDate));
} else {
    $formattedDateDisplay = formatDateBangla($targetDate);
}

// Language Labels Dictionary
if ($lang === 'en') {
    $lblStatementHeader = 'Daily Income & Expense Statement';
    $lblDate = 'Date :';
    $lblTime = 'Time :';
    $lblDay = 'Day :';
    $lblMonthlySales = 'Monthly Sales (L) :';
    $lblYesterdayStock = 'Yesterday Stock (%) :';
    $lblTodayStock = 'Latest Stock (%) :';
    $lblTodaySales = 'Today Sales (L) :';
    $dayNameDisplay = date('l', strtotime($targetDate));

    // Tables & Sections
    $lblNozzleCaption = 'Nozzle Reading';
    $lblGeneralMeter = 'General Meter Reading';
    $lblMasterMeter = 'Master Meter Reading';
    $thNozzle = 'Nozzle';
    $thCurrReadingG = 'Current';
    $thPrevReadingG = 'Previous';
    $thCurrReadingM = 'Current';
    $thPrevReadingM = 'Previous';
    $thSoldQty = 'Qty (L)';
    $thRate = 'Rate';
    $thAmount = 'Amount';
    $lblTotalNozzle = 'Total';
    $msgNoNozzleData = 'No Reading Data Available';

    $lblCollTitle = 'Collections & Other Income';
    $thSL = 'SL';
    $thCollParticular = 'Particular / Customer';
    $thPayMethod = 'Payment Method';
    $thCollAmount = 'Amount (TK)';
    $lblTotalCollSection = 'Total Collection:';

    $lblTodaysSaleSummary = 'Todays Sale :';
    $lblOthersCollSummary = 'Others Collection :';
    $lblTotalCollSummary = 'Total Collection :';

    $lblExpTitle = 'Expenses:';
    $thExpParticular = 'Description';
    $thExpAmount = 'Amount (TK)';
    $lblTotalExp = 'Total Expenses:';
    $msgNoExpData = 'No Expense Data Available';

    $lblCreditTitle = 'Credit Sales';
    $thCreditCustomer = 'Customer Name';
    $thCreditAmount = 'Due Amount (TK)';
    $lblTotalCredit = 'Total Credit Sales:';
    $msgNoCreditSales = 'No credit sales occurred';

    $lblCashTitle = 'Cash Collections';
    $thCashPerson = 'Description';
    $thCashAmount = 'Collected Amount (TK)';
    $lblTotalCash = 'Total Cash Collected:';
    $msgNoCashData = 'No Cash Collection Data Available';

    $lblRemarks = 'Remarks:';
    $msgNoSupplierPayment = 'No supplier payment occurred';
    $lblSummaryTitle = 'Daily Summary & Closing Balance';
    $lblTotalDailyCollSummary = 'Total Daily Collection';
    $lblTotalExpSummary = 'Total Expenses';
    $lblTotalCreditSummary = 'Total Credit Sales';
    $lblTotalCashSummary = 'Total Cash Collection (Collected by Shareholder / Employee)';
    $lblClosingBalance = 'Closing Balance';
    $lblClosedAlert = 'Statement for this date has been closed';
    $lblFinalizeBadge = 'Finalize';
    $lblDraftBadge = 'Draft';
} else {
    $lblStatementHeader = 'দৈনিক আয়-ব্যয়ের বিবরণ';
    $lblDate = 'তারিখ :';
    $lblTime = 'সময় :';
    $lblDay = 'বার :';
    $lblMonthlySales = 'এই মাসের বিক্রয়:';
    $lblYesterdayStock = 'গতকালের সর্বশেষ স্টক:';
    $lblTodayStock = 'সর্বশেষ স্টক:';
    $lblTodaySales = 'আজকের বিক্রয়:';

    $englishDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $banglaDays = ['রবিবার', 'সোমবার', 'মঙ্গলবার', 'বুধবার', 'বৃহস্পতিবার', 'শুক্রবার', 'শনিবার'];
    $dayNameDisplay = str_replace($englishDays, $banglaDays, date('l', strtotime($targetDate)));

    // Tables & Sections
    $lblNozzleCaption = 'নজেল রিডিং';
    $lblGeneralMeter = 'জেনারেল মিটার রিডিং';
    $lblMasterMeter = 'মাষ্টার মিটার রিডিং';
    $thNozzle = 'নজেল';
    $thCurrReadingG = 'বর্তমান';
    $thPrevReadingG = 'পূর্বের';
    $thCurrReadingM = 'বর্তমান';
    $thPrevReadingM = 'পূর্বের';
    $thSoldQty = 'পরিমাণ';
    $thRate = 'দর';
    $thAmount = 'টাকা';
    $lblTotalNozzle = 'মোট:';
    $msgNoNozzleData = 'কোন রিডিং তথ্য পাওয়া যায়নি';

    $lblCollTitle = 'বকেয়া আদায় / অন্যান্য খাতে আয়';
    $thSL = 'ক্রম.';
    $thCollParticular = 'বিবরণ';
    $thPayMethod = 'পেমেন্ট মেথড';
    $thCollAmount = 'টাকা';
    $lblTotalCollSection = 'সর্বমোট আয়:';

    $lblTodaysSaleSummary = 'আজকের বিক্রয়:';
    $lblOthersCollSummary = 'অন্যান্য কালেকশন:';
    $lblTotalCollSummary = 'সর্বমোট কালেকশন:';

    $lblExpTitle = 'ব্যয়ের বিবরণ';
    $thExpParticular = 'বিবরণ';
    $thExpAmount = 'টাকা';
    $lblTotalExp = 'মোট খরচ:';
    $msgNoExpData = 'কোন খরচের তথ্য পাওয়া যায়নি';

    $lblCreditTitle = 'বাকী লেনদেন:';
    $thCreditCustomer = 'গ্রাহকের নাম';
    $thCreditAmount = 'বাকীর পরিমাণ';
    $lblTotalCredit = 'মোট বাকী:';
    $msgNoCreditSales = 'বাকীতে লেনদেন হয়নি';

    $lblCashTitle = 'নগদ টাকার হিসাব';
    $thCashPerson = 'বিবরণ';
    $thCashAmount = 'সংগৃহীত টাকা';
    $lblTotalCash = 'মোট সংগৃহীত টাকা';
    $msgNoCashData = 'কোন ক্যাশ কালেকশন ডাটা নেই';

    $lblRemarks = 'মন্তব্য:';
    $msgNoSupplierPayment = 'সাপ্লায়ার পেমেন্ট হয়নি';
    $lblSummaryTitle = 'লেনদেনের সারসংক্ষেপ:';
    $lblTotalDailyCollSummary = 'আজকের সর্বমোট কালেকশন';
    $lblTotalExpSummary = 'মোট খরচ';
    $lblTotalCreditSummary = 'মোট বাকী';
    $lblTotalCashSummary = 'সর্বমোট সংগ্রহ';
    $lblClosingBalance = 'অবশিষ্ট স্থিত টাকা:';
    $lblClosedAlert = 'এই তারিখের হিসাবটি ইতোমধ্যে ক্লোজ করা হয়েছে';
    $lblFinalizeBadge = 'Finalize';
    $lblDraftBadge = 'Draft';
}

// --- Company Info ---
$company = getCompanyInfo();
if ($lang === 'en') {
    $companyNameDisplay = !empty($company->CompanyName) ? $company->CompanyName : ($company->CompanyNameBN ?? 'Sangu LPG Filling Station');
    $companyAddress = !empty($company->AddressEN) ? $company->AddressEN : (!empty($company->Address) ? $company->Address : 'Amilaish, Satkania, Chattogram.');
} else {
    $companyNameDisplay = !empty($company->CompanyNameBN) ? $company->CompanyNameBN : ($company->CompanyName ?? 'সাঙ্গু এল.পি.জি ফিলিং ষ্টেশন');
    $companyAddress = !empty($company->AddressBN) ? $company->AddressBN : (!empty($company->AddressEN) ? $company->AddressEN : (!empty($company->Address) ? $company->Address : 'আমিলাইশ, সাতকানিয়া, চট্টগ্রাম'));
}

// --- Data Queries ---

// 1. Nozzle Meter Readings for the date
$sqlNozzle = "SELECT 
    nr.*,
    n.NozzleName,
    n.NozzleNo,
    d.DisName,
    ft.FuelName,
    ft.SellingRate AS DefaultRate
FROM trx_nozzlereading nr
JOIN mst_nozzle n ON nr.NozzleID = n.NozzleID
JOIN mst_dispenser d ON nr.DisID = d.DisID
JOIN mst_fueltype ft ON n.FuelTypeID = ft.FuelTypeID
WHERE nr.ReadingDate = ? AND nr.IsActive = 1 AND nr.IsDeleted = 0
ORDER BY n.NozzleID ASC";
$nozzleReadings = $objQuery->index($sqlNozzle, [$targetDate]);

// Calculate totals from nozzle readings
$totalGeneralLiters = 0;
$totalMasterLiters = 0;
$totalSalesAmount = 0;
$unitSellingRate = 0;

foreach ($nozzleReadings as $nr) {
    $genDiff = floatval($nr->GeneralReading) - floatval($nr->PreviousGeneral);
    if ($genDiff < 0) $genDiff = floatval($nr->SaleQuantity);
    $totalGeneralLiters += $genDiff;

    $mastDiff = floatval($nr->MasterReading) - floatval($nr->PreviousMaster);
    if ($mastDiff < 0) $mastDiff = floatval($nr->SaleQuantity);
    $totalMasterLiters += $mastDiff;

    $totalSalesAmount += floatval($nr->SalesAmt);
    if ($nr->SellingRate > 0) {
        $unitSellingRate = floatval($nr->SellingRate);
    }
}

if ($unitSellingRate == 0 && $totalGeneralLiters > 0) {
    $unitSellingRate = $totalSalesAmount / $totalGeneralLiters;
}

// 1.1 Calculate Monthly Total Sales in Liters (from 1st day of month up to target date)
$monthStartDate = date('Y-m-01', strtotime($targetDate));
$sqlMonthlySales = "SELECT SUM(SaleQuantity) AS TotalMonthlySalesLiters, SUM(SalesAmt) AS TotalMonthlySalesAmt FROM trx_nozzlereading WHERE ReadingDate >= ? AND ReadingDate <= ? AND IsActive = 1 AND IsDeleted = 0";
$monthlySalesData = $objQuery->index($sqlMonthlySales, [$monthStartDate, $targetDate]);
$monthlyTotalSalesLiters = 0;
$monthlyTotalSalesAmt = 0;
if (!empty($monthlySalesData)) {
    if (isset($monthlySalesData[0]->TotalMonthlySalesLiters)) {
        $monthlyTotalSalesLiters = floatval($monthlySalesData[0]->TotalMonthlySalesLiters);
    }
    if (isset($monthlySalesData[0]->TotalMonthlySalesAmt)) {
        $monthlyTotalSalesAmt = floatval($monthlySalesData[0]->TotalMonthlySalesAmt);
    }
}

// 2. Tank Stock Readings
$sqlTank = "SELECT * FROM trx_tankreading WHERE ReadingDate = ? AND IsDeleted = 0 ORDER BY TankReadingID DESC LIMIT 1";
$tankReading = $objQuery->index($sqlTank, [$targetDate]);

$todayStockPercent = 0;
$todaySoldPercent = 0;

if (!empty($tankReading)) {
    $todayStockPercent = floatval($tankReading[0]->CurrentReadingPercent);
    $todaySoldPercent = floatval($tankReading[0]->TodaySoldPercent);
}

// Fetch Yesterday's (targetDate - 1 day) CurrentReadingPercent from trx_tankreading
$prevDate = date('Y-m-d', strtotime($targetDate . ' -1 day'));
$sqlPrevTank = "SELECT CurrentReadingPercent FROM trx_tankreading WHERE ReadingDate = ? AND IsDeleted = 0 ORDER BY TankReadingID DESC LIMIT 1";
$prevTank = $objQuery->index($sqlPrevTank, [$prevDate]);

$yesterdayStockPercent = 0;
if (!empty($prevTank) && isset($prevTank[0]->CurrentReadingPercent)) {
    $yesterdayStockPercent = floatval($prevTank[0]->CurrentReadingPercent);
} elseif (!empty($tankReading) && isset($tankReading[0]->PreviousReadingPercent)) {
    $yesterdayStockPercent = floatval($tankReading[0]->PreviousReadingPercent);
}

// 3. Daily Expenses (Joins trx_expense, mst_expenseparticular, mst_expensecategory, mst_employee)
$sqlExpense = "SELECT 
    e.*,
    COALESCE(ep.ParticularNameEN, emp.NameEN, sh.NameEN, e.ParticularID) AS ParticularNameEN,
    COALESCE(ep.ParticularNameBN, emp.NameBN, sh.NameBN, emp.NameEN, sh.NameEN, e.ParticularID) AS ParticularNameBN,
    COALESCE(ec.ExpenseCategoryID, ec_sal.ExpenseCategoryID, ec_sh.ExpenseCategoryID, 11) AS ExpenseCategoryID,
    COALESCE(ec.CategoryNameEN, ec_sal.CategoryNameEN, ec_sh.CategoryNameEN, 'Salary') AS CategoryNameEN,
    COALESCE(ec.CategoryNameBN, ec_sal.CategoryNameBN, ec_sh.CategoryNameBN, 'বেতন') AS CategoryNameBN
FROM trx_expense e
LEFT JOIN mst_expenseparticular ep 
    ON (e.ParticularID = ep.ParticularID OR e.ParticularID = CAST(ep.ExpenseParticularID AS CHAR))
    AND ep.IsDeleted = 0
LEFT JOIN mst_expensecategory ec 
    ON ep.ExpenseCategoryID = ec.ExpenseCategoryID 
    AND ec.IsDeleted = 0
LEFT JOIN mst_employee emp 
    ON (e.ParticularID = emp.EmployeeId OR e.ParticularID = CAST(emp.Id AS CHAR))
    AND emp.IsDeleted = 0
LEFT JOIN mst_shareholder sh 
    ON (e.ParticularID = sh.ShareHolderID OR e.ParticularID = CAST(sh.Id AS CHAR))
    AND sh.IsDeleted = 0
LEFT JOIN mst_expensecategory ec_sh 
    ON sh.ExpenseCategoryID = ec_sh.ExpenseCategoryID 
    AND ec_sh.IsDeleted = 0
LEFT JOIN mst_expensecategory ec_sal 
    ON (emp.Id IS NOT NULL OR emp.EmployeeId IS NOT NULL) 
    AND (ec_sal.ExpenseCategoryID = 11 OR ec_sal.CategoryNameEN = 'Salary')
    AND ec_sal.IsDeleted = 0
WHERE e.ExpenseDate = ? AND e.IsActive = 1 AND e.IsDeleted = 0
ORDER BY e.ExpenseID ASC";
$expenses = $objQuery->index($sqlExpense, [$targetDate]);

$totalExpenses = 0;
foreach ($expenses as $exp) {
    $totalExpenses += floatval($exp->Amount);
}

// 4. Cash Collections (Nogod Taka) - Fetch TitleEN, TitleBN for Shareholder
$sqlCash = "SELECT 
    cc.*,
    b.BankName,
    b.AccountName,
    b.AccountNumber,
    sh.TitleEN AS ShareholderTitleEN,
    sh.TitleBN AS ShareholderTitleBN,
    sh.NameEN AS ShareholderNameEN,
    sh.NameBN AS ShareholderNameBN,
    emp.NameEN AS EmployeeNameEN,
    emp.NameBN AS EmployeeNameBN
FROM trx_cashcollection cc
LEFT JOIN mst_employee emp ON cc.CollectedByType = 'Employee' AND cc.CollectedPersonID = emp.Id
LEFT JOIN mst_shareholder sh ON cc.CollectedByType = 'Shareholder' AND cc.CollectedPersonID = sh.Id
LEFT JOIN mst_bankaccount b ON cc.BankAccountID = b.BankAccountID
WHERE cc.CollectionDate = ? AND cc.IsActive = 1 AND cc.IsDeleted = 0
ORDER BY cc.CashCollectionID ASC";
$cashCollections = $objQuery->index($sqlCash, [$targetDate]);

$totalCashCollected = 0;
foreach ($cashCollections as $cc) {
    $totalCashCollected += floatval($cc->Amount);
}

// 5. Customer Dues (Credit Sales / বাকী লেনদেন)
$sqlCredit = "SELECT 
    cd.*,
    c.CustomerName
FROM trx_customerdue cd
LEFT JOIN mst_customer c ON cd.CustomerID = c.CustomerID
WHERE cd.TxnDate = ? AND cd.IsActive = 1 AND cd.IsDeleted = 0
ORDER BY cd.CustomerDueID ASC";
$creditSales = $objQuery->index($sqlCredit, [$targetDate]);

$totalCreditSales = 0;
foreach ($creditSales as $cs) {
    $totalCreditSales += floatval($cs->DueAmount > 0 ? $cs->DueAmount : $cs->TotalAmount);
}

// 6. Customer Collections & Others Collections (বকেয়া আদায়/ অন্যান্য খাতে আয়)
$sqlCustCollList = "SELECT cc.*, c.CustomerName, pm.MethodName 
                    FROM trx_customercollection cc 
                    LEFT JOIN mst_customer c ON cc.CustomerID = c.CustomerID 
                    LEFT JOIN cfg_paymentmethod pm ON cc.PaymentMethodID = pm.PaymentMethodID 
                    WHERE cc.TxnDate = ? AND cc.IsActive = 1 AND cc.IsDeleted = 0 
                    ORDER BY cc.CustomerCollectionID ASC";
$custCollectionsList = $objQuery->index($sqlCustCollList, [$targetDate]);

$sqlOthersCollList = "SELECT oc.*, ep.ParticularNameBN, ep.ParticularNameEN, pm.MethodName 
                      FROM trx_otherscollection oc 
                      LEFT JOIN mst_expenseparticular ep ON oc.ParticularID = ep.ExpenseParticularID 
                      LEFT JOIN cfg_paymentmethod pm ON oc.PaymentMethodID = pm.PaymentMethodID 
                      WHERE oc.CollectionDate = ? AND oc.IsActive = 1 AND oc.IsDeleted = 0 
                      ORDER BY oc.OthersCollectionID ASC";
$othersCollectionsList = $objQuery->index($sqlOthersCollList, [$targetDate]);

$totalCustColl = 0;
foreach ($custCollectionsList as $ccItem) {
    $totalCustColl += floatval($ccItem->Amount);
}

$totalOthersColl = 0;
foreach ($othersCollectionsList as $ocItem) {
    $totalOthersColl += floatval($ocItem->Amount);
}

$totalOtherIncome = $totalCustColl + $totalOthersColl;
$totalDailyCollection = $totalSalesAmount + $totalOtherIncome;
$closingBalance = round($totalDailyCollection - $totalExpenses - $totalCreditSales - $totalCashCollected);

// 7. Supplier Payments (সাপ্লায়ার পেমেন্ট)
$sqlSupplierPayment = "SELECT 
    sp.*,
    s.SupplierName,
    pm.MethodName
FROM trx_supplierpayment sp
LEFT JOIN mst_supplier s ON sp.SupplierID = s.SupplierID
LEFT JOIN cfg_paymentmethod pm ON sp.PaymentMethodID = pm.PaymentMethodID
WHERE sp.PaymentDate = ? AND sp.IsActive = 1 AND sp.IsDeleted = 0
ORDER BY sp.SupplierPaymentID ASC";
$supplierPayments = $objQuery->index($sqlSupplierPayment, [$targetDate]);

// 8. Fuel Purchases (পণ্য ক্রয়)
ensureFuelPurchaseTablesExist();
$sqlFuelPurchase = "SELECT 
    fp.*,
    s.SupplierName
FROM trx_fuelpurchase fp
LEFT JOIN mst_supplier s ON fp.SupplierID = s.SupplierID
WHERE fp.PurchaseDate = ? AND fp.IsActive = 1 AND fp.IsDeleted = 0
ORDER BY fp.FuelPurchaseID ASC";
$fuelPurchases = $objQuery->index($sqlFuelPurchase, [$targetDate]);
?>

<style>
/* Custom Styling matching the printed statement design */
.statement-card {
    background: #ffffff;
    border: none;
    padding: 8px 20px 20px 20px;
    font-family: 'SolaimanLipi', 'Nikosh', 'Kalpurush', 'Calibri', Arial, sans-serif;
    color: #000000;
    max-width: 950px;
    margin: 0 auto;
}

.table-statement {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
}

.table-statement th, 
.table-statement td {
    border: 1px solid #000000;
    padding: 4px 8px;
    font-size: 15px;
    vertical-align: middle;
    color: #000000;
}

.table-statement th {
    background-color: #f0f0f0;
    text-align: center;
    font-weight: bold;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

/* --- Top Info Bar Styling (Fluid Pill Badges / Chips Layout) --- */
.top-info-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    margin-bottom: 14px;
    background: transparent;
    border: none;
}

.top-info-item {
    display: inline-flex;
    align-items: center;
    padding: 5px 12px;
    border-radius: 6px;
    border: 1px solid #d0d0d0;
    font-size: 14px;
    white-space: nowrap;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}

.top-info-item.item-prevstock {
    background-color: #f3e8ff;
    border-color: #e9d5ff;
    color: #6b21a8;
}

.top-info-item.item-stock {
    background-color: #fef3c7;
    border-color: #fde68a;
    color: #92400e;
}

.top-info-item.item-sales {
    background-color: #ffe4e6;
    border-color: #fecdd3;
    color: #9f1239;
}

.top-info-item.item-monthly {
    background-color: #dbeafe;
    border-color: #bfdbfe;
    color: #1e40af;
}

.top-info-item .info-label {
    font-size: 14px;
    color: inherit;
    white-space: nowrap;
    margin-right: 5px;
}

.top-info-item .info-val {
    font-size: 14.5px;
    font-weight: 800;
    white-space: nowrap;
    color: inherit;
}

.val-danger { color: inherit; }
.val-purple { color: inherit; }
.val-warning { color: inherit; }
.val-primary { color: inherit; }

/* Mobile View Mode for Top Info Bar - Fluid Pill Chips */
#statement-print-area.mobile-view-mode .top-info-bar {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
    justify-content: center !important;
    align-items: center !important;
    gap: 10px !important;
    width: 100% !important;
    background: transparent !important;
    border: none !important;
    margin-bottom: 16px !important;
}

#statement-print-area.mobile-view-mode .top-info-item {
    display: inline-flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 8px 14px !important;
    border-radius: 8px !important;
    border: 1.5px solid !important;
    font-size: 17px !important;
    text-align: center !important;
    white-space: nowrap !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
}

#statement-print-area.mobile-view-mode .top-info-item .info-label,
#statement-print-area.mobile-view-mode .top-info-item .info-label strong {
    font-size: 17px !important;
    font-weight: 800 !important;
    margin-right: 5px !important;
    margin-bottom: 0 !important;
    color: inherit !important;
    white-space: nowrap !important;
}

#statement-print-area.mobile-view-mode .top-info-item .info-val {
    font-size: 18.5px !important;
    font-weight: 900 !important;
    color: inherit !important;
    white-space: nowrap !important;
}

@media print {
    html, body, #content-wrapper, #main-content, .wrapper {
        background: #ffffff !important;
        background-color: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    body * {
        visibility: hidden;
    }
    #sidebar, .top-header, .top-navbar, .no-print, .btn, .card-header, footer {
        display: none !important;
    }
    #statement-print-area, #statement-print-area * {
        visibility: visible;
    }
    #statement-print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
        background: #ffffff !important;
    }
    .statement-card {
        border: none !important;
        box-shadow: none !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        background: #ffffff !important;
    }
}

/* --- Mobile View Mode Styling (Senior Citizen / Extra Large Font Mode) --- */
#statement-print-area.mobile-view-mode {
    font-size: 22px !important;
    line-height: 1.6 !important;
    color: #000000 !important;
}

#statement-print-area.mobile-view-mode .statement-card {
    max-width: 100% !important;
    padding: 15px 12px !important;
}

#statement-print-area.mobile-view-mode h2 {
    font-size: 32px !important;
    font-weight: 900 !important;
    color: #000000 !important;
    margin-bottom: 6px !important;
}

#statement-print-area.mobile-view-mode p {
    font-size: 20px !important;
    color: #111111 !important;
    font-weight: 700 !important;
}

#statement-print-area.mobile-view-mode span {
    font-size: 23px !important;
    font-weight: 800 !important;
}

#statement-print-area.mobile-view-mode .table-statement {
    border: 2px solid #000000 !important;
    margin-bottom: 16px !important;
}

#statement-print-area.mobile-view-mode .table-statement th,
#statement-print-area.mobile-view-mode .table-statement td {
    padding: 10px 12px !important;
    font-size: 21px !important;
    border: 1.5px solid #000000 !important;
    color: #000000 !important;
    line-height: 1.6 !important;
}

#statement-print-area.mobile-view-mode .table-statement th {
    font-size: 22px !important;
    font-weight: 900 !important;
    background-color: #dbe4ee !important;
    color: #000000 !important;
}

/* Section Title Headers (Nozzle Reading, Expenses, etc.) */
#statement-print-area.mobile-view-mode .table-statement th[colspan] {
    font-size: 25px !important;
    font-weight: 900 !important;
    background-color: transparent !important;
    color: #000000 !important;
    padding: 10px 4px !important;
    border: none !important;
}

/* Table Footer / Totals */
#statement-print-area.mobile-view-mode .table-statement tfoot tr td {
    font-size: 22px !important;
    font-weight: 900 !important;
    background-color: #e2e8f0 !important;
}

/* Bold Values & Totals */
#statement-print-area.mobile-view-mode .font-weight-bold,
#statement-print-area.mobile-view-mode .fw-bold,
#statement-print-area.mobile-view-mode strong {
    font-weight: 800 !important;
}

/* Closing Balance & Summary */
#statement-print-area.mobile-view-mode .closing-balance-val {
    font-size: 27px !important;
    font-weight: 900 !important;
}

/* Remarks Section Extra Large Font */
#statement-print-area.mobile-view-mode .remarks-footer-section {
    font-size: 24px !important;
    line-height: 1.75 !important;
    margin-top: 15px !important;
}

#statement-print-area.mobile-view-mode .remarks-footer-section div {
    font-size: 24px !important;
    line-height: 1.75 !important;
}

#statement-print-area.mobile-view-mode .remarks-footer-section strong {
    font-size: 26px !important;
    font-weight: 900 !important;
}

#statement-print-area.mobile-view-mode .remarks-footer-section .remarks-title {
    font-size: 28px !important;
    font-weight: 900 !important;
    margin-bottom: 8px !important;
}

#statement-print-area.mobile-view-mode .remarks-footer-section .mb-1 {
    font-size: 24px !important;
    margin-bottom: 10px !important;
    line-height: 1.75 !important;
}

#statement-print-area.mobile-view-mode .remarks-footer-section i {
    font-size: 22px !important;
    margin-right: 6px !important;
}

/* --- Keep Nozzle Reading Section Original Format in Mobile View --- */
#statement-print-area.mobile-view-mode .nozzle-reading-table th:not([colspan="9"]),
#statement-print-area.mobile-view-mode .nozzle-reading-table td {
    font-size: 14.5px !important;
    padding: 4px 6px !important;
    line-height: 1.4 !important;
    border: 1px solid #000000 !important;
}

#statement-print-area.mobile-view-mode .nozzle-reading-table th:not([colspan="9"]) {
    font-size: 14.5px !important;
    font-weight: bold !important;
    background-color: #f0f0f0 !important;
}

#statement-print-area.mobile-view-mode .nozzle-reading-table th[colspan="9"],
.nozzle-reading-table th[colspan="9"] {
    font-size: 28px !important;
    font-weight: 900 !important;
    background: transparent !important;
    background-color: transparent !important;
    padding: 12px 4px 8px 4px !important;
    border: none !important;
    border-top: none !important;
    border-bottom: none !important;
    border-left: none !important;
    border-right: none !important;
}

#statement-print-area.mobile-view-mode .nozzle-reading-table tfoot tr td {
    font-size: 14.5px !important;
    font-weight: bold !important;
    background-color: #f0f0f0 !important;
}

/* --- Ultra-Large Font Size (32px - 36px) for Amount Numbers in Mobile View --- */
#statement-print-area.mobile-view-mode td.text-right,
#statement-print-area.mobile-view-mode td[style*="text-align: right"],
#statement-print-area.mobile-view-mode td[style*="text-align:right"],
#statement-print-area.mobile-view-mode .expenses-table td.text-right,
#statement-print-area.mobile-view-mode .cash-collections-table td.text-right,
#statement-print-area.mobile-view-mode .summary-table td:last-child,
#statement-print-area.mobile-view-mode .expenses-table tfoot td.text-right,
#statement-print-area.mobile-view-mode .cash-collections-table tfoot td.text-right,
#statement-print-area.mobile-view-mode .summary-table tfoot td {
    font-size: 32px !important;
    font-weight: 900 !important;
    letter-spacing: 0.5px !important;
}

/* Closing Balance Amount (Extra Huge 36px) */
#statement-print-area.mobile-view-mode .summary-table tfoot tr td:last-child,
#statement-print-area.mobile-view-mode .summary-table tfoot td.text-right {
    font-size: 36px !important;
    font-weight: 900 !important;
}

#statement-print-area.mobile-view-mode .expenses-table th[colspan],
#statement-print-area.mobile-view-mode .cash-collections-table th[colspan],
#statement-print-area.mobile-view-mode .summary-table th[colspan] {
    font-size: 28px !important;
    font-weight: 900 !important;
    padding: 12px 4px 8px 4px !important;
    border: none !important;
    background-color: transparent !important;
}

#statement-print-area.mobile-view-mode .expenses-table th,
#statement-print-area.mobile-view-mode .cash-collections-table th,
#statement-print-area.mobile-view-mode .summary-table th {
    font-size: 25px !important;
    font-weight: 900 !important;
}

#statement-print-area.mobile-view-mode .expenses-table tfoot td,
#statement-print-area.mobile-view-mode .cash-collections-table tfoot td,
#statement-print-area.mobile-view-mode .summary-table tfoot td {
    font-size: 28px !important;
    font-weight: 900 !important;
    padding: 14px 14px !important;
}

/* Closing Balance Amount (Extra Huge) */
#statement-print-area.mobile-view-mode .summary-table tfoot tr td:last-child {
    font-size: 34px !important;
    font-weight: 900 !important;
}

@media print {
    #statement-print-area.mobile-view-mode .table-statement th,
    #statement-print-area.mobile-view-mode .table-statement td {
        font-size: 20px !important;
        padding: 8px 10px !important;
    }
    #statement-print-area.mobile-view-mode .nozzle-reading-table th,
    #statement-print-area.mobile-view-mode .nozzle-reading-table td {
        font-size: 14px !important;
        padding: 4px 6px !important;
    }
    #statement-print-area.mobile-view-mode h2 {
        font-size: 28px !important;
    }
    #statement-print-area.mobile-view-mode span {
        font-size: 21px !important;
    }
}
</style>

<!-- Filter & Toolbar -->
<div class="card mb-4 no-print shadow-sm">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-center">
            <div class="col-md-3">
                <label class="form-label fw-bold"><i class="fas fa-calendar-alt me-1 text-primary"></i> <?php echo $lang === 'en' ? 'Select Date' : 'তারিখ'; ?></label>
                <input type="date" name="statement_date" class="form-control" value="<?php echo htmlspecialchars($targetDate); ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold"><i class="fas fa-clock me-1 text-primary"></i> <?php echo $lang === 'en' ? 'Time' : 'সময়'; ?></label>
                <input type="text" name="statement_time" class="form-control" value="<?php echo htmlspecialchars($targetTime); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold"><i class="fas fa-language me-1 text-primary"></i> <?php echo $lang === 'en' ? 'Language' : 'ভাষা (Language)'; ?></label>
                <select name="lang" id="statement_lang" class="form-select fw-bold" onchange="this.form.submit()">
                    <option value="bn" <?php echo $lang === 'bn' ? 'selected' : ''; ?>>বাংলা (Bangla)</option>
                    <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>English</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> <?php echo $lang === 'en' ? 'Generate' : 'রিপোর্ট দেখুন'; ?></button>
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-success dropdown-toggle fw-bold" id="btnPrintDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-print me-1"></i> <?php echo $lang === 'en' ? 'Print' : 'প্রিন্ট'; ?>
                    </button>
                    <ul class="dropdown-menu shadow" aria-labelledby="btnPrintDropdown">
                        <li>
                            <a class="dropdown-item fw-bold py-2" href="javascript:void(0)" onclick="printReport('a4')">
                                <i class="fas fa-file-alt text-primary me-2"></i> A4 | Official
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item fw-bold py-2" href="javascript:void(0)" onclick="printReport('mobile')">
                                <i class="fas fa-mobile-alt text-success me-2"></i> Mobile View
                            </a>
                        </li>
                    </ul>
                </div>
                <?php if ($isClosed): ?>
                    <span class="badge bg-danger fs-6 d-inline-flex align-items-center py-2 px-3" title="<?php echo $lblClosedAlert; ?>">
                        <i class="fas fa-lock me-1"></i> <?php echo $lang === 'en' ? 'Closed' : 'ক্লোজ করা হয়েছে'; ?>
                    </span>
                <?php else: ?>
                    <button type="button" id="btnFinalSubmit" class="btn btn-warning fw-bold text-dark">
                        <i class="fas fa-check-circle me-1"></i> Final Submit
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($isClosed): ?>
<div class="alert alert-danger shadow-sm border-2 border-danger text-center fw-bold fs-5 mb-3 rounded-3 py-2 no-print">
    <i class="fas fa-lock me-2"></i> <?php echo $lblClosedAlert; ?>
    <?php if (!empty($closeInfo->SubmittedAt)): ?>
        <span class="fs-6 text-dark ms-2">(<?php echo $lang === 'en' ? 'Submit Time:' : 'ফাইন্যাল সাবমিট সময়:'; ?> <?php echo date('d-m-Y h:i A', strtotime($closeInfo->SubmittedAt)); ?>)</span>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Statement Print Area -->
<div id="statement-print-area">
    <div class="statement-card shadow" style="position: relative;">
        
        <!-- Report Status Badge in Right Upper Corner -->
        <div style="position: absolute; top: 8px; right: 15px;" class="report-status-box">
            <?php if ($isClosed): ?>
                <span class="badge bg-success fs-6 py-2 px-3 fw-bold border border-success shadow-sm" style="font-size: 14px; letter-spacing: 0.5px;">
                    <i class="fas fa-check-circle me-1"></i> <?php echo $lblFinalizeBadge; ?>
                </span>
            <?php else: ?>
                <span class="badge bg-secondary fs-6 py-2 px-3 fw-bold border border-secondary shadow-sm" style="font-size: 14px; letter-spacing: 0.5px;">
                    <i class="fas fa-edit me-1"></i> <?php echo $lblDraftBadge; ?>
                </span>
            <?php endif; ?>
        </div>
        
        <?php if ($isClosed): ?>
        <div class="text-center fw-bold py-1 mb-2 no-print" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; font-size: 14px;">
            <i class="fas fa-lock me-1"></i> <?php echo $lblClosedAlert; ?>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="text-center mb-2" style="margin-top: 0;">
            <h2 class="fw-bold mb-0" style="font-size: 23px; font-weight: 700;"><?php echo htmlspecialchars($companyNameDisplay); ?></h2>
            <p class="mb-1 text-muted" style="font-size: 13.5px; margin-bottom: 4px;"><?php echo htmlspecialchars($companyAddress); ?></p>
            <div class="mt-1">
                <span style="font-size: 17.5px; font-weight: 700; border-bottom: 2px solid #000; padding-bottom: 2px; letter-spacing: 0.3px;">
                    <?php echo $lblStatementHeader; ?> &nbsp;—&nbsp; <span style="color: #000;"><?php echo $formattedDateDisplay; ?></span> <span style="font-size: 15.5px; font-weight: 600; color: #555;">(<?php echo $dayNameDisplay; ?>)</span>
                </span>
            </div>
        </div>

        <!-- Top Info Bar (Built with Divs / Flexbox Layout) -->
        <div class="top-info-bar mb-3">
            <!-- 1. গতকালের সর্বশেষ স্টক (%) -->
            <div class="top-info-item item-prevstock">
                <div class="info-label">
                    <i class="fas fa-history text-purple me-1"></i> <strong><?php echo $lblYesterdayStock; ?></strong>
                </div>
                <div class="info-val val-purple">
                    <?php echo number_format($yesterdayStockPercent, 0); ?>%
                </div>
            </div>

            <!-- 2. সর্বশেষ স্টক (%) -->
            <div class="top-info-item item-stock">
                <div class="info-label">
                    <i class="fas fa-gas-pump text-warning me-1"></i> <strong><?php echo $lblTodayStock; ?></strong>
                </div>
                <div class="info-val val-warning">
                    <?php echo number_format($todayStockPercent, 0); ?>%
                </div>
            </div>

            <!-- 3. আজকের বিক্রয় (লিটার) -->
            <div class="top-info-item item-sales">
                <div class="info-label">
                    <i class="fas fa-tint text-danger me-1"></i> <strong><?php echo $lblTodaySales; ?></strong>
                </div>
                <div class="info-val val-danger">
                    <?php echo number_format($totalGeneralLiters, 2); ?> L
                </div>
            </div>

            <!-- 4. এই মাসের মোট বিক্রয় -->
            <div class="top-info-item item-monthly">
                <div class="info-label">
                    <i class="fas fa-chart-line text-primary me-1"></i> <strong><?php echo $lblMonthlySales; ?></strong>
                </div>
                <div class="info-val val-primary">
                    <?php echo number_format($monthlyTotalSalesLiters, 2); ?> L
                </div>
            </div>
        </div>

        <!-- Nozzle Reading Sales Table -->
        <table class="table-statement nozzle-reading-table mb-3" style="margin-top: -12px;">
            <thead>
                <tr>
                    <th colspan="9" style="background-color: transparent; font-size: 14px; text-align: left; font-weight: bold; padding: 2px 0px 4px 2px; border: none;">
                        <?php echo $lblNozzleCaption; ?>
                    </th>
                </tr>
                <tr>
                    <th rowspan="2" style="width: 10%; vertical-align: middle; text-align: center;"><?php echo $thNozzle; ?></th>
                    <th colspan="3" style="text-align: center;"><?php echo $lblMasterMeter; ?></th>
                    <th colspan="3" style="text-align: center;"><?php echo $lblGeneralMeter; ?></th>
                    <th rowspan="2" style="width: 8%; vertical-align: middle; text-align: center;"><?php echo $thRate; ?></th>
                    <th rowspan="2" style="width: 12%; vertical-align: middle; text-align: center;"><?php echo $thAmount; ?></th>
                </tr>
                <tr>
                    <th style="width: 12%; text-align: center;"><?php echo $thCurrReadingM; ?></th>
                    <th style="width: 12%; text-align: center;"><?php echo $thPrevReadingM; ?></th>
                    <th style="width: 11%; text-align: center;"><?php echo $thSoldQty; ?></th>
                    <th style="width: 12%; text-align: center;"><?php echo $thCurrReadingG; ?></th>
                    <th style="width: 12%; text-align: center;"><?php echo $thPrevReadingG; ?></th>
                    <th style="width: 11%; text-align: center;"><?php echo $thSoldQty; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($nozzleReadings)):
                    foreach ($nozzleReadings as $nr):
                        $gCurr = floatval($nr->GeneralReading);
                        $gPrev = floatval($nr->PreviousGeneral);
                        $gDiff = $gCurr - $gPrev;
                        if ($gDiff < 0) $gDiff = floatval($nr->SaleQuantity);

                        $mCurr = floatval($nr->MasterReading);
                        $mPrev = floatval($nr->PreviousMaster);
                        $mDiff = $mCurr - $mPrev;
                        if ($mDiff < 0) $mDiff = floatval($nr->SaleQuantity);
                        
                        $rate = floatval($nr->SellingRate) > 0 ? floatval($nr->SellingRate) : $unitSellingRate;
                        $amt = floatval($nr->SalesAmt);
                        if ($amt <= 0 && $gDiff > 0) {
                            $amt = round($gDiff * $rate, 2);
                        }
                        
                        $nozzleName = !empty($nr->NozzleName) ? $nr->NozzleName : ('Nozzle ' . $nr->NozzleNo);
                ?>
                <tr>
                    <td class="text-center font-weight-bold"><?php echo htmlspecialchars($nozzleName); ?></td>
                    <td class="text-right"><?php echo number_format($mCurr, 2); ?></td>
                    <td class="text-right"><?php echo number_format($mPrev, 2); ?></td>
                    <td class="text-right font-weight-bold"><?php echo number_format($mDiff, 2); ?></td>
                    <td class="text-right"><?php echo number_format($gCurr, 2); ?></td>
                    <td class="text-right"><?php echo number_format($gPrev, 2); ?></td>
                    <td class="text-right font-weight-bold"><?php echo number_format($gDiff, 2); ?></td>
                    <td class="text-right"><?php echo number_format($rate, 2); ?></td>
                    <td class="text-right font-weight-bold"><?php echo number_format($amt, 2); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-2"><?php echo $msgNoNozzleData; ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="3" class="text-right" style="padding-right: 12px;"><?php echo $lblTotalNozzle; ?></td>
                    <td class="text-right"><?php echo number_format($totalMasterLiters, 2); ?></td>
                    <td colspan="2" class="text-right"></td>
                    <td class="text-right"><?php echo number_format($totalGeneralLiters, 2); ?></td>
                    <td class="text-right"><?php echo number_format($unitSellingRate, 2); ?></td>
                    <td class="text-right"><?php echo number_format($totalSalesAmount, 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Collection / Other Income Section (After Nozzle Reading) -->
        <?php 
        $hasCollData = (!empty($custCollectionsList) || !empty($othersCollectionsList));
        if ($hasCollData):
        ?>
        <table class="table-statement mb-3" style="margin-top: -10px;">
            <thead>
                <tr>
                    <th colspan="4" style="background-color: transparent; font-size: 14px; text-align: left; font-weight: bold; padding: 4px 0px 2px 2px; border: none;">
                        <?php echo $lblCollTitle; ?>
                    </th>
                </tr>
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <th style="width: 6%;"><?php echo $thSL; ?></th>
                    <th style="width: 58%;"><?php echo $thCollParticular; ?></th>
                    <th style="width: 20%;"><?php echo $thPayMethod; ?></th>
                    <th style="width: 16%; text-align: right; white-space: nowrap;"><?php echo $thCollAmount; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $slColl = 1;

                if (!empty($custCollectionsList)):
                    foreach ($custCollectionsList as $ccItem):
                        $prefix = ($lang === 'en') ? 'Due Collection - ' : 'বকেয়া আদায় - ';
                        $cName = $ccItem->CustomerName ?? (($lang === 'en') ? 'Customer' : 'গ্রাহক');
                ?>
                <tr>
                    <td class="text-center"><?php echo $slColl++; ?></td>
                    <td><?php echo $prefix; ?><strong><?php echo htmlspecialchars($cName); ?></strong></td>
                    <td class="text-center">
                        <?php 
                        $mName = $ccItem->MethodName ?? 'Cash';
                        if (stripos($mName, 'bank') !== false || stripos($mName, 'cheque') !== false || stripos($mName, 'online') !== false) {
                            echo '<i class="fas fa-university text-primary me-1" title="Bank Deposit"></i>';
                        }
                        echo htmlspecialchars($mName);
                        ?>
                    </td>
                    <td class="text-right font-weight-bold"><?php echo number_format($ccItem->Amount, 2); ?></td>
                </tr>
                <?php 
                    endforeach;
                endif;

                if (!empty($othersCollectionsList)):
                    foreach ($othersCollectionsList as $ocItem):
                        $prefix = ($lang === 'en') ? 'Other Income - ' : 'অন্যান্য আয় - ';
                        if ($lang === 'en') {
                            $pName = !empty($ocItem->ParticularNameEN) ? $ocItem->ParticularNameEN : (!empty($ocItem->ParticularNameBN) ? $ocItem->ParticularNameBN : ($ocItem->PartyName ?? 'Other Income'));
                        } else {
                            $pName = !empty($ocItem->ParticularNameBN) ? $ocItem->ParticularNameBN : (!empty($ocItem->ParticularNameEN) ? $ocItem->ParticularNameEN : ($ocItem->PartyName ?? 'অন্যান্য আয়'));
                        }
                ?>
                <tr>
                    <td class="text-center"><?php echo $slColl++; ?></td>
                    <td><?php echo $prefix; ?><strong><?php echo htmlspecialchars($pName); ?></strong><?php echo !empty($ocItem->PartyName) ? ' (' . htmlspecialchars($ocItem->PartyName) . ')' : ''; ?></td>
                    <td class="text-center">
                        <?php 
                        $mName = $ocItem->MethodName ?? 'Cash';
                        if (stripos($mName, 'bank') !== false || stripos($mName, 'cheque') !== false || stripos($mName, 'online') !== false) {
                            echo '<i class="fas fa-university text-primary me-1" title="Bank Deposit"></i>';
                        }
                        echo htmlspecialchars($mName);
                        ?>
                    </td>
                    <td class="text-right font-weight-bold"><?php echo number_format($ocItem->Amount, 2); ?></td>
                </tr>
                <?php 
                    endforeach;
                endif;
                ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td colspan="3" class="text-right"><?php echo $lblTotalCollSection; ?></td>
                    <td class="text-right"><?php echo number_format($totalOtherIncome, 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Total Collection Summary Table (Vertical Layout - Full Width, Right Aligned) -->
        <table class="table-statement mb-3" style="width: 100%; border: 1px solid #ccc;">
            <tr>
                <td style="width: 84%; text-align: right; font-weight: bold; background-color: #f8f9fa; padding: 5px 10px; border: 1px solid #ccc;">
                    <?php echo $lblTodaysSaleSummary; ?>
                </td>
                <td style="width: 16%; text-align: right; font-weight: bold; padding: 5px 10px; border: 1px solid #ccc;">
                    <?php echo number_format($totalSalesAmount, 2); ?>
                </td>
            </tr>
            <tr>
                <td style="text-align: right; font-weight: bold; background-color: #f8f9fa; padding: 5px 10px; border: 1px solid #ccc;">
                    <?php echo $lblOthersCollSummary; ?>
                </td>
                <td style="text-align: right; font-weight: bold; padding: 5px 10px; border: 1px solid #ccc;">
                    <?php echo number_format($totalOtherIncome, 2); ?>
                </td>
            </tr>
            <tr style="background-color: #e9ecef;">
                <td style="text-align: right; font-weight: bold; font-size: 15.5px; padding: 6px 10px; border: 1px solid #ccc;">
                    <?php echo $lblTotalCollSummary; ?>
                </td>
                <td style="text-align: right; font-weight: 800; font-size: 15.5px; padding: 6px 10px; border: 1px solid #ccc;">
                    <?php echo number_format($totalSalesAmount + $totalOtherIncome, 2); ?>
                </td>
            </tr>
        </table>
        <?php endif; ?>

        <!-- Expenses Section (Full Width) -->
        <table class="table-statement expenses-table mb-2" style="margin-top: -8px;">
            <thead>
                <tr>
                    <th colspan="3" style="background-color: transparent; font-size: 16px; text-align: left; font-weight: bold; padding: 2px 0px 2px 2px; border: none;">
                        <?php echo $lblExpTitle; ?>
                    </th>
                </tr>
                <tr style="background-color: #f8f9fa; font-weight: bold; font-size: 15px;">
                    <th style="width: 6%;"><?php echo $thSL; ?></th>
                    <th style="width: 78%;"><?php echo $thExpParticular; ?></th>
                    <th style="width: 16%; text-align: right; white-space: nowrap;"><?php echo $thExpAmount; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($expenses)):
                    $slExp = 1;
                    foreach ($expenses as $exp):
                        $groupName = ($lang === 'en') 
                            ? (!empty($exp->CategoryNameEN) ? $exp->CategoryNameEN : ($exp->CategoryNameBN ?? 'Other')) 
                            : (!empty($exp->CategoryNameBN) ? $exp->CategoryNameBN : ($exp->CategoryNameEN ?? 'অন্যান্য'));

                        $particularName = ($lang === 'en') 
                            ? (!empty($exp->ParticularNameEN) ? $exp->ParticularNameEN : ($exp->ParticularNameBN ?? '—')) 
                            : (!empty($exp->ParticularNameBN) ? $exp->ParticularNameBN : ($exp->ParticularNameEN ?? '—'));

                        $expRemarks = !empty($exp->Remarks) ? ' (' . htmlspecialchars($exp->Remarks) . ')' : '';
                ?>
                <tr>
                    <td class="text-center"><?php echo $slExp++; ?></td>
                    <td>
                        <strong class="text-secondary">[<?php echo htmlspecialchars($groupName); ?>]</strong> 
                        <?php echo htmlspecialchars($particularName) . $expRemarks; ?>
                    </td>
                    <td class="text-right font-weight-bold"><?php echo number_format($exp->Amount, 2); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="3" class="text-center text-muted py-2"><?php echo $msgNoExpData; ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="2" class="text-right"><?php echo $lblTotalExp; ?></td>
                    <td class="text-right"><?php echo number_format($totalExpenses, 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Credit Sales / Dues Section (Full Width, After Expenses) -->
        <?php if (!empty($creditSales)): ?>
        <table class="table-statement mb-2" style="margin-top: -8px;">
            <thead>
                <tr>
                    <th colspan="3" style="background-color: transparent; font-size: 18px; text-align: left; font-weight: bold; padding: 4px 0px 2px 2px; border: none;">
                        <?php echo $lblCreditTitle; ?>
                    </th>
                </tr>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <th style="width: 6%;"><?php echo $thSL; ?></th>
                    <th style="width: 78%;"><?php echo $thCreditCustomer; ?></th>
                    <th style="width: 16%; text-align: right; white-space: nowrap;"><?php echo $thCreditAmount; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $slDue = 1;
                foreach ($creditSales as $cs):
                    $cDueName = $cs->CustomerName ?? (($lang === 'en') ? 'Customer Due' : 'গ্রাহক বাকী');
                ?>
                <tr>
                    <td class="text-center"><?php echo $slDue++; ?></td>
                    <td><?php echo htmlspecialchars($cDueName); ?></td>
                    <td class="text-right font-weight-bold"><?php echo number_format($cs->DueAmount > 0 ? $cs->DueAmount : $cs->TotalAmount, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="2" class="text-right"><?php echo $lblTotalCredit; ?></td>
                    <td class="text-right"><?php echo number_format($totalCreditSales, 2); ?></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>

        <!-- Cash Collections Section (Full Width) -->
        <table class="table-statement cash-collections-table mb-2" style="margin-top: -8px;">
            <thead>
                <tr>
                    <th colspan="3" style="background-color: transparent; font-size: 16px; text-align: left; font-weight: bold; padding: 2px 0px 2px 2px; border: none;">
                        <?php echo $lblCashTitle; ?>
                    </th>
                </tr>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <th style="width: 6%;"><?php echo $thSL; ?></th>
                    <th style="width: 78%;"><?php echo $thCashPerson; ?></th>
                    <th style="width: 16%; text-align: right; white-space: nowrap;"><?php echo $thCashAmount; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($cashCollections)):
                    $slCash = 1;
                    foreach ($cashCollections as $cc):
                        $bankTag = !empty($cc->BankName) ? ' (<i class="fas fa-university text-primary me-1" title="Bank Deposit"></i>' . htmlspecialchars($cc->BankName) . ')' : '';
                        $remarksTag = !empty($cc->Remarks) ? (!empty($bankTag) ? ' — ' . htmlspecialchars($cc->Remarks) : ' (' . htmlspecialchars($cc->Remarks) . ')') : '';
                        
                        if ($cc->CollectedByType === 'Shareholder') {
                            $tEn = !empty($cc->ShareholderTitleEN) ? trim($cc->ShareholderTitleEN) : (!empty($cc->TitleEN) ? trim($cc->TitleEN) : '');
                            $tBn = !empty($cc->ShareholderTitleBN) ? trim($cc->ShareholderTitleBN) : (!empty($cc->TitleBN) ? trim($cc->TitleBN) : '');
                            $nEn = !empty($cc->ShareholderNameEN) ? trim($cc->ShareholderNameEN) : '';
                            $nBn = !empty($cc->ShareholderNameBN) ? trim($cc->ShareholderNameBN) : $nEn;
                            
                            if ($lang === 'en') {
                                $title = $tEn ?: $tBn;
                                $name = $nEn ?: $nBn;
                            } else {
                                $title = $tBn ?: $tEn;
                                $name = $nBn ?: $nEn;
                            }
                            $personName = trim(($title ? $title . ' ' : '') . $name);
                        } elseif ($cc->CollectedByType === 'Employee') {
                            $nEn = !empty($cc->EmployeeNameEN) ? trim($cc->EmployeeNameEN) : '';
                            $nBn = !empty($cc->EmployeeNameBN) ? trim($cc->EmployeeNameBN) : $nEn;
                            $personName = ($lang === 'en') ? $nEn : $nBn;
                        } else {
                            $personName = !empty($cc->PersonName) ? $cc->PersonName : 'N/A';
                        }
                ?>
                <tr>
                    <td class="text-center"><?php echo $slCash++; ?></td>
                    <td><?php echo htmlspecialchars($personName) . $bankTag . $remarksTag; ?></td>
                    <td class="text-right font-weight-bold"><?php echo number_format($cc->Amount, 2); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="3" class="text-center text-muted py-2"><?php echo $msgNoCashData; ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="2" class="text-right"><?php echo $lblTotalCash; ?></td>
                    <td class="text-right"><?php echo number_format($totalCashCollected, 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Summary & Closing Balance Section (Before Remarks) -->
        <table class="table-statement summary-table mb-2" style="margin-top: -8px;">
            <thead>
                <tr>
                    <th colspan="2" style="background-color: transparent; font-size: 16px; text-align: left; font-weight: bold; padding: 2px 0px 2px 2px; border: none;">
                        <?php echo $lblSummaryTitle; ?>
                    </th>
                </tr>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <th style="width: 84%; text-align: left; padding: 4px 8px;"><?php echo $lang === 'en' ? 'Particulars' : 'বিবরণ (Particulars)'; ?></th>
                    <th style="width: 16%; text-align: right; padding: 4px 8px; white-space: nowrap;"><?php echo $lang === 'en' ? 'Amount (TK)' : 'পরিমাণ (TK)'; ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: 600; padding: 4px 8px;">
                        <i class="fas fa-plus-circle text-success me-1"></i> <?php echo $lblTotalDailyCollSummary; ?>
                    </td>
                    <td class="text-right" style="text-align: right; font-weight: bold; padding: 4px 8px;">
                        <?php echo number_format($totalDailyCollection, 2); ?>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 600; padding: 4px 8px;">
                        <i class="fas fa-minus-circle text-danger me-1"></i> <?php echo $lblTotalExpSummary; ?>
                    </td>
                    <td class="text-right" style="text-align: right; font-weight: bold; padding: 4px 8px; color: #c0392b;">
                        <?php echo number_format($totalExpenses, 2); ?>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 600; padding: 4px 8px;">
                        <i class="fas fa-user-clock text-warning me-1"></i> <?php echo $lblTotalCreditSummary; ?>
                    </td>
                    <td class="text-right" style="text-align: right; font-weight: bold; padding: 4px 8px; color: #d35400;">
                        <?php echo number_format($totalCreditSales, 2); ?>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 600; padding: 4px 8px;">
                        <i class="fas fa-hand-holding-usd text-info me-1"></i> <?php echo $lblTotalCashSummary; ?>
                    </td>
                    <td class="text-right" style="text-align: right; font-weight: bold; padding: 4px 8px; color: #2980b9;">
                        <?php echo number_format($totalCashCollected, 2); ?>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr style="background-color: <?php echo $closingBalance > 0 ? '#fdf2f2' : '#f0f4f8'; ?>; font-weight: bold; font-size: 15px;">
                    <td style="padding: 5px 8px; font-weight: bold; color: <?php echo $closingBalance > 0 ? '#b71c1c' : '#000000'; ?>;">
                        <?php if ($closingBalance > 0): ?>
                            <i class="fas fa-exclamation-triangle text-danger me-1" title="Error: Closing Balance is greater than 0"></i>
                        <?php else: ?>
                            <i class="fas fa-wallet text-dark me-1"></i>
                        <?php endif; ?>
                        <?php echo $lblClosingBalance; ?>
                    </td>
                    <td class="text-right" style="text-align: right; padding: 5px 8px; font-weight: 800; font-size: 15.5px; color: <?php echo $closingBalance > 0 ? '#b71c1c' : ($closingBalance == 0 ? '#1b5e20' : '#d32f2f'); ?>;">
                        <?php if ($closingBalance > 0): ?>
                            <i class="fas fa-exclamation-circle text-danger me-1" style="font-size: 14px;"></i>
                        <?php endif; ?>
                        <?php echo number_format($closingBalance, 2); ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Remarks Footer -->
        <div class="mt-2 remarks-footer-section" style="padding: 0px; margin-top: -2px; font-size: 14.5px;">
            <div class="fw-bold mb-1 remarks-title" style="font-size: 15px;">
                <strong><?php echo $lblRemarks; ?></strong>
            </div>
            
            <div style="padding-left: 4px;">
                <!-- Credit Sales Info -->
                <?php if (!empty($creditSales)): ?>
                    <?php foreach ($creditSales as $cs): 
                        $cDueName = $cs->CustomerName ?? (($lang === 'en') ? 'Customer Due' : 'গ্রাহক বাকী');
                        $dueAmt = number_format($cs->DueAmount > 0 ? $cs->DueAmount : $cs->TotalAmount, 2);
                        $vehStr = !empty($cs->VehicleNumber) ? ' (' . ($lang === 'en' ? 'Vehicle: ' : 'গাড়ী: ') . htmlspecialchars($cs->VehicleNumber) . ')' : '';
                        $csRemStr = !empty($cs->Remarks) ? ' — ' . htmlspecialchars($cs->Remarks) : '';
                    ?>
                        <div class="mb-1" style="line-height: 1.4;">
                            <i class="fas fa-file-invoice-dollar text-danger me-1" style="font-size: 14px;"></i>
                            <strong><?php echo $lang === 'en' ? 'Credit Sale' : 'বাকী লেনদেন'; ?>:</strong> 
                            <?php echo htmlspecialchars($cDueName); ?> — 
                            <span class="fw-bold"><?php echo $dueAmt; ?> TK</span>
                            <?php echo $vehStr . $csRemStr; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="mb-1 text-muted" style="line-height: 1.4;">
                        <i class="fas fa-info-circle text-secondary me-1" style="font-size: 14px;"></i>
                        <span><?php echo $msgNoCreditSales; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Supplier Payments Info -->
                <?php if (!empty($supplierPayments)): ?>
                    <?php foreach ($supplierPayments as $sp): 
                        $supName = $sp->SupplierName ?? (($lang === 'en') ? 'Supplier' : 'সরবরাহকারী');
                        $spAmt = number_format($sp->Amount, 2);
                        $mName = $sp->MethodName ?? 'Cash';
                        $refStr = !empty($sp->ReferenceNo) ? ' (' . ($lang === 'en' ? 'Ref: ' : 'রেফারেন্স: ') . htmlspecialchars($sp->ReferenceNo) . ')' : '';
                        $spRemStr = !empty($sp->Remarks) ? ' — ' . htmlspecialchars($sp->Remarks) : '';
                    ?>
                        <div class="mb-1" style="line-height: 1.4;">
                            <i class="fas fa-truck text-primary me-1" style="font-size: 14px;"></i>
                            <strong><?php echo $lang === 'en' ? 'Supplier Payment' : 'সাপ্লায়ার পেমেন্ট'; ?>:</strong> 
                            <?php echo htmlspecialchars($supName); ?> — 
                            <span class="fw-bold"><?php echo $spAmt; ?> TK</span>
                            <span class="text-muted">(<?php echo htmlspecialchars($mName); ?>)</span>
                            <?php echo $refStr . $spRemStr; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Fuel Purchases Info -->
                <?php if (!empty($fuelPurchases)): ?>
                    <?php foreach ($fuelPurchases as $fp): 
                        $supName = $fp->SupplierName ?? (($lang === 'en') ? 'Supplier' : 'সরবরাহকারী');
                        $invNo   = !empty($fp->InvoiceNo) ? ' (' . ($lang === 'en' ? 'Inv: ' : 'ইনভয়েস: ') . htmlspecialchars($fp->InvoiceNo) . ')' : '';
                        $fpTotal = number_format($fp->TotalAmount, 2);
                        
                        $sqlPd = "SELECT pd.*, ft.FuelName, t.TankName 
                                  FROM trx_purchase_details pd 
                                  LEFT JOIN mst_fueltype ft ON pd.FuelTypeID = ft.FuelTypeID 
                                  LEFT JOIN mst_tank t ON pd.TankID = t.TankID 
                                  WHERE pd.FuelPurchaseID = ? AND pd.IsDeleted = 0 
                                  ORDER BY pd.PurchaseDetailID ASC";
                        $pDetails = $objQuery->index($sqlPd, [$fp->FuelPurchaseID]);
                        
                        $itemDescs = [];
                        if (!empty($pDetails)) {
                            foreach ($pDetails as $pd) {
                                $fName = $pd->FuelName ?? 'Fuel';
                                $tName = !empty($pd->TankName) ? ' - ' . $pd->TankName : '';
                                $qStr  = number_format($pd->Quantity, 2) . ($lang === 'en' ? ' L' : ' লিটার');
                                $itemDescs[] = htmlspecialchars($fName . $tName) . ': ' . $qStr;
                            }
                        } elseif (!empty($fp->FuelTypeID)) {
                            $sqlLegacy = "SELECT ft.FuelName, t.TankName FROM mst_fueltype ft LEFT JOIN mst_tank t ON t.TankID = ? WHERE ft.FuelTypeID = ?";
                            $legData = $objQuery->index($sqlLegacy, [$fp->TankID, $fp->FuelTypeID]);
                            if (!empty($legData)) {
                                $fName = $legData[0]->FuelName ?? 'Fuel';
                                $tName = !empty($legData[0]->TankName) ? ' - ' . $legData[0]->TankName : '';
                                $qStr  = number_format($fp->Quantity, 2) . ($lang === 'en' ? ' L' : ' লিটার');
                                $itemDescs[] = htmlspecialchars($fName . $tName) . ': ' . $qStr;
                            }
                        }
                        
                        $itemsStr = !empty($itemDescs) ? ' [' . implode(', ', $itemDescs) . ']' : '';
                        $fpRemStr = !empty($fp->Remarks) ? ' — ' . htmlspecialchars($fp->Remarks) : '';
                    ?>
                        <div class="mb-1" style="line-height: 1.4;">
                            <i class="fas fa-shopping-cart text-success me-1" style="font-size: 14px;"></i>
                            <strong><?php echo $lang === 'en' ? 'Fuel Purchase' : 'পণ্য ক্রয়'; ?>:</strong> 
                            <?php echo htmlspecialchars($supName) . $invNo; ?> — 
                            <span class="fw-bold"><?php echo $fpTotal; ?> TK</span>
                            <?php echo $itemsStr . $fpRemStr; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
function printReport(mode) {
    const printArea = document.getElementById('statement-print-area');
    if (!printArea) return;

    if (mode === 'mobile') {
        printArea.classList.add('mobile-view-mode');
    } else {
        printArea.classList.remove('mobile-view-mode');
    }

    setTimeout(function() {
        window.print();
    }, 150);
}

$(document).ready(function() {
    $('#btnFinalSubmit').on('click', function() {
        const targetDate = "<?php echo $targetDate; ?>";
        const displayDate = "<?php echo $formattedDateDisplay; ?>";
        const hasNozzleReadings = <?php echo !empty($nozzleReadings) ? 'true' : 'false'; ?>;
        const closingBalance = <?php echo floatval($closingBalance); ?>;
        const lang = "<?php echo $lang; ?>";

        if (!hasNozzleReadings) {
            const title = (lang === 'en') ? 'Nozzle Reading Blank!' : 'নজেল রিডিং খালি!';
            const msg = (lang === 'en')
                ? 'Nozzle reading is blank! Cannot perform Final Submit without nozzle readings.'
                : 'নজেল রিডিং খালি রয়েছে! নজেল রিডিং এন্ট্রি না করা পর্যন্ত ফাইন্যাল সাবমিট করা যাবে না!';
            Swal.fire({
                icon: 'warning',
                title: title,
                text: msg,
                confirmButtonColor: '#d33',
                confirmButtonText: (lang === 'en') ? 'OK' : 'ঠিক আছে'
            });
            return false;
        }

        if (Math.abs(closingBalance) > 0.001) {
            const title = (lang === 'en') ? 'Closing Balance Mismatch!' : 'অবশিষ্ট স্থিত টাকা অসঙ্গতি!';
            const msg = (lang === 'en')
                ? 'Final Submit is not allowed until Closing Balance is 0.00!'
                : 'অবশিষ্ট স্থিত টাকা (Closing Balance) ৳০.০০ না হওয়া পর্যন্ত ফাইন্যাল সাবমিট করা যাবে না!';
            Swal.fire({
                icon: 'warning',
                title: title,
                text: msg,
                confirmButtonColor: '#d33',
                confirmButtonText: (lang === 'en') ? 'OK' : 'ঠিক আছে'
            });
            return false;
        }
        
        const btn = $(this);
        const originalHtml = btn.html();

        Swal.fire({
            title: (lang === 'en') ? 'Confirm Final Submit?' : 'ফাইন্যাল সাবমিট নিশ্চিতকরণ',
            text: (lang === 'en') 
                ? 'Are you sure you want to final submit (close) statement for ' + displayDate + '?' 
                : 'আপনি কি নিশ্চিত যে ' + displayDate + ' তারিখের হিসাবটি ফাইন্যাল সাবমিট (ক্লোজ) করতে চান?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: (lang === 'en') ? 'Yes, Submit' : 'হ্যাঁ, সাবমিট করুন',
            cancelButtonText: (lang === 'en') ? 'Cancel' : 'বাতিল'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Submitting...');
                
                $.ajax({
                    url: '<?php echo BASE_URL; ?>modules/entry/final_submit_entry.php',
                    type: 'POST',
                    data: {
                        action: 'final_submit',
                        statement_date: targetDate
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: (lang === 'en') ? 'Submitted!' : 'সফল!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: (lang === 'en') ? 'Error!' : 'ত্রুটি!',
                                text: response.message || 'Error processing request!',
                                confirmButtonColor: '#d33'
                            });
                            btn.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: (lang === 'en') ? 'Connection Error!' : 'সংযোগ সমস্যা!',
                            text: 'An error occurred while connecting to the server: ' + error,
                            confirmButtonColor: '#d33'
                        });
                        btn.prop('disabled', false).html(originalHtml);
                    }
                });
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
