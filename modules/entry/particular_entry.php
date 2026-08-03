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
    $pid = sanitize($_POST['particular_id'] ?? '');
    $cat = intval($_POST['category_id'] ?? 0);
    $nameEn = sanitize($_POST['name_en'] ?? '');
    $nameBn = sanitize($_POST['name_bn'] ?? '');
    $active = intval($_POST['is_active'] ?? 1);
    if (empty($nameEn) || !$cat) jsonResponse(false, 'Name and category are required!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE mst_expenseparticular SET ParticularID=?, ExpenseCategoryID=?, ParticularNameEN=?, ParticularNameBN=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE ExpenseParticularID=? AND IsDeleted=0", [$pid, $cat, $nameEn, $nameBn, $active, getUserId(), $id]);
        jsonResponse(true, 'Particular updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO mst_expenseparticular (ParticularID, ExpenseCategoryID, ParticularNameEN, ParticularNameBN, CreatedBy, IsActive) VALUES (?,?,?,?,?,?)", [$pid, $cat, $nameEn, $nameBn, getUserId(), $active]);
        jsonResponse(true, 'Particular added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_expenseparticular SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE ExpenseParticularID=?", [getUserId(), $id]);
    jsonResponse(true, 'Particular deleted successfully!');
}