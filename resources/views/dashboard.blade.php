@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
{{-- Page Title --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">Dashboard</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">Shopno</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- Summary Cards Row --}}
<div class="row" id="summary-cards-container">
    {{-- Card for Total Revenue --}}
    <div class="col-md-4">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Today's Revenue</p>
                        <h4 class="mb-0" id="summary-todays-revenue">$0.00</h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-primary"><span class="avatar-title"><i class="bx bx-dollar-circle font-size-24"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Card for Total Products --}}
    <div class="col-md-4">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Total Products</p>
                        <h4 class="mb-0" id="summary-total-products">0</h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-primary"><span class="avatar-title"><i class="bx bx-cube-alt font-size-24"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Card for Total Customers --}}
    <div class="col-md-4">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Total Customers</p>
                        <h4 class="mb-0" id="summary-total-customers">0</h4>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-primary"><span class="avatar-title"><i class="bx bxs-user-detail font-size-24"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Charts and Latest Transactions Row --}}
<div class="row">
    {{-- Sales Trend Chart --}}
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Sales Analytics (Last 30 Days)</h4>
                <div id="sales-analytics-chart" class="apex-charts" dir="ltr"></div>
            </div>
        </div>
    </div>
    {{-- Latest Transactions --}}
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Latest Sales</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <tbody id="latest-sales-list">
                            {{-- Latest sales will be loaded here by JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- ApexCharts library (included in your Skote template assets) --}}
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    {{-- Dedicated JS file for the dashboard page --}}
    <script src="{{ asset('js/pages/dashboard.js') }}"></script> 
@endpush