<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Authentication required!');
}

$source = basename($_POST['source'] ?? '');
$id = intval($_POST['record_id'] ?? 0);

if ($source === '' || $id <= 0) {
    jsonResponse(false, 'Invalid record request!');
}

$map = [
    'shift_entry.php' => [
        'table' => 'mst_shift',
        'pk' => 'ShiftID',
        'fields' => ['ShiftID', 'ShiftName', 'StartTime', 'EndTime', 'IsActive'],
        'aliases' => ['ShiftID' => 'shift_id', 'ShiftName' => 'shift_name', 'StartTime' => 'start_time', 'EndTime' => 'end_time', 'IsActive' => 'is_active'],
    ],
    'fuel_type_entry.php' => [
        'table' => 'mst_fueltype',
        'pk' => 'FuelTypeID',
        'fields' => ['FuelTypeID', 'FuelName', 'FuelCode', 'UnitOfMeasure', 'SellingRate', 'PurchaseRate', 'CommissionRate', 'TaxPercent', 'Density', 'ColorCode', 'Remarks', 'IsActive'],
        'aliases' => ['FuelTypeID' => 'record_id', 'FuelName' => 'fuel_name', 'FuelCode' => 'fuel_code', 'UnitOfMeasure' => 'unit_of_measure', 'SellingRate' => 'selling_rate', 'PurchaseRate' => 'purchase_rate', 'CommissionRate' => 'commission_rate', 'TaxPercent' => 'tax_percent', 'Density' => 'density', 'ColorCode' => 'color_code', 'Remarks' => 'remarks', 'IsActive' => 'is_active'],
    ],
    'dispenser_entry.php' => [
        'table' => 'mst_dispenser',
        'pk' => 'DisID',
        'fields' => ['DisID', 'DisName', 'DisCode', 'IsActive'],
        'aliases' => ['DisID' => 'record_id', 'DisName' => 'dis_name', 'DisCode' => 'dis_code', 'IsActive' => 'is_active'],
    ],
    'tank_group_entry.php' => [
        'table' => 'mst_tankgroup',
        'pk' => 'TankGroupID',
        'fields' => ['TankGroupID', 'TankGroupName', 'FuelTypeID', 'IsActive'],
        'aliases' => ['TankGroupID' => 'record_id', 'TankGroupName' => 'group_name', 'FuelTypeID' => 'fuel_type_id', 'IsActive' => 'is_active'],
    ],
    'tank_entry.php' => [
        'table' => 'mst_tank',
        'pk' => 'TankID',
        'fields' => ['TankID', 'TankName', 'TankCode', 'TankGroupID', 'FuelTypeID', 'Capacity', 'MinLevel', 'OpeningStockPercent', 'Priority', 'IsActive'],
        'aliases' => ['TankID' => 'record_id', 'TankName' => 'tank_name', 'TankCode' => 'tank_code', 'TankGroupID' => 'tank_group_id', 'FuelTypeID' => 'fuel_type_id', 'Capacity' => 'capacity', 'MinLevel' => 'min_level', 'OpeningStockPercent' => 'opening_stock', 'Priority' => 'priority', 'IsActive' => 'is_active'],
    ],
    'nozzle_entry.php' => [
        'table' => 'mst_nozzle',
        'pk' => 'NozzleID',
        'fields' => ['NozzleID', 'NozzleName', 'NozzleNo', 'DisID', 'FuelTypeID', 'TankGroupID', 'OpeningGeneral', 'OpeningMaster', 'IsActive'],
        'aliases' => ['NozzleID' => 'record_id', 'NozzleName' => 'nozzle_name', 'NozzleNo' => 'nozzle_no', 'DisID' => 'dis_id', 'FuelTypeID' => 'fuel_type_id', 'TankGroupID' => 'tank_group_id', 'OpeningGeneral' => 'opening_general', 'OpeningMaster' => 'opening_master', 'IsActive' => 'is_active'],
    ],
    'bank_account_entry.php' => [
        'table' => 'mst_bankaccount',
        'pk' => 'BankAccountID',
        'fields' => ['BankAccountID', 'BankName', 'BranchName', 'AccountName', 'AccountNumber', 'AccountType', 'RoutingNumber', 'OpeningBalance', 'OpeningDate', 'CurrentBalance', 'IsActive'],
        'aliases' => ['BankAccountID' => 'record_id', 'BankName' => 'bank_name', 'BranchName' => 'branch_name', 'AccountName' => 'account_name', 'AccountNumber' => 'account_number', 'AccountType' => 'account_type', 'RoutingNumber' => 'routing_number', 'OpeningBalance' => 'opening_balance', 'OpeningDate' => 'opening_date', 'CurrentBalance' => 'current_balance', 'IsActive' => 'is_active'],
    ],
    'supplier_entry.php' => [
        'table' => 'mst_supplier',
        'pk' => 'SupplierID',
        'fields' => ['SupplierID', 'SupplierName', 'ContactPerson', 'Mobile', 'Email', 'Address', 'OpeningBalance', 'IsActive'],
        'aliases' => ['SupplierID' => 'record_id', 'SupplierName' => 'supplier_name', 'ContactPerson' => 'contact_person', 'Mobile' => 'mobile', 'Email' => 'email', 'Address' => 'address', 'OpeningBalance' => 'opening_balance', 'IsActive' => 'is_active'],
    ],
    'customer_entry.php' => [
        'table' => 'mst_customer',
        'pk' => 'CustomerID',
        'fields' => ['CustomerID', 'CustomerName', 'Mobile', 'VehicleNumber', 'Address', 'OpeningDue', 'IsActive'],
        'aliases' => ['CustomerID' => 'record_id', 'CustomerName' => 'customer_name', 'Mobile' => 'mobile', 'VehicleNumber' => 'vehicle_no', 'Address' => 'address', 'OpeningDue' => 'opening_due', 'IsActive' => 'is_active'],
    ],
    'employee_entry.php' => [
        'table' => 'mst_employee',
        'pk' => 'Id',
        'fields' => ['Id', 'EmployeeId', 'NameEN', 'NameBN', 'FatherName', 'MotherName', 'DateOfBirth', 'JoiningDate', 'Mobile', 'Address', 'NationalID', 'Guarantor', 'Salary', 'Remarks', 'IsActive'],
        'aliases' => ['Id' => 'record_id', 'EmployeeId' => 'emp_id', 'NameEN' => 'name_en', 'NameBN' => 'name_bn', 'FatherName' => 'father', 'MotherName' => 'mother', 'DateOfBirth' => 'dob', 'JoiningDate' => 'joining', 'Mobile' => 'mobile', 'Address' => 'address', 'NationalID' => 'nid', 'Guarantor' => 'guarantor', 'Salary' => 'salary', 'Remarks' => 'remarks', 'IsActive' => 'is_active'],
    ],
    'shareholder_entry.php' => [
        'table' => 'mst_shareholder',
        'pk' => 'Id',
        'fields' => ['Id', 'ShareHolderID', 'ExpenseCategoryID', 'NameEN', 'NameBN', 'Mobile', 'Address', 'InvestmentAmount', 'Remarks', 'IsActive'],
        'aliases' => ['Id' => 'record_id', 'ShareHolderID' => 'shareholder_id', 'ExpenseCategoryID' => 'expense_category_id', 'NameEN' => 'name_en', 'NameBN' => 'name_bn', 'Mobile' => 'mobile', 'Address' => 'address', 'InvestmentAmount' => 'investment', 'Remarks' => 'remarks', 'IsActive' => 'is_active'],
    ],
    'particular_group_entry.php' => [
        'table' => 'mst_expensecategory',
        'pk' => 'ExpenseCategoryID',
        'fields' => ['ExpenseCategoryID', 'CategoryNameEN', 'CategoryNameBN', 'IsActive'],
        'aliases' => ['ExpenseCategoryID' => 'record_id', 'CategoryNameEN' => 'name_en', 'CategoryNameBN' => 'name_bn', 'IsActive' => 'is_active'],
    ],
    'particular_entry.php' => [
        'table' => 'mst_expenseparticular',
        'pk' => 'ExpenseParticularID',
        'fields' => ['ExpenseParticularID', 'ParticularID', 'ExpenseCategoryID', 'ParticularNameEN', 'ParticularNameBN', 'IsActive'],
        'aliases' => ['ExpenseParticularID' => 'record_id', 'ParticularID' => 'particular_id', 'ExpenseCategoryID' => 'category_id', 'ParticularNameEN' => 'name_en', 'ParticularNameBN' => 'name_bn', 'IsActive' => 'is_active'],
    ],
    'nozzle_reading_entry.php' => [
        'table' => 'trx_nozzlereading',
        'pk' => 'NozzleReadingID',
        'fields' => ['NozzleReadingID', 'ReadingDate', 'ShiftID', 'DisID', 'NozzleID', 'GeneralReading', 'MasterReading', 'PreviousGeneral', 'PreviousMaster', 'Notes'],
        'aliases' => ['NozzleReadingID' => 'record_id', 'ReadingDate' => 'reading_date', 'ShiftID' => 'shift_id', 'DisID' => 'dis_id', 'NozzleID' => 'nozzle_id', 'GeneralReading' => 'general_reading', 'MasterReading' => 'master_reading', 'PreviousGeneral' => 'prev_general', 'PreviousMaster' => 'prev_master', 'Notes' => 'notes'],
    ],
    'customer_due_entry.php' => [
        'table' => 'trx_customerdue',
        'pk' => 'CustomerDueID',
        'fields' => ['CustomerDueID', 'TxnDate', 'CustomerID', 'FuelTypeID', 'VehicleNumber', 'Quantity', 'Rate', 'TotalAmount', 'PaidAmount', 'DueAmount', 'Remarks'],
        'aliases' => ['CustomerDueID' => 'record_id', 'TxnDate' => 'txn_date', 'CustomerID' => 'customer_id', 'FuelTypeID' => 'fuel_type_id', 'VehicleNumber' => 'vehicle_no', 'Quantity' => 'quantity', 'Rate' => 'rate', 'TotalAmount' => 'total_amount', 'PaidAmount' => 'paid_amount', 'DueAmount' => 'due_amount', 'Remarks' => 'remarks'],
    ],
    'due_collection_entry.php' => [
        'table' => 'trx_customercollection',
        'pk' => 'CustomerCollectionID',
        'fields' => ['CustomerCollectionID', 'TxnDate', 'CustomerID', 'Amount', 'PaymentMethodID', 'ReferenceNo', 'Remarks'],
        'aliases' => ['CustomerCollectionID' => 'record_id', 'TxnDate' => 'txn_date', 'CustomerID' => 'customer_id', 'Amount' => 'amount', 'PaymentMethodID' => 'payment_method', 'ReferenceNo' => 'reference_no', 'Remarks' => 'remarks'],
    ],
    'cash_collection_entry.php' => [
        'table' => 'trx_cashcollection',
        'pk' => 'CashCollectionID',
        'fields' => ['CashCollectionID', 'CollectionDate', 'CollectionTime', 'Amount', 'CollectedByType', 'CollectedPersonID', 'Remarks'],
        'aliases' => ['CashCollectionID' => 'record_id', 'CollectionDate' => 'collection_date', 'CollectionTime' => 'collection_time', 'Amount' => 'amount', 'CollectedByType' => 'collected_by_type', 'Remarks' => 'remarks'],
    ],
    'others_collection_entry.php' => [
        'table' => 'trx_otherscollection',
        'pk' => 'OthersCollectionID',
        'fields' => ['OthersCollectionID', 'CollectionDate', 'ParticularID', 'PartyName', 'Amount', 'PaymentMethodID', 'ReferenceNo', 'Narration', 'Remarks'],
        'aliases' => ['OthersCollectionID' => 'record_id', 'CollectionDate' => 'collection_date', 'ParticularID' => 'particular_id', 'PartyName' => 'party_name', 'Amount' => 'amount', 'PaymentMethodID' => 'payment_method', 'ReferenceNo' => 'reference_no', 'Narration' => 'narration', 'Remarks' => 'remarks'],
    ],
    'expense_entry.php' => [
        'table' => 'trx_expense',
        'pk' => 'ExpenseID',
        'fields' => ['ExpenseID', 'ExpenseDate', 'ParticularID', 'Amount', 'PaymentMethodID', 'ReferenceNo', 'Remarks'],
        'aliases' => ['ExpenseID' => 'record_id', 'ExpenseDate' => 'expense_date', 'ParticularID' => 'particular_id', 'Amount' => 'amount', 'PaymentMethodID' => 'payment_method', 'ReferenceNo' => 'reference_no', 'Remarks' => 'remarks'],
    ],
    'fuel_purchase_entry.php' => [
        'table' => 'trx_fuelpurchase',
        'pk' => 'FuelPurchaseID',
        'fields' => ['FuelPurchaseID', 'PurchaseDate', 'InvoiceNo', 'SupplierID', 'FuelTypeID', 'TankID', 'Quantity', 'Rate', 'Amount', 'TaxAmount', 'DiscountType', 'DiscountValue', 'DiscountAmount', 'TotalAmount', 'PaidAmount', 'PaymentStatus', 'PaymentMethodID', 'BankAccountID', 'PaymentRef', 'Remarks'],
        'aliases' => ['FuelPurchaseID' => 'record_id', 'PurchaseDate' => 'purchase_date', 'InvoiceNo' => 'invoice_no', 'SupplierID' => 'supplier_id', 'FuelTypeID' => 'fuel_type_id', 'TankID' => 'tank_id', 'Quantity' => 'quantity', 'Rate' => 'rate', 'Amount' => 'amount', 'TaxAmount' => 'tax_amount', 'DiscountType' => 'discount_type', 'DiscountValue' => 'discount_value', 'DiscountAmount' => 'discount_amount', 'TotalAmount' => 'total_amount', 'PaidAmount' => 'paid_amount', 'PaymentStatus' => 'payment_status', 'PaymentMethodID' => 'payment_method', 'BankAccountID' => 'bank_account_id', 'PaymentRef' => 'payment_ref', 'Remarks' => 'remarks'],
    ],
    'fuel_price_adjustment_entry.php' => [
        'table' => 'trx_fuelpriceadjustment',
        'pk' => 'FuelPriceAdjustmentID',
        'fields' => ['FuelPriceAdjustmentID', 'EffectiveDate', 'FuelTypeID', 'OldSellingPrice', 'NewSellingPrice', 'StockQuantity', 'ReferenceNo', 'Remarks'],
        'aliases' => ['FuelPriceAdjustmentID' => 'record_id', 'EffectiveDate' => 'effective_date', 'FuelTypeID' => 'fuel_type_id', 'OldSellingPrice' => 'old_price', 'NewSellingPrice' => 'new_price', 'StockQuantity' => 'stock_qty', 'ReferenceNo' => 'reference_no', 'Remarks' => 'remarks'],
    ],
    'supplier_payment_entry.php' => [
        'table' => 'trx_supplierpayment',
        'pk' => 'SupplierPaymentID',
        'fields' => ['SupplierPaymentID', 'PaymentDate', 'SupplierID', 'Amount', 'PaymentMethodID', 'ReferenceNo', 'Remarks'],
        'aliases' => ['SupplierPaymentID' => 'record_id', 'PaymentDate' => 'payment_date', 'SupplierID' => 'supplier_id', 'Amount' => 'amount', 'PaymentMethodID' => 'payment_method', 'ReferenceNo' => 'reference_no', 'Remarks' => 'remarks'],
    ],
    'tank_reading_entry.php' => [
        'table' => 'trx_tankreading',
        'pk' => 'TankReadingID',
        'fields' => ['TankReadingID', 'ReadingDate', 'TankID', 'PreviousReadingPercent', 'CurrentReadingPercent', 'TodaySoldPercent'],
        'aliases' => ['TankReadingID' => 'record_id', 'ReadingDate' => 'reading_date', 'TankID' => 'tank_id', 'PreviousReadingPercent' => 'prev_reading', 'CurrentReadingPercent' => 'current_reading', 'TodaySoldPercent' => 'sold_percent'],
    ],
    'stock_adjustment_entry.php' => [
        'table' => 'trx_stockadjustment',
        'pk' => 'StockAdjustmentID',
        'fields' => ['StockAdjustmentID', 'AdjustmentDate', 'TankID', 'AdjustmentType', 'Quantity', 'Reason', 'Remarks'],
        'aliases' => ['StockAdjustmentID' => 'record_id', 'AdjustmentDate' => 'adjustment_date', 'TankID' => 'tank_id', 'AdjustmentType' => 'adjustment_type', 'Quantity' => 'quantity', 'Reason' => 'reason', 'Remarks' => 'remarks'],
    ],
];

