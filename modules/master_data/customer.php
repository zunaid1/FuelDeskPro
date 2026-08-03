<?php
$pageTitle = 'Customer Management';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
$data = $objQuery->index("SELECT * FROM mst_customer WHERE IsDeleted=0 ORDER BY CustomerID ASC");
?>
<div class="table-container">
    <div class="table-header"><h5><i class="fas fa-user-friends text-primary me-2"></i><?php echo t('Customer Management'); ?></h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New</button></div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>Customer Name</th><th>Mobile</th><th>Vehicle No.</th><th>Address</th><th>Opening Due</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody><?php $sl=1; foreach($data as $row): ?><tr>
            <td><?php echo $sl++; ?></td><td><?php echo htmlspecialchars($row->CustomerName); ?></td><td><?php echo htmlspecialchars($row->Mobile); ?></td>
            <td><?php echo htmlspecialchars($row->VehicleNumber ?? 'N/A'); ?></td><td><?php echo htmlspecialchars($row->Address ?? 'N/A'); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->OpeningDue, 2); ?></td>
            <td><span class="badge bg-<?php echo $row->IsActive?'success':'danger'; ?>"><?php echo $row->IsActive?'Active':'Inactive'; ?></span></td>
            <td><button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->CustomerID; ?>" data-name="<?php echo htmlspecialchars($row->CustomerName); ?>" data-mobile="<?php echo htmlspecialchars($row->Mobile); ?>" data-vehicle="<?php echo htmlspecialchars($row->VehicleNumber ?? ''); ?>" data-address="<?php echo htmlspecialchars($row->Address ?? ''); ?>" data-due="<?php echo $row->OpeningDue; ?>" data-status="<?php echo $row->IsActive; ?>"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->CustomerID; ?>"><i class="fas fa-trash"></i></button></td>
        </tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form id="dataForm" method="POST" action="../../modules/entry/customer_entry.php">
        <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Customer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Customer Name <span class="text-danger">*</span></label><input type="text" name="customer_name" id="customer_name" class="form-control" required></div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Mobile <span class="text-danger">*</span></label><input type="text" name="mobile" id="mobile" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Vehicle Number</label><input type="text" name="vehicle_no" id="vehicle_no" class="form-control"></div>
            </div>
            <div class="row">
                <div class="col-md-8 mb-3"><label class="form-label">Address</label><textarea name="address" id="address" class="form-control" rows="2"></textarea></div>
                <div class="col-md-4 mb-3"><label class="form-label">Opening Due</label><input type="number" step="0.01" name="opening_due" id="opening_due" class="form-control" value="0"></div>
            </div>
            <div class="mb-3"><label class="form-label">Status</label><select name="is_active" id="is_active" class="form-select"><option value="1">Active</option><option value="0">Inactive</option></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
    </form>
</div></div></div>
<script>
$(document).ready(function() {
    $('#addModal').on('show.bs.modal', function(e) { if(!$(e.relatedTarget).hasClass('edit-btn')){ $('#dataForm')[0].reset();$('#edit_id').val('');$('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Customer'); } });
    $(document).on('click', '.edit-btn', function() { const b=$(this);$('#edit_id').val(b.data('id'));$('#customer_name').val(b.data('name'));$('#mobile').val(b.data('mobile'));$('#vehicle_no').val(b.data('vehicle'));$('#address').val(b.data('address'));$('#opening_due').val(b.data('due'));$('#is_active').val(b.data('status'));$('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Customer');$('#addModal').modal('show'); });
    $('#dataForm').on('submit', function(e) { e.preventDefault();const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');$.ajax({url:f.attr('action'),type:'POST',data:f.serialize(),dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');$('#addModal').modal('hide');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},error:function(){showNotification('Error!','error');},complete:function(){btn.prop('disabled',false).html(orig);}});});
    $(document).on('click', '.delete-btn', function() { if(!confirmDelete()) return;const b=$(this),id=b.data('id');b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');$.ajax({url:'../../modules/entry/customer_entry.php',type:'POST',data:{action:'delete',record_id:id},dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},complete:function(){b.prop('disabled',false).html('<i class="fas fa-trash"></i>');}});});
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>