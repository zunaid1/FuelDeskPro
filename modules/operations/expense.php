<?php
$pageTitle = 'Expense';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
$particulars = $objQuery->index("SELECT ParticularID, ParticularNameEN,ParticularNameBN FROM vw_expparticular ORDER BY ParticularNameEN");
$methods = $objQuery->index("SELECT PaymentMethodID, MethodName FROM cfg_paymentmethod WHERE IsActive=1 AND IsDeleted=0");
$data = $objQuery->index("SELECT e.*, COALESCE(ep.ParticularNameEN, emp.NameEN, e.ParticularID) AS ParticularNameEN, COALESCE(ep.ParticularNameBN, emp.NameBN, emp.NameEN, e.ParticularID) AS ParticularNameBN, pm.MethodName FROM trx_expense e LEFT JOIN mst_expenseparticular ep ON (e.ParticularID=ep.ParticularID OR e.ParticularID=CAST(ep.ExpenseParticularID AS CHAR)) AND ep.IsDeleted=0 LEFT JOIN mst_employee emp ON (e.ParticularID=emp.EmployeeId OR e.ParticularID=CAST(emp.Id AS CHAR)) AND emp.IsDeleted=0 LEFT JOIN cfg_paymentmethod pm ON e.PaymentMethodID=pm.PaymentMethodID WHERE e.IsDeleted=0 ORDER BY e.ExpenseDate DESC, e.ExpenseID DESC");
?>
<?php if (isStatementClosed(today())): ?>
<div class="alert alert-danger shadow-sm border-danger text-center fw-bold fs-6 mb-3 py-2">
    <i class="fas fa-lock me-2"></i> আজকের তারিখের (<?php echo date('d-m-Y'); ?>) হিসাবটি ইতোমধ্যে ক্লোজ করা হয়েছে
</div>
<?php endif; ?>
<div class="table-container">
    <div class="table-header"><h5><i class="fas fa-receipt text-primary me-2"></i>Expense</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New Expense</button></div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>Date</th><th>Particular</th><th>Amount</th><th>Payment Method</th><th>Reference</th><th>Actions</th></tr></thead>
        <tbody><?php $sl=1; foreach($data as $row): ?><tr>
            <td><?php echo $sl++; ?></td><td><?php echo formatDate($row->ExpenseDate); ?></td>
            <td><?php echo htmlspecialchars($row->ParticularNameEN ?? $row->ParticularID); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->Amount, 2); ?></td>
            <td><?php echo htmlspecialchars($row->MethodName ?? $row->PaymentMethodID); ?></td>
            <td><?php echo htmlspecialchars($row->ReferenceNo ?? ''); ?></td>
            <td>
                <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->ExpenseID; ?>" data-date="<?php echo $row->ExpenseDate; ?>" data-particular="<?php echo htmlspecialchars($row->ParticularID ?? ''); ?>" data-amount="<?php echo $row->Amount; ?>" data-method="<?php echo $row->PaymentMethodID; ?>" data-ref="<?php echo htmlspecialchars($row->ReferenceNo ?? ''); ?>" data-remarks="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->ExpenseID; ?>"><i class="fas fa-trash"></i></button>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form id="dataForm" method="POST" action="../../modules/entry/expense_entry.php">
        <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <!-- Line 1: Date & Reference No -->
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" name="expense_date" id="expense_date" class="form-control" required value="<?php echo today(); ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Reference No</label><input type="text" name="reference_no" id="reference_no" class="form-control"></div>
            </div>
            <!-- Line 2: Particular -->
            <div class="row">
                <div class="col-md-12 mb-3"><label class="form-label">Particular <span class="text-danger">*</span></label>
                    <select name="particular_id" id="particular_id" class="form-select select2" required><option value="">Select Particular</option>
                    <?php foreach($particulars as $p): ?><option value="<?php echo $p->ParticularID; ?>"><?php echo htmlspecialchars($p->ParticularNameBN); ?></option><?php endforeach; ?></select></div>
            </div>
            <!-- Line 3: Payment Method & Amount -->
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Payment Method</label>
                    <select name="payment_method" id="payment_method" class="form-select select2"><option value="">Select Method</option>
                    <?php foreach($methods as $m): ?><option value="<?php echo $m->PaymentMethodID; ?>" <?php echo $m->MethodName === 'Cash' ? 'selected' : ''; ?>><?php echo htmlspecialchars($m->MethodName); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6 mb-3"><label class="form-label">Amount <span class="text-danger">*</span></label><input type="number" step="0.01" name="amount" id="amount" class="form-control" required></div>
            </div>
            <!-- Line 4: Remarks -->
            <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
    </form>
</div></div></div>
<script>
$(document).ready(function() {
    $('#addModal').on('show.bs.modal', function(e) { if(!$(e.relatedTarget).hasClass('edit-btn')){ $('#dataForm')[0].reset();$('#edit_id').val('');$('#expense_date').val('<?php echo today(); ?>');$('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Expense'); } });
    $(document).on('click', '.edit-btn', function() { const b=$(this);$('#edit_id').val(b.data('id'));$('#expense_date').val(b.data('date'));$('#particular_id').val(b.data('particular')).trigger('change');$('#amount').val(b.data('amount'));$('#payment_method').val(b.data('method')).trigger('change');$('#reference_no').val(b.data('ref'));$('#remarks').val(b.data('remarks'));$('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Expense');$('#addModal').modal('show'); });
    $('#dataForm').on('submit', function(e) { e.preventDefault();const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');$.ajax({url:f.attr('action'),type:'POST',data:f.serialize(),dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');$('#addModal').modal('hide');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},error:function(){showNotification('Error!','error');},complete:function(){btn.prop('disabled',false).html(orig);}});});
    $(document).on('click', '.delete-btn', function() { if(!confirmDelete()) return;const b=$(this),id=b.data('id');b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');$.ajax({url:'../../modules/entry/expense_entry.php',type:'POST',data:{action:'delete',record_id:id},dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},complete:function(){b.prop('disabled',false).html('<i class="fas fa-trash"></i>');}});});
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
