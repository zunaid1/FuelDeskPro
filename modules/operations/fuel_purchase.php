<?php
/**
 * FuelDeskPro - Multi-Item Fuel Purchase Management
 * 
 * Supports purchasing multiple fuel items in a single invoice, saving records into
 * trx_fuelpurchase (Header), trx_purchase_details (Line Items), and trx_stock_in (Stock Ledger).
 * 
 * @package FuelDeskPro
 */

$pageTitle = 'Fuel Purchase';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

ensureFuelPurchaseTablesExist();

$suppliers = $objQuery->index("SELECT SupplierID, SupplierName FROM mst_supplier WHERE IsActive=1 AND IsDeleted=0 ORDER BY SupplierName ASC");
$fuels     = $objQuery->index("SELECT FuelTypeID, FuelName FROM mst_fueltype WHERE IsActive=1 AND IsDeleted=0 ORDER BY FuelTypeID ASC");
$tanks     = $objQuery->index("SELECT TankID, TankName, FuelTypeID FROM mst_tank WHERE IsActive=1 AND IsDeleted=0 ORDER BY TankID ASC");

$sqlData = "SELECT fp.*, s.SupplierName, 
            (SELECT COUNT(*) FROM trx_purchase_details pd WHERE pd.FuelPurchaseID = fp.FuelPurchaseID AND pd.IsDeleted = 0) AS ItemCount,
            (SELECT COALESCE(SUM(pd.Quantity), 0) FROM trx_purchase_details pd WHERE pd.FuelPurchaseID = fp.FuelPurchaseID AND pd.IsDeleted = 0) AS TotalQty
            FROM trx_fuelpurchase fp 
            LEFT JOIN mst_supplier s ON fp.SupplierID = s.SupplierID 
            WHERE fp.IsDeleted = 0 
            ORDER BY fp.PurchaseDate DESC, fp.FuelPurchaseID DESC";
$data = $objQuery->index($sqlData);
?>

