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

function ensureTableSchema() {
    global $objQuery;
    try {
        $tableInfo = $objQuery->index("SHOW CREATE TABLE mst_expenseparticular");
        if (!empty($tableInfo) && isset($tableInfo[0]->{'Create Table'})) {
            $createTableSql = $tableInfo[0]->{'Create Table'};
            if (strpos($createTableSql, 'AUTO_INCREMENT') === false) {
                $objQuery->inUpDel("ALTER TABLE mst_expenseparticular MODIFY ExpenseParticularID INT(11) NOT NULL AUTO_INCREMENT");
            }
        }
    } catch (Exception $e) {
        // Handle gracefully
    }
}

function handleSave() {
    global $objQuery;
    ensureTableSchema();
    $id = intval($_POST['record_id'] ?? 0);
    $cat = intval($_POST['category_id'] ?? 0);
    $nameEn = sanitize($_POST['name_en'] ?? '');
    $nameBn = sanitize($_POST['name_bn'] ?? '');
    $active = intval($_POST['is_active'] ?? 1);
    if (empty($nameEn) || !$cat) jsonResponse(false, 'Name and category are required!');
    if ($id > 0) {
        $pid = 'PRT' . $id;
        $objQuery->inUpDel("UPDATE mst_expenseparticular SET ParticularID=?, ExpenseCategoryID=?, ParticularNameEN=?, ParticularNameBN=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE ExpenseParticularID=? AND IsDeleted=0", [$pid, $cat, $nameEn, $nameBn, $active, getUserId(), $id]);
        jsonResponse(true, 'Particular updated successfully!');
    } else {
        $maxRow = $objQuery->index("SELECT MAX(ExpenseParticularID) AS max_id FROM mst_expenseparticular");
        $nextId = intval($maxRow[0]->max_id ?? 0) + 1;
        if ($nextId < 1) $nextId = 1;
        $pid = 'PRT' . $nextId;
        $objQuery->inUpDel("INSERT INTO mst_expenseparticular (ExpenseParticularID, ParticularID, ExpenseCategoryID, ParticularNameEN, ParticularNameBN, CreatedBy, IsActive) VALUES (?,?,?,?,?,?,?)", [$nextId, $pid, $cat, $nameEn, $nameBn, getUserId(), $active]);
        jsonResponse(true, 'Particular added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_expenseparticular SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE ExpenseParticularID=?", [getUserId(), $id]);
    jsonResponse(true, 'Particular deleted successfully!');
}