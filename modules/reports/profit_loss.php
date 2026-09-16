<?php
/**
 * FuelDeskPro - Profit / Loss Report  (profit_loss.php)
 *
 * Calculates gross profit and net profit for a selected date range using the
 * Average Cost (AVCO) method.
 * Supports bilingual display (Bangla / English).
 *
 * @package FuelDeskPro
 */

$pageTitle = 'Profit / Loss Report';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// ─────────────────────────────────────────────────────────────────────────────
// INPUT & LANGUAGE HANDLING
// ─────────────────────────────────────────────────────────────────────────────
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date']   ?? date('Y-m-t');
$lang      = $_GET['lang']       ?? currentLang();

if (!in_array($lang, ['bn', 'en'])) { $lang = 'bn'; }

if (strlen($startDate) === 7) { $startDate .= '-01'; }
if (strlen($endDate)   === 7) { $endDate   .= '-' . date('t', strtotime($endDate . '-01')); }

$prevDay  = date('Y-m-d', strtotime($startDate . ' -1 day'));   // day before period
$generate = isset($_GET['generate']);

// ─────────────────────────────────────────────────────────────────────────────
// LANGUAGE LABELS
// ─────────────────────────────────────────────────────────────────────────────
if ($lang === 'en') {
    $lblReportTitle          = 'Profit / Loss Report';
    $lblFromDate             = 'From Date';
    $lblToDate               = 'To Date';
    $lblLanguage             = 'Language';
    $lblGenerate             = 'Generate Report';
    $lblPrint                = 'Print';
    $lblPeriod               = 'Period';
    $lblMethod               = 'Method';
    $lblGenerated            = 'Generated';
    
    // Metric Cards
    $lblCardTotalSales       = 'Total Sales';
    $lblCardCOGS             = 'Cost of Goods Sold';
    $lblCardGrossProfit      = 'Gross Profit';
    $lblCardNetProfit        = 'Net Profit';
    $lblCardNetLoss          = 'Net Loss';
    $lblCardClosingVal       = 'Closing Stock Value';
    $lblCardFuelSold         = 'Fuel Sold';
    $lblCardOperatingExp     = 'Operating Expenses';
    $lblCardProfitPct        = 'Profit %';
    $lblCardSoldSub          = 'L sold';
    $lblCardPurchCostSub     = 'Purchase cost:';
    $lblCardMarginSub        = 'Margin:';
    $lblCardNetMarginSub     = 'Net margin:';
    $lblCardAvcoSub          = 'Inventory at AVCO';
    $lblCardPeriodSub        = 'In selected period';
    $lblCardOtherIncSub      = 'Other income:';
    $lblCardGrossSub         = 'Gross:';

    // Section 1: Fuel-wise Statement
    $lblFuelSecTitle         = 'Fuel-wise Inventory & Profit Statement';
    $lblThItem               = 'Item';
    $lblThOpenStock          = 'Opening Stock';
    $lblThPurchase           = 'Purchase';
    $lblThTotAvailable       = 'Total Available';
    $lblThSoldQty            = 'Sold Qty';
    $lblThClosingStock       = 'Closing Stock';
    $lblThCOGS               = 'COGS';
    $lblThSalesAmt           = 'Sales Amt';
    $lblThGrossProfit        = 'Gross Profit';
    $lblThMargin             = 'Margin';
    $lblThQtyL               = 'Qty (L)';
    $lblThValue              = 'Value (' . $currencySymbol . ')';
    $lblThCost               = 'Cost (' . $currencySymbol . ')';
    $lblNoData               = 'No data found for the selected period.';
    $lblTotal                = 'TOTAL';

    // Section 2: Operating Expenses Breakdown
    $lblExpSecTitle          = 'Operating Expenses Breakdown';
    $lblThCategory           = 'Category';
    $lblThParticular         = 'Particular';
    $lblThEntries            = 'Entries';
    $lblThAmount             = 'Amount (' . $currencySymbol . ')';
    $lblTotalExpenses        = 'Total Expenses:';

    // Section 3: Sales Analysis
    $lblSalesAnalysisTitle   = 'Sales Analysis — Cash vs Credit';
    $lblThDescription        = 'Description';
    $lblThPctTotal           = '% of Total Sales';
    $lblCashSales            = 'Cash Sales';
    $lblCreditSales          = 'Credit Sales';

    // Section 4: Summary P&L
    $lblPLSummaryTitle       = 'Profit & Loss Summary';
    $lblOtherIncome          = 'Other Income';
    
    // Balance Sheet Highlights
    $lblBSHighlightsTitle    = 'Balance Sheet Highlights';
    $lblCustReceivable       = 'Customer Receivable';
    $lblSupplierPayable      = 'Supplier Payable';
    $lblCashSalesAmt         = 'Cash Sales Amount';
    $lblCreditSalesAmt       = 'Credit Sales Amount';
    $lblTotalPurchPeriod     = 'Total Purchase (Period)';
    $lblFuelSoldPeriod       = 'Fuel Sold (Period)';
    $lblOpenStockValue       = 'Opening Stock Value';

    // Note Section
    $lblCostingMethod        = 'Costing Method: Average Cost (AVCO)';
    $lblNoteOpenStock        = 'Opening Stock Value = Opening Qty × Weighted Avg Cost per litre (up to day before period)';
    $lblNoteCOGS             = 'COGS = Opening Stock Value + Purchase Cost − Closing Stock Value';
    $lblNoteGrossProfit      = 'Gross Profit = Sales Amount − COGS';
    $lblNoteNetProfit        = 'Net Profit = Gross Profit − Operating Expenses + Other Income';

    // Placeholder
    $lblSelectDateRange      = 'Select a date range and click Generate Report';
    $lblAvcoNote             = 'This report shows profit & loss using the Average Cost (AVCO) method.';
} else {
    // Bangla
    $lblReportTitle          = 'লাভ / ক্ষতি হিসাব রিপোর্ট';
    $lblFromDate             = 'শুরুর তারিখ';
    $lblToDate               = 'শেষ তারিখ';
    $lblLanguage             = 'ভাষা (Language)';
    $lblGenerate             = 'রিপোর্ট দেখুন';
    $lblPrint                = 'প্রিন্ট';
    $lblPeriod               = 'সময়কাল';
    $lblMethod               = 'পদ্ধতি';
    $lblGenerated            = 'তৈরির সময়';
    
    // Metric Cards
    $lblCardTotalSales       = 'মোট বিক্রয় (Total Sales)';
    $lblCardCOGS             = 'বিক্রীত পণ্যের ব্যয় (COGS)';
    $lblCardGrossProfit      = 'মোট লাভ (Gross Profit)';
    $lblCardNetProfit        = 'নিট লাভ (Net Profit)';
    $lblCardNetLoss          = 'নিট ক্ষতি (Net Loss)';
    $lblCardClosingVal       = 'সমাপনী মজুদের মূল্য';
    $lblCardFuelSold         = 'মোট জ্বালানি বিক্রয়';
    $lblCardOperatingExp     = 'পরিচালন ব্যয় (Expenses)';
    $lblCardProfitPct        = 'লাভের হার (%)';
    $lblCardSoldSub          = 'লিটার বিক্রয়';
    $lblCardPurchCostSub     = 'ক্রয় ব্যয়:';
    $lblCardMarginSub        = 'মার্জিন:';
    $lblCardNetMarginSub     = 'নিট মার্জিন:';
    $lblCardAvcoSub          = 'AVCO পদ্ধতিতে মজুদ';
    $lblCardPeriodSub        = 'নির্বাচিত সময়কালে';
    $lblCardOtherIncSub      = 'অন্যান্য আয়:';
    $lblCardGrossSub         = 'মোট লাভ:';

    // Section 1: Fuel-wise Statement
    $lblFuelSecTitle         = 'পণ্যভিত্তিক মজুদ ও লাভ-ক্ষতি বিবরণী';
    $lblThItem               = 'পণ্য/আইটেম';
    $lblThOpenStock          = 'প্রারম্ভিক মজুদ';
    $lblThPurchase           = 'ক্রয়';
    $lblThTotAvailable       = 'মোট মজুদ';
    $lblThSoldQty            = 'বিক্রয় পরিমাণ';
    $lblThClosingStock       = 'সমাপনী মজুদ';
    $lblThCOGS               = 'COGS (ব্যয়)';
    $lblThSalesAmt           = 'বিক্রয় মূল্য';
    $lblThGrossProfit        = 'মোট লাভ (Gross Profit)';
    $lblThMargin             = 'মার্জিন';
    $lblThQtyL               = 'পরিমাণ (লিটার)';
    $lblThValue              = 'মূল্য (' . $currencySymbol . ')';
    $lblThCost               = 'ক্রয়মূল্য (' . $currencySymbol . ')';
    $lblNoData               = 'নির্বাচিত সময়ে কোনো তথ্য পাওয়া যায়নি।';
    $lblTotal                = 'সর্বমোট (TOTAL)';

    // Section 2: Operating Expenses Breakdown
    $lblExpSecTitle          = 'পরিচালন ব্যয়ের বিবরণী (Operating Expenses)';
    $lblThCategory           = 'ক্যাটাগরি';
    $lblThParticular         = 'খাত/বিবরণ (Particular)';
    $lblThEntries            = 'এন্ট্রি সংখ্যা';
    $lblThAmount             = 'পরিমাণ (' . $currencySymbol . ')';
    $lblTotalExpenses        = 'সর্বমোট পরিচালন ব্যয়:';

    // Section 3: Sales Analysis
    $lblSalesAnalysisTitle   = 'বিক্রয় বিশ্লেষণ — নগদ বনাম বাকী';
    $lblThDescription        = 'বিবরণ';
    $lblThPctTotal           = 'মোট বিক্রয়ের %';
    $lblCashSales            = 'নগদ বিক্রয় (Cash Sales)';
    $lblCreditSales          = 'বাকী বিক্রয় (Credit Sales)';

    // Section 4: Summary P&L
    $lblPLSummaryTitle       = 'লাভ-ক্ষতি সংক্ষিপ্ত সারসংক্ষেপ';
    $lblOtherIncome          = 'অন্যান্য খাতে আয় (Other Income)';
    
    // Balance Sheet Highlights
    $lblBSHighlightsTitle    = 'ব্যালেন্স শিট মূল সূচকসমূহ';
    $lblCustReceivable       = 'গ্রাহকের নিকট মোট পাওনা (Customer Dues)';
    $lblSupplierPayable      = 'সাপ্লায়ার দেনা (Supplier Payable)';
    $lblCashSalesAmt         = 'নগদ বিক্রয় (Cash Sales)';
    $lblCreditSalesAmt       = 'বাকী বিক্রয় (Credit Sales)';
    $lblTotalPurchPeriod     = 'মোট ক্রয় মূল্য (সময়ের মধ্যে)';
    $lblFuelSoldPeriod       = 'মোট বিক্রয়কৃত লিটার';
    $lblOpenStockValue       = 'প্রারম্ভিক মজুদের মূল্য (Opening Stock)';

    // Note Section
    $lblCostingMethod        = 'হিসাব পদ্ধতি: এভারেজ কস্ট পদ্ধতি (AVCO - Average Costing)';
    $lblNoteOpenStock        = 'প্রারম্ভিক মজুদের মূল্য = প্রারম্ভিক লিটার × প্রতি লিটার এভারেজ ক্রয়মূল্য (সময়ের পূর্বের দিন পর্যন্ত)';
    $lblNoteCOGS             = 'বিক্রীত পণ্যের ব্যয় (COGS) = প্রারম্ভিক মজুদের মূল্য + মোট ক্রয় মূল্য − সমাপনী মজুদের মূল্য';
    $lblNoteGrossProfit      = 'মোট লাভ (Gross Profit) = মোট বিক্রয় মূল্য − COGS';
    $lblNoteNetProfit        = 'নিট লাভ (Net Profit) = মোট লাভ − পরিচালন ব্যয় + অন্যান্য আয়';

    // Placeholder
    $lblSelectDateRange      = 'তারিখ সিলেক্ট করে "রিপোর্ট দেখুন" বাটনে ক্লিক করুন';
    $lblAvcoNote             = 'এই রিপোর্টটি এভারেজ কস্ট (AVCO) পদ্ধতিতে লাভ ও ক্ষতি হিসাব করে।';
}