<div class="table-container shadow-sm rounded bg-white p-3">
    <div class="table-header d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-shopping-cart me-2"></i>Fuel Purchase Invoices</h5>
        <button class="btn btn-primary btn-sm fw-bold" id="btnOpenAddModal" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus me-1"></i> Add New Purchase Invoice
        </button>
    </div>

    <table class="table table-hover align-middle datatable">
        <thead class="table-light">
            <tr>
                <th style="width: 5%;">SL</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 12%;">Invoice No</th>
                <th style="width: 20%;">Supplier</th>
                <th style="width: 10%; text-align: center;">Items</th>
                <th style="width: 12%; text-align: right;">Total Qty (L)</th>
                <th style="width: 12%; text-align: right;">Total Amount</th>
                <th style="width: 9%; text-align: center;">Payment</th>
                <th style="width: 10%; text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sl = 1; 
            foreach($data as $row): 
                $badgeClass = $row->PaymentStatus === 'Paid' ? 'success' : ($row->PaymentStatus === 'Partial' ? 'warning' : 'danger');
            ?>
            <tr>
                <td><?php echo $sl++; ?></td>
                <td><?php echo formatDate($row->PurchaseDate); ?></td>
                <td class="fw-bold text-dark"><?php echo htmlspecialchars($row->InvoiceNo ?: 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($row->SupplierName ?: 'N/A'); ?></td>
                <td class="text-center">
                    <span class="badge bg-info text-dark font-weight-bold px-2 py-1">
                        <i class="fas fa-boxes me-1"></i><?php echo intval($row->ItemCount ?: 1); ?> Items
                    </span>
                </td>
                <td class="text-right font-weight-bold">
                    <?php echo number_format(floatval($row->TotalQty ?: $row->Quantity), 2); ?> L
                </td>
                <td class="text-right font-weight-bold text-success">
                    <?php echo $currencySymbol . ' ' . number_format($row->TotalAmount, 2); ?>
                </td>
                <td class="text-center">
                    <span class="badge bg-<?php echo $badgeClass; ?> px-2 py-1"><?php echo htmlspecialchars($row->PaymentStatus); ?></span>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-secondary view-details-btn me-1" data-id="<?php echo $row->FuelPurchaseID; ?>" title="View Items">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-info edit-btn me-1" data-id="<?php echo $row->FuelPurchaseID; ?>" title="Edit Invoice">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row->FuelPurchaseID; ?>" title="Delete Invoice">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add / Edit Multi-Item Purchase Modal -->
<div class="modal fade" id="addModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="dataForm" method="POST" action="../../modules/entry/fuel_purchase_entry.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="record_id" id="edit_id" value="">
                
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalTitle">
                        <i class="fas fa-plus-circle text-primary me-2"></i>Add New Purchase Invoice
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <!-- Invoice Header Information -->
                    <div class="card mb-3 border-0 bg-light">
                        <div class="card-body py-2">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="purchase_date" id="purchase_date" class="form-control" required value="<?php echo today(); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Invoice No</label>
                                    <input type="text" name="invoice_no" id="invoice_no" class="form-control" placeholder="e.g. INV-2026-001">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Supplier <span class="text-danger">*</span></label>
                                    <select name="supplier_id" id="supplier_id" class="form-select select2" required>
                                        <option value="">Select Supplier</option>
                                        <?php foreach($suppliers as $s): ?>
                                            <option value="<?php echo $s->SupplierID; ?>"><?php echo htmlspecialchars($s->SupplierName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Payment Status</label>
                                    <select name="payment_status" id="payment_status" class="form-select">
                                        <option value="Due">Due</option>
                                        <option value="Partial">Partial</option>
                                        <option value="Paid">Paid</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Multi-Item Purchase Grid Table -->
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm align-middle" id="itemsTable">
                            <thead class="table-secondary">
                                <tr class="text-center">
                                    <th style="width: 25%;">Fuel Type (পণ্য) <span class="text-danger">*</span></th>
                                    <th style="width: 25%;">Storage Tank (ট্যাংক) <span class="text-danger">*</span></th>
                                    <th style="width: 15%;">Quantity / Liters <span class="text-danger">*</span></th>
                                    <th style="width: 15%;">Rate (দর) <span class="text-danger">*</span></th>
                                    <th style="width: 15%;">Total Line Amount (TK)</th>
                                    <th style="width: 5%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <!-- Dynamic Item Rows appended via JS -->
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <button type="button" class="btn btn-sm btn-success fw-bold" id="btnAddRow">
                            <i class="fas fa-plus me-1"></i> Add Another Product Line (পণ্য যোগ করুন)
                        </button>
                        <div class="text-end">
                            <span class="text-muted me-2">Item Count:</span>
                            <span class="fw-bold fs-6 text-dark" id="lblItemCount">0</span>
                        </div>
                    </div>

                    <!-- Summary, Tax & Discount Section -->
                    <div class="row justify-content-end bg-light p-2 rounded mx-0">
                        <div class="col-md-5">
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <label class="fw-bold mb-0">Sub Total Amount:</label>
                                <input type="number" step="0.01" name="sub_total" id="sub_total" class="form-control text-end fw-bold" style="width: 170px;" readonly value="0.00">
                            </div>

                            <!-- Discount Row with Toggle Control -->
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <input type="hidden" name="discount_type" id="discount_type" value="Fixed">
                                <input type="hidden" name="discount_amount" id="discount_amount" value="0.00">
                                
                                <div class="d-flex align-items-center me-2">
                                    <label class="fw-bold mb-0 me-1" id="lblDiscount">Discount:</label>
                                    <div class="btn-group btn-group-sm" role="group" id="discountToggleGroup">
                                        <button type="button" class="btn btn-primary btn-sm px-2 py-0 fw-bold active" id="btnModeFixed" title="Discount in Taka (TK)">TK</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm px-2 py-0 fw-bold" id="btnModePercent" title="Discount in Percent (%)">%</button>
                                    </div>
                                </div>
                                <div style="width: 170px;">
                                    <input type="number" step="0.01" name="discount_value" id="discount_value" class="form-control text-end fw-bold text-danger" placeholder="0.00" value="0.00">
                                </div>
                            </div>
                            <div class="mb-2 d-flex justify-content-between align-items-center text-danger small fw-bold" id="rowDiscountCalc" style="display: none;">
                                <span>Calculated Discount:</span>
                                <span>- <span id="lblDiscountCalc">0.00</span> TK</span>
                            </div>

                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <label class="fw-bold mb-0">Tax / Freight Charge:</label>
                                <input type="number" step="0.01" name="tax_amount" id="tax_amount" class="form-control text-end fw-bold" style="width: 170px;" value="0.00">
                            </div>
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <label class="fw-bold mb-0 text-primary fs-6">Grand Total Amount:</label>
                                <input type="number" step="0.01" name="total_amount" id="total_amount" class="form-control text-end fw-bold fs-6 border-primary text-success" style="width: 170px;" readonly value="0.00">
                            </div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-bold">Invoice Remarks / Notes</label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="5" placeholder="Additional details, delivery challan no, vehicle info..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-save me-1"></i> Save Purchase Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="viewModalTitle">
                    <i class="fas fa-file-invoice me-2"></i>Purchase Invoice Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2 text-muted">Loading invoice details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const fuelOptions = `<?php foreach($fuels as $f): ?><option value="<?php echo $f->FuelTypeID; ?>"><?php echo htmlspecialchars($f->FuelName); ?></option><?php endforeach; ?>`;
    const tankOptions = `<?php foreach($tanks as $t): ?><option value="<?php echo $t->TankID; ?>" data-fuel="<?php echo $t->FuelTypeID; ?>"><?php echo htmlspecialchars($t->TankName); ?></option><?php endforeach; ?>`;

    function createRowHtml(data = {}) {
        const fuelId = data.FuelTypeID || data.fuel_type_id || '';
        const tankId = data.TankID || data.tank_id || '';
        const qty    = data.Quantity || data.quantity || '';
        const rate   = data.Rate || data.rate || '';
        const amt    = data.Amount || data.amount || '';

        return `
            <tr class="item-row">
                <td>
                    <select name="fuel_type_id[]" class="form-select form-select-sm row-fuel" required>
                        <option value="">-- Select Fuel --</option>
                        ${fuelOptions}
                    </select>
                </td>
                <td>
                    <select name="tank_id[]" class="form-select form-select-sm row-tank" required>
                        <option value="">-- Select Tank --</option>
                        ${tankOptions}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.001" name="quantity[]" class="form-control form-select-sm text-end row-qty" value="${qty}" placeholder="0.000" required>
                </td>
                <td>
                    <input type="number" step="0.01" name="rate[]" class="form-control form-select-sm text-end row-rate" value="${rate}" placeholder="0.00" required>
                </td>
                <td>
                    <input type="number" step="0.01" name="amount[]" class="form-control form-select-sm text-end row-amount" value="${amt}" readonly>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Remove Row">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    function addRow(data = {}) {
        const row = $(createRowHtml(data));
        $('#itemsTableBody').append(row);
        if (data.FuelTypeID || data.fuel_type_id) {
            row.find('.row-fuel').val(data.FuelTypeID || data.fuel_type_id);
        }
        if (data.TankID || data.tank_id) {
            row.find('.row-tank').val(data.TankID || data.tank_id);
        }
        recalcTotals();
    }

    function setDiscountMode(mode) {
        $('#discount_type').val(mode);
        if (mode === 'Percentage') {
            $('#btnModePercent').addClass('active btn-primary').removeClass('btn-outline-primary');
            $('#btnModeFixed').removeClass('active btn-primary').addClass('btn-outline-primary');
            $('#discount_value').attr('placeholder', '0.00 (%)');
        } else {
            $('#btnModeFixed').addClass('active btn-primary').removeClass('btn-outline-primary');
            $('#btnModePercent').removeClass('active btn-primary').addClass('btn-outline-primary');
            $('#discount_value').attr('placeholder', '0.00 (TK)');
        }
        recalcTotals();
    }

    $('#btnModeFixed').on('click', function() {
        setDiscountMode('Fixed');
    });

    $('#btnModePercent').on('click', function() {
        setDiscountMode('Percentage');
    });

    function recalcTotals() {
        let subTotal = 0;
        let rowCount = 0;

        $('#itemsTableBody .item-row').each(function() {
            rowCount++;
            const qty = parseFloat($(this).find('.row-qty').val()) || 0;
            const rate = parseFloat($(this).find('.row-rate').val()) || 0;
            const lineAmt = qty * rate;
            
            $(this).find('.row-amount').val(lineAmt.toFixed(2));
            subTotal += lineAmt;
        });

        $('#lblItemCount').text(rowCount);
        $('#sub_total').val(subTotal.toFixed(2));

        const mode = $('#discount_type').val() || 'Fixed';
        const discVal = parseFloat($('#discount_value').val()) || 0;
        let discAmt = 0;

        if (mode === 'Percentage') {
            discAmt = (subTotal * discVal) / 100;
            if (discVal > 0) {
                $('#rowDiscountCalc').show();
                $('#lblDiscountCalc').text(discAmt.toFixed(2));
            } else {
                $('#rowDiscountCalc').hide();
            }
        } else {
            discAmt = discVal;
            $('#rowDiscountCalc').hide();
        }

        $('#discount_amount').val(discAmt.toFixed(2));

        const tax = parseFloat($('#tax_amount').val()) || 0;
        const grandTotal = Math.max(0, subTotal - discAmt + tax);
        $('#total_amount').val(grandTotal.toFixed(2));
    }

    // Event Listeners for dynamic grid
    $('#btnAddRow').on('click', function() {
        addRow();
    });

    $(document).on('click', '.btn-remove-row', function() {
        if ($('#itemsTableBody .item-row').length > 1) {
            $(this).closest('tr').remove();
            recalcTotals();
        } else {
            alert('Invoice must contain at least one item!');
        }
    });

    $(document).on('input change', '.row-qty, .row-rate, #tax_amount, #discount_value', function() {
        recalcTotals();
    });

    // Reset Modal Form
    $('#btnOpenAddModal').on('click', function() {
        $('#dataForm')[0].reset();
        $('#edit_id').val('');
        $('#purchase_date').val('<?php echo today(); ?>');
        $('#payment_status').val('Due');
        $('#tax_amount').val('0.00');
        $('#discount_value').val('0.00');
        setDiscountMode('Fixed');
        $('#itemsTableBody').empty();
        $('#modalTitle').html('<i class="fas fa-plus-circle text-primary me-2"></i>Add New Purchase Invoice');
        addRow(); // Default 1 row
    });

    // Edit Invoice Event
    $(document).on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        $('#dataForm')[0].reset();
        $('#edit_id').val(id);
        $('#itemsTableBody').empty();
        $('#modalTitle').html('<i class="fas fa-edit text-info me-2"></i>Edit Purchase Invoice #' + id);

        $.ajax({
            url: '../../modules/entry/get_record.php',
            type: 'POST',
            data: { source: 'fuel_purchase_entry.php', record_id: id },
            dataType: 'json',
            success: function(r) {
                if (r.success && r.data) {
                    $('#purchase_date').val(r.data.purchase_date);
                    $('#invoice_no').val(r.data.invoice_no);
                    $('#supplier_id').val(r.data.supplier_id).trigger('change');
                    $('#payment_status').val(r.data.payment_status);
                    $('#tax_amount').val(r.data.tax_amount || 0);
                    $('#discount_value').val(r.data.discount_value || 0);
                    setDiscountMode(r.data.discount_type || 'Fixed');
                    $('#remarks').val(r.data.remarks);

                    if (r.data.items && r.data.items.length > 0) {
                        r.data.items.forEach(item => addRow(item));
                    } else {
                        // Fallback for single-item legacy records
                        addRow({
                            fuel_type_id: r.data.fuel_type_id,
                            tank_id: r.data.tank_id,
                            quantity: r.data.quantity,
                            rate: r.data.rate,
                            amount: r.data.amount
                        });
                    }
                    $('#addModal').modal('show');
                } else {
                    showNotification(r.message || 'Error loading record', 'error');
                }
            },
            error: function() {
                showNotification('Error connecting to server!', 'error');
            }
        });
    });

    // View Invoice Details Event
    $(document).on('click', '.view-details-btn', function() {
        const id = $(this).data('id');
        $('#viewDetailsModal').modal('show');
        $('#viewModalTitle').html('<i class="fas fa-file-invoice me-2"></i>Purchase Invoice Details #' + id);
        $('#viewModalBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">Loading...</p></div>');

        $.ajax({
            url: '../../modules/entry/get_record.php',
            type: 'POST',
            data: { source: 'fuel_purchase_entry.php', record_id: id },
            dataType: 'json',
            success: function(r) {
                if (r.success && r.data) {
                    let itemsHtml = '';
                    let totalQty = 0;
                    let totalAmount = 0;

                    if (r.data.items && r.data.items.length > 0) {
                        let sl = 1;
                        r.data.items.forEach(item => {
                            const q = parseFloat(item.Quantity) || 0;
                            const a = parseFloat(item.Amount) || 0;
                            totalQty += q;
                            totalAmount += a;
                            itemsHtml += `
                                <tr>
                                    <td class="text-center">${sl++}</td>
                                    <td>Fuel Type #${item.FuelTypeID}</td>
                                    <td>Tank #${item.TankID}</td>
                                    <td class="text-end fw-bold">${q.toFixed(2)} L</td>
                                    <td class="text-end">${parseFloat(item.Rate).toFixed(2)}</td>
                                    <td class="text-end fw-bold">${a.toFixed(2)}</td>
                                </tr>
                            `;
                        });
                    }

                    const discTypeLabel = r.data.discount_type === 'Percentage' ? (parseFloat(r.data.discount_value || 0) + '%') : 'Flat';
                    const discAmt = parseFloat(r.data.discount_amount || 0);

                    const html = `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6><strong>Invoice No:</strong> ${r.data.invoice_no || 'N/A'}</h6>
                                <h6><strong>Purchase Date:</strong> ${r.data.purchase_date}</h6>
                            </div>
                            <div class="col-md-6 text-end">
                                <h6><strong>Payment Status:</strong> <span class="badge bg-primary">${r.data.payment_status}</span></h6>
                                <h6><strong>Sub Total:</strong> ${parseFloat(r.data.amount || 0).toFixed(2)} TK</h6>
                                ${discAmt > 0 ? `<h6 class="text-danger"><strong>Discount (${discTypeLabel}):</strong> -${discAmt.toFixed(2)} TK</h6>` : ''}
                                <h6><strong>Tax/Freight:</strong> +${parseFloat(r.data.tax_amount || 0).toFixed(2)} TK</h6>
                                <h6 class="text-success fw-bold"><strong>Grand Total:</strong> ${parseFloat(r.data.total_amount || 0).toFixed(2)} TK</h6>
                            </div>
                        </div>
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>SL</th>
                                    <th>Fuel Item</th>
                                    <th>Storage Tank</th>
                                    <th>Quantity</th>
                                    <th>Rate</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsHtml || '<tr><td colspan="6" class="text-center text-muted">No items found</td></tr>'}
                            </tbody>
                            <tfoot class="table-light font-weight-bold">
                                <tr>
                                    <td colspan="3" class="text-end">Total Summary:</td>
                                    <td class="text-end">${totalQty.toFixed(2)} L</td>
                                    <td></td>
                                    <td class="text-end">${parseFloat(r.data.total_amount).toFixed(2)} TK</td>
                                </tr>
                            </tfoot>
                        </table>
                        ${r.data.remarks ? `<p class="mb-0"><strong>Remarks:</strong> ${r.data.remarks}</p>` : ''}
                    `;
                    $('#viewModalBody').html(html);
                } else {
                    $('#viewModalBody').html('<div class="alert alert-danger">Failed to load invoice details!</div>');
                }
            },
            error: function() {
                $('#viewModalBody').html('<div class="alert alert-danger">Connection error!</div>');
            }
        });
    });

    // AJAX Form Submit
    $('#dataForm').on('submit', function(e) {
        e.preventDefault();
        const f = $(this);
        const btn = f.find('[type="submit"]');
        const orig = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: f.attr('action'),
            type: 'POST',
            data: f.serialize(),
            dataType: 'json',
            success: function(r) {
                if (r.success) {
                    showNotification(r.message, 'success');
                    $('#addModal').modal('hide');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification(r.message || 'Error processing request!', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error: ' + error, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html(orig);
            }
        });
    });

    // Delete Invoice
    $(document).on('click', '.delete-btn', function() {
        if (!confirmDelete()) return;
        const btn = $(this);
        const id = btn.data('id');

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '../../modules/entry/fuel_purchase_entry.php',
            type: 'POST',
            data: { action: 'delete', record_id: id },
            dataType: 'json',
            success: function(r) {
                if (r.success) {
                    showNotification(r.message, 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification(r.message || 'Delete failed!', 'error');
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>