<?php
/**
 * FuelDeskPro - Multi-Item Fuel Purchase Entry Processor
 * 
 * Handles multi-item purchase transactions, updating trx_fuelpurchase,
 * trx_purchase_details, and trx_stock_in.
 * 
 * @package FuelDeskPro
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Authentication required!');
}

ensureFuelPurchaseTablesExist();

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

function handleSave()
{
    global $objQuery;

    $id             = intval($_POST['record_id'] ?? 0);
    $date           = sanitize($_POST['purchase_date'] ?? '');
    $invoice        = sanitize($_POST['invoice_no'] ?? '');
    $supplier       = intval($_POST['supplier_id'] ?? 0);
    $status         = sanitize($_POST['payment_status'] ?? 'Due');
    $paidAmount     = floatval($_POST['paid_amount'] ?? 0);
    $paymentMethod  = intval($_POST['payment_method'] ?? 0);
    $bankAccountId  = !empty($_POST['bank_account_id']) ? intval($_POST['bank_account_id']) : null;
    $paymentRef     = sanitize($_POST['payment_ref'] ?? '');
    $remarks        = sanitize($_POST['remarks'] ?? '');
    $tax            = floatval($_POST['tax_amount'] ?? 0);
    $discountType   = sanitize($_POST['discount_type'] ?? 'Fixed');
    $discountValue  = floatval($_POST['discount_value'] ?? 0);
    $discountAmount = floatval($_POST['discount_amount'] ?? 0);
    $userId         = getUserId();

    if (!in_array($discountType, ['Percentage', 'Fixed'])) {
        $discountType = 'Fixed';
    }

    $fuelTypeIds = $_POST['fuel_type_id'] ?? [];
    $tankIds     = $_POST['tank_id'] ?? [];
    $quantities  = $_POST['quantity'] ?? [];
    $rates       = $_POST['rate'] ?? [];
    $amounts     = $_POST['amount'] ?? [];

    if (empty($date) || !$supplier) {
        jsonResponse(false, 'Date and Supplier are required!');
    }

    if (!is_array($fuelTypeIds) || empty($fuelTypeIds)) {
        jsonResponse(false, 'Please add at least one line item!');
    }

    $validItems = [];
    $subTotal = 0;

    for ($i = 0; $i < count($fuelTypeIds); $i++) {
        $fId  = intval($fuelTypeIds[$i] ?? 0);
        $tId  = intval($tankIds[$i] ?? 0);
        $qty  = floatval($quantities[$i] ?? 0);
        $rate = floatval($rates[$i] ?? 0);
        $amt  = floatval($amounts[$i] ?? 0);

        if ($amt <= 0 && $qty > 0 && $rate > 0) {
            $amt = round($qty * $rate, 2);
        }

        if ($fId > 0 && $tId > 0 && $qty > 0 && $rate > 0) {
            $subTotal += $amt;
            $validItems[] = [
                'fuel_type_id' => $fId,
                'tank_id'      => $tId,
                'quantity'     => $qty,
                'rate'         => $rate,
                'amount'       => $amt
            ];
        }
    }

    if (empty($validItems)) {
        jsonResponse(false, 'Please enter valid fuel, tank, quantity, and rate for at least one item!');
    }

    // Auto calculate discount amount if percentage mode or fallback
    if ($discountType === 'Percentage' && $discountValue > 0) {
        $discountAmount = round($subTotal * ($discountValue / 100), 2);
    } elseif ($discountType === 'Fixed' && $discountValue > 0 && $discountAmount <= 0) {
        $discountAmount = round($discountValue, 2);
    }

    $totalAmount = round(max(0, $subTotal - $discountAmount + $tax), 2);

    // Sync Payment Status with Paid Amount
    if ($paidAmount <= 0) {
        $status = 'Due';
        $paidAmount = 0.00;
    } elseif ($paidAmount >= $totalAmount && $totalAmount > 0) {
        $status = 'Paid';
    } else {
        $status = 'Partial';
    }

    // Fallback default payment method if paying but method not specified
    if ($paidAmount > 0 && $paymentMethod <= 0) {
        $defMethod = $objQuery->index("SELECT PaymentMethodID FROM cfg_paymentmethod WHERE IsActive=1 AND IsDeleted=0 ORDER BY PaymentMethodID ASC LIMIT 1");
        if (!empty($defMethod)) {
            $paymentMethod = intval($defMethod[0]->PaymentMethodID);
        }
    }

    try {
        $objQuery->begin();

        if ($id > 0) {
            $sqlHeader = "UPDATE trx_fuelpurchase SET 
                PurchaseDate = ?, 
                InvoiceNo = ?, 
                SupplierID = ?, 
                Amount = ?, 
                TaxAmount = ?, 
                DiscountType = ?, 
                DiscountValue = ?, 
                DiscountAmount = ?, 
                TotalAmount = ?, 
                PaidAmount = ?, 
                PaymentMethodID = ?, 
                BankAccountID = ?, 
                PaymentRef = ?, 
                PaymentStatus = ?, 
                Remarks = ?, 
                UpdatedBy = ?, 
                UpdatedAt = NOW() 
                WHERE FuelPurchaseID = ? AND IsDeleted = 0";
            $objQuery->inUpDel($sqlHeader, [$date, $invoice, $supplier, $subTotal, $tax, $discountType, $discountValue, $discountAmount, $totalAmount, $paidAmount, $paymentMethod, $bankAccountId, $paymentRef, $status, $remarks, $userId, $id]);
            $fuelPurchaseId = $id;

            // Soft-delete existing line items & stock-in records for update
            $objQuery->inUpDel("UPDATE trx_purchase_details SET IsDeleted = 1 WHERE FuelPurchaseID = ?", [$fuelPurchaseId]);
            $objQuery->inUpDel("UPDATE trx_stock_in SET IsDeleted = 1 WHERE ReferenceType = 'FuelPurchase' AND ReferenceID = ?", [$fuelPurchaseId]);
        } else {
            $sqlHeader = "INSERT INTO trx_fuelpurchase 
                (PurchaseDate, InvoiceNo, SupplierID, Amount, TaxAmount, DiscountType, DiscountValue, DiscountAmount, TotalAmount, PaidAmount, PaymentMethodID, BankAccountID, PaymentRef, PaymentStatus, Remarks, CreatedBy) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $objQuery->inUpDel($sqlHeader, [$date, $invoice, $supplier, $subTotal, $tax, $discountType, $discountValue, $discountAmount, $totalAmount, $paidAmount, $paymentMethod, $bankAccountId, $paymentRef, $status, $remarks, $userId]);
            $fuelPurchaseId = intval($objQuery->getLastInsertId());
        }

        // Insert line items & stock receiving records
        foreach ($validItems as $item) {
            $sqlDetail = "INSERT INTO trx_purchase_details 
                (FuelPurchaseID, FuelTypeID, TankID, Quantity, Rate, Amount) 
                VALUES (?, ?, ?, ?, ?, ?)";
            $objQuery->inUpDel($sqlDetail, [$fuelPurchaseId, $item['fuel_type_id'], $item['tank_id'], $item['quantity'], $item['rate'], $item['amount']]);
            $detailId = intval($objQuery->getLastInsertId());

            $sqlStock = "INSERT INTO trx_stock_in 
                (StockInDate, ReferenceType, ReferenceID, ReferenceDetailID, FuelTypeID, TankID, Quantity, UnitRate, TotalValue, Remarks, CreatedBy) 
                VALUES (?, 'FuelPurchase', ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $objQuery->inUpDel($sqlStock, [$date, $fuelPurchaseId, $detailId, $item['fuel_type_id'], $item['tank_id'], $item['quantity'], $item['rate'], $item['amount'], $remarks, $userId]);
        }

        // Manage Supplier Payment record in trx_supplierpayment
        $refNo = !empty($paymentRef) ? $paymentRef : ($invoice ? "Inv: " . $invoice : "Purchase #" . $fuelPurchaseId);
        $existingSp = $objQuery->index("SELECT SupplierPaymentID FROM trx_supplierpayment WHERE FuelPurchaseID = ? AND IsDeleted = 0 LIMIT 1", [$fuelPurchaseId]);

        if ($paidAmount > 0) {
            if (!empty($existingSp)) {
                $spId = intval($existingSp[0]->SupplierPaymentID);
                $sqlSp = "UPDATE trx_supplierpayment SET 
                    PaymentDate = ?, 
                    SupplierID = ?, 
                    Amount = ?, 
                    PaymentMethodID = ?, 
                    BankAccountID = ?, 
                    ReferenceNo = ?, 
                    Remarks = ?, 
                    UpdatedBy = ?, 
                    UpdatedAt = NOW() 
                    WHERE SupplierPaymentID = ? AND IsDeleted = 0";
                $objQuery->inUpDel($sqlSp, [$date, $supplier, $paidAmount, $paymentMethod, $bankAccountId, $refNo, $remarks, $userId, $spId]);
            } else {
                $sqlSp = "INSERT INTO trx_supplierpayment 
                    (FuelPurchaseID, PaymentDate, SupplierID, Amount, PaymentMethodID, BankAccountID, ReferenceNo, Remarks, CreatedBy) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $objQuery->inUpDel($sqlSp, [$fuelPurchaseId, $date, $supplier, $paidAmount, $paymentMethod, $bankAccountId, $refNo, $remarks, $userId]);
            }
        } else {
            if (!empty($existingSp)) {
                $spId = intval($existingSp[0]->SupplierPaymentID);
                $objQuery->inUpDel("UPDATE trx_supplierpayment SET IsDeleted = 1, UpdatedBy = ?, UpdatedAt = NOW() WHERE SupplierPaymentID = ?", [$userId, $spId]);
            }
        }

        $objQuery->commit();
        jsonResponse(true, $id > 0 ? 'Purchase updated successfully!' : 'Purchase saved successfully!');
    } catch (\Throwable $e) {
        $objQuery->rollback();
        jsonResponse(false, 'Database Error: ' . $e->getMessage());
    }
}

function handleDelete()
{
    global $objQuery;

    $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(false, 'Invalid Purchase ID!');
    }

    $userId = getUserId();

    try {
        $objQuery->begin();
        $objQuery->inUpDel("UPDATE trx_fuelpurchase SET IsDeleted = 1, UpdatedBy = ?, UpdatedAt = NOW() WHERE FuelPurchaseID = ?", [$userId, $id]);
        $objQuery->inUpDel("UPDATE trx_purchase_details SET IsDeleted = 1 WHERE FuelPurchaseID = ?", [$id]);
        $objQuery->inUpDel("UPDATE trx_stock_in SET IsDeleted = 1 WHERE ReferenceType = 'FuelPurchase' AND ReferenceID = ?", [$id]);
        $objQuery->inUpDel("UPDATE trx_supplierpayment SET IsDeleted = 1, UpdatedBy = ?, UpdatedAt = NOW() WHERE FuelPurchaseID = ?", [$userId, $id]);
        $objQuery->commit();

        jsonResponse(true, 'Purchase deleted successfully!');
    } catch (\Throwable $e) {
        $objQuery->rollback();
        jsonResponse(false, 'Error deleting record: ' . $e->getMessage());
    }
}