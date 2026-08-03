<?php
$pageTitle = 'Fuel Purchase';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
$suppliers = $objQuery->index("SELECT SupplierID, SupplierName FROM mst_supplier WHERE IsActive=1 AND IsDeleted=0");
$fuels = $objQuery->index("SELECT FuelTypeID, FuelName FROM mst_fueltype WHERE IsActive=1 AND IsDeleted=0");
$tanks = $objQuery->index("SELECT TankID, TankName FROM mst_tank WHERE IsActive=1 AND IsDeleted=0");
$data = $objQuery->index("SELECT fp.*, s.SupplierName, ft.FuelName, t.TankName FROM trx_fuelpurchase fp LEFT JOIN mst_supplier s ON fp.SupplierID=s.SupplierID LEFT JOIN mst_fueltype ft ON fp.FuelTypeID=ft.FuelTypeID LEFT JOIN mst_tank t ON fp.TankID=t.TankID WHERE fp.IsDeleted=0 ORDER BY fp.PurchaseDate DESC, fp.FuelPurchaseID DESC");
?>
<div class="table-container">
    <div class="table-header"><h5><i class="fas fa-shopping-cart text-primary me-2"></i>Fuel Purchase</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add New Purchase</button></div>
    <table class="table table-hover datatable">
        <thead><tr><th>SL</th><th>Date</th><th>Invoice</th><th>Supplier</th><th>Fuel</th><th>Tank</th><th>Qty</th><th>Rate</th><th>Amount</th><th>Payment</th><th>Actions</th></tr></thead>
        <tbody><?php $sl=1; foreach($data as $row): ?><tr>
            <td><?php echo $sl++; ?></td><td><?php echo formatDate($row->PurchaseDate); ?></td>
            <td><?php echo htmlspecialchars($row->InvoiceNo ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row->SupplierName ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row->FuelName ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row->TankName ?? 'N/A'); ?></td>
            <td><?php echo number_format($row->Quantity, 3); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->Rate, 2); ?></td>
            <td><?php echo $currencySymbol.' '.number_format($row->TotalAmount, 2); ?></td>
            <td><span class="badge bg-<?php echo $row->PaymentStatus=='Paid'?'success':($row->PaymentStatus=='Partial'?'warning':'danger'); ?>"><?php echo $row->PaymentStatus; ?></span></td>
            <td>
                <button class="btn btn-sm btn-info edit-btn" data-id="<?php echo $row->FuelPurchaseID; ?>" data-date="<?php echo $row->PurchaseDate; ?>" data-invoice="<?php echo htmlspecialchars($row->InvoiceNo ?? ''); ?>" data-supplier="<?php echo $row->SupplierID; ?>" data-fuel="<?php echo $row->FuelTypeID; ?>" data-tank="<?php echo $row->TankID; ?>" data-qty="<?php echo $row->Quantity; ?>" data-rate="<?php echo $row->Rate; ?>" data-amount="<?php echo $row->Amount; ?>" data-tax="<?php echo $row->TaxAmount; ?>" data-total="<?php echo $row->TotalAmount; ?>" data-status="<?php echo $row->PaymentStatus; ?>" data-remarks="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->FuelPurchaseID; ?>"><i class="fas fa-trash"></i></button>
            </td>
        </tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form id="dataForm" method="POST" action="../../modules/entry/fuel_purchase_entry.php">
        <input type="hidden" name="action" value="save"><input type="hidden" name="record_id" id="edit_id" value="">
        <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add New Purchase</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" name="purchase_date" id="purchase_date" class="form-control" required value="<?php echo today(); ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Invoice No</label><input type="text" name="invoice_no" id="invoice_no" class="form-control"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id" id="supplier_id" class="form-select select2" required><option value="">Select Supplier</option>
                    <?php foreach($suppliers as $s): ?><option value="<?php echo $s->SupplierID; ?>"><?php echo htmlspecialchars($s->SupplierName); ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Fuel Type <span class="text-danger">*</span></label>
                    <select name="fuel_type_id" id="fuel_type_id" class="form-select select2" required><option value="">Select Fuel</option>
                    <?php foreach($fuels as $f): ?><option value="<?php echo $f->FuelTypeID; ?>"><?php echo htmlspecialchars($f->FuelName); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4 mb-3"><label class="form-label">Tank <span class="text-danger">*</span></label>
                    <select name="tank_id" id="tank_id" class="form-select select2" required><option value="">Select Tank</option>
                    <?php foreach($tanks as $t): ?><option value="<?php echo $t->TankID; ?>"><?php echo htmlspecialchars($t->TankName); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4 mb-3"><label class="form-label">Quantity <span class="text-danger">*</span></label><input type="number" step="0.001" name="quantity" id="quantity" class="form-control" required></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Rate <span class="text-danger">*</span></label><input type="number" step="0.01" name="rate" id="rate" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" id="amount" class="form-control" readonly></div>
                <div class="col-md-4 mb-3"><label class="form-label">Tax Amount</label><input type="number" step="0.01" name="tax_amount" id="tax_amount" class="form-control" value="0"></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Total Amount</label><input type="number" step="0.01" name="total_amount" id="total_amount" class="form-control" readonly></div>
                <div class="col-md-4 mb-3"><label class="form-label">Payment Status</label>
                    <select name="payment_status" id="payment_status" class="form-select">
                        <option value="Due">Due</option><option value="Partial">Partial</option><option value="Paid">Paid</option>
                    </select></div>
                <div class="col-md-4 mb-3"><label class="form-label">Remarks</label><textarea name="remarks" id="remarks" class="form-control" rows="2"></textarea></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button></div>
    </form>
