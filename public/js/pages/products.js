$(document).ready(function() {

    // Helper function to create an authenticated Axios instance.
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

    // This is the correct path to the default image, accessed from the public folder.
    const defaultImage = '/images/default-product.png';

    // Initialize DataTable
    const table = $('#products-table').DataTable({
        responsive: true,
        processing: true,
        // The columns array defines what data to show in each column.
        columns: [
            { data: 'id', title: 'ID' },
            {
                data: 'primary_image_url',
                title: 'Image',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    // Use the image from the API, or the default if it's missing.
                    const imgSrc = data || defaultImage;
                    // The 'onerror' attribute is a failsafe for broken image links.
                    return `<img 
                                src="${imgSrc}" 
                                alt="${row.name}" 
                                class="product-image-thumb"
                                onerror="this.onerror=null;this.src='${defaultImage}';"
                            >`;
                }
            },
            { data: 'name', title: 'Name' },
            { data: 'category.name', title: 'Category', defaultContent: 'N/A' },
            { data: 'sale_price', title: 'Price', render: data => `$${parseFloat(data || 0).toFixed(2)}` },
            { data: 'stock_quantity', title: 'Stock', defaultContent: '0' },
            { data: 'is_active', title: 'Status', render: data => data ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' },
            {
                data: null, title: 'Actions', orderable: false, searchable: false,
                render: (data, type, row) => `<button class="btn btn-sm btn-info edit-btn" data-id="${row.id}">Edit</button>
                                              <button class="btn btn-sm btn-danger delete-btn ms-1" data-id="${row.id}">Delete</button>`
            }
        ],
        language: {
            emptyTable: "Loading products..."
        }
    });

    // Function to load products into the table
    function loadProducts() {
        const api = authApi();
        if (!api) return;

        api.get('products', { params: { per_page: 500 } })
            .then(response => {
                // The product array is inside response.data.data as per your API structure.
                if (response.data && Array.isArray(response.data.data)) {
                    table.clear().rows.add(response.data.data).draw();
                } else {
                    table.settings()[0].oLanguage.sEmptyTable = "No products found.";
                    table.clear().draw();
                }
            })
            .catch(error => {
                console.error("Could not fetch products:", error);
                table.settings()[0].oLanguage.sEmptyTable = "Error: Failed to load data.";
                table.clear().draw();
            });
    }

    // Function to load categories into the dropdown
    function loadCategories(selectedId = null) {
        const api = authApi();
        if (!api) return;
        api.get('categories', { params: { per_page: 500 } }).then(response => {
            let options = '<option value="">Select Category</option>';
            if(response.data && response.data.data) {
                response.data.data.forEach(cat => {
                    options += `<option value="${cat.id}" ${cat.id == selectedId ? 'selected' : ''}>${cat.name}</option>`;
                });
            }
            $('#category_id').html(options);
        });
    }
    
    // --- Event Handlers ---
    
    // Add new product button click
    $('#addNewProductBtn').on('click', function() {
        $('#product-form')[0].reset();
        $('#product_id').val('');
        $('#productModalLabel').text('Add New Product');
        $('#image_preview_container').empty();
        $('#is_active').prop('checked', true);
        loadCategories();
        $('#productModal').modal('show');
    });

    // Edit product button click
    $('#products-table tbody').on('click', '.edit-btn', function() {
        const productId = $(this).data('id');
        const api = authApi();
        if (!api) return;
        api.get(`products/${productId}`).then(response => {
            const product = response.data.data;
            $('#productModalLabel').text(`Edit Product: ${product.name}`);
            $('#product_id').val(product.id);
            $('#name').val(product.name);
            $('#sku').val(product.sku);
            $('#sale_price').val(product.sale_price);
            $('#purchase_price').val(product.purchase_price);
            $('#stock_quantity').val(product.stock_quantity);
            $('#low_stock_threshold').val(product.low_stock_threshold);
            $('#unit').val(product.unit);
            $('#description').val(product.description);
            $('#is_active').prop('checked', product.is_active);
            $('#is_featured').prop('checked', product.is_featured);
            loadCategories(product.category?.id);
            // Clear old previews and show current image
            $('#image_preview_container').html(`<img src="${product.primary_image_url}" class="img-thumbnail me-2" style="width: 100px; height: auto;" alt="Current Image" />`);
            $('#productModal').modal('show');
        });
    });

    // Image input change event
    $('#images').on('change', function() {
        $('#image_preview_container').empty();
        const files = this.files;
        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = e => $('#image_preview_container').append(`<img src="${e.target.result}" class="img-thumbnail me-2" style="width: 100px; height: auto;"/>`);
            reader.readAsDataURL(file);
        });
    });

    // Save/Update product
    $('#saveProductBtn').on('click', function() {
        const api = authApi();
        if (!api) return;
        const form = $('#product-form')[0];
        const formData = new FormData(form);
        const productId = $('#product_id').val();
        if (!$('#is_active').is(':checked')) formData.set('is_active', 0);
        if (!$('#is_featured').is(':checked')) formData.set('is_featured', 0);

        let url = 'products';
        if (productId) {
            url = `products/${productId}`;
            formData.append('_method', 'PUT');
        }

        api.post(url, formData).then(response => {
            $('#productModal').modal('hide');
            Swal.fire('Success!', 'Product saved successfully!', 'success');
            loadProducts();
        }).catch(error => {
            if (error.response && error.response.status === 422) {
                let errorHtml = '<ul class="text-start ps-3">';
                for (const field in error.response.data.errors) {
                    errorHtml += `<li>${error.response.data.errors[field][0]}</li>`;
                }
                errorHtml += '</ul>';
                Swal.fire({ title: 'Validation Failed', html: errorHtml, icon: 'error' });
            } else {
                Swal.fire('Error', 'Could not save the product.', 'error');
            }
        });
    });

    // Delete product
    $('#products-table tbody').on('click', '.delete-btn', function() {
        const productId = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?', icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete it!'
        }).then(result => {
            if (result.isConfirmed) {
                const api = authApi();
                if (!api) return;
                api.delete(`products/${productId}`).then(() => {
                    Swal.fire('Deleted!', 'Product has been deleted.', 'success');
                    loadProducts();
                }).catch(() => Swal.fire('Error', 'Could not delete product.', 'error'));
            }
        });
    });

    // Initial data load
    loadProducts();
});