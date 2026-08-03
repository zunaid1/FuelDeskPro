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
    $date = $_POST['effective_date'] ?? '';
    $fuel = intval($_POST['fuel_type_id'] ?? 0);
    $old = floatval($_POST['old_price'] ?? 0);
    $new = floatval($_POST['new_price'] ?? 0);
    $stock = floatval($_POST['stock_qty'] ?? 0);
    $ref = sanitize($_POST['reference_no'] ?? '');
    $remarks = sanitize($_POST['remarks'] ?? '');
    if (empty($date) || !$fuel || $new <= 0 || $stock <= 0) jsonResponse(false, 'Required fields missing!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE trx_fuelpriceadjustment SET EffectiveDate=?, FuelTypeID=?, OldSellingPrice=?, NewSellingPrice=?, StockQuantity=?, ReferenceNo=?, Remarks=?, UpdatedBy=?, UpdatedAt=NOW() WHERE FuelPriceAdjustmentID=? AND IsDeleted=0", [$date, $fuel, $old, $new, $stock, $ref, $remarks, getUserId(), $id]);
        jsonResponse(true, 'Adjustment updated successfully!');
    } else {
        $check = $objQuery->index("SELECT FuelPriceAdjustmentID FROM trx_fuelpriceadjustment WHERE FuelTypeID=? AND EffectiveDate=? AND IsDeleted=0", [$fuel, $date]);
        if (!empty($check)) jsonResponse(false, 'Adjustment already exists for this fuel/date!');
        $objQuery->inUpDel("INSERT INTO trx_fuelpriceadjustment (EffectiveDate, FuelTypeID, OldSellingPrice, NewSellingPrice, StockQuantity, ReferenceNo, Remarks, CreatedBy) VALUES (?,?,?,?,?,?,?,?)", [$date, $fuel, $old, $new, $stock, $ref, $remarks, getUserId()]);
        jsonResponse(true, 'Adjustment added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE trx_fuelpriceadjustment SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE FuelPriceAdjustmentID=?", [getUserId(), $id]);
    jsonResponse(true, 'Adjustment deleted successfully!');
}