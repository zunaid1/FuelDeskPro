<?php
/**
 * FuelDeskPro - Common Helper Functions
 * 
 * @package FuelDeskPro
 */

// Set default system timezone
date_default_timezone_set('Asia/Dhaka');

/**
 * Get global database object
 * 
 * @return Query
 */
function db()
{
    global $objQuery;
    return $objQuery;
}

/**
 * Get company profile info
 * 
 * @param string $key Optional specific field to return
 * @return mixed
 */
function getCompanyInfo($key = null)
{
    $sql = "SELECT * FROM cfg_companyprofile WHERE CompanyID = 1 AND IsActive = 1 AND IsDeleted = 0";
    $data = db()->index($sql);
    
    if (!empty($data)) {
        $company = $data[0];
        if (empty($company->CompanyNameBN)) {
            db()->inUpDel("UPDATE cfg_companyprofile SET CompanyNameBN = ? WHERE CompanyID = 1", ['সাঙ্গু এল.পি.জি ফিলিং ষ্টেশন']);
            $company->CompanyNameBN = 'সাঙ্গু এল.পি.জি ফিলিং ষ্টেশন';
        }
        if ($key && isset($company->$key)) {
            return $company->$key;
        }
        return $company;
    }
    return null;
}

/**
 * Get formatted company profile info array based on selected language
 * 
 * @param string $lang 'bn' or 'en'
 * @return array
 */
function getFormattedCompanyInfo($lang = 'bn')
{
    $company = getCompanyInfo();
    if (!$company) {
        return [
            'name'    => ($lang === 'en' ? 'Shangu LPG Filling Station' : 'সাঙ্গু এল.পি.জি ফিলিং ষ্টেশন'),
            'address' => ($lang === 'en' ? 'Amilaish, Satkania, Chattogram.' : 'আমিলাইশ, সাতকানিয়া, চট্টগ্রাম।'),
            'mobile'  => '01819800600',
            'email'   => 'sfs@gmail.com',
            'raw'     => null
        ];
    }

    if ($lang === 'en') {
        $name    = !empty($company->CompanyName) ? $company->CompanyName : 'Shangu LPG Filling Station';
        $address = !empty($company->AddressEN) ? $company->AddressEN : (!empty($company->Address) ? $company->Address : 'Amilaish, Satkania, Chattogram.');
        $mobile  = $company->MobileNo ?? ($company->PhoneNo ?? '');
        $email   = $company->Email ?? '';
    } else {
        $name    = !empty($company->CompanyNameBN) ? $company->CompanyNameBN : 'সাঙ্গু এল.পি.জি ফিলিং ষ্টেশন';
        $address = !empty($company->AddressBN) ? $company->AddressBN : (!empty($company->Address) ? $company->Address : 'আমিলাইশ, সাতকানিয়া, চট্টগ্রাম।');
        
        $rawMobile = $company->MobileNo ?? ($company->PhoneNo ?? '');
        $mobile    = strtr((string)$rawMobile, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']);
        $email     = $company->Email ?? '';
    }

    return [
        'name'    => $name,
        'address' => $address,
        'mobile'  => $mobile,
        'email'   => $email,
        'raw'     => $company
    ];
}

/**
 * Get system setting value
 * 
 * @param string $key Setting key
 * @return string|null
 */
function getSetting($key)
{
    $sql = "SELECT SettingValue FROM cfg_systemsetting WHERE SettingKey = ? AND IsActive = 1 AND IsDeleted = 0";
    $data = db()->index($sql, [$key]);
    
    if (!empty($data)) {
        return $data[0]->SettingValue;
    }
    return null;
}

/**
 * Generate a unique code with prefix
 * 
 * @param string $prefix Prefix for the code
 * @param string $table  Table name
 * @param string $column Column name to check
 * @return string
 */
function generateCode($prefix, $table, $column = null)
{
    $col = $column ?: $prefix . 'ID';
    $sql = "SELECT MAX(CAST(SUBSTRING({$col}, LENGTH('{$prefix}') + 1) AS UNSIGNED)) AS max_code FROM {$table}";
    $data = db()->index($sql);
    
    $nextId = 1;
    if (!empty($data) && $data[0]->max_code) {
        $nextId = $data[0]->max_code + 1;
    }
    
    return $prefix . $nextId;
}

/**
 * Format date to display format
 * 
 * @param string $date     Date string
 * @param string $format   Output format (default: d-m-Y)
 * @return string
 */
function formatDate($date, $format = 'd-m-Y')
{
    if (empty($date) || $date == '0000-00-00') {
        return '';
    }
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Format date for database storage
 * 
 * @param string $date Date string in d-m-Y format
 * @return string
 */
function dbDate($date)
{
    if (empty($date)) {
        return null;
    }
    $d = DateTime::createFromFormat('d-m-Y', $date);
    if ($d) {
        return $d->format('Y-m-d');
    }
    // Try direct format
    $d = date('Y-m-d', strtotime($date));
    return $d ?: null;
}

/**
 * Format currency amount
 * 
 * @param float  $amount   Amount to format
 * @param int    $decimals Decimal places
 * @return string
 */
function formatCurrency($amount, $decimals = 2)
{
    return number_format((float)$amount, $decimals, '.', ',');
}

/**
 * Get current date for database
 * 
 * @return string
 */
function today()
{
    return date('Y-m-d');
}

/**
 * Get current time
 * 
 * @return string
 */
function now()
{
    return date('H:i:s');
}

/**
 * Get current datetime
 * 
 * @return string
 */
function datetime()
{
    return date('Y-m-d H:i:s');
}

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Get current logged in user ID
 * 
 * @return int|null
 */
function getUserId()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user name
 * 
 * @return string|null
 */
function getUserName()
{
    return $_SESSION['user_name'] ?? null;
}

/**
 * Get current user role
 * 
 * @return string|null
 */
function getUserRole()
{
    return $_SESSION['user_role'] ?? null;
}

/**
 * Generate a JSON response for AJAX calls
 * 
 * @param bool   $success Whether the operation was successful
 * @param string $message Response message
 * @param array  $extra   Extra data to include
 */
function jsonResponse($success, $message = '', $extra = [])
{
    header('Content-Type: application/json');
    $response = array_merge([
        'success' => $success,
        'message' => $message
    ], $extra);
    echo json_encode($response);
    exit;
}

/**
 * Generate slug from string
 * 
 * @param string $string Input string
 * @return string
 */
function slugify($string)
{
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
}

/**
 * Sanitize input string
 * 
 * @param string $input Input to sanitize
 * @return string
 */
function sanitize($input)
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if request is AJAX
 * 
 * @return bool
 */
function isAjax()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Get active navigation menu item
 * 
 * @param string $page Current page name
 * @param string $menu Menu item to check
 * @return string 'active' or ''
 */
function isActive($page, $menu)
{
    return ($page == $menu) ? 'active' : '';
}

/**
 * Get the current page name from URL
 * 
 * @return string
 */
function getPageName()
{
    $page = basename($_SERVER['PHP_SELF']);
    return pathinfo($page, PATHINFO_FILENAME);
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 */
function redirect($url)
{
    header("Location: {$url}");
    exit;
}

function currentLang()
{
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'bn'], true)) {
        $_SESSION['lang'] = $_GET['lang'];
    }

    return $_SESSION['lang'] ?? 'en';
}

