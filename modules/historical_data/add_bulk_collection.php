<?php
/**
 * FuelDeskPro - Historical Data Entry / Add Bulk Collection (.csv Import)
 *
 * Allows uploading bulk historical sales, collection, and commission records prior to software live.
 * Saves data into database table: trx_historical_bulk_collection
 *
 * @package FuelDeskPro
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

global $objQuery;
if (!isset($objQuery) || $objQuery === null) {
    $objQuery = db();
}

// Ensure database table exists
ensureHistoricalBulkCollectionTableExists();

$pageTitle = t('Add Bulk Collection') . ' - FuelDeskPro';

$msg = '';
$msgType = 'success';

// Handle Sample CSV Download
if (isset($_GET['action']) && $_GET['action'] === 'download_sample') {
    $filename = "sample_bulk_collection.csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'SalesQty', 'SalesAmount', 'OtherCollection', 'TotalCollection', 'CommissionRate', 'CommissionAmount', 'Remarks']);
    fputcsv($out, [date('Y-m-01'), '1000.00', '135000.00', '5000.00', '140000.00', '1.50', '2025.00', 'Sample Bulk Record 1']);
    fputcsv($out, [date('Y-m-02'), '1200.00', '162000.00', '0.00', '162000.00', '1.50', '2430.00', 'Sample Bulk Record 2']);
    fclose($out);
    exit;
}

// Handle Single Entry Delete (Soft Delete)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = intval($_GET['id']);
    if ($delId > 0) {
        $objQuery->inUpDel("UPDATE trx_historical_bulk_collection SET IsDeleted = 1, UpdatedAt = NOW() WHERE Id = ?", [$delId]);
        $_SESSION['flash_msg'] = t('Record deleted successfully.');
        $_SESSION['flash_type'] = 'success';
        header("Location: add_bulk_collection.php");
        exit;
    }
}

// Handle Single Manual Record Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_single') {
    $txnDate          = trim($_POST['TxnDate'] ?? '');
    $salesQty         = floatval($_POST['SalesQty'] ?? 0);
    $salesAmount      = floatval($_POST['SalesAmount'] ?? 0);
    $otherCollection  = floatval($_POST['OtherCollection'] ?? 0);
    $totalCollection  = floatval($_POST['TotalCollection'] ?? 0);
    $commissionRate   = floatval($_POST['CommissionRate'] ?? 0);
    $commissionAmount = floatval($_POST['CommissionAmount'] ?? 0);
    $remarks          = trim($_POST['Remarks'] ?? '');

    if (empty($txnDate)) {
        $msg = t('Please select a valid transaction date.');
        $msgType = 'danger';
    } else {
        $rate = ($salesQty > 0) ? ($salesAmount / $salesQty) : 0.00;
        if ($totalCollection <= 0) {
            $totalCollection = $salesAmount + $otherCollection;
        }
        if ($commissionAmount <= 0) {
            $commissionAmount = $salesQty * $commissionRate;
        }

        $userId = $_SESSION['user_id'] ?? 1;

        $insertSql = "INSERT INTO trx_historical_bulk_collection 
            (TxnDate, SalesQty, SalesAmount, Rate, OtherCollection, TotalCollection, CommissionRate, CommissionAmount, Remarks, CreatedBy, CreatedAt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $objQuery->inUpDel($insertSql, [
            $txnDate, $salesQty, $salesAmount, $rate, $otherCollection, $totalCollection, $commissionRate, $commissionAmount, $remarks, $userId
        ]);

        $_SESSION['flash_msg'] = t('Bulk collection record added successfully.');
        $_SESSION['flash_type'] = 'success';
        header("Location: add_bulk_collection.php");
        exit;
    }
}

// Handle Single Manual Record Edit / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_single') {
    $recordId         = intval($_POST['RecordId'] ?? 0);
    $txnDate          = trim($_POST['TxnDate'] ?? '');
    $salesQty         = floatval($_POST['SalesQty'] ?? 0);
    $salesAmount      = floatval($_POST['SalesAmount'] ?? 0);
    $otherCollection  = floatval($_POST['OtherCollection'] ?? 0);
    $totalCollection  = floatval($_POST['TotalCollection'] ?? 0);
    $commissionRate   = floatval($_POST['CommissionRate'] ?? 0);
    $commissionAmount = floatval($_POST['CommissionAmount'] ?? 0);
    $remarks          = trim($_POST['Remarks'] ?? '');

    if ($recordId > 0 && !empty($txnDate)) {
        $rate = ($salesQty > 0) ? ($salesAmount / $salesQty) : 0.00;
        if ($totalCollection <= 0) {
            $totalCollection = $salesAmount + $otherCollection;
        }
        if ($commissionAmount <= 0) {
            $commissionAmount = $salesQty * $commissionRate;
        }
        $userId = $_SESSION['user_id'] ?? 1;

        $updateSql = "UPDATE trx_historical_bulk_collection SET 
            TxnDate = ?, SalesQty = ?, SalesAmount = ?, Rate = ?, OtherCollection = ?, TotalCollection = ?, CommissionRate = ?, CommissionAmount = ?, Remarks = ?, UpdatedBy = ?, UpdatedAt = NOW()
            WHERE Id = ?";

        $objQuery->inUpDel($updateSql, [
            $txnDate, $salesQty, $salesAmount, $rate, $otherCollection, $totalCollection, $commissionRate, $commissionAmount, $remarks, $userId, $recordId
        ]);

        $_SESSION['flash_msg'] = t('Record updated successfully.');
        $_SESSION['flash_type'] = 'success';
        header("Location: add_bulk_collection.php");
        exit;
    }
}

// Helper to normalize uploaded dates (supports Excel serial numbers, YYYY-MM-DD, DD-MM-YYYY, etc.)
function parseHistoricalDate($dateStr) {
    $dateStr = trim((string)$dateStr);
    if (empty($dateStr)) return date('Y-m-d');

    // 1. Check Excel Serial Date (e.g. 45413)
    if (is_numeric($dateStr) && floatval($dateStr) > 30000 && floatval($dateStr) < 70000) {
        $excelDate = floatval($dateStr);
        $unixTimestamp = ($excelDate - 25569) * 86400;
        return date('Y-m-d', $unixTimestamp);
    }

    // 2. Format YYYY-MM-DD or YYYY/MM/DD
    if (preg_match('/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}$/', $dateStr)) {
        return date('Y-m-d', strtotime(str_replace('/', '-', $dateStr)));
    }

    // 3. Format DD-MM-YYYY or DD/MM/YYYY
    if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $dateStr, $matches)) {
        $day = intval($matches[1]);
        $month = intval($matches[2]);
        $year = intval($matches[3]);
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    $ts = strtotime($dateStr);
    return ($ts !== false) ? date('Y-m-d', $ts) : date('Y-m-d');
}

// Helper to map CSV/Excel header columns accurately
function mapBulkCollectionHeaders($headerRow) {
    $colMap = [
        'date'             => null,
        'salesqty'         => null,
        'salesamount'      => null,
        'othercollection'  => null,
        'totalcollection'  => null,
        'commissionrate'   => null,
        'commissionamount' => null,
        'remarks'          => null
    ];

    foreach ($headerRow as $idx => $cell) {
        $text = strtolower(trim((string)$cell));
        $cleanText = preg_replace('/[^a-z0-9]/', '', $text);

        // Date
        if ($colMap['date'] === null && (strpos($text, 'date') !== false || strpos($text, 'তারিখ') !== false)) {
            $colMap['date'] = $idx;
        }
        // Sales Quantity (Liter)
        elseif ($colMap['salesqty'] === null && (strpos($cleanText, 'salesqty') !== false || strpos($cleanText, 'qty') !== false || strpos($cleanText, 'liter') !== false || strpos($text, 'লিটার') !== false)) {
            $colMap['salesqty'] = $idx;
        }
        // Commission Amount (Checked BEFORE general sales amount!)
        elseif ($colMap['commissionamount'] === null && (strpos($cleanText, 'commissionamount') !== false || (strpos($text, 'commission') !== false && strpos($text, 'amount') !== false) || (strpos($text, 'কমিশন') !== false && strpos($text, 'টাকা') !== false))) {
            $colMap['commissionamount'] = $idx;
        }
        // Commission Rate
        elseif ($colMap['commissionrate'] === null && (strpos($cleanText, 'commissionrate') !== false || strpos($cleanText, 'rate') !== false || strpos($text, 'কমিশন') !== false || strpos($text, 'হার') !== false)) {
            $colMap['commissionrate'] = $idx;
        }
        // Other Collection
        elseif ($colMap['othercollection'] === null && (strpos($cleanText, 'othercollection') !== false || strpos($text, 'other') !== false || strpos($text, 'অন্যান্য') !== false)) {
            $colMap['othercollection'] = $idx;
        }
        // Total Collection
        elseif ($colMap['totalcollection'] === null && (strpos($cleanText, 'totalcollection') !== false || strpos($text, 'total') !== false || strpos($text, 'মোট') !== false)) {
            $colMap['totalcollection'] = $idx;
        }
        // Sales Amount
        elseif ($colMap['salesamount'] === null && (strpos($cleanText, 'salesamount') !== false || strpos($text, 'sales') !== false || strpos($text, 'amount') !== false || strpos($text, 'বিক্রয়') !== false)) {
            $colMap['salesamount'] = $idx;
        }
        // Remarks
        elseif ($colMap['remarks'] === null && (strpos($text, 'remark') !== false || strpos($text, 'মন্তব্য') !== false || strpos($text, 'note') !== false)) {
            $colMap['remarks'] = $idx;
        }
    }

    // Default positional fallbacks if missing from header
    if ($colMap['date'] === null) $colMap['date'] = 0;
    if ($colMap['salesqty'] === null) $colMap['salesqty'] = 1;
    if ($colMap['salesamount'] === null) $colMap['salesamount'] = 2;
    if ($colMap['othercollection'] === null) $colMap['othercollection'] = 3;
    if ($colMap['totalcollection'] === null) $colMap['totalcollection'] = 4;
    if ($colMap['commissionrate'] === null) $colMap['commissionrate'] = 5;
    if ($colMap['commissionamount'] === null) $colMap['commissionamount'] = 6;
    if ($colMap['remarks'] === null) $colMap['remarks'] = 7;

    return $colMap;
}

// Multi-format Parser for CSV (comma/semicolon/tab), XLSX, and XLS HTML tables
function parseHistoricalBulkFile($filePath, $fileName) {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // 1. Try XLSX Zip XML parser first
    if ($ext === 'xlsx' && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $sharedStrings = [];
            $ssXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($ssXml) {
                $xml = @simplexml_load_string($ssXml);
                if ($xml) {
                    foreach ($xml->si as $si) {
                        if (isset($si->t)) {
                            $sharedStrings[] = (string)$si->t;
                        } elseif (isset($si->r)) {
                            $text = '';
                            foreach ($si->r as $r) { $text .= (string)$r->t; }
                            $sharedStrings[] = $text;
                        } else {
                            $sharedStrings[] = '';
                        }
                    }
                }
            }
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            if ($sheetXml) {
                $xml = @simplexml_load_string($sheetXml);
                if ($xml && isset($xml->sheetData)) {
                    $rows = [];
                    foreach ($xml->sheetData->row as $row) {
                        $rowData = [];
                        foreach ($row->c as $cell) {
                            $t = (string)$cell['t'];
                            $val = (string)$cell->v;
                            if ($t === 's' && isset($sharedStrings[intval($val)])) {
                                $val = $sharedStrings[intval($val)];
                            }
                            $rowData[] = trim($val);
                        }
                        if (!empty(array_filter($rowData, 'strlen'))) {
                            $rows[] = $rowData;
                        }
                    }
                    if (!empty($rows)) return $rows;
                }
            }
        }
    }

    // 2. Try HTML/XML Table parser (for XLS or HTML export)
    $content = file_get_contents($filePath);
    if ($content !== false && (stripos($content, '<table') !== false || stripos($content, '<tr') !== false)) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?>' . $content);
        $rows = [];
        foreach ($dom->getElementsByTagName('tr') as $tr) {
            $rowData = [];
            foreach ($tr->getElementsByTagName('td') as $td) {
                $rowData[] = trim($td->nodeValue);
            }
            if (empty($rowData)) {
                foreach ($tr->getElementsByTagName('th') as $th) {
                    $rowData[] = trim($th->nodeValue);
                }
            }
            if (!empty(array_filter($rowData, 'strlen'))) {
                $rows[] = $rowData;
            }
        }
        if (!empty($rows)) return $rows;
    }

    // 3. Fallback CSV Parser (Auto-detect BOM, comma, semicolon, tab)
    if ($content === false) return [];
    if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
        $content = substr($content, 3);
    }
    $content = str_replace(["\r\n", "\r"], "\n", $content);
    $lines = explode("\n", $content);

    $delimiter = ",";
    if (isset($lines[0])) {
        if (substr_count($lines[0], ';') > substr_count($lines[0], ',')) $delimiter = ";";
        elseif (substr_count($lines[0], "\t") > substr_count($lines[0], ',')) $delimiter = "\t";
    }

    $rows = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $data = str_getcsv($line, $delimiter);
        if (!empty(array_filter($data, 'strlen'))) {
            $rows[] = $data;
        }
    }
    return $rows;
}

// Handle Bulk CSV Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_csv') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $msg = t('Please upload a valid CSV or Excel file.');
        $msgType = 'danger';
    } else {
        $filePath = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];

        $rows = parseHistoricalBulkFile($filePath, $fileName);

        if (empty($rows)) {
            $msg = t('Uploaded file contains no readable data rows.');
            $msgType = 'warning';
        } else {
            $colMap = mapBulkCollectionHeaders($rows[0]);

            // Header detection
            $firstRowText = implode(' ', array_map('strtolower', $rows[0]));
            $hasHeader = false;
            if (preg_match('/(date|qty|sales|amount|collection|commission|remark|তারিখ|লিটার|বিক্রয়|কমিশন|মন্তব্য)/i', $firstRowText)) {
                $hasHeader = true;
            }

            $startIndex = $hasHeader ? 1 : 0;
            $insertedCount = 0;
            $userId = $_SESSION['user_id'] ?? 1;

            for ($i = $startIndex; $i < count($rows); $i++) {
                $r = $rows[$i];
                $rawDate  = $r[$colMap['date']] ?? '';
                $rawQty   = $r[$colMap['salesqty']] ?? 0;
                $rawAmt   = $r[$colMap['salesamount']] ?? 0;
                $rawOther = $r[$colMap['othercollection']] ?? 0;
                $rawTot   = $r[$colMap['totalcollection']] ?? 0;
                $rawCommR = $r[$colMap['commissionrate']] ?? 0;
                $rawCommA = $r[$colMap['commissionamount']] ?? 0;
                $rawRmk   = $r[$colMap['remarks']] ?? '';

                $txnDate = parseHistoricalDate($rawDate);
                
                // Sanitize numeric inputs (strip currency signs, commas, spaces)
                $cleanQty   = preg_replace('/[^\d.-]/', '', (string)$rawQty);
                $cleanAmt   = preg_replace('/[^\d.-]/', '', (string)$rawAmt);
                $cleanOther = preg_replace('/[^\d.-]/', '', (string)$rawOther);
                $cleanTot   = preg_replace('/[^\d.-]/', '', (string)$rawTot);
                $cleanCommR = preg_replace('/[^\d.-]/', '', (string)$rawCommR);
                $cleanCommA = preg_replace('/[^\d.-]/', '', (string)$rawCommA);

                $salesQty         = floatval($cleanQty);
                $salesAmount      = floatval($cleanAmt);
                $otherCollection  = floatval($cleanOther);
                $totalCollection  = floatval($cleanTot);
                $commissionRate   = floatval($cleanCommR);
                $commissionAmount = floatval($cleanCommA);
                $remarks          = trim((string)$rawRmk);

                if ($salesQty <= 0 && $salesAmount <= 0 && $totalCollection <= 0) {
                    continue; // Skip blank rows
                }

                $rate = ($salesQty > 0) ? ($salesAmount / $salesQty) : 0.00;
                if ($totalCollection <= 0) {
                    $totalCollection = $salesAmount + $otherCollection;
                }
                if ($commissionAmount <= 0) {
                    $commissionAmount = $salesQty * $commissionRate;
                }

                $insertSql = "INSERT INTO trx_historical_bulk_collection 
                    (TxnDate, SalesQty, SalesAmount, Rate, OtherCollection, TotalCollection, CommissionRate, CommissionAmount, Remarks, CreatedBy, CreatedAt)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                $objQuery->inUpDel($insertSql, [
                    $txnDate, $salesQty, $salesAmount, $rate, $otherCollection, $totalCollection, $commissionRate, $commissionAmount, $remarks, $userId
                ]);

                $insertedCount++;
            }

            if ($insertedCount > 0) {
                $_SESSION['flash_msg'] = sprintf(t('Successfully imported %d bulk collection records.'), $insertedCount);
                $_SESSION['flash_type'] = 'success';
                header("Location: add_bulk_collection.php");
                exit;
            } else {
                $msg = t('No valid records were imported from the CSV file.');
                $msgType = 'warning';
            }
        }
    }
}

// Flash Message Handling
if (isset($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    $msgType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

// Filter Options
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date']   ?? date('Y-m-d');

// Fetch Historical Bulk Collection Records
$records = $objQuery->index("
    SELECT * 
    FROM trx_historical_bulk_collection 
    WHERE IsDeleted = 0 
      AND TxnDate BETWEEN ? AND ? 
    ORDER BY TxnDate DESC, Id DESC
", [$startDate, $endDate]);

// Calculate Summary Totals
$totQty = 0;
$totSales = 0;
$totOtherColl = 0;
$totColl = 0;
$totComm = 0;

foreach ($records as $r) {
    $totQty       += floatval($r->SalesQty);
    $totSales     += floatval($r->SalesAmount);
    $totOtherColl += floatval($r->OtherCollection);
    $totColl      += floatval($r->TotalCollection);
    $totComm      += floatval($r->CommissionAmount);
}

$avgRate = ($totQty > 0) ? ($totSales / $totQty) : 0.00;

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <!-- Page Title & Top Actions -->
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1 text-dark">
                    <i class="fas fa-file-csv text-primary me-2"></i>
                    <?php echo t('Add Bulk Collection'); ?> 
                    <small class="text-muted fs-6">(<?php echo t('Historical Data Entry'); ?>)</small>
                </h4>
                <p class="text-muted small mb-0">
                    <?php echo t('Upload CSV file to bulk import software live / historical sales & collection entries.'); ?>
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="?action=download_sample" class="btn btn-outline-success btn-sm fw-bold shadow-sm">
                    <i class="fas fa-download me-1"></i> <?php echo t('Download Sample CSV'); ?>
                </a>
                <button type="button" class="btn btn-primary btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addSingleModal">
                    <i class="fas fa-plus-circle me-1"></i> <?php echo t('Add Single Record'); ?>
                </button>
            </div>
        </div>

        <!-- Alert Notification -->
        <?php if (!empty($msg)): ?>
            <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show shadow-sm border-0" role="alert">
                <i class="fas <?php echo ($msgType === 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- CSV Bulk Import Card -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header text-white py-3" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-cloud-upload-alt me-2 text-warning"></i>
                    <?php echo t('Upload CSV / Excel File for Bulk Import'); ?>
                </h6>
            </div>
            <div class="card-body bg-light p-4">
                <form method="POST" action="" enctype="multipart/form-data" class="row g-3 align-items-end">
                    <input type="hidden" name="action" value="upload_csv">
                    
                    <div class="col-md-9">
                        <label for="csv_file" class="form-label fw-bold text-dark small">
                            <?php echo t('Select CSV File (.csv)'); ?> <span class="text-danger">*</span>
                        </label>
                        <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv, .xlsx, .xls" required>
                        <div class="form-text small text-muted">
                            <i class="fas fa-info-circle me-1 text-primary"></i>
                            <?php echo t('Expected columns'); ?>: <code>Date, SalesQty, SalesAmount, OtherCollection, TotalCollection, CommissionRate, CommissionAmount, Remarks</code>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm">
                            <i class="fas fa-file-import me-1"></i> <?php echo t('Import Bulk Data'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border-radius: 10px;">
                    <div class="card-body p-3">
                        <small class="text-white-50 text-uppercase fw-bold" style="font-size: 11px;"><?php echo t('Total Sales Qty (Liter)'); ?></small>
                        <h4 class="fw-bold mb-0 font-monospace mt-1"><?php echo number_format($totQty, 2); ?> <span class="fs-6 fw-normal">L</span></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border-radius: 10px;">
                    <div class="card-body p-3">
                        <small class="text-white-50 text-uppercase fw-bold" style="font-size: 11px;"><?php echo t('Total Sales Amount'); ?></small>
                        <h4 class="fw-bold mb-0 font-monospace mt-1"><?php echo number_format($totSales, 2); ?> <span class="fs-6 fw-normal">৳</span></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%); border-radius: 10px;">
                    <div class="card-body p-3">
                        <small class="text-white-50 text-uppercase fw-bold" style="font-size: 11px;"><?php echo t('Total Collection'); ?></small>
                        <h4 class="fw-bold mb-0 font-monospace mt-1"><?php echo number_format($totColl, 2); ?> <span class="fs-6 fw-normal">৳</span></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border-radius: 10px;">
                    <div class="card-body p-3">
                        <small class="text-white-50 text-uppercase fw-bold" style="font-size: 11px;"><?php echo t('Total Commission'); ?></small>
                        <h4 class="fw-bold mb-0 font-monospace mt-1"><?php echo number_format($totComm, 2); ?> <span class="fs-6 fw-normal">৳</span></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Records List Card -->
        <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-list text-primary me-2"></i>
                    <?php echo t('Historical Bulk Collection Records'); ?>
                    <span class="badge bg-primary rounded-pill ms-2"><?php echo count($records); ?></span>
                </h6>
                <form method="GET" action="" class="d-flex align-items-center gap-2">
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($startDate); ?>">
                    <span class="text-muted small">to</span>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($endDate); ?>">
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                        <i class="fas fa-filter me-1"></i> <?php echo t('Filter'); ?>
                    </button>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="bulkCollectionTable">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 5%;">#</th>
                                <th style="width: 11%;"><?php echo t('Date'); ?></th>
                                <th class="text-end" style="width: 12%;"><?php echo t('Sales Qty (L)'); ?></th>
                                <th class="text-end" style="width: 13%;"><?php echo t('Sales Amount (৳)'); ?></th>
                                <th class="text-end" style="width: 10%;"><?php echo t('Rate (৳/L)'); ?></th>
                                <th class="text-end" style="width: 12%;"><?php echo t('Other Coll (৳)'); ?></th>
                                <th class="text-end" style="width: 13%;"><?php echo t('Total Coll (৳)'); ?></th>
                                <th class="text-end" style="width: 10%;"><?php echo t('Commission (৳)'); ?></th>
                                <th style="width: 9%;"><?php echo t('Remarks'); ?></th>
                                <th class="text-center" style="width: 5%;"><?php echo t('Action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <?php echo t('No historical bulk collection records found for the selected date range.'); ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php $sl = 1; foreach ($records as $r): ?>
                            <?php 
                                $qty       = floatval($r->SalesQty);
                                $amt       = floatval($r->SalesAmount);
                                $rate      = floatval($r->Rate);
                                $otherColl = floatval($r->OtherCollection);
                                $totColl   = floatval($r->TotalCollection);
                                $commR     = floatval($r->CommissionRate);
                                $commA     = floatval($r->CommissionAmount);
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $sl++; ?></td>
                                <td class="fw-semibold"><?php echo date('d-M-Y', strtotime($r->TxnDate)); ?></td>
                                <td class="text-end font-monospace text-primary"><?php echo number_format($qty, 2); ?></td>
                                <td class="text-end font-monospace fw-bold text-dark"><?php echo number_format($amt, 2); ?></td>
                                <td class="text-end font-monospace text-secondary"><?php echo number_format($rate, 2); ?></td>
                                <td class="text-end font-monospace text-info"><?php echo number_format($otherColl, 2); ?></td>
                                <td class="text-end font-monospace fw-bold text-success"><?php echo number_format($totColl, 2); ?></td>
                                <td class="text-end font-monospace text-danger">
                                    <?php echo number_format($commA, 2); ?>
                                    <?php if ($commR > 0): ?>
                                        <small class="text-muted d-block" style="font-size: 10px;">(<?php echo number_format($commR, 2); ?>%)</small>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?php echo htmlspecialchars($r->Remarks ?? '-'); ?></td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm p-1 rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v text-muted"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 8px; font-size: 13px;">
                                            <li>
                                                <a class="dropdown-item py-1.5" href="javascript:void(0)" onclick='openEditModal(<?php echo json_encode($r); ?>)'>
                                                    <i class="fas fa-edit text-primary me-2"></i> <?php echo t('Edit'); ?>
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <a class="dropdown-item py-1.5 text-danger" href="?action=delete&id=<?php echo $r->Id; ?>" onclick="return confirm('<?php echo t('Are you sure you want to delete this record?'); ?>')">
                                                    <i class="fas fa-trash-alt me-2"></i> <?php echo t('Delete'); ?>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2" class="text-end fs-6"><?php echo t('Summary Total'); ?>:</td>
                                <td class="text-end font-monospace text-primary fs-6"><?php echo number_format($totQty, 2); ?></td>
                                <td class="text-end font-monospace text-dark fs-6"><?php echo number_format($totSales, 2); ?></td>
                                <td class="text-end font-monospace text-secondary fs-6"><?php echo number_format($avgRate, 2); ?></td>
                                <td class="text-end font-monospace text-info fs-6"><?php echo number_format($totOtherColl, 2); ?></td>
                                <td class="text-end font-monospace text-success fs-6"><?php echo number_format($totColl, 2); ?></td>
                                <td class="text-end font-monospace text-danger fs-6"><?php echo number_format($totComm, 2); ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Single Record Modal -->
<div class="modal fade" id="addSingleModal" tabindex="-1" aria-labelledby="addSingleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                <h5 class="modal-title fw-bold" id="addSingleModalLabel">
                    <i class="fas fa-plus-circle text-warning me-2"></i> <?php echo t('Add Single Bulk Collection Record'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_single">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" name="TxnDate" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Sales Quantity (Liter)'); ?></label>
                            <input type="number" step="0.01" name="SalesQty" id="addSalesQty" class="form-control" value="0.00" oninput="calcAddRate()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Sales Amount (৳)'); ?></label>
                            <input type="number" step="0.01" name="SalesAmount" id="addSalesAmount" class="form-control" value="0.00" oninput="calcAddRate()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Rate (৳ / Liter)'); ?> <small class="text-primary">(Auto)</small></label>
                            <input type="number" step="0.01" id="addRate" class="form-control bg-light" readonly value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Other Collection (৳)'); ?></label>
                            <input type="number" step="0.01" name="OtherCollection" id="addOtherCollection" class="form-control" value="0.00" oninput="calcAddTotalColl()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Total Collection (৳)'); ?> <small class="text-primary">(Auto)</small></label>
                            <input type="number" step="0.01" name="TotalCollection" id="addTotalCollection" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Commission Rate (৳/L)'); ?></label>
                            <input type="number" step="0.01" name="CommissionRate" id="addCommissionRate" class="form-control" value="0.00" oninput="calcAddCommission()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Commission Amount (৳)'); ?> <small class="text-primary">(Auto)</small></label>
                            <input type="number" step="0.01" name="CommissionAmount" id="addCommissionAmount" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Remarks'); ?></label>
                            <input type="text" name="Remarks" class="form-control" placeholder="<?php echo t('Optional notes...'); ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm px-3 fw-bold" data-bs-dismiss="modal"><?php echo t('Close'); ?></button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold"><?php echo t('Save Record'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Record Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                <h5 class="modal-title fw-bold" id="editModalLabel">
                    <i class="fas fa-edit text-warning me-2"></i> <?php echo t('Edit Bulk Collection Record'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_single">
                <input type="hidden" name="RecordId" id="editRecordId">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" name="TxnDate" id="editTxnDate" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Sales Quantity (Liter)'); ?></label>
                            <input type="number" step="0.01" name="SalesQty" id="editSalesQty" class="form-control" oninput="calcEditRate()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Sales Amount (৳)'); ?></label>
                            <input type="number" step="0.01" name="SalesAmount" id="editSalesAmount" class="form-control" oninput="calcEditRate()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Rate (৳ / Liter)'); ?> <small class="text-primary">(Auto)</small></label>
                            <input type="number" step="0.01" id="editRate" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Other Collection (৳)'); ?></label>
                            <input type="number" step="0.01" name="OtherCollection" id="editOtherCollection" class="form-control" oninput="calcEditTotalColl()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Total Collection (৳)'); ?> <small class="text-primary">(Auto)</small></label>
                            <input type="number" step="0.01" name="TotalCollection" id="editTotalCollection" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Commission Rate (৳/L)'); ?></label>
                            <input type="number" step="0.01" name="CommissionRate" id="editCommissionRate" class="form-control" oninput="calcEditCommission()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Commission Amount (৳)'); ?> <small class="text-primary">(Auto)</small></label>
                            <input type="number" step="0.01" name="CommissionAmount" id="editCommissionAmount" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-muted"><?php echo t('Remarks'); ?></label>
                            <input type="text" name="Remarks" id="editRemarks" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm px-3 fw-bold" data-bs-dismiss="modal"><?php echo t('Close'); ?></button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold"><?php echo t('Update Record'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calcAddRate() {
    const qty = parseFloat(document.getElementById('addSalesQty').value) || 0;
    const amt = parseFloat(document.getElementById('addSalesAmount').value) || 0;
    const rateEl = document.getElementById('addRate');
    if (qty > 0 && amt > 0) {
        rateEl.value = (amt / qty).toFixed(2);
    } else {
        rateEl.value = '0.00';
    }
    calcAddTotalColl();
    calcAddCommission();
}

function calcAddTotalColl() {
    const amt = parseFloat(document.getElementById('addSalesAmount').value) || 0;
    const other = parseFloat(document.getElementById('addOtherCollection').value) || 0;
    const totEl = document.getElementById('addTotalCollection');
    totEl.value = (amt + other).toFixed(2);
}

function calcAddCommission() {
    const qty = parseFloat(document.getElementById('addSalesQty').value) || 0;
    const commRate = parseFloat(document.getElementById('addCommissionRate').value) || 0;
    const commAmtEl = document.getElementById('addCommissionAmount');
    if (qty > 0 && commRate > 0) {
        commAmtEl.value = (qty * commRate).toFixed(2);
    }
}

function calcEditRate() {
    const qty = parseFloat(document.getElementById('editSalesQty').value) || 0;
    const amt = parseFloat(document.getElementById('editSalesAmount').value) || 0;
    const rateEl = document.getElementById('editRate');
    if (qty > 0 && amt > 0) {
        rateEl.value = (amt / qty).toFixed(2);
    } else {
        rateEl.value = '0.00';
    }
    calcEditTotalColl();
    calcEditCommission();
}

function calcEditTotalColl() {
    const amt = parseFloat(document.getElementById('editSalesAmount').value) || 0;
    const other = parseFloat(document.getElementById('editOtherCollection').value) || 0;
    const totEl = document.getElementById('editTotalCollection');
    totEl.value = (amt + other).toFixed(2);
}

function calcEditCommission() {
    const qty = parseFloat(document.getElementById('editSalesQty').value) || 0;
    const commRate = parseFloat(document.getElementById('editCommissionRate').value) || 0;
    const commAmtEl = document.getElementById('editCommissionAmount');
    if (qty > 0 && commRate > 0) {
        commAmtEl.value = (qty * commRate).toFixed(2);
    }
}

function openEditModal(record) {
    document.getElementById('editRecordId').value = record.Id;
    document.getElementById('editTxnDate').value = record.TxnDate;
    document.getElementById('editSalesQty').value = parseFloat(record.SalesQty).toFixed(2);
    document.getElementById('editSalesAmount').value = parseFloat(record.SalesAmount).toFixed(2);
    document.getElementById('editRate').value = parseFloat(record.Rate).toFixed(2);
    document.getElementById('editOtherCollection').value = parseFloat(record.OtherCollection).toFixed(2);
    document.getElementById('editTotalCollection').value = parseFloat(record.TotalCollection).toFixed(2);
    document.getElementById('editCommissionRate').value = parseFloat(record.CommissionRate).toFixed(2);
    document.getElementById('editCommissionAmount').value = parseFloat(record.CommissionAmount).toFixed(2);
    document.getElementById('editRemarks').value = record.Remarks || '';

    const modalEl = document.getElementById('editModal');
    let bsModal = bootstrap.Modal.getInstance(modalEl);
    if (!bsModal) {
        bsModal = new bootstrap.Modal(modalEl);
    }
    bsModal.show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
