/**
 * FuelDeskPro - Custom JavaScript
 * 
 * @package FuelDeskPro
 */

// Global AJAX setup
$.ajaxSetup({
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
});

// Global notification function
function showNotification(message, type = 'success') {
    // Check if toast container exists, if not create it
    if ($('#toastContainer').length === 0) {
        $('body').append(`
            <div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
            </div>
        `);
    }

    const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-warning';
    const iconClass = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle';

    const toastId = 'toast-' + Date.now();
    const toast = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${iconClass} me-2"></i> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    $('#toastContainer').append(toast);

    // Auto remove after 4 seconds
    setTimeout(() => {
        $('#' + toastId).remove();
    }, 4000);
}

function appLang() {
    return $('body').data('lang') || 'en';
}

function dataTableLanguage() {
    if (appLang() !== 'bn') {
        return {
            search: 'Search:',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'Showing 0 to 0 of 0 entries',
            emptyTable: 'No data available',
            zeroRecords: 'No matching records found'
        };
    }

    return {
        search: 'খুঁজুন:',
        lengthMenu: '_MENU_ টি দেখান',
        info: '_TOTAL_টির মধ্যে _START_ থেকে _END_ দেখানো হচ্ছে',
        infoEmpty: '০ থেকে ০ দেখানো হচ্ছে',
        emptyTable: 'কোনো তথ্য নেই',
        zeroRecords: 'মিল পাওয়া যায়নি',
        paginate: {
            first: 'প্রথম',
            last: 'শেষ',
            next: 'পরবর্তী',
            previous: 'পূর্ববর্তী'
        }
    };
}

function initBootstrapJqueryBridge() {
    if (!$.fn.modal && window.bootstrap && bootstrap.Modal) {
        $.fn.modal = function(action) {
            return this.each(function() {
                const modal = bootstrap.Modal.getOrCreateInstance(this);
                if (action === 'show') {
                    modal.show(window.fuelDeskLastModalTrigger || undefined);
                } else if (action === 'hide') {
                    modal.hide();
                } else if (action === 'toggle') {
                    modal.toggle();
                }
            });
        };
    }
}

function initAppDataTables() {
    $('.datatable').each(function() {
        if (!$.fn.DataTable.isDataTable(this)) {
            $(this).DataTable({
                responsive: true,
                language: dataTableLanguage()
            });
        }
    });
}

function initAppSelect2() {
    $('.select2').each(function() {
        if (!$(this).data('select2')) {
            $(this).select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : $(document.body)
            });
        }
    });
}

function validateForm(form) {
    let valid = true;
    form.find('[required]').each(function() {
        const field = $(this);
        const value = $.trim(field.val());
        if (!value) {
            field.addClass('is-invalid');
            valid = false;
        } else {
            field.removeClass('is-invalid');
        }
    });

    form.find('input[type="number"]').each(function() {
        const field = $(this);
        if (!field.val()) return;
        const value = parseFloat(field.val());
        const min = field.attr('min');
        const max = field.attr('max');
        if ((min !== undefined && value < parseFloat(min)) || (max !== undefined && value > parseFloat(max))) {
            field.addClass('is-invalid');
            valid = false;
        }
    });

    if (!valid) {
        showNotification(appLang() === 'bn' ? 'প্রয়োজনীয় তথ্য সঠিকভাবে দিন।' : 'Please complete required fields correctly.', 'error');
    }
    return valid;
}

function entrySourceFromForm(form) {
    const action = form.attr('action') || '';
    return action.split('/').pop();
}

function populateFormFromDatabase(form, data) {
    Object.keys(data).forEach(function(name) {
        const field = form.find('[name="' + name + '"]');
        if (!field.length) return;

        field.val(data[name] ?? '');
        if (field.is('select')) {
            field.trigger('change');
        }
        field.removeClass('is-invalid');
    });

    if (data.record_id !== undefined) {
        form.find('#edit_id').val(data.record_id);
    }
    if (data.shift_id !== undefined) {
        form.find('#edit_shift_id').val(data.shift_id);
    }
}

function fetchRecordForEdit(button) {
    const recordId = button.data('id');
    const form = button.closest('body').find('form[action*="/entry/"]').first();
    const source = entrySourceFromForm(form);

    if (!recordId || !source) return;

    $.ajax({
        url: (window.FUELDESK_BASE_URL || '/FuelDeskPro/') + 'modules/entry/get_record.php',
        type: 'POST',
        dataType: 'json',
        data: {
            source: source,
            record_id: recordId
        },
        success: function(response) {
            if (response.success) {
                populateFormFromDatabase(form, response.data || {});
            } else {
                showNotification(response.message || 'Record could not be loaded.', 'error');
            }
        },
        error: function() {
            showNotification('Record could not be loaded from database.', 'error');
        }
    });
}

// Confirm dialog for delete operations
function confirmDelete(message = 'Are you sure you want to delete this record?') {
    return confirm(message);
}

// Format number with commas
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Format date from yyyy-mm-dd to dd-mm-yyyy
function formatDateDisplay(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        return parts[2] + '-' + parts[1] + '-' + parts[0];
    }
    return dateStr;
}

// Convert dd-mm-yyyy to yyyy-mm-dd for database
function formatDateForDB(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        return parts[2] + '-' + parts[1] + '-' + parts[0];
    }
    return dateStr;
}

// Handle AJAX form submission
$(document).ready(function() {
    initBootstrapJqueryBridge();

    document.addEventListener('click', function(event) {
        window.fuelDeskLastModalTrigger = event.target.closest('.edit-btn, [data-bs-toggle="modal"]');
    }, true);

    $(document).on('click', '.edit-btn, .edit-shift', function() {
        const button = $(this);
        setTimeout(function() {
            fetchRecordForEdit(button);
        }, 50);
    });

    $('body').append('<div class="sidebar-backdrop"></div>');

    initAppDataTables();
    initAppSelect2();

    $(document).on('input change', '.is-invalid', function() {
        if ($.trim($(this).val())) {
            $(this).removeClass('is-invalid');
        }
    });

    $(document).on('submit', 'form', function(e) {
        if (!validateForm($(this))) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
    });

    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').toggleClass('active');
        $('#content-wrapper').toggleClass('active');
        $('.sidebar-backdrop').toggleClass('show', $('#sidebar').hasClass('active'));
    });

    $(document).on('click', '.sidebar-backdrop', function() {
        $('#sidebar').removeClass('active');
        $('#content-wrapper').removeClass('active');
        $(this).removeClass('show');
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        $('.alert-dismissible').fadeOut('slow');
    }, 5000);

    // Handle form submission via AJAX for modals
    $(document).on('submit', '.ajax-form', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const url = form.attr('action');
        const method = form.attr('method') || 'POST';
        const submitBtn = form.find('[type="submit"]');
        const originalText = submitBtn.html();

        // Disable button
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        $.ajax({
            url: url,
            type: method,
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    // Close modal if exists
                    form.closest('.modal').modal('hide');
                    // Reload DataTable if exists
                    if (typeof dataTable !== 'undefined') {
                        dataTable.ajax.reload();
                    } else {
                        location.reload();
                    }
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('An error occurred: ' + error, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                submitBtn.html(originalText);
            }
        });
    });

    // Handle delete button clicks
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        
        if (!confirmDelete()) return;

        const url = $(this).data('url');
        const btn = $(this);

        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: url,
            type: 'POST',
            data: { action: 'delete', _method: 'DELETE' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    location.reload();
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
