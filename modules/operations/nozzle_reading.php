<?php
$pageTitle = 'Nozzle Meter Readings';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$shifts = $objQuery->index("SELECT ShiftID, ShiftName FROM mst_shift WHERE IsActive=1 AND IsDeleted=0");
$dispensers = $objQuery->index("SELECT DisID, DisName FROM mst_dispenser WHERE IsActive=1 AND IsDeleted=0");
$nozzles = $objQuery->index("SELECT n.NozzleID, n.NozzleName, n.DisID, d.DisName FROM mst_nozzle n LEFT JOIN mst_dispenser d ON n.DisID=d.DisID WHERE n.IsActive=1 AND n.IsDeleted=0 ORDER BY n.NozzleID");
$data = $objQuery->index("SELECT nr.*, s.ShiftName, d.DisName, n.NozzleName FROM trx_nozzlereading nr LEFT JOIN mst_shift s ON nr.ShiftID=s.ShiftID LEFT JOIN mst_dispenser d ON nr.DisID=d.DisID LEFT JOIN mst_nozzle n ON nr.NozzleID=n.NozzleID WHERE nr.IsDeleted=0 ORDER BY nr.ReadingDate DESC, nr.NozzleReadingID DESC");

$isMeterReadingReadOnlySetting = trim(getSetting('IsMeterReadingReadOnly') ?? '');
$isMeterReadingReadOnly = (strcasecmp($isMeterReadingReadOnlySetting, 'Yes') === 0 || strcasecmp($isMeterReadingReadOnlySetting, 'True') === 0);
?>
<?php if (isStatementClosed(today())): ?>
<div class="alert alert-danger shadow-sm border-danger text-center fw-bold fs-6 mb-3 py-2">
    <i class="fas fa-lock me-2"></i> আজকের তারিখের (<?php echo date('d-m-Y'); ?>) হিসাবটি ইতোমধ্যে ক্লোজ করা হয়েছে
</div>
<?php endif; ?>
<div class="table-container">
    <div class="table-header">
        <h5><i class="fas fa-tachometer-alt text-primary me-2"></i><?php echo t('Nozzle Meter Readings'); ?></h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New Reading</button>
    </div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>Date</th><th>Shift</th><th>Dispenser</th><th>Nozzle</th><th>General Reading</th><th>Master Reading</th><th>Sale Qty</th><th>Selling Rate</th><th>Sales Amt</th><th>Actions</th></tr></thead>
        <tbody><?php $sl=1; foreach($data as $row): ?><tr>
            <td><?php echo $sl++; ?></td>
            <td><?php echo formatDate($row->ReadingDate); ?></td>
            <td><?php echo htmlspecialchars($row->ShiftName ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row->DisName ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row->NozzleName ?? 'N/A'); ?></td>
            <td><?php echo number_format($row->GeneralReading, 3); ?></td>
            <td><?php echo number_format($row->MasterReading, 3); ?></td>
            <td><?php echo number_format($row->SaleQuantity, 3); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->SellingRate, 2); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->SalesAmt, 2); ?></td>
            <td>
                <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->NozzleReadingID; ?>" data-date="<?php echo $row->ReadingDate; ?>" data-shift="<?php echo $row->ShiftID; ?>" data-dis="<?php echo $row->DisID; ?>" data-nozzle="<?php echo $row->NozzleID; ?>" data-general="<?php echo $row->GeneralReading; ?>" data-master="<?php echo $row->MasterReading; ?>" data-prevgeneral="<?php echo $row->PreviousGeneral; ?>" data-prevmaster="<?php echo $row->PreviousMaster; ?>" data-selling="<?php echo $row->SellingRate; ?>" data-salesamt="<?php echo $row->SalesAmt; ?>" data-notes="<?php echo htmlspecialchars($row->Notes ?? ''); ?>"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->NozzleReadingID; ?>"><i class="fas fa-trash"></i></button>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table>
