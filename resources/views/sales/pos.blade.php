@extends('layouts.app')
@section('title', 'Point of Sale (POS)')

@push('styles')
{{-- Custom CSS for the POS interface --}}
<style>
    #pos-container {
        display: flex;
        gap: 1.5rem;
    }
    #product-list-section {
        flex: 7;
    }
    #cart-section {
        flex: 5;
    }
    .product-card {
        cursor: pointer;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        transition: all 0.2s ease-in-out;
    }
    .product-card:hover {
        border-color: #556ee6;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .product-card-img {
        width: 100%;
        height: 120px;
        object-fit: cover;
    }
    .cart-item-qty-input {
        width: 60px;
        text-align: center;
    }
    #product-grid {
        max-height: 70vh;
        overflow-y: auto;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">Point of Sale</h4>
        </div>
    </div>
</div>

<div id="pos-container">

    {{-- Left Section: Product Listing & Search --}}
    <div id="product-list-section">
        <div class="card">
            <div class="card-body">
                {{-- Search and Filter Bar --}}
                <div class="row mb-3">
                    <div class="col-md-7">
                        <input type="text" id="product-search-input" class="form-control" placeholder="Search by product name or SKU...">
                    </div>
                    <div class="col-md-5">
                        <select id="category-filter-select" class="form-select">
                            <option value="">All Categories</option>
                            {{-- Categories will be loaded here by JS --}}
                        </select>
                    </div>
                </div>

                {{-- Product Grid --}}
                <div class="row g-3" id="product-grid">
                    {{-- Product cards will be loaded here by JS --}}
                </div>
            </div>
        </div>
    </div>

    {{-- Right Section: Cart & Payment --}}
    <div id="cart-section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Order Details</h5>

                {{-- Customer Selection --}}
                <div class="mb-3">
                    <label for="customer-select" class="form-label">Customer</label>
                    <select id="customer-select" class="form-select" name="customer_id" required>
                        {{-- Customers will be loaded here --}}
                    </select>
                </div>
                <hr>

                {{-- Cart Items Table --}}
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cart-items-body">
                            {{-- Cart items will be added here by JS --}}
                            <tr><td colspan="5" class="text-center">Cart is empty</td></tr>
                        </tbody>
                    </table>
                </div>
                <hr>

                {{-- Order Summary --}}
                <div>
                    <div class="d-flex justify-content-between">
                        <strong>Subtotal:</strong>
                        <span id="cart-subtotal">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <strong>Discount:</strong>
                        <span id="cart-discount">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold font-size-16">
                        <strong>Total:</strong>
                        <span id="cart-total">$0.00</span>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-grid gap-2 mt-4">
                    <button class="btn btn-primary btn-lg" id="process-payment-btn">Process Payment</button>
                    <button class="btn btn-danger" id="cancel-sale-btn">Cancel Sale</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    {{-- This is the dedicated JavaScript file for the POS page --}}
    <script src="{{ asset('js/pages/pos.js') }}"></script> 
@endpush