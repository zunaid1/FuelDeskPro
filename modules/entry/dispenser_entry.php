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
    $name = sanitize($_POST['dis_name'] ?? '');
    $code = sanitize($_POST['dis_code'] ?? '');
    $active = intval($_POST['is_active'] ?? 1);
    if (empty($name)) jsonResponse(false, 'Dispenser name is required!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE mst_dispenser SET DisName=?, DisCode=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE DisID=? AND IsDeleted=0", [$name, $code, $active, getUserId(), $id]);
        jsonResponse(true, 'Dispenser updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO mst_dispenser (DisName, DisCode, CreatedBy, IsActive) VALUES (?,?,?,?)", [$name, $code, getUserId(), $active]);
        jsonResponse(true, 'Dispenser added successfully!');
    }
}
function handleDelete() {
    global $objQuery;
    $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_dispenser SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE DisID=?", [getUserId(), $id]);
    jsonResponse(true, 'Dispenser deleted successfully!');
}