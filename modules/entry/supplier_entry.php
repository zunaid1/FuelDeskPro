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
    $name = sanitize($_POST['supplier_name'] ?? '');
    $contact = sanitize($_POST['contact_person'] ?? '');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $balance = floatval($_POST['opening_balance'] ?? 0);
    $active = intval($_POST['is_active'] ?? 1);
    if (empty($name)) jsonResponse(false, 'Supplier name is required!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE mst_supplier SET SupplierName=?, ContactPerson=?, Mobile=?, Email=?, Address=?, OpeningBalance=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE SupplierID=? AND IsDeleted=0", [$name, $contact, $mobile, $email, $address, $balance, $active, getUserId(), $id]);
        jsonResponse(true, 'Supplier updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO mst_supplier (SupplierName, ContactPerson, Mobile, Email, Address, OpeningBalance, CreatedBy, IsActive) VALUES (?,?,?,?,?,?,?,?)", [$name, $contact, $mobile, $email, $address, $balance, getUserId(), $active]);
        jsonResponse(true, 'Supplier added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_supplier SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE SupplierID=?", [getUserId(), $id]);
    jsonResponse(true, 'Supplier deleted successfully!');
}