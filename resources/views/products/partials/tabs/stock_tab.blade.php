<form id="stock-adjustment-form" onsubmit="return false;">
    <p>Adjust stock for the main product or a specific variant.</p>
    <div class="mb-3">
        <label for="stock_variant_select" class="form-label">Product / Variant</label>
        <select id="stock_variant_select" name="variant_id" class="form-select">
            {{-- This will be populated by JS --}}
        </select>
    </div>
    <div class="mb-3">
        <label for="stock_adjustment_type" class="form-label">Adjustment Type</label>
        <select id="stock_adjustment_type" name="type" class="form-select">
            <option value="addition">Addition (+)</option>
            <option value="subtraction">Subtraction (-)</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="stock_adjustment_quantity" class="form-label">Quantity</label>
        <input type="number" id="stock_adjustment_quantity" name="quantity" class="form-control" required min="1">
    </div>
    <div class="mb-3">
        <label for="stock_adjustment_reason" class="form-label">Reason (Optional)</label>
        <input type="text" id="stock_adjustment_reason" name="reason" class="form-control" placeholder="e.g., Initial stock, Damaged goods">
    </div>
    <button type="submit" class="btn btn-primary" id="adjustStockBtn">Adjust Stock</button>
</form>