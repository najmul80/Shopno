// Filename: public/js/pages/sales_history.js
$(document).ready(function() {

    function authApi() {
        const token = localStorage.getItem('access_token');
        if (!token) {
            window.location.href = '/login';
            return null;
        }
        return axios.create({
            baseURL: '/api/v1/',
            headers: { 'Authorization': `Bearer ${token}` }
        });
    }

    // --- DataTable Initialization for Sales History ---
    const table = $('#sales-table').DataTable({
        responsive: true,
        processing: true,
        ajax: {
            url: "/api/v1/sales", // Your sales list API endpoint
            type: "GET",
            beforeSend: function (xhr) {
                xhr.setRequestHeader('Authorization', `Bearer ${localStorage.getItem('access_token')}`);
            }
        },
        columns: [
            { data: 'invoice_number', title: 'Invoice ID' },
            { data: 'customer.name', title: 'Customer', defaultContent: 'Walk-in Customer' },
            { data: 'total_amount', title: 'Total Amount', render: data => `$${parseFloat(data).toFixed(2)}` },
            { data: 'sale_date', title: 'Date' },
            { data: 'status', title: 'Status', render: data => `<span class="badge bg-success">${data}</span>` },
            { 
                data: 'id', // Pass the sale ID to the button
                title: 'Actions', 
                orderable: false, 
                searchable: false,
                render: (data, type, row) => `
                    <button class="btn btn-sm btn-primary view-invoice-btn" data-id="${data}">View Invoice</button>
                `
            }
        ]
    });
loadSales()
    // --- Event Handler for "View Invoice" button ---
    $('#sales-table tbody').on('click', '.view-invoice-btn', function() {
        const saleId = $(this).data('id');
        const api = authApi();
        if(!api) return;

        // Fetch detailed invoice data
        api.get(`invoices/${saleId}/json`)
            .then(response => {
                const invoice = response.data.data;
                const invoiceContent = $('#invoice-content');
                
                // Build the invoice HTML
                let itemsHtml = '';
                invoice.items.forEach(item => {
                    itemsHtml += `
                        <tr class="item">
                            <td>${item.product_name}</td>
                            <td>${item.quantity}</td>
                            <td>$${parseFloat(item.unit_price).toFixed(2)}</td>
                            <td>$${parseFloat(item.sub_total).toFixed(2)}</td>
                        </tr>
                    `;
                });

                const fullInvoiceHtml = `
                    <table cellpadding="0" cellspacing="0">
                        <tr class="top">
                            <td colspan="4">
                                <table>
                                    <tr>
                                        <td class="title"><h2>Invoice</h2></td>
                                        <td>
                                            Invoice #: ${invoice.invoice_number}<br>
                                            Created: ${invoice.sale_date}<br>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr class="information">
                            <td colspan="4">
                                <table>
                                    <tr>
                                        <td>
                                            <strong>Billed To:</strong><br>
                                            ${invoice.customer.name}<br>
                                            ${invoice.customer.email || ''}<br>
                                            ${invoice.customer.phone || ''}
                                        </td>
                                        <td>
                                            <strong>From:</strong><br>
                                            ${invoice.store.name}<br>
                                            ${invoice.store.email || ''}<br>
                                            ${invoice.store.address_line1 || ''}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr class="heading">
                            <td>Item</td>
                            <td>Qty</td>
                            <td>Price</td>
                            <td>Subtotal</td>
                        </tr>
                        ${itemsHtml}
                        <tr class="total">
                            <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                            <td style="font-weight: bold;">$${parseFloat(invoice.total_amount).toFixed(2)}</td>
                        </tr>
                    </table>
                `;

                invoiceContent.html(fullInvoiceHtml);
                $('#invoiceModal').modal('show');

            }).catch(error => {
                console.error("Failed to load invoice details:", error);
                Swal.fire('Error', 'Could not load invoice details.', 'error');
            });
    });

    // --- Print Invoice Logic ---
    $('#printInvoiceBtn').on('click', function() {
        const invoiceHtml = document.getElementById('invoice-content').innerHTML;
        const printWindow = window.open('', '_blank', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Print Invoice</title>');
        // Optional: Add some basic styling for printing
        printWindow.document.write('<style>body { font-family: sans-serif; } table { width: 100%; border-collapse: collapse; } td, th { border: 1px solid #ddd; padding: 8px; } .invoice-box { width: 100%; margin: auto; padding: 20px; } </style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write(invoiceHtml);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus(); // required for IE
        printWindow.print();
        // printWindow.close(); // You might want to close it automatically after printing
    });

    
});