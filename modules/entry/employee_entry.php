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
    $eid = sanitize($_POST['emp_id'] ?? '');
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
    if (empty($nameEn) || empty($mobile) || empty($nid)) jsonResponse(false, 'Name, mobile, and NID are required!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE mst_employee SET EmployeeId=?, NameEN=?, NameBN=?, FatherName=?, MotherName=?, DateOfBirth=?, JoiningDate=?, Mobile=?, Address=?, NationalID=?, Guarantor=?, Salary=?, Remarks=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE Id=? AND IsDeleted=0", [$eid, $nameEn, $nameBn, $father, $mother, $dob, $joining, $mobile, $address, $nid, $guarantor, $salary, $remarks, $active, getUserId(), $id]);
        jsonResponse(true, 'Employee updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO mst_employee (EmployeeId, NameEN, NameBN, FatherName, MotherName, DateOfBirth, JoiningDate, Mobile, Address, NationalID, Guarantor, Salary, Remarks, CreatedBy, IsActive) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", [$eid, $nameEn, $nameBn, $father, $mother, $dob, $joining, $mobile, $address, $nid, $guarantor, $salary, $remarks, getUserId(), $active]);
        jsonResponse(true, 'Employee added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_employee SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE Id=?", [getUserId(), $id]);
    jsonResponse(true, 'Employee deleted successfully!');
}