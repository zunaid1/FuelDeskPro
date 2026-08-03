<?php
$pageTitle = 'Particular Group (Expense Category) Management';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
$data = $objQuery->index("SELECT * FROM mst_expensecategory WHERE IsDeleted=0 ORDER BY ExpenseCategoryID ASC");
?>
<div class="table-container">
    <div class="table-header"><h5><i class="fas fa-tags text-primary me-2"></i><?php echo t('Particular Group / Expense Category'); ?></h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New</button></div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>Category Name (EN)</th><th>Category Name (BN)</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody><?php $sl=1; foreach($data as $row): ?><tr>
            <td><?php echo $sl++; ?></td><td><?php echo htmlspecialchars($row->CategoryNameEN); ?></td><td><?php echo htmlspecialchars($row->CategoryNameBN ?? 'N/A'); ?></td>
            <td><span class="badge bg-<?php echo $row->IsActive?'success':'danger'; ?>"><?php echo $row->IsActive?'Active':'Inactive'; ?></span></td>
            <td><button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->ExpenseCategoryID; ?>" data-nameen="<?php echo htmlspecialchars($row->CategoryNameEN); ?>" data-namebn="<?php echo htmlspecialchars($row->CategoryNameBN ?? ''); ?>" data-status="<?php echo $row->IsActive; ?>"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->ExpenseCategoryID; ?>"><i class="fas fa-trash"></i></button></td>
        </tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form id="dataForm" method="POST" action="../../modules/entry/particular_group_entry.php">
        <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Category Name (EN) <span class="text-danger">*</span></label><input type="text" name="name_en" id="name_en" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Category Name (BN)</label><input type="text" name="name_bn" id="name_bn" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Status</label><select name="is_active" id="is_active" class="form-select"><option value="1">Active</option><option value="0">Inactive</option></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
    </form>
</div></div></div>
<script>
$(document).ready(function() {
    $('#addModal').on('show.bs.modal', function(e) { if(!$(e.relatedTarget).hasClass('edit-btn')){ $('#dataForm')[0].reset();$('#edit_id').val('');$('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Category'); } });
    $(document).on('click', '.edit-btn', function() { const b=$(this);$('#edit_id').val(b.data('id'));$('#name_en').val(b.data('nameen'));$('#name_bn').val(b.data('namebn'));$('#is_active').val(b.data('status'));$('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Category');$('#addModal').modal('show'); });
    $('#dataForm').on('submit', function(e) { e.preventDefault();const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');$.ajax({url:f.attr('action'),type:'POST',data:f.serialize(),dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');$('#addModal').modal('hide');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},error:function(){showNotification('Error!','error');},complete:function(){btn.prop('disabled',false).html(orig);}});});
    $(document).on('click', '.delete-btn', function() { if(!confirmDelete()) return;const b=$(this),id=b.data('id');b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');$.ajax({url:'../../modules/entry/particular_group_entry.php',type:'POST',data:{action:'delete',record_id:id},dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},complete:function(){b.prop('disabled',false).html('<i class="fas fa-trash"></i>');}});});
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>