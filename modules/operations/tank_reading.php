<?php
$pageTitle = 'Tank Readings';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
$tanks = $objQuery->index("SELECT t.TankID, t.TankName, t.OpeningStockPercent, ft.FuelName FROM mst_tank t LEFT JOIN mst_fueltype ft ON t.FuelTypeID=ft.FuelTypeID WHERE t.IsActive=1 AND t.IsDeleted=0");
$data = $objQuery->index("SELECT tr.*, t.TankName, ft.FuelName FROM trx_tankreading tr LEFT JOIN mst_tank t ON tr.TankID=t.TankID LEFT JOIN mst_fueltype ft ON t.FuelTypeID=ft.FuelTypeID WHERE tr.IsDeleted=0 ORDER BY tr.ReadingDate DESC, tr.TankReadingID DESC");
?>
<?php if (isStatementClosed(today())): ?>
<div class="alert alert-danger shadow-sm border-danger text-center fw-bold fs-6 mb-3 py-2">
    <i class="fas fa-lock me-2"></i> আজকের তারিখের (<?php echo date('d-m-Y'); ?>) হিসাবটি ইতোমধ্যে ক্লোজ করা হয়েছে
</div>
<?php endif; ?>
<div class="table-container">
    <div class="table-header"><h5><i class="fas fa-chart-line text-primary me-2"></i>Tank Readings</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New Reading</button></div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>Date</th><th>Tank</th><th>Fuel</th><th>Previous %</th><th>Current %</th><th>Sold %</th><th>Actions</th></tr></thead>
        <tbody><?php $sl=1; foreach($data as $row): ?><tr>
            <td><?php echo $sl++; ?></td><td><?php echo formatDate($row->ReadingDate); ?></td>
            <td><?php echo htmlspecialchars($row->TankName ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row->FuelName ?? 'N/A'); ?></td>
            <td><?php echo number_format($row->PreviousReadingPercent, 2); ?>%</td>
            <td><?php echo number_format($row->CurrentReadingPercent, 2); ?>%</td>
            <td><?php echo number_format($row->TodaySoldPercent, 2); ?>%</td>
            <td>
                <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->TankReadingID; ?>" data-date="<?php echo $row->ReadingDate; ?>" data-tank="<?php echo $row->TankID; ?>" data-prev="<?php echo $row->PreviousReadingPercent; ?>" data-current="<?php echo $row->CurrentReadingPercent; ?>" data-sold="<?php echo $row->TodaySoldPercent; ?>"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->TankReadingID; ?>"><i class="fas fa-trash"></i></button>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form id="dataForm" method="POST" action="../../modules/entry/tank_reading_entry.php">
        <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Tank Reading</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" name="reading_date" id="reading_date" class="form-control" required value="<?php echo today(); ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Tank <span class="text-danger">*</span></label>
                    <select name="tank_id" id="tank_id" class="form-select select2" required><option value="">Select Tank</option>
                    <?php foreach($tanks as $t): ?><option value="<?php echo $t->TankID; ?>" data-opening="<?php echo $t->OpeningStockPercent; ?>"><?php echo htmlspecialchars($t->TankName.' ('.$t->FuelName.')'); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4 mb-3"><label class="form-label">Previous Reading (%)</label><input type="number" step="0.01" name="prev_reading" id="prev_reading" class="form-control" readonly></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Current Reading (%) <span class="text-danger">*</span></label><input type="number" step="0.01" name="current_reading" id="current_reading" class="form-control" required min="0" max="100"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Today Sold (%)</label><input type="number" step="0.01" name="sold_percent" id="sold_percent" class="form-control" readonly></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
    </form>
</div></div></div>
<script>
$(document).ready(function() {
    function loadPreviousReading() {
        const tankId = $('#tank_id').val();
        const readingDate = $('#reading_date').val();
        if (tankId) {
            const selected = $('#tank_id').find(':selected');
            const opening = selected.data('opening') !== undefined ? selected.data('opening') : '';
            $('#prev_reading').val(opening);
            $.ajax({
                url: '../../modules/entry/tank_reading_entry.php',
                type: 'POST',
                data: { action: 'get_previous', tank_id: tankId, reading_date: readingDate },
                dataType: 'json',
                success: function(r) {
                    if (r.success) {
                        $('#prev_reading').val(r.prev_percent);
                        const prev = parseFloat(r.prev_percent) || 0;
                        const curr = parseFloat($('#current_reading').val()) || 0;
                        if ($('#current_reading').val() !== '') {
                            $('#sold_percent').val(Math.max(0, prev - curr).toFixed(2));
                        }
                    }
                }
            });
        }
    }

    // Load previous reading when tank or reading date is changed
    $('#tank_id, #reading_date').on('change', function() {
        if (!$('#edit_id').val()) {
            loadPreviousReading();
        }
    });

    // Calculate sold percent
    $('#current_reading').on('input', function() {
        const prev = parseFloat($('#prev_reading').val()) || 0;
        const curr = parseFloat($(this).val()) || 0;
        $('#sold_percent').val(Math.max(0, prev - curr).toFixed(2));
    });
    $('#addModal').on('show.bs.modal', function(e) { if(!$(e.relatedTarget).hasClass('edit-btn')){ $('#dataForm')[0].reset();$('#edit_id').val('');$('#reading_date').val('<?php echo today(); ?>');$('#prev_reading').val('');$('#sold_percent').val('');$('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Tank Reading'); } });
    $(document).on('click', '.edit-btn', function() { const b=$(this);$('#edit_id').val(b.data('id'));$('#reading_date').val(b.data('date'));$('#tank_id').val(b.data('tank')).trigger('change');$('#prev_reading').val(b.data('prev'));$('#current_reading').val(b.data('current'));$('#sold_percent').val(b.data('sold'));$('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Tank Reading');$('#addModal').modal('show'); });
    $('#dataForm').on('submit', function(e) { e.preventDefault();const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');$.ajax({url:f.attr('action'),type:'POST',data:f.serialize(),dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');$('#addModal').modal('hide');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},error:function(){showNotification('Error!','error');},complete:function(){btn.prop('disabled',false).html(orig);}});});
    $(document).on('click', '.delete-btn', function() { if(!confirmDelete()) return;const b=$(this),id=b.data('id');b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');$.ajax({url:'../../modules/entry/tank_reading_entry.php',type:'POST',data:{action:'delete',record_id:id},dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},complete:function(){b.prop('disabled',false).html('<i class="fas fa-trash"></i>');}});});
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>