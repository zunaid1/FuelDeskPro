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
function handleSave() {
    global $objQuery;
    $id = intval($_POST['record_id'] ?? 0);
    $date = $_POST['purchase_date'] ?? '';
    $invoice = sanitize($_POST['invoice_no'] ?? '');
    $supplier = intval($_POST['supplier_id'] ?? 0);
    $fuel = intval($_POST['fuel_type_id'] ?? 0);
    $tank = intval($_POST['tank_id'] ?? 0);
    $qty = floatval($_POST['quantity'] ?? 0);
    $rate = floatval($_POST['rate'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $tax = floatval($_POST['tax_amount'] ?? 0);
    $total = floatval($_POST['total_amount'] ?? 0);
    $status = $_POST['payment_status'] ?? 'Due';
    $remarks = sanitize($_POST['remarks'] ?? '');
    if (empty($date) || !$supplier || !$fuel || !$tank || $qty <= 0 || $rate <= 0) jsonResponse(false, 'Required fields missing!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE trx_fuelpurchase SET PurchaseDate=?, InvoiceNo=?, SupplierID=?, FuelTypeID=?, TankID=?, Quantity=?, Rate=?, Amount=?, TaxAmount=?, TotalAmount=?, PaymentStatus=?, Remarks=?, UpdatedBy=?, UpdatedAt=NOW() WHERE FuelPurchaseID=? AND IsDeleted=0", [$date, $invoice, $supplier, $fuel, $tank, $qty, $rate, $amount, $tax, $total, $status, $remarks, getUserId(), $id]);
        jsonResponse(true, 'Purchase updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO trx_fuelpurchase (PurchaseDate, InvoiceNo, SupplierID, FuelTypeID, TankID, Quantity, Rate, Amount, TaxAmount, TotalAmount, PaymentStatus, Remarks, CreatedBy) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)", [$date, $invoice, $supplier, $fuel, $tank, $qty, $rate, $amount, $tax, $total, $status, $remarks, getUserId()]);
        jsonResponse(true, 'Purchase added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE trx_fuelpurchase SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE FuelPurchaseID=?", [getUserId(), $id]);
    jsonResponse(true, 'Purchase deleted successfully!');
}