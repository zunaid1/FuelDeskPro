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
    $bankAccountId = !empty($_POST['bank_account_id']) ? intval($_POST['bank_account_id']) : null;
    $remarks = sanitize($_POST['remarks'] ?? '');
    if (empty($date) || $amount <= 0 || empty($type) || empty($personRaw)) jsonResponse(false, 'Required fields missing!');
    if (isStatementClosed($date)) jsonResponse(false, 'এই তারিখের ('.date('d-m-Y', strtotime($date)).') হিসাবটি ইতোমধ্যে ফাইনাল সাবমিট (ক্লোজ) করা হয়েছে! নতুন ডাটা সেভ বা আপডেট করা সম্ভব নয়।');
    $parts = explode('_', $personRaw);
    $personId = intval($parts[1] ?? 0);
    if ($personId <= 0) jsonResponse(false, 'Invalid person!');

    // Ensure BankAccountID column exists
    try {
        $checkCol = $objQuery->index("SHOW COLUMNS FROM `trx_cashcollection` LIKE 'BankAccountID'");
        if (empty($checkCol)) {
            $objQuery->inUpDel("ALTER TABLE `trx_cashcollection` ADD COLUMN `BankAccountID` INT(11) NULL DEFAULT NULL AFTER `CollectedPersonID`");
        }
    } catch (Exception $e) {}

    if ($id > 0) {
        $objQuery->inUpDel("UPDATE trx_cashcollection SET CollectionDate=?, CollectionTime=?, Amount=?, CollectedByType=?, CollectedPersonID=?, BankAccountID=?, Remarks=?, UpdatedBy=?, UpdatedAt=NOW() WHERE CashCollectionID=? AND IsDeleted=0", [$date, $time, $amount, $type, $personId, $bankAccountId, $remarks, getUserId(), $id]);
        jsonResponse(true, 'Cash collection updated successfully!');
    } else {
        $objQuery->inUpDel("INSERT INTO trx_cashcollection (CollectionDate, CollectionTime, Amount, CollectedByType, CollectedPersonID, BankAccountID, Remarks, CreatedBy) VALUES (?,?,?,?,?,?,?,?)", [$date, $time, $amount, $type, $personId, $bankAccountId, $remarks, getUserId()]);
        jsonResponse(true, 'Cash collection added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $rec = $objQuery->index("SELECT CollectionDate FROM trx_cashcollection WHERE CashCollectionID=?", [$id]);
    if (!empty($rec) && isStatementClosed($rec[0]->CollectionDate)) {
        jsonResponse(false, 'এই তারিখের ('.date('d-m-Y', strtotime($rec[0]->CollectionDate)).') হিসাবটি ইতোমধ্যে ফাইনাল সাবমিট (ক্লোজ) করা হয়েছে! এই তারিখের ডাটা মোছা সম্ভব নয়।');
    }
    $objQuery->inUpDel("UPDATE trx_cashcollection SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE CashCollectionID=?", [getUserId(), $id]);
    jsonResponse(true, 'Cash collection deleted successfully!');
}