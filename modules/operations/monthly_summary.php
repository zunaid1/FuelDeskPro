<?php
/**
 * FuelDeskPro - Monthly Summary LPG View Module
 * 
 * View for generating and printing the Monthly Summary - LPG report.
 * 
 * @package FuelDeskPro
 */

$pageTitle = 'Monthly Summary - LPG';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$company = getCompanyInfo();
$compName = $company ? $company->CompanyName : 'Shangu LPG Filling Station';
$compAddress = $company ? $company->Address : 'Amilaish, Satkania, Chattogram';
$currencySymbol = $company ? $company->CurrencySymbol : '৳';
$currentMonth = date('Y-m');
?>

<style>
/* Custom styles for Monthly Summary LPG Report */
.report-header-box {
    border-bottom: 2px solid #0d6efd;
    padding-bottom: 15px;
    margin-bottom: 20px;
}
.report-title-badge {
    background-color: #e7f1ff;
    color: #0d6efd;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    display: inline-block;
}
.table-dense th, .table-dense td {
    padding: 0.4rem 0.5rem;
    font-size: 0.875rem;
}
.table-dense th {
    background-color: #f8f9fa;
    color: #334155;
    font-weight: 600;
    vertical-align: middle;
}
.summary-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: transform 0.15s ease-in-out;
}
.summary-card:hover {
    transform: translateY(-2px);
}
.summary-card .card-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    font-weight: 600;
}
.summary-card .card-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
}
.net-profit-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #ffffff;
    border: none;
}
.net-profit-card .card-label {
    color: #94a3b8;
}
.net-profit-card .card-value {
    color: #38bdf8;
    font-size: 1.4rem;
}

/* Print Specific Rules */
@media print {
    #sidebar, .top-navbar, .no-print, #sidebarCollapse {
        display: none !important;
    }
    #content-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 0 !important;
    }
    #main-content {
        padding: 0 !important;
    }
    body {
        background: #ffffff !important;
        font-size: 11px !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .table-dense th, .table-dense td {
        padding: 3px 5px !important;
        font-size: 10px !important;
    }
    .print-footer {
        display: flex !important;
        justify-content: space-between;
        align-items: center;
        border-top: 1px dashed #cbd5e1;
        padding-top: 10px;
        margin-top: 30px;
        font-size: 11px;
        color: #475569;
    }
}
.print-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px dashed #cbd5e1;
    padding-top: 10px;
    margin-top: 30px;
    font-size: 12px;
    color: #64748b;
}
</style>

