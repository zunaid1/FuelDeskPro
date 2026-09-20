<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) jsonResponse(false, 'Authentication required!');
$action = $_POST['action'] ?? '';
switch ($action) {
    case 'save': handleSave(); break;
    case 'delete': handleDelete(); break;
    default: jsonResponse(false, 'Invalid action!');
}
function ensureCustomerDueSchema() {
    static $done = false;
    if ($done) return;
    global $objQuery;
    try {
        $objQuery->inUpDel("ALTER TABLE trx_customerdue MODIFY CustomerID VARCHAR(50) NOT NULL");
    } catch (Throwable $e) {}
    try {
        $objQuery->inUpDel("ALTER TABLE trx_customercollection MODIFY CustomerID VARCHAR(50) NOT NULL");
    } catch (Throwable $e) {}
    if (function_exists('ensureCustomerDueTableSchema')) {
        ensureCustomerDueTableSchema();
    }
    $done = true;
}

function handleSave() {
    global $objQuery;
    ensureCustomerDueSchema();
    $id = intval($_POST['record_id'] ?? 0);
    $date = $_POST['txn_date'] ?? '';
    $rawCustomer = $_POST['customer_id'] ?? '';
    $entity = parseSelectedEntity($rawCustomer);
    $customerID = $entity['id'] > 0 ? $entity['id'] : sanitize($rawCustomer);
    $customerType = $entity['type'];
    $fuel = intval($_POST['fuel_type_id'] ?? 0);
    $vehicle = sanitize($_POST['vehicle_no'] ?? '');
    $qty = floatval($_POST['quantity'] ?? 0);
    $rate = floatval($_POST['rate'] ?? 0);
    $total = floatval($_POST['total_amount'] ?? 0);
    $paid = floatval($_POST['paid_amount'] ?? 0);
    $due = floatval($_POST['due_amount'] ?? 0);
    $remarks = sanitize($_POST['remarks'] ?? '');

    if ($total > 0 && $rate > 0 && $qty <= 0) {
        $qty = round($total / $rate, 3);
    }
    if ($total > 0 && $qty > 0 && $rate <= 0) {
        $rate = round($total / $qty, 2);
    }
    if ($total <= 0 && $qty > 0 && $rate > 0) {
        $total = round($qty * $rate, 2);
    }
    if ($total > 0 && $due <= 0) {
        $due = max(0, $total - $paid);
    }

    if (empty($date) || empty($rawCustomer) || !$fuel || ($qty <= 0 && $total <= 0) || $rate <= 0) jsonResponse(false, 'Required fields missing!');
    if (isStatementClosed($date)) jsonResponse(false, 'এই তারিখের ('.date('d-m-Y', strtotime($date)).') হিসাবটি ইতোমধ্যে ফাইনাল সাবমিট (ক্লোজ) করা হয়েছে! নতুন ডাটা সেভ বা আপডেট করা সম্ভব নয়।');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE trx_customerdue SET TxnDate=?, CustomerID=?, CustomerType=?, FuelTypeID=?, VehicleNumber=?, Quantity=?, Rate=?, TotalAmount=?, PaidAmount=?, DueAmount=?, Remarks=?, UpdatedBy=?, UpdatedAt=NOW() WHERE CustomerDueID=? AND IsDeleted=0", [$date, $customerID, $customerType, $fuel, $vehicle, $qty, $rate, $total, $paid, $due, $remarks, getUserId(), $id]);
        jsonResponse(true, 'Due updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO trx_customerdue (TxnDate, CustomerID, CustomerType, FuelTypeID, VehicleNumber, Quantity, Rate, TotalAmount, PaidAmount, DueAmount, Remarks, CreatedBy) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)", [$date, $customerID, $customerType, $fuel, $vehicle, $qty, $rate, $total, $paid, $due, $remarks, getUserId()]);
        jsonResponse(true, 'Due added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $rec = $objQuery->index("SELECT TxnDate FROM trx_customerdue WHERE CustomerDueID=?", [$id]);
    if (!empty($rec) && isStatementClosed($rec[0]->TxnDate)) {
        jsonResponse(false, 'এই তারিখের ('.date('d-m-Y', strtotime($rec[0]->TxnDate)).') হিসাবটি ইতোমধ্যে ফাইনাল সাবমিট (ক্লোজ) করা হয়েছে! এই তারিখের ডাটা মোছা সম্ভব নয়।');
    }
    $objQuery->inUpDel("UPDATE trx_customerdue SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE CustomerDueID=?", [getUserId(), $id]);
    jsonResponse(true, 'Due deleted successfully!');
}