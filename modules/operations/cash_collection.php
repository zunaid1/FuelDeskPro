<?php
$pageTitle = 'Cash Collections';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Auto-ensure mst_bankaccount table and BankAccountID column in trx_cashcollection exist
try {
    $objQuery->inUpDel("CREATE TABLE IF NOT EXISTS `mst_bankaccount` (
      `BankAccountID` INT AUTO_INCREMENT PRIMARY KEY,
      `BankName` VARCHAR(150) NOT NULL,
      `BranchName` VARCHAR(150) DEFAULT NULL,
      `AccountName` VARCHAR(150) NOT NULL,
      `AccountNumber` VARCHAR(50) NOT NULL,
      `AccountType` VARCHAR(50) DEFAULT 'Current',
      `RoutingNumber` VARCHAR(50) DEFAULT NULL,
      `OpeningBalance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
      `OpeningDate` DATE DEFAULT NULL,
      `CurrentBalance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
      `IsDefault` TINYINT(1) DEFAULT 0,
      `CreatedBy` INT DEFAULT NULL,
      `CreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `UpdatedBy` INT DEFAULT NULL,
      `UpdatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `IsActive` TINYINT(1) DEFAULT 1,
      `IsDeleted` TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    $checkCol = $objQuery->index("SHOW COLUMNS FROM `trx_cashcollection` LIKE 'BankAccountID'");
    if (empty($checkCol)) {
        $objQuery->inUpDel("ALTER TABLE `trx_cashcollection` ADD COLUMN `BankAccountID` INT(11) NULL DEFAULT NULL AFTER `CollectedPersonID`");
    }
} catch (Exception $e) {}

$employees = $objQuery->index("SELECT Id, NameEN, EmployeeId FROM mst_employee WHERE IsActive=1 AND IsDeleted=0");
$shareholders = $objQuery->index("SELECT Id, NameEN, ShareHolderID FROM mst_shareholder WHERE IsActive=1 AND IsDeleted=0");
$bankAccounts = $objQuery->index("SELECT BankAccountID, BankName, AccountName, AccountNumber FROM mst_bankaccount WHERE IsActive=1 AND IsDeleted=0 ORDER BY BankName ASC");
$data = $objQuery->index("SELECT c.*, b.BankName, b.AccountName, b.AccountNumber FROM trx_cashcollection c LEFT JOIN mst_bankaccount b ON c.BankAccountID = b.BankAccountID WHERE c.IsDeleted=0 ORDER BY c.CollectionDate DESC, c.CashCollectionID DESC");
?>
<?php if (isStatementClosed(today())): ?>
<div class="alert alert-danger shadow-sm border-danger text-center fw-bold fs-6 mb-3 py-2">
    <i class="fas fa-lock me-2"></i> আজকের তারিখের (<?php echo date('d-m-Y'); ?>) হিসাবটি ইতোমধ্যে ক্লোজ করা হয়েছে
</div>
<?php endif; ?>
<div class="table-container">
    <div class="table-header"><h5><i class="fas fa-money-bill-wave text-primary me-2"></i>Cash Collections</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New</button></div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>Date</th><th>Time</th><th>Amount</th><th>Collected By</th><th>Person Name</th><th>Deposit Target</th><th>Actions</th></tr></thead>
        <tbody><?php $sl=1; foreach($data as $row): 
            $personName = '';
            if ($row->CollectedByType == 'Employee') {
                $p = $objQuery->index("SELECT NameEN FROM mst_employee WHERE Id=?", [$row->CollectedPersonID]);
                if (!empty($p)) $personName = $p[0]->NameEN;
            } else {
                $p = $objQuery->index("SELECT NameEN FROM mst_shareholder WHERE Id=?", [$row->CollectedPersonID]);
                if (!empty($p)) $personName = $p[0]->NameEN;
            }
        ?><tr>
            <td><?php echo $sl++; ?></td><td><?php echo formatDate($row->CollectionDate); ?></td>
            <td><?php echo date('h:i A', strtotime($row->CollectionTime)); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->Amount, 2); ?></td>
            <td><?php echo $row->CollectedByType; ?></td>
            <td><?php echo htmlspecialchars($personName ?? 'N/A'); ?></td>
            <td>
                <?php if (!empty($row->BankAccountID) && !empty($row->BankName)): ?>
                    <span class="badge bg-info text-dark" title="<?php echo htmlspecialchars(($row->AccountName ?? '') . ' - ' . ($row->AccountNumber ?? '')); ?>">
                        <i class="fas fa-university me-1"></i><?php echo htmlspecialchars($row->BankName . ' (' . substr($row->AccountNumber, -4) . ')'); ?>
                    </span>
                <?php else: ?>
                    <span class="badge bg-secondary">
                        <i class="fas fa-wallet me-1"></i>Cash in Hand
                    </span>
                <?php endif; ?>
            </td>
            <td>
                <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->CashCollectionID; ?>" data-date="<?php echo $row->CollectionDate; ?>" data-time="<?php echo $row->CollectionTime; ?>" data-amount="<?php echo $row->Amount; ?>" data-type="<?php echo $row->CollectedByType; ?>" data-person="<?php echo $row->CollectedPersonID; ?>" data-bank_account_id="<?php echo $row->BankAccountID ?? ''; ?>" data-remarks="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->CashCollectionID; ?>"><i class="fas fa-trash"></i></button>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form id="dataForm" method="POST" action="../../modules/entry/cash_collection_entry.php">
        <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Cash Collection</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" name="collection_date" id="collection_date" class="form-control" required value="<?php echo today(); ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Time</label><input type="time" name="collection_time" id="collection_time" class="form-control" step="1" value="<?php echo now(); ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Amount <span class="text-danger">*</span></label><input type="number" step="0.01" name="amount" id="amount" class="form-control" required></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Collected By <span class="text-danger">*</span></label>
                    <select name="collected_by_type" id="collected_by_type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="Employee">Employee</option>
                        <option value="Shareholder">Shareholder</option>
                    </select></div>
                <div class="col-md-8 mb-3"><label class="form-label">Person <span class="text-danger">*</span></label>
                    <select name="collected_person_id" id="collected_person_id" class="form-select select2" required><option value="">Select Person</option>
                    <optgroup label="Employees"><?php foreach($employees as $e): ?><option value="emp_<?php echo $e->Id; ?>" data-type="Employee"><?php echo htmlspecialchars($e->NameEN.' ('.$e->EmployeeId.')'); ?></option><?php endforeach; ?></optgroup>
                    <optgroup label="Shareholders"><?php foreach($shareholders as $s): ?><option value="sh_<?php echo $s->Id; ?>" data-type="Shareholder"><?php echo htmlspecialchars($s->NameEN.' ('.$s->ShareHolderID.')'); ?></option><?php endforeach; ?></optgroup>
                    </select></div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label"><i class="fas fa-university text-primary me-1"></i>Deposit Target / Bank Account</label>
                    <select name="bank_account_id" id="bank_account_id" class="form-select">
                        <option value="">Cash in Hand (নগদ ক্যাশ)</option>
                        <?php foreach($bankAccounts as $b): ?>
                            <option value="<?php echo $b->BankAccountID; ?>"><?php echo htmlspecialchars($b->BankName . ' - ' . $b->AccountName . ' (' . $b->AccountNumber . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">টাকা সরাসরি ব্যাংকে জমা করা হলে নির্দিষ্ট ব্যাংক একাউন্ট সিলেক্ট করুন।</small>
                </div>
            </div>
            <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
    </form>
</div></div></div>
<script>
$(document).ready(function() {
    $('#collected_person_id').on('change', function() {
        const val = $(this).val();
        if (val) {
            const parts = val.split('_');
            const type = parts[0] === 'emp' ? 'Employee' : 'Shareholder';
            $('#collected_by_type').val(type);
        }
    });
    $('#addModal').on('show.bs.modal', function(e) { if(!$(e.relatedTarget).hasClass('edit-btn')){ $('#dataForm')[0].reset();$('#edit_id').val('');$('#collection_date').val('<?php echo today(); ?>');$('#collection_time').val('<?php echo now(); ?>');$('#bank_account_id').val('').trigger('change');$('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Cash Collection'); } });
    $(document).on('click', '.edit-btn', function() { const b=$(this);$('#edit_id').val(b.data('id'));$('#collection_date').val(b.data('date'));$('#collection_time').val(b.data('time'));$('#amount').val(b.data('amount'));$('#collected_by_type').val(b.data('type'));const pid = b.data('person'); const prefix = b.data('type')==='Employee'?'emp':'sh';$('#collected_person_id').val(prefix+'_'+pid).trigger('change');$('#bank_account_id').val(b.data('bank_account_id')||'').trigger('change');$('#remarks').val(b.data('remarks'));$('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Cash Collection');$('#addModal').modal('show'); });
    $('#dataForm').on('submit', function(e) { e.preventDefault();const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');$.ajax({url:f.attr('action'),type:'POST',data:f.serialize(),dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');$('#addModal').modal('hide');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},error:function(){showNotification('Error!','error');},complete:function(){btn.prop('disabled',false).html(orig);}});});
    $(document).on('click', '.delete-btn', function() { if(!confirmDelete()) return;const b=$(this),id=b.data('id');b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');$.ajax({url:'../../modules/entry/cash_collection_entry.php',type:'POST',data:{action:'delete',record_id:id},dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},complete:function(){b.prop('disabled',false).html('<i class="fas fa-trash"></i>');}});});
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>