if (!isset($map[$source])) {
    jsonResponse(false, 'Record source is not allowed!');
}

$config = $map[$source];
$columns = implode(', ', array_map(static fn($field) => "`{$field}`", $config['fields']));
$rows = $objQuery->index("SELECT {$columns} FROM `{$config['table']}` WHERE `{$config['pk']}`=? AND IsDeleted=0 LIMIT 1", [$id]);

if (empty($rows)) {
    jsonResponse(false, 'Record not found!');
}

$row = $rows[0];
$formData = [];

foreach ($config['aliases'] as $dbField => $formField) {
    $formData[$formField] = $row->$dbField ?? '';
}

if ($source === 'cash_collection_entry.php') {
    $prefix = ($row->CollectedByType ?? '') === 'Employee' ? 'emp' : 'sh';
    $formData['collected_person_id'] = $prefix . '_' . ($row->CollectedPersonID ?? '');
}

if ($source === 'fuel_purchase_entry.php') {
    ensureFuelPurchaseTablesExist();
    $items = $objQuery->index("SELECT * FROM trx_purchase_details WHERE FuelPurchaseID = ? AND IsDeleted = 0 ORDER BY PurchaseDetailID ASC", [$id]);
    $formData['items'] = $items;
}

jsonResponse(true, 'Record loaded successfully!', ['data' => $formData]);
