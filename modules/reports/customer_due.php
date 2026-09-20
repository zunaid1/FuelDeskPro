<?php
/**
 * FuelDeskPro - Customer Due Report (কাস্টমার বকেয়া রিপোর্ট)
 *
 * Comprehensive report showing customer credit sales, due collections, 
 * previous dues, opening dues, and net outstanding balance statement.
 * Merges data from trx_customerdue, trx_customerdue_Previous, trx_Customer_Due_Opening, and trx_customercollection via UNION.
 * Supports dynamic bilingual rendering (en / bn) and title prefixes (TitleEN, TitleBN).
 *
 * @package FuelDeskPro
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

global $objQuery;
if (!isset($objQuery) || $objQuery === null) {
    $objQuery = db();
}

// Auto-ensure required database schemas exist
ensureCustomerDueOpeningTableExists();
ensureCustomerDuePreviousTableExists();
ensureCustomerCollectionTableSchema();
ensureCustomerDueTableSchema();
ensurePersonTitlesTableSchema();

// Language Detection (en / bn) from top header / session / query parameter
$lang = $_GET['lang'] ?? currentLang();
if (!in_array($lang, ['en', 'bn'], true)) {
    $lang = 'bn';
}

$pageTitle = ($lang === 'en') ? 'Customer Due Report' : 'কাস্টমার বকেয়া রিপোর্ট';

// Dynamic Company Information
$company = getCompanyInfo();
if ($lang === 'en') {
    $compName = $company ? (!empty($company->CompanyName) ? $company->CompanyName : 'Sanghu LPG Filling Station') : 'Sanghu LPG Filling Station';
    $compAddress = $company ? (!empty($company->Address) ? $company->Address : 'Kaliaish, Satkania, Chattogram.') : 'Kaliaish, Satkania, Chattogram.';
} else {
    $compName = $company ? (!empty($company->CompanyNameBN) ? $company->CompanyNameBN : (!empty($company->CompanyName) ? $company->CompanyName : 'সাংগু এলপিজি ফিলিং স্টেশন')) : 'সাংগু এলপিজি ফিলিং স্টেশন';
    $compAddress = $company ? (!empty($company->AddressBN) ? $company->AddressBN : (!empty($company->Address) ? $company->Address : 'কানিয়াছ, সাতকানিয়া, চট্টগ্রাম।')) : 'কানিয়াছ, সাতকানিয়া, চট্টগ্রাম।';
}
$compMobile = $company ? (!empty($company->MobileNo) ? $company->MobileNo : ($company->PhoneNo ?? '')) : '';

// --- Filter Handling ---
$startDate  = $_GET['start_date']  ?? date('Y-m-01');
$endDate    = $_GET['end_date']    ?? date('Y-m-d');
$rawPerson  = $_GET['customer_id'] ?? '';
$entityType = $_GET['entity_type'] ?? 'all';

// Helper to parse composite person selection (e.g. C_101, E_5, S_2)
if (!function_exists('parseReportEntity')) {
    function parseReportEntity($val) {
        $val = trim((string)$val);
        $parts = explode('_', $val);
        if (count($parts) === 2) {
            $code = strtoupper($parts[0]);
            $id = intval($parts[1]);
            $type = 'Customer';
            if ($code === 'E') $type = 'Employee';
            if ($code === 'S') $type = 'Shareholder';
            return ['type' => $type, 'id' => $id, 'val' => $code . '_' . $id];
        }
        $id = intval($val);
        return ['type' => 'Customer', 'id' => $id, 'val' => ($id > 0 ? 'C_' . $id : '')];
    }
}

$selectedEntity = parseReportEntity($rawPerson);

/**
 * Helper to format Person Name with TitleEN / TitleBN
 */
function formatPersonTitleName($titleEN, $titleBN, $nameEN, $nameBN, $lang = 'bn') {
    $tEN = trim((string)$titleEN);
    $tBN = trim((string)$titleBN);
    $nEN = trim((string)$nameEN);
    $nBN = trim((string)$nameBN);

    if ($lang === 'en') {
        $baseName = !empty($nEN) ? $nEN : $nBN;
        if (!empty($tEN)) {
            $title = $tEN;
        } elseif (!empty($tBN)) {
            $map = ['জনাব' => 'Mr.', 'বেগম' => 'Mrs.', 'মিস' => 'Ms.', 'ডক্টর' => 'Dr.'];
            $title = $map[$tBN] ?? $tBN;
        } else {
            $title = 'Mr.';
        }
    } else {
        // Bangla
        $baseName = !empty($nBN) ? $nBN : $nEN;
        if (!empty($tBN)) {
            $title = $tBN;
        } elseif (!empty($tEN)) {
            $map = ['Mr.' => 'জনাব', 'Mr' => 'জনাব', 'Mrs.' => 'বেগম', 'Mrs' => 'বেগম', 'Ms.' => 'মিস', 'Ms' => 'মিস', 'Dr.' => 'ডক্টর'];
            $title = $map[$tEN] ?? $tEN;
        } else {
            $title = 'জনাব';
        }
    }

    if (empty($baseName)) {
        return '';
    }

    return trim($title . ' ' . $baseName);
}

