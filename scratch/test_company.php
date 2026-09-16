<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$infoBn = getFormattedCompanyInfo('bn');
$infoEn = getFormattedCompanyInfo('en');

echo "BN NAME: " . $infoBn['name'] . "\n";
echo "EN NAME: " . $infoEn['name'] . "\n";

$raw = getCompanyInfo();
echo "RAW ARRAY/OBJECT:\n";
print_r($raw);
