<?php
require_once __DIR__ . '/../config/database.php';
$cust = $objQuery->index("SELECT CustomerID, CustomerName, Mobile FROM mst_customer");
echo "mst_customer:\n" . json_encode($cust, JSON_PRETTY_PRINT) . "\n\n";

$all = $objQuery->index("SELECT * FROM vw_customerlistall");
echo "vw_customerlistall:\n" . json_encode($all, JSON_PRETTY_PRINT);
