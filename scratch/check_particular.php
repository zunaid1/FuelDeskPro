<?php
require_once __DIR__ . '/../config/database.php';
global $objQuery;

echo "SHOW CREATE TABLE mst_expenseparticular:\n";
print_r($objQuery->index("SHOW CREATE TABLE mst_expenseparticular"));

echo "\nMAX ExpenseParticularID:\n";
print_r($objQuery->index("SELECT MAX(ExpenseParticularID) as max_id FROM mst_expenseparticular"));