</div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form id="dataForm" method="POST" action="../../modules/entry/nozzle_reading_entry.php">
        <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Reading</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" name="reading_date" id="reading_date" class="form-control" required value="<?php echo today(); ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Shift <span class="text-danger">*</span></label>
                    <select name="shift_id" id="shift_id" class="form-select select2" required><option value="">Select Shift</option>
                    <?php foreach($shifts as $s): ?><option value="<?php echo $s->ShiftID; ?>"><?php echo htmlspecialchars($s->ShiftName); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4 mb-3"><label class="form-label">Dispenser <span class="text-danger">*</span></label>
                    <select name="dis_id" id="dis_id" class="form-select select2" required><option value="">Select Dispenser</option>
                    <?php foreach($dispensers as $d): ?><option value="<?php echo $d->DisID; ?>"><?php echo htmlspecialchars($d->DisName); ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Nozzle <span class="text-danger">*</span></label>
                    <select name="nozzle_id" id="nozzle_id" class="form-select select2" required><option value="">Select Nozzle</option>
                    <?php foreach($nozzles as $n): ?><option value="<?php echo $n->NozzleID; ?>" data-dis="<?php echo $n->DisID; ?>"><?php echo htmlspecialchars($n->NozzleName.' ('.$n->DisName.')'); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4 mb-3"><label class="form-label">Selling Rate (<?php echo $currencySymbol; ?>) <span class="text-danger">*</span></label><input type="number" step="0.01" name="selling_rate" id="selling_rate" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Sales Amount (<?php echo $currencySymbol; ?>)</label><input type="number" step="0.01" name="sales_amt" id="sales_amt" class="form-control" readonly></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">General Reading <span class="text-danger">*</span></label><input type="number" step="0.001" name="general_reading" id="general_reading" class="form-control" style="background-color: #e7f1ff; border: 2px solid #4169e1;" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Master Reading <span class="text-danger">*</span></label><input type="number" step="0.001" name="master_reading" id="master_reading" class="form-control" style="background-color: #e9ecef; border: 2px solid #495057;" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Diff. General</label><input type="number" step="0.001" name="diff_general" id="diff_general" class="form-control" style="background-color: #d0e1fd;" readonly></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Previous General</label><input type="number" step="0.001" name="prev_general" id="prev_general" class="form-control" style="background-color: #f0f7ff;" <?php echo $isMeterReadingReadOnly ? 'readonly' : ''; ?>></div>
                <div class="col-md-4 mb-3"><label class="form-label">Previous Master</label><input type="number" step="0.001" name="prev_master" id="prev_master" class="form-control" style="background-color: #f8f9fa;" <?php echo $isMeterReadingReadOnly ? 'readonly' : ''; ?>></div>
                <div class="col-md-4 mb-3"><label class="form-label">Diff. Master</label><input type="number" step="0.001" name="diff_master" id="diff_master" class="form-control" style="background-color: #dee2e6;" readonly></div>
            </div>
            <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" id="notes" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
    </form>
</div></div></div>

