// Filename: public/js/pages/dashboard.js
$(document).ready(function() {

    // Helper function to create an authenticated Axios instance
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

    // --- Function to load summary card data ---
    function loadSummaryData() {
        const api = authApi();
        if (!api) return;

        api.get('dashboard/summary')
            .then(response => {
                const summary = response.data.data;
                $('#summary-todays-revenue').text(`$${parseFloat(summary.todays_sales || 0).toFixed(2)}`);
                $('#summary-total-products').text(summary.total_products || 0);
                $('#summary-total-customers').text(summary.total_customers || 0);
            })
            .catch(error => {
                console.error("Failed to load summary data:", error);
            });
    }

    // --- Function to load and render the sales trend chart ---
    function loadSalesChart() {
        const api = authApi();
        if (!api) return;

        api.get('dashboard/sales-trends') // Your API endpoint for sales trends
            .then(response => {
                const trends = response.data.data;
                const chartData = {
                    series: [{
                        name: 'Sales',
                        data: trends.map(item => item.total_sales)
                    }],
                    categories: trends.map(item => item.date)
                };
                renderChart(chartData);
            })
            .catch(error => {
                console.error("Failed to load sales chart data:", error);
            });
    }
    
    // --- Function to render the ApexChart ---
    function renderChart(chartData) {
        const options = {
            chart: {
                height: 350,
                type: 'line',
                zoom: { enabled: false },
                toolbar: { show: false }
            },
            series: chartData.series,
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            xaxis: {
                categories: chartData.categories,
                type: 'datetime'
            },
            yaxis: {
                title: { text: 'Revenue ($)' },
                labels: {
                    formatter: function (value) {
                        return "$" + value.toFixed(2);
                    }
                },
            },
            tooltip: {
                x: { format: 'dd MMM yyyy' },
                y: {
                    formatter: function (value) {
                        return "$" + value.toFixed(2);
                    }
                }
            },
            colors: ['#556ee6'],
            grid: {
                borderColor: '#f1f1f1',
            }
        };

        const chart = new ApexCharts(document.querySelector("#sales-analytics-chart"), options);
        chart.render();
    }
    
    // --- Function to load latest sales ---
    function loadLatestSales() {
        const api = authApi();
        if (!api) return;

        // Fetch the last 5 sales
        api.get('sales', { params: { per_page: 5, sort_by: 'latest' } })
            .then(response => {
                const sales = response.data.data;
                const listContainer = $('#latest-sales-list');
                listContainer.empty();
                
                if (sales && sales.length > 0) {
                    sales.forEach(sale => {
                        const saleHtml = `
                            <tr>
                                <td>
                                    <h5 class="font-size-14 mb-1">${sale.customer.name || 'Walk-in Customer'}</h5>
                                    <p class="text-muted mb-0">${sale.invoice_number}</p>
                                </td>
                                <td>
                                    <h5 class="font-size-14 mb-0 text-end">$${parseFloat(sale.total_amount).toFixed(2)}</h5>
                                </td>
                            </tr>
                        `;
                        listContainer.append(saleHtml);
                    });
                } else {
                    listContainer.html('<tr><td colspan="2" class="text-center">No recent sales found.</td></tr>');
                }
            })
            .catch(error => {
                console.error("Failed to load latest sales:", error);
            });
    }


    // --- Initial Load ---
    loadSummaryData();
    loadSalesChart();
    loadLatestSales();
});