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
    $nameEn = sanitize($_POST['name_en'] ?? '');
    $nameBn = sanitize($_POST['name_bn'] ?? '');
    $active = intval($_POST['is_active'] ?? 1);
    if (empty($nameEn)) jsonResponse(false, 'Category name is required!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE mst_expensecategory SET CategoryNameEN=?, CategoryNameBN=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE ExpenseCategoryID=? AND IsDeleted=0", [$nameEn, $nameBn, $active, getUserId(), $id]);
        jsonResponse(true, 'Category updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO mst_expensecategory (CategoryNameEN, CategoryNameBN, CreatedBy, IsActive) VALUES (?,?,?,?)", [$nameEn, $nameBn, getUserId(), $active]);
        jsonResponse(true, 'Category added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_expensecategory SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE ExpenseCategoryID=?", [getUserId(), $id]);
    jsonResponse(true, 'Category deleted successfully!');
}