<?php
$pageTitle = 'Stock Adjustment';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

ensureStockAdjustmentTableExist();

$tanks = $objQuery->index("
    SELECT t.TankID, t.TankName, ft.FuelName, t.Capacity
    FROM mst_tank t
    LEFT JOIN mst_fueltype ft ON t.FuelTypeID = ft.FuelTypeID
    WHERE t.IsActive = 1 AND t.IsDeleted = 0
    ORDER BY t.TankName
");

$data = $objQuery->index("
    SELECT sa.*, 
           sa.AdjustmentID AS StockAdjustmentID, 
           COALESCE(sa.Reason, sa.AdjustmentReason, '') AS Reason, 
           t.TankName, 
           ft.FuelName
    FROM trx_stockadjustment sa
    LEFT JOIN mst_tank t ON sa.TankID = t.TankID
    LEFT JOIN mst_fueltype ft ON (sa.FuelTypeID = ft.FuelTypeID OR t.FuelTypeID = ft.FuelTypeID)
    WHERE sa.IsDeleted = 0
    ORDER BY sa.AdjustmentDate DESC, sa.AdjustmentID DESC
");

$totalStockIn = 0;
$totalStockOut = 0;
foreach ($data as $r) {
    if (in_array($r->AdjustmentType, ['Stock IN', 'ADD'])) {
        $totalStockIn += floatval($r->Quantity);
    } else {
        $totalStockOut += floatval($r->Quantity);
    }
}
?>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card bg-light-success border-success">
            <div class="card-body p-3 text-center">
                <h6 class="text-success mb-1"><i class="fas fa-arrow-down me-1"></i> Total Manual Stock IN</h6>
                <h4 class="mb-0 fw-bold text-success"><?php echo number_format($totalStockIn, 3); ?> L</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-light-danger border-danger">
            <div class="card-body p-3 text-center">
                <h6 class="text-danger mb-1"><i class="fas fa-arrow-up me-1"></i> Total Manual Stock OUT</h6>
                <h4 class="mb-0 fw-bold text-danger"><?php echo number_format($totalStockOut, 3); ?> L</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-light-info border-info">
            <div class="card-body p-3 text-center">
                <h6 class="text-info mb-1"><i class="fas fa-balance-scale me-1"></i> Net Adjustment</h6>
                <h4 class="mb-0 fw-bold text-info"><?php echo number_format($totalStockIn - $totalStockOut, 3); ?> L</h4>
            </div>
        </div>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h5><i class="fas fa-sliders-h text-primary me-2"></i>Stock Adjustment (Stock IN / Stock OUT)</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Add New Adjustment
        </button>
    </div>
    <table class="table table-hover datatable">
        <thead>
            <tr>
                <th>SL</th>
                <th>Date</th>
                <th>Tank Name</th>
                <th>Fuel Type</th>
                <th>Type</th>
                <th>Quantity (L)</th>
                <th>Reason</th>
                <th>Remarks</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $sl = 1; foreach ($data as $row): ?>
            <?php $isStockIn = in_array($row->AdjustmentType, ['Stock IN', 'ADD']); ?>
            <tr>
                <td><?php echo $sl++; ?></td>
                <td><?php echo formatDate($row->AdjustmentDate); ?></td>
                <td><?php echo htmlspecialchars($row->TankName ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row->FuelName ?? 'N/A'); ?></td>
                <td>
                    <span class="badge bg-<?php echo $isStockIn ? 'success' : 'danger'; ?>">
                        <i class="fas fa-<?php echo $isStockIn ? 'plus-circle' : 'minus-circle'; ?> me-1"></i>
                        <?php echo htmlspecialchars($row->AdjustmentType); ?>
                    </span>
                </td>
                <td class="fw-bold"><?php echo number_format($row->Quantity, 3); ?></td>
                <td><?php echo htmlspecialchars($row->Reason !== '' ? $row->Reason : '-'); ?></td>
                <td><?php echo htmlspecialchars($row->Remarks ?? '-'); ?></td>
                <td>
                    <button class="btn btn-sm btn-info edit-btn" 
                        data-id="<?php echo $row->StockAdjustmentID; ?>" 
                        data-date="<?php echo $row->AdjustmentDate; ?>" 
                        data-tank="<?php echo $row->TankID; ?>" 
                        data-type="<?php echo htmlspecialchars($row->AdjustmentType); ?>" 
                        data-quantity="<?php echo $row->Quantity; ?>" 
                        data-reason="<?php echo htmlspecialchars($row->Reason ?? ''); ?>" 
                        data-remarks="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->StockAdjustmentID; ?>">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="dataForm" method="POST" action="../../modules/entry/stock_adjustment_entry.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="record_id" id="edit_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Stock Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Adjustment Date <span class="text-danger">*</span></label>
                            <input type="date" name="adjustment_date" id="adjustment_date" class="form-control" required value="<?php echo today(); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tank <span class="text-danger">*</span></label>
                            <select name="tank_id" id="tank_id" class="form-select select2" required>
                                <option value="">Select Tank</option>
                                <?php foreach ($tanks as $t): ?>
                                    <option value="<?php echo $t->TankID; ?>">
                                        <?php echo htmlspecialchars($t->TankName . ' (' . ($t->FuelName ?? 'N/A') . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                            <select name="adjustment_type" id="adjustment_type" class="form-select" required>
                                <option value="Stock IN">Stock IN (মজুদ বৃদ্ধি)</option>
                                <option value="Stock OUT">Stock OUT (মজুদ হ্রাস)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantity (Liters) <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" name="quantity" id="quantity" class="form-control" placeholder="0.000" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reason</label>
                            <select name="reason" id="reason" class="form-select">
                                <option value="Dip Variance">Dip Reading Variance (ডিপ গরমিল)</option>
                                <option value="Calibration">Pump Calibration (ক্যালিব্রেশন)</option>
                                <option value="Tank Transfer">Tank Transfer (ট্যাংক ট্রান্সফার)</option>
                                <option value="Spillage/Evaporation">Evaporation / Loss (বাষ্পীভবন / ক্ষতি)</option>
                                <option value="Opening Adjustment">Initial Opening Stock Adjustment</option>
                                <option value="Other">Other Reason</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="2" placeholder="অতিরিক্ত বিবরণ লিখুন..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#addModal').on('show.bs.modal', function(e) {
        if (!$(e.relatedTarget).hasClass('edit-btn')) {
            $('#dataForm')[0].reset();
            $('#edit_id').val('');
            $('#adjustment_date').val('<?php echo today(); ?>');
            $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Stock Adjustment');
        }
    });

    $(document).on('click', '.edit-btn', function() {
        const b = $(this);
        $('#edit_id').val(b.data('id'));
        $('#adjustment_date').val(b.data('date'));
        $('#tank_id').val(b.data('tank')).trigger('change');
        $('#adjustment_type').val(b.data('type'));
        $('#quantity').val(b.data('quantity'));
        $('#reason').val(b.data('reason'));
        $('#remarks').val(b.data('remarks'));
        $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Stock Adjustment');
        $('#addModal').modal('show');
    });

    $('#dataForm').on('submit', function(e) {
        e.preventDefault();
        const f = $(this), btn = f.find('[type="submit"]'), orig = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        $.ajax({
            url: f.attr('action'),
            type: 'POST',
            data: f.serialize(),
            dataType: 'json',
            success: function(r) {
                if (r.success) {
                    showNotification(r.message, 'success');
                    $('#addModal').modal('hide');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification(r.message, 'error');
                }
            },
            error: function() {
                showNotification('Error processing request!', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html(orig);
            }
        });
    });

    $(document).on('click', '.delete-btn', function() {
        if (!confirmDelete()) return;
        const b = $(this), id = b.data('id');
        b.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url: '../../modules/entry/stock_adjustment_entry.php',
            type: 'POST',
            data: { action: 'delete', record_id: id },
            dataType: 'json',
            success: function(r) {
                if (r.success) {
                    showNotification(r.message, 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification(r.message, 'error');
                }
            },
            complete: function() {
                b.prop('disabled', false).html('<i class="fas fa-trash"></i>');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
