<?php
/**
 * FuelDeskPro - Stock Summary Report (স্টক সামারি)
 *
 * Calculates fuel stock movements from:
 * 1. Supplier Stock Purchases (trx_stock_in)
 * 2. Nozzle Fuel Sales (trx_nozzlereading)
 * 3. Manual Stock Adjustments (trx_stockadjustment)
 *
 * @package FuelDeskPro
 */

$pageTitle = 'Stock Summary';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

global $objQuery;
if (!isset($objQuery) || $objQuery === null) {
    $objQuery = db();
}

// Ensure required tables exist
ensureStockAdjustmentTableExist();

// Input & Filter handling
$startDate  = $_GET['start_date']  ?? date('Y-m-01');
$endDate    = $_GET['end_date']    ?? date('Y-m-d');
$fuelTypeId = $_GET['fuel_type_id'] ?? 'all';
$tankId     = $_GET['tank_id']     ?? 'all';
$lang       = $_GET['lang']        ?? (function_exists('currentLang') ? currentLang() : 'bn');

if (!in_array($lang, ['bn', 'en'])) {
    $lang = 'bn';
}

$pageDisplayTitle = ($lang === 'en') ? 'Fuel Stock Summary Report' : 'ফুয়েল স্টক সামারি রিপোর্ট';

// Fetch Fuel Types for filter
$fuelTypes = $objQuery->index("SELECT FuelTypeID, FuelName FROM mst_fueltype WHERE IsActive = 1 AND IsDeleted = 0 ORDER BY FuelName");

// Fetch Tanks for filter
$tanksList = $objQuery->index("SELECT t.TankID, t.TankName, ft.FuelName FROM mst_tank t LEFT JOIN mst_fueltype ft ON t.FuelTypeID = ft.FuelTypeID WHERE t.IsActive = 1 AND t.IsDeleted = 0 ORDER BY t.TankName");

// Build Tank Query
$whereClause = "t.IsActive = 1 AND t.IsDeleted = 0";
$queryParams = [];

if ($fuelTypeId !== 'all' && !empty($fuelTypeId)) {
    $whereClause .= " AND t.FuelTypeID = ?";
    $queryParams[] = intval($fuelTypeId);
}

if ($tankId !== 'all' && !empty($tankId)) {
    $whereClause .= " AND t.TankID = ?";
    $queryParams[] = intval($tankId);
}

