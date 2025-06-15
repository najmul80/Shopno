<div>
    <div class="alert alert-info">
        Manage different versions of this product, like sizes or colors. Each variant can have its own SKU, price, and stock level.
    </div>
    <h5>Existing Variants</h5>
    <div id="variant-list-container" class="mb-4">
        {{-- List of existing variants will be loaded here --}}
        <p>No variants found.</p>
    </div>
    <hr>
    <h5>Add / Edit Variant</h5>
    <form id="variant-form" class="p-3 border rounded bg-light" onsubmit="return false;">
        <input type="hidden" id="variant_id" name="id">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="variant_name_suffix" class="form-label">Variant Name (e.g., "Large", "Red")</label>
                <input type="text" id="variant_name_suffix" name="name_suffix" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label for="variant_sku" class="form-label">Variant SKU</label>
                <input type="text" id="variant_sku" name="sku" class="form-control">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="variant_sale_price" class="form-label">Sale Price (Set a specific price for this variant)</label>
                <input type="number" id="variant_sale_price" name="sale_price" class="form-control" step="0.01">
            </div>
            <div class="col-md-6 mb-3">
                <label for="variant_stock_quantity" class="form-label">Stock Quantity</label>
                <input type="number" id="variant_stock_quantity" name="stock_quantity" class="form-control">
            </div>
        </div>
        {{-- You can add more fields like purchase price, image, etc. here --}}
        <button type="button" class="btn btn-primary" id="saveVariantBtn">Save Variant</button>
        <button type="button" class="btn btn-secondary" id="clearVariantFormBtn">Clear Form</button>
    </form>
</div>