<?php
$pageTitle = 'Customer Dues';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
$customers = $objQuery->index("SELECT * FROM vw_customerlistall");
$fuels = $objQuery->index("SELECT FuelTypeID, FuelName, SellingRate FROM mst_fueltype WHERE IsActive=1 AND IsDeleted=0");
$data = $objQuery->index("SELECT cd.*, COALESCE(c.CustomerNameBN, c.CustomerNameEN) AS CustomerName, c.Mobile, ft.FuelName FROM trx_customerdue cd LEFT JOIN vw_customerlistall c ON cd.CustomerID=c.CustomerId LEFT JOIN mst_fueltype ft ON cd.FuelTypeID=ft.FuelTypeID WHERE cd.IsDeleted=0 ORDER BY cd.TxnDate DESC, cd.CustomerDueID DESC");
?>
<?php if (isStatementClosed(today())): ?>
<div class="alert alert-danger shadow-sm border-danger text-center fw-bold fs-6 mb-3 py-2">
    <i class="fas fa-lock me-2"></i> আজকের তারিখের (<?php echo date('d-m-Y'); ?>) হিসাবটি ইতোমধ্যে ক্লোজ করা হয়েছে
</div>
<?php endif; ?>
<div class="table-container">
    <div class="table-header"><h5><i class="fas fa-file-invoice text-primary me-2"></i>Customer Dues</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New Due</button></div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>Date</th><th>Customer</th><th>Mobile</th><th>Fuel</th><th>Vehicle</th><th>Qty</th><th>Rate</th><th>Total</th><th>Paid</th><th>Due</th><th>Actions</th></tr></thead>
        <tbody><?php $sl=1; foreach($data as $row): ?><tr>
            <td><?php echo $sl++; ?></td><td><?php echo formatDate($row->TxnDate); ?></td>
            <td><?php echo htmlspecialchars($row->CustomerName ?? 'N/A'); ?></td><td><?php echo htmlspecialchars($row->Mobile ?? ''); ?></td>
            <td><?php echo htmlspecialchars($row->FuelName ?? 'N/A'); ?></td><td><?php echo htmlspecialchars($row->VehicleNumber ?? 'N/A'); ?></td>
            <td><?php echo number_format($row->Quantity, 3); ?></td><td><?php echo $currencySymbol.' '.number_format($row->Rate, 2); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->TotalAmount, 2); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->PaidAmount, 2); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->DueAmount, 2); ?></td>
            <td>
                <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->CustomerDueID; ?>" data-date="<?php echo $row->TxnDate; ?>" data-customer="<?php echo $row->CustomerID; ?>" data-fuel="<?php echo $row->FuelTypeID; ?>" data-vehicle="<?php echo htmlspecialchars($row->VehicleNumber ?? ''); ?>" data-qty="<?php echo $row->Quantity; ?>" data-rate="<?php echo $row->Rate; ?>" data-total="<?php echo $row->TotalAmount; ?>" data-paid="<?php echo $row->PaidAmount; ?>" data-due="<?php echo $row->DueAmount; ?>" data-remarks="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->CustomerDueID; ?>"><i class="fas fa-trash"></i></button>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form id="dataForm" method="POST" action="../../modules/entry/customer_due_entry.php">
        <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Due</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" name="txn_date" id="txn_date" class="form-control" required value="<?php echo today(); ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" id="customer_id" class="form-select select2" required><option value="">Select Customer</option>
                    <?php foreach($customers as $c): $cId = $c->CustomerId ?? $c->CustomerID ?? 0; $cName = $c->CustomerNameBN ?? $c->CustomerNameEN ?? $c->CustomerName ?? 'N/A'; ?><option value="<?php echo $cId; ?>"><?php echo htmlspecialchars($cName.' ('.($c->Mobile ?? '').')'); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4 mb-3"><label class="form-label">Fuel Type <span class="text-danger">*</span></label>
                    <select name="fuel_type_id" id="fuel_type_id" class="form-select select2" required><option value="">Select Fuel</option>
                    <?php foreach($fuels as $f): ?><option value="<?php echo $f->FuelTypeID; ?>" data-rate="<?php echo $f->SellingRate ?? 0; ?>"><?php echo htmlspecialchars($f->FuelName); ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Vehicle Number</label><input type="text" name="vehicle_no" id="vehicle_no" class="form-control"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Quantity</label><input type="number" step="0.001" name="quantity" id="quantity" class="form-control" placeholder="0.000"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Rate <span class="text-danger">*</span></label><input type="number" step="0.01" name="rate" id="rate" class="form-control" required placeholder="0.00"></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Total Amount (বিক্রয় টাকা) <span class="text-danger">*</span></label><input type="number" step="0.01" name="total_amount" id="total_amount" class="form-control" required placeholder="0.00"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Paid Amount</label><input type="number" step="0.01" name="paid_amount" id="paid_amount" class="form-control" value="0"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Due Amount</label><input type="number" step="0.01" name="due_amount" id="due_amount" class="form-control" readonly></div>
            </div>
            <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
    </form>
