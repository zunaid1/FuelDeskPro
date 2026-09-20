<?php
require_once __DIR__ . '/../config/database.php';
$cols = $objQuery->index("DESCRIBE trx_customerdue");
echo json_encode($cols, JSON_PRETTY_PRINT);
