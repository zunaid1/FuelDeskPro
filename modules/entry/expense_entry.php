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
    $date = $_POST['expense_date'] ?? '';
    $particular = sanitize($_POST['particular_id'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $method = intval($_POST['payment_method'] ?? 0);
    $ref = sanitize($_POST['reference_no'] ?? '');
    $remarks = sanitize($_POST['remarks'] ?? '');
    if (empty($date) || empty($particular) || $amount <= 0) jsonResponse(false, 'Required fields missing!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE trx_expense SET ExpenseDate=?, ParticularID=?, Amount=?, PaymentMethodID=?, ReferenceNo=?, Remarks=?, UpdatedBy=?, UpdatedAt=NOW() WHERE ExpenseID=? AND IsDeleted=0", [$date, $particular, $amount, $method, $ref, $remarks, getUserId(), $id]);
        jsonResponse(true, 'Expense updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO trx_expense (ExpenseDate, ParticularID, Amount, PaymentMethodID, ReferenceNo, Remarks, CreatedBy) VALUES (?,?,?,?,?,?,?)", [$date, $particular, $amount, $method, $ref, $remarks, getUserId()]);
        jsonResponse(true, 'Expense added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE trx_expense SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE ExpenseID=?", [getUserId(), $id]);
    jsonResponse(true, 'Expense deleted successfully!');
}