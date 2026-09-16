<?php
/**
 * FuelDeskPro - System Settings Management (Master Data / Configuration)
 * 
 * Manage Key-Value system settings stored in cfg_systemsetting.
 * 
 * @package FuelDeskPro
 */

$pageTitle = 'System Settings';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

// Fetch all system settings from cfg_systemsetting
$sql = "SELECT * FROM cfg_systemsetting WHERE IsDeleted = 0 ORDER BY SystemSettingID ASC";
$settings = $objQuery->index($sql);

$totalSettings = count($settings);
$activeSettings = 0;
foreach ($settings as $s) {
    if ($s->IsActive) $activeSettings++;
}
?>

<div class="row mb-3">
    <div class="col-md-6 col-xl-3">
        <div class="card bg-primary text-white shadow-sm">
            <div class="card-body py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-white-50 mb-1">Total System Settings</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $totalSettings; ?></h3>
                </div>
                <div class="fs-1 text-white-50"><i class="fas fa-cogs"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card bg-success text-white shadow-sm">
            <div class="card-body py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-white-50 mb-1">Active Settings</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $activeSettings; ?></h3>
                </div>
                <div class="fs-1 text-white-50"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h5><i class="fas fa-cog text-primary me-2"></i><?php echo t('System Settings'); ?></h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSettingModal">
            <i class="fas fa-plus me-1"></i> Add New Setting
        </button>
    </div>

    <table class="table table-hover datatable">
        <thead>
            <tr>
                <th style="width: 5%;">SL</th>
                <th style="width: 25%;">Setting Key</th>
                <th style="width: 20%;">Setting Value</th>
                <th style="width: 35%;">Description</th>
                <th style="width: 7%;">Status</th>
                <th style="width: 8%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $sl = 1; foreach ($settings as $setting): ?>
            <tr>
                <td><?php echo $sl++; ?></td>
                <td><code class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($setting->SettingKey); ?></code></td>
                <td>
                    <span class="badge bg-secondary text-wrap fs-6" style="max-width: 200px;">
                        <?php echo htmlspecialchars($setting->SettingValue ?? 'N/A'); ?>
                    </span>
                </td>
                <td class="small text-muted"><?php echo nl2br(htmlspecialchars($setting->Description ?? '')); ?></td>
                <td>
                    <span class="badge bg-<?php echo $setting->IsActive ? 'success' : 'danger'; ?>">
                        <?php echo $setting->IsActive ? 'Active' : 'Inactive'; ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-info edit-setting" 
                        data-bs-toggle="modal"
                        data-bs-target="#addSettingModal"
                        data-id="<?php echo $setting->SystemSettingID; ?>"
                        data-key="<?php echo htmlspecialchars($setting->SettingKey, ENT_QUOTES); ?>"
                        data-value="<?php echo htmlspecialchars($setting->SettingValue ?? '', ENT_QUOTES); ?>"
                        data-description="<?php echo htmlspecialchars($setting->Description ?? '', ENT_QUOTES); ?>"
                        data-status="<?php echo $setting->IsActive; ?>"
                        title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-setting" 
                        data-id="<?php echo $setting->SystemSettingID; ?>"
                        title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Setting Modal -->
<div class="modal fade" id="addSettingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="settingForm" method="POST" action="<?php echo BASE_URL; ?>modules/entry/system_setting_entry.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="setting_id" id="edit_setting_id" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New System Setting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Setting Key <span class="text-danger">*</span></label>
                        <input type="text" name="setting_key" id="setting_key" class="form-control" required 
                               placeholder="e.g. IsMeterReadingReadOnly">
                        <small class="text-muted">Unique key identifier used by the application.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Setting Value</label>
                        <input type="text" name="setting_value" id="setting_value" class="form-control" 
                               placeholder="e.g. Yes or INV">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" 
                                  placeholder="Explain what this setting controls..."></textarea>
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
                        <i class="fas fa-save me-1"></i> Save Setting
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle modal show event for both Add and Edit
    $('#addSettingModal').on('show.bs.modal', function(e) {
        const button = $(e.relatedTarget);
        if (button.hasClass('edit-setting')) {
            // Edit Mode - populate form fields from data attributes
            $('#edit_setting_id').val(button.data('id'));
            $('#setting_key').val(button.data('key'));
            $('#setting_value').val(button.data('value'));
            $('#description').val(button.data('description'));
            $('#is_active').val(button.data('status'));
            
            $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit System Setting');
            $('#saveBtn').html('<i class="fas fa-save me-1"></i> Update Setting');
        } else {
            // Add Mode - reset form fields
            $('#settingForm')[0].reset();
            $('#edit_setting_id').val('');
            $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New System Setting');
            $('#saveBtn').html('<i class="fas fa-save me-1"></i> Save Setting');
        }
    });

    // Handle form submission
    $('#settingForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const formData = form.serialize();
        const submitBtn = form.find('[type="submit"]');
        const originalText = submitBtn.html();

        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    $('#addSettingModal').modal('hide');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('An error occurred while saving!', 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                submitBtn.html(originalText);
            }
        });
    });

    // Delete button click
    $(document).on('click', '.delete-setting', function() {
        if (!confirmDelete('Are you sure you want to delete this setting?')) return;

        const btn = $(this);
        const settingId = btn.data('id');
        
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '<?php echo BASE_URL; ?>modules/entry/system_setting_entry.php',
            type: 'POST',
            data: { action: 'delete', setting_id: settingId },
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
