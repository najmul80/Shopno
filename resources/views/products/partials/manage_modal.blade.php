<div class="modal fade" id="manageProductModal" tabindex="-1" aria-labelledby="manageProductModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manageProductModalLabel">Manage Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#basicInfo" role="tab">Basic Info</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#images" role="tab">Images</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#variants" role="tab">Variants</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#stock" role="tab">Stock Control</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#codes" role="tab">Barcode/QR</a></li>
                </ul>
                <div class="tab-content p-3">
                    <div class="tab-pane active" id="basicInfo" role="tabpanel">@include('products.partials.tabs.basic_info_tab')</div>
                    <div class="tab-pane" id="images" role="tabpanel">@include('products.partials.tabs.images_tab')</div>
                    <div class="tab-pane" id="variants" role="tabpanel">@include('products.partials.tabs.variants_tab')</div>
                    <div class="tab-pane" id="stock" role="tabpanel">@include('products.partials.tabs.stock_tab')</div>
                    <div class="tab-pane" id="codes" role="tabpanel">@include('products.partials.tabs.codes_tab')</div>
                </div>
            </div>
        </div>
    </div>
</div>