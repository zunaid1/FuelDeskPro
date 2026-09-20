<?php
/**
 * FuelDeskPro - Historical Data Entry / Upload Previous Customer Dues
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Auto-ensure trx_Customer_Due_Opening table exists
ensureCustomerDueOpeningTableExists();

/**
 * Helper to parse composite entity selection (e.g. C_101, E_5, S_2)
 */
function parseSelectedEntity($val) {
    $val = trim((string)$val);
    $parts = explode('_', $val);
    if (count($parts) === 2) {
        $code = strtoupper($parts[0]);
        $id = intval($parts[1]);
        $type = 'Customer';
        if ($code === 'E') $type = 'Employee';
        if ($code === 'S') $type = 'Shareholder';
        return ['type' => $type, 'id' => $id, 'val' => $code . '_' . $id];
    }
    $id = intval($val);
    return ['type' => 'Customer', 'id' => $id, 'val' => ($id > 0 ? 'C_' . $id : '')];
}

/**
 * Helper to parse native XLSX zip XML files
 */
function parseXlsxFile($filePath) {
    if (!class_exists('ZipArchive')) return false;
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) return false;

    // Load shared strings
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
                    foreach ($si->r as $r) {
                        $text .= (string)$r->t;
                    }
                    $sharedStrings[] = $text;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }
    }

    // Load sheet1.xml
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if (!$sheetXml) return false;

    $xml = @simplexml_load_string($sheetXml);
    if (!$xml || !isset($xml->sheetData)) return false;

    $rows = [];
    foreach ($xml->sheetData->row as $row) {
        $rowData = [];
        foreach ($row->c as $cell) {
            $t = (string)$cell['t']; // t="s" means shared string index
            $val = (string)$cell->v;
            if ($t === 's' && isset($sharedStrings[intval($val)])) {
                $val = $sharedStrings[intval($val)];
            }
            $rowData[] = trim($val);
        }
        if (!empty($rowData)) {
            $rows[] = $rowData;
        }
    }
    return $rows;
}

/**
 * Universal Parser for XLSX, XLS (HTML/XML), and CSV
 */
function parseUploadedExcelOrCsv($filePath, $fileName) {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // 1. Try XLSX Zip XML parser first
    if ($ext === 'xlsx') {
        $xlsxRows = parseXlsxFile($filePath);
        if ($xlsxRows !== false && count($xlsxRows) > 0) {
            return $xlsxRows;
        }
    }

    // 2. Try HTML/XML Table parser for .xls
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
            if (!empty($rowData)) {
                $rows[] = $rowData;
            }
        }
        if (!empty($rows)) return $rows;
    }

    // 3. Fallback CSV Parser
    if ($content === false) return [];
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
        if (!empty($data)) $rows[] = $data;
    }
    return $rows;
}

// Handle Direct CSV (.csv) Download Request
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    $filename = "Previous-Due-Blank.csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Output UTF-8 BOM for MS Excel compatibility
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Sales Amount', 'Payment', 'Remark']);
    fputcsv($output, ['11-Sep-24', '3306.00', '0.00', 'নমুনা তথ্য']);
    fclose($output);
    exit;
}

$alertMessage = '';
$alertType = 'info';

