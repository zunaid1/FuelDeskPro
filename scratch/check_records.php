<?php
require_once __DIR__ . '/../config/database.php';
global $objQuery;

$rows = $objQuery->index("SELECT * FROM mst_employee ORDER BY Id DESC LIMIT 3");
print_r($rows);
