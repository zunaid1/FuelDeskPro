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
    'category_id' => '11',
    'name_en' => 'Test Particular EN',
    'name_bn' => 'টেস্ট পার্টিকুলার বিএন',
    'is_active' => '1'
];

echo "Calling handleSave for particular_entry...\n";
require __DIR__ . '/../modules/entry/particular_entry.php';
