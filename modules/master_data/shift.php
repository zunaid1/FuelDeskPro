<?php
/**
 * FuelDeskPro - Shift Management (Master Data)
 * 
 * @package FuelDeskPro
 */

$pageTitle = 'Shift Management';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Get all shifts
$sql = "SELECT * FROM mst_shift WHERE IsDeleted = 0 ORDER BY ShiftID ASC";
$shifts = $objQuery->index($sql);
?>

<div class="table-container">
    <div class="table-header">
        <h5><i class="fas fa-clock text-primary me-2"></i><?php echo t('Shift Management'); ?></h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addShiftModal">
            <i class="fas fa-plus"></i> Add New Shift
        </button>
    </div>

    <table class="table table-hover datatable">
        <thead>
            <tr>
                <th>SL</th>
                <th>Shift Name</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $sl = 1; foreach ($shifts as $shift): ?>
            <tr>
                <td><?php echo $sl++; ?></td>
                <td><?php echo htmlspecialchars($shift->ShiftName); ?></td>
                <td><?php echo $shift->StartTime ? date('h:i A', strtotime($shift->StartTime)) : 'N/A'; ?></td>
                <td><?php echo $shift->EndTime ? date('h:i A', strtotime($shift->EndTime)) : 'N/A'; ?></td>
                <td>
                    <span class="badge bg-<?php echo $shift->IsActive ? 'success' : 'danger'; ?>">
                        <?php echo $shift->IsActive ? 'Active' : 'Inactive'; ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-info edit-shift" 
                        data-id="<?php echo $shift->ShiftID; ?>"
                        data-name="<?php echo htmlspecialchars($shift->ShiftName); ?>"
                        data-start="<?php echo $shift->StartTime; ?>"
                        data-end="<?php echo $shift->EndTime; ?>"
                        data-status="<?php echo $shift->IsActive; ?>"
                        title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-shift" 
                        data-id="<?php echo $shift->ShiftID; ?>"
                        title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Shift Modal -->
<div class="modal fade" id="addShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="shiftForm" method="POST" action="../../modules/entry/shift_entry.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="shift_id" id="edit_shift_id" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Shift</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Shift Name <span class="text-danger">*</span></label>
                        <input type="text" name="shift_name" id="shift_name" class="form-control" required 
                               placeholder="e.g. Morning Shift">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="end_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="is_active" id="is_active" class="form-select">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <i class="fas fa-save me-1"></i> Save Shift
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Reset modal on open for Add
    $('#addShiftModal').on('show.bs.modal', function(e) {
        // Check if triggered by edit button
        if (!$(e.relatedTarget).hasClass('edit-shift')) {
            // Add mode - reset form
            $('#shiftForm')[0].reset();
            $('#edit_shift_id').val('');
            $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Shift');
            $('#saveBtn').html('<i class="fas fa-save me-1"></i> Save Shift');
            $('#shift_name').prop('readonly', false);
        }
    });

    // Edit button click
    $(document).on('click', '.edit-shift', function() {
        const btn = $(this);
        
        $('#edit_shift_id').val(btn.data('id'));
        $('#shift_name').val(btn.data('name'));
        $('#start_time').val(btn.data('start'));
        $('#end_time').val(btn.data('end'));
        $('#is_active').val(btn.data('status'));
        
        $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Shift');
        $('#saveBtn').html('<i class="fas fa-update me-1"></i> Update Shift');
        
        // Open modal
        $('#addShiftModal').modal('show');
    });

    // Handle form submission
    $('#shiftForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const formData = form.serialize();
        const submitBtn = form.find('[type="submit"]');
        const originalText = submitBtn.html();

        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    $('#addShiftModal').modal('hide');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('An error occurred!', 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                submitBtn.html(originalText);
            }
        });
    });

    // Delete button click
    $(document).on('click', '.delete-shift', function() {
        if (!confirmDelete('Are you sure you want to delete this shift?')) return;

        const btn = $(this);
        const shiftId = btn.data('id');
        
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '../../modules/entry/shift_entry.php',
            type: 'POST',
            data: { action: 'delete', shift_id: shiftId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification(response.message, 'error');
                    btn.prop('disabled', false);
                    btn.html('<i class="fas fa-trash"></i>');
                }
            },
            error: function() {
                showNotification('Delete failed!', 'error');
                btn.prop('disabled', false);
                btn.html('<i class="fas fa-trash"></i>');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>