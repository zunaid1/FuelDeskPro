<?php
require_once __DIR__ . '/../config/database.php';
global $objQuery;

try {
    echo "Altering mst_employee to set Id AUTO_INCREMENT...\n";
    $objQuery->inUpDel("ALTER TABLE mst_employee MODIFY Id INT(11) NOT NULL AUTO_INCREMENT");
    echo "SUCCESS!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "New SHOW CREATE TABLE:\n";
print_r($objQuery->index("SHOW CREATE TABLE mst_employee"));
