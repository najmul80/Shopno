// Filename: public/js/pages/reports/sales_history_report.js
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

    // Initialize an empty DataTable
    const table = $('#sales-report-table').DataTable({
        responsive: true,
        columns: [
            { data: 'invoice_number', title: 'Invoice ID' },
            { data: 'sale_date', title: 'Date' },
            { data: 'customer.name', title: 'Customer', defaultContent: 'N/A' },
            { data: 'total_amount', title: 'Total Amount', render: data => `$${parseFloat(data).toFixed(2)}` },
            { data: 'status', title: 'Status', render: data => `<span class="badge bg-success">${data}</span>` }
        ],
        dom: 'Bfrtip', // Add Buttons (B) to the DOM
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        language: { emptyTable: "Please select a date range and click 'Generate Report'." }
    });

    // Handle form submission
    $('#report-filter-form').on('submit', function(e) {
        e.preventDefault();

        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();

        if (!startDate || !endDate) {
            Swal.fire('Missing Dates', 'Please select both a start and end date.', 'warning');
            return;
        }

        const api = authApi();
        if (!api) return;

        // Show processing indicator
        table.processing(true);
        table.settings()[0].oLanguage.sEmptyTable = "Loading report data...";
        table.clear().draw();

        // Fetch report data from the API
        api.get('reports/sales-history', {
            params: {
                start_date: startDate,
                end_date: endDate
            }
        })
        .then(response => {
            if (response.data && Array.isArray(response.data.data)) {
                table.clear().rows.add(response.data.data).draw();
            } else {
                table.settings()[0].oLanguage.sEmptyTable = "No sales found for the selected period.";
                table.clear().draw();
            }
        })
        .catch(error => {
            console.error("Failed to load sales report:", error);
            table.settings()[0].oLanguage.sEmptyTable = "Error: Could not load the report.";
            table.clear().draw();
            Swal.fire('Error', 'Could not generate the sales report.', 'error');
        })
        .finally(() => {
            // Hide processing indicator
            table.processing(false);
        });
    });

    // Set default dates for user convenience (e.g., this month)
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    $('#start_date').val(firstDayOfMonth.toISOString().split('T')[0]);
    $('#end_date').val(today.toISOString().split('T')[0]);

});