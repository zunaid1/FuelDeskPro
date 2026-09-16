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
    $bankName = sanitize($_POST['bank_name'] ?? '');
    $branchName = sanitize($_POST['branch_name'] ?? '');
    $accountName = sanitize($_POST['account_name'] ?? '');
    $accountNumber = sanitize($_POST['account_number'] ?? '');
    $accountType = sanitize($_POST['account_type'] ?? 'Current');
    $routingNumber = sanitize($_POST['routing_number'] ?? '');
    $openingBalance = floatval($_POST['opening_balance'] ?? 0);
    $openingDate = $_POST['opening_date'] ?? date('Y-m-d');
    $isActive = intval($_POST['is_active'] ?? 1);

    if (empty($bankName) || empty($accountName) || empty($accountNumber)) {
        jsonResponse(false, 'Bank Name, Account Name and Account Number are required!');
    }

    // Auto-ensure table exists
    try {
        $objQuery->inUpDel("CREATE TABLE IF NOT EXISTS `mst_bankaccount` (
          `BankAccountID` INT AUTO_INCREMENT PRIMARY KEY,
          `BankName` VARCHAR(150) NOT NULL,
          `BranchName` VARCHAR(150) DEFAULT NULL,
          `AccountName` VARCHAR(150) NOT NULL,
          `AccountNumber` VARCHAR(50) NOT NULL,
          `AccountType` VARCHAR(50) DEFAULT 'Current',
          `RoutingNumber` VARCHAR(50) DEFAULT NULL,
          `OpeningBalance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          `OpeningDate` DATE DEFAULT NULL,
          `CurrentBalance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          `IsDefault` TINYINT(1) DEFAULT 0,
          `CreatedBy` INT DEFAULT NULL,
          `CreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `UpdatedBy` INT DEFAULT NULL,
          `UpdatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `IsActive` TINYINT(1) DEFAULT 1,
          `IsDeleted` TINYINT(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } catch (Exception $e) {}

    if ($id > 0) {
        // Calculate balance delta if opening balance changed
        $existing = $objQuery->index("SELECT OpeningBalance, CurrentBalance FROM mst_bankaccount WHERE BankAccountID=?", [$id]);
        $currentBalance = $openingBalance;
        if (!empty($existing)) {
            $oldOpening = floatval($existing[0]->OpeningBalance);
            $oldCurrent = floatval($existing[0]->CurrentBalance);
            $diff = $openingBalance - $oldOpening;
            $currentBalance = $oldCurrent + $diff;
        }

        $objQuery->inUpDel(
            "UPDATE mst_bankaccount SET BankName=?, BranchName=?, AccountName=?, AccountNumber=?, AccountType=?, RoutingNumber=?, OpeningBalance=?, OpeningDate=?, CurrentBalance=?, IsActive=?, UpdatedBy=?, UpdatedAt=NOW() WHERE BankAccountID=? AND IsDeleted=0",
            [$bankName, $branchName, $accountName, $accountNumber, $accountType, $routingNumber, $openingBalance, $openingDate, $currentBalance, $isActive, getUserId(), $id]
        );
        jsonResponse(true, 'Bank Account updated successfully!');
    } else {
        $currentBalance = $openingBalance;
        $objQuery->inUpDel(
            "INSERT INTO mst_bankaccount (BankName, BranchName, AccountName, AccountNumber, AccountType, RoutingNumber, OpeningBalance, OpeningDate, CurrentBalance, IsActive, CreatedBy) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [$bankName, $branchName, $accountName, $accountNumber, $accountType, $routingNumber, $openingBalance, $openingDate, $currentBalance, $isActive, getUserId()]
        );
        jsonResponse(true, 'Bank Account added successfully!');
    }
}

function handleDelete() {
    global $objQuery;
    $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $objQuery->inUpDel("UPDATE mst_bankaccount SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE BankAccountID=?", [getUserId(), $id]);
    jsonResponse(true, 'Bank Account deleted successfully!');
}
