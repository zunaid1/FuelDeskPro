/**
 * FuelDeskPro - Monthly Summary LPG AJAX Logic
 * 
 * Handles report generation, table population, and financial summary updates via AJAX.
 * 
 * @package FuelDeskPro
 */

$(document).ready(function() {
    // Helper function to format numbers with commas and fixed decimals
    function numFmt(num, decimals = 2) {
        if (num === null || num === undefined || isNaN(num)) {
            num = 0;
        }
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    // Format date string from YYYY-MM-DD to DD-MM-YYYY
    function dateFmt(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            return parts[2] + '-' + parts[1] + '-' + parts[0];
        }
        return dateStr;
    }

    // Main AJAX function to load report data from monthly_summary_api.php
    function fetchMonthlySummary() {
        const monthVal = $('#month_select').val();
        const commRate = $('#commission_rate').val();
        const adjPlus  = $('#adj_plus').val();
        const adjMinus = $('#adj_minus').val();

        const btn = $('#btnGenerate');
        const origBtnText = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Loading...');
        $('#reportTbody').html(`
            <tr>
                <td colspan="10" class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin fa-2x me-2 text-primary"></i>
                    <div>Fetching monthly LPG data...</div>
                </td>
            </tr>
        `);

        $.ajax({
            url: (window.FUELDESK_BASE_URL || '/FuelDeskPro/') + 'modules/operations/monthly_summary_api.php',
            type: 'POST',
            dataType: 'json',
            data: {
                month: monthVal,
                commission_rate: commRate,
                adj_plus: adjPlus,
                adj_minus: adjMinus
            },
            success: function(response) {
                if (response.status === 'success') {
                    renderTable(response.data);
                    renderTotals(response.totals);
                    renderSummary(response.summary);

                    if (response.month_label) {
                        $('#reportMonthLabel').text(response.month_label);
                    }
                } else {
                    $('#reportTbody').html(`
                        <tr>
                            <td colspan="10" class="text-center text-danger py-3">
                                <i class="fas fa-exclamation-circle me-1"></i> ${response.message || 'Failed to load report data.'}
                            </td>
                        </tr>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#reportTbody').html(`
                    <tr>
                        <td colspan="10" class="text-center text-danger py-3">
                            <i class="fas fa-exclamation-triangle me-1"></i> Error loading report data. Please try again.
                        </td>
                    </tr>
                `);
            },
            complete: function() {
                btn.prop('disabled', false).html(origBtnText);
            }
        });
    }

    // Render data table rows (<tbody>)
    function renderTable(rows) {
        const tbody = $('#reportTbody');
        tbody.empty();

        if (!rows || rows.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="10" class="text-center text-muted py-3">No records found for the selected month.</td>
                </tr>
            `);
            return;
        }

        let html = '';
        rows.forEach(function(row) {
            html += `
                <tr>
                    <td class="text-center">${dateFmt(row.Date)}</td>
                    <td class="text-end">${numFmt(row.Opening, 3)}</td>
                    <td class="text-end fw-semibold">${numFmt(row.ConsumedLiter, 3)}</td>
                    <td class="text-end">${numFmt(row.Rate, 2)}</td>
                    <td class="text-end fw-semibold">${numFmt(row.SalesAmount, 2)}</td>
                    <td class="text-end">${numFmt(row.DueColl, 2)}</td>
                    <td class="text-end">${numFmt(row.TotalCollection, 2)}</td>
                    <td class="text-end">${numFmt(row.DueSales, 2)}</td>
                    <td class="text-end text-danger">${numFmt(row.Expense, 2)}</td>
                    <td class="text-end text-primary">${numFmt(row.BankDeposit, 2)}</td>
                </tr>
            `;
        });

        tbody.html(html);
    }

    // Render grand totals row (<tfoot>)
    function renderTotals(totals) {
        const tfoot = $('#reportTfoot');
        tfoot.empty();

        if (!totals) return;

        const html = `
            <tr class="table-dark">
                <td class="text-center fw-bold">TOTAL</td>
                <td class="text-end">-</td>
                <td class="text-end fw-bold">${numFmt(totals.total_consumed_liter, 3)}</td>
                <td class="text-end">-</td>
                <td class="text-end fw-bold">${numFmt(totals.total_sales_amount, 2)}</td>
                <td class="text-end fw-bold">${numFmt(totals.total_due_coll, 2)}</td>
                <td class="text-end fw-bold">${numFmt(totals.total_collection, 2)}</td>
                <td class="text-end fw-bold">${numFmt(totals.total_due_sales, 2)}</td>
                <td class="text-end fw-bold text-warning">${numFmt(totals.total_expense, 2)}</td>
                <td class="text-end fw-bold text-info">${numFmt(totals.total_bank_deposit, 2)}</td>
            </tr>
        `;

        tfoot.html(html);
    }

    // Render financial summary cards
    function renderSummary(summary) {
        if (!summary) return;

        $('#sumCommissionRate').text(numFmt(summary.commission_rate, 2));
        $('#sumSalesCommission').text(numFmt(summary.sales_commission, 2));
        $('#sumTotalExpense').text(numFmt(summary.total_expense, 2));
        $('#sumAdjPlus').text(numFmt(summary.adj_plus, 2));
        $('#sumAdjMinus').text(numFmt(summary.adj_minus, 2));
        
        const netProfitSpan = $('#sumNetProfit');
        netProfitSpan.text(numFmt(summary.net_profit, 2));

        if (summary.net_profit < 0) {
            netProfitSpan.removeClass('text-info text-success').addClass('text-danger');
        } else {
            netProfitSpan.removeClass('text-danger').addClass('text-info');
        }
    }

    // Event listeners
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        fetchMonthlySummary();
    });

    $('#month_select').on('change', function() {
        fetchMonthlySummary();
    });

    $('#commission_rate, #adj_plus, #adj_minus').on('change input', function() {
        // Debounce automatic refresh when typing adjustments or commission rate
        clearTimeout(window.summaryTimer);
        window.summaryTimer = setTimeout(fetchMonthlySummary, 400);
    });

    // Initial load on page ready
    fetchMonthlySummary();
});
