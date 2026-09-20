<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user_id'] = 100;

try {
    // Ensure column schema is VARCHAR(50)
    try {
        $objQuery->inUpDel("ALTER TABLE trx_customerdue MODIFY CustomerID VARCHAR(50) NOT NULL");
    } catch (Throwable $e) {}

    $date = date('Y-m-d'); // 2026-09-17
    $customer = 'SH102'; // Mohammad Ali
    $fuel = 101; // LPG
    $vehicle = 'DHAKA METRO LA-1234';
    $qty = 11.503;
    $rate = 86.93;
    $total = 1000.00;
    $paid = 0.00;
    $due = 1000.00;
    $remarks = 'Demo Credit Sale (মোহাম্মদ আলী - ১,০০০ টাকা)';
    $userId = 100;

    $res = $objQuery->inUpDel("INSERT INTO trx_customerdue (TxnDate, CustomerID, FuelTypeID, VehicleNumber, Quantity, Rate, TotalAmount, PaidAmount, DueAmount, Remarks, CreatedBy) VALUES (?,?,?,?,?,?,?,?,?,?,?)", [$date, $customer, $fuel, $vehicle, $qty, $rate, $total, $paid, $due, $remarks, $userId]);
    echo "SUCCESS: Demo due record inserted! Affected rows: " . $res . "\n";
    $id = $objQuery->getLastInsertId();
    echo "Inserted CustomerDueID: " . $id . "\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
