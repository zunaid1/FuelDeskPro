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
    $name = sanitize($_POST['tank_name'] ?? '');
    $code = sanitize($_POST['tank_code'] ?? '');
    $group = !empty($_POST['tank_group_id']) ? intval($_POST['tank_group_id']) : null;
    $fuel = intval($_POST['fuel_type_id'] ?? 0);
    $capacity = floatval($_POST['capacity'] ?? 0);
    $min = floatval($_POST['min_level'] ?? 0);
    $opening = floatval($_POST['opening_stock'] ?? 0);
    $priority = intval($_POST['priority'] ?? 100);
    $active = intval($_POST['is_active'] ?? 1);
    if (empty($name) || !$fuel || $capacity <= 0) jsonResponse(false, 'Tank name, fuel type and capacity are required!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE mst_tank SET TankName=?, TankCode=?, TankGroupID=?, FuelTypeID=?, Capacity=?, MinLevel=?, OpeningStockPercent=?, Priority=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE TankID=? AND IsDeleted=0", [$name, $code, $group, $fuel, $capacity, $min, $opening, $priority, $active, getUserId(), $id]);
        jsonResponse(true, 'Tank updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO mst_tank (TankName, TankCode, TankGroupID, FuelTypeID, Capacity, MinLevel, OpeningStockPercent, Priority, CreatedBy, IsActive) VALUES (?,?,?,?,?,?,?,?,?,?)", [$name, $code, $group, $fuel, $capacity, $min, $opening, $priority, getUserId(), $active]);
        jsonResponse(true, 'Tank added successfully!');
    }
}
function handleDelete() {
    global $objQuery;
    $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_tank SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE TankID=?", [getUserId(), $id]);
    jsonResponse(true, 'Tank deleted successfully!');
}