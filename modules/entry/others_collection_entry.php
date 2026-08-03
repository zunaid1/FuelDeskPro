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
    $particular = intval($_POST['particular_id'] ?? 0);
    $party = sanitize($_POST['party_name'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $method = intval($_POST['payment_method'] ?? 0);
    $ref = sanitize($_POST['reference_no'] ?? '');
    $narration = sanitize($_POST['narration'] ?? '');
    $remarks = sanitize($_POST['remarks'] ?? '');
    if (empty($date) || !$particular || $amount <= 0 || !$method) jsonResponse(false, 'Required fields missing!');
    if ($id > 0) {
        $objQuery->inUpDel("UPDATE trx_otherscollection SET CollectionDate=?, ParticularID=?, PartyName=?, Amount=?, PaymentMethodID=?, ReferenceNo=?, Narration=?, Remarks=?, UpdatedBy=?, UpdatedAt=NOW() WHERE OthersCollectionID=? AND IsDeleted=0", [$date, $particular, $party, $amount, $method, $ref, $narration, $remarks, getUserId(), $id]);
        jsonResponse(true, 'Collection updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO trx_otherscollection (CollectionDate, ParticularID, PartyName, Amount, PaymentMethodID, ReferenceNo, Narration, Remarks, CreatedBy) VALUES (?,?,?,?,?,?,?,?,?)", [$date, $particular, $party, $amount, $method, $ref, $narration, $remarks, getUserId()]);
        jsonResponse(true, 'Collection added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE trx_otherscollection SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE OthersCollectionID=?", [getUserId(), $id]);
    jsonResponse(true, 'Collection deleted successfully!');
}