// Handle Upload Dues File POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_dues') {
    $rawVal = $_POST['customer_id'] ?? '';
    $entity = parseSelectedEntity($rawVal);
    
    if ($entity['id'] <= 0) {
        $alertMessage = "অনুগ্রহ করে কাস্টমার / শেয়ারহোল্ডার / কর্মচারী সিলেক্ট করুন!";
        $alertType = "danger";
    } elseif (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $alertMessage = "অনুগ্রহ করে একটি সঠিক Excel/CSV ফাইল নির্বাচন করুন!";
        $alertType = "danger";
    } else {
        $fileTmpPath = $_FILES['excel_file']['tmp_name'];
        $fileName = $_FILES['excel_file']['name'];
        
        $parsedRows = parseUploadedExcelOrCsv($fileTmpPath, $fileName);

        if (!empty($parsedRows)) {
            $insertedDuesCount = 0;
            $insertedPaymentsCount = 0;

            // Fetch previous running balance for selected entity
            $runningBalance = 0;
            $prevBalRes = db()->index("SELECT Balance FROM trx_Customer_Due_Opening WHERE CustomerID = ? AND CustomerType = ? AND IsDeleted = 0 ORDER BY TxnDate DESC, OpeningDueID DESC LIMIT 1", [$entity['id'], $entity['type']]);
            if (!empty($prevBalRes)) {
                $runningBalance = floatval($prevBalRes[0]->Balance);
            }

            foreach ($parsedRows as $data) {
                if (count($data) < 2) continue;

                $col0 = trim($data[0] ?? '');
                $col1 = trim(str_replace(',', '', $data[1] ?? '')); // LPG Purchase
                $col2 = trim(str_replace(',', '', $data[2] ?? '')); // Payment
                
                // Remark is col 3 if 4 columns, or col 4 if 5 columns
                $col3 = trim($data[3] ?? '');
                if (count($data) >= 5 && is_numeric(str_replace(',', '', $data[3]))) {
                    $col3 = trim($data[4] ?? '');
                }

                // Ignore header rows (supports 'Sales Amount', 'Sales', 'LPG Purchase', 'Date', etc.)
                if (empty($col0) || stripos($col0, 'Date') !== false || stripos($col0, 'Sales') !== false || stripos($col0, 'LPG') !== false || stripos($col0, 'Opening Balance') !== false || stripos($col0, 'Sanghu') !== false || stripos($col0, 'গ্রাহকের') !== false || stripos($col0, 'ঠিকানা') !== false || stripos($col0, 'Due List') !== false || stripos($col0, 'নাম') !== false) {
                    continue;
                }

                // Parse Date (e.g. 11-Sep-24, 11/09/2024, 2024-09-11)
                $rawDate = str_replace('/', '-', $col0);
                $parsedTime = strtotime($rawDate);
                if (!$parsedTime) {
                    continue;
                }
                $txnDate = date('Y-m-d', $parsedTime);

                $purchaseAmt = is_numeric($col1) ? floatval($col1) : 0;
                $paymentAmt  = is_numeric($col2) ? floatval($col2) : 0;

                if ($purchaseAmt > 0 || $paymentAmt > 0) {
                    // Auto calculate running balance
                    $runningBalance += ($purchaseAmt - $paymentAmt);

                    $sqlOpening = "INSERT INTO trx_Customer_Due_Opening 
                        (TxnDate, CustomerID, CustomerType, SalesAmount, Payment, Balance, Remarks, CreatedBy, CreatedAt, IsActive, IsDeleted) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 100, NOW(), 1, 0)";
                    db()->inUpDel($sqlOpening, [$txnDate, $entity['id'], $entity['type'], $purchaseAmt, $paymentAmt, $runningBalance, $col3 ?: 'Excel Uploaded Opening Record']);
                    
                    if ($purchaseAmt > 0) $insertedDuesCount++;
                    if ($paymentAmt > 0) $insertedPaymentsCount++;
                }
            }

            if ($insertedDuesCount > 0 || $insertedPaymentsCount > 0) {
                $alertMessage = "সফলভাবে MS Excel ফাইলটি থেকে <strong>{$insertedDuesCount}টি</strong> বাকিতে বিক্রয় এবং <strong>{$insertedPaymentsCount}টি</strong> পেমেন্ট রেকর্ড ডাটাবেজে অন্তর্ভুক্ত হয়েছে!";
                $alertType = "success";
            } else {
                $alertMessage = "ফাইলে কোনো সঠিক ডাটা সারি পাওয়া যায়নি। অনুগ্রহ করে নমুনা Excel ফাইল অনুযায়ী আপলোড করুন।";
                $alertType = "warning";
            }
        } else {
            $alertMessage = "Excel ফাইলটি পড়া সম্ভব হয়নি! ফাইলটি ফরম্যাট করে পুনরায় চেষ্টা করুন।";
            $alertType = "danger";
        }
    }
}

