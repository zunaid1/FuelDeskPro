<?php
/**
 * FuelDeskPro - Cash Collections Module
 * 
 * Enhanced with large typography, user-friendly KPI cards, bold data tables,
 * and high-contrast form inputs.
 *
 * @package FuelDeskPro
 */

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

// Calculate Today's Summary Metrics
$todayDate = today();
$todayTotal = 0;
$todayBankTotal = 0;
$todayCashHandTotal = 0;

foreach ($data as $r) {
    if ($r->CollectionDate === $todayDate) {
        $amt = floatval($r->Amount);
        $todayTotal += $amt;
        if (!empty($r->BankAccountID)) {
            $todayBankTotal += $amt;
        } else {
            $todayCashHandTotal += $amt;
        }
    }
}
?>

<style>
/* Page & Table Typography */
.table-container h5 {
    font-size: 1.35rem !important;
    font-weight: 700 !important;
}
.datatable th {
    font-size: 1.05rem !important;
    font-weight: 700 !important;
    background-color: #f8f9fa !important;
    color: #1e293b !important;
    padding: 12px 14px !important;
}
.datatable td {
    font-size: 1.05rem !important;
    font-weight: 500 !important;
    vertical-align: middle !important;
    padding: 12px 14px !important;
}
.amount-text {
    color: #15803d !important;
    font-weight: 700 !important;
    font-size: 1.15rem !important;
    font-family: monospace, sans-serif;
}
.badge-bank {
    font-size: 0.95rem !important;
    padding: 6px 12px !important;
    background-color: #e0f2fe !important;
    color: #0369a1 !important;
    border: 1px solid #7dd3fc !important;
    border-radius: 6px !important;
}
.badge-cash {
    font-size: 0.95rem !important;
    padding: 6px 12px !important;
    background-color: #fef3c7 !important;
    color: #92400e !important;
    border: 1px solid #fde68a !important;
    border-radius: 6px !important;
}

/* Modal Typography & Styling */
#addModal .modal-title {
    font-size: 1.3rem !important;
    font-weight: 700 !important;
}
#addModal .form-label {
    font-size: 1.05rem !important;
    font-weight: 600 !important;
    color: #334155 !important;
    margin-bottom: 0.4rem !important;
}
#addModal .form-control, 
#addModal .form-select {
    font-size: 1.15rem !important;
    font-weight: 600 !important;
    padding: 0.55rem 0.85rem !important;
    border-radius: 6px !important;
}
#addModal .form-control:focus, 
#addModal .form-select:focus {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2) !important;
}
.kpi-card {
    background: #ffffff;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: 18px 22px;
    transition: all 0.2s ease-in-out;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.08);
}
</style>

<?php if (isStatementClosed(today())): ?>
<div class="alert alert-danger shadow-sm border-danger text-center fw-bold fs-6 mb-3 py-2">
    <i class="fas fa-lock me-2"></i> আজকের তারিখের (<?php echo date('d-m-Y'); ?>) হিসাবটি ইতোমধ্যে ক্লোজ করা হয়েছে
</div>
<?php endif; ?>

<!-- Summary KPI Cards -->
<div class="row mb-4 g-3">
    <div class="col-md-4 col-sm-6">
        <div class="kpi-card border-start border-4 border-success">
            <div class="text-muted small fw-bold text-uppercase"><i class="fas fa-money-bill-wave text-success me-1"></i> 今日 / Today Total Collection</div>
            <div class="h3 mb-0 fw-bold text-success mt-1"><?php echo $currencySymbol . ' ' . number_format($todayTotal, 2); ?></div>
            <div class="small text-muted mt-1"><i class="fas fa-calendar-day me-1"></i><?php echo date('d-m-Y'); ?> (আজকের মোট কালেকশন)</div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="kpi-card border-start border-4 border-info">
            <div class="text-muted small fw-bold text-uppercase"><i class="fas fa-university text-info me-1"></i> Today Bank Deposits</div>
            <div class="h3 mb-0 fw-bold text-info mt-1"><?php echo $currencySymbol . ' ' . number_format($todayBankTotal, 2); ?></div>
            <div class="small text-muted mt-1"><i class="fas fa-building me-1"></i>ব্যাংকে জমাকৃত কালেকশন</div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="kpi-card border-start border-4 border-warning">
            <div class="text-muted small fw-bold text-uppercase"><i class="fas fa-wallet text-warning me-1"></i> Today Cash in Hand</div>
            <div class="h3 mb-0 fw-bold text-warning mt-1"><?php echo $currencySymbol . ' ' . number_format($todayCashHandTotal, 2); ?></div>
            <div class="small text-muted mt-1"><i class="fas fa-hand-holding-usd me-1"></i>নগদ ক্যাশ (স্টেশন ক্যাশ)</div>
        </div>
    </div>
