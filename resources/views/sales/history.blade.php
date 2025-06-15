@extends('layouts.app')
@section('title', 'Sales History')

@push('styles')
    <link href="{{ asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">Sales History</h4>
            <div class="page-title-right">
                <a href="{{ route('pos.index') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i> New Sale (POS) 
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table id="sales-table" class="table table-bordered dt-responsive nowrap w-100">
                    <thead>
                        <tr>
                            <th>Invoice ID</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Due Amount</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Invoice Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoiceModalLabel">Invoice Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="invoiceModalBody">
                {{-- Invoice details will be loaded here by JavaScript --}}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="printInvoiceBtn"><i class="fa fa-print"></i> Print</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- Required DataTables JS files --}}
    <script src="{{ asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    {{-- THIS IS THE CRITICAL ADDITION --}}
    <script>
        // Create a global object to hold asset paths for our JS files to use.
        window.ASSET_PATHS = {
            bootstrap_css: "{{ asset('assets/css/bootstrap.min.css') }}"
        };
    </script>

    {{-- Now, load the main JS file for this page --}}
    <script src="{{ asset('js/pages/sales.js') }}"></script> 
@endpush