$pageTitle = $lblReportTitle;
$companyNameDisplay = $company ? ($lang === 'en' ? (!empty($company->CompanyName) ? $company->CompanyName : ($company->CompanyNameBN ?? 'FuelDeskPro')) : (!empty($company->CompanyNameBN) ? $company->CompanyNameBN : ($company->CompanyName ?? 'FuelDeskPro'))) : 'FuelDeskPro';
$companyAddressDisplay = $company ? ($lang === 'en' ? (!empty($company->AddressEN) ? $company->AddressEN : ($company->Address ?? '')) : (!empty($company->AddressBN) ? $company->AddressBN : ($company->AddressEN ?? ($company->Address ?? '')))) : '';

// ─────────────────────────────────────────────────────────────────────────────
// HELPER
// ─────────────────────────────────────────────────────────────────────────────
function safeFloat($v): float { return (float)($v ?? 0); }

// ─────────────────────────────────────────────────────────────────────────────
// MAIN DATA QUERIES  (all run only when generate is set)
// ─────────────────────────────────────────────────────────────────────────────
$fuelRows      = [];   // per-fuel P&L rows
$totals        = [];   // grand totals
$expenseDetail = [];   // expenses breakdown
$otherIncome   = 0;    // others collection (income)
$custReceivable= 0;    // customer balance due
$supplierPayable=0;    // supplier outstanding
$cashSaleAmt   = 0;
$creditSaleAmt = 0;

