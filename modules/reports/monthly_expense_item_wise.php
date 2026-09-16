<?php
/**
 * FuelDeskPro - Monthly Expense Item Wise Report (monthly_expense_item_wise.php)
 *
 * Displays an interactive item-wise monthly expense report with banded row styling,
 * colorful headers/footers, and interactive modal drill-down for detailed transactions.
 *
 * @package FuelDeskPro
 */

$pageTitle = 'Monthly Expense Item Wise Report';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// ─────────────────────────────────────────────────────────────────────────────
// INPUT & LANGUAGE HANDLING
// ─────────────────────────────────────────────────────────────────────────────
$monthYear  = $_GET['month_year']  ?? date('Y-m');
$lang       = $_GET['lang']        ?? currentLang();
$reportType = $_GET['report_type'] ?? 'summary';

if (!in_array($lang, ['bn', 'en'])) { $lang = 'bn'; }
if (!in_array($reportType, ['summary', 'details'])) { $reportType = 'summary'; }

// Derive start and end dates for selected month
$startDate = $monthYear . '-01';
$endDate   = date('Y-m-t', strtotime($startDate));

// ─────────────────────────────────────────────────────────────────────────────
// MONTH FORMATTING HELPERS
// ─────────────────────────────────────────────────────────────────────────────
function formatBanglaMonthYear($monthYearStr) {
    $parts = explode('-', $monthYearStr);
    $year = $parts[0] ?? date('Y');
    $month = $parts[1] ?? date('m');
    
    $bMonths = [
        '01' => 'জানুয়ারি', '02' => 'ফেব্রুয়ারি', '03' => 'মার্চ', '04' => 'এপ্রিল',
        '05' => 'মে', '06' => 'জুন', '07' => 'জুলাই', '08' => 'আগস্ট',
        '09' => 'সেপ্টেম্বর', '10' => 'অক্টোবর', '11' => 'নভেম্বর', '12' => 'ডিসেম্বর'
    ];
    $mName = $bMonths[$month] ?? date('F', strtotime($monthYearStr . '-01'));
    $shortYear = substr($year, -2);
    $bYear = strtr($shortYear, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']);
    return $mName . '-' . $bYear;
}

function formatEnglishMonthYear($monthYearStr) {
    return date('F-y', strtotime($monthYearStr . '-01'));
}

// Column & Button Labels
if ($lang === 'en') {
    $lblGroup            = 'Expense Group';
    $lblParticular       = 'Particular';
    $lblAmount           = 'Amount (TK)';
    $lblTotal            = 'Total';
    $lblFromMonth        = 'Select Month';
    $lblLanguage         = 'Language';
    $lblReportType       = 'Report Type';
    $lblSummary          = 'Summary';
    $lblDetails          = 'Details';
    $lblDate             = 'Date';
    $lblPaymentMethod    = 'Payment Method';
    $lblReference        = 'Reference';
    $lblRemarks          = 'Remarks';
    $lblGenerate         = 'Show';
    $lblPrint            = 'Print';
    $lblNoData           = 'No expense data found for the selected month.';
    $lblClickHint        = 'Click any row to view detailed transaction breakdown';
    $lblGroupSummaryCard = 'Expense Particular Group Summary';
    $lblEntries          = 'Entries';
    $lblShare            = '% Share';
} else {
    $lblGroup            = 'ব্যয়ের গ্রুপ (Group)';
    $lblParticular       = 'বিবরণ (Particular)';
    $lblAmount           = 'পরিমাণ (টাকা)';
    $lblTotal            = 'সর্বমোট';
    $lblFromMonth        = 'মাস নির্বাচন করুন';
    $lblLanguage         = 'ভাষা (Language)';
    $lblReportType       = 'রিপোর্টের ধরন (Type)';
    $lblSummary          = 'সারসংক্ষেপ (Summary)';
    $lblDetails          = 'বিস্তারিত (Details)';
    $lblDate             = 'তারিখ';
    $lblPaymentMethod    = 'পেমেন্ট মেথড';
    $lblReference        = 'রেফারেন্স';
    $lblRemarks          = 'মন্তব্য';
    $lblGenerate         = 'রিপোর্ট দেখুন';
    $lblPrint            = 'প্রিন্ট';
    $lblNoData           = 'নির্বাচনকৃত মাসে কোনো ব্যয়ের তথ্য পাওয়া যায়নি।';
    $lblClickHint        = 'বিস্তারিত এন্ট্রি দেখতে যেকোনো রৌ (Row) তে ক্লিক করুন';
    $lblGroupSummaryCard = 'ব্যয়ের গ্রুপভিত্তিক সারসংক্ষেপ (Expense Group Summary)';
    $lblEntries          = 'এন্ট্রির সংখ্যা (Entries)';
    $lblShare            = 'ব্যয়ের হার (%)';
}

// ─────────────────────────────────────────────────────────────────────────────
// DATA QUERIES
// ─────────────────────────────────────────────────────────────────────────────
// 1. Expense Particular Group Summary Query (Category-wise summary)
$sqlCategorySummary = "SELECT 
    COALESCE(ec.ExpenseCategoryID, ec_sal.ExpenseCategoryID, 11) AS ExpenseCategoryID,
    COALESCE(ec.CategoryNameEN, ec_sal.CategoryNameEN, 'Salary') AS CategoryNameEN,
    COALESCE(ec.CategoryNameBN, ec_sal.CategoryNameBN, 'বেতন') AS CategoryNameBN,
    SUM(e.Amount) AS CategoryTotal,
    COUNT(*) AS CategoryEntries
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
LEFT JOIN mst_expensecategory ec_sal 
    ON (emp.Id IS NOT NULL OR emp.EmployeeId IS NOT NULL) 
    AND (ec_sal.ExpenseCategoryID = 11 OR ec_sal.CategoryNameEN = 'Salary')
    AND ec_sal.IsDeleted = 0
WHERE e.ExpenseDate BETWEEN ? AND ?
  AND e.IsActive = 1 AND e.IsDeleted = 0
GROUP BY 
    COALESCE(ec.ExpenseCategoryID, ec_sal.ExpenseCategoryID, 11),
    COALESCE(ec.CategoryNameEN, ec_sal.CategoryNameEN, 'Salary'),
    COALESCE(ec.CategoryNameBN, ec_sal.CategoryNameBN, 'বেতন')
ORDER BY CategoryTotal DESC, CategoryNameEN ASC";

$categorySummaryRecords = $objQuery->index($sqlCategorySummary, [$startDate, $endDate]);

$catSummaryTotalAmount  = 0;
$catSummaryTotalEntries = 0;
foreach ($categorySummaryRecords as $cs) {
    $catSummaryTotalAmount  += floatval($cs->CategoryTotal);
    $catSummaryTotalEntries += intval($cs->CategoryEntries);
}

if ($reportType === 'summary') {
    // 2. Summary query grouped by Particular (handles both mst_expenseparticular and mst_employee)
    $sqlSummary = "SELECT 
                e.ParticularID AS KeyParticularID,
                COALESCE(ep.ParticularID, emp.EmployeeId, e.ParticularID) AS ParticularID,
                COALESCE(ep.ParticularNameEN, emp.NameEN, e.ParticularID) AS ParticularNameEN,
                COALESCE(ep.ParticularNameBN, emp.NameBN, emp.NameEN, e.ParticularID) AS ParticularNameBN,
                COALESCE(ec.ExpenseCategoryID, ec_sal.ExpenseCategoryID, 11) AS ExpenseCategoryID,
                COALESCE(ec.CategoryNameEN, ec_sal.CategoryNameEN, 'Salary') AS CategoryNameEN,
                COALESCE(ec.CategoryNameBN, ec_sal.CategoryNameBN, 'বেতন') AS CategoryNameBN,
                SUM(e.Amount) AS TotalAmount,
                COUNT(*) AS EntryCount
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
            LEFT JOIN mst_expensecategory ec_sal 
                ON (emp.Id IS NOT NULL OR emp.EmployeeId IS NOT NULL) 
                AND (ec_sal.ExpenseCategoryID = 11 OR ec_sal.CategoryNameEN = 'Salary')
                AND ec_sal.IsDeleted = 0
            WHERE e.ExpenseDate BETWEEN ? AND ?
              AND e.IsActive = 1 AND e.IsDeleted = 0
            GROUP BY 
                e.ParticularID,
                ep.ParticularID,
                emp.EmployeeId,
                ep.ParticularNameEN,
                emp.NameEN,
                ep.ParticularNameBN,
                emp.NameBN,
                ec.ExpenseCategoryID,
                ec.CategoryNameEN,
                ec.CategoryNameBN,
                ec_sal.ExpenseCategoryID,
                ec_sal.CategoryNameEN,
                ec_sal.CategoryNameBN
            ORDER BY CategoryNameEN ASC, ParticularNameEN ASC";

    $records = $objQuery->index($sqlSummary, [$startDate, $endDate]);

    $grandTotal = 0;
    foreach ($records as $r) {
        $grandTotal += floatval($r->TotalAmount);
    }

    // 3. Detailed individual transactions for modal popup
    $sqlDetails = "SELECT 
        e.ExpenseID,
        e.ExpenseDate,
        e.ParticularID,
        e.Amount,
        pm.MethodName,
        e.ReferenceNo,
        e.Remarks,
        COALESCE(ep.ParticularNameEN, emp.NameEN, e.ParticularID) AS ParticularNameEN,
        COALESCE(ep.ParticularNameBN, emp.NameBN, emp.NameEN, e.ParticularID) AS ParticularNameBN,
        COALESCE(ec.ExpenseCategoryID, ec_sal.ExpenseCategoryID, 11) AS ExpenseCategoryID,
        COALESCE(ec.CategoryNameEN, ec_sal.CategoryNameEN, 'Salary') AS CategoryNameEN,
        COALESCE(ec.CategoryNameBN, ec_sal.CategoryNameBN, 'বেতন') AS CategoryNameBN
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
    LEFT JOIN mst_expensecategory ec_sal 
        ON (emp.Id IS NOT NULL OR emp.EmployeeId IS NOT NULL) 
        AND (ec_sal.ExpenseCategoryID = 11 OR ec_sal.CategoryNameEN = 'Salary')
        AND ec_sal.IsDeleted = 0
    LEFT JOIN cfg_paymentmethod pm ON e.PaymentMethodID = pm.PaymentMethodID
    WHERE e.ExpenseDate BETWEEN ? AND ?
      AND e.IsActive = 1 AND e.IsDeleted = 0
    ORDER BY ParticularNameEN ASC, e.ExpenseDate ASC, e.ExpenseID ASC";

    $detailRecords = $objQuery->index($sqlDetails, [$startDate, $endDate]);

    $detailsMap = [];
    $groupParticularSummaryMap = [];
    foreach ($detailRecords as $d) {
        $pid   = $d->ParticularID;
        $catId = $d->ExpenseCategoryID;

        $particularName = $lang === 'en'
            ? (!empty($d->ParticularNameEN) ? $d->ParticularNameEN : ($d->ParticularNameBN ?? '—'))
            : (!empty($d->ParticularNameBN) ? $d->ParticularNameBN : ($d->ParticularNameEN ?? '—'));

        $amountVal = floatval($d->Amount);

        // Particular row details
        if (!isset($detailsMap[$pid])) {
            $detailsMap[$pid] = [];
        }
        $detailsMap[$pid][] = [
            'date'       => formatDate($d->ExpenseDate),
            'particular' => $particularName,
            'amount'     => formatCurrency($amountVal),
            'method'     => $d->MethodName ?? '-',
            'ref'        => $d->ReferenceNo ?? '',
            'remarks'    => $d->Remarks ?? ''
        ];

        // Category Group details grouped by ParticularID
        if (!isset($groupParticularSummaryMap[$catId])) {
            $groupParticularSummaryMap[$catId] = [];
        }
        if (!isset($groupParticularSummaryMap[$catId][$pid])) {
            $groupParticularSummaryMap[$catId][$pid] = [
                'particular_id'   => $pid,
                'particular_name' => $particularName,
                'total_amount'    => 0,
                'entry_count'     => 0,
                'transactions'    => []
            ];
        }
        $groupParticularSummaryMap[$catId][$pid]['total_amount'] += $amountVal;
        $groupParticularSummaryMap[$catId][$pid]['entry_count']  += 1;
        $groupParticularSummaryMap[$catId][$pid]['transactions'][] = [
            'date'    => formatDate($d->ExpenseDate),
            'amount'  => formatCurrency($amountVal),
            'method'  => $d->MethodName ?? '-',
            'ref'     => $d->ReferenceNo ?? '',
            'remarks' => $d->Remarks ?? ''
        ];
    }

    // Sort Particulars within each group ASC by particular_name
    foreach ($groupParticularSummaryMap as $catId => &$pDataMap) {
        uasort($pDataMap, function($a, $b) {
            return strnatcasecmp($a['particular_name'], $b['particular_name']);
        });
    }
    unset($pDataMap);

} else {
    // Details query for line-by-line report view
    $sqlFullDetails = "SELECT 
        e.ExpenseID,
        e.ExpenseDate,
        e.ParticularID,
        e.Amount,
        e.ReferenceNo,
        e.Remarks,
        pm.MethodName,
        COALESCE(ep.ParticularNameEN, emp.NameEN, e.ParticularID) AS ParticularNameEN,
        COALESCE(ep.ParticularNameBN, emp.NameBN, emp.NameEN, e.ParticularID) AS ParticularNameBN,
        COALESCE(ec.ExpenseCategoryID, ec_sal.ExpenseCategoryID, 11) AS ExpenseCategoryID,
        COALESCE(ec.CategoryNameEN, ec_sal.CategoryNameEN, 'Salary') AS CategoryNameEN,
        COALESCE(ec.CategoryNameBN, ec_sal.CategoryNameBN, 'বেতন') AS CategoryNameBN
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
    LEFT JOIN mst_expensecategory ec_sal 
        ON (emp.Id IS NOT NULL OR emp.EmployeeId IS NOT NULL) 
        AND (ec_sal.ExpenseCategoryID = 11 OR ec_sal.CategoryNameEN = 'Salary')
        AND ec_sal.IsDeleted = 0
    LEFT JOIN cfg_paymentmethod pm ON e.PaymentMethodID = pm.PaymentMethodID
    WHERE e.ExpenseDate BETWEEN ? AND ?
      AND e.IsActive = 1 AND e.IsDeleted = 0
    ORDER BY ParticularNameEN ASC, e.ExpenseDate ASC, e.ExpenseID ASC";

    $fullDetailRecords = $objQuery->index($sqlFullDetails, [$startDate, $endDate]);

    $detailsGrandTotal = 0;
    $groupedByDate = [];
    $groupParticularSummaryMap = [];
    foreach ($fullDetailRecords as $f) {
        $amountVal = floatval($f->Amount);
        $detailsGrandTotal += $amountVal;
        $dateKey = $f->ExpenseDate;
        if (!isset($groupedByDate[$dateKey])) {
            $groupedByDate[$dateKey] = [];
        }
        $groupedByDate[$dateKey][] = $f;

        $catId = $f->ExpenseCategoryID;
        $pid   = $f->ParticularID;
        $particularName = $lang === 'en'
            ? (!empty($f->ParticularNameEN) ? $f->ParticularNameEN : ($f->ParticularNameBN ?? '—'))
            : (!empty($f->ParticularNameBN) ? $f->ParticularNameBN : ($f->ParticularNameEN ?? '—'));

        if (!isset($groupParticularSummaryMap[$catId])) {
            $groupParticularSummaryMap[$catId] = [];
        }
        if (!isset($groupParticularSummaryMap[$catId][$pid])) {
            $groupParticularSummaryMap[$catId][$pid] = [
                'particular_id'   => $pid,
                'particular_name' => $particularName,
                'total_amount'    => 0,
                'entry_count'     => 0,
                'transactions'    => []
            ];
        }
        $groupParticularSummaryMap[$catId][$pid]['total_amount'] += $amountVal;
        $groupParticularSummaryMap[$catId][$pid]['entry_count']  += 1;
        $groupParticularSummaryMap[$catId][$pid]['transactions'][] = [
            'date'    => formatDate($f->ExpenseDate),
            'amount'  => formatCurrency($amountVal),
            'method'  => $f->MethodName ?? '-',
            'ref'     => $f->ReferenceNo ?? '',
            'remarks' => $f->Remarks ?? ''
        ];
    }

    // Sort Particulars within each group ASC by particular_name
    foreach ($groupParticularSummaryMap as $catId => &$pDataMap) {
        uasort($pDataMap, function($a, $b) {
            return strnatcasecmp($a['particular_name'], $b['particular_name']);
        });
    }
    unset($pDataMap);
}

// ─────────────────────────────────────────────────────────────────────────────
// COMPANY INFO & REPORT HEADER METADATA
// ─────────────────────────────────────────────────────────────────────────────
$company = getCompanyInfo();
if ($company && !empty($company->TimeZone)) {
    date_default_timezone_set($company->TimeZone);
} else {
    date_default_timezone_set('Asia/Dhaka');
}

if ($lang === 'en') {
    $companyName    = !empty($company->CompanyName) ? $company->CompanyName : 'Shangu LPG Filling Station';
    $companyAddress = !empty($company->AddressEN) ? $company->AddressEN : (!empty($company->Address) ? $company->Address : 'Amilaish, Satkania, Chattogram.');
    $companyMobile  = $company->MobileNo ?? ($company->PhoneNo ?? '01819800600');
    $companyEmail   = $company->Email ?? 'sfs@gmail.com';
} else {
    $companyName    = !empty($company->CompanyNameBN) ? $company->CompanyNameBN : 'সাঙ্গু এল.পি.জি ফিলিং ষ্টেশন';
    $companyAddress = !empty($company->AddressBN) ? $company->AddressBN : (!empty($company->Address) ? $company->Address : 'আমিলাইশ, সাতকানিয়া, চট্টগ্রাম।');
    $rawMobile      = $company->MobileNo ?? ($company->PhoneNo ?? '01819800600');
    $companyMobile  = strtr((string)$rawMobile, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']);
    $companyEmail   = $company->Email ?? 'sfs@gmail.com';
}
$reportTitle    = ($lang === 'en') ? 'Monthly Expense Item-Wise Report' : 'মাসিক আইটেম-ভিত্তিক খরচ বিবরণী';
$formattedMonthDisplay = $lang === 'en' ? formatEnglishMonthYear($monthYear) : formatBanglaMonthYear($monthYear);
?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     STYLES: MODERN BANDED TABLE & PROFESSIONAL PRINT LAYOUT
════════════════════════════════════════════════════════════════════════════ -->
<style>
/* Modern Report Card Container */
.report-card {
    border-radius: 12px;
    border: 1px solid #cbd5e1;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    background: #ffffff;
    overflow: hidden;
}

/* Modern Styled Table */
.modern-expense-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-family: 'SolaimanLipi', 'Kalpurush', 'Nikosh', 'Arial', sans-serif;
}

/* Table Header - Gray Theme with Black Text */
.modern-expense-table thead tr th,
.table-royal-header,
.table thead tr th,
.table thead th {
    background: #e2e8f0 !important;
    color: #000000 !important;
    font-size: 1rem;
    font-weight: 700;
    padding: 12px 18px;
    border-bottom: 2px solid #cbd5e1;
    letter-spacing: 0.3px;
}

.modern-expense-table thead tr th.col-group {
    width: 30%;
    text-align: left;
}

.modern-expense-table thead tr th.col-particular {
    width: 45%;
    text-align: left;
}

.modern-expense-table thead tr th.col-amount {
    width: 25%;
    text-align: right;
}

/* Banded Rows (Odd/Even Row Style) */
.modern-expense-table tbody tr.odd-row {
    background-color: #ffffff;
}

.modern-expense-table tbody tr.even-row {
    background-color: #f8fafc;
}

/* Interactive Clickable Rows with Hover State */
.modern-expense-table tbody tr.clickable-row {
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}

.modern-expense-table tbody tr.clickable-row:hover,
.table tbody tr.clickable-group-row:hover {
    background-color: #bae6fd !important;
    transform: scale(1.001);
}

.modern-expense-table td {
    padding: 12px 18px;
    font-size: 0.95rem;
    color: #1e293b;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.modern-expense-table td.col-amount {
    font-weight: 700;
    color: #0f172a;
    text-align: right;
    font-size: 1.05rem;
}

/* Table Footer - Light Blue / Gray */
.modern-expense-table tfoot tr td {
    background: #e2e8f0;
    color: #000000;
    font-size: 1.1rem;
    font-weight: 800;
    padding: 14px 18px;
    border-top: 2px solid #cbd5e1;
}

.modern-expense-table tfoot tr td.total-label {
    text-align: right;
    color: #000000;
}

.modern-expense-table tfoot tr td.total-amount {
    text-align: right;
    color: #0284c7;
}

/* Printable Header Styling */
.report-header {
    border-bottom: 2px solid #0284c7;
    margin-bottom: 20px;
    padding-bottom: 10px;
}
.company-name {
    font-size: 24px;
    color: #0f172a;
    font-weight: 800;
}
.company-address {
    font-size: 13.5px;
}
.report-title-badge {
    background-color: #f1f5f9;
    color: #0f172a;
    border: 1px solid #cbd5e1;
}

@media print {
    /* Hide non-printable elements */
    #sidebar, .top-header, .top-navbar, .no-print, .btn, nav, .click-hint, .modal, .modal-backdrop, .sidebar, header, footer {
        display: none !important;
    }

    @page {
        size: A4 portrait;
        margin: 10mm 5mm 12mm 5mm;
        @bottom-right {
            content: "<?php echo $lang === 'en' ? 'Page: ' : 'পৃষ্ঠা: '; ?>" counter(page) " <?php echo $lang === 'en' ? ' of ' : ' এর '; ?>" counter(pages);
            font-size: 9.5pt;
            font-family: 'SolaimanLipi', 'Kalpurush', 'Nikosh', 'Arial', sans-serif;
            color: #1e293b;
            font-weight: bold;
        }
    }

    body {
        background: #ffffff !important;
        color: #000000 !important;
        font-family: 'SolaimanLipi', 'Kalpurush', 'Nikosh', 'Arial', sans-serif !important;
        font-size: 11.5pt !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    #content-wrapper, .wrapper, .main-content, .table-container {
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
        background: transparent !important;
        width: 100% !important;
    }

    .report-header {
        border-bottom: 2px solid #000000 !important;
        margin-bottom: 12px !important;
        padding-bottom: 6px !important;
        text-align: center !important;
    }

    .company-name {
        font-size: 20pt !important;
        color: #000000 !important;
        font-weight: 800 !important;
        margin-bottom: 4px !important;
    }

    .company-address {
        font-size: 10.5pt !important;
        color: #333333 !important;
        margin-bottom: 4px !important;
    }

    .report-title-badge {
        background: none !important;
        border: none !important;
        color: #000000 !important;
        font-size: 13pt !important;
        font-weight: bold !important;
        text-decoration: underline !important;
        padding: 0 !important;
    }

    .report-card, .group-detail-card {
        border: 1px solid #cbd5e1 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        margin-bottom: 12px !important;
        page-break-inside: auto !important;
    }

    .expense-summary-card {
        margin-bottom: 15px !important;
    }

    .expense-details-card {
        page-break-before: always !important;
        break-before: page !important;
    }

    .expense-summary-card table th {
        font-size: 13pt !important;
        padding: 7px 10px !important;
    }

    .expense-summary-card table td {
        font-size: 12.5pt !important;
        padding: 7px 10px !important;
    }

    .expense-summary-card table tfoot td {
        font-size: 13.5pt !important;
    }

    tr {
        page-break-inside: avoid !important;
    }

    .card-header {
        background: #e2e8f0 !important;
        color: #000000 !important;
        border-bottom: 1px solid #cbd5e1 !important;
        padding: 6px 10px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .card-header h6, .card-header i {
        color: #000000 !important;
        font-size: 11.5pt !important;
    }

    .badge {
        border: 1px solid #000000 !important;
        color: #000000 !important;
        background: none !important;
    }

    /* Print Tables & Data Font Sizes */
    .modern-expense-table, table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 11.5pt !important;
    }

    .modern-expense-table thead tr th, table thead tr th, table thead th {
        background-color: #e2e8f0 !important;
        color: #000000 !important;
        border: 1px solid #94a3b8 !important;
        padding: 6px 8px !important;
        font-weight: bold !important;
        font-size: 12pt !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .modern-expense-table thead tr th span.text-primary,
    table thead tr th span.text-primary,
    table thead tr th span {
        color: #1e40af !important;
        font-weight: 700 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .modern-expense-table tbody tr td, table tbody tr td {
        border: 1px solid #666666 !important;
        padding: 5px 8px !important;
        color: #000000 !important;
        background: #ffffff !important;
        font-size: 11.5pt !important;
    }

    .modern-expense-table tfoot tr td, table tfoot tr td {
        background-color: #e2e8f0 !important;
        color: #000000 !important;
        font-weight: bold !important;
        border: 1px solid #94a3b8 !important;
        padding: 6px 8px !important;
        font-size: 12.5pt !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .modern-expense-table tfoot tr td.total-label,
    .modern-expense-table tfoot tr td.total-amount {
        color: #000000 !important;
    }

    .progress {
        border: 1px solid #cbd5e1 !important;
        background-color: #e2e8f0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .progress-bar, .progress-bar.bg-primary {
        background-color: #2563eb !important;
        background: #2563eb !important;
        color: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print-signatures {
        display: block !important;
        margin-top: 35px !important;
        page-break-inside: avoid !important;
    }


}
</style>

<!-- ═══════════════════════════════════════════════════════════════════════════
     PAGE HTML
════════════════════════════════════════════════════════════════════════════ -->
<div class="table-container">
    

    
    <!-- Top Filter Toolbar -->
    <div class="card mb-4 no-print shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold"><i class="fas fa-calendar-alt text-primary me-1"></i> <?php echo $lblFromMonth; ?></label>
                    <input type="month" name="month_year" class="form-control" value="<?php echo $monthYear; ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold"><i class="fas fa-filter text-primary me-1"></i> <?php echo $lblReportType; ?></label>
                    <select name="report_type" class="form-select">
                        <option value="summary" <?php echo $reportType === 'summary' ? 'selected' : ''; ?>><?php echo $lblSummary; ?></option>
                        <option value="details" <?php echo $reportType === 'details' ? 'selected' : ''; ?>><?php echo $lblDetails; ?></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold"><i class="fas fa-language text-primary me-1"></i> <?php echo $lblLanguage; ?></label>
                    <select name="lang" class="form-select">
                        <option value="bn" <?php echo $lang === 'bn' ? 'selected' : ''; ?>>Bangla (বাংলা)</option>
                        <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>English</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-search me-1"></i> <?php echo $lblGenerate; ?></button>
                    <button type="button" onclick="window.print()" class="btn btn-success"><i class="fas fa-print me-1"></i> <?php echo $lblPrint; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Printable Report Header (Hidden on UI screen, shown only when printing) -->
    <div class="report-header text-center mb-4 pb-2 d-none d-print-block">
        <h2 class="fw-bold mb-1 company-name" style="font-family: 'SolaimanLipi', 'Kalpurush', 'Nikosh', 'Arial', sans-serif;">
            <?php echo htmlspecialchars($companyName); ?>
        </h2>
        <?php if (!empty($companyAddress) || !empty($companyMobile) || !empty($companyEmail)): ?>
            <p class="mb-1 company-address text-muted">
                <?php echo htmlspecialchars($companyAddress); ?>
                <?php if (!empty($companyMobile)): ?>
                    <span class="mx-1">|</span> <i class="fas fa-phone-alt me-1"></i><?php echo htmlspecialchars($companyMobile); ?>
                <?php endif; ?>
                <?php if (!empty($companyEmail)): ?>
                    <span class="mx-1">|</span> <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($companyEmail); ?>
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <div class="mt-2">
            <span class="report-title-badge d-inline-block px-3 py-1 rounded fw-bold fs-6">
                <?php echo htmlspecialchars($reportTitle); ?> — <span class="text-primary"><?php echo htmlspecialchars($formattedMonthDisplay); ?></span>
            </span>
        </div>
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

    <!-- ═══════════════════════════════════════════════════════════════════════════
         EXPENSE PARTICULAR GROUP SUMMARY CARD (SHOWS AFTER FILTER SECTION)
    ════════════════════════════════════════════════════════════════════════════ -->
    <div class="card mb-4 shadow-sm border-0 report-card expense-summary-card">
        <div class="card-header text-white d-flex justify-content-between align-items-center py-2 px-3" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);">
            <h6 class="mb-0 fw-bold text-white">
                <i class="fas fa-layer-group text-warning me-2"></i>
                <?php echo htmlspecialchars($lblGroupSummaryCard); ?>
            </h6>
            <span class="badge bg-light text-dark fw-bold fs-7">
                <?php echo count($categorySummaryRecords); ?> <?php echo $lang === 'en' ? 'Groups' : 'টি গ্রুপ'; ?>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" style="font-family: 'SolaimanLipi', 'Kalpurush', 'Nikosh', 'Arial', sans-serif;">
                    <thead style="background-color: #e2e8f0; color: #000000;">
                        <tr>
                            <th style="width: 55%; color: #000000;" class="ps-3"><?php echo htmlspecialchars($lblGroup); ?></th>
                            <th style="width: 30%; color: #000000;" class="text-end pe-3"><?php echo htmlspecialchars($lblAmount); ?></th>
                            <th style="width: 15%; color: #000000;" class="pe-3"><?php echo htmlspecialchars($lblShare); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($categorySummaryRecords)): foreach ($categorySummaryRecords as $catRow): 
                            $catGroupName = $lang === 'en' 
                                ? (!empty($catRow->CategoryNameEN) ? $catRow->CategoryNameEN : ($catRow->CategoryNameBN ?? 'Other')) 
                                : (!empty($catRow->CategoryNameBN) ? $catRow->CategoryNameBN : ($catRow->CategoryNameEN ?? 'অন্যান্য'));
                            $catAmount  = floatval($catRow->CategoryTotal);
                            $catPercent = $catSummaryTotalAmount > 0 ? ($catAmount / $catSummaryTotalAmount) * 100 : 0;
                        ?>
                        <tr class="clickable-group-row"
                            style="cursor: pointer;"
                            data-category-id="<?php echo htmlspecialchars($catRow->ExpenseCategoryID); ?>"
                            data-group-name="<?php echo htmlspecialchars($catGroupName); ?>"
                            data-total="<?php echo formatCurrency($catAmount); ?>"
                            title="<?php echo $lang === 'en' ? 'Click to view group detailed transactions' : 'এই ব্যয়ের গ্রুপের সকল বিস্তারিত লেনদেন দেখতে ক্লিক করুন'; ?>">
                            <td class="ps-3 fw-bold text-dark" style="font-size: 1.1rem;">
                                <i class="fas fa-search-plus text-primary me-1 d-none-print" style="font-size: 0.9rem;"></i>
                                <?php echo htmlspecialchars($catGroupName); ?>
                            </td>
                            <td class="text-end pe-3 fw-bold text-dark" style="font-size: 1.15rem;"><?php echo formatCurrency($catAmount); ?></td>
                            <td class="pe-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 10px; background-color: #e2e8f0; border-radius: 4px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo number_format($catPercent, 1); ?>%; border-radius: 4px;" aria-valuenow="<?php echo number_format($catPercent, 1); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="fw-bold text-dark" style="min-width: 52px; text-align: right; font-size: 1.05rem;"><?php echo number_format($catPercent, 1); ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="3" class="text-center py-3 text-muted"><?php echo $lblNoData; ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($categorySummaryRecords)): ?>
                    <tfoot class="fw-bold" style="background-color: #e2e8f0; color: #000000;">
                        <tr>
                            <td class="ps-3 fw-bold" style="color: #000000; font-size: 1.15rem;"><?php echo htmlspecialchars($lblTotal); ?></td>
                            <td class="text-end pe-3 text-primary" style="font-weight: 800; font-size: 1.25rem;"><?php echo formatCurrency($catSummaryTotalAmount); ?></td>
                            <td class="pe-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 10px; background-color: rgba(0,0,0,0.1); border-radius: 4px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%; border-radius: 4px;"></div>
                                    </div>
                                    <span class="fw-bold text-dark" style="min-width: 52px; text-align: right; font-size: 1.05rem;">100.0%</span>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════
         EXPENSE DETAILS SECTION (GROUP-WISE BREAKDOWN)
    ════════════════════════════════════════════════════════════════════════════ -->
    <div class="card mb-4 shadow-sm border-0 report-card expense-details-card">
        <div class="card-header bg-gradient bg-dark text-white d-flex justify-content-between align-items-center py-2 px-3">
            <h6 class="mb-0 fw-bold text-white">
                <i class="fas fa-list-alt text-warning me-2"></i>
                <?php echo $lang === 'en' ? 'Expense Details' : 'খরচের বিবরণ'; ?>
            </h6>
            <span class="badge bg-light text-dark fw-bold fs-7">
                <?php echo count($categorySummaryRecords); ?> <?php echo $lang === 'en' ? 'Groups' : 'টি গ্রুপ'; ?>
            </span>
        </div>
        <div class="card-body p-3">
            <?php 
            if (!empty($categorySummaryRecords)):
                foreach ($categorySummaryRecords as $catRow): 
                    $catId = $catRow->ExpenseCategoryID;
                    $catGroupName = $lang === 'en' 
                        ? (!empty($catRow->CategoryNameEN) ? $catRow->CategoryNameEN : ($catRow->CategoryNameBN ?? 'Other')) 
                        : (!empty($catRow->CategoryNameBN) ? $catRow->CategoryNameBN : ($catRow->CategoryNameEN ?? 'অন্যান্য'));
                    
                    $pDataMap = $groupParticularSummaryMap[$catId] ?? [];
                    if (empty($pDataMap)) continue;
            ?>
            <div class="mb-4 rounded border shadow-sm group-detail-card" style="page-break-inside: avoid; overflow: hidden;">
                <!-- Group Transactions Table -->
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" style="font-family: 'SolaimanLipi', 'Kalpurush', 'Nikosh', 'Arial', sans-serif;">
                        <thead style="background-color: #e2e8f0; color: #000000;">
                            <tr>
                                <th style="width: 8%; color: #000000;" class="text-center">#</th>
                                <th style="width: 64%; color: #000000;"><span class="text-dark me-1" style="font-size: 1.15rem; font-weight: 800; color: #000000 !important;"><?php echo htmlspecialchars($catGroupName); ?></span> — <?php echo $lang === 'en' ? 'Particular' : 'বিবরণ (Particular)'; ?></th>
                                <th style="width: 28%; color: #000000;" class="text-end"><?php echo $lang === 'en' ? 'Total Amount (' . $currencySymbol . ')' : 'মোট পরিমাণ (৳)'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $pIndex = 1;
                            $catTotalEntries = 0;
                            $catTotalAmount = 0;
                            foreach ($pDataMap as $pid => $item):
                                $catTotalEntries += intval($item['entry_count']);
                                $catTotalAmount += floatval($item['total_amount']);
                            ?>
                            <!-- Particular Header Row -->
                            <tr class="fw-bold bg-light align-middle" style="border-top: 2px solid #cbd5e1;">
                                <td class="text-center text-secondary"><?php echo $pIndex; ?></td>
                                <td>
                                    <i class="fas fa-folder text-primary me-2"></i>
                                    <strong class="text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars($item['particular_name']); ?></strong>
                                    <small class="text-muted ms-2 fw-normal" style="font-size: 0.78rem;">(<?php echo $item['entry_count']; ?> <?php echo $lang === 'en' ? 'entries' : 'টি এন্ট্রি'; ?>)</small>
                                </td>
                                <td class="text-end text-danger fw-bold fs-6">৳<?php echo formatCurrency($item['total_amount']); ?></td>
                            </tr>

                            <!-- Transaction Details Sub-Rows -->
                            <?php if (!empty($item['transactions'])): foreach ($item['transactions'] as $tx): ?>
                            <tr style="font-size: 0.88rem; background-color: #ffffff;">
                                <td></td>
                                <td class="ps-4 text-muted">
                                    <i class="far fa-calendar-alt me-1 text-info"></i>
                                    <strong class="text-dark"><?php echo $tx['date']; ?></strong>
                                    <?php if (!empty($tx['remarks'])): ?>
                                        <span class="ms-2 text-dark small">[<?php echo htmlspecialchars($tx['remarks']); ?>]</span>
                                    <?php endif; ?>
                                    <span class="ms-2 text-secondary small" style="font-size: 0.8rem;">(<?php echo htmlspecialchars($tx['method']); ?><?php if (!empty($tx['ref'])): ?> - <?php echo htmlspecialchars($tx['ref']); ?><?php endif; ?>)</span>
                                </td>
                                <td class="text-end text-dark font-monospace"><?php echo $tx['amount']; ?></td>
                            </tr>
                            <?php endforeach; endif; ?>

                            <?php $pIndex++; endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold fs-6">
                            <tr>
                                <td colspan="2" class="text-end fw-bold">
                                    <?php echo $lang === 'en' ? 'Total Group Expenses:' : 'গ্রুপের সর্বমোট খরচ:'; ?>
                                    <small class="text-muted ms-2 fw-normal" style="font-size: 0.82rem;">(<?php echo $catTotalEntries; ?> <?php echo $lang === 'en' ? 'entries' : 'টি এন্ট্রি'; ?>)</small>
                                </td>
                                <td class="text-end text-danger fw-bold fs-6"><?php echo formatCurrency($catTotalAmount); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="text-center py-4 text-muted"><?php echo $lblNoData; ?></div>
            <?php endif; ?>
        </div>
    </div>



    <!-- Printable Signature Block (Hidden on UI screen, shown only when printing) -->
    <div class="print-signatures mt-5 pt-4 d-none d-print-block">
        <div class="row text-center">
            <div class="col-4">
                <div class="border-top border-dark pt-1 fw-bold fs-7">
                    <?php echo $lang === 'en' ? 'Prepared By' : 'প্রস্তুতকারকের স্বাক্ষর'; ?>
                </div>
            </div>
            <div class="col-4">
                <div class="border-top border-dark pt-1 fw-bold fs-7">
                    <?php echo $lang === 'en' ? 'Checked By' : 'যাচাইকারীর স্বাক্ষর'; ?>
                </div>
            </div>
            <div class="col-4">
                <div class="border-top border-dark pt-1 fw-bold fs-7">
                    <?php echo $lang === 'en' ? 'Approved By' : 'অনুমোদনকারীর স্বাক্ষর'; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     EXPENSE DETAIL DRILL-DOWN MODAL
════════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="expenseDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);">
                <h5 class="modal-title fw-bold text-white">
                    <i class="fas fa-list-alt me-2 text-warning"></i>
                    <span id="modalParticular"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 p-3 rounded bg-light border">
                    <div>
                        <span class="text-muted small d-block"><?php echo $lang === 'en' ? 'Expense Group' : 'ব্যয়ের গ্রুপ'; ?></span>
                        <strong class="fs-6 text-primary" id="modalGroup"></strong>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small d-block"><?php echo $lang === 'en' ? 'Month' : 'মাস'; ?></span>
                        <strong class="fs-6 text-dark"><?php echo $lang === 'en' ? formatEnglishMonthYear($monthYear) : formatBanglaMonthYear($monthYear); ?></strong>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead style="background-color: #e2e8f0; color: #000000;" id="modalTableHeader">
                            <tr>
                                <th style="width: 6%;" class="text-center">#</th>
                                <th style="width: 18%;"><?php echo $lang === 'en' ? 'Date' : 'তারিখ'; ?></th>
                                <th style="width: 20%;" class="text-end"><?php echo $lang === 'en' ? 'Amount (' . $currencySymbol . ')' : 'পরিমাণ (' . $currencySymbol . ')'; ?></th>
                                <th style="width: 18%;"><?php echo $lang === 'en' ? 'Payment Method' : 'পেমেন্ট মেথড'; ?></th>
                                <th style="width: 18%;"><?php echo $lang === 'en' ? 'Reference' : 'রেফারেন্স'; ?></th>
                                <th style="width: 20%;"><?php echo $lang === 'en' ? 'Remarks' : 'মন্তব্য'; ?></th>
                            </tr>
                        </thead>
                        <tbody id="modalTableBody">
                        </tbody>
                        <tfoot class="table-light fw-bold fs-6">
                            <tr id="modalFooterRow">
                                <td colspan="2" class="text-end" id="modalFooterTotalLabel"><?php echo $lang === 'en' ? 'Total Amount:' : 'মোট পরিমাণ:'; ?></td>
                                <td class="text-end text-danger" id="modalTotalAmount"></td>
                                <td colspan="3" id="modalFooterRestColspan"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> <?php echo $lang === 'en' ? 'Close' : 'বন্ধ করুন'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const detailsMap = <?php echo json_encode($detailsMap ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const groupParticularSummaryMap = <?php echo json_encode($groupParticularSummaryMap ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const lang = "<?php echo $lang; ?>";
    const currencySymbol = "<?php echo $currencySymbol; ?>";

    function formatCurrencyJs(num) {
        return '৳' + parseFloat(num).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Particular row click
    $(document).on('click', '.clickable-row', function() {
        const pid = $(this).data('particular-id');
        const groupName = $(this).data('group');
        const particularName = $(this).data('particular');
        const totalAmount = $(this).data('total');

        $('#modalGroup').text(groupName);
        $('#modalParticular').text(particularName);

        $('#modalTableHeader').html(`<tr>
            <th style="width: 6%;" class="text-center">#</th>
            <th style="width: 18%;">${lang === 'en' ? 'Date' : 'তারিখ'}</th>
            <th style="width: 20%;" class="text-end">${lang === 'en' ? 'Amount (' + currencySymbol + ')' : 'পরিমাণ (' + currencySymbol + ')'}</th>
            <th style="width: 18%;">${lang === 'en' ? 'Payment Method' : 'পেমেন্ট মেথড'}</th>
            <th style="width: 18%;">${lang === 'en' ? 'Reference' : 'রেফারেন্স'}</th>
            <th style="width: 20%;">${lang === 'en' ? 'Remarks' : 'মন্তব্য'}</th>
        </tr>`);

        $('#modalFooterTotalLabel').attr('colspan', 2);
        $('#modalFooterRestColspan').attr('colspan', 3);

        const items = detailsMap[pid] || [];
        let rowsHtml = '';

        if (items.length > 0) {
            items.forEach(function(item, idx) {
                rowsHtml += `<tr>
                    <td class="text-center fw-bold">${idx + 1}</td>
                    <td>${item.date}</td>
                    <td class="text-end fw-bold text-danger">${item.amount}</td>
                    <td>${item.method}</td>
                    <td>${item.ref || '-'}</td>
                    <td>${item.remarks || '-'}</td>
                </tr>`;
            });
        } else {
            const noDataMsg = (lang === 'en') ? 'No detailed records found.' : 'কোনো বিস্তারিত এন্ট্রি পাওয়া যায়নি।';
            rowsHtml = `<tr><td colspan="6" class="text-center text-muted py-3">${noDataMsg}</td></tr>`;
        }

        $('#modalTableBody').html(rowsHtml);
        $('#expenseDetailModal').modal('show');
    });

    // Expense Group Summary row click (Grouped by ParticularID)
    $(document).on('click', '.clickable-group-row', function() {
        const catId = $(this).data('category-id');
        const groupName = $(this).data('group-name');
        const totalAmount = $(this).data('total');

        $('#modalGroup').text(groupName);
        $('#modalParticular').text(lang === 'en' ? groupName + ' — Particular Summary' : groupName + ' — বিবরণ ভিত্তিক সারসংক্ষেপ');

        $('#modalTableHeader').html(`<tr>
            <th style="width: 8%;" class="text-center">#</th>
            <th style="width: 64%;">${lang === 'en' ? 'Particular Name' : 'বিবরণ (Particular)'}</th>
            <th style="width: 28%;" class="text-end">${lang === 'en' ? 'Total Amount (' + currencySymbol + ')' : 'মোট পরিমাণ (' + currencySymbol + ')'}</th>
        </tr>`);

        const pDataMap = groupParticularSummaryMap[catId] || {};
        const pList = Object.values(pDataMap);
        let rowsHtml = '';
        let totalEntries = 0;

        if (pList.length > 0) {
            pList.forEach(function(item, idx) {
                totalEntries += item.entry_count;
                rowsHtml += `<tr class="fw-bold bg-light align-middle" style="border-top: 2px solid #cbd5e1;">
                    <td class="text-center text-secondary">${idx + 1}</td>
                    <td>
                        <i class="fas fa-folder text-primary me-2"></i>
                        <strong>${item.particular_name}</strong>
                        <small class="text-muted ms-2 fw-normal" style="font-size: 0.78rem;">(${item.entry_count} ${lang === 'en' ? 'entries' : 'টি এন্ট্রি'})</small>
                    </td>
                    <td class="text-end text-danger fw-bold fs-6">${formatCurrencyJs(item.total_amount)}</td>
                </tr>`;

                if (item.transactions && item.transactions.length > 0) {
                    item.transactions.forEach(function(tx) {
                        rowsHtml += `<tr style="font-size: 0.88rem; background-color: #ffffff;">
                            <td></td>
                            <td class="ps-4 text-muted">
                                <i class="far fa-calendar-alt me-1 text-info"></i> <strong>${tx.date}</strong>
                                ${tx.remarks ? ' <span class="ms-2 text-dark small">[' + tx.remarks + ']</span>' : ''}
                                <span class="ms-2 text-secondary small" style="font-size: 0.8rem;">(${tx.method}${tx.ref ? ' - ' + tx.ref : ''})</span>
                            </td>
                            <td class="text-end text-dark font-monospace">${tx.amount}</td>
                        </tr>`;
                    });
                }
            });
        } else {
            const noDataMsg = (lang === 'en') ? 'No detailed records found.' : 'কোনো বিস্তারিত এন্ট্রি পাওয়া যায়নি।';
            rowsHtml = `<tr><td colspan="3" class="text-center text-muted py-3">${noDataMsg}</td></tr>`;
        }

        $('#modalTableBody').html(rowsHtml);

        $('#modalFooterRow').html(`
            <td colspan="2" class="text-end fw-bold" id="modalFooterTotalLabel">
                ${lang === 'en' ? 'Total Group Expenses:' : 'গ্রুপের সর্বমোট খরচ:'}
                <small class="text-muted ms-2 fw-normal" style="font-size: 0.82rem;">(${totalEntries} ${lang === 'en' ? 'entries' : 'টি এন্ট্রি'})</small>
            </td>
            <td class="text-end text-danger fw-bold fs-6" id="modalTotalAmount">${totalAmount}</td>
        `);

        $('#expenseDetailModal').modal('show');
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
