<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user_id'] = 100;

try {
    $date = '2026-09-17';
    $customer = 'SH102';
    $fuel = 101;
    $vehicle = 'a';
    $qty = 13.682;
    $rate = 73.09;
    $total = 1000.00;
    $paid = 0.00;
    $due = 1000.00;
    $remarks = 'শাহ জাহান';
    $userId = 100;

    $res = $objQuery->inUpDel("INSERT INTO trx_customerdue (TxnDate, CustomerID, FuelTypeID, VehicleNumber, Quantity, Rate, TotalAmount, PaidAmount, DueAmount, Remarks, CreatedBy) VALUES (?,?,?,?,?,?,?,?,?,?,?)", [$date, $customer, $fuel, $vehicle, $qty, $rate, $total, $paid, $due, $remarks, $userId]);
    echo "INSERT SUCCESS! Rows: " . $res;
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage();
}