if ($generate) {

    // ── 1. ALL FUEL TYPES ────────────────────────────────────────────────────
    $allFuels = $objQuery->index(
        "SELECT FuelTypeID, FuelName FROM mst_fueltype WHERE IsActive=1 AND IsDeleted=0 ORDER BY FuelName"
    );

    foreach ($allFuels as $fuel) {
        $fid = $fuel->FuelTypeID;

        // ── 1a. Opening stock qty  (purchase before period − sales before period) ──
        $openPurchaseQty = safeFloat($objQuery->index(
            "SELECT COALESCE(SUM(Quantity),0) AS v FROM trx_fuelpurchase
              WHERE FuelTypeID=? AND PurchaseDate<=? AND IsActive=1 AND IsDeleted=0",
            [$fid, $prevDay]
        )[0]->v ?? 0);

        $openSalesQty = safeFloat($objQuery->index(
            "SELECT COALESCE(SUM(nr.SaleQuantity),0) AS v
               FROM trx_nozzlereading nr
               JOIN mst_nozzle n ON nr.NozzleID = n.NozzleID
              WHERE n.FuelTypeID=? AND nr.ReadingDate<=? AND nr.IsActive=1 AND nr.IsDeleted=0",
            [$fid, $prevDay]
        )[0]->v ?? 0);

        $openQty = max(0, $openPurchaseQty - $openSalesQty);

        // ── 1b. Opening stock VALUE  (avg cost × opening qty) ────────────────
        $allTimePurchaseCostPre = safeFloat($objQuery->index(
            "SELECT COALESCE(SUM(TotalAmount),0) AS v FROM trx_fuelpurchase
              WHERE FuelTypeID=? AND PurchaseDate<=? AND IsActive=1 AND IsDeleted=0",
            [$fid, $prevDay]
        )[0]->v ?? 0);

        $avgCostPre = $openPurchaseQty > 0 ? ($allTimePurchaseCostPre / $openPurchaseQty) : 0;
        $openStockValue = $openQty * $avgCostPre;

        // ── 1c. Purchases DURING period ───────────────────────────────────────
        $purchRow = $objQuery->index(
            "SELECT COALESCE(SUM(Quantity),0) AS Qty, COALESCE(SUM(TotalAmount),0) AS Cost
               FROM trx_fuelpurchase
              WHERE FuelTypeID=? AND PurchaseDate BETWEEN ? AND ?
                AND IsActive=1 AND IsDeleted=0",
            [$fid, $startDate, $endDate]
        );
        $purchQty  = safeFloat($purchRow[0]->Qty  ?? 0);
        $purchCost = safeFloat($purchRow[0]->Cost ?? 0);

        // ── 1d. Sales DURING period ───────────────────────────────────────────
        $salesRow = $objQuery->index(
            "SELECT COALESCE(SUM(nr.SaleQuantity),0) AS Qty,
                    COALESCE(SUM(nr.SalesAmt),0) AS Amount
               FROM trx_nozzlereading nr
               JOIN mst_nozzle n ON nr.NozzleID = n.NozzleID
              WHERE n.FuelTypeID=? AND nr.ReadingDate BETWEEN ? AND ?
                AND nr.IsActive=1 AND nr.IsDeleted=0",
            [$fid, $startDate, $endDate]
        );
        $soldQty    = safeFloat($salesRow[0]->Qty    ?? 0);
        $salesAmt   = safeFloat($salesRow[0]->Amount ?? 0);

        // ── 1e. Total available & closing stock ──────────────────────────────
        $totalAvailable = $openQty + $purchQty;
        $closingQty     = max(0, $totalAvailable - $soldQty);

        $allTimePurchaseCostFull = safeFloat($objQuery->index(
            "SELECT COALESCE(SUM(TotalAmount),0) AS v FROM trx_fuelpurchase
              WHERE FuelTypeID=? AND PurchaseDate<=? AND IsActive=1 AND IsDeleted=0",
            [$fid, $endDate]
        )[0]->v ?? 0);
        $allTimePurchaseQtyFull = safeFloat($objQuery->index(
            "SELECT COALESCE(SUM(Quantity),0) AS v FROM trx_fuelpurchase
              WHERE FuelTypeID=? AND PurchaseDate<=? AND IsActive=1 AND IsDeleted=0",
            [$fid, $endDate]
        )[0]->v ?? 0);

        $avgCostFull    = $allTimePurchaseQtyFull > 0
                            ? ($allTimePurchaseCostFull / $allTimePurchaseQtyFull)
                            : $avgCostPre;

        $closingStockVal = $closingQty * $avgCostFull;

        // ── 1f. COGS  (absorption costing via stock movement) ────────────────
        $cogs        = $openStockValue + $purchCost - $closingStockVal;
        $grossProfit = $salesAmt - $cogs;
        $margin      = $salesAmt > 0 ? round(($grossProfit / $salesAmt) * 100, 1) : 0;

        if ($soldQty > 0 || $purchQty > 0 || $openQty > 0) {
            $fuelRows[] = [
                'fuel'           => $fuel->FuelName,
                'openQty'        => $openQty,
                'openVal'        => $openStockValue,
                'purchQty'       => $purchQty,
                'purchCost'      => $purchCost,
                'totalAvailable' => $totalAvailable,
                'soldQty'        => $soldQty,
                'salesAmt'       => $salesAmt,
                'closingQty'     => $closingQty,
                'closingVal'     => $closingStockVal,
                'cogs'           => $cogs,
                'grossProfit'    => $grossProfit,
                'avgCost'        => $avgCostFull,
                'margin'         => $margin,
            ];
        }
    }

    // ── 2. OPERATING EXPENSES ─────────────────────────────────────────────────
    $expenseDetail = $objQuery->index(
        "SELECT COALESCE(ec.CategoryNameEN, ec_sal.CategoryNameEN, 'Salary') AS CategoryNameEN,
                COALESCE(ec.CategoryNameBN, ec_sal.CategoryNameBN, 'বেতন') AS CategoryNameBN,
                COALESCE(ep.ParticularNameEN, emp.NameEN, e.ParticularID) AS ParticularNameEN,
                COALESCE(ep.ParticularNameBN, emp.NameBN, emp.NameEN, e.ParticularID) AS ParticularNameBN,
                SUM(e.Amount) AS Total, COUNT(*) AS Cnt
           FROM trx_expense e
      LEFT JOIN mst_expenseparticular ep ON (e.ParticularID = ep.ParticularID OR e.ParticularID = CAST(ep.ExpenseParticularID AS CHAR)) AND ep.IsDeleted = 0
      LEFT JOIN mst_expensecategory   ec ON ep.ExpenseCategoryID = ec.ExpenseCategoryID AND ec.IsDeleted = 0
      LEFT JOIN mst_employee emp         ON (e.ParticularID = emp.EmployeeId OR e.ParticularID = CAST(emp.Id AS CHAR)) AND emp.IsDeleted = 0
      LEFT JOIN mst_expensecategory ec_sal ON (emp.Id IS NOT NULL OR emp.EmployeeId IS NOT NULL) AND (ec_sal.ExpenseCategoryID = 11 OR ec_sal.CategoryNameEN = 'Salary') AND ec_sal.IsDeleted = 0
          WHERE e.ExpenseDate BETWEEN ? AND ?
            AND e.IsActive=1 AND e.IsDeleted=0
          GROUP BY e.ParticularID, ep.ParticularID, emp.EmployeeId
          ORDER BY CategoryNameEN, ParticularNameEN",
        [$startDate, $endDate]
    );

    // ── 3. OTHER INCOME  (others collection) ─────────────────────────────────
    $otherIncomeRow = $objQuery->index(
        "SELECT COALESCE(SUM(Amount),0) AS v FROM trx_otherscollection
          WHERE CollectionDate BETWEEN ? AND ? AND IsActive=1 AND IsDeleted=0",
        [$startDate, $endDate]
    );
    $otherIncome = safeFloat($otherIncomeRow[0]->v ?? 0);

    // ── 4. CUSTOMER RECEIVABLE (outstanding dues) ─────────────────────────────
    $custRecRow = $objQuery->index(
        "SELECT COALESCE(SUM(DueAmount),0) AS v FROM trx_customerdue
          WHERE IsActive=1 AND IsDeleted=0"
    );
    $custReceivable = safeFloat($custRecRow[0]->v ?? 0);

    // ── 5. SUPPLIER PAYABLE (outstanding purchase payments) ───────────────────
    $supPurchRow = $objQuery->index(
        "SELECT COALESCE(SUM(TotalAmount),0) AS v FROM trx_fuelpurchase
          WHERE IsActive=1 AND IsDeleted=0"
    );
    $supPaidRow = $objQuery->index(
        "SELECT COALESCE(SUM(Amount),0) AS v FROM trx_supplierpayment
          WHERE IsActive=1 AND IsDeleted=0"
    );
    $supplierPayable = safeFloat($supPurchRow[0]->v ?? 0)
                     - safeFloat($supPaidRow[0]->v ?? 0);

    // ── 6. CASH vs CREDIT SALES ────────────────────────────────────────────────
    $creditRow = $objQuery->index(
        "SELECT COALESCE(SUM(TotalAmount),0) AS v FROM trx_customerdue
          WHERE TxnDate BETWEEN ? AND ? AND IsActive=1 AND IsDeleted=0",
        [$startDate, $endDate]
    );
    $creditSaleAmt = safeFloat($creditRow[0]->v ?? 0);

    // ── 7. GRAND TOTALS ────────────────────────────────────────────────────────
    $totals = [
        'openVal'      => array_sum(array_column($fuelRows, 'openVal')),
        'purchCost'    => array_sum(array_column($fuelRows, 'purchCost')),
        'purchQty'     => array_sum(array_column($fuelRows, 'purchQty')),
        'soldQty'      => array_sum(array_column($fuelRows, 'soldQty')),
        'salesAmt'     => array_sum(array_column($fuelRows, 'salesAmt')),
        'closingVal'   => array_sum(array_column($fuelRows, 'closingVal')),
        'cogs'         => array_sum(array_column($fuelRows, 'cogs')),
        'grossProfit'  => array_sum(array_column($fuelRows, 'grossProfit')),
    ];

    $totalExpenses = 0;
    foreach ($expenseDetail as $r) { $totalExpenses += safeFloat($r->Total); }

    $totals['expenses']   = $totalExpenses;
    $totals['otherIncome']= $otherIncome;
    $totals['netProfit']  = $totals['grossProfit'] - $totalExpenses + $otherIncome;
    $totals['margin']     = $totals['salesAmt'] > 0
                              ? round(($totals['grossProfit'] / $totals['salesAmt']) * 100, 1)
                              : 0;
    $totals['netMargin']  = $totals['salesAmt'] > 0
                              ? round(($totals['netProfit']   / $totals['salesAmt']) * 100, 1)
                              : 0;

    $cashSaleAmt = $totals['salesAmt'] - $creditSaleAmt;
}
?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     INLINE PRINT & REPORT STYLES
════════════════════════════════════════════════════════════════════════════ -->
<style>
/* ── Dashboard metric cards ── */
.pl-metric-card {
    border: none;
    border-radius: 14px;
    padding: 1.2rem 1.4rem;
    color: #fff;
    position: relative;
    overflow: hidden;
    transition: transform .2s, box-shadow .2s;
}
.pl-metric-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(0,0,0,.18); }
.pl-metric-card .metric-icon {
    position: absolute; right: 1rem; top: 50%;
    transform: translateY(-50%);
    font-size: 2.8rem; opacity: .18;
}
.pl-metric-card .metric-label { font-size: .75rem; font-weight: 600; letter-spacing: .6px; text-transform: uppercase; opacity: .85; }
.pl-metric-card .metric-value { font-size: 1.65rem; font-weight: 700; line-height: 1.2; margin-top: .15rem; }
.pl-metric-card .metric-sub   { font-size: .7rem; opacity: .75; margin-top: .1rem; }

