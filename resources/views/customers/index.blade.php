@extends('layouts.app')
@section('title', 'Customer Management')

{{-- Add DataTables CSS to the <head> section of the layout --}}
@push('styles')
    <link href="{{ asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
{{-- Page Title and "Add New" Button --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">Customer List</h4>
            <div class="page-title-right">
                <button type="button" class="btn btn-primary" id="addNewCustomerBtn">
                    <i class="bx bx-plus me-1"></i> Add New Customer
                </button>
            </div>
        </div>
    </div>
</div>

{{-- The Card containing the DataTable --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table id="customers-table" class="table table-bordered dt-responsive nowrap w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerModalLabel">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="customer-form" onsubmit="return false;">
                    <input type="hidden" id="customer_id" name="id">
                    <div class="mb-3">
                        <label for="customer_name" class="form-label">Customer Name</label>
                        <input type="text" class="form-control" id="customer_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="customer_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="customer_email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="customer_phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="customer_phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="customer_address" class="form-label">Address</label>
                        <textarea class="form-control" id="customer_address" name="address" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveCustomerBtn">Save Customer</button>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Push the page-specific JavaScript file to the layout --}}
@push('scripts')
    <script src="{{ asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    
    {{-- This is the dedicated JavaScript file for the customers page --}}
    <script src="{{ asset('js/pages/customers.js') }}"></script> 
@endpush