</div></div></div>
<script>
$(document).ready(function() {
    function calcTotal() {
        const qty = parseFloat($('#quantity').val()) || 0;
        const rate = parseFloat($('#rate').val()) || 0;
        const amt = qty * rate;
        const tax = parseFloat($('#tax_amount').val()) || 0;
        $('#amount').val(amt.toFixed(2));
        $('#total_amount').val((amt + tax).toFixed(2));
    }
    $('#quantity, #rate, #tax_amount').on('input', calcTotal);
    $('#addModal').on('show.bs.modal', function(e) { if(!$(e.relatedTarget).hasClass('edit-btn')){ $('#dataForm')[0].reset();$('#edit_id').val('');$('#purchase_date').val('<?php echo today(); ?>');$('#payment_status').val('Due');$('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>Add New Purchase'); } });
    $(document).on('click', '.edit-btn', function() { const b=$(this);$('#edit_id').val(b.data('id'));$('#purchase_date').val(b.data('date'));$('#invoice_no').val(b.data('invoice'));$('#supplier_id').val(b.data('supplier')).trigger('change');$('#fuel_type_id').val(b.data('fuel')).trigger('change');$('#tank_id').val(b.data('tank')).trigger('change');$('#quantity').val(b.data('qty'));$('#rate').val(b.data('rate'));$('#amount').val(b.data('amount'));$('#tax_amount').val(b.data('tax'));$('#total_amount').val(b.data('total'));$('#payment_status').val(b.data('status'));$('#remarks').val(b.data('remarks'));$('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Purchase');$('#addModal').modal('show'); });
    $('#dataForm').on('submit', function(e) { e.preventDefault();const f=$(this),btn=f.find('[type="submit"]'),orig=btn.html();btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');$.ajax({url:f.attr('action'),type:'POST',data:f.serialize(),dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');$('#addModal').modal('hide');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},error:function(){showNotification('Error!','error');},complete:function(){btn.prop('disabled',false).html(orig);}});});
    $(document).on('click', '.delete-btn', function() { if(!confirmDelete()) return;const b=$(this),id=b.data('id');b.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i>');$.ajax({url:'../../modules/entry/fuel_purchase_entry.php',type:'POST',data:{action:'delete',record_id:id},dataType:'json',success:function(r){if(r.success){showNotification(r.message,'success');setTimeout(()=>location.reload(),500);}else showNotification(r.message,'error');},complete:function(){b.prop('disabled',false).html('<i class="fas fa-trash"></i>');}});});
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>