.grad-sales   { background: linear-gradient(135deg,#1a73e8,#0d47a1); }
.grad-cost    { background: linear-gradient(135deg,#e53935,#b71c1c); }
.grad-gross   { background: linear-gradient(135deg,#00897b,#004d40); }
.grad-net     { background: linear-gradient(135deg,#7b1fa2,#4a148c); }
.grad-stock   { background: linear-gradient(135deg,#f57c00,#e65100); }
.grad-sold    { background: linear-gradient(135deg,#0288d1,#01579b); }
.grad-remain  { background: linear-gradient(135deg,#43a047,#1b5e20); }
.grad-percent { background: linear-gradient(135deg,#c0ca33,#827717); }

/* ── Section card ── */
.report-section { border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; overflow: hidden; }
.report-section .section-header {
    padding: .7rem 1.1rem;
    font-weight: 700; font-size: .9rem; letter-spacing: .3px;
    display: flex; align-items: center; gap: .5rem;
}
.report-section .section-header i { font-size: 1rem; }
.sh-blue   { background: linear-gradient(90deg,#1a73e8 0%,#4285f4 100%); color:#fff; }
.sh-green  { background: linear-gradient(90deg,#00897b 0%,#26a69a 100%); color:#fff; }
.sh-red    { background: linear-gradient(90deg,#e53935 0%,#ef5350 100%); color:#fff; }
.sh-purple { background: linear-gradient(90deg,#7b1fa2 0%,#ab47bc 100%); color:#fff; }
.sh-orange { background: linear-gradient(90deg,#f57c00 0%,#ffa726 100%); color:#fff; }
.sh-teal   { background: linear-gradient(90deg,#00838f 0%,#26c6da 100%); color:#fff; }

/* ── Table tweaks ── */
.pl-table th { background:#f8fafc; font-size: .78rem; font-weight: 700; letter-spacing: .3px; color:#475569; white-space: nowrap; }
.pl-table td { font-size: .82rem; vertical-align: middle; }
.pl-table tfoot td, .pl-table tfoot th { background:#f1f5f9; font-weight: 700; }
.profit-pos { color: #16a34a; font-weight: 700; }
.profit-neg { color: #dc2626; font-weight: 700; }
.badge-metric { font-size: .68rem; padding: .25em .6em; border-radius: 20px; }

/* ── Summary box ── */
.summary-line { display: flex; justify-content: space-between; align-items: center; padding: .45rem .8rem; border-bottom: 1px dashed #e2e8f0; }
.summary-line:last-child { border-bottom: none; }
.summary-line.total-line { background: #f0fdf4; font-weight: 700; border-radius: 6px; margin-top: .4rem; }
.summary-line.loss-line  { background: #fef2f2; font-weight: 700; border-radius: 6px; margin-top: .4rem; }

/* ── Print ── */
@media print {
    #sidebar, .top-navbar, .no-print, .btn, nav { display: none !important; }
    #content-wrapper { margin-left: 0 !important; }
    .pl-metric-card { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .report-section  { page-break-inside: avoid; }
    body { font-size: 11px; }
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════════════
     PAGE WRAPPER
════════════════════════════════════════════════════════════════════════════ -->
<div class="table-container">

    <!-- Page header -->
    <div class="table-header mb-3">
        <h5><i class="fas fa-chart-line text-primary me-2"></i><?php echo htmlspecialchars($lblReportTitle); ?></h5>
        <?php if ($generate): ?>
        <div class="d-flex gap-2 no-print">
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-print me-1"></i> <?php echo $lblPrint; ?>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Filter Form ──────────────────────────────────────────────────── -->
    <form method="GET" class="row g-3 mb-4 no-print">
        <div class="col-md-3">
            <label class="form-label fw-semibold"><i class="fas fa-calendar-alt me-1 text-primary"></i> <?php echo $lblFromDate; ?> <span class="text-danger">*</span></label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $startDate; ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold"><i class="fas fa-calendar-check me-1 text-primary"></i> <?php echo $lblToDate; ?> <span class="text-danger">*</span></label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $endDate; ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold"><i class="fas fa-language me-1 text-primary"></i> <?php echo $lblLanguage; ?></label>
            <select name="lang" id="lang" class="form-select">
                <option value="bn" <?php echo $lang === 'bn' ? 'selected' : ''; ?>>Bangla (বাংলা)</option>
                <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>English</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end gap-2">
            <button type="submit" name="generate" class="btn btn-primary flex-grow-1">
                <i class="fas fa-rocket me-1"></i> <?php echo $lblGenerate; ?>
            </button>
            <a href="profit_loss.php" class="btn btn-outline-secondary"><i class="fas fa-redo"></i></a>
        </div>
    </form>

    <?php if (!$generate): ?>
    <!-- ── Placeholder ──────────────────────────────────────────────── -->
    <div class="text-center py-5">
        <div style="font-size:4rem; color:#cbd5e1;"><i class="fas fa-chart-pie"></i></div>
        <h5 class="text-muted mt-3"><?php echo $lblSelectDateRange; ?></h5>
        <p class="text-muted small"><?php echo $lblAvcoNote; ?></p>
    </div>

    <?php else: /* ═══ REPORT OUTPUT ══════════════════════════════════════ */ ?>

    <!-- ══ Report Header (print) ════════════════════════════════════════ -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0 fw-bold"><?php echo htmlspecialchars($companyNameDisplay); ?></h4>
                    <small class="text-muted"><?php echo htmlspecialchars($companyAddressDisplay); ?></small>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5 class="mb-1 text-primary"><?php echo htmlspecialchars($lblReportTitle); ?></h5>
                    <small class="text-muted">
                        <strong><?php echo $lblPeriod; ?>:</strong> <?php echo formatDate($startDate); ?> &mdash; <?php echo formatDate($endDate); ?><br>
                        <strong><?php echo $lblMethod; ?>:</strong> <?php echo $lang === 'en' ? 'Average Cost (AVCO)' : 'এভারেজ কস্ট (AVCO)'; ?> &nbsp;|&nbsp;
                        <strong><?php echo $lblGenerated; ?>:</strong> <?php echo date('d-m-Y H:i'); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ DASHBOARD CARDS ═══════════════════════════════════════════════ -->
    <div class="row g-3 mb-4 no-print">
        <!-- Total Sales -->
        <div class="col-6 col-md-3">
            <div class="pl-metric-card grad-sales">
                <div class="metric-label"><?php echo $lblCardTotalSales; ?></div>
                <div class="metric-value"><?php echo $currencySymbol . ' ' . formatCurrency($totals['salesAmt']); ?></div>
                <div class="metric-sub"><?php echo formatCurrency($totals['soldQty'], 3); ?> <?php echo $lblCardSoldSub; ?></div>
                <div class="metric-icon"><i class="fas fa-dollar-sign"></i></div>
            </div>
        </div>
        <!-- Total COGS -->
        <div class="col-6 col-md-3">
            <div class="pl-metric-card grad-cost">
                <div class="metric-label"><?php echo $lblCardCOGS; ?></div>
                <div class="metric-value"><?php echo $currencySymbol . ' ' . formatCurrency($totals['cogs']); ?></div>
                <div class="metric-sub"><?php echo $lblCardPurchCostSub; ?> <?php echo $currencySymbol . ' ' . formatCurrency($totals['purchCost']); ?></div>
                <div class="metric-icon"><i class="fas fa-shopping-cart"></i></div>
            </div>
        </div>
        <!-- Gross Profit -->
        <div class="col-6 col-md-3">
            <div class="pl-metric-card grad-gross">
                <div class="metric-label"><?php echo $lblCardGrossProfit; ?></div>
                <div class="metric-value"><?php echo $currencySymbol . ' ' . formatCurrency($totals['grossProfit']); ?></div>
                <div class="metric-sub"><?php echo $lblCardMarginSub; ?> <?php echo $totals['margin']; ?>%</div>
                <div class="metric-icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
        <!-- Net Profit -->
        <div class="col-6 col-md-3">
            <div class="pl-metric-card <?php echo $totals['netProfit'] >= 0 ? 'grad-net' : 'grad-cost'; ?>">
                <div class="metric-label"><?php echo $totals['netProfit'] >= 0 ? $lblCardNetProfit : $lblCardNetLoss; ?></div>
                <div class="metric-value"><?php echo $currencySymbol . ' ' . formatCurrency($totals['netProfit']); ?></div>
                <div class="metric-sub"><?php echo $lblCardNetMarginSub; ?> <?php echo $totals['netMargin']; ?>%</div>
                <div class="metric-icon"><i class="fas fa-coins"></i></div>
            </div>
        </div>
        <!-- Closing Stock Value -->
        <div class="col-6 col-md-3">
            <div class="pl-metric-card grad-stock">
                <div class="metric-label"><?php echo $lblCardClosingVal; ?></div>
                <div class="metric-value"><?php echo $currencySymbol . ' ' . formatCurrency($totals['closingVal']); ?></div>
                <div class="metric-sub"><?php echo $lblCardAvcoSub; ?></div>
                <div class="metric-icon"><i class="fas fa-boxes"></i></div>
            </div>
        </div>
        <!-- Fuel Sold -->
        <div class="col-6 col-md-3">
            <div class="pl-metric-card grad-sold">
                <div class="metric-label"><?php echo $lblCardFuelSold; ?></div>
                <div class="metric-value"><?php echo formatCurrency($totals['soldQty'], 3); ?> L</div>
                <div class="metric-sub"><?php echo $lblCardPeriodSub; ?></div>
                <div class="metric-icon"><i class="fas fa-gas-pump"></i></div>
            </div>
        </div>
        <!-- Operating Expenses -->
        <div class="col-6 col-md-3">
            <div class="pl-metric-card grad-percent">
                <div class="metric-label"><?php echo $lblCardOperatingExp; ?></div>
                <div class="metric-value"><?php echo $currencySymbol . ' ' . formatCurrency($totals['expenses']); ?></div>
                <div class="metric-sub"><?php echo $lblCardOtherIncSub; ?> <?php echo $currencySymbol . ' ' . formatCurrency($otherIncome); ?></div>
                <div class="metric-icon"><i class="fas fa-receipt"></i></div>
            </div>
        </div>
        <!-- Profit % -->
        <div class="col-6 col-md-3">
            <div class="pl-metric-card grad-remain">
                <div class="metric-label"><?php echo $lblCardProfitPct; ?></div>
                <div class="metric-value"><?php echo $totals['netMargin']; ?>%</div>
                <div class="metric-sub"><?php echo $lblCardGrossSub; ?> <?php echo $totals['margin']; ?>%</div>
                <div class="metric-icon"><i class="fas fa-percentage"></i></div>
            </div>
        </div>
    </div>

    <!-- ══ 1. PER-FUEL INVENTORY & PROFIT TABLE ══════════════════════════ -->
    <div class="report-section">
        <div class="section-header sh-blue">
            <i class="fas fa-gas-pump"></i> <?php echo $lblFuelSecTitle; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm pl-table mb-0">
                <thead>
                    <tr>
                        <th rowspan="2" class="align-middle">#</th>
                        <th rowspan="2" class="align-middle"><?php echo $lblThItem; ?></th>
                        <th colspan="2" class="text-center"><?php echo $lblThOpenStock; ?></th>
                        <th colspan="2" class="text-center"><?php echo $lblThPurchase; ?></th>
                        <th class="text-center"><?php echo $lblThTotAvailable; ?></th>
                        <th class="text-center"><?php echo $lblThSoldQty; ?></th>
                        <th colspan="2" class="text-center"><?php echo $lblThClosingStock; ?></th>
                        <th class="text-center"><?php echo $lblThCOGS; ?></th>
                        <th class="text-center"><?php echo $lblThSalesAmt; ?></th>
                        <th class="text-center"><?php echo $lblThGrossProfit; ?></th>
                        <th class="text-center"><?php echo $lblThMargin; ?></th>
                    </tr>
                    <tr>
                        <th class="text-end"><?php echo $lblThQtyL; ?></th>
                        <th class="text-end"><?php echo $lblThValue; ?></th>
                        <th class="text-end"><?php echo $lblThQtyL; ?></th>
                        <th class="text-end"><?php echo $lblThCost; ?></th>
                        <th class="text-end"><?php echo $lblThQtyL; ?></th>
                        <th class="text-end"><?php echo $lblThQtyL; ?></th>
                        <th class="text-end"><?php echo $lblThQtyL; ?></th>
                        <th class="text-end"><?php echo $lblThValue; ?></th>
                        <th class="text-end"><?php echo $currencySymbol; ?></th>
                        <th class="text-end"><?php echo $currencySymbol; ?></th>
                        <th class="text-end"><?php echo $currencySymbol; ?></th>
                        <th class="text-center">%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fuelRows)): ?>
                    <tr><td colspan="14" class="text-center text-muted py-3"><?php echo $lblNoData; ?></td></tr>
                    <?php else: $sl = 1; foreach ($fuelRows as $r): ?>
                    <tr>
                        <td><?php echo $sl++; ?></td>
                        <td class="fw-semibold"><?php echo htmlspecialchars($r['fuel']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($r['openQty'], 3); ?></td>
                        <td class="text-end"><?php echo formatCurrency($r['openVal']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($r['purchQty'], 3); ?></td>
                        <td class="text-end"><?php echo formatCurrency($r['purchCost']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($r['totalAvailable'], 3); ?></td>
                        <td class="text-end"><?php echo formatCurrency($r['soldQty'], 3); ?></td>
                        <td class="text-end"><?php echo formatCurrency($r['closingQty'], 3); ?></td>
                        <td class="text-end"><?php echo formatCurrency($r['closingVal']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($r['cogs']); ?></td>
                        <td class="text-end fw-semibold text-primary"><?php echo formatCurrency($r['salesAmt']); ?></td>
                        <td class="text-end <?php echo $r['grossProfit'] >= 0 ? 'profit-pos' : 'profit-neg'; ?>">
                            <?php echo formatCurrency($r['grossProfit']); ?>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-metric <?php echo $r['margin'] >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $r['margin']; ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <?php if (!empty($fuelRows)): ?>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end fw-bold"><?php echo $lblTotal; ?></td>
                        <td class="text-end fw-bold">&mdash;</td>
                        <td class="text-end fw-bold"><?php echo formatCurrency($totals['openVal']); ?></td>
                        <td class="text-end fw-bold"><?php echo formatCurrency($totals['purchQty'], 3); ?></td>
                        <td class="text-end fw-bold"><?php echo formatCurrency($totals['purchCost']); ?></td>
                        <td class="text-end fw-bold">&mdash;</td>
                        <td class="text-end fw-bold"><?php echo formatCurrency($totals['soldQty'], 3); ?></td>
                        <td class="text-end fw-bold">&mdash;</td>
                        <td class="text-end fw-bold"><?php echo formatCurrency($totals['closingVal']); ?></td>
                        <td class="text-end fw-bold"><?php echo formatCurrency($totals['cogs']); ?></td>
                        <td class="text-end fw-bold text-primary"><?php echo formatCurrency($totals['salesAmt']); ?></td>
                        <td class="text-end fw-bold <?php echo $totals['grossProfit'] >= 0 ? 'profit-pos' : 'profit-neg'; ?>">
                            <?php echo formatCurrency($totals['grossProfit']); ?>
                        </td>
                        <td class="text-center fw-bold">
                            <span class="badge <?php echo $totals['margin'] >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $totals['margin']; ?>%
                            </span>
                        </td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- ══ 2. EXPENSES BREAKDOWN ══════════════════════════════════════════ -->
    <?php if (!empty($expenseDetail)): ?>
    <div class="report-section">
        <div class="section-header sh-red">
            <i class="fas fa-receipt"></i> <?php echo $lblExpSecTitle; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm pl-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php echo $lblThCategory; ?></th>
                        <th><?php echo $lblThParticular; ?></th>
                        <th class="text-center"><?php echo $lblThEntries; ?></th>
                        <th class="text-end"><?php echo $lblThAmount; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sl = 1; $prevCat = ''; foreach ($expenseDetail as $r): ?>
                    <?php 
                        $catName = $lang === 'bn' ? (!empty($r->CategoryNameBN) ? $r->CategoryNameBN : ($r->CategoryNameEN ?? 'অন্যান্য')) : (!empty($r->CategoryNameEN) ? $r->CategoryNameEN : ($r->CategoryNameBN ?? 'Uncategorized'));
                        $partName = $lang === 'bn' ? (!empty($r->ParticularNameBN) ? $r->ParticularNameBN : ($r->ParticularNameEN ?? '—')) : (!empty($r->ParticularNameEN) ? $r->ParticularNameEN : ($r->ParticularNameBN ?? '—'));
                        $cat = htmlspecialchars($catName); 
                    ?>
                    <tr <?php if ($cat !== $prevCat): ?>class="table-active"<?php endif; ?>>
                        <td><?php echo $sl++; ?></td>
                        <td>
                            <?php if ($cat !== $prevCat):
                                $prevCat = $cat;
                                echo '<span class="badge bg-secondary">' . $cat . '</span>';
                            endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($partName); ?></td>
                        <td class="text-center"><?php echo $r->Cnt; ?></td>
                        <td class="text-end text-danger fw-semibold"><?php echo formatCurrency($r->Total); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-bold"><?php echo $lblTotalExpenses; ?></td>
                        <td class="text-end fw-bold text-danger"><?php echo formatCurrency($totals['expenses']); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ 3. CASH vs CREDIT SALES ════════════════════════════════════════ -->
    <div class="report-section">
        <div class="section-header sh-teal">
            <i class="fas fa-exchange-alt"></i> <?php echo $lblSalesAnalysisTitle; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-sm pl-table mb-0">
                <thead>
                    <tr>
                        <th><?php echo $lblThDescription; ?></th>
                        <th class="text-end"><?php echo $lblThAmount; ?></th>
                        <th class="text-end"><?php echo $lblThPctTotal; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="fas fa-money-bill-wave text-success me-1"></i> <?php echo $lblCashSales; ?></td>
                        <td class="text-end"><?php echo formatCurrency($cashSaleAmt); ?></td>
                        <td class="text-end">
                            <?php $pct = $totals['salesAmt'] > 0 ? round(($cashSaleAmt/$totals['salesAmt'])*100,1) : 0; ?>
                            <span class="badge bg-success"><?php echo $pct; ?>%</span>
                        </td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-file-invoice text-warning me-1"></i> <?php echo $lblCreditSales; ?></td>
                        <td class="text-end"><?php echo formatCurrency($creditSaleAmt); ?></td>
                        <td class="text-end">
                            <?php $pct2 = $totals['salesAmt'] > 0 ? round(($creditSaleAmt/$totals['salesAmt'])*100,1) : 0; ?>
                            <span class="badge bg-warning text-dark"><?php echo $pct2; ?>%</span>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="fw-bold"><?php echo $lblCardTotalSales; ?></td>
                        <td class="text-end fw-bold text-primary"><?php echo formatCurrency($totals['salesAmt']); ?></td>
                        <td class="text-end fw-bold">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- ══ 4. SUMMARY P&L STATEMENT ══════════════════════════════════════ -->
    <div class="row g-4 mb-4">
        <!-- Left: P&L Summary -->
        <div class="col-md-6">
            <div class="report-section h-100">
                <div class="section-header sh-purple">
                    <i class="fas fa-balance-scale"></i> <?php echo $lblPLSummaryTitle; ?>
                </div>
                <div class="p-3">
                    <div class="summary-line">
                        <span><i class="fas fa-arrow-up text-success me-2"></i><?php echo $lblCardTotalSales; ?></span>
                        <strong class="text-primary"><?php echo $currencySymbol . ' ' . formatCurrency($totals['salesAmt']); ?></strong>
                    </div>
                    <div class="summary-line">
                        <span><i class="fas fa-minus-circle text-danger me-2"></i><?php echo $lblCardCOGS; ?></span>
                        <strong class="text-danger">(-) <?php echo $currencySymbol . ' ' . formatCurrency($totals['cogs']); ?></strong>
                    </div>
                    <div class="summary-line <?php echo $totals['grossProfit'] >= 0 ? 'total-line' : 'loss-line'; ?>">
                        <span><i class="fas fa-chart-bar me-2"></i><?php echo $lblCardGrossProfit; ?></span>
                        <strong class="<?php echo $totals['grossProfit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $currencySymbol . ' ' . formatCurrency($totals['grossProfit']); ?>
                            <small>(<?php echo $totals['margin']; ?>%)</small>
                        </strong>
                    </div>

                    <div class="mt-3">
                        <div class="summary-line">
                            <span><i class="fas fa-minus-circle text-danger me-2"></i><?php echo $lblCardOperatingExp; ?></span>
                            <strong class="text-danger">(-) <?php echo $currencySymbol . ' ' . formatCurrency($totals['expenses']); ?></strong>
                        </div>
                        <div class="summary-line">
                            <span><i class="fas fa-plus-circle text-success me-2"></i><?php echo $lblOtherIncome; ?></span>
                            <strong class="text-success">(+) <?php echo $currencySymbol . ' ' . formatCurrency($otherIncome); ?></strong>
                        </div>
                    </div>

                    <div class="summary-line <?php echo $totals['netProfit'] >= 0 ? 'total-line' : 'loss-line'; ?> mt-2" style="font-size:1.05rem;">
                        <span><i class="fas fa-coins me-2"></i><?php echo $totals['netProfit'] >= 0 ? $lblCardNetProfit : $lblCardNetLoss; ?></span>
                        <strong class="<?php echo $totals['netProfit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $currencySymbol . ' ' . formatCurrency(abs($totals['netProfit'])); ?>
                            <small>(<?php echo $totals['netMargin']; ?>%)</small>
                        </strong>
                    </div>

                    <div class="summary-line mt-2">
                        <span><i class="fas fa-boxes text-warning me-2"></i><?php echo $lblCardClosingVal; ?></span>
                        <strong class="text-warning"><?php echo $currencySymbol . ' ' . formatCurrency($totals['closingVal']); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Balance Sheet Highlights -->
        <div class="col-md-6">
            <div class="report-section h-100">
                <div class="section-header sh-orange">
                    <i class="fas fa-university"></i> <?php echo $lblBSHighlightsTitle; ?>
                </div>
                <div class="p-3">
                    <div class="summary-line">
                        <span><i class="fas fa-user-friends text-primary me-2"></i><?php echo $lblCustReceivable; ?></span>
                        <strong class="text-primary"><?php echo $currencySymbol . ' ' . formatCurrency(max(0, $custReceivable)); ?></strong>
                    </div>
                    <div class="summary-line">
                        <span><i class="fas fa-truck text-danger me-2"></i><?php echo $lblSupplierPayable; ?></span>
                        <strong class="text-danger"><?php echo $currencySymbol . ' ' . formatCurrency(max(0, $supplierPayable)); ?></strong>
                    </div>
                    <div class="summary-line">
                        <span><i class="fas fa-money-bill-wave text-success me-2"></i><?php echo $lblCashSalesAmt; ?></span>
                        <strong class="text-success"><?php echo $currencySymbol . ' ' . formatCurrency($cashSaleAmt); ?></strong>
                    </div>
                    <div class="summary-line">
                        <span><i class="fas fa-file-invoice text-warning me-1"></i><?php echo $lblCreditSalesAmt; ?></span>
                        <strong class="text-warning"><?php echo $currencySymbol . ' ' . formatCurrency($creditSaleAmt); ?></strong>
                    </div>
                    <div class="summary-line">
                        <span><i class="fas fa-shopping-cart text-secondary me-2"></i><?php echo $lblTotalPurchPeriod; ?></span>
                        <strong><?php echo $currencySymbol . ' ' . formatCurrency($totals['purchCost']); ?></strong>
                    </div>
                    <div class="summary-line">
                        <span><i class="fas fa-tachometer-alt text-info me-2"></i><?php echo $lblFuelSoldPeriod; ?></span>
                        <strong><?php echo formatCurrency($totals['soldQty'], 3); ?> L</strong>
                    </div>
                    <div class="summary-line">
                        <span><i class="fas fa-oil-can text-warning me-2"></i><?php echo $lblOpenStockValue; ?></span>
                        <strong><?php echo $currencySymbol . ' ' . formatCurrency($totals['openVal']); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ 5. CALCULATION METHOD NOTE ══════════════════════════════════════ -->
    <div class="alert alert-info d-flex gap-3 align-items-start no-print">
        <i class="fas fa-info-circle mt-1" style="font-size:1.2rem;"></i>
        <div>
            <strong><?php echo $lblCostingMethod; ?></strong><br>
            <small>
                <strong><?php echo $lang === 'en' ? 'Opening Stock Value' : 'প্রারম্ভিক মজুদের মূল্য'; ?></strong> = <?php echo $lblNoteOpenStock; ?><br>
                <strong><?php echo $lblThCOGS; ?></strong> = <?php echo $lblNoteCOGS; ?><br>
                <strong><?php echo $lblCardGrossProfit; ?></strong> = <?php echo $lblNoteGrossProfit; ?><br>
                <strong><?php echo $lblCardNetProfit; ?></strong> = <?php echo $lblNoteNetProfit; ?>
            </small>
        </div>
    </div>

    <?php endif; /* end generate */ ?>

</div><!-- /table-container -->

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