function t($key)
{
    $translations = [
        'en' => [
            'Dashboard' => 'Dashboard',
            'Master Data' => 'Master Data',
            'Operations' => 'Operations',
            'Reports' => 'Reports',
            'Settings' => 'Settings',
            'Fuel Station Management' => 'Fuel Station Management',
            'Logout' => 'Logout',
            'Profile' => 'Profile',
            'Nozzle Meter Readings' => 'Nozzle Meter Readings',
            'Nozzle Readings' => 'Nozzle Readings',
            'Customer Dues' => 'Customer Dues',
            'Due Collections' => 'Due Collections',
            'Cash Collections' => 'Cash Collections',
            'Others Collections' => 'Others Collections',
            'Expense' => 'Expense',
            'Fuel Purchase' => 'Fuel Purchase',
            'Fuel Price Adjustment' => 'Fuel Price Adjustment',
            'Supplier Payment' => 'Supplier Payment',
            'Tank Readings' => 'Tank Readings',
            'Today Sales' => 'Today Sales',
            'Today Collections' => 'Today Collections',
            'Today Expense' => 'Today Expense',
            'Customer Due' => 'Customer Due',
            'Supplier Paid' => 'Supplier Paid',
            'Fuel Purchased' => 'Fuel Purchased',
            'Shift' => 'Shift',
            'Fuel Type' => 'Fuel Type',
            'Tank Group' => 'Tank Group',
            'Tanks' => 'Tanks',
            'Tank' => 'Tank',
            'Dispenser' => 'Dispenser',
            'Nozzles' => 'Nozzles',
            'Nozzle' => 'Nozzle',
            'Supplier' => 'Supplier',
            'Shareholders' => 'Shareholders',
            'Shareholder' => 'Shareholder',
            'Employee' => 'Employee',
            'Customer' => 'Customer',
            'Particular Group' => 'Particular Group',
            'Particular' => 'Particular',
            'Shift Management' => 'Shift Management',
            'Fuel Type Management' => 'Fuel Type Management',
            'Tank Group Management' => 'Tank Group Management',
            'Tanks Management' => 'Tanks Management',
            'Dispenser Management' => 'Dispenser Management',
            'Nozzle Management' => 'Nozzle Management',
            'Supplier Management' => 'Supplier Management',
            'Shareholder Management' => 'Shareholder Management',
            'Employee Management' => 'Employee Management',
            'Customer Management' => 'Customer Management',
            'Particular Group Management' => 'Particular Group Management',
            'Particular Group (Expense Category) Management' => 'Particular Group (Expense Category) Management',
            'Particular Group / Expense Category' => 'Particular Group / Expense Category',
            'Expense Particular Management' => 'Expense Particular Management',
            'System Settings' => 'System Settings',
            'Daily Statement Closing Status' => 'Daily Statement Closing Status',
            'Stock Summary' => 'Stock Summary',
            'Stock Adjustment' => 'Stock Adjustment',
            'Opening Stock' => 'Opening Stock',
            'Stock IN (Purchases)' => 'Stock IN (Purchases)',
            'Sales (Stock Out)' => 'Sales (Stock Out)',
            'Stock Add (Adjustment)' => 'Stock Add (Adjustment)',
            'Stock Deduct (Adjustment)' => 'Stock Deduct (Adjustment)',
            'Current Stock' => 'Current Stock',
        ],
        'bn' => [
            'Dashboard' => 'ড্যাশবোর্ড',
            'Master Data' => 'মাস্টার ডাটা',
            'Operations' => 'অপারেশন',
            'Reports' => 'রিপোর্ট',
            'Settings' => 'সেটিংস',
            'System Settings' => 'সিস্টেম সেটিংস',
            'Daily Statement Closing Status' => 'ডেইলি স্টেটমেন্ট ক্লোজিং স্ট্যাটাস',
            'Stock Summary' => 'স্টক সামারি',
            'Stock Adjustment' => 'স্টক এডজাস্টমেন্ট',
            'Opening Stock' => 'ওপেনিং স্টক',
            'Stock IN (Purchases)' => 'স্টক ইন (ক্রয়)',
            'Sales (Stock Out)' => 'বিক্রয় (স্টক আউট)',
            'Stock Add (Adjustment)' => 'স্টক যুক্ত (সমন্বয়)',
            'Stock Deduct (Adjustment)' => 'স্টক কর্তন (সমন্বয়)',
            'Current Stock' => 'বর্তমান স্টক',
            'Fuel Station Management' => 'ফুয়েল স্টেশন ম্যানেজমেন্ট',
            'Logout' => 'লগআউট',
            'Profile' => 'প্রোফাইল',
            'Nozzle Meter Readings' => 'নজল মিটার রিডিং',
            'Nozzle Readings' => 'নজল রিডিং',
            'Customer Dues' => 'কাস্টমার বাকি',
            'Due Collections' => 'বাকি আদায়',
            'Cash Collections' => 'ক্যাশ কালেকশন',
            'Others Collections' => 'অন্যান্য কালেকশন',
            'Expense' => 'খরচ',
            'Fuel Purchase' => 'ফুয়েল ক্রয়',
            'Fuel Price Adjustment' => 'ফুয়েল মূল্য সমন্বয়',
            'Supplier Payment' => 'সাপ্লায়ার পেমেন্ট',
            'Tank Readings' => 'ট্যাংক রিডিং',
            'Today Sales' => 'আজকের বিক্রয়',
            'Today Collections' => 'আজকের কালেকশন',
            'Today Expense' => 'আজকের খরচ',
            'Customer Due' => 'কাস্টমার বাকি',
            'Supplier Paid' => 'সাপ্লায়ার পেমেন্ট',
            'Fuel Purchased' => 'ফুয়েল ক্রয়',
            'Shift' => 'শিফট',
            'Fuel Type' => 'ফুয়েল টাইপ',
            'Tank Group' => 'ট্যাংক গ্রুপ',
            'Tanks' => 'ট্যাংকস',
            'Tank' => 'ট্যাংক',
            'Dispenser' => 'ডিসপেন্সার',
            'Nozzles' => 'নজলস',
            'Nozzle' => 'নজল',
            'Supplier' => 'সাপ্লায়ার',
            'Shareholders' => 'শেয়ারহোল্ডারস',
            'Shareholder' => 'শেয়ারহোল্ডার',
            'Employee' => 'কর্মচারী',
            'Customer' => 'গ্রাহক',
            'Particular Group' => 'পার্টিকুলার গ্রুপ',
            'Particular' => 'পার্টিকুলার',
            'Shift Management' => 'শিফট ম্যানেজমেন্ট',
            'Fuel Type Management' => 'ফুয়েল টাইপ ম্যানেজমেন্ট',
            'Tank Group Management' => 'ট্যাংক গ্রুপ ম্যানেজমেন্ট',
            'Tanks Management' => 'ট্যাংকস ম্যানেজমেন্ট',
            'Dispenser Management' => 'ডিসপেন্সার ম্যানেজমেন্ট',
            'Nozzle Management' => 'নজল ম্যানেজমেন্ট',
            'Supplier Management' => 'সাপ্লায়ার ম্যানেজমেন্ট',
            'Shareholder Management' => 'শেয়ারহোল্ডার ম্যানেজমেন্ট',
            'Employee Management' => 'কর্মচারী ম্যানেজমেন্ট',
            'Customer Management' => 'গ্রাহক ম্যানেজমেন্ট',
            'Particular Group Management' => 'পার্টিকুলার গ্রুপ ম্যানেজমেন্ট',
            'Particular Group (Expense Category) Management' => 'পার্টিকুলার গ্রুপ (খরচ ক্যাটাগরি) ম্যানেজমেন্ট',
            'Particular Group / Expense Category' => 'পার্টিকুলার গ্রুপ / খরচ ক্যাটাগরি',
            'Expense Particular Management' => 'খরচ পার্টিকুলার ম্যানেজমেন্ট',
        ],
    ];

    $lang = currentLang();
    return $translations[$lang][$key] ?? $key;
}

