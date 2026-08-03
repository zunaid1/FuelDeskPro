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
    $date = $_POST['txn_date'] ?? '';
    $customer = intval($_POST['customer_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $method = intval($_POST['payment_method'] ?? 0);
    $ref = sanitize($_POST['reference_no'] ?? '');
    $remarks = sanitize($_POST['remarks'] ?? '');
    if (empty($date) || !$customer || $amount <= 0 || !$method) jsonResponse(false, 'Required fields missing!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE trx_customercollection SET TxnDate=?, CustomerID=?, Amount=?, PaymentMethodID=?, ReferenceNo=?, Remarks=?, UpdatedBy=?, UpdatedAt=NOW() WHERE CustomerCollectionID=? AND IsDeleted=0", [$date, $customer, $amount, $method, $ref, $remarks, getUserId(), $id]);
        jsonResponse(true, 'Collection updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO trx_customercollection (TxnDate, CustomerID, Amount, PaymentMethodID, ReferenceNo, Remarks, CreatedBy) VALUES (?,?,?,?,?,?,?)", [$date, $customer, $amount, $method, $ref, $remarks, getUserId()]);
        jsonResponse(true, 'Collection added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE trx_customercollection SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE CustomerCollectionID=?", [getUserId(), $id]);
    jsonResponse(true, 'Collection deleted successfully!');
}