<script>
$(document).ready(function() {
    // Calculate differences and sales amount from readings and selling rate
    function calculateDifferences() {
        var sellingRate = parseFloat($('#selling_rate').val()) || 0;
        var prevGeneral = parseFloat($('#prev_general').val()) || 0;
        var generalReading = parseFloat($('#general_reading').val()) || 0;
        var prevMaster = parseFloat($('#prev_master').val()) || 0;
        var masterReading = parseFloat($('#master_reading').val()) || 0;

        var diffGen = Math.max(0, generalReading - prevGeneral);
        var diffMas = Math.max(0, masterReading - prevMaster);

        $('#diff_general').val(diffGen.toFixed(3));
        $('#diff_master').val(diffMas.toFixed(3));

        var salesAmt = (diffGen * sellingRate).toFixed(2);
        $('#sales_amt').val(salesAmt);
    }

    // Recalculate differences and sales amount on relevant field changes
    $('#selling_rate, #general_reading, #prev_general, #master_reading, #prev_master').on('input change', calculateDifferences);

    // Filter nozzles by selected dispenser
    const allNozzleOptions = $('#nozzle_id').html();
    $('#dis_id').on('change', function() {
        const disId = $(this).val();
        const $nozzle = $('#nozzle_id');
        // Store currently selected nozzle before rebuild
        const prevVal = $nozzle.val();
        // Destroy select2 instance
        if ($nozzle.data('select2')) {
            $nozzle.select2('destroy');
        }
        // Rebuild options: keep placeholder, add only nozzles matching the dispenser
        $nozzle.empty().append('<option value="">Select Nozzle</option>');
        if (disId) {
            $(allNozzleOptions).filter('option').each(function() {
                if ($(this).val() !== '' && $(this).data('dis') == disId) {
                    $nozzle.append($(this).clone());
                }
            });
        }
        // Restore previous selection if still valid
        if (prevVal && $nozzle.find('option[value="' + prevVal + '"]').length > 0) {
            $nozzle.val(prevVal);
        }
        // Reinitialize select2
        $nozzle.select2({ theme: 'bootstrap-5', dropdownParent: $('#addModal') });
    });

    // Load previous readings and selling rate when nozzle or reading_date is changed
    function loadPreviousReadings() {
        const nozzleId = $('#nozzle_id').val();
        const readingDate = $('#reading_date').val();
        // Auto-fetch only when adding new reading (edit_id is empty)
        if (nozzleId && $('#edit_id').val() === '') {
            $.ajax({
                url: '../../modules/entry/nozzle_reading_entry.php',
                type: 'POST',
                data: { action: 'get_previous', nozzle_id: nozzleId, reading_date: readingDate },
                dataType: 'json',
                success: function(r) {
                    if (r.success) {
                        $('#prev_general').val(r.prev_general);
                        $('#prev_master').val(r.prev_master);
                        $('#selling_rate').val(r.selling_rate);
                        calculateDifferences();
                    }
                }
            });
        }
    }

    $('#nozzle_id, #reading_date').on('change', loadPreviousReadings);

    $('#addModal').on('show.bs.modal', function(e) {
        if (!$(e.relatedTarget).hasClass('edit-btn')) {
            $('#dataForm')[0].reset();
            $('#edit_id').val('');
            $('#reading_date').val('<?php echo today(); ?>');
            $('#prev_general').val('');
            $('#prev_master').val('');
            $('#diff_general').val('');
            $('#diff_master').val('');
            $('#selling_rate').val('');
            $('#sales_amt').val('');
            $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Reading');
        }
    });
    $(document).on('click', '.edit-btn', function() {
        const b=$(this);
        $('#edit_id').val(b.data('id'));
        $('#reading_date').val(b.data('date'));
        $('#shift_id').val(b.data('shift')).trigger('change');
        $('#dis_id').val(b.data('dis')).trigger('change');
        $('#nozzle_id').val(b.data('nozzle')).trigger('change');
        $('#general_reading').val(b.data('general'));
        $('#master_reading').val(b.data('master'));
        $('#prev_general').val(b.data('prevgeneral'));
        $('#prev_master').val(b.data('prevmaster'));
        $('#selling_rate').val(b.data('selling'));
        $('#sales_amt').val(b.data('salesamt'));
        calculateDifferences();
        $('#notes').val(b.data('notes'));
        $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Reading');
        $('#addModal').modal('show');
    });
    $('#dataForm').on('submit', function(e) {
        e.preventDefault();const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        $.ajax({url:f.attr('action'),type:'POST',data:f.serialize(),dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');$('#addModal').modal('hide');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},error:function(){showNotification('Error!','error');},complete:function(){btn.prop('disabled',false).html(orig);}});
    });
    $(document).on('click', '.delete-btn', function() {
        if(!confirmDelete()) return;const b=$(this),id=b.data('id');b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({url:'../../modules/entry/nozzle_reading_entry.php',type:'POST',data:{action:'delete',record_id:id},dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},complete:function(){b.prop('disabled',false).html('<i class="fas fa-trash"></i>');}});
    });
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
