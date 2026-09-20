<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) jsonResponse(false, 'Authentication required!');

ensurePersonTitlesTableSchema();

$action = $_POST['action'] ?? '';
switch ($action) {
    case 'save': handleSave(); break;
    case 'delete': handleDelete(); break;
    default: jsonResponse(false, 'Invalid action!');
}
function handleSave() {
    global $objQuery;
    $id = intval($_POST['record_id'] ?? 0);
    $titleEN = sanitize($_POST['title_en'] ?? '');
    $titleBN = sanitize($_POST['title_bn'] ?? '');
    $name = sanitize($_POST['customer_name'] ?? '');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $vehicle = sanitize($_POST['vehicle_no'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $due = floatval($_POST['opening_due'] ?? 0);
    $active = intval($_POST['is_active'] ?? 1);
    if (empty($name) || empty($mobile)) jsonResponse(false, 'Name and mobile are required!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE mst_customer SET TitleEN=?, TitleBN=?, CustomerName=?, Mobile=?, VehicleNumber=?, Address=?, OpeningDue=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE CustomerID=? AND IsDeleted=0", [$titleEN, $titleBN, $name, $mobile, $vehicle, $address, $due, $active, getUserId(), $id]);
        jsonResponse(true, 'Customer updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO mst_customer (TitleEN, TitleBN, CustomerName, Mobile, VehicleNumber, Address, OpeningDue, CreatedBy, IsActive) VALUES (?,?,?,?,?,?,?,?,?)", [$titleEN, $titleBN, $name, $mobile, $vehicle, $address, $due, getUserId(), $active]);
        jsonResponse(true, 'Customer added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_customer SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE CustomerID=?", [getUserId(), $id]);
    jsonResponse(true, 'Customer deleted successfully!');
}