{{-- This form is used for both creating and editing a product --}}
<form id="product-form" onsubmit="return false;" enctype="multipart/form-data">
    <input type="hidden" id="product_id" name="id">

    <div class="row">
        <div class="col-md-8">
            <div class="mb-3">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="sku" class="form-label">SKU</label>
                <input type="text" class="form-control" id="sku" name="sku">
            </div>
        </div>
    </div>
    
    {{-- More form fields... --}}
    <div class="row">
        <div class="col-md-6"><div class="mb-3"><label for="category_id" class="form-label">Category</label><select class="form-select" name="category_id" id="category_id" required></select></div></div>
        <div class="col-md-6"><div class="mb-3"><label for="unit" class="form-label">Unit</label><input type="text" class="form-control" id="unit" name="unit" placeholder="pcs"></div></div>
    </div>
    <div class="row">
        <div class="col-md-6"><div class="mb-3"><label for="purchase_price" class="form-label">Purchase Price</label><input type="number" class="form-control" id="purchase_price" name="purchase_price" step="0.01" placeholder="0.00"></div></div>
        <div class="col-md-6"><div class="mb-3"><label for="sale_price" class="form-label">Sale Price</label><input type="number" class="form-control" id="sale_price" name="sale_price" step="0.01" required placeholder="0.00"></div></div>
    </div>
    <div class="row">
        <div class="col-md-6"><div class="mb-3"><label for="stock_quantity" class="form-label">Stock Quantity</label><input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required placeholder="0"></div></div>
        <div class="col-md-6"><div class="mb-3"><label for="low_stock_threshold" class="form-label">Low Stock Threshold</label><input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" placeholder="5"></div></div>
    </div>
    <div class="mb-3"><label for="description" class="form-label">Description</label><textarea class="form-control" id="description" name="description" rows="3"></textarea></div>
    
    {{-- Image Upload Section --}}
    <div class="mb-3">
        <label for="images" class="form-label">Product Images</label>
        <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
        <div id="image_preview_container" class="d-flex flex-wrap gap-2 mt-2"></div>
        <small id="image_error" class="text-danger"></small>
    </div>

    {{-- Checkboxes --}}
    <div class="d-flex gap-3">
        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked><label class="form-check-label" for="is_active">Active</label></div>
        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1"><label class="form-check-label" for="is_featured">Featured</label></div>
    </div>
</form>