<?php
$pageTitle = 'Due Collections';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
$customers = $objQuery->index("SELECT CustomerID, CustomerName, Mobile FROM mst_customer WHERE IsActive=1 AND IsDeleted=0");
$methods = $objQuery->index("SELECT PaymentMethodID, MethodName FROM cfg_paymentmethod WHERE IsActive=1 AND IsDeleted=0");
$data = $objQuery->index("SELECT cc.*, c.CustomerName, pm.MethodName FROM trx_customercollection cc LEFT JOIN mst_customer c ON cc.CustomerID=c.CustomerID LEFT JOIN cfg_paymentmethod pm ON cc.PaymentMethodID=pm.PaymentMethodID WHERE cc.IsDeleted=0 ORDER BY cc.TxnDate DESC, cc.CustomerCollectionID DESC");
?>
<div class="table-container">
    <div class="table-header"><h5><i class="fas fa-hand-holding-usd text-primary me-2"></i>Due Collections</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New Collection</button></div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>Date</th><th>Customer</th><th>Amount</th><th>Payment Method</th><th>Reference</th><th>Actions</th></tr></thead>
        <tbody><?php $sl=1; foreach($data as $row): ?><tr>
            <td><?php echo $sl++; ?></td><td><?php echo formatDate($row->TxnDate); ?></td>
            <td><?php echo htmlspecialchars($row->CustomerName ?? 'N/A'); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->Amount, 2); ?></td>
            <td><?php echo htmlspecialchars($row->MethodName ?? $row->PaymentMethodID); ?></td>
            <td><?php echo htmlspecialchars($row->ReferenceNo ?? ''); ?></td>
            <td>
                <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->CustomerCollectionID; ?>" data-date="<?php echo $row->TxnDate; ?>" data-customer="<?php echo $row->CustomerID; ?>" data-amount="<?php echo $row->Amount; ?>" data-method="<?php echo $row->PaymentMethodID; ?>" data-ref="<?php echo htmlspecialchars($row->ReferenceNo ?? ''); ?>" data-remarks="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->CustomerCollectionID; ?>"><i class="fas fa-trash"></i></button>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form id="dataForm" method="POST" action="../../modules/entry/due_collection_entry.php">
        <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Collection</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" name="txn_date" id="txn_date" class="form-control" required value="<?php echo today(); ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" id="customer_id" class="form-select select2" required><option value="">Select Customer</option>
                    <?php foreach($customers as $c): ?><option value="<?php echo $c->CustomerID; ?>"><?php echo htmlspecialchars($c->CustomerName.' ('.$c->Mobile.')'); ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Amount <span class="text-danger">*</span></label><input type="number" step="0.01" name="amount" id="amount" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select name="payment_method" id="payment_method" class="form-select select2" required><option value="">Select Method</option>
                    <?php foreach($methods as $m): ?><option value="<?php echo $m->PaymentMethodID; ?>"><?php echo htmlspecialchars($m->MethodName); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4 mb-3"><label class="form-label">Reference No</label><input type="text" name="reference_no" id="reference_no" class="form-control"></div>
            </div>
            <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
    </form>
</div></div></div>
<script>
$(document).ready(function() {
    $('#addModal').on('show.bs.modal', function(e) { if(!$(e.relatedTarget).hasClass('edit-btn')){ $('#dataForm')[0].reset();$('#edit_id').val('');$('#txn_date').val('<?php echo today(); ?>');$('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Collection'); } });
    $(document).on('click', '.edit-btn', function() { const b=$(this);$('#edit_id').val(b.data('id'));$('#txn_date').val(b.data('date'));$('#customer_id').val(b.data('customer')).trigger('change');$('#amount').val(b.data('amount'));$('#payment_method').val(b.data('method')).trigger('change');$('#reference_no').val(b.data('ref'));$('#remarks').val(b.data('remarks'));$('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Collection');$('#addModal').modal('show'); });
    $('#dataForm').on('submit', function(e) { e.preventDefault();const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');$.ajax({url:f.attr('action'),type:'POST',data:f.serialize(),dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');$('#addModal').modal('hide');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},error:function(){showNotification('Error!','error');},complete:function(){btn.prop('disabled',false).html(orig);}});});
    $(document).on('click', '.delete-btn', function() { if(!confirmDelete()) return;const b=$(this),id=b.data('id');b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');$.ajax({url:'../../modules/entry/due_collection_entry.php',type:'POST',data:{action:'delete',record_id:id},dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},complete:function(){b.prop('disabled',false).html('<i class="fas fa-trash"></i>');}});});
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
