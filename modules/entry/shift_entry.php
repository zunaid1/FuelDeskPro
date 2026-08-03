<?php
/**
 * FuelDeskPro - Shift Entry Handler (AJAX)
 * 
 * Handles Add, Update, Delete operations for shifts.
 * 
 * @package FuelDeskPro
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Start session for auth check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Authentication required!');
}

// Get action from request
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'save':
        handleSave();
        break;
    case 'delete':
        handleDelete();
        break;
    default:
        jsonResponse(false, 'Invalid action!');
}

/**
 * Handle Save (Insert/Update) operation
 */
function handleSave()
{
    global $objQuery;

    $shiftId    = intval($_POST['shift_id'] ?? 0);
    $shiftName  = sanitize($_POST['shift_name'] ?? '');
    $startTime  = $_POST['start_time'] ?? '';
    $endTime    = $_POST['end_time'] ?? '';
    $isActive   = intval($_POST['is_active'] ?? 1);

    // Validation
    if (empty($shiftName)) {
        jsonResponse(false, 'Shift name is required!');
    }
    if (empty($startTime)) {
        jsonResponse(false, 'Start time is required!');
    }
    if (empty($endTime)) {
        jsonResponse(false, 'End time is required!');
    }

    if ($shiftId > 0) {
        // UPDATE existing shift
        $sql = "UPDATE mst_shift SET 
                    ShiftName = ?, 
                    StartTime = ?, 
                    EndTime = ?, 
                    IsActive = ?,
                    UpdatedBy = ?,
                    UpdatedAt = NOW()
                WHERE ShiftID = ? AND IsDeleted = 0";
        
        $params = [$shiftName, $startTime, $endTime, $isActive, getUserId(), $shiftId];
        $objQuery->inUpDel($sql, $params);

        jsonResponse(true, 'Shift updated successfully!');
    } else {
        // INSERT new shift
        // Check for duplicate name
        $check = $objQuery->index("SELECT ShiftID FROM mst_shift WHERE ShiftName = ? AND IsDeleted = 0", [$shiftName]);
        if (!empty($check)) {
            jsonResponse(false, 'Shift name already exists!');
        }

        $sql = "INSERT INTO mst_shift (ShiftName, StartTime, EndTime, CreatedBy, IsActive) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [$shiftName, $startTime, $endTime, getUserId(), $isActive];
        $objQuery->inUpDel($sql, $params);

        jsonResponse(true, 'Shift added successfully!');
    }
}

/**
 * Handle Delete operation (soft delete)
 */
function handleDelete()
{
    global $objQuery;

    $shiftId = intval($_POST['shift_id'] ?? 0);

    if ($shiftId <= 0) {
        jsonResponse(false, 'Invalid shift ID!');
    }

    // Soft delete - set IsDeleted = 1
    $sql = "UPDATE mst_shift SET 
                IsDeleted = 1, 
                UpdatedBy = ?, 
                UpdatedAt = NOW() 
            WHERE ShiftID = ?";
    
    $objQuery->inUpDel($sql, [getUserId(), $shiftId]);

    jsonResponse(true, 'Shift deleted successfully!');
}