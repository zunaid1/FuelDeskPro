<?php
require_once __DIR__ . '/../config/database.php';

try {
    $res1 = $objQuery->inUpDel("ALTER TABLE trx_customerdue MODIFY CustomerID VARCHAR(50) NOT NULL");
    echo "ALTER trx_customerdue SUCCESS!\n";
} catch (Throwable $e) {
    echo "ALTER trx_customerdue ERR: " . $e->getMessage() . "\n";
}

try {
    $res2 = $objQuery->inUpDel("ALTER TABLE trx_customercollection MODIFY CustomerID VARCHAR(50) NOT NULL");
    echo "ALTER trx_customercollection SUCCESS!\n";
} catch (Throwable $e) {
    echo "ALTER trx_customercollection ERR: " . $e->getMessage() . "\n";
}