$tankRows = $objQuery->index("
    SELECT t.TankID, t.TankName, t.TankCode, t.TankGroupID, t.FuelTypeID, t.Capacity, t.MinLevel, t.OpeningStockPercent,
           ft.FuelName
    FROM mst_tank t
    LEFT JOIN mst_fueltype ft ON t.FuelTypeID = ft.FuelTypeID
    WHERE {$whereClause}
    ORDER BY t.TankName
", $queryParams);

// Calculate stock movements for each tank
$stockData = [];
$totalOpeningStock = 0;
$totalPeriodStockIn = 0;
$totalPeriodSales = 0;
$totalPeriodStockAdd = 0;
$totalPeriodStockDeduct = 0;
$totalCurrentStock = 0;
$totalCapacity = 0;

foreach ($tankRows as $tank) {
    $tId = intval($tank->TankID);
    $tgId = intval($tank->TankGroupID);
    $ftId = intval($tank->FuelTypeID);
    $capacity = floatval($tank->Capacity);
    
    // Initial Master Opening Stock in Liters
    $baseOpeningLiters = $capacity * (floatval($tank->OpeningStockPercent) / 100);

    // 1. Prior Stock IN (Purchases before start date)
    $priorStockInRec = $objQuery->index("
        SELECT SUM(Quantity) AS total 
        FROM trx_stock_in 
        WHERE TankID = ? AND StockInDate < ? AND IsActive = 1 AND IsDeleted = 0
    ", [$tId, $startDate]);
    $priorStockIn = floatval($priorStockInRec[0]->total ?? 0);

    // 2. Prior Sales (Nozzle readings before start date)
    $priorSalesRec = $objQuery->index("
        SELECT SUM(nr.SaleQuantity) AS total 
        FROM trx_nozzlereading nr 
        JOIN mst_nozzle n ON nr.NozzleID = n.NozzleID 
        WHERE (n.TankGroupID = ? OR n.FuelTypeID = ?) 
          AND nr.ReadingDate < ? AND nr.IsActive = 1 AND nr.IsDeleted = 0
    ", [$tgId > 0 ? $tgId : -1, $ftId, $startDate]);
    $priorSales = floatval($priorSalesRec[0]->total ?? 0);

    // 3. Prior Manual Stock ADD (Adjustments before start date)
    $priorAddRec = $objQuery->index("
        SELECT SUM(Quantity) AS total 
        FROM trx_stockadjustment 
        WHERE TankID = ? AND AdjustmentDate < ? AND AdjustmentType IN ('ADD', 'Stock IN') AND IsActive = 1 AND IsDeleted = 0
    ", [$tId, $startDate]);
    $priorAdd = floatval($priorAddRec[0]->total ?? 0);

    // 4. Prior Manual Stock DEDUCT (Adjustments before start date)
    $priorDeductRec = $objQuery->index("
        SELECT SUM(Quantity) AS total 
        FROM trx_stockadjustment 
        WHERE TankID = ? AND AdjustmentDate < ? AND AdjustmentType IN ('DEDUCT', 'Stock OUT') AND IsActive = 1 AND IsDeleted = 0
    ", [$tId, $startDate]);
    $priorDeduct = floatval($priorDeductRec[0]->total ?? 0);

    // Effective Opening Stock at Start Date
    $effectiveOpening = $baseOpeningLiters + $priorStockIn - $priorSales + $priorAdd - $priorDeduct;

    // ------------------- Period Calculations (start_date to end_date) -------------------
    // Period Stock IN
    $periodInRec = $objQuery->index("
        SELECT SUM(Quantity) AS total 
        FROM trx_stock_in 
        WHERE TankID = ? AND StockInDate >= ? AND StockInDate <= ? AND IsActive = 1 AND IsDeleted = 0
    ", [$tId, $startDate, $endDate]);
    $periodStockIn = floatval($periodInRec[0]->total ?? 0);

    // Period Sales
    $periodSalesRec = $objQuery->index("
        SELECT SUM(nr.SaleQuantity) AS total 
        FROM trx_nozzlereading nr 
        JOIN mst_nozzle n ON nr.NozzleID = n.NozzleID 
        WHERE (n.TankGroupID = ? OR n.FuelTypeID = ?) 
          AND nr.ReadingDate >= ? AND nr.ReadingDate <= ? AND nr.IsActive = 1 AND nr.IsDeleted = 0
    ", [$tgId > 0 ? $tgId : -1, $ftId, $startDate, $endDate]);
    $periodSales = floatval($periodSalesRec[0]->total ?? 0);

    // Period Stock ADD
    $periodAddRec = $objQuery->index("
        SELECT SUM(Quantity) AS total 
        FROM trx_stockadjustment 
        WHERE TankID = ? AND AdjustmentDate >= ? AND AdjustmentDate <= ? AND AdjustmentType IN ('ADD', 'Stock IN') AND IsActive = 1 AND IsDeleted = 0
    ", [$tId, $startDate, $endDate]);
    $periodStockAdd = floatval($periodAddRec[0]->total ?? 0);

    // Period Stock DEDUCT
    $periodDeductRec = $objQuery->index("
        SELECT SUM(Quantity) AS total 
        FROM trx_stockadjustment 
        WHERE TankID = ? AND AdjustmentDate >= ? AND AdjustmentDate <= ? AND AdjustmentType IN ('DEDUCT', 'Stock OUT') AND IsActive = 1 AND IsDeleted = 0
    ", [$tId, $startDate, $endDate]);
    $periodStockDeduct = floatval($periodDeductRec[0]->total ?? 0);

    // Current Ending Stock Balance
    $currentStock = $effectiveOpening + $periodStockIn - $periodSales + $periodStockAdd - $periodStockDeduct;

    $fillPercent = ($capacity > 0) ? ($currentStock / $capacity) * 100 : 0;

    $stockData[] = [
        'TankID'          => $tId,
        'TankName'        => $tank->TankName,
        'TankCode'        => $tank->TankCode,
        'FuelName'        => $tank->FuelName ?? 'N/A',
        'Capacity'        => $capacity,
        'MinLevel'        => floatval($tank->MinLevel),
        'OpeningStock'    => $effectiveOpening,
        'PeriodStockIn'   => $periodStockIn,
        'PeriodSales'     => $periodSales,
        'PeriodStockAdd'  => $periodStockAdd,
        'PeriodStockDeduct' => $periodStockDeduct,
        'CurrentStock'    => $currentStock,
        'FillPercent'     => $fillPercent,
    ];

    $totalOpeningStock     += $effectiveOpening;
    $totalPeriodStockIn    += $periodStockIn;
    $totalPeriodSales      += $periodSales;
    $totalPeriodStockAdd   += $periodStockAdd;
    $totalPeriodStockDeduct+= $periodStockDeduct;
    $totalCurrentStock     += $currentStock;
    $totalCapacity         += $capacity;
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<style>
@media print {
    #sidebar, .top-navbar, .filter-card, .btn-print, footer {
        display: none !important;
    }
    #content-wrapper {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}

.summary-fluid-card {
    background: #ffffff;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: 16px 20px;
    transition: all 0.2s ease-in-out;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
}
.summary-fluid-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.08);
}
.fluid-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
}
</style>

<div class="container-fluid px-4 py-3">
    <!-- Header Title & Action Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-boxes text-primary me-2"></i><?php echo htmlspecialchars($pageDisplayTitle); ?></h4>
            <small class="text-muted">
                <?php echo ($lang === 'en') ? 'Date Range:' : 'তারিখ সীমা:'; ?> 
                <strong><?php echo date('d-m-Y', strtotime($startDate)); ?></strong> 
                <?php echo ($lang === 'en') ? 'to' : 'হতে'; ?> 
                <strong><?php echo date('d-m-Y', strtotime($endDate)); ?></strong>
            </small>
        </div>
        <div>
            <button onclick="window.print();" class="btn btn-outline-secondary btn-sm btn-print">
                <i class="fas fa-print me-1"></i> <?php echo ($lang === 'en') ? 'Print Report' : 'প্রিন্ট করুন'; ?>
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-4 filter-card shadow-sm border-0">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-bold mb-1"><?php echo ($lang === 'en') ? 'Start Date' : 'শুরুর তারিখ'; ?></label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($startDate); ?>" required>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-bold mb-1"><?php echo ($lang === 'en') ? 'End Date' : 'শেষের তারিখ'; ?></label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($endDate); ?>" required>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label small fw-bold mb-1"><?php echo ($lang === 'en') ? 'Fuel Type' : 'ফুয়েল টাইপ'; ?></label>
                    <select name="fuel_type_id" class="form-select form-select-sm">
                        <option value="all"><?php echo ($lang === 'en') ? '-- All Fuel Types --' : '-- সকল ফুয়েল টাইপ --'; ?></option>
                        <?php foreach ($fuelTypes as $ft): ?>
                            <option value="<?php echo $ft->FuelTypeID; ?>" <?php echo ($fuelTypeId == $ft->FuelTypeID) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ft->FuelName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label small fw-bold mb-1"><?php echo ($lang === 'en') ? 'Tank' : 'ট্যাংক'; ?></label>
                    <select name="tank_id" class="form-select form-select-sm">
                        <option value="all"><?php echo ($lang === 'en') ? '-- All Tanks --' : '-- সকল ট্যাংক --'; ?></option>
                        <?php foreach ($tanksList as $tk): ?>
                            <option value="<?php echo $tk->TankID; ?>" <?php echo ($tankId == $tk->TankID) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tk->TankName . ' (' . ($tk->FuelName ?? 'N/A') . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-sm-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="fas fa-filter me-1"></i> <?php echo ($lang === 'en') ? 'Filter' : 'ফিল্টার'; ?>
                    </button>
                    <a href="stock_summary.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Fluid Summary Cards -->
    <div class="fluid-grid mb-4">
        <div class="summary-fluid-card border-start border-4 border-info">
            <div class="text-muted small fw-bold text-uppercase"><?php echo ($lang === 'en') ? 'Opening Stock' : 'ওপেনিং স্টক'; ?></div>
            <div class="h4 mb-0 fw-bold text-info mt-1"><?php echo number_format($totalOpeningStock, 3); ?> <span class="fs-6 text-muted">L</span></div>
            <div class="small text-muted mt-1"><i class="fas fa-calendar-alt me-1"></i><?php echo date('d-m-Y', strtotime($startDate)); ?></div>
        </div>

        <div class="summary-fluid-card border-start border-4 border-success">
            <div class="text-muted small fw-bold text-uppercase"><?php echo ($lang === 'en') ? 'Stock IN (Purchases)' : 'স্টক ইন (ক্রয়)'; ?></div>
            <div class="h4 mb-0 fw-bold text-success mt-1">+<?php echo number_format($totalPeriodStockIn, 3); ?> <span class="fs-6 text-muted">L</span></div>
            <div class="small text-muted mt-1"><i class="fas fa-truck me-1"></i>trx_stock_in</div>
        </div>

        <div class="summary-fluid-card border-start border-4 border-danger">
            <div class="text-muted small fw-bold text-uppercase"><?php echo ($lang === 'en') ? 'Fuel Sales (Stock Out)' : 'বিক্রয় (স্টক আউট)'; ?></div>
            <div class="h4 mb-0 fw-bold text-danger mt-1">-<?php echo number_format($totalPeriodSales, 3); ?> <span class="fs-6 text-muted">L</span></div>
            <div class="small text-muted mt-1"><i class="fas fa-gas-pump me-1"></i>trx_nozzlereading</div>
        </div>

        <div class="summary-fluid-card border-start border-4 border-warning">
            <div class="text-muted small fw-bold text-uppercase"><?php echo ($lang === 'en') ? 'Net Adjustments' : 'সমন্বয় (এডজাস্টমেন্ট)'; ?></div>
            <?php $netAdj = $totalPeriodStockAdd - $totalPeriodStockDeduct; ?>
            <div class="h4 mb-0 fw-bold <?php echo $netAdj >= 0 ? 'text-success' : 'text-danger'; ?> mt-1">
                <?php echo ($netAdj >= 0 ? '+' : '') . number_format($netAdj, 3); ?> <span class="fs-6 text-muted">L</span>
            </div>
            <div class="small text-muted mt-1"><i class="fas fa-sliders-h me-1"></i>trx_stockadjustment</div>
        </div>

        <div class="summary-fluid-card border-start border-4 border-primary">
            <div class="text-muted small fw-bold text-uppercase"><?php echo ($lang === 'en') ? 'Current Net Stock' : 'বর্তমান নেট স্টক'; ?></div>
            <div class="h4 mb-0 fw-bold text-primary mt-1"><?php echo number_format($totalCurrentStock, 3); ?> <span class="fs-6 text-muted">L</span></div>
            <div class="small text-muted mt-1"><i class="fas fa-tachometer-alt me-1"></i><?php echo date('d-m-Y', strtotime($endDate)); ?></div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-table text-primary me-2"></i><?php echo ($lang === 'en') ? 'Tank-wise Stock Movement Breakdown' : 'ট্যাংক-ভিত্তিক স্টক হিসাব'; ?></h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th><?php echo ($lang === 'en') ? 'Tank Name' : 'ট্যাংকের নাম'; ?></th>
                        <th><?php echo ($lang === 'en') ? 'Fuel Type' : 'ফুয়েল টাইপ'; ?></th>
                        <th class="text-end"><?php echo ($lang === 'en') ? 'Capacity (L)' : 'ধারণক্ষমতা (লিটার)'; ?></th>
                        <th class="text-end"><?php echo ($lang === 'en') ? 'Opening Stock (L)' : 'ওপেনিং স্টক (লিটার)'; ?></th>
                        <th class="text-end text-success"><?php echo ($lang === 'en') ? 'Stock IN (L)' : 'স্টক ইন (লিটার)'; ?></th>
                        <th class="text-end text-danger"><?php echo ($lang === 'en') ? 'Sales (L)' : 'বিক্রয় (লিটার)'; ?></th>
                        <th class="text-end text-success"><?php echo ($lang === 'en') ? 'Stock ADD (L)' : 'স্টক প্লাস (লিটার)'; ?></th>
                        <th class="text-end text-danger"><?php echo ($lang === 'en') ? 'Stock DEDUCT (L)' : 'স্টক মাইনাস (লিটার)'; ?></th>
                        <th class="text-end fw-bold text-primary"><?php echo ($lang === 'en') ? 'Current Stock (L)' : 'বর্তমান স্টক (লিটার)'; ?></th>
                        <th class="text-center" style="width: 140px;"><?php echo ($lang === 'en') ? 'Fill Level' : 'স্টক লেভেল (%)'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stockData)): ?>
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">
                                <i class="fas fa-info-circle me-1"></i> <?php echo ($lang === 'en') ? 'No stock records found for the selected criteria.' : 'কোন তথ্য পাওয়া যায়নি।'; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $sl = 1; foreach ($stockData as $row): ?>
                            <tr>
                                <td class="text-center text-muted"><?php echo $sl++; ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['TankName']); ?></div>
                                    <?php if (!empty($row['TankCode'])): ?>
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['TankCode']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark"><?php echo htmlspecialchars($row['FuelName']); ?></span>
                                </td>
                                <td class="text-end fw-semibold"><?php echo number_format($row['Capacity'], 3); ?></td>
                                <td class="text-end text-secondary fw-semibold"><?php echo number_format($row['OpeningStock'], 3); ?></td>
                                <td class="text-end text-success font-monospace">+<?php echo number_format($row['PeriodStockIn'], 3); ?></td>
                                <td class="text-end text-danger font-monospace">-<?php echo number_format($row['PeriodSales'], 3); ?></td>
                                <td class="text-end text-success font-monospace">+<?php echo number_format($row['PeriodStockAdd'], 3); ?></td>
                                <td class="text-end text-danger font-monospace">-<?php echo number_format($row['PeriodStockDeduct'], 3); ?></td>
                                <td class="text-end fw-bold text-primary fs-6"><?php echo number_format($row['CurrentStock'], 3); ?></td>
                                <td class="text-center">
                                    <?php 
                                        $percent = round($row['FillPercent'], 1);
                                        $barClass = 'bg-success';
                                        if ($percent < 20) { $barClass = 'bg-danger'; }
                                        elseif ($percent < 40) { $barClass = 'bg-warning text-dark'; }
                                    ?>
                                    <div class="progress" style="height: 18px;" title="<?php echo $percent; ?>%">
                                        <div class="progress-bar <?php echo $barClass; ?> fw-bold" role="progressbar" style="width: <?php echo min(100, max(0, $percent)); ?>%;">
                                            <?php echo $percent; ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="3" class="text-end"><?php echo ($lang === 'en') ? 'Total:' : 'মোট:'; ?></td>
                        <td class="text-end"><?php echo number_format($totalCapacity, 3); ?></td>
                        <td class="text-end"><?php echo number_format($totalOpeningStock, 3); ?></td>
                        <td class="text-end text-success">+<?php echo number_format($totalPeriodStockIn, 3); ?></td>
                        <td class="text-end text-danger">-<?php echo number_format($totalPeriodSales, 3); ?></td>
                        <td class="text-end text-success">+<?php echo number_format($totalPeriodStockAdd, 3); ?></td>
                        <td class="text-end text-danger">-<?php echo number_format($totalPeriodStockDeduct, 3); ?></td>
                        <td class="text-end text-primary fs-6"><?php echo number_format($totalCurrentStock, 3); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
