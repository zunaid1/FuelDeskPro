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
    $name = sanitize($_POST['group_name'] ?? '');
    $fuel = intval($_POST['fuel_type_id'] ?? 0);
    $active = intval($_POST['is_active'] ?? 1);
    if (empty($name) || !$fuel) jsonResponse(false, 'Group name and fuel type are required!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE mst_tankgroup SET TankGroupName=?, FuelTypeID=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE TankGroupID=? AND IsDeleted=0", [$name, $fuel, $active, getUserId(), $id]);
        jsonResponse(true, 'Tank group updated successfully!');
    } else {
        $check = $objQuery->index("SELECT TankGroupID FROM mst_tankgroup WHERE TankGroupName=? AND IsDeleted=0", [$name]);
        if (!empty($check)) jsonResponse(false, 'Group name already exists!');
        $objQuery->inUpDel("INSERT INTO mst_tankgroup (TankGroupName, FuelTypeID, CreatedBy, IsActive) VALUES (?,?,?,?)", [$name, $fuel, getUserId(), $active]);
        jsonResponse(true, 'Tank group added successfully!');
    }
}
function handleDelete() {
    global $objQuery;
    $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_tankgroup SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE TankGroupID=?", [getUserId(), $id]);
    jsonResponse(true, 'Tank group deleted successfully!');
}