// Fetch All Persons (Customers, Employees, Shareholders) for Filter Dropdown
$allPersons = $objQuery->index("
    SELECT 
        CONCAT('C_', CustomerID) AS ValueID,
        CustomerID AS RealID,
        'Customer' AS EntityType,
        TitleEN,
        TitleBN,
        CustomerName AS NameEN,
        CustomerName AS NameBN,
        Mobile,
        Address
    FROM mst_customer 
    WHERE IsDeleted = 0

    UNION ALL

    SELECT 
        CONCAT('E_', Id) AS ValueID,
        Id AS RealID,
        'Employee' AS EntityType,
        TitleEN,
        TitleBN,
        NameEN,
        NameBN,
        Mobile,
        Address
    FROM mst_employee 
    WHERE IsDeleted = 0

    UNION ALL

    SELECT 
        CONCAT('S_', Id) AS ValueID,
        Id AS RealID,
        'Shareholder' AS EntityType,
        TitleEN,
        TitleBN,
        NameEN,
        NameBN,
        Mobile,
        Address
    FROM mst_shareholder 
    WHERE IsDeleted = 0

    ORDER BY ValueID ASC
");

// Pre-format names for dropdown & print view header
foreach ($allPersons as $p) {
    $p->FormattedName = formatPersonTitleName($p->TitleEN, $p->TitleBN, $p->NameEN, $p->NameBN, $lang);
}

// Selected Person Details for Print View Header
if ($lang === 'en') {
    $selectedPersonName = 'All Customers / Persons';
} else {
    $selectedPersonName = 'সকল গ্রাহক / ব্যক্তি';
}
$selectedPersonAddress = '-';

if ($selectedEntity['id'] > 0) {
    foreach ($allPersons as $p) {
        if ($p->ValueID === $selectedEntity['val']) {
            $selectedPersonName = $p->FormattedName;
            $selectedPersonAddress = !empty($p->Address) ? $p->Address : '-';
            break;
        }
    }
}

// Stream 1: Operational Customer Dues (trx_customerdue)
$opDueSql = "
    SELECT 
        cd.TxnDate,
        CASE 
            WHEN cd.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED)
            WHEN cd.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED)
            WHEN cd.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED)
            ELSE CAST(cd.CustomerID AS UNSIGNED)
        END AS RealID,
        CASE 
            WHEN cd.CustomerID LIKE 'S_%' THEN 'Shareholder'
            WHEN cd.CustomerID LIKE 'E_%' THEN 'Employee'
            WHEN cd.CustomerID LIKE 'C_%' THEN 'Customer'
            WHEN cd.CustomerType IN ('Shareholder', 'Employee') THEN cd.CustomerType
            ELSE 'Customer'
        END AS CustomerType,
        CASE 
            WHEN cd.CustomerID LIKE 'E_%' OR cd.CustomerType = 'Employee' THEN (SELECT TitleEN FROM mst_employee WHERE Id = (CASE WHEN cd.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
            WHEN cd.CustomerID LIKE 'S_%' OR cd.CustomerType = 'Shareholder' THEN (SELECT TitleEN FROM mst_shareholder WHERE Id = (CASE WHEN cd.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
            ELSE (SELECT TitleEN FROM mst_customer WHERE CustomerID = (CASE WHEN cd.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
        END AS TitleEN,
        CASE 
            WHEN cd.CustomerID LIKE 'E_%' OR cd.CustomerType = 'Employee' THEN (SELECT TitleBN FROM mst_employee WHERE Id = (CASE WHEN cd.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
            WHEN cd.CustomerID LIKE 'S_%' OR cd.CustomerType = 'Shareholder' THEN (SELECT TitleBN FROM mst_shareholder WHERE Id = (CASE WHEN cd.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
            ELSE (SELECT TitleBN FROM mst_customer WHERE CustomerID = (CASE WHEN cd.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
        END AS TitleBN,
        CASE 
            WHEN cd.CustomerID LIKE 'E_%' OR cd.CustomerType = 'Employee' THEN (SELECT NameEN FROM mst_employee WHERE Id = (CASE WHEN cd.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
            WHEN cd.CustomerID LIKE 'S_%' OR cd.CustomerType = 'Shareholder' THEN (SELECT NameEN FROM mst_shareholder WHERE Id = (CASE WHEN cd.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
            ELSE (SELECT CustomerName FROM mst_customer WHERE CustomerID = (CASE WHEN cd.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
        END AS NameEN,
        CASE 
            WHEN cd.CustomerID LIKE 'E_%' OR cd.CustomerType = 'Employee' THEN (SELECT NameBN FROM mst_employee WHERE Id = (CASE WHEN cd.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
            WHEN cd.CustomerID LIKE 'S_%' OR cd.CustomerType = 'Shareholder' THEN (SELECT NameBN FROM mst_shareholder WHERE Id = (CASE WHEN cd.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
            ELSE (SELECT CustomerName FROM mst_customer WHERE CustomerID = (CASE WHEN cd.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
        END AS NameBN,
        CASE 
            WHEN cd.CustomerID LIKE 'E_%' OR cd.CustomerType = 'Employee' THEN (SELECT Mobile FROM mst_employee WHERE Id = (CASE WHEN cd.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
            WHEN cd.CustomerID LIKE 'S_%' OR cd.CustomerType = 'Shareholder' THEN (SELECT Mobile FROM mst_shareholder WHERE Id = (CASE WHEN cd.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
            ELSE (SELECT Mobile FROM mst_customer WHERE CustomerID = (CASE WHEN cd.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) ELSE CAST(cd.CustomerID AS UNSIGNED) END))
        END AS Mobile,
        'Credit Sale (LPG/Fuel)' AS TxnSourceEN,
        'বাকিতে বিক্রয় (LPG/ফুয়েল)' AS TxnSourceBN,
        cd.TotalAmount AS CreditSale,
        cd.PaidAmount AS Payment,
        cd.DueAmount AS NetDue,
        cd.Remarks
    FROM trx_customerdue cd
    WHERE cd.IsDeleted = 0 AND cd.TxnDate BETWEEN ? AND ?
";
$opParams = [$startDate, $endDate];

if ($selectedEntity['id'] > 0) {
    $opDuePrefix = ($selectedEntity['type'] === 'Shareholder') ? 'S_%' : (($selectedEntity['type'] === 'Employee') ? 'E_%' : 'C_%');
    $opDueSql .= " AND (
        cd.CustomerID = ?
        OR (cd.CustomerID = ? AND (cd.CustomerType = ? OR ((cd.CustomerType IS NULL OR cd.CustomerType = '') AND ? = 'Customer')))
        OR (cd.CustomerID LIKE ? AND CAST(SUBSTRING(cd.CustomerID, 3) AS UNSIGNED) = ?)
    )";
    $opParams[] = $selectedEntity['val'];
    $opParams[] = $selectedEntity['id'];
    $opParams[] = $selectedEntity['type'];
    $opParams[] = $selectedEntity['type'];
    $opParams[] = $opDuePrefix;
    $opParams[] = $selectedEntity['id'];
} elseif ($entityType !== 'all') {
    $opDuePrefix = ($entityType === 'Shareholder') ? 'S_%' : (($entityType === 'Employee') ? 'E_%' : 'C_%');
    $opDueSql .= " AND (
        cd.CustomerType = ? 
        OR ((cd.CustomerType IS NULL OR cd.CustomerType = '') AND ? = 'Customer')
        OR cd.CustomerID LIKE ?
    )";
    $opParams[] = $entityType;
    $opParams[] = $entityType;
    $opParams[] = $opDuePrefix;
}

// Stream 2: Previous Customer Dues (trx_customerdue_Previous)
$prevSql = "
    SELECT 
        p.TxnDate,
        CASE 
            WHEN p.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED)
            WHEN p.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED)
            WHEN p.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED)
            ELSE CAST(p.CustomerID AS UNSIGNED)
        END AS RealID,
        CASE 
            WHEN p.CustomerID LIKE 'S_%' THEN 'Shareholder'
            WHEN p.CustomerID LIKE 'E_%' THEN 'Employee'
            WHEN p.CustomerID LIKE 'C_%' THEN 'Customer'
            WHEN p.CustomerType IN ('Shareholder', 'Employee') THEN p.CustomerType
            ELSE 'Customer'
        END AS CustomerType,
        CASE 
            WHEN p.CustomerID LIKE 'E_%' OR p.CustomerType = 'Employee' THEN (SELECT TitleEN FROM mst_employee WHERE Id = (CASE WHEN p.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
            WHEN p.CustomerID LIKE 'S_%' OR p.CustomerType = 'Shareholder' THEN (SELECT TitleEN FROM mst_shareholder WHERE Id = (CASE WHEN p.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
            ELSE (SELECT TitleEN FROM mst_customer WHERE CustomerID = (CASE WHEN p.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
        END AS TitleEN,
        CASE 
            WHEN p.CustomerID LIKE 'E_%' OR p.CustomerType = 'Employee' THEN (SELECT TitleBN FROM mst_employee WHERE Id = (CASE WHEN p.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
            WHEN p.CustomerID LIKE 'S_%' OR p.CustomerType = 'Shareholder' THEN (SELECT TitleBN FROM mst_shareholder WHERE Id = (CASE WHEN p.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
            ELSE (SELECT TitleBN FROM mst_customer WHERE CustomerID = (CASE WHEN p.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
        END AS TitleBN,
        CASE 
            WHEN p.CustomerID LIKE 'E_%' OR p.CustomerType = 'Employee' THEN (SELECT NameEN FROM mst_employee WHERE Id = (CASE WHEN p.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
            WHEN p.CustomerID LIKE 'S_%' OR p.CustomerType = 'Shareholder' THEN (SELECT NameEN FROM mst_shareholder WHERE Id = (CASE WHEN p.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
            ELSE (SELECT CustomerName FROM mst_customer WHERE CustomerID = (CASE WHEN p.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
        END AS NameEN,
        CASE 
            WHEN p.CustomerID LIKE 'E_%' OR p.CustomerType = 'Employee' THEN (SELECT NameBN FROM mst_employee WHERE Id = (CASE WHEN p.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
            WHEN p.CustomerID LIKE 'S_%' OR p.CustomerType = 'Shareholder' THEN (SELECT NameBN FROM mst_shareholder WHERE Id = (CASE WHEN p.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
            ELSE (SELECT CustomerName FROM mst_customer WHERE CustomerID = (CASE WHEN p.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
        END AS NameBN,
        CASE 
            WHEN p.CustomerID LIKE 'E_%' OR p.CustomerType = 'Employee' THEN (SELECT Mobile FROM mst_employee WHERE Id = (CASE WHEN p.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
            WHEN p.CustomerID LIKE 'S_%' OR p.CustomerType = 'Shareholder' THEN (SELECT Mobile FROM mst_shareholder WHERE Id = (CASE WHEN p.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
            ELSE (SELECT Mobile FROM mst_customer WHERE CustomerID = (CASE WHEN p.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) ELSE CAST(p.CustomerID AS UNSIGNED) END))
        END AS Mobile,
        'Previous Due Record' AS TxnSourceEN,
        'পূর্বের বকেয়া রেকর্ড' AS TxnSourceBN,
        p.TotalAmount AS CreditSale,
        p.PaidAmount AS Payment,
        p.DueAmount AS NetDue,
        p.Remarks
    FROM trx_customerdue_Previous p
    WHERE p.IsDeleted = 0 AND p.TxnDate BETWEEN ? AND ?
";
$prevParams = [$startDate, $endDate];

if ($selectedEntity['id'] > 0) {
    $prevPrefix = ($selectedEntity['type'] === 'Shareholder') ? 'S_%' : (($selectedEntity['type'] === 'Employee') ? 'E_%' : 'C_%');
    $prevSql .= " AND (
        p.CustomerID = ?
        OR (p.CustomerID = ? AND (p.CustomerType = ? OR ((p.CustomerType IS NULL OR p.CustomerType = '') AND ? = 'Customer')))
        OR (p.CustomerID LIKE ? AND CAST(SUBSTRING(p.CustomerID, 3) AS UNSIGNED) = ?)
    )";
    $prevParams[] = $selectedEntity['val'];
    $prevParams[] = $selectedEntity['id'];
    $prevParams[] = $selectedEntity['type'];
    $prevParams[] = $selectedEntity['type'];
    $prevParams[] = $prevPrefix;
    $prevParams[] = $selectedEntity['id'];
} elseif ($entityType !== 'all') {
    $prevPrefix = ($entityType === 'Shareholder') ? 'S_%' : (($entityType === 'Employee') ? 'E_%' : 'C_%');
    $prevSql .= " AND (
        p.CustomerType = ? 
        OR ((p.CustomerType IS NULL OR p.CustomerType = '') AND ? = 'Customer')
        OR p.CustomerID LIKE ?
    )";
    $prevParams[] = $entityType;
    $prevParams[] = $entityType;
    $prevParams[] = $prevPrefix;
}

// Stream 3: Opening Historical Dues (trx_Customer_Due_Opening)
$histSql = "
    SELECT 
        o.TxnDate,
        CASE 
            WHEN o.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED)
            WHEN o.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED)
            WHEN o.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED)
            ELSE CAST(o.CustomerID AS UNSIGNED)
        END AS RealID,
        CASE 
            WHEN o.CustomerID LIKE 'S_%' THEN 'Shareholder'
            WHEN o.CustomerID LIKE 'E_%' THEN 'Employee'
            WHEN o.CustomerID LIKE 'C_%' THEN 'Customer'
            WHEN o.CustomerType IN ('Shareholder', 'Employee') THEN o.CustomerType
            ELSE 'Customer'
        END AS CustomerType,
        CASE 
            WHEN o.CustomerID LIKE 'E_%' OR o.CustomerType = 'Employee' THEN (SELECT TitleEN FROM mst_employee WHERE Id = (CASE WHEN o.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
            WHEN o.CustomerID LIKE 'S_%' OR o.CustomerType = 'Shareholder' THEN (SELECT TitleEN FROM mst_shareholder WHERE Id = (CASE WHEN o.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
            ELSE (SELECT TitleEN FROM mst_customer WHERE CustomerID = (CASE WHEN o.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
        END AS TitleEN,
        CASE 
            WHEN o.CustomerID LIKE 'E_%' OR o.CustomerType = 'Employee' THEN (SELECT TitleBN FROM mst_employee WHERE Id = (CASE WHEN o.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
            WHEN o.CustomerID LIKE 'S_%' OR o.CustomerType = 'Shareholder' THEN (SELECT TitleBN FROM mst_shareholder WHERE Id = (CASE WHEN o.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
            ELSE (SELECT TitleBN FROM mst_customer WHERE CustomerID = (CASE WHEN o.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
        END AS TitleBN,
        CASE 
            WHEN o.CustomerID LIKE 'E_%' OR o.CustomerType = 'Employee' THEN (SELECT NameEN FROM mst_employee WHERE Id = (CASE WHEN o.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
            WHEN o.CustomerID LIKE 'S_%' OR o.CustomerType = 'Shareholder' THEN (SELECT NameEN FROM mst_shareholder WHERE Id = (CASE WHEN o.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
            ELSE (SELECT CustomerName FROM mst_customer WHERE CustomerID = (CASE WHEN o.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
        END AS NameEN,
        CASE 
            WHEN o.CustomerID LIKE 'E_%' OR o.CustomerType = 'Employee' THEN (SELECT NameBN FROM mst_employee WHERE Id = (CASE WHEN o.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
            WHEN o.CustomerID LIKE 'S_%' OR o.CustomerType = 'Shareholder' THEN (SELECT NameBN FROM mst_shareholder WHERE Id = (CASE WHEN o.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
            ELSE (SELECT CustomerName FROM mst_customer WHERE CustomerID = (CASE WHEN o.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
        END AS NameBN,
        CASE 
            WHEN o.CustomerID LIKE 'E_%' OR o.CustomerType = 'Employee' THEN (SELECT Mobile FROM mst_employee WHERE Id = (CASE WHEN o.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
            WHEN o.CustomerID LIKE 'S_%' OR o.CustomerType = 'Shareholder' THEN (SELECT Mobile FROM mst_shareholder WHERE Id = (CASE WHEN o.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
            ELSE (SELECT Mobile FROM mst_customer WHERE CustomerID = (CASE WHEN o.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) ELSE CAST(o.CustomerID AS UNSIGNED) END))
        END AS Mobile,
        'Opening Due Record' AS TxnSourceEN,
        'প্রারম্ভিক বকেয়া রেকর্ড' AS TxnSourceBN,
        o.SalesAmount AS CreditSale,
        o.Payment AS Payment,
        (o.SalesAmount - o.Payment) AS NetDue,
        o.Remarks
    FROM trx_Customer_Due_Opening o
    WHERE o.IsDeleted = 0 AND o.TxnDate BETWEEN ? AND ?
";
$histParams = [$startDate, $endDate];

if ($selectedEntity['id'] > 0) {
    $histPrefix = ($selectedEntity['type'] === 'Shareholder') ? 'S_%' : (($selectedEntity['type'] === 'Employee') ? 'E_%' : 'C_%');
    $histSql .= " AND (
        o.CustomerID = ?
        OR (o.CustomerID = ? AND (o.CustomerType = ? OR ((o.CustomerType IS NULL OR o.CustomerType = '') AND ? = 'Customer')))
        OR (o.CustomerID LIKE ? AND CAST(SUBSTRING(o.CustomerID, 3) AS UNSIGNED) = ?)
    )";
    $histParams[] = $selectedEntity['val'];
    $histParams[] = $selectedEntity['id'];
    $histParams[] = $selectedEntity['type'];
    $histParams[] = $selectedEntity['type'];
    $histParams[] = $histPrefix;
    $histParams[] = $selectedEntity['id'];
} elseif ($entityType !== 'all') {
    $histPrefix = ($entityType === 'Shareholder') ? 'S_%' : (($entityType === 'Employee') ? 'E_%' : 'C_%');
    $histSql .= " AND (
        o.CustomerType = ? 
        OR ((o.CustomerType IS NULL OR o.CustomerType = '') AND ? = 'Customer')
        OR o.CustomerID LIKE ?
    )";
    $histParams[] = $entityType;
    $histParams[] = $entityType;
    $histParams[] = $histPrefix;
}

// Stream 4: Operational Due Collections (trx_customercollection)
$opCollSql = "
    SELECT 
        cc.TxnDate,
        CASE 
            WHEN cc.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED)
            WHEN cc.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED)
            WHEN cc.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED)
            ELSE CAST(cc.CustomerID AS UNSIGNED)
        END AS RealID,
        CASE 
            WHEN cc.CustomerID LIKE 'S_%' THEN 'Shareholder'
            WHEN cc.CustomerID LIKE 'E_%' THEN 'Employee'
            WHEN cc.CustomerID LIKE 'C_%' THEN 'Customer'
            WHEN cc.CustomerType IN ('Shareholder', 'Employee') THEN cc.CustomerType
            ELSE 'Customer'
        END AS CustomerType,
        CASE 
            WHEN cc.CustomerID LIKE 'E_%' OR cc.CustomerType = 'Employee' THEN (SELECT TitleEN FROM mst_employee WHERE Id = (CASE WHEN cc.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
            WHEN cc.CustomerID LIKE 'S_%' OR cc.CustomerType = 'Shareholder' THEN (SELECT TitleEN FROM mst_shareholder WHERE Id = (CASE WHEN cc.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
            ELSE (SELECT TitleEN FROM mst_customer WHERE CustomerID = (CASE WHEN cc.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
        END AS TitleEN,
        CASE 
            WHEN cc.CustomerID LIKE 'E_%' OR cc.CustomerType = 'Employee' THEN (SELECT TitleBN FROM mst_employee WHERE Id = (CASE WHEN cc.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
            WHEN cc.CustomerID LIKE 'S_%' OR cc.CustomerType = 'Shareholder' THEN (SELECT TitleBN FROM mst_shareholder WHERE Id = (CASE WHEN cc.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
            ELSE (SELECT TitleBN FROM mst_customer WHERE CustomerID = (CASE WHEN cc.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
        END AS TitleBN,
        CASE 
            WHEN cc.CustomerID LIKE 'E_%' OR cc.CustomerType = 'Employee' THEN (SELECT NameEN FROM mst_employee WHERE Id = (CASE WHEN cc.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
            WHEN cc.CustomerID LIKE 'S_%' OR cc.CustomerType = 'Shareholder' THEN (SELECT NameEN FROM mst_shareholder WHERE Id = (CASE WHEN cc.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
            ELSE (SELECT CustomerName FROM mst_customer WHERE CustomerID = (CASE WHEN cc.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
        END AS NameEN,
        CASE 
            WHEN cc.CustomerID LIKE 'E_%' OR cc.CustomerType = 'Employee' THEN (SELECT NameBN FROM mst_employee WHERE Id = (CASE WHEN cc.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
            WHEN cc.CustomerID LIKE 'S_%' OR cc.CustomerType = 'Shareholder' THEN (SELECT NameBN FROM mst_shareholder WHERE Id = (CASE WHEN cc.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
            ELSE (SELECT CustomerName FROM mst_customer WHERE CustomerID = (CASE WHEN cc.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
        END AS NameBN,
        CASE 
            WHEN cc.CustomerID LIKE 'E_%' OR cc.CustomerType = 'Employee' THEN (SELECT Mobile FROM mst_employee WHERE Id = (CASE WHEN cc.CustomerID LIKE 'E_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
            WHEN cc.CustomerID LIKE 'S_%' OR cc.CustomerType = 'Shareholder' THEN (SELECT Mobile FROM mst_shareholder WHERE Id = (CASE WHEN cc.CustomerID LIKE 'S_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
            ELSE (SELECT Mobile FROM mst_customer WHERE CustomerID = (CASE WHEN cc.CustomerID LIKE 'C_%' THEN CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) ELSE CAST(cc.CustomerID AS UNSIGNED) END))
        END AS Mobile,
        CONCAT('Due Collection (', COALESCE(pm.MethodName, 'Cash'), ')') AS TxnSourceEN,
        CONCAT('বকেয়া সংগ্রহ (', COALESCE(pm.MethodName, 'ক্যাশ'), ')') AS TxnSourceBN,
        0.00 AS CreditSale,
        cc.Amount AS Payment,
        (0 - cc.Amount) AS NetDue,
        cc.Remarks
    FROM trx_customercollection cc
    LEFT JOIN cfg_paymentmethod pm ON cc.PaymentMethodID = pm.PaymentMethodID
    WHERE cc.IsDeleted = 0 AND cc.TxnDate BETWEEN ? AND ?
";
$opCollParams = [$startDate, $endDate];

if ($selectedEntity['id'] > 0) {
    $opCollPrefix = ($selectedEntity['type'] === 'Shareholder') ? 'S_%' : (($selectedEntity['type'] === 'Employee') ? 'E_%' : 'C_%');
    $opCollSql .= " AND (
        cc.CustomerID = ?
        OR (cc.CustomerID = ? AND (cc.CustomerType = ? OR ((cc.CustomerType IS NULL OR cc.CustomerType = '') AND ? = 'Customer')))
        OR (cc.CustomerID LIKE ? AND CAST(SUBSTRING(cc.CustomerID, 3) AS UNSIGNED) = ?)
    )";
    $opCollParams[] = $selectedEntity['val'];
    $opCollParams[] = $selectedEntity['id'];
    $opCollParams[] = $selectedEntity['type'];
    $opCollParams[] = $selectedEntity['type'];
    $opCollParams[] = $opCollPrefix;
    $opCollParams[] = $selectedEntity['id'];
} elseif ($entityType !== 'all') {
    $opCollPrefix = ($entityType === 'Shareholder') ? 'S_%' : (($entityType === 'Employee') ? 'E_%' : 'C_%');
    $opCollSql .= " AND (
        cc.CustomerType = ? 
        OR ((cc.CustomerType IS NULL OR cc.CustomerType = '') AND ? = 'Customer')
        OR cc.CustomerID LIKE ?
    )";
    $opCollParams[] = $entityType;
    $opCollParams[] = $entityType;
    $opCollParams[] = $opCollPrefix;
}

// Merge all 4 transaction streams (trx_customerdue, trx_customerdue_Previous, trx_Customer_Due_Opening, trx_customercollection)
$allTransactions = [];

$opDues   = $objQuery->index($opDueSql, $opParams);
$prevDues = $objQuery->index($prevSql, $prevParams);
$histDues = $objQuery->index($histSql, $histParams);
$opColls  = $objQuery->index($opCollSql, $opCollParams);

foreach ($opDues as $r)   $allTransactions[] = $r;
foreach ($prevDues as $r) $allTransactions[] = $r;
foreach ($histDues as $r) $allTransactions[] = $r;
foreach ($opColls as $r)  $allTransactions[] = $r;

// Format Person Name & Source for each transaction based on $lang
foreach ($allTransactions as $t) {
    $t->PersonName = formatPersonTitleName($t->TitleEN ?? '', $t->TitleBN ?? '', $t->NameEN ?? '', $t->NameBN ?? '', $lang);
    $t->TxnSource  = ($lang === 'en') ? ($t->TxnSourceEN ?? '') : ($t->TxnSourceBN ?? '');

    // Normalize default historical remarks dynamically according to selected language ($lang)
    $rmk = trim((string)($t->Remarks ?? ''));
    if ($lang === 'en') {
        if ($rmk === 'প্রারম্ভিক বকেয়া রেকর্ড' || $rmk === 'Excel Uploaded Opening Record' || $rmk === 'Opening Due Record') {
            $t->Remarks = 'Opening Balance';
        } elseif ($rmk === 'পূর্বের বকেয়া রেকর্ড') {
            $t->Remarks = 'Previous Due Record';
        }
    } else {
        if ($rmk === 'Opening Balance' || $rmk === 'Opening Due Record' || $rmk === 'Excel Uploaded Opening Record') {
            $t->Remarks = 'প্রারম্ভিক বকেয়া রেকর্ড';
        } elseif ($rmk === 'Previous Due Record') {
            $t->Remarks = 'পূর্বের বকেয়া রেকর্ড';
        }
    }
}

// Sort combined transactions by Date ASC
usort($allTransactions, function($a, $b) {
    return strtotime($a->TxnDate) <=> strtotime($b->TxnDate);
});

// Calculate Totals
$totalCreditSale = 0;
$totalPayment = 0;
$totalNetDue = 0;

foreach ($allTransactions as $t) {
    $totalCreditSale += floatval($t->CreditSale);
    $totalPayment += floatval($t->Payment);
    $totalNetDue += floatval($t->NetDue);
}

// Calculate Customer-Wise Summary Grouping & Transaction Map for Modal & Individual Print Breakdown
$personMetadataMap = [];
if (!empty($allPersons)) {
    foreach ($allPersons as $p) {
        $personMetadataMap[$p->ValueID] = [
            'name'    => $p->FormattedName,
            'mobile'  => $p->Mobile ?? '',
            'address' => !empty($p->Address) ? $p->Address : '-',
            'type'    => $p->EntityType
        ];
    }
}

$customerSummary = [];
$personTxnsMap = [];

foreach ($allTransactions as $t) {
    $type = $t->CustomerType ?? 'Customer';
    $prefix = ($type === 'Shareholder') ? 'S_' : (($type === 'Employee') ? 'E_' : 'C_');
    $key = $prefix . ($t->RealID ?? 0);

    if (!isset($customerSummary[$key])) {
        $meta = $personMetadataMap[$key] ?? [
            'name'    => $t->PersonName ?? 'Unknown',
            'mobile'  => $t->Mobile ?? '',
            'address' => '-',
            'type'    => $type
        ];

        $customerSummary[$key] = [
            'key'          => $key,
            'PersonName'   => $meta['name'],
            'Mobile'       => $meta['mobile'],
            'Address'      => $meta['address'],
            'CustomerType' => $type,
            'RealID'       => $t->RealID ?? 0,
            'CreditSale'   => 0.0,
            'Payment'      => 0.0,
            'NetDue'       => 0.0,
        ];
        $personTxnsMap[$key] = [
            'key'          => $key,
            'PersonName'   => $meta['name'],
            'Mobile'       => $meta['mobile'],
            'Address'      => $meta['address'],
            'CustomerType' => $type,
            'txns'         => []
        ];
    }
    $customerSummary[$key]['CreditSale'] += floatval($t->CreditSale);
    $customerSummary[$key]['Payment']    += floatval($t->Payment);
    $customerSummary[$key]['NetDue']     += floatval($t->NetDue);

    $personTxnsMap[$key]['txns'][] = [
        'TxnDate'    => date('d-M-y', strtotime($t->TxnDate)),
        'TxnYear'    => date('Y', strtotime($t->TxnDate)),
        'TxnSource'  => $t->TxnSource ?? '',
        'CreditSale' => floatval($t->CreditSale),
        'Payment'    => floatval($t->Payment),
        'NetDue'     => floatval($t->NetDue),
        'Remarks'    => $t->Remarks ?? ''
    ];
}

// Sort summary by NetDue DESC
usort($customerSummary, function($a, $b) {
    return $b['NetDue'] <=> $a['NetDue'];
});

// Step 1 & 2 KPI & Chart Data Calculations
$debtorCount = 0;
foreach ($customerSummary as $cs) {
    if (floatval($cs['NetDue']) > 0) {
        $debtorCount++;
    }
}
$avgDuePerDebtor = ($debtorCount > 0) ? ($totalNetDue / $debtorCount) : 0.0;
$recoveryRate = ($totalCreditSale > 0) ? round(($totalPayment / $totalCreditSale) * 100, 1) : 0.0;
$remainingDuePct = max(0.0, 100.0 - $recoveryRate);

// Category Totals for Donut Chart & Category Filter Tabs
$catDues = ['Customer' => 0.0, 'Shareholder' => 0.0, 'Employee' => 0.0];
$catCounts = ['Customer' => 0, 'Shareholder' => 0, 'Employee' => 0];

foreach ($customerSummary as $cs) {
    $type = $cs['CustomerType'] ?? 'Customer';
    if (!isset($catDues[$type])) {
        $type = 'Customer';
    }
    $dueVal = max(0.0, floatval($cs['NetDue']));
    $catDues[$type] += $dueVal;
    if ($dueVal > 0) {
        $catCounts[$type]++;
    }
}

// Top 10 Debtors for Horizontal Bar Chart
$topDebtors = [];
foreach ($customerSummary as $cs) {
    if (floatval($cs['NetDue']) > 0) {
        $topDebtors[] = [
            'name' => $cs['PersonName'],
            'due'  => round(floatval($cs['NetDue']), 2)
        ];
        if (count($topDebtors) >= 10) break;
    }
}

// Step 3: Monthly Sales vs Collections Trend Calculation
$monthlyMap = [];
foreach ($allTransactions as $t) {
    $mKey = date('Y-m', strtotime($t->TxnDate));
    if (!isset($monthlyMap[$mKey])) {
        $monthlyMap[$mKey] = ['sale' => 0.0, 'paid' => 0.0];
    }
    $monthlyMap[$mKey]['sale'] += floatval($t->CreditSale);
    $monthlyMap[$mKey]['paid'] += floatval($t->Payment);
}
ksort($monthlyMap);

$trendMonths = [];
$trendSales  = [];
$trendPaids  = [];
foreach ($monthlyMap as $mKey => $mVal) {
    $trendMonths[] = date('M-Y', strtotime($mKey . '-01'));
    $trendSales[]  = round($mVal['sale'], 2);
    $trendPaids[]  = round($mVal['paid'], 2);
}

// Step 4: Dues Ageing Analysis (মেয়াদ ভিত্তিক বকেয়া বিশ্লেষণ - FIFO Payment Allocation)
$ageingBuckets = [
    '0_30'  => ['name_bn' => '০-৩০ দিন (নতুন)', 'name_en' => '0-30 Days', 'amount' => 0.0, 'count' => 0, 'bg' => '#ecfdf5', 'color' => '#047857', 'border' => '#a7f3d0', 'icon' => 'fa-clock'],
    '31_60' => ['name_bn' => '৩১-৬০ দিন (মাঝারি)', 'name_en' => '31-60 Days', 'amount' => 0.0, 'count' => 0, 'bg' => '#fffbe6', 'color' => '#b45309', 'border' => '#fde68a', 'icon' => 'fa-hourglass-half'],
    '61_90' => ['name_bn' => '৬১-৯০ দিন (পুরাতন)', 'name_en' => '61-90 Days', 'amount' => 0.0, 'count' => 0, 'bg' => '#fff7ed', 'color' => '#c2410c', 'border' => '#ffedd5', 'icon' => 'fa-calendar-minus'],
    '90_plus' => ['name_bn' => '৯০+ দিন (ঝুঁকিপূর্ণ)', 'name_en' => '90+ Days (Risk)', 'amount' => 0.0, 'count' => 0, 'bg' => '#fff1f2', 'color' => '#9f1239', 'border' => '#fecdd3', 'icon' => 'fa-exclamation-triangle'],
];

$todayTs = time();

// Group transactions per person to perform FIFO collection allocation against open sales
$personTxnGroups = [];
foreach ($allTransactions as $t) {
    $type = $t->CustomerType ?? 'Customer';
    $prefix = ($type === 'Shareholder') ? 'S_' : (($type === 'Employee') ? 'E_' : 'C_');
    $pKey = $prefix . ($t->RealID ?? 0);
    
    if (!isset($personTxnGroups[$pKey])) {
        $personTxnGroups[$pKey] = [
            'sales' => [],
            'payments' => 0.0
        ];
    }
    
    $saleAmt = floatval($t->CreditSale);
    $paidAmt = floatval($t->Payment);
    
    if ($saleAmt > 0) {
        $personTxnGroups[$pKey]['sales'][] = [
            'date' => $t->TxnDate,
            'amount' => $saleAmt
        ];
    }
    if ($paidAmt > 0) {
        $personTxnGroups[$pKey]['payments'] += $paidAmt;
    }
}

// Perform Accounting FIFO allocation per person
foreach ($personTxnGroups as $pKey => $pData) {
    $totalPaid = $pData['payments'];
    
    // Sort sales by Date ASC (Oldest sales first)
    usort($pData['sales'], function($a, $b) {
        return strtotime($a['date']) <=> strtotime($b['date']);
    });
    
    foreach ($pData['sales'] as $sale) {
        $sAmt = $sale['amount'];
        $unpaidAmt = 0.0;
        
        if ($totalPaid >= $sAmt) {
            $totalPaid -= $sAmt;
            $unpaidAmt = 0.0;
        } else {
            $unpaidAmt = $sAmt - $totalPaid;
            $totalPaid = 0.0;
        }
        
        if ($unpaidAmt > 0.001) {
            $txnTs = strtotime($sale['date']);
            $ageDays = max(0, floor(($todayTs - $txnTs) / 86400));
            
            if ($ageDays <= 30) {
                $ageingBuckets['0_30']['amount'] += $unpaidAmt;
                $ageingBuckets['0_30']['count']++;
            } elseif ($ageDays <= 60) {
                $ageingBuckets['31_60']['amount'] += $unpaidAmt;
                $ageingBuckets['31_60']['count']++;
            } elseif ($ageDays <= 90) {
                $ageingBuckets['61_90']['amount'] += $unpaidAmt;
                $ageingBuckets['61_90']['count']++;
            } else {
                $ageingBuckets['90_plus']['amount'] += $unpaidAmt;
                $ageingBuckets['90_plus']['count']++;
            }
        }
    }
}

// Individual Customer Printable Statement New Tab Handler
if (isset($_GET['action']) && $_GET['action'] === 'print_individual') {
    $targetKey = $_GET['customer_key'] ?? '';
    $singlePersonData = $personTxnsMap[$targetKey] ?? null;

    if (!$singlePersonData && !empty($personTxnsMap)) {
        $singlePersonData = reset($personTxnsMap);
    }

    $pName = $singlePersonData['PersonName'] ?? 'Unknown Customer';
    $pMobile = $singlePersonData['Mobile'] ?? '';
    $pAddress = !empty($singlePersonData['Address']) ? $singlePersonData['Address'] : '-';
    $pType = $singlePersonData['CustomerType'] ?? 'Customer';
    $txnsList = $singlePersonData['txns'] ?? [];

    $typeLabel = ($lang === 'en') ? $pType : (($pType === 'Customer') ? 'গ্রাহক' : (($pType === 'Employee') ? 'কর্মচারী' : 'শেয়ারহোল্ডার'));
    $latestYear = date('Y');
    if (!empty($txnsList)) {
        $lastTxn = end($txnsList);
        $latestYear = $lastTxn['TxnYear'] ?? $latestYear;
    }

    ?>
    <!DOCTYPE html>
    <html lang="<?php echo htmlspecialchars($lang); ?>">
    <head>
        <meta charset="UTF-8">
        <title><?php echo htmlspecialchars($pName); ?> - Due Statement</title>
        <style>
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
            @media print {
                .no-print {
                    display: none !important;
                }
                body {
                    padding: 0 !important;
                }
            }
            body {
                font-family: 'Times New Roman', Times, serif, 'Nikosh', 'SolaimanLipi', sans-serif;
                margin: 0;
                padding: 15px;
                color: #000;
                background: #fff;
            }
            .top-action-bar {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 10px 18px;
                margin-bottom: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }
            .btn-print-action {
                background-color: #2563eb;
                color: #ffffff;
                border: none;
                padding: 8px 18px;
                font-size: 14px;
                font-weight: bold;
                border-radius: 6px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
            }
            .btn-print-action:hover {
                background-color: #1d4ed8;
            }
            .btn-close-action {
                background-color: #64748b;
                color: #ffffff;
                border: none;
                padding: 8px 16px;
                font-size: 14px;
                font-weight: 500;
                border-radius: 6px;
                cursor: pointer;
            }
            .btn-close-action:hover {
                background-color: #475569;
            }
            .header-block {
                text-align: center;
                margin-bottom: 20px;
            }
            .company-name {
                font-size: 28px;
                font-weight: bold;
                margin: 0;
                padding: 0;
            }
            .company-sub {
                font-size: 15px;
                margin-top: 3px;
            }
            .report-title {
                font-size: 19px;
                font-weight: bold;
                text-decoration: underline;
                border-bottom: 1.5px solid #000;
                padding-bottom: 2px;
                display: inline-block;
                margin-top: 10px;
            }
            .meta-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 18px;
                font-size: 15px;
                font-weight: bold;
            }
            .meta-table td {
                padding: 4px 0;
            }
            .meta-val {
                font-size: 16px;
                border-bottom: 1px solid #000;
                padding-bottom: 2px;
            }
            .data-table {
                width: 100%;
                border-collapse: collapse;
                border: 1.5px solid #000;
                font-size: 14px;
            }
            .data-table th {
                border: 1px solid #000;
                padding: 6px 10px;
                font-weight: bold;
                font-size: 15px;
                background-color: #f8f9fa;
            }
            .data-table td {
                border: 1px solid #000;
                padding: 5px 10px;
            }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .font-bold { font-weight: bold; }
            .footer-block {
                margin-top: 45px;
                width: 100%;
                display: flex;
                justify-content: space-between;
                font-size: 14px;
                page-break-inside: avoid;
            }
            .sig-box {
                text-align: center;
                width: 200px;
            }
            .sig-line {
                border-top: 1px dashed #000;
                padding-top: 5px;
                font-weight: bold;
            }
            .page-footer {
                margin-top: 25px;
                font-size: 11px;
                color: #333;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-top: 1px solid #eee;
                padding-top: 5px;
                page-break-inside: avoid;
            }
        </style>
    </head>
    <body onload="window.print()">
        <!-- Top Action Bar for Screen Preview (Hidden on Paper Print) -->
        <div class="top-action-bar no-print">
            <div style="font-weight: bold; color: #0f172a; font-size: 15px; font-family: sans-serif;">
                📄 <?php echo ($lang === 'en') ? 'Customer Due Statement Preview' : 'কাস্টমার বকেয়া বিবরণী (প্রিভিউ)'; ?>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="window.print()" class="btn-print-action">
                    🖨️ <?php echo ($lang === 'en') ? 'Print Report' : 'প্রিন্ট রিপোর্ট'; ?>
                </button>
                <button onclick="window.close()" class="btn-close-action">
                    ✖️ <?php echo ($lang === 'en') ? 'Close' : 'বন্ধ করুন'; ?>
                </button>
            </div>
        </div>

        <!-- Header -->
        <div class="header-block">
            <h1 class="company-name"><?php echo htmlspecialchars($compName); ?></h1>
            <div class="company-sub">
                <?php echo htmlspecialchars($compAddress); ?>
                <?php if (!empty($compMobile)): ?> | Mobile: <?php echo htmlspecialchars($compMobile); ?><?php endif; ?>
            </div>
            <div style="margin-top: 10px;">
                <span class="report-title"><?php echo ($lang === 'en') ? 'Due List' : 'বকেয়া তালিকা'; ?></span>
                <div style="font-size: 13px; font-weight: normal; margin-top: 4px; color: #333;">
                    (<?php echo ($lang === 'en') ? 'Period' : 'সময়কাল'; ?>: <?php echo date('d-M-Y', strtotime($startDate)); ?> <?php echo ($lang === 'en') ? 'to' : 'হতে'; ?> <?php echo date('d-M-Y', strtotime($endDate)); ?>)
                </div>
            </div>
        </div>

        <!-- Metadata -->
        <table class="meta-table">
            <tr>
                <td style="width: 110px; vertical-align: bottom;">
                    <?php echo ($lang === 'en') ? 'Customer Name :' : 'গ্রাহকের নাম :'; ?>
                </td>
                <td class="meta-val" style="vertical-align: bottom;">
                    <?php echo htmlspecialchars($pName); ?>
                    <span style="font-weight: normal; font-size: 13px; margin-left: 10px;">(<?php echo htmlspecialchars($typeLabel); ?><?php echo !empty($pMobile) ? ' - ' . htmlspecialchars($pMobile) : ''; ?>)</span>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: bottom; padding-top: 8px;">
                    <?php echo ($lang === 'en') ? 'Address :' : 'ঠিকানা :'; ?>
                </td>
                <td class="meta-val" style="vertical-align: bottom; font-weight: normal;">
                    <?php echo htmlspecialchars($pAddress); ?>
                </td>
            </tr>
        </table>

        <!-- Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%;" class="text-center"><?php echo ($lang === 'en') ? 'Date' : 'তারিখ'; ?></th>
                    <th style="width: 22%;" class="text-right"><?php echo ($lang === 'en') ? 'LPG Purchase' : 'এলপিজি ক্রয়/বকেয়া'; ?></th>
                    <th style="width: 20%;" class="text-right"><?php echo ($lang === 'en') ? 'Payment' : 'পরিশোধ/আদায়'; ?></th>
                    <th style="width: 22%;" class="text-right"><?php echo ($lang === 'en') ? 'Balance' : 'অবশিষ্ট বকেয়া'; ?></th>
                    <th style="width: 16%;" class="text-center"><?php echo ($lang === 'en') ? 'Remark' : 'মন্তব্য'; ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-left"><?php echo ($lang === 'en') ? 'Opening Balance' : 'প্রারম্ভিক জের (Opening Balance)'; ?></td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-center">-</td>
                </tr>
                <?php 
                    $totSale = 0; $totPaid = 0; $runningBal = 0;
                    foreach ($txnsList as $row):
                        $sale = floatval($row['CreditSale']);
                        $paid = floatval($row['Payment']);
                        $due  = floatval($row['NetDue']);

                        $totSale += $sale;
                        $totPaid += $paid;
                        $runningBal += $due;
                ?>
                <tr>
                    <td class="text-center"><?php echo $row['TxnDate']; ?></td>
                    <td class="text-right font-bold"><?php echo $sale > 0 ? number_format($sale, 2) : '-'; ?></td>
                    <td class="text-right"><?php echo $paid > 0 ? number_format($paid, 2) : '-'; ?></td>
                    <td class="text-right font-bold"><?php echo number_format($runningBal, 2); ?></td>
                    <td class="text-center" style="font-size: 12px; line-height: 1.2;"><?php echo htmlspecialchars(!empty($row['Remarks']) ? $row['Remarks'] : '-'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="border-top: 1.5px solid #000; font-weight: bold;">
                    <td class="text-right" style="font-size: 16px;"><?php echo ($lang === 'en') ? 'Total' : 'মোট'; ?></td>
                    <td class="text-right" style="font-size: 16px;"><?php echo number_format($totSale, 2); ?></td>
                    <td class="text-right" style="font-size: 16px;"><?php echo number_format($totPaid, 2); ?></td>
                    <td class="text-right" style="font-size: 16px;"><?php echo number_format($runningBal, 2); ?></td>
                    <td class="text-center">-</td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures -->
        <div class="footer-block">
            <div class="sig-box">
                <div class="sig-line"><?php echo ($lang === 'en') ? 'Prepared By Signature' : 'প্রস্তুতকারকের স্বাক্ষর'; ?></div>
            </div>
            <div class="sig-box">
                <div class="sig-line"><?php echo ($lang === 'en') ? 'Cashier Signature' : 'ক্যাশিয়ারের স্বাক্ষর'; ?></div>
            </div>
            <div class="sig-box">
                <div class="sig-line"><?php echo ($lang === 'en') ? 'Approved By Signature' : 'অনুমোদনকারীর স্বাক্ষর'; ?></div>
            </div>
        </div>

        <!-- Page Footer -->
        <div class="page-footer">
            <div><?php echo htmlspecialchars($compName); ?></div>
            <div><?php echo htmlspecialchars($pName); ?> | <?php echo date('d-M-Y h:i A'); ?></div>
            <div>Page: 1</div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Excel CSV Export Handler
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $filename = "Customer-Due-Report-" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $out = fopen('php://output', 'w');
    
    if ($lang === 'en') {
        fputcsv($out, ['Date', 'Person Name', 'Mobile', 'Category', 'Transaction Type', 'Credit Sale', 'Payment', 'Net Due', 'Remarks']);
    } else {
        fputcsv($out, ['তারিখ', 'ব্যক্তির নাম', 'মোবাইল', 'ক্যাটাগরি', 'লেনদেনের ধরন', 'বাকিতে বিক্রয়', 'পরিশোধ/আদায়', 'অবশিষ্ট বকেয়া', 'মন্তব্য']);
    }

    $rBal = 0;
    foreach ($allTransactions as $row) {
        $s = floatval($row->CreditSale);
        $p = floatval($row->Payment);
        $d = floatval($row->NetDue);
        $rBal += $d;

        $catLabel = $row->CustomerType ?? 'Customer';
        if ($lang === 'bn') {
            $catLabel = ($catLabel === 'Customer') ? 'গ্রাহক' : (($catLabel === 'Employee') ? 'কর্মচারী' : 'শেয়ারহোল্ডার');
        }

        fputcsv($out, [
            date('d-M-Y', strtotime($row->TxnDate)),
            $row->PersonName ?? '',
            $row->Mobile ?? '',
            $catLabel,
            $row->TxnSource ?? '',
            number_format($s, 2, '.', ''),
            number_format($p, 2, '.', ''),
            number_format($rBal, 2, '.', ''),
            $row->Remarks ?? ''
        ]);
    }

    $totalText = ($lang === 'en') ? 'Total' : 'সর্বমোট';
    fputcsv($out, [$totalText, '', '', '', '', number_format($totalCreditSale, 2, '.', ''), number_format($totalPayment, 2, '.', ''), number_format($totalNetDue, 2, '.', ''), '']);
    fclose($out);
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Google Fonts: Hind Siliguri (Modern Bangla) & Inter -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Include Chart.js Library for Dashboard Visualizations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Global Modern Typography & Print Specific Styling -->
<style>
body, button, input, select, textarea, .card, table, h1, h2, h3, h4, h5, h6, .badge {
    font-family: 'Hind Siliguri', 'Inter', system-ui, -apple-system, sans-serif !important;
}

@media print {
    html, body {
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        color: #000000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Hide Navigation, Sidebar, Filters, Dropdowns, and Modal elements */
    body:not(.print-single-customer) #sidebar, 
    body:not(.print-single-customer) .sidebar, 
    body:not(.print-single-customer) nav, 
    body:not(.print-single-customer) header, 
    body:not(.print-single-customer) footer, 
    body:not(.print-single-customer) .navbar, 
    body:not(.print-single-customer) .filter-card, 
    body:not(.print-single-customer) .d-print-none,
    body:not(.print-single-customer) .btn,
    body:not(.print-single-customer) .btn-group,
    body:not(.print-single-customer) .dropdown-menu,
    body:not(.print-single-customer) #summarySearchInput,
    body:not(.print-single-customer) #categoryTabs,
    body:not(.print-single-customer) #singleCustomerPrintArea,
    body:not(.print-single-customer) .modal,
    body:not(.print-single-customer) .modal-backdrop {
        display: none !important;
    }

    /* Standardize main printable dashboard container */
    body:not(.print-single-customer) #mainPrintContainer {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
    }

    /* Show Print Header */
    body:not(.print-single-customer) #reportPrintHeader {
        display: block !important;
    }

    /* Keep all dashboard cards, charts, gauge meters, and summary tables visible */
    body:not(.print-single-customer) .metric-cards-row,
    body:not(.print-single-customer) .screen-table-card,
    body:not(.print-single-customer) .recovery-rate-card,
    body:not(.print-single-customer) .dashboard-charts-row,
    body:not(.print-single-customer) .ageing-trend-row {
        display: flex !important;
        page-break-inside: avoid !important;
    }
    
    body:not(.print-single-customer) .screen-table-card, 
    body:not(.print-single-customer) .recovery-rate-card {
        display: block !important;
    }

    /* Ensure action column is hidden during print */
    body:not(.print-single-customer) .summary-action-col {
        display: none !important;
    }

    /* A4-Official Print Mode Styling */
    body.print-mode-a4 {
        font-size: 11.5px !important;
    }
    body.print-mode-a4 @page {
        size: A4 portrait;
        margin: 8mm 10mm 8mm 10mm;
    }

    /* Mobile View Print Mode Styling */
    body.print-mode-mobile {
        font-size: 13px !important;
    }
    body.print-mode-mobile @page {
        size: portrait;
        margin: 4mm;
    }
    body.print-mode-mobile #mainPrintContainer {
        max-width: 480px !important;
        margin: 0 auto !important;
    }
    body.print-mode-mobile .row > [class*="col-"] {
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
        margin-bottom: 12px !important;
    }
    body.print-mode-mobile .card {
        border: 1px solid #cbd5e1 !important;
        box-shadow: none !important;
    }

    /* Dedicated Individual Customer Print Handler */
    body.print-single-customer #sidebar, 
    body.print-single-customer .sidebar, 
    body.print-single-customer nav, 
    body.print-single-customer header, 
    body.print-single-customer footer, 
    body.print-single-customer .navbar, 
    body.print-single-customer .filter-card, 
    body.print-single-customer #mainPrintContainer,
    body.print-single-customer .modal,
    body.print-single-customer .modal-backdrop {
        display: none !important;
    }

    body.print-single-customer #singleCustomerPrintArea {
        display: block !important;
        position: relative !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        color: #000 !important;
        font-family: 'Times New Roman', Times, serif, 'Nikosh', 'SolaimanLipi', sans-serif !important;
        box-shadow: none !important;
    }
}

/* Explicit Royal Blue Header Styling for Customer Summary Table */
#customerSummaryTable thead, 
#customerSummaryTable thead tr, 
#customerSummaryTable thead th {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important;
    background-color: #1e3a8a !important;
    color: #ffffff !important;
    border-bottom: none !important;
}
</style>

<div class="row mb-4">
    <div class="col-md-12" id="mainPrintContainer">

        <!-- Print Document Header (Visible only in Print) -->
        <div id="reportPrintHeader" class="d-none d-print-block text-center mb-4 pb-2 border-bottom border-2 border-dark">
            <h2 class="fw-bold mb-1 text-dark" style="font-size: 22px;"><?php echo htmlspecialchars($compName); ?></h2>
            <div class="text-muted small mb-1" style="font-size: 13px;"><?php echo htmlspecialchars($compAddress); ?></div>
            <h4 class="fw-bold text-decoration-underline text-dark my-2" style="font-size: 17px;">
                <?php echo ($lang === 'en') ? 'Customer Due Summary & Risk Analytics Report' : 'কাস্টমার বকেয়া সারসংক্ষেপ ও রিকভারি এনালাইটিক্স রিপোর্ট'; ?>
            </h4>
            <div class="small font-monospace text-secondary fw-bold" style="font-size: 12px;">
                <?php echo ($lang === 'en') ? 'Period' : 'সময়কাল'; ?>: <?php echo date('d-M-Y', strtotime($startDate)); ?> <?php echo ($lang === 'en') ? 'to' : 'হতে'; ?> <?php echo date('d-M-Y', strtotime($endDate)); ?>
                | <?php echo ($lang === 'en') ? 'Category' : 'ক্যাটাগরি'; ?>: <?php echo htmlspecialchars($entityType); ?>
            </div>
        </div>
        
        <!-- Filter Card -->
        <div class="card shadow-sm border-0 mb-4 filter-card d-print-none">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between" style="background-color: #f8fafc !important;">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-user-tag text-primary me-2"></i> 
                    <?php echo ($lang === 'en') ? 'Customer Due Report' : 'কাস্টমার বকেয়া রিপোর্ট (Customer Due Report)'; ?>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <?php 
                        $exportUrl = "?action=export_csv&start_date=" . urlencode($startDate) . "&end_date=" . urlencode($endDate) . "&customer_id=" . urlencode($rawPerson) . "&entity_type=" . urlencode($entityType) . "&lang=" . urlencode($lang);
                    ?>
                    <a href="<?php echo $exportUrl; ?>" class="btn btn-success btn-sm fw-bold shadow-sm">
                        <i class="fas fa-file-excel me-1"></i> <?php echo ($lang === 'en') ? 'Export Excel' : 'Excel এক্সপোর্ট'; ?>
                    </a>
                    
                    <!-- Print Dropdown (A4-Official & Mobile View) -->
                    <div class="btn-group d-print-none">
                        <button type="button" class="btn btn-primary btn-sm fw-bold shadow-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-print me-1"></i> <?php echo ($lang === 'en') ? 'Print Report' : 'প্রিন্ট রিপোর্ট'; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 10px; font-size: 13px; z-index: 1050;">
                            <li>
                                <a class="dropdown-item py-2 fw-semibold d-flex align-items-center" href="javascript:void(0)" onclick="triggerReportPrint('a4_official')">
                                    <i class="fas fa-file-pdf text-danger me-2 fs-6"></i> 
                                    <div>
                                        <strong><?php echo ($lang === 'en') ? 'A4-Official' : 'A4-Official (এ৪ অফিসিয়াল)'; ?></strong>
                                        <small class="d-block text-muted" style="font-size: 10.5px;"><?php echo ($lang === 'en') ? 'Standard A4 Document Print' : 'অফিসিয়াল এ৪ পেপার প্রিন্ট'; ?></small>
                                    </div>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 fw-semibold d-flex align-items-center" href="javascript:void(0)" onclick="triggerReportPrint('mobile_view')">
                                    <i class="fas fa-mobile-alt text-success me-2 fs-6"></i>
                                    <div>
                                        <strong><?php echo ($lang === 'en') ? 'Mobile View' : 'Mobile View (মোবাইল ভিউ)'; ?></strong>
                                        <small class="d-block text-muted" style="font-size: 10.5px;"><?php echo ($lang === 'en') ? 'Compact Smart View' : 'মোবাইল / পোর্টেবল ভিউ'; ?></small>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card-body bg-light p-3">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang); ?>">

                    <!-- Start Date -->
                    <div class="col-md-3">
                        <label for="start_date" class="form-label fw-bold small text-muted">
                            <?php echo ($lang === 'en') ? 'Start Date' : 'শুরুর তারিখ (From Date)'; ?>
                        </label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
                    </div>

                    <!-- End Date -->
                    <div class="col-md-3">
                        <label for="end_date" class="form-label fw-bold small text-muted">
                            <?php echo ($lang === 'en') ? 'End Date' : 'শেষ তারিখ (To Date)'; ?>
                        </label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
                    </div>

                    <!-- Entity Type Filter -->
                    <div class="col-md-2">
                        <label for="entity_type" class="form-label fw-bold small text-muted">
                            <?php echo ($lang === 'en') ? 'Category' : 'ক্যাটাগরি (Category)'; ?>
                        </label>
                        <select name="entity_type" id="entity_type" class="form-select">
                            <option value="all" <?php echo $entityType === 'all' ? 'selected' : ''; ?>>
                                <?php echo ($lang === 'en') ? '-- All Categories --' : '-- সকল ক্যাটাগরি --'; ?>
                            </option>
                            <option value="Customer" <?php echo $entityType === 'Customer' ? 'selected' : ''; ?>>
                                <?php echo ($lang === 'en') ? 'Customer' : 'গ্রাহক (Customer)'; ?>
                            </option>
                            <option value="Employee" <?php echo $entityType === 'Employee' ? 'selected' : ''; ?>>
                                <?php echo ($lang === 'en') ? 'Employee' : 'কর্মচারী (Employee)'; ?>
                            </option>
                            <option value="Shareholder" <?php echo $entityType === 'Shareholder' ? 'selected' : ''; ?>>
                                <?php echo ($lang === 'en') ? 'Shareholder' : 'শেয়ারহোল্ডার (Shareholder)'; ?>
                            </option>
                        </select>
                    </div>

                    <!-- Customer / Person Filter -->
                    <div class="col-md-3">
                        <label for="customer_id" class="form-label fw-bold small text-muted">
                            <?php echo ($lang === 'en') ? 'Person' : 'নির্দিষ্ট ব্যক্তি (Person)'; ?>
                        </label>
                        <select name="customer_id" id="customer_id" class="form-select select2">
                            <option value="">
                                <?php echo ($lang === 'en') ? '-- All Persons --' : '-- সকল ব্যক্তি --'; ?>
                            </option>
                            <?php foreach($allPersons as $p): ?>
                                <?php 
                                    if ($lang === 'en') {
                                        $typeLabel = $p->EntityType;
                                    } else {
                                        $typeLabel = ($p->EntityType === 'Customer') ? 'গ্রাহক' : (($p->EntityType === 'Employee') ? 'কর্মচারী' : 'শেয়ারহোল্ডার');
                                    }
                                    $selected = ($selectedEntity['val'] === $p->ValueID) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $p->ValueID; ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($p->FormattedName) . ' (' . $typeLabel . (!empty($p->Mobile) ? ' - ' . htmlspecialchars($p->Mobile) : '') . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="fas fa-filter"></i> <?php echo ($lang === 'en') ? 'Filter' : 'ফিল্টার'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Metric Summary Cards (Corporate Executive Design) -->
        <div class="row g-3 mb-4">
            <!-- Total Credit Sales -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #9f1239 0%, #be123c 100%); border-radius: 12px;">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-white-50 text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 11px;">
                                <?php echo ($lang === 'en') ? 'Total Credit Sales' : 'মোট বাকিতে বিক্রয়'; ?>
                            </small>
                            <h3 class="fw-bold mb-0 mt-1 font-monospace"><?php echo number_format($totalCreditSale, 2); ?> <span class="fs-6 fw-normal">৳</span></h3>
                        </div>
                        <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background: rgba(255, 255, 255, 0.15); width: 48px; height: 48px;">
                            <i class="fas fa-shopping-cart fa-lg text-white"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Collection -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #047857 0%, #059669 100%); border-radius: 12px;">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-white-50 text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 11px;">
                                <?php echo ($lang === 'en') ? 'Total Collection' : 'মোট আদায়/পরিশোধ'; ?>
                            </small>
                            <h3 class="fw-bold mb-0 mt-1 font-monospace"><?php echo number_format($totalPayment, 2); ?> <span class="fs-6 fw-normal">৳</span></h3>
                        </div>
                        <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background: rgba(255, 255, 255, 0.15); width: 48px; height: 48px;">
                            <i class="fas fa-hand-holding-usd fa-lg text-white"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Net Outstanding Balance -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); border-radius: 12px;">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-white-50 text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 11px;">
                                <?php echo ($lang === 'en') ? 'Net Outstanding Balance' : 'অবশিষ্ট বকেয়া'; ?>
                            </small>
                            <h3 class="fw-bold mb-0 mt-1 font-monospace" style="color: #60a5fa;"><?php echo number_format($totalNetDue, 2); ?> <span class="fs-6 text-white fw-normal">৳</span></h3>
                        </div>
                        <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background: rgba(255, 255, 255, 0.15); width: 48px; height: 48px;">
                            <i class="fas fa-file-invoice-dollar fa-lg text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Due Summary Card (Grouped by Person with Filter Tabs & Live Search) -->
        <div class="card shadow-sm border-0 mb-4 screen-table-card" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header text-white d-flex align-items-center justify-content-between py-3 flex-wrap gap-2" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                <h6 class="mb-0 fw-bold fs-6">
                    <i class="fas fa-layer-group text-warning me-2"></i> 
                    <?php echo ($lang === 'en') ? 'Customer Due Summary' : 'কাস্টমারভিত্তিক বকেয়া সারসংক্ষেপ (Customer Due Summary)'; ?>
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <input type="text" id="summarySearchInput" class="form-control form-control-sm border-0 shadow-sm" style="width: 210px; font-size: 13px;" placeholder="<?php echo ($lang === 'en') ? '🔍 Search name / mobile...' : '🔍 নাম বা ফোন নম্বর খুঁজুন...'; ?>">
                    <span class="badge bg-white text-dark fw-bold px-3 py-2 fs-6 shadow-sm rounded-pill">
                        <span id="visiblePersonCount"><?php echo count($customerSummary); ?></span> <?php echo ($lang === 'en') ? 'Persons' : 'টি গ্রাহক'; ?>
                    </span>
                </div>
            </div>

            <!-- Interactive Category Filter Tabs (Filter Pills) -->
            <div class="card-body bg-light border-bottom py-2 px-3">
                <div class="d-flex align-items-center gap-2 flex-wrap" id="categoryTabs">
                    <button class="btn btn-sm btn-dark active cat-tab-btn px-3 fw-bold rounded-pill" data-cat="all" style="background-color: #0f172a; border-color: #0f172a;">
                        <i class="fas fa-th-large me-1"></i> <?php echo ($lang === 'en') ? 'All Categories' : 'সকল ক্যাটাগরি'; ?> 
                        <span class="badge bg-white text-dark ms-1"><?php echo count($customerSummary); ?></span>
                    </button>
                    <button class="btn btn-sm btn-outline-primary cat-tab-btn px-3 fw-bold rounded-pill bg-white" data-cat="Customer" style="color: #2563eb; border-color: #93c5fd;">
                        <i class="fas fa-user me-1"></i> <?php echo ($lang === 'en') ? 'Customer' : 'গ্রাহক'; ?> 
                        <span class="badge bg-primary text-white ms-1"><?php echo $catCounts['Customer']; ?></span>
                    </button>
                    <button class="btn btn-sm btn-outline-warning cat-tab-btn px-3 fw-bold rounded-pill bg-white" data-cat="Shareholder" style="color: #b45309; border-color: #fde68a;">
                        <i class="fas fa-handshake me-1" style="color: #d97706;"></i> <?php echo ($lang === 'en') ? 'Shareholder' : 'শেয়ারহোল্ডার'; ?> 
                        <span class="badge bg-warning text-dark ms-1"><?php echo $catCounts['Shareholder']; ?></span>
                    </button>
                    <button class="btn btn-sm btn-outline-info cat-tab-btn px-3 fw-bold rounded-pill bg-white" data-cat="Employee" style="color: #4f46e5; border-color: #c7d2fe;">
                        <i class="fas fa-user-tie me-1" style="color: #4f46e5;"></i> <?php echo ($lang === 'en') ? 'Employee' : 'কর্মচারী'; ?> 
                        <span class="badge bg-info text-white ms-1"><?php echo $catCounts['Employee']; ?></span>
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="customerSummaryTable">
                        <thead style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important; color: #ffffff !important;">
                            <tr style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important;">
                                <th class="py-3 px-3 text-center fw-bold" style="width: 7%; color: #ffffff !important; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important; font-size: 13px;">#</th>
                                <th class="py-3 px-3 fw-bold" style="width: 43%; color: #ffffff !important; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important; font-size: 13px;">
                                    <?php echo ($lang === 'en') ? 'Customer / Person Name' : 'কাস্টমার / ব্যক্তির নাম (Customer/Person)'; ?>
                                </th>
                                <th class="py-3 px-3 text-end fw-bold" style="width: 20%; color: #ffffff !important; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important; font-size: 13px;">
                                    <?php echo ($lang === 'en') ? 'Net Outstanding (৳)' : 'বকেয়ার পরিমাণ (টাকা)'; ?>
                                </th>
                                <th class="py-3 px-3 text-end fw-bold" style="width: 18%; color: #ffffff !important; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important; font-size: 13px;">
                                    <?php echo ($lang === 'en') ? 'Due Share (%)' : 'বকেয়ার শেয়ার (%)'; ?>
                                </th>
                                <th class="py-3 px-3 text-center fw-bold summary-action-col" style="width: 12%; color: #ffffff !important; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important; font-size: 13px;">
                                    <?php echo ($lang === 'en') ? 'Action' : 'অ্যাকশন'; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customerSummary)): ?>
                            <tr id="noSummaryRow">
                                <td colspan="5" class="text-center text-muted py-4">
                                    <?php echo ($lang === 'en') ? 'No customer summary data available.' : 'কোনো কাস্টমার সারসংক্ষেপ তথ্য পাওয়া যায়নি।'; ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php $rank = 1; foreach ($customerSummary as $cs): ?>
                            <?php 
                                $csDue = floatval($cs['NetDue']);
                                $csPct = ($totalNetDue > 0) ? round(($csDue / $totalNetDue) * 100, 1) : 0.0;
                                $typeVal = $cs['CustomerType'] ?? 'Customer';
                                $catBadge = ($typeVal === 'Customer') ? 'bg-primary' : (($typeVal === 'Employee') ? 'bg-info' : 'bg-warning text-dark');
                                
                                if ($lang === 'en') {
                                    $catLabel = $typeVal;
                                } else {
                                    $catLabel = ($typeVal === 'Customer') ? 'গ্রাহক' : (($typeVal === 'Employee') ? 'কর্মচারী' : 'শেয়ারহোল্ডার');
                                }

                                // Rank Badges (Highest Debtor Risk Indicators)
                                if ($rank === 1) {
                                    $rankBadge = '<span class="badge font-monospace shadow-sm" style="background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%); color: #78350f; font-size: 11px; padding: 4px 8px;"><i class="fas fa-exclamation-triangle text-danger me-1"></i>#১</span>';
                                } elseif ($rank === 2) {
                                    $rankBadge = '<span class="badge bg-secondary text-white font-monospace shadow-sm" style="font-size: 11px; padding: 4px 8px;">#২</span>';
                                } elseif ($rank === 3) {
                                    $rankBadge = '<span class="badge font-monospace shadow-sm" style="background-color: #f59e0b; color: #ffffff; font-size: 11px; padding: 4px 8px;">#৩</span>';
                                } else {
                                    $rankBadge = '<span class="badge bg-light text-muted font-monospace border" style="font-size: 11px; padding: 4px 8px;">#' . $rank . '</span>';
                                }

                                // Refined Executive Severity Badges & Dynamic Bar Gradients
                                if ($csDue >= 50000) {
                                    $severityBadge = '<span class="badge ms-1.5" style="background-color: #ffe4e6; color: #9f1239; border: 1px solid #fecdd3; font-size: 10.5px; font-weight: 600;">' . (($lang === 'en') ? 'High Due' : 'উচ্চ বকেয়া') . '</span>';
                                    $barGradient = 'linear-gradient(90deg, #f43f5e 0%, #be123c 100%)';
                                } elseif ($csDue >= 10000) {
                                    $severityBadge = '<span class="badge ms-1.5" style="background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 10.5px; font-weight: 600;">' . (($lang === 'en') ? 'Medium' : 'মাঝারি বকেয়া') . '</span>';
                                    $barGradient = 'linear-gradient(90deg, #fbbf24 0%, #d97706 100%)';
                                } else {
                                    $severityBadge = '<span class="badge ms-1.5" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 10.5px; font-weight: 500;">' . (($lang === 'en') ? 'Normal' : 'স্বাভাবিক') . '</span>';
                                    $barGradient = 'linear-gradient(90deg, #60a5fa 0%, #2563eb 100%)';
                                }

                                // Initial Avatar letter
                                $initialChar = mb_substr(trim($cs['PersonName']), 0, 1, 'UTF-8');
                            ?>
                            <tr class="summary-row" data-cat="<?php echo htmlspecialchars($typeVal); ?>" style="cursor: pointer;" onclick="openPersonDetailModal('<?php echo htmlspecialchars($cs['key']); ?>')">
                                <!-- Rank Column -->
                                <td class="text-center py-2.5 px-3">
                                    <?php echo $rankBadge; ?>
                                </td>

                                <!-- Person Name & Initials Avatar -->
                                <td class="py-2.5 px-3">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-2sm text-white" style="width: 32px; height: 32px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); font-size: 13px; flex-shrink: 0;">
                                            <?php echo htmlspecialchars($initialChar); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6" style="line-height: 1.2;">
                                                <?php echo htmlspecialchars($cs['PersonName']); ?>
                                                <span class="badge <?php echo $catBadge; ?> ms-1.5" style="font-size: 10.5px; font-weight: 500; padding: 2.5px 7px;">
                                                    <?php echo htmlspecialchars($catLabel); ?>
                                                </span>
                                                <?php echo $severityBadge; ?>
                                            </div>
                                            <?php if (!empty($cs['Mobile'])): ?>
                                                <small class="text-muted font-monospace" style="font-size: 11px;"><i class="fas fa-phone-alt me-1 text-primary opacity-50" style="font-size: 9.5px;"></i><?php echo htmlspecialchars($cs['Mobile']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Net Due Amount -->
                                <td class="py-2.5 px-3 text-end font-monospace fw-bold text-primary fs-6">
                                    <?php echo number_format($csDue, 2); ?> ৳
                                </td>

                                <!-- Progress Bar & Due Share % -->
                                <td class="py-2.5 px-3 text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <div class="progress flex-grow-1" style="height: 8px; background-color: #e2e8f0; max-width: 130px; border-radius: 10px; overflow: hidden;">
                                            <div class="progress-bar rounded-pill" role="progressbar" style="width: <?php echo min(100, max(0, $csPct)); ?>%; background: <?php echo $barGradient; ?>;"></div>
                                        </div>
                                        <span class="fw-bold small text-dark font-monospace" style="min-width: 46px; text-align: right;"><?php echo number_format($csPct, 1); ?>%</span>
                                    </div>
                                </td>

                                <!-- Details Action Button -->
                                <td class="py-2.5 px-3 text-center summary-action-col">
                                    <button type="button" class="btn btn-sm btn-light border fw-bold text-primary px-3 rounded-pill shadow-2sm" style="font-size: 11.5px; background: #ffffff;" title="<?php echo ($lang === 'en') ? 'Click to view details statement' : 'বিস্তারিত বিবরণী দেখুন'; ?>">
                                        <i class="fas fa-eye me-1 text-primary"></i> <?php echo ($lang === 'en') ? 'Details' : 'বিবরণী'; ?>
                                    </button>
                                </td>
                            </tr>
                            <?php $rank++; endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot style="background-color: #f8fafc; border-top: 2px solid #e2e8f0;" class="fw-bold">
                            <tr>
                                <td colspan="2" class="py-3 px-3 fs-6 text-dark">
                                    <i class="fas fa-calculator text-primary me-2"></i>
                                    <?php echo ($lang === 'en') ? 'Summary Total (' . count($customerSummary) . ' Persons):' : 'সর্বমোট কাস্টমার বকেয়া সারসংক্ষেপ (' . count($customerSummary) . ' জন):'; ?>
                                </td>
                                <td class="py-3 px-3 text-end font-monospace text-primary fs-6 fw-bold">
                                    <?php echo number_format($totalNetDue, 2); ?> ৳
                                </td>
                                <td class="py-3 px-3 text-end font-monospace text-dark fs-6 fw-bold">
                                    100.0%
                                </td>
                                <td class="summary-action-col"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Collection Efficiency Recovery Rate Meter & Executive KPI Mini-Cards -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px; overflow: hidden; background: #ffffff;">
            <div class="card-header bg-white py-3 px-4 border-bottom-0 d-flex align-items-center justify-content-between flex-wrap gap-2" style="background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%); border-bottom: 1px solid #f1f5f9 !important;">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle p-2 me-2.5 d-flex align-items-center justify-content-center" style="background-color: #ecfdf5; color: #059669; width: 36px; height: 36px;">
                        <i class="fas fa-chart-line fs-6"></i>
                    </div>
                    <span class="fw-bold text-dark fs-6">
                        <?php echo ($lang === 'en') ? 'Collection Recovery Rate' : 'কালেকশন ও বকেয়ার অনুপাত (Recovery Rate)'; ?>
                    </span>
                    <span class="badge ms-2.5 px-3 py-1.5 rounded-pill fs-6 font-monospace shadow-sm" style="background: linear-gradient(135deg, #047857 0%, #059669 100%); color: #ffffff; font-weight: 700;">
                        <?php echo number_format($recoveryRate, 1); ?>%
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge px-3 py-2 rounded-pill font-monospace" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 12px; font-weight: 600;">
                        <i class="fas fa-circle me-1.5" style="font-size: 8px; color: #059669;"></i> <?php echo ($lang === 'en') ? 'Collected' : 'আদায়কৃত'; ?>: <strong><?php echo number_format($recoveryRate, 1); ?>%</strong>
                    </span>
                    <span class="badge px-3 py-2 rounded-pill font-monospace" style="background-color: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; font-size: 12px; font-weight: 600;">
                        <i class="fas fa-circle me-1.5" style="font-size: 8px; color: #be123c;"></i> <?php echo ($lang === 'en') ? 'Outstanding' : 'অবশিষ্ট বকেয়া'; ?>: <strong><?php echo number_format($remainingDuePct, 1); ?>%</strong>
                    </span>
                </div>
            </div>

            <div class="card-body p-4 pt-3">
                <!-- Ultra-3D Glossy Financial Recovery Gauge Meter (Matching Top Metric Card Color Palette) -->
                <div class="mb-4">
                    <div class="progress shadow-sm" 
                         style="height: 36px; border-radius: 30px; background: linear-gradient(180deg, #cbd5e1 0%, #e2e8f0 50%, #f1f5f9 100%); box-shadow: inset 0 3px 6px rgba(0,0,0,0.22), 0 1px 2px rgba(255,255,255,0.8); border: 1px solid #cbd5e1; overflow: hidden; position: relative;">
                        
                        <!-- 3D Collected Segment (Matching Deep Emerald Green #047857 -> #059669) -->
                        <div class="progress-bar d-flex align-items-center justify-content-center fw-bold text-white font-monospace" role="progressbar" 
                             style="width: <?php echo min(100, max(0, $recoveryRate)); ?>%; 
                                    background: linear-gradient(180deg, #10b981 0%, #059669 45%, #047857 100%); 
                                    box-shadow: inset 0 2px 3px rgba(255,255,255,0.65), inset 0 -2px 3px rgba(0,0,0,0.35), 0 0 10px rgba(5,150,105,0.4); 
                                    border-right: 3px solid #ffffff; 
                                    transition: width 0.6s ease; font-size: 14px; text-shadow: 0 1.5px 3px rgba(0,0,0,0.7); position: relative;" 
                             title="<?php echo ($lang === 'en') ? 'Collected' : 'আদায়কৃত'; ?>: <?php echo number_format($totalPayment, 2); ?> ৳ (<?php echo number_format($recoveryRate, 1); ?>%)">
                            <?php if ($recoveryRate >= 14): ?>
                                <span class="d-inline-flex align-items-center gap-2"><i class="fas fa-check-circle" style="filter: drop-shadow(0 1px 1px rgba(0,0,0,0.5)); margin-right: 4px;"></i> <?php echo ($lang === 'en') ? 'Collected' : 'আদায়'; ?>: <?php echo number_format($recoveryRate, 1); ?>% (<?php echo number_format($totalPayment, 0); ?> ৳)</span>
                            <?php elseif ($recoveryRate >= 8): ?>
                                <span><?php echo number_format($recoveryRate, 1); ?>%</span>
                            <?php endif; ?>
                        </div>

                        <!-- 3D Outstanding Segment (Matching Crimson Red #9f1239 -> #be123c) -->
                        <div class="progress-bar d-flex align-items-center justify-content-center fw-bold text-white font-monospace" role="progressbar" 
                             style="width: <?php echo min(100, max(0, $remainingDuePct)); ?>%; 
                                    background: linear-gradient(180deg, #f43f5e 0%, #be123c 45%, #9f1239 100%); 
                                    box-shadow: inset 0 2px 3px rgba(255,255,255,0.65), inset 0 -2px 3px rgba(0,0,0,0.35), 0 0 10px rgba(190,18,60,0.4); 
                                    transition: width 0.6s ease; font-size: 14px; text-shadow: 0 1.5px 3px rgba(0,0,0,0.7); position: relative;" 
                             title="<?php echo ($lang === 'en') ? 'Outstanding' : 'অবশিষ্ট বকেয়া'; ?>: <?php echo number_format($totalNetDue, 2); ?> ৳ (<?php echo number_format($remainingDuePct, 1); ?>%)">
                            <?php if ($remainingDuePct >= 14): ?>
                                <span class="d-inline-flex align-items-center gap-2"><i class="fas fa-exclamation-triangle" style="filter: drop-shadow(0 1px 1px rgba(0,0,0,0.5)); margin-right: 4px;"></i> <?php echo ($lang === 'en') ? 'Due' : 'বকেয়া'; ?>: <?php echo number_format($remainingDuePct, 1); ?>% (<?php echo number_format($totalNetDue, 0); ?> ৳)</span>
                            <?php elseif ($remainingDuePct >= 8): ?>
                                <span><?php echo number_format($remainingDuePct, 1); ?>%</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Gauge Scale Ruler Marks (0% -> 100%) -->
                    <div class="d-flex justify-content-between px-2 mt-1.5 small text-muted font-monospace fw-bold opacity-75" style="font-size: 10.5px;">
                        <span>| 0%</span>
                        <span>| 25%</span>
                        <span>| 50%</span>
                        <span>| 75%</span>
                        <span>100% |</span>
                    </div>
                </div>
                
                <!-- Bottom Executive KPI Mini-Cards -->
                <div class="row g-3">
                    <!-- Total Debtors Card -->
                    <div class="col-md-6">
                        <div class="p-3 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #bae6fd; border-radius: 12px;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="background-color: #0284c7; color: #ffffff; width: 44px; height: 44px; flex-shrink: 0;">
                                    <i class="fas fa-users fs-5"></i>
                                </div>
                                <div>
                                    <small class="text-uppercase fw-bold d-block" style="font-size: 11px; letter-spacing: 0.5px; color: #0369a1;">
                                        <?php echo ($lang === 'en') ? 'TOTAL DEBTORS' : 'মোট দেনাদার সংখ্যা'; ?>
                                    </small>
                                    <h4 class="fw-bold mb-0 font-monospace mt-0.5" style="color: #0c4a6e;">
                                        <?php echo $debtorCount; ?> <span class="fs-6 fw-bold" style="color: #0284c7;"><?php echo ($lang === 'en') ? 'Persons' : 'জন'; ?></span>
                                    </h4>
                                </div>
                            </div>
                            <span class="badge bg-white text-primary fw-semibold px-2.5 py-1 border border-info border-opacity-25" style="font-size: 11px;">
                                <i class="fas fa-user-check me-1"></i> <?php echo ($lang === 'en') ? 'Active' : 'সক্রিয় দেনাদার'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Avg Due per Person Card -->
                    <div class="col-md-6">
                        <div class="p-3 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #fffbe6 0%, #fef3c7 100%); border: 1px solid #fde68a; border-radius: 12px;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="background-color: #d97706; color: #ffffff; width: 44px; height: 44px; flex-shrink: 0;">
                                    <i class="fas fa-calculator fs-5"></i>
                                </div>
                                <div>
                                    <small class="text-uppercase fw-bold d-block" style="font-size: 11px; letter-spacing: 0.5px; color: #b45309;">
                                        <?php echo ($lang === 'en') ? 'AVG DUE PER PERSON' : 'জনপ্রতি গড় বকেয়া'; ?>
                                    </small>
                                    <h4 class="fw-bold mb-0 font-monospace mt-0.5" style="color: #78350f;">
                                        <?php echo number_format($avgDuePerDebtor, 2); ?> <span class="fs-6 text-warning-emphasis fw-normal">৳</span>
                                    </h4>
                                </div>
                            </div>
                            <span class="badge bg-white text-warning-emphasis fw-semibold px-2.5 py-1 border border-warning border-opacity-25" style="font-size: 11px; color: #b45309 !important;">
                                <i class="fas fa-chart-pie me-1"></i> <?php echo ($lang === 'en') ? 'Avg Ratio' : 'গড় হিসাব'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphical Dashboard Charts -->
        <div class="row g-3 mb-4">
            <!-- Top 10 Debtors Bar Chart -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-header bg-white py-3 px-4 border-0 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-bottom: 1px solid #f1f5f9 !important;">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-crown text-warning me-2"></i>
                            <?php echo ($lang === 'en') ? 'Top 10 Outstanding Debtors' : 'সর্বোচ্চ বকেয়া থাকা ১০ জন ব্যক্তি'; ?>
                        </h6>
                        <?php $top10Total = !empty($topDebtors) ? array_sum(array_column($topDebtors, 'due')) : 0; ?>
                        <span class="badge px-3 py-1.5 rounded-pill font-monospace" style="background-color: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; font-size: 12px; font-weight: 700;">
                            <?php echo ($lang === 'en') ? 'Top 10 Total' : '১০ জনের মোট বকেয়া'; ?>: <?php echo number_format($top10Total, 2); ?> ৳
                        </span>
                    </div>
                    <div class="card-body p-3">
                        <div style="height: 275px; position: relative;">
                            <canvas id="topDebtorsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Distribution Donut Chart -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-header bg-white py-3 px-4 border-0 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-bottom: 1px solid #f1f5f9 !important;">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-chart-pie text-warning me-2"></i>
                            <?php echo ($lang === 'en') ? 'Due Distribution by Category' : 'ক্যাটাগরি ভিত্তিক বকেয়ার অনুপাত'; ?>
                        </h6>
                        <span class="badge px-2.5 py-1 rounded-pill bg-light text-dark border fw-bold" style="font-size: 11px;">
                            <?php echo ($lang === 'en') ? '3 Categories' : '৩টি ক্যাটাগরি'; ?>
                        </span>
                    </div>
                    <div class="card-body p-3 d-flex flex-column align-items-center justify-content-between">
                        <!-- Canvas with Glassmorphism Center Ring -->
                        <div style="height: 200px; width: 100%; position: relative;" class="d-flex align-items-center justify-content-center mb-2">
                            <canvas id="categoryDonutChart"></canvas>
                            <!-- Glassmorphism Center Ring -->
                            <div style="position: absolute; text-align: center; pointer-events: none; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 112px; height: 112px; border-radius: 50%; background: radial-gradient(circle, #ffffff 65%, #f8fafc 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(0,0,0,0.06), 0 0 10px rgba(0,0,0,0.03); border: 1px solid #e2e8f0;">
                                <small class="text-muted fw-bold d-block" style="font-size: 10px; letter-spacing: 0.5px; text-transform: uppercase;">
                                    <?php echo ($lang === 'en') ? 'TOTAL DUE' : 'মোট বকেয়া'; ?>
                                </small>
                                <span class="fw-bold font-monospace text-dark" style="font-size: 13px; color: #0f172a !important;">
                                    <?php echo number_format($totalNetDue, 2); ?> ৳
                                </span>
                            </div>
                        </div>

                        <!-- Interactive Category Summary Stat Pills -->
                        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap w-100 pt-2 border-top">
                            <?php
                                $custPct = ($totalNetDue > 0) ? number_format(($catDues['Customer'] / $totalNetDue) * 100, 1) : '0.0';
                                $shPct   = ($totalNetDue > 0) ? number_format(($catDues['Shareholder'] / $totalNetDue) * 100, 1) : '0.0';
                                $empPct  = ($totalNetDue > 0) ? number_format(($catDues['Employee'] / $totalNetDue) * 100, 1) : '0.0';
                            ?>
                            <span class="badge px-2.5 py-1.5 rounded-pill font-monospace" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 11px;">
                                <i class="fas fa-circle me-1" style="color: #2563eb; font-size: 8px;"></i> <?php echo ($lang === 'en') ? 'Customer' : 'গ্রাহক'; ?>: <strong><?php echo number_format($catDues['Customer'], 2); ?> ৳</strong> (<?php echo $custPct; ?>%)
                            </span>
                            <span class="badge px-2.5 py-1.5 rounded-pill font-monospace" style="background-color: #fffbe6; color: #b45309; border: 1px solid #fde68a; font-size: 11px;">
                                <i class="fas fa-circle me-1" style="color: #d97706; font-size: 8px;"></i> <?php echo ($lang === 'en') ? 'Shareholder' : 'শেয়ারহোল্ডার'; ?>: <strong><?php echo number_format($catDues['Shareholder'], 2); ?> ৳</strong> (<?php echo $shPct; ?>%)
                            </span>
                            <span class="badge px-2.5 py-1.5 rounded-pill font-monospace" style="background-color: #f5f3ff; color: #4338ca; border: 1px solid #ddd6fe; font-size: 11px;">
                                <i class="fas fa-circle me-1" style="color: #4f46e5; font-size: 8px;"></i> <?php echo ($lang === 'en') ? 'Employee' : 'কর্মচারী'; ?>: <strong><?php echo number_format($catDues['Employee'], 2); ?> ৳</strong> (<?php echo $empPct; ?>%)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dues Ageing Analysis & Monthly Sale vs Payment Trend Row -->
        <div class="row g-3 mb-4">
            <!-- Dues Ageing Risk Analysis Card -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; overflow: hidden; background: #ffffff;">
                    <div class="card-header bg-white py-3 px-4 border-0 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-bottom: 1px solid #f1f5f9 !important;">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-history text-danger me-2"></i>
                            <?php echo ($lang === 'en') ? 'Dues Ageing Analysis (Risk Profile)' : 'মেয়াদ ভিত্তিক বকেয়া ঝুঁকি বিশ্লেষণ (Ageing Analysis)'; ?>
                        </h6>
                        <span class="badge px-2.5 py-1 rounded-pill bg-light text-dark border fw-bold" style="font-size: 11px;">
                            <i class="fas fa-clock me-1 text-muted"></i> <?php echo ($lang === 'en') ? '4 Ageing Buckets' : '৪টি মেয়াদী ধাপ'; ?>
                        </span>
                    </div>
                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        <div class="row g-2.5">
                            <?php foreach ($ageingBuckets as $bKey => $bucket): ?>
                            <?php 
                                $bAmt = $bucket['amount'];
                                $bCount = $bucket['count'];
                                $bPct = ($totalNetDue > 0) ? round(($bAmt / $totalNetDue) * 100, 1) : 0.0;
                            ?>
                            <div class="col-6">
                                <div class="p-3 rounded-3" style="background-color: <?php echo $bucket['bg']; ?>; border: 1px solid <?php echo $bucket['border']; ?>; height: 100%;">
                                    <div class="d-flex align-items-center justify-content-between mb-1.5">
                                        <small class="fw-bold text-uppercase" style="color: <?php echo $bucket['color']; ?>; font-size: 11px;">
                                            <i class="fas <?php echo $bucket['icon']; ?> me-1"></i>
                                            <?php echo ($lang === 'en') ? $bucket['name_en'] : $bucket['name_bn']; ?>
                                        </small>
                                        <span class="badge rounded-pill font-monospace" style="background-color: #ffffff; color: <?php echo $bucket['color']; ?>; border: 1px solid <?php echo $bucket['border']; ?>; font-size: 10px;">
                                            <?php echo $bCount; ?> <?php echo ($lang === 'en') ? 'txns' : 'টি'; ?>
                                        </span>
                                    </div>
                                    <h5 class="fw-bold font-monospace mb-1" style="color: <?php echo $bucket['color']; ?>;">
                                        <?php echo number_format($bAmt, 2); ?> <span class="fs-6 fw-normal">৳</span>
                                    </h5>
                                    <div class="d-flex align-items-center gap-2 mt-2">
                                        <div class="progress flex-grow-1" style="height: 6px; background-color: rgba(0,0,0,0.06); border-radius: 10px;">
                                            <div class="progress-bar rounded-pill" role="progressbar" style="width: <?php echo min(100, max(0, $bPct)); ?>%; background-color: <?php echo $bucket['color']; ?>;"></div>
                                        </div>
                                        <small class="fw-bold font-monospace" style="color: <?php echo $bucket['color']; ?>; font-size: 11px; min-width: 38px; text-align: right;">
                                            <?php echo $bPct; ?>%
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Sales vs Payments Trend Line Chart -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; overflow: hidden; background: #ffffff;">
                    <div class="card-header bg-white py-3 px-4 border-0 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-bottom: 1px solid #f1f5f9 !important;">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-chart-line text-success me-2"></i>
                            <?php echo ($lang === 'en') ? 'Monthly Credit Sale vs Collection Trend' : 'মাসিক বাকিতে বিক্রয় ও কালেকশন ট্রেন্ড'; ?>
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge px-2 py-1 rounded-pill" style="background-color: #fff1f2; color: #be123c; border: 1px solid #fecdd3; font-size: 10.5px;">
                                <i class="fas fa-circle me-1" style="font-size: 7px; color: #be123c;"></i> <?php echo ($lang === 'en') ? 'Credit Sale' : 'বাকি বিক্রয়'; ?>
                            </span>
                            <span class="badge px-2 py-1 rounded-pill" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 10.5px;">
                                <i class="fas fa-circle me-1" style="font-size: 7px; color: #047857;"></i> <?php echo ($lang === 'en') ? 'Collection' : 'আদায়/পরিশোধ'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div style="height: 250px; position: relative;">
                            <canvas id="monthlyTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Report Data Table (Screen View - Hidden per user request) -->
        <div class="card shadow-sm border-0 d-none">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-list text-primary me-2"></i> 
                    <?php echo ($lang === 'en') ? 'Customer Due Statement Details' : 'কাস্টমার বকেয়া বিস্তারিত বিবরণী'; ?> 
                    <small class="text-muted ms-2">(<?php echo ($lang === 'en') ? 'Period' : 'সময়কাল'; ?>: <?php echo date('d-M-Y', strtotime($startDate)); ?> <?php echo ($lang === 'en') ? 'to' : 'হতে'; ?> <?php echo date('d-M-Y', strtotime($endDate)); ?>)</small>
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <input type="text" id="reportLiveSearch" class="form-control form-control-sm" style="width: 220px;" placeholder="<?php echo ($lang === 'en') ? '🔍 Search (Date, Name, etc.)...' : '🔍 খুঁজুন (তারিখ, নাম, ইত্যাদি)...'; ?>">
                    <span class="badge bg-primary fs-6">
                        <?php echo ($lang === 'en') ? 'Total Entries: ' : 'মোট এন্ট্রি: '; ?><?php echo count($allTransactions); ?>
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0" id="reportScreenTable">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center">SL</th>
                                <th><?php echo ($lang === 'en') ? 'Date' : 'তারিখ (Date)'; ?></th>
                                <th><?php echo ($lang === 'en') ? 'Person Name' : 'ব্যক্তির নাম (Customer/Person)'; ?></th>
                                <th><?php echo ($lang === 'en') ? 'Category' : 'ক্যাটাগরি'; ?></th>
                                <th><?php echo ($lang === 'en') ? 'Transaction Type' : 'লেনদেনের ধরন (Transaction Type)'; ?></th>
                                <th class="text-end"><?php echo ($lang === 'en') ? 'Credit Sale (৳)' : 'বাকিতে বিক্রয় (৳)'; ?></th>
                                <th class="text-end"><?php echo ($lang === 'en') ? 'Payment (৳)' : 'পরিশোধ/আদায় (৳)'; ?></th>
                                <th class="text-end"><?php echo ($lang === 'en') ? 'Net Due (৳)' : 'অবশিষ্ট বকেয়া (৳)'; ?></th>
                                <th><?php echo ($lang === 'en') ? 'Remarks' : 'মন্তব্য (Remarks)'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customerSummary) || empty($allTransactions)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <?php echo ($lang === 'en') ? 'No due or collection records found for the selected period.' : 'নির্বাচিত সময়ে কোনো বকেয়া বা কালেকশন রেকর্ড পাওয়া যায়নি।'; ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php 
                                $sl = 1;
                                foreach ($customerSummary as $cs):
                                    $cKey = $cs['key'];
                                    $pData = $personTxnsMap[$cKey] ?? null;
                                    if (!$pData || empty($pData['txns'])) continue;

                                    $cName = $pData['PersonName'] ?? '';
                                    $cMobile = $pData['Mobile'] ?? '';
                                    $cAddress = (!empty($pData['Address']) && $pData['Address'] !== '-') ? $pData['Address'] : '';
                                    $cType = $pData['CustomerType'] ?? 'Customer';
                                    $catBadge = ($cType === 'Customer') ? 'bg-primary' : (($cType === 'Employee') ? 'bg-info' : 'bg-warning text-dark');
                                    
                                    if ($lang === 'en') {
                                        $catLabel = $cType;
                                    } else {
                                        $catLabel = ($cType === 'Customer') ? 'গ্রাহক' : (($cType === 'Employee') ? 'কর্মচারী' : 'শেয়ারহোল্ডার');
                                    }
                            ?>
                            <!-- Customer Group Header Row -->
                            <tr class="table-secondary fw-bold border-top border-2 border-dark">
                                <td colspan="9" class="py-2 px-3 text-dark fs-6">
                                    <i class="fas fa-user-circle text-primary me-2"></i>
                                    <strong><?php echo ($lang === 'en') ? 'Customer / Person' : 'গ্রাহক / ব্যক্তি'; ?>:</strong> <?php echo htmlspecialchars($cName); ?>
                                    <span class="badge <?php echo $catBadge; ?> ms-2"><?php echo htmlspecialchars($catLabel); ?></span>
                                    <?php if (!empty($cMobile)): ?>
                                        <span class="small text-muted font-monospace ms-2"><i class="fas fa-phone-alt me-1"></i><?php echo htmlspecialchars($cMobile); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($cAddress)): ?>
                                        <span class="small text-muted ms-2"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($cAddress); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Customer Transaction Rows -->
                            <?php 
                                $custSaleSum = 0;
                                $custPaidSum = 0;
                                $custBal = 0;
                                foreach ($pData['txns'] as $tRow):
                                    $s = floatval($tRow['CreditSale']);
                                    $p = floatval($tRow['Payment']);
                                    $d = floatval($tRow['NetDue']);
                                    $custSaleSum += $s;
                                    $custPaidSum += $p;
                                    $custBal += $d;
                                    $displayRemark = !empty($tRow['Remarks']) ? $tRow['Remarks'] : $tRow['TxnSource'];
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $sl++; ?></td>
                                <td><?php echo htmlspecialchars($tRow['TxnDate']); ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($cName); ?></td>
                                <td><span class="badge <?php echo $catBadge; ?>"><?php echo htmlspecialchars($catLabel); ?></span></td>
                                <td><?php echo htmlspecialchars($tRow['TxnSource']); ?></td>
                                <td class="text-end font-monospace text-danger"><?php echo $s > 0 ? number_format($s, 2) : '-'; ?></td>
                                <td class="text-end font-monospace text-success"><?php echo $p > 0 ? number_format($p, 2) : '-'; ?></td>
                                <td class="text-end font-monospace fw-bold text-primary"><?php echo number_format($custBal, 2); ?> ৳</td>
                                <td class="small text-muted"><?php echo htmlspecialchars($displayRemark); ?></td>
                            </tr>
                            <?php endforeach; ?>

                            <!-- Customer Subtotal Row -->
                            <tr class="table-light fw-bold border-bottom border-2 border-secondary">
                                <td colspan="5" class="text-end py-2">
                                    <i class="fas fa-calculator text-muted me-1"></i>
                                    <?php echo ($lang === 'en') ? 'Subtotal for ' : 'সাব টোটাল - '; ?><?php echo htmlspecialchars($cName); ?>:
                                </td>
                                <td class="text-end text-danger font-monospace py-2"><?php echo number_format($custSaleSum, 2); ?> ৳</td>
                                <td class="text-end text-success font-monospace py-2"><?php echo number_format($custPaidSum, 2); ?> ৳</td>
                                <td class="text-end text-primary font-monospace fs-6 py-2"><?php echo number_format($custBal, 2); ?> ৳</td>
                                <td></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-dark fw-bold">
                            <tr>
                                <td colspan="5" class="text-end fs-6">
                                    <?php echo ($lang === 'en') ? 'Grand Total:' : 'সর্বমোট (Grand Total):'; ?>
                                </td>
                                <td class="text-end text-danger font-monospace fs-6"><?php echo number_format($totalCreditSale, 2); ?> ৳</td>
                                <td class="text-end text-success font-monospace fs-6"><?php echo number_format($totalPayment, 2); ?> ৳</td>
                                <td class="text-end text-warning font-monospace fs-6"><?php echo number_format($totalNetDue, 2); ?> ৳</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Detailed Customer Due Breakdown Modal -->
<div class="modal fade" id="personDetailModal" tabindex="-1" aria-labelledby="personDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                <div>
                    <h5 class="modal-title fw-bold" id="modalPersonName"></h5>
                    <div class="small opacity-75 mt-1" id="modalPersonMeta"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead style="background-color: #0f172a; color: #ffffff;">
                            <tr>
                                <th class="text-center py-2" style="width: 5%; color: #fff;">#</th>
                                <th class="py-2" style="width: 18%; color: #fff;"><?php echo ($lang === 'en') ? 'Date' : 'তারিখ'; ?></th>
                                <th class="py-2" style="width: 25%; color: #fff;"><?php echo ($lang === 'en') ? 'Transaction Type' : 'লেনদেনের ধরন'; ?></th>
                                <th class="text-end py-2" style="width: 17%; color: #fff;"><?php echo ($lang === 'en') ? 'Credit Sale (৳)' : 'বাকিতে বিক্রয় (৳)'; ?></th>
                                <th class="text-end py-2" style="width: 17%; color: #fff;"><?php echo ($lang === 'en') ? 'Payment (৳)' : 'পরিশোধ/আদায় (৳)'; ?></th>
                                <th class="text-end py-2" style="width: 18%; color: #fff;"><?php echo ($lang === 'en') ? 'Net Due (৳)' : 'অবশিষ্ট বকেয়া (৳)'; ?></th>
                            </tr>
                        </thead>
                        <tbody id="modalTxnTableBody">
                        </tbody>
                        <tfoot style="background-color: #f1f5f9;" class="fw-bold">
                            <tr>
                                <td colspan="3" class="text-end py-2"><?php echo ($lang === 'en') ? 'Total:' : 'সর্বমোট:'; ?></td>
                                <td class="text-end text-danger font-monospace py-2" id="modalTotalSale">0.00 ৳</td>
                                <td class="text-end text-success font-monospace py-2" id="modalTotalPaid">0.00 ৳</td>
                                <td class="text-end text-primary font-monospace py-2 fs-6" id="modalTotalDue">0.00 ৳</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 justify-content-between">
                <div class="small text-muted">
                    <i class="fas fa-info-circle me-1 text-primary"></i> <?php echo ($lang === 'en') ? 'Individual customer due statement' : 'গ্রাহকের বকেয়া লেনদেন বিস্তারিত বিবরণী'; ?>
                </div>
                <div>
                    <button type="button" onclick="printIndividualCustomerStatement()" class="btn btn-primary btn-sm px-3 fw-bold me-2 rounded-pill shadow-sm">
                        <i class="fas fa-print me-1"></i> <?php echo ($lang === 'en') ? 'Print Statement' : 'প্রিন্ট রিপোর্ট'; ?>
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm px-3 fw-bold rounded-pill" data-bs-dismiss="modal">
                        <?php echo ($lang === 'en') ? 'Close' : 'বন্ধ করুন'; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- INDIVIDUAL CUSTOMER DEDICATED PRINT TEMPLATE (MATCHING EXACT REFERENCE DESIGN) -->
<div id="singleCustomerPrintArea" class="d-none">
    <!-- Header: Company Name, Address, Underlined Title -->
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="font-size: 28px; font-weight: bold; margin: 0; padding: 0; font-family: 'Times New Roman', serif;" id="singlePrintCompName">
            <?php echo htmlspecialchars($compName); ?>
        </h1>
        <div style="font-size: 15px; font-weight: normal; margin-top: 3px; font-family: 'Times New Roman', serif;" id="singlePrintCompAddress">
            <?php echo htmlspecialchars($compAddress); ?>
        </div>
        <div style="margin-top: 10px;">
            <span style="font-size: 19px; font-weight: bold; text-decoration: underline; border-bottom: 1.5px solid #000; padding-bottom: 2px; font-family: 'Times New Roman', serif;" id="singlePrintReportTitle">
                Due Statement
            </span>
        </div>
    </div>

    <!-- Customer Metadata Block -->
    <div style="margin-bottom: 18px; font-size: 15px; font-weight: bold; font-family: 'Times New Roman', serif;">
        <table style="width: 100%; border: none; border-collapse: collapse;">
            <tr>
                <td style="width: 110px; vertical-align: bottom; padding-bottom: 4px;">
                    <?php echo ($lang === 'en') ? 'Customer Name :' : 'গ্রাহকের নাম :'; ?>
                </td>
                <td style="font-size: 16px; border-bottom: 1px solid #000; padding-bottom: 2px; vertical-align: bottom;" id="singlePrintCustomerName">
                    -
                </td>
            </tr>
            <tr>
                <td style="vertical-align: bottom; padding-top: 8px; padding-bottom: 4px;">
                    <?php echo ($lang === 'en') ? 'Address :' : 'ঠিকানা :'; ?>
                </td>
                <td style="border-bottom: 1px solid #000; padding-top: 8px; padding-bottom: 2px; vertical-align: bottom; font-weight: normal;" id="singlePrintCustomerAddress">
                    -
                </td>
            </tr>
        </table>
    </div>

    <!-- Main Print Data Table (Matching Reference Table Design) -->
    <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #000; font-size: 14px; font-family: 'Times New Roman', serif;">
        <thead>
            <tr style="border-bottom: 1.5px solid #000; background-color: #f8f9fa;">
                <th style="border: 1px solid #000; padding: 6px 10px; text-align: center; font-weight: bold; width: 20%; font-size: 15px;">Date</th>
                <th style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-weight: bold; width: 22%; font-size: 15px;">LPG Purchase</th>
                <th style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-weight: bold; width: 20%; font-size: 15px;">Payment</th>
                <th style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-weight: bold; width: 22%; font-size: 15px;">Balance</th>
                <th style="border: 1px solid #000; padding: 6px 10px; text-align: center; font-weight: bold; width: 16%; font-size: 15px;">Remark</th>
            </tr>
        </thead>
        <tbody id="singlePrintTableBody">
            <!-- Row 1: Opening Balance -->
            <tr>
                <td style="border: 1px solid #000; padding: 5px 10px; text-align: left;">Opening Balance</td>
                <td style="border: 1px solid #000; padding: 5px 10px; text-align: right;">-</td>
                <td style="border: 1px solid #000; padding: 5px 10px; text-align: right;">-</td>
                <td style="border: 1px solid #000; padding: 5px 10px; text-align: right;">-</td>
                <td style="border: 1px solid #000; padding: 5px 10px; text-align: center;">-</td>
            </tr>
        </tbody>
        <tfoot>
            <tr style="border-top: 1.5px solid #000; font-weight: bold;">
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-size: 16px;">Total</td>
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-size: 16px;" id="singlePrintTotalSale">0.00</td>
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-size: 16px;" id="singlePrintTotalPaid">0.00</td>
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-size: 16px;" id="singlePrintTotalBalance">0.00</td>
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: center;">-</td>
            </tr>
        </tfoot>
    </table>

    <!-- Page Footer Line -->
    <div style="margin-top: 25px; font-size: 11px; color: #333; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding-top: 5px; font-family: 'Times New Roman', serif; page-break-inside: avoid;">
        <div id="singlePrintFooterCompName"><?php echo htmlspecialchars($compName); ?></div>
        <div id="singlePrintFooterPersonName">-</div>
        <div>Page: 1</div>
    </div>
</div>

<!-- PRINT-ONLY DEDICATED REPORT TEMPLATE (ENHANCED PREMIUM DESIGN WITH SIGNATURE BLOCK & MOBILE NO) -->
<div id="printReportArea" class="d-none d-print-block">
    <!-- Header: Company Name, Address, Mobile, Underlined Title -->
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="font-size: 28px; font-weight: bold; margin: 0; padding: 0; font-family: 'Times New Roman', serif;">
            <?php echo htmlspecialchars($compName); ?>
        </h1>
        <div style="font-size: 15px; font-weight: normal; margin-top: 3px;">
            <?php echo htmlspecialchars($compAddress); ?>
            <?php if (!empty($compMobile)): ?>
                | <span style="font-weight: bold;"><?php echo ($lang === 'en') ? 'Mobile:' : 'মোবাইল:'; ?></span> <?php echo htmlspecialchars($compMobile); ?>
            <?php endif; ?>
        </div>
        <div style="margin-top: 10px;">
            <span style="font-size: 19px; font-weight: bold; text-decoration: underline; border-bottom: 1.5px solid #000; padding-bottom: 2px;">
                <?php echo ($lang === 'en') ? 'Due Statement' : 'বকেয়া তালিকা'; ?>
            </span>
        </div>
    </div>

    <!-- Customer Metadata Block -->
    <div style="margin-bottom: 18px; font-size: 15px; font-weight: bold;">
        <table style="width: 100%; border: none; border-collapse: collapse;">
            <tr>
                <td style="width: 130px; vertical-align: bottom; padding-bottom: 4px;">
                    <?php echo ($lang === 'en') ? 'Customer Name :' : 'গ্রাহকের নাম :'; ?>
                </td>
                <td style="font-size: 16px; border-bottom: 1px solid #000; padding-bottom: 2px; vertical-align: bottom;">
                    <?php echo htmlspecialchars($selectedPersonName); ?>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: bottom; padding-top: 8px; padding-bottom: 4px;">
                    <?php echo ($lang === 'en') ? 'Address :' : 'ঠিকানা :'; ?>
                </td>
                <td style="border-bottom: 1px solid #000; padding-top: 8px; padding-bottom: 2px; vertical-align: bottom; font-weight: normal;">
                    <?php echo htmlspecialchars($selectedPersonAddress); ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Main Print Data Table -->
    <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #000; font-size: 14px; font-family: 'Times New Roman', serif;">
        <thead>
            <tr style="border-bottom: 1.5px solid #000; background-color: #f8f9fa;">
                <th style="border: 1px solid #000; padding: 6px 10px; text-align: center; font-weight: bold; width: 18%; font-size: 15px;">
                    <?php echo ($lang === 'en') ? 'Date' : 'Date'; ?>
                </th>
                <th style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-weight: bold; width: 20%; font-size: 15px;">
                    <?php echo ($lang === 'en') ? 'LPG Purchase' : 'LPG Purchase'; ?>
                </th>
                <th style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-weight: bold; width: 20%; font-size: 15px;">
                    <?php echo ($lang === 'en') ? 'Payment' : 'Payment'; ?>
                </th>
                <th style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-weight: bold; width: 22%; font-size: 15px;">
                    <?php echo ($lang === 'en') ? 'Balance' : 'Balance'; ?>
                </th>
                <th style="border: 1px solid #000; padding: 6px 10px; text-align: center; font-weight: bold; width: 20%; font-size: 15px;">
                    <?php echo ($lang === 'en') ? 'Remark' : 'Remark'; ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customerSummary) || empty($allTransactions)): ?>
            <tr>
                <td colspan="5" style="border: 1px solid #000; padding: 10px; text-align: center; color: #666;">
                    <?php echo ($lang === 'en') ? 'No due or collection records found for the selected period.' : 'নির্বাচিত সময়ে কোনো বকেয়া বা কালেকশন রেকর্ড পাওয়া যায়নি।'; ?>
                </td>
            </tr>
            <?php else: ?>
            <?php 
                foreach ($customerSummary as $cs):
                    $cKey = $cs['key'];
                    $pData = $personTxnsMap[$cKey] ?? null;
                    if (!$pData || empty($pData['txns'])) continue;

                    $cName = $pData['PersonName'] ?? '';
                    $cMobile = $pData['Mobile'] ?? '';
                    $cAddress = (!empty($pData['Address']) && $pData['Address'] !== '-') ? $pData['Address'] : '';
                    $cType = $pData['CustomerType'] ?? 'Customer';
                    
                    if ($lang === 'en') {
                        $catLabel = $cType;
                    } else {
                        $catLabel = ($cType === 'Customer') ? 'গ্রাহক' : (($cType === 'Employee') ? 'কর্মচারী' : 'শেয়ারহোল্ডার');
                    }
            ?>
            <!-- Customer Group Header Row -->
            <tr style="background-color: #e2e8f0; font-weight: bold; border-top: 2px solid #000; border-bottom: 1.5px solid #000; page-break-inside: avoid;">
                <td colspan="5" style="border: 1px solid #000; padding: 6px 10px; font-size: 14px; background-color: #e2e8f0; color: #0f172a;">
                    <strong><?php echo ($lang === 'en') ? 'Customer / Person' : 'গ্রাহক / ব্যক্তি'; ?>:</strong> <?php echo htmlspecialchars($cName); ?>
                    <span style="font-size: 12px; font-weight: normal; margin-left: 8px; color: #334155;">
                        (<?php echo htmlspecialchars($catLabel); ?><?php echo !empty($cMobile) ? ' | ' . (($lang === 'en') ? 'Mobile: ' : 'মোবাইল: ') . htmlspecialchars($cMobile) : ''; ?><?php echo !empty($cAddress) ? ' | ' . (($lang === 'en') ? 'Address: ' : 'ঠিকানা: ') . htmlspecialchars($cAddress) : ''; ?>)
                    </span>
                </td>
            </tr>

            <!-- Customer Transaction Rows -->
            <?php 
                $custSaleSum = 0; 
                $custPaidSum = 0; 
                $custBal = 0;
                foreach ($pData['txns'] as $tRow):
                    $s = floatval($tRow['CreditSale']);
                    $p = floatval($tRow['Payment']);
                    $d = floatval($tRow['NetDue']);
                    $custSaleSum += $s;
                    $custPaidSum += $p;
                    $custBal += $d;

                    $displayRemark = !empty($tRow['Remarks']) ? $tRow['Remarks'] : $tRow['TxnSource'];
            ?>
            <tr style="page-break-inside: avoid;">
                <td style="border: 1px solid #000; padding: 5px 10px; text-align: center;"><?php echo htmlspecialchars($tRow['TxnDate']); ?></td>
                <td style="border: 1px solid #000; padding: 5px 10px; text-align: right; font-weight: bold;">
                    <?php echo $s > 0 ? number_format($s, 2) : '-'; ?>
                </td>
                <td style="border: 1px solid #000; padding: 5px 10px; text-align: right;">
                    <?php echo $p > 0 ? number_format($p, 2) : '-'; ?>
                </td>
                <td style="border: 1px solid #000; padding: 5px 10px; text-align: right; font-weight: bold;">
                    <?php echo number_format($custBal, 2); ?>
                </td>
                <td style="border: 1px solid #000; padding: 4px 6px; text-align: center; font-size: 11px; line-height: 1.2;">
                    <?php echo htmlspecialchars($displayRemark); ?>
                </td>
            </tr>
            <?php endforeach; ?>

            <!-- Customer Subtotal Row -->
            <tr style="background-color: #f1f5f9; font-weight: bold; border-top: 1px solid #000; border-bottom: 2px solid #000; page-break-inside: avoid;">
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-size: 13px; background-color: #f1f5f9;">
                    <?php echo ($lang === 'en') ? 'Subtotal for ' : 'সাব টোটাল - '; ?><?php echo htmlspecialchars($cName); ?>:
                </td>
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-size: 13px; font-weight: bold; background-color: #f1f5f9;">
                    <?php echo number_format($custSaleSum, 2); ?>
                </td>
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-size: 13px; font-weight: bold; background-color: #f1f5f9;">
                    <?php echo number_format($custPaidSum, 2); ?>
                </td>
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-size: 13px; font-weight: bold; background-color: #f1f5f9;">
                    <?php echo number_format($custBal, 2); ?>
                </td>
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: center; background-color: #f1f5f9;">-</td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #000; background-color: #e2e8f0; font-weight: bold; page-break-inside: avoid;">
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-size: 15px;">
                    <?php echo ($lang === 'en') ? 'Grand Total' : 'সর্বমোট (Grand Total)'; ?>
                </td>
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-size: 15px;"><?php echo number_format($totalCreditSale, 2); ?></td>
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-size: 15px;"><?php echo number_format($totalPayment, 2); ?></td>
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: right; font-size: 15px;"><?php echo number_format($totalNetDue, 2); ?></td>
                <td style="border: 1px solid #000; padding: 6px 10px; text-align: center;">-</td>
            </tr>
        </tfoot>
    </table>

    <!-- Official Signature Block -->
    <div style="margin-top: 45px; width: 100%; display: flex; justify-content: space-between; font-family: 'Times New Roman', serif; font-size: 14px; page-break-inside: avoid;">
        <div style="text-align: center; width: 200px;">
            <div style="border-top: 1px dashed #000; padding-top: 5px; font-weight: bold;">
                <?php echo ($lang === 'en') ? 'Prepared By Signature' : 'প্রস্তুতকারকের স্বাক্ষর'; ?>
            </div>
        </div>
        <div style="text-align: center; width: 200px;">
            <div style="border-top: 1px dashed #000; padding-top: 5px; font-weight: bold;">
                <?php echo ($lang === 'en') ? 'Cashier Signature' : 'ক্যাশিয়ারের স্বাক্ষর'; ?>
            </div>
        </div>
        <div style="text-align: center; width: 200px;">
            <div style="border-top: 1px dashed #000; padding-top: 5px; font-weight: bold;">
                <?php echo ($lang === 'en') ? 'Approved By Signature' : 'অনুমোদনকারীর স্বাক্ষর'; ?>
            </div>
        </div>
    </div>

    <!-- Page Footer Bar -->
    <div style="margin-top: 25px; font-size: 11px; color: #555; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding-top: 5px; font-family: 'Times New Roman', serif; page-break-inside: avoid;">
        <div><?php echo htmlspecialchars($compName); ?></div>
        <div>
            <?php echo htmlspecialchars($selectedPersonName); ?> | 
            <?php echo ($lang === 'en') ? 'Printed Date & Time: ' : 'প্রিন্টের তারিখ ও সময়: '; ?><?php echo date('d-M-Y h:i A'); ?>
        </div>
        <div>Page: 1</div>
    </div>
</div>

<script>
const personTxnsData = <?php echo json_encode($personTxnsMap); ?>;
let currentActiveModalKey = null;

function openPersonDetailModal(key) {
    currentActiveModalKey = key;
    const pData = personTxnsData[key];
    if (!pData) return;

    const modalName = document.getElementById('modalPersonName');
    const modalMeta = document.getElementById('modalPersonMeta');
    const tbody = document.getElementById('modalTxnTableBody');
    const totalSaleEl = document.getElementById('modalTotalSale');
    const totalPaidEl = document.getElementById('modalTotalPaid');
    const totalDueEl = document.getElementById('modalTotalDue');

    if (modalName) modalName.textContent = pData.PersonName;
    if (modalMeta) {
        let catText = (pData.CustomerType === 'Customer') ? '<?php echo ($lang === "en") ? "Customer" : "গ্রাহক"; ?>' : 
                      ((pData.CustomerType === 'Employee') ? '<?php echo ($lang === "en") ? "Employee" : "কর্মচারী"; ?>' : '<?php echo ($lang === "en") ? "Shareholder" : "শেয়ারহোল্ডার"; ?>');
        modalMeta.innerHTML = `<span class="badge bg-primary me-2">${catText}</span>` + (pData.Mobile ? `📞 ${pData.Mobile}` : '');
    }

    let rowsHtml = '';
    let totSale = 0, totPaid = 0, totDue = 0;

    if (!pData.txns || pData.txns.length === 0) {
        rowsHtml = `<tr><td colspan="6" class="text-center text-muted py-3"><?php echo ($lang === "en") ? "No transactions found." : "কোনো লেনদেন রেকর্ড পাওয়া যায়নি।"; ?></td></tr>`;
    } else {
        pData.txns.forEach((t, idx) => {
            totSale += t.CreditSale;
            totPaid += t.Payment;
            totDue += t.NetDue;

            const saleText = t.CreditSale > 0 ? t.CreditSale.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ৳' : '-';
            const paidText = t.Payment > 0 ? t.Payment.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ৳' : '-';
            const dueText = t.NetDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ৳';

            rowsHtml += `<tr>
                <td class="text-center">${idx + 1}</td>
                <td>${t.TxnDate}</td>
                <td><span class="fw-bold">${t.TxnSource}</span>${t.Remarks ? `<br><small class="text-muted">${t.Remarks}</small>` : ''}</td>
                <td class="text-end font-monospace text-danger">${saleText}</td>
                <td class="text-end font-monospace text-success">${paidText}</td>
                <td class="text-end font-monospace fw-bold text-primary">${dueText}</td>
            </tr>`;
        });
    }

    if (tbody) tbody.innerHTML = rowsHtml;
    if (totalSaleEl) totalSaleEl.textContent = totSale.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ৳';
    if (totalPaidEl) totalPaidEl.textContent = totPaid.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ৳';
    if (totalDueEl) totalDueEl.textContent = totDue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ৳';

    const modalEl = document.getElementById('personDetailModal');
    if (modalEl) {
        let bsModal = bootstrap.Modal.getInstance(modalEl);
        if (!bsModal) {
            bsModal = new bootstrap.Modal(modalEl);
        }
        bsModal.show();
    }
}

function triggerReportPrint(mode) {
    document.body.classList.remove('print-single-customer', 'print-mode-a4', 'print-mode-mobile');
    
    if (mode === 'mobile_view') {
        document.body.classList.add('print-mode-mobile');
    } else {
        document.body.classList.add('print-mode-a4');
    }

    if (typeof Chart !== 'undefined' && Chart.instances) {
        Object.keys(Chart.instances).forEach(function(key) {
            try { Chart.instances[key].resize(); } catch(e){}
        });
    }

    setTimeout(function() {
        window.print();
    }, 150);
}

function printIndividualCustomerStatement() {
    if (!currentActiveModalKey) return;
    const printUrl = `customer_due.php?action=print_individual&customer_key=${encodeURIComponent(currentActiveModalKey)}&lang=<?php echo urlencode($lang); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>&entity_type=<?php echo urlencode($entityType); ?>&customer_id=<?php echo urlencode($rawPerson); ?>`;
    window.open(printUrl, '_blank');
}

window.addEventListener('afterprint', function() {
    document.body.classList.remove('print-single-customer', 'print-mode-a4', 'print-mode-mobile');
});

function filterTableByCustomer(name) {
    const searchInput = document.getElementById('summarySearchInput');
    if (searchInput) {
        searchInput.value = name;
        searchInput.dispatchEvent(new Event('keyup'));
        const table = document.getElementById('customerSummaryTable');
        if (table) {
            table.scrollIntoView({ behavior: 'smooth' });
        }
    }
}

document.addEventListener("DOMContentLoaded", function() {
    // 1. Instant Live Search Filter for Main Table (if visible)
    const searchInput = document.getElementById('reportLiveSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#reportScreenTable tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }

    // 2. Top 10 Debtors Horizontal Bar Chart Initialization
    const topCtx = document.getElementById('topDebtorsChart');
    if (topCtx && typeof Chart !== 'undefined') {
        const topLabels = <?php echo json_encode(array_column($topDebtors, 'name')); ?>;
        const topData = <?php echo json_encode(array_column($topDebtors, 'due')); ?>;

        // Dynamic Multi-Color Risk Gradients for Bars (Matching Corporate Risk Theme)
        const barColors = topData.map((val, idx) => {
            if (idx === 0) return 'rgba(190, 18, 60, 0.9)';   // Top 1: Crimson Red (#be123c)
            if (idx === 1) return 'rgba(217, 119, 6, 0.9)';   // Top 2: Amber (#d97706)
            if (idx === 2) return 'rgba(202, 138, 4, 0.9)';   // Top 3: Golden (#ca8a04)
            return 'rgba(37, 99, 235, 0.85)';                 // Top 4-10: Royal Blue (#2563eb)
        });

        const borderColors = topData.map((val, idx) => {
            if (idx === 0) return '#9f1239';
            if (idx === 1) return '#b45309';
            if (idx === 2) return '#a16207';
            return '#1d4ed8';
        });

        // Custom Plugin to draw exact Taka amounts right beside each horizontal bar
        const top10ValuePlugin = {
            id: 'top10Values',
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                const dataset = chart.data.datasets[0];
                const meta = chart.getDatasetMeta(0);
                
                meta.data.forEach((bar, index) => {
                    const val = dataset.data[index] || 0;
                    if (val > 0) {
                        const text = val.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ৳';
                        ctx.save();
                        ctx.fillStyle = '#0f172a';
                        ctx.font = '600 11px system-ui, -apple-system, sans-serif';
                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(text, bar.x + 8, bar.y);
                        ctx.restore();
                    }
                });
            }
        };

        new Chart(topCtx, {
            type: 'bar',
            data: {
                labels: topLabels,
                datasets: [{
                    label: '<?php echo ($lang === "en") ? "Net Due (৳)" : "অবশিষ্ট বকেয়া (৳)"; ?>',
                    data: topData,
                    backgroundColor: barColors,
                    borderColor: borderColors,
                    borderWidth: 1.5,
                    borderRadius: 6
                }]
            },
            plugins: [top10ValuePlugin],
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: { right: 90 } // Extra right padding to fit value labels gracefully
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' ' + (context.raw || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ৳';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            callback: function(val) { return val.toLocaleString() + ' ৳'; },
                            font: { size: 10.5 }
                        }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11.5, weight: '600' },
                            color: '#0f172a'
                        }
                    }
                }
            }
        });
    }

    // 3. Category Distribution Donut Chart Initialization
    const donutCtx = document.getElementById('categoryDonutChart');
    if (donutCtx && typeof Chart !== 'undefined') {
        const catLabels = [
            '<?php echo ($lang === "en") ? "Customer" : "গ্রাহক"; ?>',
            '<?php echo ($lang === "en") ? "Shareholder" : "শেয়ারহোল্ডার"; ?>',
            '<?php echo ($lang === "en") ? "Employee" : "কর্মচারী"; ?>'
        ];
        const catData = [
            <?php echo round($catDues['Customer'], 2); ?>,
            <?php echo round($catDues['Shareholder'], 2); ?>,
            <?php echo round($catDues['Employee'], 2); ?>
        ];

        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catData,
                    backgroundColor: ['#2563eb', '#d97706', '#4f46e5'],
                    hoverBackgroundColor: ['#1d4ed8', '#b45309', '#3730a3'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const val = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : '0.0';
                                return ' ' + context.label + ': ' + val.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ৳ (' + pct + '%)';
                            }
                        }
                    }
                },
                cutout: '66%'
            }
        });
    }

    // 4. Monthly Credit Sales vs Payment Trend Line Chart Initialization
    const trendCtx = document.getElementById('monthlyTrendChart');
    if (trendCtx && typeof Chart !== 'undefined') {
        const trendLabels = <?php echo json_encode($trendMonths); ?>;
        const trendSales  = <?php echo json_encode($trendSales); ?>;
        const trendPaids  = <?php echo json_encode($trendPaids); ?>;

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [
                    {
                        label: '<?php echo ($lang === "en") ? "Credit Sale (৳)" : "বাকিতে বিক্রয় (৳)"; ?>',
                        data: trendSales,
                        borderColor: '#be123c',
                        backgroundColor: 'rgba(190, 18, 60, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#be123c',
                        pointBorderColor: '#ffffff',
                        pointHoverRadius: 6,
                        pointRadius: 4,
                        borderWidth: 2.5
                    },
                    {
                        label: '<?php echo ($lang === "en") ? "Collection (৳)" : "আদায়/পরিশোধ (৳)"; ?>',
                        data: trendPaids,
                        borderColor: '#047857',
                        backgroundColor: 'rgba(4, 120, 87, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#047857',
                        pointBorderColor: '#ffffff',
                        pointHoverRadius: 6,
                        pointRadius: 4,
                        borderWidth: 2.5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            font: { size: 11, weight: '600' }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                const val = context.raw || 0;
                                return ' ' + context.dataset.label + ': ' + val.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ৳';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { size: 10.5 } }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            callback: function(val) { return val.toLocaleString() + ' ৳'; },
                            font: { size: 10.5 }
                        }
                    }
                }
            }
        });
    }

    // 5. Category Filter Tabs & Live Instant Search for Customer Summary Table
    const catTabs = document.querySelectorAll('.cat-tab-btn');
    const summarySearch = document.getElementById('summarySearchInput');
    const summaryRows = document.querySelectorAll('#customerSummaryTable tbody tr.summary-row');
    const visibleCountBadge = document.getElementById('visiblePersonCount');

    let currentCat = 'all';

    function filterSummaryTable() {
        const searchTerm = summarySearch ? summarySearch.value.toLowerCase().trim() : '';
        let visibleCount = 0;

        summaryRows.forEach(row => {
            const rowCat = row.getAttribute('data-cat') || 'Customer';
            const rowText = row.innerText.toLowerCase();

            const matchesCat = (currentCat === 'all' || rowCat === currentCat);
            const matchesSearch = (!searchTerm || rowText.includes(searchTerm));

            if (matchesCat && matchesSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCountBadge) {
            visibleCountBadge.textContent = visibleCount;
        }
    }

    catTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            catTabs.forEach(t => {
                t.classList.remove('btn-primary', 'btn-warning', 'btn-info', 'active');
                t.classList.add('btn-outline-primary', 'bg-white');
            });

            this.classList.remove('btn-outline-primary', 'bg-white');
            this.classList.add('btn-primary', 'active');

            currentCat = this.getAttribute('data-cat') || 'all';
            filterSummaryTable();
        });
    });

    if (summarySearch) {
        summarySearch.addEventListener('keyup', filterSummaryTable);
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
