<?php
$pageTitle = 'Nozzle Management';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
$data = $objQuery->index("SELECT n.*, d.DisName, ft.FuelName, tg.TankGroupName FROM mst_nozzle n LEFT JOIN mst_dispenser d ON n.DisID=d.DisID LEFT JOIN mst_fueltype ft ON n.FuelTypeID=ft.FuelTypeID LEFT JOIN mst_tankgroup tg ON n.TankGroupID=tg.TankGroupID WHERE n.IsDeleted=0 ORDER BY n.NozzleID ASC");
$dispensers = $objQuery->index("SELECT DisID, DisName FROM mst_dispenser WHERE IsActive=1 AND IsDeleted=0");
$fuels = $objQuery->index("SELECT FuelTypeID, FuelName FROM mst_fueltype WHERE IsActive=1 AND IsDeleted=0");
$groups = $objQuery->index("SELECT TankGroupID, TankGroupName FROM mst_tankgroup WHERE IsActive=1 AND IsDeleted=0");
?>
<div class="table-container">
    <div class="table-header"><h5><i class="fas fa-faucet text-primary me-2"></i><?php echo t('Nozzle Management'); ?></h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New</button></div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>Nozzle Name</th><th>No.</th><th>Dispenser</th><th>Fuel Type</th><th>Group</th><th>Opening General</th><th>Opening Master</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody><?php $sl=1; foreach($data as $row): ?><tr>
            <td><?php echo $sl++; ?></td><td><?php echo htmlspecialchars($row->NozzleName); ?></td><td><?php echo $row->NozzleNo; ?></td>
            <td><?php echo htmlspecialchars($row->DisName ?? 'N/A'); ?></td><td><?php echo htmlspecialchars($row->FuelName ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row->TankGroupName ?? 'N/A'); ?></td>
            <td><?php echo number_format($row->OpeningGeneral, 3); ?></td><td><?php echo number_format($row->OpeningMaster, 3); ?></td>
            <td><span class="badge bg-<?php echo $row->IsActive?'success':'danger'; ?>"><?php echo $row->IsActive?'Active':'Inactive'; ?></span></td>
            <td><button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->NozzleID; ?>" data-name="<?php echo htmlspecialchars($row->NozzleName); ?>" data-no="<?php echo $row->NozzleNo; ?>" data-dis="<?php echo $row->DisID; ?>" data-fuel="<?php echo $row->FuelTypeID; ?>" data-group="<?php echo $row->TankGroupID; ?>" data-general="<?php echo $row->OpeningGeneral; ?>" data-master="<?php echo $row->OpeningMaster; ?>" data-status="<?php echo $row->IsActive; ?>"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->NozzleID; ?>"><i class="fas fa-trash"></i></button></td>
        </tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form id="dataForm" method="POST" action="../../modules/entry/nozzle_entry.php">
        <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Nozzle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Nozzle Name <span class="text-danger">*</span></label><input type="text" name="nozzle_name" id="nozzle_name" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Nozzle No. <span class="text-danger">*</span></label><input type="number" name="nozzle_no" id="nozzle_no" class="form-control" required></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Dispenser <span class="text-danger">*</span></label>
                    <select name="dis_id" id="dis_id" class="form-select select2" required><option value="">Select Dispenser</option>
                    <?php foreach($dispensers as $d): ?><option value="<?php echo $d->DisID; ?>"><?php echo htmlspecialchars($d->DisName); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6 mb-3"><label class="form-label">Fuel Type <span class="text-danger">*</span></label>
                    <select name="fuel_type_id" id="fuel_type_id" class="form-select select2" required><option value="">Select Fuel</option>
                    <?php foreach($fuels as $f): ?><option value="<?php echo $f->FuelTypeID; ?>"><?php echo htmlspecialchars($f->FuelName); ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Tank Group</label>
                    <select name="tank_group_id" id="tank_group_id" class="form-select select2"><option value="">Select Group</option>
                    <?php foreach($groups as $g): ?><option value="<?php echo $g->TankGroupID; ?>"><?php echo htmlspecialchars($g->TankGroupName); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3 mb-3"><label class="form-label">Opening General</label><input type="number" step="0.001" name="opening_general" id="opening_general" class="form-control" value="0"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Opening Master</label><input type="number" step="0.001" name="opening_master" id="opening_master" class="form-control" value="0"></div>
            </div>
            <div class="mb-3"><label class="form-label">Status</label><select name="is_active" id="is_active" class="form-select"><option value="1">Active</option><option value="0">Inactive</option></select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
    </form>
</div></div></div>
<script>
$(document).ready(function() {
    $('#addModal').on('show.bs.modal', function(e) { if(!$(e.relatedTarget).hasClass('edit-btn')){ $('#dataForm')[0].reset();$('#edit_id').val('');$('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Nozzle'); } });
    $(document).on('click', '.edit-btn', function() { const b=$(this);$('#edit_id').val(b.data('id'));$('#nozzle_name').val(b.data('name'));$('#nozzle_no').val(b.data('no'));$('#dis_id').val(b.data('dis')).trigger('change');$('#fuel_type_id').val(b.data('fuel')).trigger('change');$('#tank_group_id').val(b.data('group')).trigger('change');$('#opening_general').val(b.data('general'));$('#opening_master').val(b.data('master'));$('#is_active').val(b.data('status'));$('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Nozzle');$('#addModal').modal('show'); });
    $('#dataForm').on('submit', function(e) { e.preventDefault();const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');$.ajax({url:f.attr('action'),type:'POST',data:f.serialize(),dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');$('#addModal').modal('hide');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},error:function(){showNotification('Error!','error');},complete:function(){btn.prop('disabled',false).html(orig);}});});
    $(document).on('click', '.delete-btn', function() { if(!confirmDelete()) return;const b=$(this),id=b.data('id');b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');$.ajax({url:'../../modules/entry/nozzle_entry.php',type:'POST',data:{action:'delete',record_id:id},dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},complete:function(){b.prop('disabled',false).html('<i class="fas fa-trash"></i>');}});});
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>