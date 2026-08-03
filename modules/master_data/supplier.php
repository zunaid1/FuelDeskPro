<?php
$pageTitle = 'Supplier Management';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
$data = $objQuery->index("SELECT * FROM mst_supplier WHERE IsDeleted=0 ORDER BY SupplierID ASC");
?>
<div class="table-container">
    <div class="table-header"><h5><i class="fas fa-truck text-primary me-2"></i><?php echo t('Supplier Management'); ?></h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New</button></div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>Supplier Name</th><th>Contact Person</th><th>Mobile</th><th>Email</th><th>Opening Balance</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody><?php $sl=1; foreach($data as $row): ?><tr>
            <td><?php echo $sl++; ?></td><td><?php echo htmlspecialchars($row->SupplierName); ?></td>
            <td><?php echo htmlspecialchars($row->ContactPerson ?? 'N/A'); ?></td><td><?php echo htmlspecialchars($row->Mobile ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row->Email ?? 'N/A'); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->OpeningBalance, 2); ?></td>
            <td><span class="badge bg-<?php echo $row->IsActive?'success':'danger'; ?>"><?php echo $row->IsActive?'Active':'Inactive'; ?></span></td>
            <td><button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->SupplierID; ?>" data-name="<?php echo htmlspecialchars($row->SupplierName); ?>" data-contact="<?php echo htmlspecialchars($row->ContactPerson ?? ''); ?>" data-mobile="<?php echo htmlspecialchars($row->Mobile ?? ''); ?>" data-email="<?php echo htmlspecialchars($row->Email ?? ''); ?>" data-address="<?php echo htmlspecialchars($row->Address ?? ''); ?>" data-balance="<?php echo $row->OpeningBalance; ?>" data-status="<?php echo $row->IsActive; ?>"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->SupplierID; ?>"><i class="fas fa-trash"></i></button></td>
        </tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form id="dataForm" method="POST" action="../../modules/entry/supplier_entry.php">
        <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Supplier Name <span class="text-danger">*</span></label><input type="text" name="supplier_name" id="supplier_name" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Contact Person</label><input type="text" name="contact_person" id="contact_person" class="form-control"></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Mobile</label><input type="text" name="mobile" id="mobile" class="form-control"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" id="email" class="form-control"></div>
            </div>
            <div class="row">
                <div class="col-md-8 mb-3"><label class="form-label">Address</label><textarea name="address" id="address" class="form-control" rows="2"></textarea></div>
                <div class="col-md-4 mb-3"><label class="form-label">Opening Balance</label><input type="number" step="0.01" name="opening_balance" id="opening_balance" class="form-control" value="0"></div>
            </div>
            <div class="mb-3"><label class="form-label">Status</label><select name="is_active" id="is_active" class="form-select"><option value="1">Active</option><option value="0">Inactive</option></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
    </form>
</div></div></div>
<script>
$(document).ready(function() {
    $('#addModal').on('show.bs.modal', function(e) { if(!$(e.relatedTarget).hasClass('edit-btn')){ $('#dataForm')[0].reset();$('#edit_id').val('');$('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Supplier'); } });
    $(document).on('click', '.edit-btn', function() { const b=$(this);$('#edit_id').val(b.data('id'));$('#supplier_name').val(b.data('name'));$('#contact_person').val(b.data('contact'));$('#mobile').val(b.data('mobile'));$('#email').val(b.data('email'));$('#address').val(b.data('address'));$('#opening_balance').val(b.data('balance'));$('#is_active').val(b.data('status'));$('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Supplier');$('#addModal').modal('show'); });
    $('#dataForm').on('submit', function(e) { e.preventDefault();const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');$.ajax({url:f.attr('action'),type:'POST',data:f.serialize(),dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');$('#addModal').modal('hide');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},error:function(){showNotification('Error!','error');},complete:function(){btn.prop('disabled',false).html(orig);}});});
    $(document).on('click', '.delete-btn', function() { if(!confirmDelete()) return;const b=$(this),id=b.data('id');b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');$.ajax({url:'../../modules/entry/supplier_entry.php',type:'POST',data:{action:'delete',record_id:id},dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},complete:function(){b.prop('disabled',false).html('<i class="fas fa-trash"></i>');}});});
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>