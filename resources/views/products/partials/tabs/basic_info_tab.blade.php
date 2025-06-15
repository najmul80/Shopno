<form id="product-basic-info-form" onsubmit="return false;">
    {{-- This hidden input will hold the ID of the product being edited --}}
    <input type="hidden" id="product_id" name="id">

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="name" class="form-label">Product Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="category_id" class="form-label">Category</label>
            <select class="form-select" name="category_id" id="category_id" required>
                {{-- Options will be loaded by JavaScript --}}
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="sku" class="form-label">SKU (Stock Keeping Unit)</label>
            <input type="text" class="form-control" id="sku" name="sku">
        </div>
        <div class="col-md-6 mb-3">
            <label for="unit" class="form-label">Unit (e.g., pcs, kg, ltr)</label>
            <input type="text" class="form-control" id="unit" name="unit" placeholder="pcs">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="purchase_price" class="form-label">Purchase Price</label>
            <input type="number" class="form-control" id="purchase_price" name="purchase_price" step="0.01" placeholder="0.00">
        </div>
        <div class="col-md-6 mb-3">
            <label for="sale_price" class="form-label">Sale Price</label>
            <input type="number" class="form-control" id="sale_price" name="sale_price" step="0.01" required placeholder="0.00">
        </div>
    </div>
     <div class="row">
        <div class="col-md-6 mb-3">
            <label for="stock_quantity" class="form-label">Initial Stock Quantity</label>
            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" placeholder="0">
        </div>
        <div class="col-md-6 mb-3">
            <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
            <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" placeholder="5">
        </div>
    </div>
    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
    </div>
    <div class="d-flex gap-3">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
            <label class="form-check-label" for="is_active">Product is Active</label>
        </div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured">
            <label class="form-check-label" for="is_featured">Featured Product</label>
        </div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="has_variants" name="has_variants">
            <label class="form-check-label" for="has_variants">This product has variants</label>
        </div>
    </div>
</form>