$pageTitle = 'Previous Customer Dues';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Fetch Active Customers, Employees, and Shareholders via UNION
$allPersons = db()->index("
    SELECT 
        CONCAT('C_', CustomerID) AS ValueID,
        CustomerID AS RealID,
        'Customer' AS EntityType,
        CustomerName AS Name,
        Mobile,
        Address
    FROM mst_customer 
    WHERE IsDeleted = 0

    UNION ALL

    SELECT 
        CONCAT('E_', Id) AS ValueID,
        Id AS RealID,
        'Employee' AS EntityType,
        COALESCE(NULLIF(NameBN, ''), NameEN) AS Name,
        Mobile,
        Address
    FROM mst_employee 
    WHERE IsDeleted = 0

    UNION ALL

    SELECT 
        CONCAT('S_', Id) AS ValueID,
        Id AS RealID,
        'Shareholder' AS EntityType,
        COALESCE(NULLIF(NameBN, ''), NameEN) AS Name,
        Mobile,
        Address
    FROM mst_shareholder 
    WHERE IsDeleted = 0

    ORDER BY Name ASC
");

// Filter by selected entity if set
$rawFilterVal = $_GET['customer_id'] ?? ($_POST['customer_id'] ?? '');
$filterEntity = parseSelectedEntity($rawFilterVal);
$filterValueID = $filterEntity['val'];
?>

<div class="row mb-4">
    <div class="col-md-12">
        <!-- Main Card -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between py-3">
                <h5 class="mb-0">
                    <i class="fas fa-folder-plus me-2"></i> <?php echo t('Previous Data Entry'); ?> 
                    <small class="fs-6 opacity-75">(পূর্বের হিসাব এন্ট্রি / আপলোড - MS Excel support)</small>
                </h5>
                <span class="badge bg-light text-primary fs-6">May 2024 - June 2026</span>
            </div>

            <div class="card-body p-4">

                <?php if (!empty($alertMessage)): ?>
                <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-<?php echo $alertType==='success'?'check-circle':($alertType==='warning'?'exclamation-triangle':'exclamation-circle'); ?> fa-2x me-3"></i>
                    <div><?php echo $alertMessage; ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Instruction Banner -->
                <div class="alert alert-light border-start border-4 border-info shadow-sm mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle text-info fa-2x me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1">CSV / MS Excel বকেয়া শিট আপলোড নির্দেশিকা:</h6>
                            <p class="mb-0 text-muted small">
                                ১. প্রথমে ব্যক্তি (কাস্টমার, শেয়ারহোল্ডার বা কর্মচারী) সিলেক্ট করুন। <br>
                                ২. <strong>"Download Blank CSV"</strong> বাটনে ক্লিক করে <strong>.csv</strong> টেমপ্লেট ফাইলটি ডাউনলোড করুন। <br>
                                ৩. ডাটা টাইপ করে আপনার সুবিধামত <strong>.csv</strong>, <strong>.xlsx</strong> বা <strong>.xls</strong> ফাইলে সেভ করে <strong>"Upload Dues"</strong> বাটনে ক্লিক করুন।
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form Section -->
                <form id="duesUploadForm" action="" method="POST" enctype="multipart/form-data" class="card p-4 border bg-light shadow-sm">
                    <input type="hidden" name="action" id="formAction" value="upload_dues">

                    <div class="row g-3 align-items-end">
                        <!-- Customer / Shareholder / Employee Dropdown -->
                        <div class="col-md-5">
                            <label for="customer_id" class="form-label fw-bold">
                                <i class="fas fa-users text-primary me-1"></i> কাস্টমার / শেয়ারহোল্ডার / কর্মচারী নির্বাচন করুন <span class="text-danger">*</span>
                            </label>
                            <select name="customer_id" id="customer_id" class="form-select select2" required onchange="updateDownloadLink()">
                                <option value="">-- ব্যক্তি নির্বাচন করুন --</option>
                                <?php foreach($allPersons as $p): ?>
                                    <?php 
                                        $typeBadge = ($p->EntityType === 'Customer') ? 'গ্রাহক' : (($p->EntityType === 'Employee') ? 'কর্মচারী' : 'শেয়ারহোল্ডার');
                                        $label = htmlspecialchars($p->Name) . ' (' . $typeBadge . (!empty($p->Mobile) ? ' - ' . htmlspecialchars($p->Mobile) : '') . ')';
                                        $selected = ($filterValueID === $p->ValueID) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo $p->ValueID; ?>" <?php echo $selected; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- File Upload Selection -->
                        <div class="col-md-7">
                            <label for="excel_file" class="form-label fw-bold">
                                <i class="fas fa-file-excel text-success me-1"></i> File: Select MS Excel File (.xlsx, .xls, .csv) <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="excel_file" id="excel_file" class="form-control" accept=".xlsx, .xls, .csv">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap align-items-center justify-content-start gap-3 mt-4 pt-2 border-top">
                        <button type="submit" onclick="document.getElementById('formAction').value='upload_dues';" class="btn btn-primary px-4 fw-bold">
                            <i class="fas fa-upload me-2"></i> Upload Dues
                        </button>
                        
                        <a id="btnDownloadBlank" href="#" onclick="triggerBlankDownload(event)" class="btn btn-outline-success px-4 fw-bold">
                            <i class="fas fa-file-csv me-2"></i> Download Blank CSV (.csv)
                        </a>
                    </div>
                </form>

                <!-- Customer Ledger Data Display (If Filtered/Selected) -->
                <?php if ($filterEntity['id'] > 0): ?>
                <?php 
                    $cDueList = db()->index("
                        SELECT 
                            OpeningDueID AS ID, 
                            TxnDate, 
                            SalesAmount AS Debit, 
                            Payment AS Credit, 
                            Balance, 
                            Remarks, 
                            IF(SalesAmount > 0, 'Sales Amount', 'Payment') AS Type 
                        FROM trx_Customer_Due_Opening 
                        WHERE CustomerID = ? AND CustomerType = ? AND IsDeleted = 0
                        ORDER BY TxnDate ASC, OpeningDueID ASC", [$filterEntity['id'], $filterEntity['type']]);
                ?>
                <div class="mt-5">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-list-alt text-primary me-2"></i> নির্বাচিত ব্যক্তির সফটওয়্যার হিসাব লেজার
                        </h6>
                        <span class="badge bg-secondary">মোট রেকর্ড: <?php echo count($cDueList); ?></span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>SL</th>
                                    <th>তারিখ (Date)</th>
                                    <th>ধরন (Type)</th>
                                    <th class="text-end">Sales Amount / বাকিতে ক্রয় (৳)</th>
                                    <th class="text-end">Payment / পরিশোধ (৳)</th>
                                    <th class="text-end">ব্যালেন্স (Balance ৳)</th>
                                    <th>মন্তব্য (Remark)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cDueList)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">এই ব্যক্তির কোনো বকেয়া বা পরিশোধের রেকর্ড এখনো ইনপুট করা হয়নি।</td>
                                </tr>
                                <?php else: ?>
                                <?php $sl=1; $totalDebit=0; $totalCredit=0; foreach($cDueList as $row): ?>
                                <?php $totalDebit += $row->Debit; $totalCredit += $row->Credit; ?>
                                <tr>
                                    <td><?php echo $sl++; ?></td>
                                    <td><?php echo date('d-M-Y', strtotime($row->TxnDate)); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($row->Type==='Sales Amount' || $row->Type==='LPG Purchase')?'danger':'success'; ?>">
                                            <?php echo $row->Type; ?>
                                        </span>
                                    </td>
                                    <td class="text-end font-monospace"><?php echo $row->Debit > 0 ? number_format($row->Debit, 2) : '-'; ?></td>
                                    <td class="text-end font-monospace"><?php echo $row->Credit > 0 ? number_format($row->Credit, 2) : '-'; ?></td>
                                    <td class="text-end font-monospace fw-bold text-primary"><?php echo number_format($row->Balance, 2); ?> ৳</td>
                                    <td><?php echo htmlspecialchars($row->Remarks ?? ''); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-light fw-bold">
                                    <td colspan="3" class="text-end">সর্বমোট (Total):</td>
                                    <td class="text-end text-danger font-monospace"><?php echo number_format($totalDebit, 2); ?> ৳</td>
                                    <td class="text-end text-success font-monospace"><?php echo number_format($totalCredit, 2); ?> ৳</td>
                                    <td colspan="2" class="text-primary font-monospace">সর্বশেষ অবশিষ্ট বকেয়া: <?php echo number_format($totalDebit - $totalCredit, 2); ?> ৳</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
function triggerBlankDownload(e) {
    e.preventDefault();
    var customerId = document.getElementById('customer_id').value;
    if (!customerId) {
        alert('অনুগ্রহ করে আগে ড্রপডাউন থেকে একজন ব্যক্তি নির্বাচন করুন!');
        return;
    }
    window.location.href = 'previous_customer_due.php?action=download_template&customer_id=' + encodeURIComponent(customerId);
}

function updateDownloadLink() {
    var customerId = document.getElementById('customer_id').value;
    if (customerId) {
        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('customer_id', customerId);
        window.history.replaceState({}, '', currentUrl);
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
