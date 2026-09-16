<?php
$pageTitle = 'Bank Account Management';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Auto-ensure mst_bankaccount table exists
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
} catch (Exception $e) {}

$data = $objQuery->index("SELECT * FROM mst_bankaccount WHERE IsDeleted=0 ORDER BY BankName ASC, AccountName ASC");
?>
<div class="table-container">
    <div class="table-header">
        <h5><i class="fas fa-university text-primary me-2"></i><?php echo t('Bank Accounts'); ?></h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New</button>
    </div>
    <table class="table table-hover datatable">
        <thead>
            <tr>
                <th>SL</th>
                <th>Bank Name</th>
                <th>Branch Name</th>
                <th>Account Name</th>
                <th>Account Number</th>
                <th>Account Type</th>
                <th>Opening Balance</th>
                <th>Current Balance</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $sl=1; foreach($data as $row): ?>
            <tr>
                <td><?php echo $sl++; ?></td>
                <td><strong><?php echo htmlspecialchars($row->BankName); ?></strong></td>
                <td><?php echo htmlspecialchars($row->BranchName ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row->AccountName); ?></td>
                <td><code><?php echo htmlspecialchars($row->AccountNumber); ?></code></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row->AccountType ?? 'Current'); ?></span></td>
                <td><?php echo $currencySymbol.' '.number_format($row->OpeningBalance, 2); ?></td>
                <td><strong class="text-success"><?php echo $currencySymbol.' '.number_format($row->CurrentBalance, 2); ?></strong></td>
                <td><span class="badge bg-<?php echo $row->IsActive ? 'success' : 'danger'; ?>"><?php echo $row->IsActive ? 'Active' : 'Inactive'; ?></span></td>
                <td>
                    <button class="btn btn-sm btn-info edit-btn" 
                        data-id="<?php echo $row->BankAccountID; ?>" 
                        data-bank_name="<?php echo htmlspecialchars($row->BankName); ?>" 
                        data-branch_name="<?php echo htmlspecialchars($row->BranchName ?? ''); ?>" 
                        data-account_name="<?php echo htmlspecialchars($row->AccountName); ?>" 
                        data-account_number="<?php echo htmlspecialchars($row->AccountNumber); ?>" 
                        data-account_type="<?php echo htmlspecialchars($row->AccountType ?? 'Current'); ?>" 
                        data-routing_number="<?php echo htmlspecialchars($row->RoutingNumber ?? ''); ?>" 
                        data-opening_balance="<?php echo $row->OpeningBalance; ?>" 
                        data-opening_date="<?php echo $row->OpeningDate ?? ''; ?>" 
                        data-status="<?php echo $row->IsActive; ?>">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->BankAccountID; ?>"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="dataForm" method="POST" action="../../modules/entry/bank_account_entry.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="record_id" id="edit_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Bank Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" name="bank_name" id="bank_name" class="form-control" required placeholder="e.g. Islami Bank Bangladesh Ltd">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Branch Name</label>
                            <input type="text" name="branch_name" id="branch_name" class="form-control" placeholder="e.g. Agrabad Branch">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" name="account_name" id="account_name" class="form-control" required placeholder="e.g. Shangu LPG Station Sales">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Account Number <span class="text-danger">*</span></label>
                            <input type="text" name="account_number" id="account_number" class="form-control" required placeholder="e.g. 205014502001234">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Account Type</label>
                            <select name="account_type" id="account_type" class="form-select">
                                <option value="Current">Current (চলতি)</option>
                                <option value="Savings">Savings (সঞ্চয়ী)</option>
                                <option value="OD/CC">OD / CC (ঋণ হিসাব)</option>
                                <option value="STD">STD / Short Term Deposit</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Routing / SWIFT Number</label>
                            <input type="text" name="routing_number" id="routing_number" class="form-control" placeholder="Routing No">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Opening Date</label>
                            <input type="date" name="opening_date" id="opening_date" class="form-control" value="<?php echo today(); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Opening Balance</label>
                            <input type="number" step="0.01" name="opening_balance" id="opening_balance" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="is_active" id="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
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
            $('#opening_date').val('<?php echo today(); ?>');
            $('#opening_balance').val('0.00');
            $('#account_type').val('Current');
            $('#is_active').val('1');
            $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Bank Account');
        }
    });

    $(document).on('click', '.edit-btn', function() {
        const b = $(this);
        $('#edit_id').val(b.data('id'));
        $('#bank_name').val(b.data('bank_name'));
        $('#branch_name').val(b.data('branch_name'));
        $('#account_name').val(b.data('account_name'));
        $('#account_number').val(b.data('account_number'));
        $('#account_type').val(b.data('account_type'));
        $('#routing_number').val(b.data('routing_number'));
        $('#opening_balance').val(b.data('opening_balance'));
        $('#opening_date').val(b.data('opening_date'));
        $('#is_active').val(b.data('status'));
        $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Bank Account');
        $('#addModal').modal('show');
    });

    $('#dataForm').on('submit', function(e) {
        e.preventDefault();
        const f = $(this), btn = f.find('[type="submit"]'), orig = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        $.ajax({
            url: f.attr('action'),
            type: 'POST',
            data: f.serialize(),
            dataType: 'json',
            success: function(r) {
                if (r.success) {
                    showNotification(r.message, 'success');
                    $('#addModal').modal('hide');
                    setTimeout(() => location.reload(), 500);
                } else showNotification(r.message, 'error');
            },
            error: function() {
                showNotification('Error processing request!', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html(orig);
            }
        });
    });

    $(document).on('click', '.delete-btn', function() {
        if (!confirmDelete()) return;
        const b = $(this), id = b.data('id');
        b.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url: '../../modules/entry/bank_account_entry.php',
            type: 'POST',
            data: { action: 'delete', record_id: id },
            dataType: 'json',
            success: function(r) {
                if (r.success) {
                    showNotification(r.message, 'success');
                    setTimeout(() => location.reload(), 500);
                } else showNotification(r.message, 'error');
            },
            complete: function() {
                b.prop('disabled', false).html('<i class="fas fa-trash"></i>');
            }
        });
    });
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
