<?php
$pageTitle = 'Employee Management';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
$data = $objQuery->index("SELECT * FROM mst_employee WHERE IsDeleted=0 ORDER BY Id ASC");
$categories = $objQuery->index("SELECT ExpenseCategoryID, CategoryNameEN, CategoryNameBN FROM mst_expensecategory WHERE IsDeleted=0 AND IsActive=1 ORDER BY ExpenseCategoryID ASC");
?>
<div class="table-container">
    <div class="table-header"><h5><i class="fas fa-user-tie text-primary me-2"></i><?php echo t('Employee Management'); ?></h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New</button></div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>ID</th><th>Name (EN)</th><th>Name (BN)</th><th>Mobile</th><th>NID</th><th>Salary</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody><?php $sl=1; foreach($data as $row): ?><tr>
            <td><?php echo $sl++; ?></td><td><?php echo htmlspecialchars($row->EmployeeId ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row->NameEN); ?></td><td><?php echo htmlspecialchars($row->NameBN ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row->Mobile); ?></td><td><?php echo htmlspecialchars($row->NationalID); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->Salary, 2); ?></td>
            <td><span class="badge bg-<?php echo $row->IsActive?'success':'danger'; ?>"><?php echo $row->IsActive?'Active':'Inactive'; ?></span></td>
            <td><button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->Id; ?>" data-eid="<?php echo htmlspecialchars($row->EmployeeId ?? ''); ?>" data-expcat="<?php echo $row->ExpenseCategoryID ?? 11; ?>" data-nameen="<?php echo htmlspecialchars($row->NameEN); ?>" data-namebn="<?php echo htmlspecialchars($row->NameBN ?? ''); ?>" data-father="<?php echo htmlspecialchars($row->FatherName ?? ''); ?>" data-mother="<?php echo htmlspecialchars($row->MotherName ?? ''); ?>" data-dob="<?php echo $row->DateOfBirth; ?>" data-join="<?php echo $row->JoiningDate; ?>" data-mobile="<?php echo htmlspecialchars($row->Mobile); ?>" data-address="<?php echo htmlspecialchars($row->Address ?? ''); ?>" data-nid="<?php echo htmlspecialchars($row->NationalID); ?>" data-guarantor="<?php echo htmlspecialchars($row->Guarantor ?? ''); ?>" data-salary="<?php echo $row->Salary; ?>" data-remarks="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>" data-status="<?php echo $row->IsActive; ?>"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->Id; ?>"><i class="fas fa-trash"></i></button></td>
        </tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form id="dataForm" method="POST" action="../../modules/entry/employee_entry.php">
        <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Employee</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Expense Category</label>
                    <select name="expense_category_id" id="expense_category_id" class="form-select">
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat->ExpenseCategoryID; ?>" <?php echo ($cat->ExpenseCategoryID == 11) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat->CategoryNameEN . ($cat->CategoryNameBN ? ' ('.$cat->CategoryNameBN.')' : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3"><label class="form-label">Name (EN) <span class="text-danger">*</span></label><input type="text" name="name_en" id="name_en" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Name (BN) <span class="text-danger">*</span></label><input type="text" name="name_bn" id="name_bn" class="form-control" required></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Father's Name</label><input type="text" name="father" id="father" class="form-control"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Mother's Name</label><input type="text" name="mother" id="mother" class="form-control"></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Date of Birth</label><input type="date" name="dob" id="dob" class="form-control"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Joining Date</label><input type="date" name="joining" id="joining" class="form-control"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Mobile</label><input type="text" name="mobile" id="mobile" class="form-control"></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">NID</label><input type="text" name="nid" id="nid" class="form-control"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Salary</label><input type="number" step="0.01" name="salary" id="salary" class="form-control" value="0"></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Guarantor</label><input type="text" name="guarantor" id="guarantor" class="form-control"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Address</label><textarea name="address" id="address" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Remarks</label><textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea></div>
                <div class="col-md-6 mb-3"><label class="form-label">Status</label><select name="is_active" id="is_active" class="form-select"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
    </form>
</div></div></div>
<script>
$(document).ready(function() {
    $('#addModal').on('show.bs.modal', function(e) { if(!$(e.relatedTarget).hasClass('edit-btn')){ $('#dataForm')[0].reset();$('#edit_id').val('');$('#expense_category_id').val('11');$('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Employee'); } });
    $(document).on('click', '.edit-btn', function() { const b=$(this);$('#edit_id').val(b.data('id'));$('#expense_category_id').val(b.data('expcat') || 11);$('#name_en').val(b.data('nameen'));$('#name_bn').val(b.data('namebn'));$('#father').val(b.data('father'));$('#mother').val(b.data('mother'));$('#dob').val(b.data('dob'));$('#joining').val(b.data('join'));$('#mobile').val(b.data('mobile'));$('#address').val(b.data('address'));$('#nid').val(b.data('nid'));$('#guarantor').val(b.data('guarantor'));$('#salary').val(b.data('salary'));$('#remarks').val(b.data('remarks'));$('#is_active').val(b.data('status'));$('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Employee');$('#addModal').modal('show'); });
    $('#dataForm').on('submit', function(e) { e.preventDefault();const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');$.ajax({url:f.attr('action'),type:'POST',data:f.serialize(),dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');$('#addModal').modal('hide');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},error:function(){showNotification('Error!','error');},complete:function(){btn.prop('disabled',false).html(orig);}});});
    $(document).on('click', '.delete-btn', function() { if(!confirmDelete()) return;const b=$(this),id=b.data('id');b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');$.ajax({url:'../../modules/entry/employee_entry.php',type:'POST',data:{action:'delete',record_id:id},dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},complete:function(){b.prop('disabled',false).html('<i class="fas fa-trash"></i>');}});});
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>