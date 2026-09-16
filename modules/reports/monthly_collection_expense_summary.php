<?php
/**
 * FuelDeskPro - Monthly Collection & Expense Summary Report (monthly_collection_expense_summary.php)
 *
 * Displays monthly register statement for sales, collections, expenses, due sales and bank deposits
 * formatted exactly according to official station register format.
 * Includes Excel and PDF export functionality.
 *
 * @package FuelDeskPro
 */

$pageTitle = 'Monthly Collection & Expense Summary';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

global $objQuery;
if (!isset($objQuery) || $objQuery === null) {
    $objQuery = db();
}

// ─────────────────────────────────────────────────────────────────────────────
// INPUT & LANGUAGE HANDLING
// ─────────────────────────────────────────────────────────────────────────────
$monthYear = $_GET['month_year'] ?? date('Y-m');
$lang      = $_GET['lang']       ?? (function_exists('currentLang') ? currentLang() : ($_SESSION['lang'] ?? 'bn'));

if (!in_array($lang, ['bn', 'en'])) { $lang = 'bn'; }

// Derive start and end dates from month-year
$startDate = $monthYear . '-01';
$endDate   = date('Y-m-t', strtotime($startDate));

// ─────────────────────────────────────────────────────────────────────────────
// DATE FORMATTING HELPERS
// ─────────────────────────────────────────────────────────────────────────────
function formatDailyReportDate($dateStr, $lang = 'bn') {
    if (empty($dateStr)) return '';
    $timestamp = strtotime($dateStr);
    if ($lang === 'en') {
        return date('d-M-y', $timestamp);
    }
    
    $day = date('d', $timestamp);
    $monthNum = date('n', $timestamp);
    $yearShort = date('y', $timestamp);

    $bMonthsShort = [
        1 => 'জানু', 2 => 'ফেব্রু', 3 => 'মার্চ', 4 => 'এপ্রিল',
        5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
        9 => 'সেপ্টে', 10 => 'অক্টো', 11 => 'নভে', 12 => 'ডিসে'
    ];

    $engDigits = ['0','1','2','3','4','5','6','7','8','9'];
    $bnDigits  = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];

    $dayBn = str_replace($engDigits, $bnDigits, $day);
    $yearBn = str_replace($engDigits, $bnDigits, $yearShort);
    $monthBn = $bMonthsShort[$monthNum] ?? '';

    return "{$dayBn}-{$monthBn}-{$yearBn}";
}

