<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) jsonResponse(false, 'Authentication required!');
$action = $_POST['action'] ?? '';
switch ($action) {
    case 'save': handleSave(); break;
    case 'delete': handleDelete(); break;
    case 'get_previous': getPreviousReading(); break;
    default: jsonResponse(false, 'Invalid action!');
}
function handleSave() {
    global $objQuery;
    $id = intval($_POST['record_id'] ?? 0);
    $date = $_POST['reading_date'] ?? '';
    $tank = intval($_POST['tank_id'] ?? 0);
    $prev = floatval($_POST['prev_reading'] ?? 0);
    $current = floatval($_POST['current_reading'] ?? 0);
    $sold = floatval($_POST['sold_percent'] ?? 0);
    if (empty($date) || !$tank || $current < 0 || $current > 100) jsonResponse(false, 'Required fields missing or invalid!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE trx_tankreading SET ReadingDate=?, TankID=?, PreviousReadingPercent=?, CurrentReadingPercent=?, TodaySoldPercent=?, UpdatedBy=?, UpdatedAt=NOW() WHERE TankReadingID=? AND IsDeleted=0", [$date, $tank, $prev, $current, $sold, getUserId(), $id]);
        jsonResponse(true, 'Tank reading updated successfully!');
    } else {
        $check = $objQuery->index("SELECT TankReadingID FROM trx_tankreading WHERE TankID=? AND ReadingDate=? AND IsDeleted=0", [$tank, $date]);
        if (!empty($check)) jsonResponse(false, 'Reading already exists for this tank/date!');
        $objQuery->inUpDel("INSERT INTO trx_tankreading (ReadingDate, TankID, PreviousReadingPercent, CurrentReadingPercent, TodaySoldPercent, CreatedBy) VALUES (?,?,?,?,?,?)", [$date, $tank, $prev, $current, $sold, getUserId()]);
        jsonResponse(true, 'Tank reading added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE trx_tankreading SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE TankReadingID=?", [getUserId(), $id]);
    jsonResponse(true, 'Tank reading deleted successfully!');
}
function getPreviousReading() {
    global $objQuery;
    $tankId = intval($_POST['tank_id'] ?? 0);
    if ($tankId <= 0) jsonResponse(false, 'Invalid tank!');
    $last = $objQuery->index("SELECT CurrentReadingPercent FROM trx_tankreading WHERE TankID=? AND IsDeleted=0 ORDER BY ReadingDate DESC, TankReadingID DESC LIMIT 1", [$tankId]);
    if (!empty($last)) {
        jsonResponse(true, '', ['prev_percent' => $last[0]->CurrentReadingPercent]);
    } else {
        jsonResponse(true, '', ['prev_percent' => 0]);
    }
}