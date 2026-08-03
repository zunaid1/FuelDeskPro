<?php
/**
 * FuelDeskPro - Common Helper Functions
 * 
 * @package FuelDeskPro
 */

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
        if ($key && isset($company->$key)) {
            return $company->$key;
        }
        return $company;
    }
    return null;
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
        ],
        'bn' => [
            'Dashboard' => 'ড্যাশবোর্ড',
            'Master Data' => 'মাস্টার ডাটা',
            'Operations' => 'অপারেশন',
            'Reports' => 'রিপোর্ট',
            'Settings' => 'সেটিংস',
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
