<?php
/**
 * FuelDeskPro - System Setting Entry Handler (AJAX)
 * 
 * Handles Add, Update, Delete operations for system settings.
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

    $settingId    = intval($_POST['setting_id'] ?? 0);
    $settingKey   = sanitize($_POST['setting_key'] ?? '');
    $settingValue = trim($_POST['setting_value'] ?? '');
    $description  = sanitize($_POST['description'] ?? '');
    $isActive     = intval($_POST['is_active'] ?? 1);

    // Validation
    if (empty($settingKey)) {
        jsonResponse(false, 'Setting Key is required!');
    }

    if ($settingId > 0) {
        // UPDATE existing setting
        // Check duplicate key for other records
        $check = $objQuery->index("SELECT SystemSettingID FROM cfg_systemsetting WHERE SettingKey = ? AND SystemSettingID != ? AND IsDeleted = 0", [$settingKey, $settingId]);
        if (!empty($check)) {
            jsonResponse(false, 'Setting Key already exists!');
        }

        $sql = "UPDATE cfg_systemsetting SET 
                    SettingKey = ?, 
                    SettingValue = ?, 
                    Description = ?, 
                    IsActive = ?,
                    UpdatedBy = ?,
                    UpdatedAt = NOW()
                WHERE SystemSettingID = ? AND IsDeleted = 0";
        
        $params = [$settingKey, $settingValue, $description, $isActive, getUserId(), $settingId];
        $objQuery->inUpDel($sql, $params);

        jsonResponse(true, 'System setting updated successfully!');
    } else {
        // INSERT new setting
        $check = $objQuery->index("SELECT SystemSettingID FROM cfg_systemsetting WHERE SettingKey = ? AND IsDeleted = 0", [$settingKey]);
        if (!empty($check)) {
            jsonResponse(false, 'Setting Key already exists!');
        }

        $sql = "INSERT INTO cfg_systemsetting (SettingKey, SettingValue, Description, CreatedBy, IsActive) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [$settingKey, $settingValue, $description, getUserId(), $isActive];
        $objQuery->inUpDel($sql, $params);

        jsonResponse(true, 'System setting added successfully!');
    }
}

/**
 * Handle Delete operation (soft delete)
 */
function handleDelete()
{
    global $objQuery;

    $settingId = intval($_POST['setting_id'] ?? 0);

    if ($settingId <= 0) {
        jsonResponse(false, 'Invalid Setting ID!');
    }

    // Soft delete
    $sql = "UPDATE cfg_systemsetting SET 
                IsDeleted = 1, 
                UpdatedBy = ?, 
                UpdatedAt = NOW() 
            WHERE SystemSettingID = ?";
    
    $objQuery->inUpDel($sql, [getUserId(), $settingId]);

    jsonResponse(true, 'System setting deleted successfully!');
}