</div>

<!-- Table Container -->
<div class="table-container shadow-sm border-0">
    <div class="table-header py-3">
        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-money-bill-wave text-primary me-2"></i>Cash Collections (ক্যাশ আদায় বিবরণ)</h5>
        <button class="btn btn-primary btn-md px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus-circle me-1"></i> Add New Collection
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle datatable mb-0">
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px;">SL</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Amount (<?php echo $currencySymbol; ?>)</th>
                    <th>Type</th>
                    <th>Person Name</th>
                    <th>Deposit Target</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $sl=1; foreach($data as $row): 
                    $personName = '';
                    if ($row->CollectedByType == 'Employee') {
                        $p = $objQuery->index("SELECT NameEN FROM mst_employee WHERE Id=?", [$row->CollectedPersonID]);
                        if (!empty($p)) $personName = $p[0]->NameEN;
                    } else {
                        $p = $objQuery->index("SELECT NameEN FROM mst_shareholder WHERE Id=?", [$row->CollectedPersonID]);
                        if (!empty($p)) $personName = $p[0]->NameEN;
                    }
                ?>
                <tr>
                    <td class="text-center text-muted"><?php echo $sl++; ?></td>
                    <td class="fw-bold"><?php echo formatDate($row->CollectionDate); ?></td>
                    <td class="text-secondary"><?php echo date('h:i A', strtotime($row->CollectionTime)); ?></td>
                    <td class="amount-text"><?php echo $currencySymbol.' '.number_format($row->Amount, 2); ?></td>
                    <td>
                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row->CollectedByType); ?></span>
                    </td>
                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($personName ?? 'N/A'); ?></td>
                    <td>
                        <?php if (!empty($row->BankAccountID) && !empty($row->BankName)): ?>
                            <span class="badge-bank" title="<?php echo htmlspecialchars(($row->AccountName ?? '') . ' - ' . ($row->AccountNumber ?? '')); ?>">
                                <i class="fas fa-university me-1"></i><?php echo htmlspecialchars($row->BankName . ' (' . substr($row->AccountNumber, -4) . ')'); ?>
                            </span>
                        <?php else: ?>
                            <span class="badge-cash">
                                <i class="fas fa-wallet me-1"></i>Cash in Hand
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-info edit-btn me-1" 
                            data-id="<?php echo $row->CashCollectionID; ?>" 
                            data-date="<?php echo $row->CollectionDate; ?>" 
                            data-time="<?php echo $row->CollectionTime; ?>" 
                            data-amount="<?php echo $row->Amount; ?>" 
                            data-type="<?php echo $row->CollectedByType; ?>" 
                            data-person="<?php echo $row->CollectedPersonID; ?>" 
                            data-bank_account_id="<?php echo $row->BankAccountID ?? ''; ?>" 
                            data-remarks="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->CashCollectionID; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="dataForm" method="POST" action="../../modules/entry/cash_collection_entry.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="record_id" id="edit_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Cash Collection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Date (তারিখ) <span class="text-danger">*</span></label>
                            <input type="date" name="collection_date" id="collection_date" class="form-control" required value="<?php echo today(); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Time (সময়)</label>
                            <input type="time" name="collection_time" id="collection_time" class="form-control" step="1" value="<?php echo now(); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Amount (পরিমাণ - <?php echo $currencySymbol; ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" id="amount" class="form-control" style="background-color: #f0fdf4; border: 2px solid #22c55e;" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Collected By Type <span class="text-danger">*</span></label>
                            <select name="collected_by_type" id="collected_by_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="Employee">Employee (কর্মচারী)</option>
                                <option value="Shareholder">Shareholder (শেয়ারহোল্ডার)</option>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Select Person (ব্যক্তি সিলেক্ট করুন) <span class="text-danger">*</span></label>
                            <select name="collected_person_id" id="collected_person_id" class="form-select select2" required>
                                <option value="">-- Select Person --</option>
                                <optgroup label="Employees (কর্মচারী)">
                                    <?php foreach($employees as $e): ?>
                                        <option value="emp_<?php echo $e->Id; ?>" data-type="Employee">
                                            <?php echo htmlspecialchars($e->NameEN.' ('.$e->EmployeeId.')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Shareholders (শেয়ারহোল্ডার)">
                                    <?php foreach($shareholders as $s): ?>
                                        <option value="sh_<?php echo $s->Id; ?>" data-type="Shareholder">
                                            <?php echo htmlspecialchars($s->NameEN.' ('.$s->ShareHolderID.')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><i class="fas fa-university text-primary me-1"></i>Deposit Target / Bank Account (জমার স্থান)</label>
                            <select name="bank_account_id" id="bank_account_id" class="form-select" style="background-color: #eff6ff;">
                                <option value="">Cash in Hand (নগদ ক্যাশ)</option>
                                <?php foreach($bankAccounts as $b): ?>
                                    <option value="<?php echo $b->BankAccountID; ?>">
                                        <?php echo htmlspecialchars($b->BankName . ' - ' . $b->AccountName . ' (' . $b->AccountNumber . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle me-1"></i>টাকা সরাসরি ব্যাংকে জমা করা হলে নির্দিষ্ট ব্যাংক একাউন্ট সিলেক্ট করুন, অন্যথায় নগদ ক্যাশ হিসেবে থাকবে।</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks (মন্তব্য / বিবরণ)</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="2" placeholder="প্রয়োজনীয় বিবরণ লিখুন..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold fs-6"><i class="fas fa-save me-1"></i> Save Collection</button>
                </div>
            </form>
        </div>
    </div>
</div>

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

    $('#addModal').on('show.bs.modal', function(e) { 
        if(!$(e.relatedTarget).hasClass('edit-btn')){ 
            $('#dataForm')[0].reset();
            $('#edit_id').val('');
            $('#collection_date').val('<?php echo today(); ?>');
            $('#collection_time').val('<?php echo now(); ?>');
            $('#bank_account_id').val('').trigger('change');
            $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Cash Collection'); 
        } 
    });

    $(document).on('click', '.edit-btn', function() { 
        const b=$(this);
        $('#edit_id').val(b.data('id'));
        $('#collection_date').val(b.data('date'));
        $('#collection_time').val(b.data('time'));
        $('#amount').val(b.data('amount'));
        $('#collected_by_type').val(b.data('type'));
        const pid = b.data('person'); 
        const prefix = b.data('type')==='Employee'?'emp':'sh';
        $('#collected_person_id').val(prefix+'_'+pid).trigger('change');
        $('#bank_account_id').val(b.data('bank_account_id')||'').trigger('change');
        $('#remarks').val(b.data('remarks'));
        $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Cash Collection');
        $('#addModal').modal('show'); 
    });

    $('#dataForm').on('submit', function(e) { 
        e.preventDefault();
        const f=$(this), btn=f.find('[type="submit"]'), orig=btn.html();
        btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        $.ajax({
            url: f.attr('action'),
            type: 'POST',
            data: f.serialize(),
            dataType: 'json',
            success: function(r){
                if(r.success){
                    showNotification(r.message,'success');
                    $('#addModal').modal('hide');
                    setTimeout(()=>location.reload(),500);
                } else {
                    showNotification(r.message,'error');
                }
            },
            error: function(){ showNotification('Error processing request!','error'); },
            complete: function(){ btn.prop('disabled',false).html(orig); }
        });
    });

    $(document).on('click', '.delete-btn', function() { 
        if(!confirmDelete()) return;
        const b=$(this), id=b.data('id');
        b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url: '../../modules/entry/cash_collection_entry.php',
            type: 'POST',
            data: {action: 'delete', record_id: id},
            dataType: 'json',
            success: function(r){
                if(r.success){
                    showNotification(r.message,'success');
                    setTimeout(()=>location.reload(),500);
                } else {
                    showNotification(r.message,'error');
                }
            },
            complete: function(){ b.prop('disabled',false).html('<i class="fas fa-trash"></i>'); }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>