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
    $shift = intval($_POST['shift_id'] ?? 0);
    $dis = intval($_POST['dis_id'] ?? 0);
    $nozzle = intval($_POST['nozzle_id'] ?? 0);
    $general = floatval($_POST['general_reading'] ?? 0);
    $master = floatval($_POST['master_reading'] ?? 0);
    $prevGeneral = floatval($_POST['prev_general'] ?? 0);
    $prevMaster = floatval($_POST['prev_master'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($date) || !$shift || !$dis || !$nozzle || $general <= 0 || $master <= 0) {
        jsonResponse(false, 'Required fields missing!');
    }
    if (isStatementClosed($date)) {
        jsonResponse(false, 'এই তারিখের ('.date('d-m-Y', strtotime($date)).') হিসাবটি ইতোমধ্যে ক্লোজ করা হয়েছে!');
    }


    // Retrieve rates from mst_fueltype via mst_nozzle
    $sellingRate = 0.00;
    $purchaseRate = 0.00;
    $commissionRate = 0.00;

    $fuelTypeData = $objQuery->index(
        "SELECT f.SellingRate, f.PurchaseRate, f.CommissionRate 
         FROM mst_nozzle n 
         INNER JOIN mst_fueltype f ON n.FuelTypeID = f.FuelTypeID 
         WHERE n.NozzleID = ?", 
        [$nozzle]
    );

    if (!empty($fuelTypeData)) {
        $sellingRate = floatval($fuelTypeData[0]->SellingRate);
        $purchaseRate = floatval($fuelTypeData[0]->PurchaseRate);
        $commissionRate = floatval($fuelTypeData[0]->CommissionRate);
    }

    // Allow user to override the selling rate (defaults to fuel type rate above)
    $userSellingRate = floatval($_POST['selling_rate'] ?? 0);
    if ($userSellingRate > 0) {
        $sellingRate = $userSellingRate;
    }

    // Calculative fields
    $saleQuantity = round(max(0, $general - $prevGeneral), 3);
    $salesAmt = round($saleQuantity * $sellingRate, 2);
    $commissionAmt = round($saleQuantity * $commissionRate, 2);

    if ($id > 0) {
        $objQuery->inUpDel(
            "UPDATE trx_nozzlereading 
             SET ReadingDate=?, ShiftID=?, DisID=?, NozzleID=?, GeneralReading=?, MasterReading=?, PreviousGeneral=?, PreviousMaster=?, 
                 SaleQuantity=?, SellingRate=?, PurchaseRate=?, SalesAmt=?, CommissionRate=?, CommissionAmt=?, 
                 Notes=?, UpdatedBy=?, UpdatedAt=NOW() 
             WHERE NozzleReadingID=? AND IsDeleted=0", 
            [
                $date, $shift, $dis, $nozzle, $general, $master, $prevGeneral, $prevMaster, 
                $saleQuantity, $sellingRate, $purchaseRate, $salesAmt, $commissionRate, $commissionAmt, 
                $notes, getUserId(), $id
            ]
        );
        jsonResponse(true, 'Reading updated successfully!');
    } else {
        $check = $objQuery->index(
            "SELECT NozzleReadingID FROM trx_nozzlereading WHERE NozzleID=? AND ReadingDate=? AND ShiftID=? AND IsDeleted=0", 
            [$nozzle, $date, $shift]
        );
        if (!empty($check)) jsonResponse(false, 'Reading already exists for this nozzle/date/shift!');
        
        $objQuery->inUpDel(
            "INSERT INTO trx_nozzlereading (
                ReadingDate, ShiftID, StationID, DisID, NozzleID, GeneralReading, MasterReading, PreviousGeneral, PreviousMaster, 
                SaleQuantity, SellingRate, PurchaseRate, SalesAmt, CommissionRate, CommissionAmt, Notes, CreatedBy
            ) VALUES (?,?,1,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", 
            [
                $date, $shift, $dis, $nozzle, 
                $general, $master, $prevGeneral, $prevMaster, 
                $saleQuantity, $sellingRate, $purchaseRate, $salesAmt, $commissionRate, $commissionAmt, 
                $notes, getUserId()
            ]
        );
        jsonResponse(true, 'Reading added successfully!');
    }
}
function handleDelete() {
    global $objQuery; $id = intval($_POST['record_id'] ?? 0);
    if ($id <= 0) jsonResponse(false, 'Invalid ID!');
    $rec = $objQuery->index("SELECT ReadingDate FROM trx_nozzlereading WHERE NozzleReadingID=?", [$id]);
    if (!empty($rec) && isStatementClosed($rec[0]->ReadingDate)) {
        jsonResponse(false, 'এই তারিখের ('.date('d-m-Y', strtotime($rec[0]->ReadingDate)).') হিসাবটি ইতোমধ্যে ফাইনাল সাবমিট (ক্লোজ) করা হয়েছে! এই তারিখের ডাটা মোছা সম্ভব নয়।');
    }
    $objQuery->inUpDel("UPDATE trx_nozzlereading SET IsDeleted=1, UpdatedBy=?, UpdatedAt=NOW() WHERE NozzleReadingID=?", [getUserId(), $id]);
    jsonResponse(true, 'Reading deleted successfully!');
}
function getPreviousReading() {
    global $objQuery;
    $nozzleId = intval($_POST['nozzle_id'] ?? 0);
    $readingDate = sanitize($_POST['reading_date'] ?? '');
    
    if ($nozzleId <= 0) jsonResponse(false, 'Invalid nozzle!');

    // Get default selling rate from fuel type via nozzle
    $sellingRate = 0.00;
    $fuelData = $objQuery->index(
        "SELECT f.SellingRate FROM mst_nozzle n 
         INNER JOIN mst_fueltype f ON n.FuelTypeID = f.FuelTypeID 
         WHERE n.NozzleID = ? AND n.IsDeleted = 0 AND f.IsDeleted = 0",
        [$nozzleId]
    );
    if (!empty($fuelData)) {
        $sellingRate = floatval($fuelData[0]->SellingRate);
    }

    $prevGeneral = 0;
    $prevMaster  = 0;
    $found = false;

    if (!empty($readingDate)) {
        // 1. Calculate 1 day prior date
        $targetPrevDate = date('Y-m-d', strtotime($readingDate . ' -1 day'));

        // 2. Check if reading exists for exact previous day
        $exactPrev = $objQuery->index(
            "SELECT GeneralReading, MasterReading 
             FROM trx_nozzlereading 
             WHERE NozzleID = ? AND ReadingDate = ? AND IsDeleted = 0 
             ORDER BY NozzleReadingID DESC LIMIT 1",
            [$nozzleId, $targetPrevDate]
        );

        if (!empty($exactPrev)) {
            $prevGeneral = $exactPrev[0]->GeneralReading;
            $prevMaster  = $exactPrev[0]->MasterReading;
            $found = true;
        } else {
            // 3. If exact previous day reading not found, find max date reading prior to selected date
            $priorLatest = $objQuery->index(
                "SELECT GeneralReading, MasterReading 
                 FROM trx_nozzlereading 
                 WHERE NozzleID = ? AND ReadingDate < ? AND IsDeleted = 0 
                 ORDER BY ReadingDate DESC, NozzleReadingID DESC LIMIT 1",
                [$nozzleId, $readingDate]
            );

            if (!empty($priorLatest)) {
                $prevGeneral = $priorLatest[0]->GeneralReading;
                $prevMaster  = $priorLatest[0]->MasterReading;
                $found = true;
            }
        }
    }

    // 4. Fallback to latest overall reading if not yet found
    if (!$found) {
        $lastOverall = $objQuery->index(
            "SELECT GeneralReading, MasterReading 
             FROM trx_nozzlereading 
             WHERE NozzleID = ? AND IsDeleted = 0 
             ORDER BY ReadingDate DESC, NozzleReadingID DESC LIMIT 1",
            [$nozzleId]
        );
        if (!empty($lastOverall)) {
            $prevGeneral = $lastOverall[0]->GeneralReading;
            $prevMaster  = $lastOverall[0]->MasterReading;
            $found = true;
        }
    }

    // 5. Final fallback to Opening Readings from master data table
    if (!$found) {
        $nozzle = $objQuery->index("SELECT OpeningGeneral, OpeningMaster FROM mst_nozzle WHERE NozzleID = ?", [$nozzleId]);
        if (!empty($nozzle)) {
            $prevGeneral = $nozzle[0]->OpeningGeneral;
            $prevMaster  = $nozzle[0]->OpeningMaster;
        }
    }

    jsonResponse(true, '', [
        'prev_general' => $prevGeneral,
        'prev_master'  => $prevMaster,
        'selling_rate' => $sellingRate
    ]);
}
