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
        $tableInfo = $objQuery->index("SHOW CREATE TABLE mst_employee");
        if (!empty($tableInfo) && isset($tableInfo[0]->{'Create Table'})) {
            $createTableSql = $tableInfo[0]->{'Create Table'};
            if (strpos($createTableSql, 'AUTO_INCREMENT') === false) {
                $objQuery->inUpDel("ALTER TABLE mst_employee MODIFY Id INT(11) NOT NULL AUTO_INCREMENT");
            }
        }
        $cols = $objQuery->index("SHOW COLUMNS FROM mst_employee LIKE 'ExpenseCategoryID'");
        if (empty($cols)) {
            $objQuery->inUpDel("ALTER TABLE mst_employee ADD COLUMN ExpenseCategoryID INT(11) NULL DEFAULT 11 AFTER EmployeeId");
        }
    } catch (Exception $e) {
        // Handle gracefully
    }
}

function handleSave() {
    global $objQuery;
    ensureTableSchema();
    $id = intval($_POST['record_id'] ?? 0);
    $expCatId = intval($_POST['expense_category_id'] ?? 11);
    if ($expCatId <= 0) $expCatId = 11;

    $nameEn = sanitize($_POST['name_en'] ?? '');
    $nameBn = sanitize($_POST['name_bn'] ?? '');
    $father = sanitize($_POST['father'] ?? '');
    $mother = sanitize($_POST['mother'] ?? '');
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $joining = !empty($_POST['joining']) ? $_POST['joining'] : null;
    $mobile = sanitize($_POST['mobile'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $nid = sanitize($_POST['nid'] ?? '');
    $guarantor = sanitize($_POST['guarantor'] ?? '');
    $salary = floatval($_POST['salary'] ?? 0);
    $remarks = sanitize($_POST['remarks'] ?? '');
    $active = intval($_POST['is_active'] ?? 1);

    if (empty($nameEn) || empty($nameBn)) jsonResponse(false, 'Name (EN) and Name (BN) are required!');

    if ($id > 0) {
        $objQuery->inUpDel("UPDATE mst_employee SET ExpenseCategoryID=?, NameEN=?, NameBN=?, FatherName=?, MotherName=?, DateOfBirth=?, JoiningDate=?, Mobile=?, Address=?, NationalID=?, Guarantor=?, Salary=?, Remarks=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE Id=? AND IsDeleted=0", [$expCatId, $nameEn, $nameBn, $father, $mother, $dob, $joining, $mobile, $address, $nid, $guarantor, $salary, $remarks, $active, getUserId(), $id]);
        jsonResponse(true, 'Employee updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO mst_employee (ExpenseCategoryID, NameEN, NameBN, FatherName, MotherName, DateOfBirth, JoiningDate, Mobile, Address, NationalID, Guarantor, Salary, Remarks, CreatedBy, IsActive) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", [$expCatId, $nameEn, $nameBn, $father, $mother, $dob, $joining, $mobile, $address, $nid, $guarantor, $salary, $remarks, getUserId(), $active]);
        $newId = $objQuery->getLastInsertId();
        $eid = 'EMP' . $newId;
        $objQuery->inUpDel("UPDATE mst_employee SET EmployeeId=? WHERE Id=?", [$eid, $newId]);
        jsonResponse(true, 'Employee added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_employee SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE Id=?", [getUserId(), $id]);
    jsonResponse(true, 'Employee deleted successfully!');
}