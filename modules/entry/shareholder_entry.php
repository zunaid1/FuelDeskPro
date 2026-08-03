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
    $shid = sanitize($_POST['shareholder_id'] ?? '');
    $nameEn = sanitize($_POST['name_en'] ?? '');
    $nameBn = sanitize($_POST['name_bn'] ?? '');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $investment = floatval($_POST['investment'] ?? 0);
    $remarks = sanitize($_POST['remarks'] ?? '');
    $active = intval($_POST['is_active'] ?? 1);
    if (empty($nameEn) || empty($mobile)) jsonResponse(false, 'Name and mobile are required!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE mst_shareholder SET ShareHolderID=?, NameEN=?, NameBN=?, Mobile=?, Address=?, InvestmentAmount=?, Remarks=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE Id=? AND IsDeleted=0", [$shid, $nameEn, $nameBn, $mobile, $address, $investment, $remarks, $active, getUserId(), $id]);
        jsonResponse(true, 'Shareholder updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO mst_shareholder (ShareHolderID, NameEN, NameBN, Mobile, Address, InvestmentAmount, Remarks, CreatedBy, IsActive) VALUES (?,?,?,?,?,?,?,?,?)", [$shid, $nameEn, $nameBn, $mobile, $address, $investment, $remarks, getUserId(), $active]);
        jsonResponse(true, 'Shareholder added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_shareholder SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE Id=?", [getUserId(), $id]);
    jsonResponse(true, 'Shareholder deleted successfully!');
}