<div class="container-fluid py-3">
    <!-- Top Filter & Controls (Hidden on Print) -->
    <div class="card mb-4 no-print border-0 shadow-sm">
        <div class="card-body">
            <form id="filterForm" class="row g-3 align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label for="month_select" class="form-label fw-bold small text-secondary">
                        <i class="fas fa-calendar-alt me-1 text-primary"></i> Select Reporting Month
                    </label>
                    <input type="month" id="month_select" name="month" class="form-control form-control-sm" value="<?php echo $currentMonth; ?>" required>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label for="commission_rate" class="form-label fw-bold small text-secondary">
                        Commission Rate (<?php echo $currencySymbol; ?>/L)
                    </label>
                    <input type="number" step="0.01" id="commission_rate" name="commission_rate" class="form-control form-control-sm" value="8.00" required>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label for="adj_plus" class="form-label fw-bold small text-secondary">
                        Adjustment (+)
                    </label>
                    <input type="number" step="0.01" id="adj_plus" name="adj_plus" class="form-control form-control-sm" value="0.00">
                </div>
                <div class="col-md-2 col-sm-6">
                    <label for="adj_minus" class="form-label fw-bold small text-secondary">
                        Adjustment (-)
                    </label>
                    <input type="number" step="0.01" id="adj_minus" name="adj_minus" class="form-control form-control-sm" value="0.00">
                </div>
                <div class="col-md-3 col-sm-12 d-flex gap-2">
                    <button type="submit" id="btnGenerate" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-sync-alt me-1"></i> Generate Report
                    </button>
                    <button type="button" onclick="window.print();" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Body Container (Printable Area) -->
    <div id="printableArea" class="bg-white p-3 rounded shadow-sm">
        <!-- Header Section -->
        <div class="text-center report-header-box">
            <h3 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($compName); ?></h3>
            <p class="text-muted mb-2 small"><?php echo htmlspecialchars($compAddress); ?></p>
            <div>
                <span class="report-title-badge">
                    <i class="fas fa-gas-pump me-1"></i> Monthly Summary - LPG (<span id="reportMonthLabel"><?php echo date('F Y'); ?></span>)
                </span>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-responsive">
            <table id="reportTable" class="table table-bordered table-sm table-hover align-middle table-dense mb-4">
                <thead>
                    <tr class="text-center">
                        <th style="width: 10%;">Date</th>
                        <th style="width: 10%;" class="text-end">Opening</th>
                        <th style="width: 10%;" class="text-end">Consumed Liter</th>
                        <th style="width: 8%;" class="text-end">Rate</th>
                        <th style="width: 11%;" class="text-end">Sales Amount</th>
                        <th style="width: 10%;" class="text-end">Due Coll.</th>
                        <th style="width: 11%;" class="text-end">Total Collection</th>
                        <th style="width: 10%;" class="text-end">Due Sales</th>
                        <th style="width: 10%;" class="text-end">Expense</th>
                        <th style="width: 10%;" class="text-end">Bank Deposit</th>
                    </tr>
                </thead>
                <tbody id="reportTbody">
                    <!-- Populated dynamically via AJAX -->
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-spin me-2"></i> Loading report data...
                        </td>
                    </tr>
                </tbody>
                <tfoot id="reportTfoot" class="table-light fw-bold text-end">
                    <!-- Grand Totals populated via AJAX -->
                </tfoot>
            </table>
        </div>

        <!-- Summary Section -->
        <div class="mt-4 pt-2">
            <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-calculator me-2"></i>Financial Profit Summary</h6>
            <div class="row g-3">
                <div class="col-md-4 col-lg-2 col-6">
                    <div class="p-3 summary-card">
                        <div class="card-label">Commission Rate</div>
                        <div class="card-value text-primary mt-1">
                            <?php echo $currencySymbol; ?> <span id="sumCommissionRate">8.00</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2 col-6">
                    <div class="p-3 summary-card">
                        <div class="card-label">Sales Commission</div>
                        <div class="card-value text-success mt-1">
                            <?php echo $currencySymbol; ?> <span id="sumSalesCommission">0.00</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2 col-6">
                    <div class="p-3 summary-card">
                        <div class="card-label">Total Expense</div>
                        <div class="card-value text-danger mt-1">
                            <?php echo $currencySymbol; ?> <span id="sumTotalExpense">0.00</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2 col-6">
                    <div class="p-3 summary-card">
                        <div class="card-label">Adjustment (+)</div>
                        <div class="card-value text-info mt-1">
                            <?php echo $currencySymbol; ?> <span id="sumAdjPlus">0.00</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2 col-6">
                    <div class="p-3 summary-card">
                        <div class="card-label">Adjustment (-)</div>
                        <div class="card-value text-warning mt-1">
                            <?php echo $currencySymbol; ?> <span id="sumAdjMinus">0.00</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2 col-12">
                    <div class="p-3 summary-card net-profit-card">
                        <div class="card-label">Net Profit</div>
                        <div class="card-value mt-1">
                            <?php echo $currencySymbol; ?> <span id="sumNetProfit">0.00</span>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-muted small mt-2 italic">
                * Net profit calculated after adjusting total operational expenses, commissions, and adjustments for this month.
            </p>
        </div>

        <!-- Print Footer -->
        <div class="print-footer">
            <div>Print: <?php echo date('d-m-Y h:i A'); ?></div>
            <div>Prepared by: Mohammad Zunaid</div>
            <div>Page 1 of 1</div>
        </div>
    </div>
</div>

<!-- Monthly Summary AJAX JavaScript -->
<script src="<?php echo BASE_URL; ?>assets/js/monthly_summary.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