function formatMonthCaptionDisplay($monthYearStr, $lang = 'bn') {
    $parts = explode('-', $monthYearStr);
    $year = $parts[0] ?? date('Y');
    $month = $parts[1] ?? date('m');
    $timestamp = strtotime($monthYearStr . '-01');

    if ($lang === 'en') {
        return date('F-y', $timestamp);
    }

    $bMonths = [
        '01' => 'জানুয়ারি', '02' => 'ফেব্রুয়ারি', '03' => 'মার্চ', '04' => 'এপ্রিল',
        '05' => 'মে', '06' => 'জুন', '07' => 'জুলাই', '08' => 'আগস্ট',
        '09' => 'সেপ্টেম্বর', '10' => 'অক্টোবর', '11' => 'নভেম্বর', '12' => 'ডিসেম্বর'
    ];
    $mName = $bMonths[$month] ?? date('F', $timestamp);
    $shortYear = substr($year, -2);
    $bYear = strtr($shortYear, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']);
    return $mName . '-' . $bYear;
}

// Language Column Labels
if ($lang === 'en') {
    $lblReportTitle    = 'Monthly Collection & Expense Summary';
    $lblFromMonth      = 'Select Month';
    $lblLanguage       = 'Language';
    $lblGenerate       = 'Show';
    $lblExcel          = 'Excel';
    $lblPDF            = 'PDF';
    $lblPrint          = 'Print';
    $lblDate           = 'Date';
    $lblSalesOthers    = 'Sales/ Others Collection';
    $lblConsumedLiter  = 'Consumed Liter';
    $lblRate           = 'Rate';
    $lblSalesAmount    = 'Sales Amount';
    $lblOthersColl     = 'Others Coll.';
    $lblTotalColl      = 'Total Collection';
    $lblExpense        = 'Expense';
    $lblDueSales       = 'Due Sales';
    $lblBankDeposit    = 'Bank Deposit';
    $lblOpening        = 'Opening';
    $lblTotal          = 'Total';
} else {
    $lblReportTitle    = 'মাসিক কালেকশন ও খরচ বিবরণী';
    $lblFromMonth      = 'মাস নির্বাচন করুন';
    $lblLanguage       = 'ভাষা';
    $lblGenerate       = 'রিপোর্ট দেখুন';
    $lblExcel          = 'এক্সেল';
    $lblPDF            = 'পিডিএফ';
    $lblPrint          = 'প্রিন্ট';
    $lblDate           = 'তারিখ';
    $lblSalesOthers    = 'বিক্রয় / অন্যান্য কালেকশন';
    $lblConsumedLiter  = 'পরিমাণ';
    $lblRate           = 'দর';
    $lblSalesAmount    = 'টাকা';
    $lblOthersColl     = 'অন্যান্য';
    $lblTotalColl      = 'সর্বমোট কালেকশন';
    $lblExpense        = 'খরচ';
    $lblDueSales       = 'বাকী বিক্রয়';
    $lblBankDeposit    = 'ব্যাংক জমা';
    $lblOpening        = 'প্রারম্ভিক (Opening)';
    $lblTotal          = 'সর্বমোট';
}

// ─────────────────────────────────────────────────────────────────────────────
// DATA QUERIES
// ─────────────────────────────────────────────────────────────────────────────
// 1. Daily Nozzle Sales (Consumed Liter, Sales Amount & Commission)
$sqlNozzle = "SELECT 
    nr.ReadingDate AS TxnDate,
    SUM(nr.SaleQuantity) AS ConsumedLiter,
    SUM(nr.SalesAmt) AS SalesAmount,
    SUM(
        CASE 
            WHEN nr.CommissionAmt > 0 THEN nr.CommissionAmt 
            WHEN nr.CommissionRate > 0 THEN (nr.SaleQuantity * nr.CommissionRate)
            ELSE (nr.SaleQuantity * COALESCE(f.CommissionRate, 0))
        END
    ) AS CommissionAmount
FROM trx_nozzlereading nr
LEFT JOIN mst_nozzle n ON nr.NozzleID = n.NozzleID
LEFT JOIN mst_fueltype f ON n.FuelTypeID = f.FuelTypeID
WHERE nr.ReadingDate BETWEEN ? AND ?
  AND nr.IsActive = 1 AND nr.IsDeleted = 0
GROUP BY nr.ReadingDate";
$nozzleRows = $objQuery->index($sqlNozzle, [$startDate, $endDate]);

// 2. Daily Customer Due Collections (Due Coll.)
$sqlDueColl = "SELECT TxnDate, SUM(Amount) AS DueCollection
FROM trx_customercollection
WHERE TxnDate BETWEEN ? AND ? AND IsActive = 1 AND IsDeleted = 0
GROUP BY TxnDate";
$dueCollRows = $objQuery->index($sqlDueColl, [$startDate, $endDate]);

// 3. Daily Others Collection
$sqlOthersColl = "SELECT CollectionDate AS TxnDate, SUM(Amount) AS OthersCollection
FROM trx_otherscollection
WHERE CollectionDate BETWEEN ? AND ? AND IsActive = 1 AND IsDeleted = 0
GROUP BY CollectionDate";
$othersCollRows = $objQuery->index($sqlOthersColl, [$startDate, $endDate]);

// 4. Daily Expenses
$sqlExpense = "SELECT ExpenseDate AS TxnDate, SUM(Amount) AS Expense
FROM trx_expense
WHERE ExpenseDate BETWEEN ? AND ? AND IsActive = 1 AND IsDeleted = 0
GROUP BY ExpenseDate";
$expenseRows = $objQuery->index($sqlExpense, [$startDate, $endDate]);

// 5. Daily Customer Credit Sales (Due Sales)
$sqlDueSales = "SELECT TxnDate, SUM(TotalAmount) AS DueSales
FROM trx_customerdue
WHERE TxnDate BETWEEN ? AND ? AND IsActive = 1 AND IsDeleted = 0
GROUP BY TxnDate";
$dueSalesRows = $objQuery->index($sqlDueSales, [$startDate, $endDate]);

// 6. Daily Cash Collections / Bank Deposits
$sqlBank = "SELECT CollectionDate AS TxnDate, SUM(Amount) AS BankDeposit
FROM trx_cashcollection
WHERE CollectionDate BETWEEN ? AND ? AND IsActive = 1 AND IsDeleted = 0
GROUP BY CollectionDate";
$bankRows = $objQuery->index($sqlBank, [$startDate, $endDate]);

// Index arrays by date string YYYY-MM-DD
$nozzleMap = [];
foreach ($nozzleRows as $r) { $nozzleMap[$r->TxnDate] = $r; }

$dueCollMap = [];
foreach ($dueCollRows as $r) { $dueCollMap[$r->TxnDate] = (float)$r->DueCollection; }

$othersCollMap = [];
foreach ($othersCollRows as $r) { $othersCollMap[$r->TxnDate] = (float)$r->OthersCollection; }

$expenseMap = [];
foreach ($expenseRows as $r) { $expenseMap[$r->TxnDate] = (float)$r->Expense; }

$dueSalesMap = [];
foreach ($dueSalesRows as $r) { $dueSalesMap[$r->TxnDate] = (float)$r->DueSales; }

$bankMap = [];
foreach ($bankRows as $r) { $bankMap[$r->TxnDate] = (float)$r->BankDeposit; }

// Aggregate daily rows for calendar month
$daysInMonth = (int)date('t', strtotime($startDate));

$totalLiter          = 0.0;
$totalSalesAmt       = 0.0;
$totalOthersCollAmt  = 0.0;
$totalCollAmt        = 0.0;
$totalExpenseAmt     = 0.0;
$totalDueSalesAmt    = 0.0;
$totalBankDepAmt     = 0.0;
$totalCommissionAmt  = 0.0;

$dailyRowsData = [];

for ($d = 1; $d <= $daysInMonth; $d++) {
    $dateStr = sprintf('%s-%02d', $monthYear, $d);

    $liter      = isset($nozzleMap[$dateStr]) ? (float)$nozzleMap[$dateStr]->ConsumedLiter : 0.0;
    $salesAmt   = isset($nozzleMap[$dateStr]) ? (float)$nozzleMap[$dateStr]->SalesAmount : 0.0;
    $commission = isset($nozzleMap[$dateStr]) ? (float)$nozzleMap[$dateStr]->CommissionAmount : 0.0;
    $rate       = ($liter > 0) ? ($salesAmt / $liter) : 0.0;
    
    $othersColl = $othersCollMap[$dateStr] ?? 0.0;
    $totColl    = $salesAmt + $othersColl;
    
    $expense    = $expenseMap[$dateStr] ?? 0.0;
    $dueSales   = $dueSalesMap[$dateStr] ?? 0.0;
    $bankDep    = $bankMap[$dateStr] ?? 0.0;

    $totalLiter          += $liter;
    $totalSalesAmt        += $salesAmt;
    $totalOthersCollAmt   += $othersColl;
    $totalCollAmt         += $totColl;
    $totalExpenseAmt      += $expense;
    $totalDueSalesAmt     += $dueSales;
    $totalBankDepAmt      += $bankDep;
    $totalCommissionAmt   += $commission;

    $dailyRowsData[] = [
        'date_str'      => $dateStr,
        'formatted_date'=> formatDailyReportDate($dateStr, $lang),
        'liter'         => $liter,
        'rate'          => $rate,
        'sales_amt'     => $salesAmt,
        'commission'    => $commission,
        'others_coll'   => $othersColl,
        'total_coll'    => $totColl,
        'expense'       => $expense,
        'due_sales'     => $dueSales,
        'bank_deposit'  => $bankDep
    ];
}

$netProfitAmt = $totalCommissionAmt - $totalExpenseAmt;

// ─────────────────────────────────────────────────────────────────────────────
// COMPANY INFO & REPORT METADATA
// ─────────────────────────────────────────────────────────────────────────────
$company = getCompanyInfo();
if ($company && !empty($company->TimeZone)) {
    date_default_timezone_set($company->TimeZone);
} else {
    date_default_timezone_set('Asia/Dhaka');
}

if ($lang === 'en') {
    // English language selected -> Database field: CompanyName
    $companyName    = !empty($company->CompanyName) ? $company->CompanyName : 'Shangu LPG Filling Station';
    $companyAddress = !empty($company->AddressEN) ? $company->AddressEN : (!empty($company->Address) ? $company->Address : 'Amilaish, Satkania, Chattogram.');
    $companyMobile  = $company->MobileNo ?? ($company->PhoneNo ?? '01819800600');
    $companyEmail   = $company->Email ?? 'sfs@gmail.com';
} else {
    // Bangla language selected -> Database field: CompanyNameBN
    $companyName    = !empty($company->CompanyNameBN) ? $company->CompanyNameBN : 'সাঙ্গু এল.পি.জি ফিলিং ষ্টেশন';
    $companyAddress = !empty($company->AddressBN) ? $company->AddressBN : (!empty($company->Address) ? $company->Address : 'আমিলাইশ, সাতকানিয়া, চট্টগ্রাম।');
    $rawMobile      = $company->MobileNo ?? ($company->PhoneNo ?? '01819800600');
    $companyMobile  = strtr((string)$rawMobile, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']);
    $companyEmail   = $company->Email ?? 'sfs@gmail.com';
}

$formattedMonthCaption = formatMonthCaptionDisplay($monthYear, $lang);

// ─────────────────────────────────────────────────────────────────────────────
// EXCEL EXPORT HANDLER
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=Monthly_Collection_Expense_Summary_" . $monthYear . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Microsoft Excel
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            table { border-collapse: collapse; width: 100%; font-family: 'SolaimanLipi', Arial, sans-serif; }
            th, td { border: 1px solid #000000; padding: 6px; text-align: center; }
            .text-end { text-align: right; }
            .fw-bold { font-weight: bold; }
        </style>
    </head>
    <body>
        <h2 style="text-align: center; margin-bottom: 4px;"><?php echo htmlspecialchars($companyName); ?></h2>
        <?php 
        $contactInfo = [];
        if (!empty($companyAddress)) { $contactInfo[] = htmlspecialchars($companyAddress); }
        if (!empty($companyMobile)) { $contactInfo[] = ($lang === 'en' ? 'Cell: ' : 'মোবাইল: ') . htmlspecialchars($companyMobile); }
        if (!empty($companyEmail)) { $contactInfo[] = ($lang === 'en' ? 'Email: ' : 'ইমেইল: ') . htmlspecialchars($companyEmail); }
        if (!empty($contactInfo)):
        ?>
        <p style="text-align: center; margin-top: 0; margin-bottom: 8px; font-size: 13px; color: #333333;">
            <?php echo implode(' | ', $contactInfo); ?>
        </p>
        <?php endif; ?>
        <h3 style="text-align: center; margin-top: 4px; margin-bottom: 8px;"><?php echo htmlspecialchars($lblReportTitle); ?> — <?php echo htmlspecialchars($formattedMonthCaption); ?></h3>
        
        <table style="width: 100%; border: none; margin-bottom: 8px;">
            <tr style="border: none;">
                <td style="border: none; text-align: left; font-size: 11px; color: #555555;">
                    <strong><?php echo $lang === 'en' ? 'Print by: ' : 'প্রিন্ট করেছেন: '; ?></strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                </td>
                <td style="border: none; text-align: right; font-size: 11px; color: #555555;">
                    <strong><?php echo $lang === 'en' ? 'Print Time: ' : 'প্রিন্টের সময়: '; ?></strong><?php echo date('d-m-Y h:i A'); ?>
                </td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th rowspan="2"><?php echo htmlspecialchars($lblDate); ?></th>
                    <th colspan="5"><?php echo htmlspecialchars($lblSalesOthers); ?></th>
                    <th rowspan="2"><?php echo htmlspecialchars($lblExpense); ?></th>
                    <th rowspan="2"><?php echo htmlspecialchars($lblDueSales); ?></th>
                    <th rowspan="2"><?php echo htmlspecialchars($lblBankDeposit); ?></th>
                </tr>
                <tr>
                    <th><?php echo htmlspecialchars($lblConsumedLiter); ?></th>
                    <th><?php echo htmlspecialchars($lblRate); ?></th>
                    <th><?php echo htmlspecialchars($lblSalesAmount); ?></th>
                    <th><?php echo htmlspecialchars($lblOthersColl); ?></th>
                    <th><?php echo htmlspecialchars($lblTotalColl); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dailyRowsData as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['formatted_date']); ?></td>
                    <td class="text-end"><?php echo $row['liter'] > 0 ? number_format($row['liter'], 2) : '-'; ?></td>
                    <td class="text-end"><?php echo $row['rate'] > 0 ? number_format($row['rate'], 2) : '-'; ?></td>
                    <td class="text-end"><?php echo $row['sales_amt'] > 0 ? formatCurrency($row['sales_amt']) : '-'; ?></td>
                    <td class="text-end"><?php echo $row['others_coll'] > 0 ? formatCurrency($row['others_coll']) : '-'; ?></td>
                    <td class="text-end"><?php echo $row['total_coll'] > 0 ? formatCurrency($row['total_coll']) : '-'; ?></td>
                    <td class="text-end"><?php echo $row['expense'] > 0 ? formatCurrency($row['expense']) : '-'; ?></td>
                    <td class="text-end"><?php echo $row['due_sales'] > 0 ? formatCurrency($row['due_sales']) : '-'; ?></td>
                    <td class="text-end"><?php echo $row['bank_deposit'] > 0 ? formatCurrency($row['bank_deposit']) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td><?php echo htmlspecialchars($lblTotal); ?></td>
                    <td class="text-end"><?php echo $totalLiter > 0 ? number_format($totalLiter, 2) : '-'; ?></td>
                    <td>-</td>
                    <td class="text-end"><?php echo $totalSalesAmt > 0 ? formatCurrency($totalSalesAmt) : '-'; ?></td>
                    <td class="text-end"><?php echo $totalOthersCollAmt > 0 ? formatCurrency($totalOthersCollAmt) : '-'; ?></td>
                    <td class="text-end"><?php echo $totalCollAmt > 0 ? formatCurrency($totalCollAmt) : '-'; ?></td>
                    <td class="text-end"><?php echo $totalExpenseAmt > 0 ? formatCurrency($totalExpenseAmt) : '-'; ?></td>
                    <td class="text-end"><?php echo $totalDueSalesAmt > 0 ? formatCurrency($totalDueSalesAmt) : '-'; ?></td>
                    <td class="text-end"><?php echo $totalBankDepAmt > 0 ? formatCurrency($totalBankDepAmt) : '-'; ?></td>
                </tr>
            </tfoot>
        </table>

        <br>
        <table style="width: 70%; margin: 15px auto; border-collapse: collapse;">
            <thead>
                <tr>
                    <th colspan="2" style="background-color: #e2e8f0; text-align: center; border: 2px solid #000; padding: 8px; font-size: 14px; font-weight: bold;">
                        <?php echo $lang === 'en' ? 'Monthly Profit & Loss Summary' : 'মাসিক পরিচালনা লাভ-ক্ষতি সংক্ষিপ্ত বিবরণী'; ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: 1px solid #000; padding: 6px; text-align: left; font-size: 12px;"><?php echo $lang === 'en' ? 'Total Fuel Sales' : 'মোট জ্বালানি বিক্রয়'; ?></td>
                    <td style="border: 1px solid #000; padding: 6px; text-align: right; font-weight: bold; font-size: 12px;">৳<?php echo formatCurrency($totalSalesAmt); ?></td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 6px; text-align: left; font-size: 12px;"><?php echo $lang === 'en' ? 'Total Fuel Commission (Gross Profit)' : 'মোট জ্বালানি কমিশন (গ্রস প্রফিট)'; ?></td>
                    <td style="border: 1px solid #000; padding: 6px; text-align: right; font-weight: bold; font-size: 12px; color: #0284c7;">৳<?php echo formatCurrency($totalCommissionAmt); ?></td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 6px; text-align: left; font-size: 12px;"><?php echo $lang === 'en' ? 'Less: Total Operating Expenses' : 'বাদ: মোট পরিচালন খরচ'; ?></td>
                    <td style="border: 1px solid #000; padding: 6px; text-align: right; font-weight: bold; font-size: 12px; color: #dc2626;">৳<?php echo formatCurrency($totalExpenseAmt); ?></td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="border: 2px solid #000; padding: 8px; text-align: left; font-weight: bold; font-size: 13px;"><?php echo $lang === 'en' ? 'Net Operating Profit / (Loss)' : 'নিট পরিচালনা লাভ / (ক্ষতি)'; ?></td>
                    <td style="border: 2px solid #000; padding: 8px; text-align: right; font-weight: bold; font-size: 13px; color: <?php echo $netProfitAmt >= 0 ? '#16a34a' : '#dc2626'; ?>;">৳<?php echo formatCurrency($netProfitAmt); ?></td>
                </tr>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// HTML VIEW INCLUDES
// ─────────────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     STYLES: OFFICIAL REGISTER GRID & PRINT FORMATTING
════════════════════════════════════════════════════════════════════════════ -->
<style>
.summary-container {
    background: #ffffff;
    font-family: 'SolaimanLipi', 'Kalpurush', 'Nikosh', 'Arial', sans-serif;
}

/* Header Company Title */
.company-header-title {
    font-size: 1.6rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 2px;
}
.company-header-address {
    font-size: 0.95rem;
    color: #334155;
    margin-bottom: 12px;
}

/* Month Caption Box Header */
.month-caption-box {
    border: 2px solid #000000;
    background-color: #ffffff;
    text-align: center;
    font-weight: 800;
    font-size: 1.25rem;
    padding: 6px 12px;
    color: #000000;
    margin-bottom: -1px;
}

/* Grid Table Styling */
.grid-summary-table {
    width: 100%;
    border-collapse: collapse !important;
    border: 2px solid #000000 !important;
    font-family: 'SolaimanLipi', 'Kalpurush', 'Nikosh', 'Arial', sans-serif;
    background: #ffffff;
}

.grid-summary-table th, 
.grid-summary-table td {
    border: 1px solid #000000 !important;
    padding: 6px 8px;
    font-size: 0.92rem;
    color: #000000;
    vertical-align: middle;
}

.grid-summary-table thead th {
    background-color: #e2e8f0 !important;
    color: #000000 !important;
    font-weight: 800;
    text-align: center;
}

.grid-summary-table tbody tr:nth-child(even) {
    background-color: #f8fafc !important;
}
.grid-summary-table tbody tr:nth-child(odd) {
    background-color: #ffffff !important;
}

.grid-summary-table tbody tr:hover {
    background-color: #e0f2fe !important;
    transition: background-color 0.15s ease-in-out;
}

.grid-summary-table tfoot tr td {
    background-color: #ffffff !important;
    font-weight: 800;
    font-size: 0.98rem;
    border-top: 2px solid #000000 !important;
}

/* Column Width Ratios */
.col-date { width: 15%; min-width: 105px; }
.col-exp { width: 10%; }
.col-due-sales { width: 10%; }
.col-bank { width: 11%; }

@media print {
    #sidebar, .top-header, .top-navbar, .no-print, .btn, nav, header, footer {
        display: none !important;
    }

    @page {
        size: A4 portrait;
        margin: 8mm 5mm 10mm 5mm;
        @bottom-right {
            content: "<?php echo $lang === 'en' ? 'Page: ' : 'পৃষ্ঠা: '; ?>" counter(page) " <?php echo $lang === 'en' ? ' of ' : ' এর '; ?>" counter(pages);
            font-size: 9pt;
            font-family: 'SolaimanLipi', 'Kalpurush', 'Nikosh', 'Arial', sans-serif;
            color: #000000;
            font-weight: bold;
        }
    }

    body {
        background: #ffffff !important;
        color: #000000 !important;
        font-family: 'SolaimanLipi', 'Kalpurush', 'Nikosh', 'Arial', sans-serif !important;
        font-size: 10.5pt !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .table-container, .summary-container {
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
    }

    .company-header-title {
        font-size: 18pt !important;
        color: #000000 !important;
    }

    .company-header-address {
        font-size: 10pt !important;
        color: #000000 !important;
    }

    .month-caption-box {
        border: 2px solid #000000 !important;
        font-size: 13pt !important;
        padding: 4px !important;
    }

    .grid-summary-table {
        border: 2px solid #000000 !important;
        font-size: 10.5pt !important;
    }

    .grid-summary-table th, 
    .grid-summary-table td {
        border: 1px solid #000000 !important;
        padding: 4px 6px !important;
        font-size: 10pt !important;
        color: #000000 !important;
    }

    tr {
        page-break-inside: avoid !important;
    }
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════════════
     PAGE HTML & FILTER BAR
════════════════════════════════════════════════════════════════════════════ -->
<div class="table-container summary-container">
    
    <!-- Top Filter Toolbar -->
    <div class="card mb-4 no-print shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold"><i class="fas fa-calendar-alt text-primary me-1"></i> <?php echo $lblFromMonth; ?></label>
                    <input type="month" name="month_year" class="form-control" value="<?php echo $monthYear; ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold"><i class="fas fa-language text-primary me-1"></i> <?php echo $lblLanguage; ?></label>
                    <select name="lang" class="form-select" onchange="this.form.submit()">
                        <option value="bn" <?php echo $lang === 'bn' ? 'selected' : ''; ?>>Bangla</option>
                        <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>English</option>
                    </select>
                </div>
                <div class="col-md-6 d-flex gap-2 flex-wrap justify-content-end align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> <?php echo $lblGenerate; ?></button>
                    <button type="button" onclick="exportToExcel()" class="btn btn-outline-success"><i class="fas fa-file-excel me-1"></i> <?php echo $lblExcel; ?></button>
                    <button type="button" onclick="exportToPDF()" class="btn btn-outline-danger"><i class="fas fa-file-pdf me-1"></i> <?php echo $lblPDF; ?></button>
                    <button type="button" onclick="window.print()" class="btn btn-success"><i class="fas fa-print me-1"></i> <?php echo $lblPrint; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Executive KPI Summary Cards (Screen Mode Only) -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-md-2-4 col-sm-6" style="width: 20%;">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb !important;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase"><?php echo $lang === 'en' ? 'Total Fuel Sales' : 'মোট জ্বালানি বিক্রয়'; ?></div>
                            <div class="fs-6 fw-bold text-primary mt-1">৳<?php echo formatCurrency($totalSalesAmt); ?></div>
                            <div class="small text-secondary mt-1"><i class="fas fa-gas-pump me-1"></i><?php echo number_format($totalLiter, 2); ?> Ltr</div>
                        </div>
                        <div class="rounded-circle bg-white p-2 shadow-sm text-primary">
                            <i class="fas fa-gas-pump"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2-4 col-sm-6" style="width: 20%;">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #d97706 !important;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase"><?php echo $lang === 'en' ? 'Total Commission' : 'মোট কমিশন (গ্রস)'; ?></div>
                            <div class="fs-6 fw-bold mt-1" style="color: #b45309;">৳<?php echo formatCurrency($totalCommissionAmt); ?></div>
                            <div class="small text-secondary mt-1"><i class="fas fa-percentage me-1"></i><?php echo $lang === 'en' ? 'Dealer Margin' : 'ডিলার মার্জিন'; ?></div>
                        </div>
                        <div class="rounded-circle bg-white p-2 shadow-sm" style="color: #d97706;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2-4 col-sm-6" style="width: 20%;">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-left: 4px solid #dc2626 !important;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase"><?php echo $lang === 'en' ? 'Total Expenses' : 'মোট পরিচালন খরচ'; ?></div>
                            <div class="fs-6 fw-bold text-danger mt-1">৳<?php echo formatCurrency($totalExpenseAmt); ?></div>
                            <div class="small text-secondary mt-1"><i class="fas fa-receipt me-1"></i><?php echo $lang === 'en' ? 'Station Cost' : 'স্টেশন খরচ'; ?></div>
                        </div>
                        <div class="rounded-circle bg-white p-2 shadow-sm text-danger">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2-4 col-sm-6" style="width: 20%;">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="background: linear-gradient(135deg, <?php echo $netProfitAmt >= 0 ? '#f0fdf4 0%, #dcfce7 100%' : '#fff1f2 0%, #ffe4e6 100%'; ?>); border-left: 4px solid <?php echo $netProfitAmt >= 0 ? '#16a34a' : '#e11d48'; ?> !important;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase"><?php echo $lang === 'en' ? 'Net Operating Profit' : 'নিট পরিচালনা লাভ'; ?></div>
                            <div class="fs-6 fw-bold <?php echo $netProfitAmt >= 0 ? 'text-success' : 'text-danger'; ?> mt-1">৳<?php echo formatCurrency($netProfitAmt); ?></div>
                            <div class="small fw-semibold <?php echo $netProfitAmt >= 0 ? 'text-success' : 'text-danger'; ?> mt-1">
                                <i class="fas <?php echo $netProfitAmt >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?> me-1"></i>
                                <?php echo $netProfitAmt >= 0 ? ($lang === 'en' ? 'Profit' : 'লাভ') : ($lang === 'en' ? 'Loss' : 'ক্ষতি'); ?>
                            </div>
                        </div>
                        <div class="rounded-circle bg-white p-2 shadow-sm <?php echo $netProfitAmt >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <i class="fas <?php echo $netProfitAmt >= 0 ? 'fa-trophy' : 'fa-exclamation-triangle'; ?>"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2-4 col-sm-6" style="width: 20%;">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-left: 4px solid #475569 !important;">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-bold text-uppercase"><?php echo $lang === 'en' ? 'Bank Deposit' : 'মোট ব্যাংক জমা'; ?></div>
                            <div class="fs-6 fw-bold text-dark mt-1">৳<?php echo formatCurrency($totalBankDepAmt); ?></div>
                            <div class="small text-secondary mt-1"><i class="fas fa-university me-1"></i><?php echo $lang === 'en' ? 'Cash Coll: ৳' : 'অন্যান্য: ৳'; ?><?php echo formatCurrency($totalOthersCollAmt); ?></div>
                        </div>
                        <div class="rounded-circle bg-white p-2 shadow-sm text-dark">
                            <i class="fas fa-university"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Top Header (Hidden on Screen UI, Shown only when Printing/PDF) -->
    <div class="report-header text-center mb-3 d-none d-print-block">
        <!-- Company Profile Info -->
        <div>
            <h2 class="fw-bold mb-1 company-header-title" style="font-family: 'SolaimanLipi', 'Kalpurush', 'Nikosh', 'Arial', sans-serif;">
                <?php echo htmlspecialchars($companyName); ?>
            </h2>
            <?php if (!empty($companyAddress) || !empty($companyMobile) || !empty($companyEmail)): ?>
                <div class="mb-2 company-header-address text-secondary" style="font-size: 0.95rem;">
                    <?php echo htmlspecialchars($companyAddress); ?>
                    <?php if (!empty($companyMobile)): ?>
                        <span class="mx-1">|</span> <i class="fas fa-phone-alt me-1 text-dark"></i><?php echo htmlspecialchars($companyMobile); ?>
                    <?php endif; ?>
                    <?php if (!empty($companyEmail)): ?>
                        <span class="mx-1">|</span> <i class="fas fa-envelope me-1 text-dark"></i><?php echo htmlspecialchars($companyEmail); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="mb-2">
            <h5 class="fw-bold text-dark d-inline-block pb-1" style="border-bottom: 2px solid #000000; font-family: 'SolaimanLipi', 'Kalpurush', 'Nikosh', 'Arial', sans-serif;">
                <?php echo htmlspecialchars($lblReportTitle); ?> — <span class="text-primary"><?php echo htmlspecialchars($formattedMonthCaption); ?></span>
            </h5>
        </div>

        <!-- Print Metadata Bar -->
        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top text-muted" style="font-size: 11px; font-weight: 600; border-color: #cbd5e1 !important;">
            <div>
                <?php echo $lang === 'en' ? 'Print by: ' : 'প্রিন্ট করেছেন: '; ?>
                <strong class="text-dark"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></strong>
            </div>
            <div>
                <?php echo $lang === 'en' ? 'Print Time: ' : 'প্রিন্টের সময়: '; ?>
                <strong class="text-dark"><?php echo date('d-m-Y h:i A'); ?></strong>
            </div>
        </div>
    </div>

    <!-- Official Summary Register Grid Table -->
    <div class="table-responsive">
        <table class="table table-bordered grid-summary-table align-middle">
            <thead>
                <tr>
                    <th rowspan="2" class="text-center align-middle col-date"><?php echo htmlspecialchars($lblDate); ?></th>
                    <th colspan="5" class="text-center"><?php echo htmlspecialchars($lblSalesOthers); ?></th>
                    <th rowspan="2" class="text-center align-middle col-exp"><?php echo htmlspecialchars($lblExpense); ?></th>
                    <th rowspan="2" class="text-center align-middle col-due-sales"><?php echo htmlspecialchars($lblDueSales); ?></th>
                    <th rowspan="2" class="text-center align-middle col-bank"><?php echo htmlspecialchars($lblBankDeposit); ?></th>
                </tr>
                <tr>
                    <th class="text-center"><?php echo htmlspecialchars($lblConsumedLiter); ?></th>
                    <th class="text-center"><?php echo htmlspecialchars($lblRate); ?></th>
                    <th class="text-center"><?php echo htmlspecialchars($lblSalesAmount); ?></th>
                    <th class="text-center"><?php echo htmlspecialchars($lblOthersColl); ?></th>
                    <th class="text-center"><?php echo htmlspecialchars($lblTotalColl); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- Daily Breakdown Data Rows -->
                <?php foreach ($dailyRowsData as $row): ?>
                <tr>
                    <td class="text-center fw-medium"><?php echo htmlspecialchars($row['formatted_date']); ?></td>
                    <td class="text-end"><?php echo $row['liter'] > 0 ? number_format($row['liter'], 2) : '-'; ?></td>
                    <td class="text-end"><?php echo $row['rate'] > 0 ? number_format($row['rate'], 2) : '-'; ?></td>
                    <td class="text-end"><?php echo $row['sales_amt'] > 0 ? formatCurrency($row['sales_amt']) : '-'; ?></td>
                    <td class="text-end"><?php echo $row['others_coll'] > 0 ? formatCurrency($row['others_coll']) : '-'; ?></td>
                    <td class="text-end fw-semibold"><?php echo $row['total_coll'] > 0 ? formatCurrency($row['total_coll']) : '-'; ?></td>
                    <td class="text-end"><?php echo $row['expense'] > 0 ? formatCurrency($row['expense']) : '-'; ?></td>
                    <td class="text-end"><?php echo $row['due_sales'] > 0 ? formatCurrency($row['due_sales']) : '-'; ?></td>
                    <td class="text-end"><?php echo $row['bank_deposit'] > 0 ? formatCurrency($row['bank_deposit']) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>

            </tbody>
            <tfoot>
                <tr class="total-row fw-bold">
                    <td class="text-center"><?php echo htmlspecialchars($lblTotal); ?></td>
                    <td class="text-end"><?php echo $totalLiter > 0 ? number_format($totalLiter, 2) : '-'; ?></td>
                    <td class="text-center">-</td>
                    <td class="text-end"><?php echo $totalSalesAmt > 0 ? formatCurrency($totalSalesAmt) : '-'; ?></td>
                    <td class="text-end"><?php echo $totalOthersCollAmt > 0 ? formatCurrency($totalOthersCollAmt) : '-'; ?></td>
                    <td class="text-end fw-bold"><?php echo $totalCollAmt > 0 ? formatCurrency($totalCollAmt) : '-'; ?></td>
                    <td class="text-end"><?php echo $totalExpenseAmt > 0 ? formatCurrency($totalExpenseAmt) : '-'; ?></td>
                    <td class="text-end"><?php echo $totalDueSalesAmt > 0 ? formatCurrency($totalDueSalesAmt) : '-'; ?></td>
                    <td class="text-end"><?php echo $totalBankDepAmt > 0 ? formatCurrency($totalBankDepAmt) : '-'; ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Financial Profit & Loss Summary Card (Visible in Screen UI, Print & PDF) -->
    <div class="card border border-2 border-dark rounded-3 mt-4 mb-3 profit-summary-card">
        <div class="card-header bg-light border-bottom border-dark py-2">
            <h6 class="fw-bold mb-0 text-dark text-uppercase text-center" style="font-family: 'SolaimanLipi', 'Kalpurush', 'Nikosh', 'Arial', sans-serif;">
                <i class="fas fa-calculator text-primary me-1 no-print"></i>
                <?php echo $lang === 'en' ? 'Monthly Profit & Loss Summary' : 'মাসিক পরিচালনা লাভ-ক্ষতি সংক্ষিপ্ত বিবরণী'; ?>
            </h6>
        </div>
        <div class="card-body py-3 px-4">
            <div class="row g-3 text-center align-items-center">
                <div class="col-md-3 col-6 border-end">
                    <div class="text-muted small fw-bold text-uppercase mb-1"><?php echo $lang === 'en' ? 'Total Fuel Sales' : 'মোট জ্বালানি বিক্রয়'; ?></div>
                    <div class="fs-5 fw-bold text-dark">৳<?php echo formatCurrency($totalSalesAmt); ?></div>
                    <div class="small text-secondary"><i class="fas fa-gas-pump me-1"></i><?php echo number_format($totalLiter, 2); ?> Ltr</div>
                </div>
                <div class="col-md-3 col-6 border-end">
                    <div class="text-muted small fw-bold text-uppercase mb-1"><?php echo $lang === 'en' ? 'Gross Commission' : 'মোট কমিশন (গ্রস লাভ)'; ?></div>
                    <div class="fs-5 fw-bold text-primary">৳<?php echo formatCurrency($totalCommissionAmt); ?></div>
                    <div class="small text-secondary"><?php echo $lang === 'en' ? 'Fuel Dealer Margin' : 'জ্বালানি ডিলার মার্জিন'; ?></div>
                </div>
                <div class="col-md-3 col-6 border-end">
                    <div class="text-muted small fw-bold text-uppercase mb-1"><?php echo $lang === 'en' ? 'Less: Total Expenses' : 'বাদ: মোট পরিচালন খরচ'; ?></div>
                    <div class="fs-5 fw-bold text-danger">৳<?php echo formatCurrency($totalExpenseAmt); ?></div>
                    <div class="small text-secondary"><?php echo $lang === 'en' ? 'Station Operational Cost' : 'স্টেশন পরিচালন খরচ'; ?></div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-muted small fw-bold text-uppercase mb-1"><?php echo $lang === 'en' ? 'Net Operating Profit' : 'নিট পরিচালনা লাভ / (ক্ষতি)'; ?></div>
                    <div class="fs-5 fw-bold <?php echo $netProfitAmt >= 0 ? 'text-success' : 'text-danger'; ?>">
                        ৳<?php echo formatCurrency($netProfitAmt); ?>
                    </div>
                    <div class="small fw-semibold <?php echo $netProfitAmt >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <i class="fas <?php echo $netProfitAmt >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?> me-1"></i>
                        <?php echo $netProfitAmt >= 0 ? ($lang === 'en' ? 'Net Profit' : 'নিট মুনাফা') : ($lang === 'en' ? 'Net Loss' : 'নিট লোকসান'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- html2pdf Bundle Library for Client-side PDF Export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
function exportToExcel() {
    const monthYear = document.querySelector('input[name="month_year"]').value;
    const lang = document.querySelector('select[name="lang"]').value;
    window.location.href = 'monthly_collection_expense_summary.php?month_year=' + encodeURIComponent(monthYear) + '&lang=' + encodeURIComponent(lang) + '&export=excel';
}

function exportToPDF() {
    if (typeof html2pdf !== 'undefined') {
        const element = document.querySelector('.summary-container');
        const reportHeader = document.querySelector('.report-header');
        const monthYear = document.querySelector('input[name="month_year"]').value;
        const opt = {
            margin:       [6, 5, 8, 5],
            filename:     'Monthly_Collection_Expense_Summary_' + monthYear + '.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        const noPrintElements = document.querySelectorAll('.no-print');
        noPrintElements.forEach(el => el.style.display = 'none');
        if (reportHeader) {
            reportHeader.classList.remove('d-none');
        }

        html2pdf().set(opt).from(element).save().then(() => {
            noPrintElements.forEach(el => el.style.display = '');
            if (reportHeader) {
                reportHeader.classList.add('d-none');
            }
        }).catch(err => {
            noPrintElements.forEach(el => el.style.display = '');
            if (reportHeader) {
                reportHeader.classList.add('d-none');
            }
            window.print();
        });
    } else {
        window.print();
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
