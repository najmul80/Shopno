// Filename: public/js/pages/customers.js
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

    // --- DataTable Initialization for Customers ---
    const table = $('#customers-table').DataTable({
        responsive: true,
        processing: true,
        columns: [
            { data: 'id', title: 'ID' },
            { data: 'name', title: 'Name' },
            { data: 'email', title: 'Email', defaultContent: '-' },
            { data: 'phone', title: 'Phone', defaultContent: '-' },
            { data: 'address', title: 'Address', defaultContent: '-', orderable: false },
            { 
                data: null, title: 'Actions', orderable: false, searchable: false,
                render: (data, type, row) => `
                    <button class="btn btn-sm btn-info edit-btn" data-id="${row.id}">Edit</button>
                    <button class="btn btn-sm btn-danger delete-btn ms-1" data-id="${row.id}">Delete</button>
                `
            }
        ],
        language: { emptyTable: "Loading customers..." }
    });

    // --- Function to fetch and load customers into the DataTable ---
    function loadCustomers() {
        const api = authApi();
        if (!api) return;

        api.get('customers', { params: { per_page: 500 } })
            .then(response => {
                if (response.data && Array.isArray(response.data.data)) {
                    table.clear().rows.add(response.data.data).draw();
                } else {
                    table.settings()[0].oLanguage.sEmptyTable = "No customers found.";
                    table.clear().draw();
                }
            })
            .catch(error => {
                console.error("Failed to load customers:", error);
                table.settings()[0].oLanguage.sEmptyTable = "Error: Could not load data.";
                table.clear().draw();
            });
    }

    // --- Event Handlers ---

    // 1. Open Modal for a New Customer
    $('#addNewCustomerBtn').on('click', function() {
        $('#customerModalLabel').text('Add New Customer');
        $('#customer-form')[0].reset();
        $('#customer_id').val('');
        $('#customerModal').modal('show');
    });

    // 2. Open Modal to Edit a Customer
    $('#customers-table tbody').on('click', '.edit-btn', function() {
        const customerId = $(this).data('id');
        const api = authApi();
        if(!api) return;

        api.get(`customers/${customerId}`)
            .then(response => {
                const customer = response.data.data;
                $('#customerModalLabel').text(`Edit Customer: ${customer.name}`);
                $('#customer_id').val(customer.id);
                $('#customer_name').val(customer.name);
                $('#customer_email').val(customer.email);
                $('#customer_phone').val(customer.phone);
                $('#customer_address').val(customer.address);
                $('#customerModal').modal('show');
            });
    });

    // 3. Handle Save/Update Button Click in the Modal
    $('#saveCustomerBtn').on('click', function() {
        const api = authApi();
        if (!api) return;

        const form = $('#customer-form')[0];
        const formData = new FormData(form);
        const customerId = $('#customer_id').val();
        
        let url = 'customers';
        if (customerId) {
            url = `customers/${customerId}`;
            formData.append('_method', 'PUT'); // Spoof PUT method for updates
        }
        
        api.post(url, formData)
            .then(response => {
                $('#customerModal').modal('hide');
                Swal.fire('Success!', 'Customer saved successfully!', 'success');
                loadCustomers(); // Reload the table with updated data
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    let errorHtml = '<ul class="text-start ps-3">';
                    for (const field in error.response.data.errors) {
                        errorHtml += `<li>${error.response.data.errors[field][0]}</li>`;
                    }
                    errorHtml += '</ul>';
                    Swal.fire({ title: 'Validation Failed', html: errorHtml, icon: 'error' });
                } else {
                    Swal.fire('Error', 'Could not save the customer.', 'error');
                }
            });
    });

    // 4. Handle Delete Button Click
    $('#customers-table tbody').on('click', '.delete-btn', function() {
        const customerId = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const api = authApi();
                if(!api) return;
                api.delete(`customers/${customerId}`)
                    .then(response => {
                        Swal.fire('Deleted!', 'The customer has been deleted.', 'success');
                        loadCustomers();
                    })
                    .catch(error => Swal.fire('Error', 'Could not delete the customer.', 'error'));
            }
        });
    });

    // --- Initial Data Load ---
    loadCustomers();
});