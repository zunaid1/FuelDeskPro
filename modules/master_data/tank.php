<?php
$pageTitle = 'Tanks Management';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$sql = "SELECT t.*, ft.FuelName, tg.TankGroupName FROM mst_tank t LEFT JOIN mst_fueltype ft ON t.FuelTypeID=ft.FuelTypeID LEFT JOIN mst_tankgroup tg ON t.TankGroupID=tg.TankGroupID WHERE t.IsDeleted=0 ORDER BY t.TankID ASC";
$data = $objQuery->index($sql);
$fuels = $objQuery->index("SELECT FuelTypeID, FuelName FROM mst_fueltype WHERE IsActive=1 AND IsDeleted=0");
$groups = $objQuery->index("SELECT TankGroupID, TankGroupName FROM mst_tankgroup WHERE IsActive=1 AND IsDeleted=0");
?>

<div class="table-container">
    <div class="table-header">
        <h5><i class="fas fa-tint text-primary me-2"></i><?php echo t('Tanks Management'); ?></h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New Tank</button>
    </div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>Tank Name</th><th>Code</th><th>Group</th><th>Fuel Type</th><th>Capacity</th><th>Min Level</th><th>Opening %</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            <?php $sl=1; foreach($data as $row): ?>
            <tr>
                <td><?php echo $sl++; ?></td>
                <td><?php echo htmlspecialchars($row->TankName); ?></td>
                <td><?php echo htmlspecialchars($row->TankCode ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row->TankGroupName ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row->FuelName ?? 'N/A'); ?></td>
                <td><?php echo number_format($row->Capacity, 3); ?></td>
                <td><?php echo number_format($row->MinLevel, 3); ?></td>
                <td><?php echo number_format($row->OpeningStockPercent, 2); ?>%</td>
                <td><span class="badge bg-<?php echo $row->IsActive?'success':'danger'; ?>"><?php echo $row->IsActive?'Active':'Inactive'; ?></span></td>
                <td>
                    <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->TankID; ?>" data-name="<?php echo htmlspecialchars($row->TankName); ?>" data-code="<?php echo htmlspecialchars($row->TankCode ?? ''); ?>" data-group="<?php echo $row->TankGroupID; ?>" data-fuel="<?php echo $row->FuelTypeID; ?>" data-capacity="<?php echo $row->Capacity; ?>" data-min="<?php echo $row->MinLevel; ?>" data-opening="<?php echo $row->OpeningStockPercent; ?>" data-priority="<?php echo $row->Priority; ?>" data-status="<?php echo $row->IsActive; ?>"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->TankID; ?>"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form id="dataForm" method="POST" action="../../modules/entry/tank_entry.php">
            <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
            <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Tank</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Tank Name <span class="text-danger">*</span></label><input type="text" name="tank_name" id="tank_name" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Tank Code</label><input type="text" name="tank_code" id="tank_code" class="form-control"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Tank Group</label>
                        <select name="tank_group_id" id="tank_group_id" class="form-select select2"><option value="">Select Group</option>
                        <?php foreach($groups as $g): ?><option value="<?php echo $g->TankGroupID; ?>"><?php echo htmlspecialchars($g->TankGroupName); ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Fuel Type <span class="text-danger">*</span></label>
                        <select name="fuel_type_id" id="fuel_type_id" class="form-select select2" required><option value="">Select Fuel</option>
                        <?php foreach($fuels as $f): ?><option value="<?php echo $f->FuelTypeID; ?>"><?php echo htmlspecialchars($f->FuelName); ?></option><?php endforeach; ?></select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Capacity <span class="text-danger">*</span></label><input type="number" step="0.001" name="capacity" id="capacity" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Min Level</label><input type="number" step="0.001" name="min_level" id="min_level" class="form-control" value="0"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Opening Stock (%)</label><input type="number" step="0.01" name="opening_stock" id="opening_stock" class="form-control" value="0"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Priority</label><input type="number" name="priority" id="priority" class="form-control" value="100"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Status</label>
                        <select name="is_active" id="is_active" class="form-select"><option value="1">Active</option><option value="0">Inactive</option></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
        </form>
    </div></div>
</div>

<script>
$(document).ready(function() {
    $('#addModal').on('show.bs.modal', function(e) { if(!$(e.relatedTarget).hasClass('edit-btn')){ $('#dataForm')[0].reset();$('#edit_id').val('');$('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Tank'); } });
    $(document).on('click', '.edit-btn', function() { const b=$(this);$('#edit_id').val(b.data('id'));$('#tank_name').val(b.data('name'));$('#tank_code').val(b.data('code'));$('#tank_group_id').val(b.data('group')).trigger('change');$('#fuel_type_id').val(b.data('fuel')).trigger('change');$('#capacity').val(b.data('capacity'));$('#min_level').val(b.data('min'));$('#opening_stock').val(b.data('opening'));$('#priority').val(b.data('priority'));$('#is_active').val(b.data('status'));$('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Tank');$('#addModal').modal('show'); });
    $('#dataForm').on('submit', function(e) { e.preventDefault();const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');$.ajax({url:f.attr('action'),type:'POST',data:f.serialize(),dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');$('#addModal').modal('hide');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},error:function(){showNotification('Error!','error');},complete:function(){btn.prop('disabled',false).html(orig);}});});
    $(document).on('click', '.delete-btn', function() { if(!confirmDelete()) return;const b=$(this),id=b.data('id');b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');$.ajax({url:'../../modules/entry/tank_entry.php',type:'POST',data:{action:'delete',record_id:id},dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},complete:function(){b.prop('disabled',false).html('<i class="fas fa-trash"></i>');}});});
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>