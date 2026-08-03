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
    $date = $_POST['collection_date'] ?? '';
    $time = $_POST['collection_time'] ?? date('H:i:s');
    $amount = floatval($_POST['amount'] ?? 0);
    $type = $_POST['collected_by_type'] ?? '';
    $personRaw = $_POST['collected_person_id'] ?? '';
    $remarks = sanitize($_POST['remarks'] ?? '');
    if (empty($date) || $amount <= 0 || empty($type) || empty($personRaw)) jsonResponse(false, 'Required fields missing!');
    $parts = explode('_', $personRaw);
    $personId = intval($parts[1] ?? 0);
    if ($personId <= 0) jsonResponse(false, 'Invalid person!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE trx_cashcollection SET CollectionDate=?, CollectionTime=?, Amount=?, CollectedByType=?, CollectedPersonID=?, Remarks=?, UpdatedBy=?, UpdatedAt=NOW() WHERE CashCollectionID=? AND IsDeleted=0", [$date, $time, $amount, $type, $personId, $remarks, getUserId(), $id]);
        jsonResponse(true, 'Cash collection updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO trx_cashcollection (CollectionDate, CollectionTime, Amount, CollectedByType, CollectedPersonID, Remarks, CreatedBy) VALUES (?,?,?,?,?,?,?)", [$date, $time, $amount, $type, $personId, $remarks, getUserId()]);
        jsonResponse(true, 'Cash collection added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE trx_cashcollection SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE CashCollectionID=?", [getUserId(), $id]);
    jsonResponse(true, 'Cash collection deleted successfully!');
}