/**
 * Ensure trx_finalsubmit table exists
 */
function ensureFinalSubmitTableExists()
{
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $sql = "CREATE TABLE IF NOT EXISTS `trx_finalsubmit` (
      `FinalSubmitID` int(11) NOT NULL AUTO_INCREMENT,
      `StatementDate` date NOT NULL,
      `IsClosed` tinyint(1) NOT NULL DEFAULT 1,
      `SubmittedAt` datetime NOT NULL DEFAULT current_timestamp(),
      `SubmittedBy` int(11) DEFAULT NULL,
      `ReopenedAt` datetime DEFAULT NULL,
      `ReopenedBy` int(11) DEFAULT NULL,
      `Remarks` varchar(255) DEFAULT NULL,
      `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
      `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`FinalSubmitID`),
      UNIQUE KEY `idx_statementdate` (`StatementDate`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    try {
        db()->inUpDel($sql);
    } catch (\Throwable $e) {
        // Table creation fallback
    }
}

/**
 * Check if daily statement for a given date is final submitted/closed
 * 
 * @param string $date Date in Y-m-d format
 * @return bool
 */
function isStatementClosed($date)
{
    if (empty($date)) return false;
    ensureFinalSubmitTableExists();
    $d = date('Y-m-d', strtotime($date));
    $sql = "SELECT IsClosed FROM trx_finalsubmit WHERE StatementDate = ?";
    $res = db()->index($sql, [$d]);
    if (!empty($res)) {
        return intval($res[0]->IsClosed) === 1;
    }
    return false;
}

