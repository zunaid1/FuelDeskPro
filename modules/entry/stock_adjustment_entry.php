<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

ensureStockAdjustmentTableExist();

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
    $id             = intval($_POST['record_id'] ?? 0);
    $date           = $_POST['adjustment_date'] ?? '';
    $tankId         = intval($_POST['tank_id'] ?? 0);
    $adjustmentType = $_POST['adjustment_type'] ?? '';
    $quantity       = floatval($_POST['quantity'] ?? 0);
    $reason         = sanitize($_POST['reason'] ?? '');
    $remarks        = sanitize($_POST['remarks'] ?? '');

    if (empty($date) || $tankId <= 0 || !in_array($adjustmentType, ['Stock IN', 'Stock OUT', 'ADD', 'DEDUCT']) || $quantity <= 0) {
        jsonResponse(false, 'Required fields missing or invalid data!');
    }

    // Auto-fetch FuelTypeID from mst_tank for consistency
    $fuelTypeId = null;
    $tankRec = $objQuery->index("SELECT FuelTypeID FROM mst_tank WHERE TankID = ?", [$tankId]);
    if (!empty($tankRec)) {
        $fuelTypeId = intval($tankRec[0]->FuelTypeID);
    }

    if ($id > 0) {
        $objQuery->inUpDel(
            "UPDATE trx_stockadjustment 
             SET AdjustmentDate=?, FuelTypeID=?, TankID=?, AdjustmentType=?, Quantity=?, Reason=?, AdjustmentReason=?, Remarks=?, UpdatedBy=?, UpdatedAt=NOW() 
             WHERE (AdjustmentID=? OR AdjustmentID=?) AND IsDeleted=0",
            [$date, $fuelTypeId, $tankId, $adjustmentType, $quantity, $reason, $reason, $remarks, getUserId(), $id, $id]
        );
        jsonResponse(true, 'Stock adjustment updated successfully!');
    } else {
        $objQuery->inUpDel(
            "INSERT INTO trx_stockadjustment (AdjustmentDate, FuelTypeID, TankID, AdjustmentType, Quantity, Reason, AdjustmentReason, Remarks, CreatedBy) 
             VALUES (?,?,?,?,?,?,?,?,?)",
            [$date, $fuelTypeId, $tankId, $adjustmentType, $quantity, $reason, $reason, $remarks, getUserId()]
        );
        jsonResponse(true, 'Stock adjustment added successfully!');
    }
}

function handleDelete() {
    global $objQuery;
    $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel(
        "UPDATE trx_stockadjustment SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE (AdjustmentID=? OR AdjustmentID=?)",
        [getUserId(), $id, $id]
    );
    jsonResponse(true, 'Stock adjustment deleted successfully!');
}

