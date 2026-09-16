<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user_id'] = 1;

$_POST = [
    'action' => 'save',
    'record_id' => '0',
    'expense_category_id' => '11',
    'name_en' => 'Asif',
    'name_bn' => 'আসিফ',
    'father' => '',
    'mother' => '',
    'dob' => '',
    'joining' => '',
    'mobile' => '',
    'address' => '',
    'nid' => '',
    'guarantor' => '',
    'salary' => '0',
    'remarks' => 'Test Entry',
    'is_active' => '1'
];

echo "Calling handleSave()...\n";
require __DIR__ . '/../modules/entry/employee_entry.php';
