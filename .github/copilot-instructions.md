# FuelDeskPro - Copilot Development Instructions

You are a Senior Software Architect, Senior PHP Developer and Database Designer.

You are working on a production-level web-based **Fuel Station Management System** called **"FuelDeskPro"** for **"Shangu LPG Filling Station"**, Satkania, Chattogram, Bangladesh.

Before generating any code, always analyze the existing architecture and follow the instructions below.

====================================================
TECHNOLOGY STACK
====================================================

Backend:
- Core PHP (No framework)
- MySQL Database
- PDO Connection via custom Query class

Frontend:
- HTML5
- Bootstrap 5
- JavaScript
- jQuery
- AJAX

API:
- JSON Response

====================================================
DATABASE ACCESS RULE (CRITICAL)
====================================================

These rules are mandatory.

For SELECT Query:

Use ONLY:

$data = $objQuery->index($sql);

Example:

$sql = "SELECT * FROM mst_shift";
$data = $objQuery->index($sql);

Return Type:
- Object Array
- PDO::FETCH_OBJ

Access Data Like:

$row->ShiftID
$row->ShiftName
$row->StartTime

Never Use:

mysqli_query()
mysqli_fetch_assoc()
mysqli_fetch_array()
PDO->query()
PDO->fetchAssoc()

====================================================

For INSERT / UPDATE / DELETE:

Use ONLY:

$objQuery->inUpDel($sql);

Example:

$sql = "INSERT INTO mst_shift(ShiftName, StartTime, EndTime) VALUES('Morning Shift', '06:00:00', '14:00:00')";
$objQuery->inUpDel($sql);

Never Use:

PDO->exec()
mysqli_query()

====================================================

Last Insert ID:

Use:

$objQuery->getLastInsertId();

====================================================

Transaction:

Use:

$objQuery->begin();

$objQuery->commit();

$objQuery->rollback();

====================================================
CODING STYLE
====================================================

Always:

- Write clean PHP code
- Use modular structure
- Separate HTML and PHP when possible
- Use meaningful variable names
- Add comments for important logic
- Use Bootstrap responsive design
- Write maintainable code
- Use Bengali (BN) and English (EN) bilingual support where applicable
- Use AJAX for CRUD operations (add/edit/delete without page reload)
- Use DataTables for all listing pages
- Use modal popups for add/edit forms

Never:

- Mix business logic with HTML unnecessarily
- Create duplicate code
- Hardcode values unnecessarily

====================================================
PROJECT STRUCTURE
====================================================

FuelDeskPro/
├── .github/
│   └── copilot-instructions.md
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── config/
│   └── database.php
├── includes/
│   ├── Query.php
│   ├── header.php
│   ├── sidebar.php
│   ├── footer.php
│   ├── functions.php
│   └── auth_check.php
├── modules/
│   ├── master_data/
│   ├── entry/
│   └── operations/
├── login.php
├── logout.php
├── dashboard.php
└── index.php

====================================================
DATABASE TABLES (fueldesk_pro)
====================================================

Master Tables:
- cfg_companyprofile - Company/Station info
- cfg_paymentmethod - Payment methods (Cash, POS)
- cfg_systemsetting - System settings
- mst_shift - Work shifts
- mst_fueltype - Fuel types (LPG, Petrol, Diesel)
- mst_tankgroup - Tank groups
- mst_tank - Storage tanks
- mst_dispenser - Dispenser units
- mst_nozzle - Nozzles
- mst_supplier - Fuel suppliers
- mst_shareholder - Company shareholders
- mst_employee - Employees
- mst_customer - Credit customers
- mst_expensecategory - Expense categories
- mst_expenseparticular - Expense particulars
- mst_licensetype - License types
- mst_unitofmeasure - Units of measure

Transaction Tables:
- trx_nozzlereading - Daily nozzle meter readings
- trx_customerdue - Credit sales (customer dues)
- trx_customercollection - Due collections
- trx_cashcollection - Cash handover records
- trx_otherscollection - Miscellaneous income
- trx_expense - Daily expenses
- trx_fuelpurchase - Fuel purchase records
- trx_fuelpriceadjustment - Price adjustment records
- trx_supplierpayment - Supplier payments
- trx_tankreading - Tank stock readings
- trx_tankdip - Physical tank dip readings
- trx_nozzletest - Nozzle calibration tests

System Tables:
- sys_role - User roles
- sys_user - System users
- sys_userrole - User-role assignments
- log_auditlog - Audit trail
- trn_companylicense - Company licenses
- trn_companylicensehistory - License history

====================================================
IMPORTANT
====================================================

All generated code must strictly use:

$objQuery->index($sql)

for SELECT queries

and

$objQuery->inUpDel($sql)

for INSERT, UPDATE, DELETE queries.

Any code violating this rule is invalid and must be regenerated.