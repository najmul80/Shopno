<div class="text-center">
    <h5>Generate Codes</h5>
    <p>Select a variant to generate its barcode. The QR code is for the main product.</p>
    <div class="mb-3">
        <label for="code_variant_select" class="form-label">Select Variant for Barcode</label>
        <select id="code_variant_select" class="form-select">
            {{-- This will be populated by JS --}}
        </select>
    </div>
    <button type="button" class="btn btn-info" id="generateBarcodeBtn">View Barcode</button>
    <button type="button" class="btn btn-secondary" id="generateQrCodeBtn">View Product QR Code</button>
    <hr>
    <div id="code-display-area" class="mt-3 p-3 border" style="min-height: 100px;">
        {{-- Barcode or QR code image will be displayed here --}}
    </div>
</div>