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
    $name = sanitize($_POST['nozzle_name'] ?? '');
    $no = intval($_POST['nozzle_no'] ?? 0);
    $dis = intval($_POST['dis_id'] ?? 0);
    $fuel = intval($_POST['fuel_type_id'] ?? 0);
    $group = !empty($_POST['tank_group_id']) ? intval($_POST['tank_group_id']) : null;
    $general = floatval($_POST['opening_general'] ?? 0);
    $master = floatval($_POST['opening_master'] ?? 0);
    $active = intval($_POST['is_active'] ?? 1);
    if (empty($name) || !$no || !$dis || !$fuel) jsonResponse(false, 'Required fields missing!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE mst_nozzle SET NozzleName=?, NozzleNo=?, DisID=?, FuelTypeID=?, TankGroupID=?, OpeningGeneral=?, OpeningMaster=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE NozzleID=? AND IsDeleted=0", [$name, $no, $dis, $fuel, $group, $general, $master, $active, getUserId(), $id]);
        jsonResponse(true, 'Nozzle updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO mst_nozzle (NozzleName, NozzleNo, DisID, FuelTypeID, TankGroupID, OpeningGeneral, OpeningMaster, CreatedBy, IsActive) VALUES (?,?,?,?,?,?,?,?,?)", [$name, $no, $dis, $fuel, $group, $general, $master, getUserId(), $active]);
        jsonResponse(true, 'Nozzle added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_nozzle SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE NozzleID=?", [getUserId(), $id]);
    jsonResponse(true, 'Nozzle deleted successfully!');
}