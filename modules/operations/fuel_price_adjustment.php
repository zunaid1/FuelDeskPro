<?php
$pageTitle = 'Fuel Price Adjustment';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
$fuels = $objQuery->index("SELECT FuelTypeID, FuelName, SellingRate, PurchaseRate FROM mst_fueltype WHERE IsActive=1 AND IsDeleted=0");
$data = $objQuery->index("SELECT fpa.*, ft.FuelName FROM trx_fuelpriceadjustment fpa LEFT JOIN mst_fueltype ft ON fpa.FuelTypeID=ft.FuelTypeID WHERE fpa.IsDeleted=0 ORDER BY fpa.EffectiveDate DESC, fpa.FuelPriceAdjustmentID DESC");
?>
<div class="table-container">
    <div class="table-header"><h5><i class="fas fa-sliders-h text-primary me-2"></i>Fuel Price Adjustment</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New Adjustment</button></div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>Date</th><th>Fuel</th><th>Old Price</th><th>New Price</th><th>Stock Qty</th><th>Diff</th><th>Adjustment</th><th>Type</th><th>Actions</th></tr></thead>
        <tbody><?php $sl=1; foreach($data as $row): ?><tr>
            <td><?php echo $sl++; ?></td><td><?php echo formatDate($row->EffectiveDate); ?></td>
            <td><?php echo htmlspecialchars($row->FuelName ?? 'N/A'); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->OldSellingPrice, 4); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->NewSellingPrice, 4); ?></td>
            <td><?php echo number_format($row->StockQuantity, 3); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->PriceDifference, 4); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->AdjustmentAmount, 2); ?></td>
            <td><span class="badge bg-<?php echo $row->AdjustmentType=='Profit'?'success':'warning'; ?>"><?php echo $row->AdjustmentType; ?></span></td>
            <td>
                <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->FuelPriceAdjustmentID; ?>" data-date="<?php echo $row->EffectiveDate; ?>" data-fuel="<?php echo $row->FuelTypeID; ?>" data-old="<?php echo $row->OldSellingPrice; ?>" data-new="<?php echo $row->NewSellingPrice; ?>" data-stock="<?php echo $row->StockQuantity; ?>" data-ref="<?php echo htmlspecialchars($row->ReferenceNo ?? ''); ?>" data-remarks="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->FuelPriceAdjustmentID; ?>"><i class="fas fa-trash"></i></button>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form id="dataForm" method="POST" action="../../modules/entry/fuel_price_adjustment_entry.php">
        <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Adjustment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Effective Date <span class="text-danger">*</span></label><input type="date" name="effective_date" id="effective_date" class="form-control" required value="<?php echo today(); ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Fuel Type <span class="text-danger">*</span></label>
                    <select name="fuel_type_id" id="fuel_type_id" class="form-select select2" required><option value="">Select Fuel</option>
                    <?php foreach($fuels as $f): ?><option value="<?php echo $f->FuelTypeID; ?>" data-old="<?php echo $f->SellingRate; ?>"><?php echo htmlspecialchars($f->FuelName); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4 mb-3"><label class="form-label">Stock Quantity <span class="text-danger">*</span></label><input type="number" step="0.001" name="stock_qty" id="stock_qty" class="form-control" required></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Old Selling Price</label><input type="number" step="0.0001" name="old_price" id="old_price" class="form-control" readonly></div>
                <div class="col-md-6 mb-3"><label class="form-label">New Selling Price <span class="text-danger">*</span></label><input type="number" step="0.0001" name="new_price" id="new_price" class="form-control" required></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Reference No</label><input type="text" name="reference_no" id="reference_no" class="form-control"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Remarks</label><textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
    </form>
</div></div></div>
<script>
$(document).ready(function() {
    $('#fuel_type_id').on('change', function() {
        const selected = $(this).find(':selected');
        const oldPrice = selected.data('old') || 0;
        $('#old_price').val(oldPrice);
    });
    $('#addModal').on('show.bs.modal', function(e) { if(!$(e.relatedTarget).hasClass('edit-btn')){ $('#dataForm')[0].reset();$('#edit_id').val('');$('#effective_date').val('<?php echo today(); ?>');$('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Adjustment'); } });
    $(document).on('click', '.edit-btn', function() { const b=$(this);$('#edit_id').val(b.data('id'));$('#effective_date').val(b.data('date'));$('#fuel_type_id').val(b.data('fuel')).trigger('change');$('#old_price').val(b.data('old'));$('#new_price').val(b.data('new'));$('#stock_qty').val(b.data('stock'));$('#reference_no').val(b.data('ref'));$('#remarks').val(b.data('remarks'));$('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Adjustment');$('#addModal').modal('show'); });
    $('#dataForm').on('submit', function(e) { e.preventDefault();const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');$.ajax({url:f.attr('action'),type:'POST',data:f.serialize(),dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');$('#addModal').modal('hide');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},error:function(){showNotification('Error!','error');},complete:function(){btn.prop('disabled',false).html(orig);}});});
    $(document).on('click', '.delete-btn', function() { if(!confirmDelete()) return;const b=$(this),id=b.data('id');b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');$.ajax({url:'../../modules/entry/fuel_price_adjustment_entry.php',type:'POST',data:{action:'delete',record_id:id},dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},complete:function(){b.prop('disabled',false).html('<i class="fas fa-trash"></i>');}});});
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>