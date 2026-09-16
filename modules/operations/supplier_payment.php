<?php
$pageTitle = 'Supplier Payment';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Ensure BankAccountID column in trx_supplierpayment and ensure 'Bank' exists in cfg_paymentmethod
try {
    $checkCol = $objQuery->index("SHOW COLUMNS FROM `trx_supplierpayment` LIKE 'BankAccountID'");
    if (empty($checkCol)) {
        $objQuery->inUpDel("ALTER TABLE `trx_supplierpayment` ADD COLUMN `BankAccountID` INT(11) NULL DEFAULT NULL AFTER `PaymentMethodID`");
    }
    $checkBank = $objQuery->index("SELECT PaymentMethodID FROM cfg_paymentmethod WHERE MethodName='Bank' AND IsDeleted=0");
    if (empty($checkBank)) {
        $objQuery->inUpDel("INSERT INTO cfg_paymentmethod (MethodName, MethodCode, IsActive, IsDeleted) VALUES ('Bank', 'BANK', 1, 0)");
    }
} catch (Exception $e) {}

$suppliers = $objQuery->index("SELECT SupplierID, SupplierName FROM mst_supplier WHERE IsActive=1 AND IsDeleted=0 ORDER BY SupplierName ASC");
$methods = $objQuery->index("SELECT PaymentMethodID, MethodName FROM cfg_paymentmethod WHERE IsActive=1 AND IsDeleted=0 ORDER BY MethodName ASC");
$bankAccounts = $objQuery->index("SELECT BankAccountID, BankName, AccountName, AccountNumber FROM mst_bankaccount WHERE IsActive=1 AND IsDeleted=0 ORDER BY BankName ASC");
$data = $objQuery->index("SELECT sp.*, s.SupplierName, pm.MethodName, ba.BankName, ba.AccountName, ba.AccountNumber FROM trx_supplierpayment sp LEFT JOIN mst_supplier s ON sp.SupplierID=s.SupplierID LEFT JOIN cfg_paymentmethod pm ON sp.PaymentMethodID=pm.PaymentMethodID LEFT JOIN mst_bankaccount ba ON sp.BankAccountID=ba.BankAccountID WHERE sp.IsDeleted=0 ORDER BY sp.PaymentDate DESC, sp.SupplierPaymentID DESC");
?>
<div class="table-container">
    <div class="table-header"><h5><i class="fas fa-credit-card text-primary me-2"></i>Supplier Payment</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New Payment</button></div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>Date</th><th>Supplier</th><th>Amount</th><th>Payment Method</th><th>Reference</th><th>Actions</th></tr></thead>
        <tbody><?php $sl=1; foreach($data as $row): ?><tr>
            <td><?php echo $sl++; ?></td><td><?php echo formatDate($row->PaymentDate); ?></td>
            <td><?php echo htmlspecialchars($row->SupplierName ?? 'N/A'); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->Amount, 2); ?></td>
            <td>
                <?php echo htmlspecialchars($row->MethodName ?? $row->PaymentMethodID); ?>
                <?php if (!empty($row->BankAccountID) && !empty($row->BankName)): ?>
                    <br><small class="badge bg-info text-dark mt-1" title="<?php echo htmlspecialchars(($row->AccountName ?? '') . ' - ' . ($row->AccountNumber ?? '')); ?>"><i class="fas fa-university me-1"></i><?php echo htmlspecialchars($row->BankName . ' (' . substr($row->AccountNumber, -4) . ')'); ?></small>
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($row->ReferenceNo ?? ''); ?></td>
            <td>
                <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->SupplierPaymentID; ?>" data-date="<?php echo $row->PaymentDate; ?>" data-supplier="<?php echo $row->SupplierID; ?>" data-amount="<?php echo $row->Amount; ?>" data-method="<?php echo $row->PaymentMethodID; ?>" data-bank="<?php echo $row->BankAccountID ?? ''; ?>" data-ref="<?php echo htmlspecialchars($row->ReferenceNo ?? ''); ?>" data-remarks="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->SupplierPaymentID; ?>"><i class="fas fa-trash"></i></button>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form id="dataForm" method="POST" action="../../modules/entry/supplier_payment_entry.php">
        <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" name="payment_date" id="payment_date" class="form-control" required value="<?php echo today(); ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id" id="supplier_id" class="form-select select2" required><option value="">Select Supplier</option>
                    <?php foreach($suppliers as $s): ?><option value="<?php echo $s->SupplierID; ?>"><?php echo htmlspecialchars($s->SupplierName); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4 mb-3"><label class="form-label">Amount <span class="text-danger">*</span></label><input type="number" step="0.01" name="amount" id="amount" class="form-control" required></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select name="payment_method" id="payment_method" class="form-select select2" required><option value="">Select Method</option>
                    <?php foreach($methods as $m): ?><option value="<?php echo $m->PaymentMethodID; ?>"><?php echo htmlspecialchars($m->MethodName); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4 mb-3" id="bank_account_container" style="display:none;"><label class="form-label">Bank Account <span class="text-danger">*</span></label>
                    <select name="bank_account_id" id="bank_account_id" class="form-select select2"><option value="">Select Bank Account</option>
                    <?php foreach($bankAccounts as $b): ?><option value="<?php echo $b->BankAccountID; ?>"><?php echo htmlspecialchars($b->BankName . ' - ' . $b->AccountName . ' (' . $b->AccountNumber . ')'); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4 mb-3"><label class="form-label">Reference No</label><input type="text" name="reference_no" id="reference_no" class="form-control"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Remarks</label><textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
    </form>
