<?php
/**
 * FuelDeskPro - Fuel Type Entry Handler (AJAX)
 * 
 * @package FuelDeskPro
 */

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
    $name = sanitize($_POST['fuel_name'] ?? '');
    $code = sanitize($_POST['fuel_code'] ?? '');
    $unit = intval($_POST['unit_of_measure'] ?? 0);
    $selling = floatval($_POST['selling_rate'] ?? 0);
    $purchase = floatval($_POST['purchase_rate'] ?? 0);
    $commission = floatval($_POST['commission_rate'] ?? 0);
    $tax = floatval($_POST['tax_percent'] ?? 0);
    $density = (isset($_POST['density']) && $_POST['density'] !== '') ? floatval($_POST['density']) : null;
    $color = sanitize($_POST['color_code'] ?? '');
    $remarks = sanitize($_POST['remarks'] ?? '');
    $active = intval($_POST['is_active'] ?? 1);

    if (empty($name)) jsonResponse(false, 'Fuel name is required!');

    if ($id > 0) {
        $sql = "UPDATE mst_fueltype SET FuelName=?, FuelCode=?, UnitOfMeasure=?, SellingRate=?, PurchaseRate=?, CommissionRate=?, TaxPercent=?, Density=?, ColorCode=?, Remarks=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE FuelTypeID=? AND IsDeleted=0";
        $objQuery->inUpDel($sql, [$name, $code, $unit, $selling, $purchase, $commission, $tax, $density, $color, $remarks, $active, getUserId(), $id]);
        jsonResponse(true, 'Fuel type updated successfully!');
    } else {
        $check = $objQuery->index("SELECT FuelTypeID FROM mst_fueltype WHERE FuelName=? AND IsDeleted=0", [$name]);
        if (!empty($check)) jsonResponse(false, 'Fuel type name already exists!');
        $sql = "INSERT INTO mst_fueltype (FuelName, FuelCode, UnitOfMeasure, SellingRate, PurchaseRate, CommissionRate, TaxPercent, Density, ColorCode, Remarks, CreatedBy, IsActive) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
        $objQuery->inUpDel($sql, [$name, $code, $unit, $selling, $purchase, $commission, $tax, $density, $color, $remarks, getUserId(), $active]);
        jsonResponse(true, 'Fuel type added successfully!');
    }
}

function handleDelete() {
    global $objQuery;
    $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_fueltype SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE FuelTypeID=?", [getUserId(), $id]);
    jsonResponse(true, 'Fuel type deleted successfully!');
}