</div></div></div>
<script>
$(document).ready(function() {
    let lastSource = 'qty';

    function calcDue() {
        const qtyVal = $('#quantity').val();
        const rateVal = $('#rate').val();
        const totalVal = $('#total_amount').val();
        
        const qty = parseFloat(qtyVal) || 0;
        const rate = parseFloat(rateVal) || 0;
        const total = parseFloat(totalVal) || 0;
        const paid = parseFloat($('#paid_amount').val()) || 0;

        let finalTotal = total;

        if (lastSource === 'total') {
            finalTotal = total;
            if (rate > 0 && totalVal !== '') {
                const calcQty = total / rate;
                $('#quantity').val(calcQty > 0 ? calcQty.toFixed(3) : '');
            }
        } else if (lastSource === 'qty') {
            if (rate > 0 && qtyVal !== '') {
                finalTotal = qty * rate;
                $('#total_amount').val(finalTotal > 0 ? finalTotal.toFixed(2) : '');
            }
        } else if (lastSource === 'rate') {
            if (total > 0 && (qty === 0 || totalVal !== '')) {
                const calcQty = total / rate;
                $('#quantity').val(calcQty > 0 ? calcQty.toFixed(3) : '');
                finalTotal = total;
            } else if (qty > 0) {
                finalTotal = qty * rate;
                $('#total_amount').val(finalTotal > 0 ? finalTotal.toFixed(2) : '');
            }
        }

        const due = Math.max(0, finalTotal - paid);
        $('#due_amount').val(due.toFixed(2));
    }

    $('#quantity').on('input', function() {
        lastSource = 'qty';
        calcDue();
    });

    $('#total_amount').on('input', function() {
        lastSource = 'total';
        calcDue();
    });

    $('#rate').on('input', function() {
        calcDue();
    });

    $('#paid_amount').on('input', function() {
        calcDue();
    });

    $('#fuel_type_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const defaultRate = parseFloat(selectedOption.data('rate')) || 0;
        if (defaultRate > 0) {
            $('#rate').val(defaultRate);
            calcDue();
        }
    });

    $('#addModal').on('show.bs.modal', function(e) {
        if(!$(e.relatedTarget).hasClass('edit-btn')){
            $('#dataForm')[0].reset();
            $('#edit_id').val('');
            $('#txn_date').val('<?php echo today(); ?>');
            $('#customer_id').val('').trigger('change');
            $('#fuel_type_id').val('').trigger('change');
            $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Due');
            lastSource = 'qty';
        }
    });

    $(document).on('click', '.edit-btn', function() {
        const b=$(this);
        $('#edit_id').val(b.data('id'));
        $('#txn_date').val(b.data('date'));
        $('#customer_id').val(b.data('customer')).trigger('change');
        $('#fuel_type_id').val(b.data('fuel')).trigger('change');
        $('#vehicle_no').val(b.data('vehicle'));
        $('#quantity').val(b.data('qty'));
        $('#rate').val(b.data('rate'));
        $('#total_amount').val(b.data('total'));
        $('#paid_amount').val(b.data('paid'));
        $('#due_amount').val(b.data('due'));
        $('#remarks').val(b.data('remarks'));
        $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Due');
        lastSource = 'total';
        $('#addModal').modal('show');
    });

    $('#dataForm').on('submit', function(e) {
        e.preventDefault();
        calcDue();
        const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();
        btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        $.ajax({
            url:f.attr('action'),
            type:'POST',
            data:f.serialize(),
            dataType:'json',
            success:function(r){
                if(r.success){
                    showNotification(r.message,'success');
                    $('#addModal').modal('hide');
                    setTimeout(()=>location.reload(),500);
                } else showNotification(r.message,'error');
            },
            error:function(xhr){
                let msg = 'Error!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    msg = xhr.responseText.replace(/<[^>]+>/g, '').trim().substring(0, 150) || 'Error!';
                }
                showNotification(msg,'error');
            },
            complete:function(){btn.prop('disabled',false).html(orig);}
        });
    });

    $(document).on('click', '.delete-btn', function() {
        if(!confirmDelete()) return;
        const b=$(this),id=b.data('id');
        b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url:'../../modules/entry/customer_due_entry.php',
            type:'POST',
            data:{action:'delete',record_id:id},
            dataType:'json',
            success:function(r){
                if(r.success){
                    showNotification(r.message,'success');
                    setTimeout(()=>location.reload(),500);
                } else showNotification(r.message,'error');
            },
            complete:function(){b.prop('disabled',false).html('<i class="fas fa-trash"></i>');}
        });
    });
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>