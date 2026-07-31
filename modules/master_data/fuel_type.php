<?php
/**
 * FuelDeskPro - Fuel Type Management (Master Data)
 * 
 * @package FuelDeskPro
 */

$pageTitle = 'Fuel Type Management';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$sql = "SELECT ft.*, uom.UnitNameEN, uom.UnitNameBN 
        FROM mst_fueltype ft 
        LEFT JOIN mst_unitofmeasure uom ON ft.UnitOfMeasure = uom.Id 
        WHERE ft.IsDeleted = 0 
        ORDER BY ft.FuelTypeID ASC";
$data = $objQuery->index($sql);

$units = $objQuery->index("SELECT * FROM mst_unitofmeasure ORDER BY UnitNameEN ASC");
?>

<div class="table-container">
    <div class="table-header">
        <h5><i class="fas fa-oil-can text-primary me-2"></i><?php echo t('Fuel Type Management'); ?></h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Add New Fuel Type
        </button>
    </div>
    <table class="table table-hover datatable">
        <thead>
            <tr>
                <th>SL</th>
                <th>Fuel Name</th>
                <th>Code</th>
                <th>Unit</th>
                <th>Selling Rate</th>
                <th>Purchase Rate</th>
                <th>Commission</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $sl = 1; foreach ($data as $row): ?>
            <tr>
                <td><?php echo $sl++; ?></td>
                <td><?php echo htmlspecialchars($row->FuelName); ?></td>
                <td><?php echo htmlspecialchars($row->FuelCode ?? 'N/A'); ?></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row->UnitNameEN ?? 'N/A'); ?></span></td>
                <td><?php echo $currencySymbol . ' ' . number_format($row->SellingRate, 2); ?></td>
                <td><?php echo $currencySymbol . ' ' . number_format($row->PurchaseRate, 2); ?></td>
                <td><?php echo number_format($row->CommissionRate, 4); ?></td>
                <td>
                    <span class="badge bg-<?php echo $row->IsActive ? 'success' : 'danger'; ?>">
                        <?php echo $row->IsActive ? 'Active' : 'Inactive'; ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-info edit-btn" 
                        data-id="<?php echo $row->FuelTypeID; ?>"
                        data-name="<?php echo htmlspecialchars($row->FuelName); ?>"
                        data-code="<?php echo htmlspecialchars($row->FuelCode ?? ''); ?>"
                        data-unit="<?php echo htmlspecialchars($row->UnitOfMeasure ?? ''); ?>"
                        data-selling="<?php echo $row->SellingRate; ?>"
                        data-purchase="<?php echo $row->PurchaseRate; ?>"
                        data-commission="<?php echo $row->CommissionRate; ?>"
                        data-tax="<?php echo $row->TaxPercent; ?>"
                        data-density="<?php echo $row->Density; ?>"
                        data-color="<?php echo htmlspecialchars($row->ColorCode ?? ''); ?>"
                        data-remarks="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>"
                        data-status="<?php echo $row->IsActive; ?>"
                        title="Edit"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->FuelTypeID; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="dataForm" method="POST" action="../../modules/entry/fuel_type_entry.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="record_id" id="edit_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Fuel Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fuel Name <span class="text-danger">*</span></label>
                            <input type="text" name="fuel_name" id="fuel_name" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fuel Code</label>
                            <input type="text" name="fuel_code" id="fuel_code" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Unit of Measure <span class="text-danger">*</span></label>
                            <select name="unit_of_measure" id="unit_of_measure" class="form-select" required>
                                <option value="">Select Unit</option>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?php echo $u->Id; ?>">
                                        <?php echo htmlspecialchars($u->UnitNameEN . ($u->UnitNameBN ? ' (' . $u->UnitNameBN . ')' : '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Selling Rate (<?php echo $currencySymbol; ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="selling_rate" id="selling_rate" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Purchase Rate (<?php echo $currencySymbol; ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="purchase_rate" id="purchase_rate" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Commission Rate</label>
                            <input type="number" step="0.0001" name="commission_rate" id="commission_rate" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tax (%)</label>
                            <input type="number" step="0.001" name="tax_percent" id="tax_percent" class="form-control" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Density</label>
                            <input type="number" step="0.0001" name="density" id="density" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Color Code</label>
                            <input type="text" name="color_code" id="color_code" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="is_active" id="is_active" class="form-select">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
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
            $('#unit_of_measure').val('');
            $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Fuel Type');
        }
    });
    $(document).on('click', '.edit-btn', function() {
        const b = $(this);
        $('#edit_id').val(b.data('id'));
        $('#fuel_name').val(b.data('name'));
        $('#fuel_code').val(b.data('code'));
        $('#unit_of_measure').val(b.data('unit'));
        $('#selling_rate').val(b.data('selling'));
        $('#purchase_rate').val(b.data('purchase'));
        $('#commission_rate').val(b.data('commission'));
        $('#tax_percent').val(b.data('tax'));
        $('#density').val(b.data('density'));
        $('#color_code').val(b.data('color'));
        $('#remarks').val(b.data('remarks'));
        $('#is_active').val(b.data('status'));
        $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Fuel Type');
        $('#addModal').modal('show');
    });
    $('#dataForm').on('submit', function(e) {
        e.preventDefault();
        const f = $(this), btn = f.find('[type="submit"]'), orig = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        $.ajax({
            url: f.attr('action'), type: 'POST', data: f.serialize(), dataType: 'json',
            success: function(r) { if(r.success) { showNotification(r.message,'success'); $('#addModal').modal('hide'); setTimeout(()=>location.reload(),500); } else showNotification(r.message,'error'); },
            error: function() { showNotification('Error occurred!','error'); },
            complete: function() { btn.prop('disabled',false).html(orig); }
        });
    });
    $(document).on('click', '.delete-btn', function() {
        if(!confirmDelete()) return;
        const b = $(this), id = b.data('id');
        b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url: '../../modules/entry/fuel_type_entry.php', type: 'POST',
            data: { action: 'delete', record_id: id }, dataType: 'json',
            success: function(r) { if(r.success) { showNotification(r.message,'success'); setTimeout(()=>location.reload(),500); } else showNotification(r.message,'error'); },
            complete: function() { b.prop('disabled',false).html('<i class="fas fa-trash"></i>'); }
        });
    });
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>