/**
 * Get final submit / close details for a given date
 * 
 * @param string $date Date in Y-m-d format
 * @return object|null
 */
function getStatementCloseInfo($date)
{
    if (empty($date)) return null;
    ensureFinalSubmitTableExists();
    $d = date('Y-m-d', strtotime($date));
    $sql = "SELECT fs.*, u.NameEN AS SubmittedByName, u2.NameEN AS ReopenedByName 
            FROM trx_finalsubmit fs
            LEFT JOIN mst_employee u ON fs.SubmittedBy = u.Id
            LEFT JOIN mst_employee u2 ON fs.ReopenedBy = u2.Id
            WHERE fs.StatementDate = ?";
    $res = db()->index($sql, [$d]);
    if (!empty($res)) {
        return $res[0];
    }
    return null;
}

/**
 * Ensure trx_purchase_details and trx_stock_in tables exist, and alter trx_fuelpurchase if needed
 */
function ensureFuelPurchaseTablesExist()
{
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $sqlDetails = "CREATE TABLE IF NOT EXISTS `trx_purchase_details` (
      `PurchaseDetailID` int(11) NOT NULL AUTO_INCREMENT,
      `FuelPurchaseID` int(11) NOT NULL,
      `FuelTypeID` int(11) NOT NULL,
      `TankID` int(11) NOT NULL,
      `Quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
      `Rate` decimal(14,2) NOT NULL DEFAULT 0.00,
      `Amount` decimal(14,2) NOT NULL DEFAULT 0.00,
      `Remarks` varchar(255) DEFAULT NULL,
      `IsActive` tinyint(1) NOT NULL DEFAULT 1,
      `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (`PurchaseDetailID`),
      KEY `idx_fuel_purchase` (`FuelPurchaseID`),
      KEY `idx_fuel_type` (`FuelTypeID`),
      KEY `idx_tank` (`TankID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $sqlStockIn = "CREATE TABLE IF NOT EXISTS `trx_stock_in` (
      `StockInID` int(11) NOT NULL AUTO_INCREMENT,
      `StockInDate` date NOT NULL,
      `ReferenceType` varchar(50) NOT NULL DEFAULT 'FuelPurchase',
      `ReferenceID` int(11) NOT NULL,
      `ReferenceDetailID` int(11) DEFAULT NULL,
      `FuelTypeID` int(11) NOT NULL,
      `TankID` int(11) NOT NULL,
      `Quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
      `UnitRate` decimal(14,2) NOT NULL DEFAULT 0.00,
      `TotalValue` decimal(14,2) NOT NULL DEFAULT 0.00,
      `Remarks` varchar(255) DEFAULT NULL,
      `CreatedBy` int(11) DEFAULT NULL,
      `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
      `UpdatedBy` int(11) DEFAULT NULL,
      `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      `IsActive` tinyint(1) NOT NULL DEFAULT 1,
      `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (`StockInID`),
      KEY `idx_ref` (`ReferenceType`, `ReferenceID`),
      KEY `idx_fuel_tank` (`FuelTypeID`, `TankID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    try {
        db()->inUpDel($sqlDetails);
        db()->inUpDel($sqlStockIn);
        db()->inUpDel("ALTER TABLE `trx_fuelpurchase` MODIFY COLUMN `FuelTypeID` int(11) NULL, MODIFY COLUMN `TankID` int(11) NULL");
    } catch (\Throwable $e) {
        // Table creation fallback
    }

    try {
        $cols = db()->index("SHOW COLUMNS FROM `trx_fuelpurchase`");
        $colNames = [];
        foreach ($cols as $c) {
            $colNames[] = is_object($c) ? ($c->Field ?? '') : ($c['Field'] ?? '');
        }

        if (!in_array('DiscountType', $colNames)) {
            try { db()->inUpDel("ALTER TABLE `trx_fuelpurchase` ADD COLUMN `DiscountType` varchar(20) NOT NULL DEFAULT 'Fixed' AFTER `TaxAmount`"); } catch (\Throwable $e) {}
        }
        if (!in_array('DiscountValue', $colNames)) {
            try { db()->inUpDel("ALTER TABLE `trx_fuelpurchase` ADD COLUMN `DiscountValue` decimal(14,2) NOT NULL DEFAULT 0.00 AFTER `DiscountType`"); } catch (\Throwable $e) {}
        }
        if (!in_array('DiscountAmount', $colNames)) {
            try { db()->inUpDel("ALTER TABLE `trx_fuelpurchase` ADD COLUMN `DiscountAmount` decimal(14,2) NOT NULL DEFAULT 0.00 AFTER `DiscountValue`"); } catch (\Throwable $e) {}
        }
        if (!in_array('PaidAmount', $colNames)) {
            try { db()->inUpDel("ALTER TABLE `trx_fuelpurchase` ADD COLUMN `PaidAmount` decimal(14,2) NOT NULL DEFAULT 0.00 AFTER `TotalAmount`"); } catch (\Throwable $e) {}
        }
        if (!in_array('PaymentMethodID', $colNames)) {
            try { db()->inUpDel("ALTER TABLE `trx_fuelpurchase` ADD COLUMN `PaymentMethodID` int(11) NULL DEFAULT NULL AFTER `PaymentStatus`"); } catch (\Throwable $e) {}
        }
        if (!in_array('BankAccountID', $colNames)) {
            try { db()->inUpDel("ALTER TABLE `trx_fuelpurchase` ADD COLUMN `BankAccountID` int(11) NULL DEFAULT NULL AFTER `PaymentMethodID`"); } catch (\Throwable $e) {}
        }
        if (!in_array('PaymentRef', $colNames)) {
            try { db()->inUpDel("ALTER TABLE `trx_fuelpurchase` ADD COLUMN `PaymentRef` varchar(150) NULL DEFAULT NULL AFTER `BankAccountID`"); } catch (\Throwable $e) {}
        }
    } catch (\Throwable $e) {
        // Safe fallback
    }

    try {
        $spCols = db()->index("SHOW COLUMNS FROM `trx_supplierpayment`");
        $spColNames = [];
        foreach ($spCols as $c) {
            $spColNames[] = is_object($c) ? ($c->Field ?? '') : ($c['Field'] ?? '');
        }
        if (!in_array('FuelPurchaseID', $spColNames)) {
            try { db()->inUpDel("ALTER TABLE `trx_supplierpayment` ADD COLUMN `FuelPurchaseID` int(11) NULL DEFAULT NULL AFTER `SupplierPaymentID`"); } catch (\Throwable $e) {}
        }
    } catch (\Throwable $e) {
        // Safe fallback
    }

    ensureStockAdjustmentTableExist();
}

/**
 * Ensure trx_stockadjustment table exists
 */
function ensureStockAdjustmentTableExist()
{
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $sqlAdj = "CREATE TABLE IF NOT EXISTS `trx_stockadjustment` (
      `AdjustmentID` int(11) NOT NULL AUTO_INCREMENT,
      `AdjustmentDate` date NOT NULL,
      `AdjustmentType` varchar(20) NOT NULL DEFAULT 'Stock IN',
      `FuelTypeID` int(11) DEFAULT NULL,
      `TankID` int(11) NOT NULL,
      `Quantity` decimal(14,3) NOT NULL DEFAULT 0.000,
      `UnitRate` decimal(14,2) NOT NULL DEFAULT 0.00,
      `TotalValue` decimal(14,2) NOT NULL DEFAULT 0.00,
      `Reason` varchar(100) DEFAULT NULL,
      `AdjustmentReason` varchar(100) DEFAULT NULL,
      `Remarks` varchar(255) DEFAULT NULL,
      `CreatedBy` int(11) DEFAULT NULL,
      `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
      `UpdatedBy` int(11) DEFAULT NULL,
      `UpdatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      `IsActive` tinyint(1) NOT NULL DEFAULT 1,
      `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (`AdjustmentID`),
      KEY `idx_adj_date` (`AdjustmentDate`),
      KEY `idx_tank` (`TankID`),
      KEY `idx_adj_type` (`AdjustmentType`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    try {
        db()->inUpDel($sqlAdj);
    } catch (\Throwable $e) {
        // Table creation fallback
    }

    try {
        $cols = db()->index("SHOW COLUMNS FROM `trx_stockadjustment`");
        $colNames = [];
        foreach ($cols as $c) {
            $colNames[] = is_object($c) ? ($c->Field ?? '') : ($c['Field'] ?? '');
        }

        if (!in_array('Reason', $colNames)) {
            try { db()->inUpDel("ALTER TABLE `trx_stockadjustment` ADD COLUMN `Reason` varchar(100) DEFAULT NULL AFTER `TotalValue`"); } catch (\Throwable $e) {}
        }
        if (!in_array('AdjustmentReason', $colNames)) {
            try { db()->inUpDel("ALTER TABLE `trx_stockadjustment` ADD COLUMN `AdjustmentReason` varchar(100) DEFAULT NULL"); } catch (\Throwable $e) {}
        }
        if (!in_array('FuelTypeID', $colNames)) {
            try { db()->inUpDel("ALTER TABLE `trx_stockadjustment` ADD COLUMN `FuelTypeID` int(11) DEFAULT NULL"); } catch (\Throwable $e) {}
        }
    } catch (\Throwable $e) {
        // Safe fallback
    }
}

