// Filename: public/js/pages/pos.js
$(document).ready(function() {

    let allProducts = []; // To store all products fetched from API
    let cart = []; // To store items added to the cart

    // --- Helper to get an authenticated Axios instance ---
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

    // --- Initial Data Loading ---

    function loadInitialData() {
        const api = authApi();
        if (!api) return;

        // Fetch all necessary data in parallel
        Promise.all([
            api.get('products', { params: { per_page: 1000 } }),
            api.get('categories', { params: { per_page: 500 } }),
            api.get('customers', { params: { per_page: 1000 } })
        ]).then(([productsRes, categoriesRes, customersRes]) => {
            // 1. Handle Products
            allProducts = productsRes.data.data;
            renderProducts(allProducts);

            // 2. Handle Categories
            const categorySelect = $('#category-filter-select');
            categoriesRes.data.data.forEach(cat => {
                categorySelect.append(`<option value="${cat.id}">${cat.name}</option>`);
            });

            // 3. Handle Customers
            const customerSelect = $('#customer-select');
            customerSelect.append(`<option value="">Walk-in Customer</option>`);
            customersRes.data.data.forEach(cust => {
                customerSelect.append(`<option value="${cust.id}">${cust.name} - ${cust.phone || 'N/A'}</option>`);
            });

        }).catch(error => {
            console.error("Failed to load initial POS data:", error);
            Swal.fire('Error', 'Could not load required data. Please refresh the page.', 'error');
        });
    }

    // --- Rendering and UI Functions ---

    function renderProducts(productsToRender) {
        const grid = $('#product-grid');
        grid.empty();

        if (productsToRender.length === 0) {
            grid.html('<div class="col-12"><p class="text-center text-muted">No products found.</p></div>');
            return;
        }

        productsToRender.forEach(product => {
            const productHtml = `
                <div class="col-md-4 col-lg-3">
                    <div class="card product-card" data-product-id="${product.id}">
                        <img src="${product.primary_image_url}" class="card-img-top product-card-img" alt="${product.name}">
                        <div class="card-body text-center p-2">
                            <h6 class="card-title font-size-14 mb-1 text-truncate">${product.name}</h6>
                            <p class="card-text fw-bold">$${parseFloat(product.sale_price).toFixed(2)}</p>
                        </div>
                    </div>
                </div>
            `;
            grid.append(productHtml);
        });
    }

    function renderCart() {
        const cartBody = $('#cart-items-body');
        cartBody.empty();
        let subtotal = 0;

        if (cart.length === 0) {
            cartBody.html('<tr><td colspan="5" class="text-center">Cart is empty</td></tr>');
        } else {
            cart.forEach((item, index) => {
                const itemTotal = item.quantity * item.price;
                subtotal += itemTotal;
                const cartItemHtml = `
                    <tr>
                        <td>${item.name}</td>
                        <td>
                            <input type="number" value="${item.quantity}" min="1" class="form-control form-control-sm cart-item-qty-input" data-cart-index="${index}">
                        </td>
                        <td>$${parseFloat(item.price).toFixed(2)}</td>
                        <td>$${itemTotal.toFixed(2)}</td>
                        <td>
                            <button class="btn btn-sm btn-danger remove-cart-item-btn" data-cart-index="${index}">
                                <i class="bx bx-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                cartBody.append(cartItemHtml);
            });
        }

        // Update totals
        $('#cart-subtotal').text(`$${subtotal.toFixed(2)}`);
        // You can add logic for discount later
        $('#cart-total').text(`$${subtotal.toFixed(2)}`);
    }

    // --- Cart Logic Functions ---

    function addToCart(productId) {
        const product = allProducts.find(p => p.id === productId);
        if (!product) return;

        // Check if item already in cart
        const cartItem = cart.find(item => item.product_id === productId);
        if (cartItem) {
            cartItem.quantity++;
        } else {
            cart.push({
                product_id: product.id,
                name: product.name,
                quantity: 1,
                price: parseFloat(product.sale_price)
            });
        }
        renderCart();
    }
    
    function updateCartQuantity(cartIndex, newQuantity) {
        if (cart[cartIndex]) {
            cart[cartIndex].quantity = parseInt(newQuantity, 10) || 1;
        }
        renderCart();
    }

    function removeFromCart(cartIndex) {
        cart.splice(cartIndex, 1);
        renderCart();
    }
    
    function clearCartAndSale() {
        cart = [];
        $('#customer-select').val('').trigger('change'); // Assuming you use select2, otherwise just .val('')
        renderCart();
    }


    // --- Event Handlers ---

    // Add product to cart when card is clicked
    $('#product-grid').on('click', '.product-card', function() {
        const productId = $(this).data('product-id');
        addToCart(productId);
    });

    // Handle search and filtering
    $('#product-search-input, #category-filter-select').on('keyup change', function() {
        const searchTerm = $('#product-search-input').val().toLowerCase();
        const categoryId = $('#category-filter-select').val();

        const filteredProducts = allProducts.filter(p => {
            const matchesCategory = !categoryId || p.category.id == categoryId;
            const matchesSearch = !searchTerm || p.name.toLowerCase().includes(searchTerm) || p.sku?.toLowerCase().includes(searchTerm);
            return matchesCategory && matchesSearch;
        });
        renderProducts(filteredProducts);
    });
    
    // Update quantity in cart
    $('#cart-items-body').on('change', '.cart-item-qty-input', function() {
        const cartIndex = $(this).data('cart-index');
        const newQuantity = $(this).val();
        updateCartQuantity(cartIndex, newQuantity);
    });
    
    // Remove item from cart
    $('#cart-items-body').on('click', '.remove-cart-item-btn', function() {
        const cartIndex = $(this).data('cart-index');
        removeFromCart(cartIndex);
    });

    // Cancel the entire sale
    $('#cancel-sale-btn').on('click', function() {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will clear the entire cart.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, clear it!'
        }).then((result) => {
            if (result.isConfirmed) {
                clearCartAndSale();
            }
        });
    });

    // Process the payment and complete the sale
    $('#process-payment-btn').on('click', function() {
        if (cart.length === 0) {
            Swal.fire('Empty Cart', 'Please add products to the cart first.', 'info');
            return;
        }

        const customerId = $('#customer-select').val();
        if (!customerId) {
            Swal.fire('No Customer', 'Please select a customer or use "Walk-in Customer".', 'info');
            // Depending on your backend, you might allow null customer_id.
            // For this example, we proceed but you might want to stop here.
        }
        
        const totalAmount = parseFloat($('#cart-total').text().replace('$', ''));
        const saleData = {
            customer_id: customerId || null,
            total_amount: totalAmount,
            // You can add more fields like discount, tax, payment_method here
            items: cart.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
                unit_price: item.price
            }))
        };

        const api = authApi();
        if (!api) return;

        api.post('sales', saleData)
            .then(response => {
                Swal.fire('Success!', 'Sale completed successfully!', 'success');
                clearCartAndSale();
            })
            .catch(error => {
                console.error("Sale processing failed:", error.response);
                Swal.fire('Error', 'Could not process the sale.', 'error');
            });
    });


    // --- Initial Load ---
    loadInitialData();
});