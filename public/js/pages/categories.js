// Filename: public/js/pages/categories.js
$(document).ready(function() {

    // Helper function to create an authenticated Axios instance with the JWT token
    function authApi() {
        const token = localStorage.getItem('access_token');
        if (!token) {
            // If no token, redirect the user to the login page
            window.location.href = '/login';
            return null;
        }
        return axios.create({
            baseURL: '/api/v1/',
            headers: { 'Authorization': `Bearer ${token}` }
        });
    }

    // --- DataTable Initialization for Categories ---
    const table = $('#categories-table').DataTable({
        responsive: true,
        processing: true,
        // Define the table columns and how to render data for each
        columns: [
            { data: 'id', title: 'ID' },
            { 
                data: 'image_url', 
                title: 'Image',
                render: (data, type, row) => `<img src="${data}" alt="${row.name}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">`,
                orderable: false, 
                searchable: false
            },
            { data: 'name', title: 'Name' },
            { data: 'description', title: 'Description', defaultContent: '-' },
            { 
                data: 'is_active', 
                title: 'Status',
                render: data => data ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'
            },
            { 
                data: null, 
                title: 'Actions', 
                orderable: false, 
                searchable: false,
                render: (data, type, row) => `
                    <button class="btn btn-sm btn-info edit-btn" data-id="${row.id}">Edit</button>
                    <button class="btn btn-sm btn-danger delete-btn ms-1" data-id="${row.id}">Delete</button>
                `
            }
        ],
        language: { emptyTable: "Loading categories..." }
    });

    // --- Function to fetch all categories and load them into the DataTable ---
    function loadCategories() {
        const api = authApi();
        if (!api) return;

        api.get('categories', { params: { per_page: 500 } }) // Fetch all categories
            .then(response => {
                // The API response for categories is paginated, so the array is in response.data.data
                if (response.data && Array.isArray(response.data.data)) {
                    table.clear().rows.add(response.data.data).draw();
                } else {
                    // This handles cases where the API might return a different structure
                    table.settings()[0].oLanguage.sEmptyTable = "No categories found.";
                    table.clear().draw();
                }
            })
            .catch(error => {
                console.error("Failed to load categories:", error);
                table.settings()[0].oLanguage.sEmptyTable = "Error: Could not load data.";
                table.clear().draw();
            });
    }

    // --- Event Handlers ---

    // 1. Open Modal for a New Category
    $('#addNewCategoryBtn').on('click', function() {
        $('#categoryModalLabel').text('Add New Category');
        $('#category-form')[0].reset();
        $('#category_id').val('');
        $('#category_is_active').prop('checked', true); // Default to active
        $('#categoryModal').modal('show');
    });

    // 2. Open Modal to Edit a Category
    $('#categories-table tbody').on('click', '.edit-btn', function() {
        const categoryId = $(this).data('id');
        const api = authApi();
        if(!api) return;

        api.get(`categories/${categoryId}`)
            .then(response => {
                const category = response.data.data;
                $('#categoryModalLabel').text(`Edit Category: ${category.name}`);
                $('#category_id').val(category.id);
                $('#category_name').val(category.name);
                $('#category_description').val(category.description);
                $('#category_is_active').prop('checked', category.is_active);
                // Clear the file input as we don't want to resubmit the old file path
                $('#category_image').val(''); 
                $('#categoryModal').modal('show');
            });
    });

    // 3. Handle Save/Update Button Click in the Modal
    $('#saveCategoryBtn').on('click', function() {
        const api = authApi();
        if (!api) return;

        const form = $('#category-form')[0];
        const formData = new FormData(form);
        const categoryId = $('#category_id').val();
        
        // Ensure unchecked 'is_active' checkbox sends a value of 0
        if (!$('#category_is_active').is(':checked')) {
            formData.set('is_active', 0);
        }

        let url = 'categories';
        // For updates, we use POST but tell Laravel to treat it as a PUT request
        if (categoryId) {
            url = `categories/${categoryId}`;
            formData.append('_method', 'PUT');
        }
        
        // Always use 'post' when sending FormData
        api.post(url, formData)
            .then(response => {
                $('#categoryModal').modal('hide');
                Swal.fire('Success!', 'Category saved successfully!', 'success');
                loadCategories(); // Reload the table with updated data
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
                    Swal.fire('Error', 'Could not save the category.', 'error');
                }
            });
    });

    // 4. Handle Delete Button Click
    $('#categories-table tbody').on('click', '.delete-btn', function() {
        const categoryId = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "Deleting this category might affect associated products!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const api = authApi();
                if(!api) return;
                api.delete(`categories/${categoryId}`)
                    .then(response => {
                        Swal.fire('Deleted!', 'The category has been deleted.', 'success');
                        loadCategories();
                    })
                    .catch(error => Swal.fire('Error', 'Could not delete the category.', 'error'));
            }
        });
    });

    // --- Initial Data Load ---
    loadCategories();
});