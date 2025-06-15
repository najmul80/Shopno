// Filename: public/js/pages/sales.js
$(document).ready(function() {

    // --- First, get the asset path and store it in a JavaScript variable ---
    // This is done once when the page loads.
    const bootstrapCssPath = "{{ asset('assets/css/bootstrap.min.css') }}";
    // NOTE: Since this is a .js file, we can't use Blade directly.
    // The path needs to be made available differently. See fix below.

    // Let's fix this properly.

    // In your sales/index.blade.php, add this script tag BEFORE including sales.js
    // <script>
    //     window.ASSET_PATHS = {
    //         bootstrap_css: "{{ asset('assets/css/bootstrap.min.css') }}"
    //     };
    // </script>
    // <script src="{{ asset('js/pages/sales.js') }}"></script>
    // This is the best practice. I will provide the code for blade file again.

    function authApi() {
        const token = localStorage.getItem('access_token');
        if (!token) { window.location.href = '/login'; return null; }
        return axios.create({ baseURL: '/api/v1/', headers: { 'Authorization': `Bearer ${token}` } });
    }

    const table = $('#sales-table').DataTable({
        // ... (your existing DataTable configuration is correct) ...
        responsive: true,
        processing: true,
        columns: [
            { data: 'invoice_id', title: 'Invoice ID' },
            { data: 'customer_name', title: 'Customer', defaultContent: 'Walk-in Customer' },
            { data: 'total_amount', title: 'Total Amount', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
            { data: 'paid_amount', title: 'Paid Amount', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
            { data: 'due_amount', title: 'Due', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
            { data: 'sale_date', title: 'Date' },
            { data: null, title: 'Actions', orderable: false, searchable: false,
                render: (data, type, row) => `<button class="btn btn-sm btn-primary view-invoice-btn" data-id="${row.id}">View Invoice</button>`
            }
        ],
        language: { emptyTable: "Loading sales history..." },
        order: [[0, 'desc']]
    });

    function loadSales() {
        const api = authApi();
        if (!api) return;
        api.get('sales', { params: { per_page: 500 } }).then(response => {
            if (response.data && Array.isArray(response.data.data)) {
                table.clear().rows.add(response.data.data).draw();
            }
        }).catch(error => {
            console.error("Failed to load sales history:", error);
            table.settings()[0].oLanguage.sEmptyTable = "Error loading data.";
            table.clear().draw();
        });
    }

    // --- Event Handler for "View Invoice" button ---
    $('#sales-table tbody').on('click', '.view-invoice-btn', function() {
        const saleId = $(this).data('id');
        const api = authApi();
        if (!api) return;

        $('#invoiceModalBody').html('<p class="text-center">Loading invoice...</p>');
        $('#invoiceModal').modal('show');
        
        api.get(`invoices/${saleId}/json`).then(response => {
            const invoice = response.data.data;
            let itemsHtml = '';
            invoice.items.forEach(item => {
                itemsHtml += `
                    <tr>
                        <td>${item.product_name}</td>
                        <td class="text-center">${item.quantity}</td>
                        <td class="text-end">${parseFloat(item.unit_price).toFixed(2)}</td>
                        <td class="text-end">${parseFloat(item.total_price).toFixed(2)}</td>
                    </tr>
                `;
            });

            const invoiceHtml = `
                <h5>Invoice: #${invoice.invoice_id}</h5>
                <p><strong>Date:</strong> ${invoice.sale_date}</p>
                <p><strong>Customer:</strong> ${invoice.customer.name || 'Walk-in Customer'}</p>
                <hr>
                <table class="table table-sm">
                    <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr></thead>
                    <tbody>${itemsHtml}</tbody>
                    <tfoot>
                        <tr><td colspan="3" class="text-end"><strong>Subtotal:</strong></td><td class="text-end">${parseFloat(invoice.sub_total).toFixed(2)}</td></tr>
                        <tr><td colspan="3" class="text-end"><strong>Discount:</strong></td><td class="text-end">${parseFloat(invoice.total_discount).toFixed(2)}</td></tr>
                        <tr><td colspan="3" class="text-end"><strong>Total:</strong></td><td class="text-end"><strong>$${parseFloat(invoice.total_amount).toFixed(2)}</strong></td></tr>
                        <tr><td colspan="3" class="text-end"><strong>Paid:</strong></td><td class="text-end">$${parseFloat(invoice.paid_amount).toFixed(2)}</td></tr>
                        <tr><td colspan="3" class="text-end"><strong>Due:</strong></td><td class="text-end">$${parseFloat(invoice.due_amount).toFixed(2)}</td></tr>
                    </tfoot>
                </table>
            `;
            $('#invoiceModalBody').html(invoiceHtml);
            
            // --- THIS IS THE CORRECTED PRINT LOGIC ---
            $('#printInvoiceBtn').off('click').on('click', function() {
                const printContent = document.getElementById('invoiceModalBody').innerHTML;
                // Get the CSS path from the global variable we set in the Blade file
                const bootstrapCss = window.ASSET_PATHS.bootstrap_css;

                const printWindow = window.open('', 'Print-Window', 'height=600,width=800');
                
                printWindow.document.write('<html><head><title>Print Invoice</title>');
                // Write the link tag with the correct path
                printWindow.document.write(`<link href="${bootstrapCss}" rel="stylesheet">`);
                printWindow.document.write('<style>body { padding: 20px; }</style>'); // Add some padding
                printWindow.document.write('</head><body>');
                printWindow.document.write(printContent);
                printWindow.document.write('</body></html>');
                
                printWindow.document.close();
                // Use a timeout to ensure CSS loads before printing
                setTimeout(() => {
                    printWindow.focus();
                    printWindow.print();
                    printWindow.close();
                }, 500);
            });

        }).catch(error => {
            console.error("Failed to load invoice:", error);
            $('#invoiceModalBody').html('<p class="text-center text-danger">Could not load invoice details.</p>');
        });
    });

    // --- Initial Load ---
    loadSales();
});