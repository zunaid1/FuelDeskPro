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
    try {
        $cols = $objQuery->index("SHOW COLUMNS FROM mst_shareholder LIKE 'ExpenseCategoryID'");
        if (empty($cols)) {
            $objQuery->inUpDel("ALTER TABLE mst_shareholder ADD COLUMN ExpenseCategoryID INT(11) NULL DEFAULT NULL AFTER ShareHolderID");
        }
    } catch (\Throwable $e) {}

    $id = intval($_POST['record_id'] ?? 0);
    $shid = sanitize($_POST['shareholder_id'] ?? '');
    $expCatId = !empty($_POST['expense_category_id']) ? intval($_POST['expense_category_id']) : null;
    $nameEn = sanitize($_POST['name_en'] ?? '');
    $nameBn = sanitize($_POST['name_bn'] ?? '');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $investment = floatval($_POST['investment'] ?? 0);
    $remarks = sanitize($_POST['remarks'] ?? '');
    $active = intval($_POST['is_active'] ?? 1);
    if (empty($nameEn) || empty($mobile)) jsonResponse(false, 'Name and mobile are required!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE mst_shareholder SET ShareHolderID=?, ExpenseCategoryID=?, NameEN=?, NameBN=?, Mobile=?, Address=?, InvestmentAmount=?, Remarks=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE Id=? AND IsDeleted=0", [$shid, $expCatId, $nameEn, $nameBn, $mobile, $address, $investment, $remarks, $active, getUserId(), $id]);
        jsonResponse(true, 'Shareholder updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO mst_shareholder (ShareHolderID, ExpenseCategoryID, NameEN, NameBN, Mobile, Address, InvestmentAmount, Remarks, CreatedBy, IsActive) VALUES (?,?,?,?,?,?,?,?,?,?)", [$shid, $expCatId, $nameEn, $nameBn, $mobile, $address, $investment, $remarks, getUserId(), $active]);
        jsonResponse(true, 'Shareholder added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_shareholder SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE Id=?", [getUserId(), $id]);
    jsonResponse(true, 'Shareholder deleted successfully!');
}