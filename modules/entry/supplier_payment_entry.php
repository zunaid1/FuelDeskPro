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
    $date = $_POST['payment_date'] ?? '';
    $supplier = intval($_POST['supplier_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $method = intval($_POST['payment_method'] ?? 0);
    $ref = sanitize($_POST['reference_no'] ?? '');
    $remarks = sanitize($_POST['remarks'] ?? '');
    if (empty($date) || !$supplier || $amount <= 0 || !$method) jsonResponse(false, 'Required fields missing!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE trx_supplierpayment SET PaymentDate=?, SupplierID=?, Amount=?, PaymentMethodID=?, ReferenceNo=?, Remarks=?, UpdatedBy=?, UpdatedAt=NOW() WHERE SupplierPaymentID=? AND IsDeleted=0", [$date, $supplier, $amount, $method, $ref, $remarks, getUserId(), $id]);
        jsonResponse(true, 'Payment updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO trx_supplierpayment (PaymentDate, SupplierID, Amount, PaymentMethodID, ReferenceNo, Remarks, CreatedBy) VALUES (?,?,?,?,?,?,?)", [$date, $supplier, $amount, $method, $ref, $remarks, getUserId()]);
        jsonResponse(true, 'Payment added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE trx_supplierpayment SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE SupplierPaymentID=?", [getUserId(), $id]);
    jsonResponse(true, 'Payment deleted successfully!');
}