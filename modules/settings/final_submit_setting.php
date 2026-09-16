<?php
/**
 * FuelDeskPro - Final Submit Settings Management
 * 
 * Manage and view Final Submit (Day Close) history and perform Re-open actions.
 * 
 * @package FuelDeskPro
 */

$pageTitle = 'Daily Statement Closing Status';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

ensureFinalSubmitTableExists();

// Filter inputs
$filterMonth = $_GET['filter_month'] ?? '';
$filterDate  = $_GET['filter_date'] ?? '';

// Build Query
$where = ["1=1"];
$params = [];

if (!empty($filterDate)) {
    $where[] = "fs.StatementDate = ?";
    $params[] = $filterDate;
} elseif (!empty($filterMonth)) {
    $where[] = "DATE_FORMAT(fs.StatementDate, '%Y-%m') = ?";
    $params[] = $filterMonth;
}

$whereClause = implode(" AND ", $where);

$sql = "SELECT fs.*, 
            e1.NameEN AS SubmittedByName, 
            e2.NameEN AS ReopenedByName 
        FROM trx_finalsubmit fs
        LEFT JOIN mst_employee e1 ON fs.SubmittedBy = e1.Id
        LEFT JOIN mst_employee e2 ON fs.ReopenedBy = e2.Id
        WHERE {$whereClause}
        ORDER BY fs.StatementDate DESC";

$records = $objQuery->index($sql, $params);

// Stats calculations
$totalClosedCount = 0;
$totalReopenedCount = 0;

$allStatsSql = "SELECT IsClosed, COUNT(*) as cnt FROM trx_finalsubmit GROUP BY IsClosed";
$statsRes = $objQuery->index($allStatsSql);
foreach ($statsRes as $st) {
    if (intval($st->IsClosed) === 1) {
        $totalClosedCount = intval($st->cnt);
    } else {
        $totalReopenedCount = intval($st->cnt);
    }
}
?>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card bg-danger text-white shadow-sm">
            <div class="card-body py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-white-50 mb-1">Total Closed Days</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $totalClosedCount; ?></h3>
                </div>
                <div class="fs-1 text-white-50"><i class="fas fa-lock"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white shadow-sm">
            <div class="card-body py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-white-50 mb-1">Re-opened Days</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $totalReopenedCount; ?></h3>
                </div>
                <div class="fs-1 text-white-50"><i class="fas fa-lock-open"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-primary text-white shadow-sm">
            <div class="card-body py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-white-50 mb-1">Filtered Results</h6>
                    <h3 class="mb-0 fw-bold"><?php echo count($records); ?></h3>
                </div>
                <div class="fs-1 text-white-50"><i class="fas fa-filter"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold"><i class="fas fa-calendar-alt text-primary me-1"></i> Filter by Month (মাস)</label>
                <input type="month" name="filter_month" class="form-control" value="<?php echo htmlspecialchars($filterMonth); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold"><i class="fas fa-calendar-day text-primary me-1"></i> Filter by Date (তারিখ)</label>
                <input type="date" name="filter_date" class="form-control" value="<?php echo htmlspecialchars($filterDate); ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Filter</button>
                <a href="<?php echo BASE_URL; ?>modules/settings/final_submit_setting.php" class="btn btn-secondary"><i class="fas fa-undo me-1"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="table-container shadow-sm bg-white p-3 rounded">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-cog text-primary me-2"></i>Final Submit Settings & History</h5>
        <span class="text-muted small">মাস বা তারিখ সিলেক্ট করে হিসাব চেক ও পুনরায় Open করা যাবে</span>
    </div>

    <table class="table table-hover datatable align-middle">
        <thead class="table-light">
            <tr>
                <th style="width: 5%;">SL</th>
                <th style="width: 12%;">Statement Date</th>
                <th style="width: 12%;">Status</th>
                <th style="width: 18%;">Final Submit Date & Time</th>
                <th style="width: 15%;">Submitted By</th>
                <th style="width: 18%;">Re-opened Info</th>
                <th style="width: 10%; text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($records)): $sl = 1; foreach ($records as $rec): ?>
            <tr>
                <td><?php echo $sl++; ?></td>
                <td>
                    <span class="fw-bold text-dark fs-6">
                        <?php echo date('d-M-Y', strtotime($rec->StatementDate)); ?>
                    </span>
                </td>
                <td>
                    <?php if (intval($rec->IsClosed) === 1): ?>
                        <span class="badge bg-danger fs-6 py-1 px-2"><i class="fas fa-lock me-1"></i> Closed</span>
                    <?php else: ?>
                        <span class="badge bg-success fs-6 py-1 px-2"><i class="fas fa-lock-open me-1"></i> Re-opened</span>
                    <?php endif; ?>
                </td>
                <td>
                    <small class="fw-semibold text-dark">
                        <i class="far fa-clock me-1 text-muted"></i>
                        <?php echo !empty($rec->SubmittedAt) ? date('d-m-Y h:i A', strtotime($rec->SubmittedAt)) : 'N/A'; ?>
                    </small>
                </td>
                <td>
                    <small class="text-muted">
                        <i class="far fa-user me-1"></i>
                        <?php echo htmlspecialchars($rec->SubmittedByName ?? 'System / Admin'); ?>
                    </small>
                </td>
                <td>
                    <?php if (!empty($rec->ReopenedAt)): ?>
                        <small class="text-success fw-semibold d-block">
                            <i class="fas fa-undo me-1"></i>
                            <?php echo date('d-m-Y h:i A', strtotime($rec->ReopenedAt)); ?>
                        </small>
                        <small class="text-muted d-block">
                            By: <?php echo htmlspecialchars($rec->ReopenedByName ?? 'Admin'); ?>
                        </small>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if (intval($rec->IsClosed) === 1): ?>
                        <button type="button" 
                                class="btn btn-sm btn-outline-primary btn-reopen"
                                data-date="<?php echo $rec->StatementDate; ?>"
                                data-display-date="<?php echo date('d-M-Y', strtotime($rec->StatementDate)); ?>"
                                title="হিসাবটি পুনরায় Open করতে ক্লিক করুন">
                            <i class="fas fa-folder-open me-1"></i> Re-open
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm btn-light text-muted" disabled>
                            <i class="fas fa-check me-1"></i> Open
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
                <td colspan="7" class="text-center text-muted py-4">কোন ফাইন্যাল সাবমিট তথ্য পাওয়া যায়নি (No final submit records found)</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    $('.btn-reopen').on('click', function() {
        const statementDate = $(this).data('date');
        const displayDate = $(this).data('display-date');
        const btn = $(this);
        const originalHtml = btn.html();

        Swal.fire({
            title: 'হিসাব Re-open করতে চান?',
            text: 'আপনি কি নিশ্চিত যে ' + displayDate + ' তারিখের ক্লোজ হওয়া হিসাবটি পুনরায় Open করতে চান?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'হ্যাঁ, Open করুন',
            cancelButtonText: 'বাতিল'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');

                $.ajax({
                    url: '<?php echo BASE_URL; ?>modules/entry/final_submit_entry.php',
                    type: 'POST',
                    data: {
                        action: 'reopen',
                        statement_date: statementDate
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'সফল!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'ত্রুটি!',
                                text: response.message || 'Error processing request',
                                confirmButtonColor: '#d33'
                            });
                            btn.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'সংযোগ সমস্যা!',
                            text: 'An error occurred while connecting to the server: ' + error,
                            confirmButtonColor: '#d33'
                        });
                        btn.prop('disabled', false).html(originalHtml);
                    }
                });
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