</div></div></div>
<script>
function checkPaymentMethod() {
    const selectedText = $('#payment_method option:selected').text().trim().toLowerCase();
    if (selectedText.includes('bank')) {
        $('#bank_account_container').show();
        $('#bank_account_id').prop('required', true);
    } else {
        $('#bank_account_container').hide();
        $('#bank_account_id').prop('required', false).val('').trigger('change');
    }
}

$(document).ready(function() {
    $('#payment_method').on('change', function() {
        checkPaymentMethod();
    });

    $('#addModal').on('show.bs.modal', function(e) { 
        if(!$(e.relatedTarget).hasClass('edit-btn')){ 
            $('#dataForm')[0].reset();
            $('#edit_id').val('');
            $('#payment_date').val('<?php echo today(); ?>');
            $('#supplier_id').val('').trigger('change');
            $('#payment_method').val('').trigger('change');
            $('#bank_account_id').val('').trigger('change');
            checkPaymentMethod();
            $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Payment'); 
        } 
    });

    $(document).on('click', '.edit-btn', function() { 
        const b=$(this);
        $('#edit_id').val(b.data('id'));
        $('#payment_date').val(b.data('date'));
        $('#supplier_id').val(b.data('supplier')).trigger('change');
        $('#amount').val(b.data('amount'));
        $('#payment_method').val(b.data('method')).trigger('change');
        checkPaymentMethod();
        if (b.data('bank')) {
            $('#bank_account_id').val(b.data('bank')).trigger('change');
        } else {
            $('#bank_account_id').val('').trigger('change');
        }
        $('#reference_no').val(b.data('ref'));
        $('#remarks').val(b.data('remarks'));
        $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Payment');
        $('#addModal').modal('show'); 
    });

    $('#dataForm').on('submit', function(e) { 
        e.preventDefault();
        const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();
        btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        $.ajax({
            url:f.attr('action'),
            type:'POST',
            data:f.serialize(),
            dataType:'json',
            success:function(r){
                if(r.success){
                    showNotification(r.message,'success');
                    $('#addModal').modal('hide');
                    setTimeout(()=>location.reload(),500);
                } else {
                    showNotification(r.message,'error');
                }
            },
            error:function(){ showNotification('Error!','error'); },
            complete:function(){ btn.prop('disabled',false).html(orig); }
        });
    });

    $(document).on('click', '.delete-btn', function() { 
        if(!confirmDelete()) return;
        const b=$(this),id=b.data('id');
        b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url:'../../modules/entry/supplier_payment_entry.php',
            type:'POST',
            data:{action:'delete',record_id:id},
            dataType:'json',
            success:function(r){
                if(r.success){
                    showNotification(r.message,'success');
                    setTimeout(()=>location.reload(),500);
                } else {
                    showNotification(r.message,'error');
                }
            },
            complete:function(){ b.prop('disabled',false).html('<i class="fas fa-trash"></i>'); }
        });
    });
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
