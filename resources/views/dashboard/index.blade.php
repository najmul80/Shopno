@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- start page title -->
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
<!-- end page title -->

<div class="row">
    <div class="col-xl-8">
        <div class="row" id="summary-cards">
            <!-- Summary cards will be loaded here by JS -->
            <div class="col-md-4">
                <div class="card mini-stats-wid">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="text-muted fw-medium">Today's Sales</p>
                                <h4 class="mb-0">Loading...</h4>
                            </div>
                            <div class="flex-shrink-0 align-self-center">
                                <div class="mini-stat-icon avatar-sm rounded-circle bg-primary"><span class="avatar-title"><i class="bx bx-copy-alt font-size-24"></i></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- More placeholder cards -->
        </div>
    </div>
    <div class="col-xl-4">
        {{-- Other dashboard elements --}}
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Fetch dashboard summary
        axios.get('dashboard/summary')
            .then(function(response) {
                const data = response.data.data;
                const cardsHtml = `
                    <div class="col-md-4">
                        <div class="card mini-stats-wid">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-grow-1">
                                        <p class="text-muted fw-medium">Today's Sales</p>
                                        <h4 class="mb-0">${data.todays_sales}</h4>
                                    </div>
                                    <div class="flex-shrink-0 align-self-center"><div class="avatar-sm rounded-circle bg-primary mini-stat-icon"><span class="avatar-title rounded-circle bg-primary"><i class="bx bx-archive-in font-size-24"></i></span></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mini-stats-wid">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-grow-1">
                                        <p class="text-muted fw-medium">Total Products</p>
                                        <h4 class="mb-0">${data.total_products}</h4>
                                    </div>
                                    <div class="flex-shrink-0 align-self-center"><div class="avatar-sm rounded-circle bg-primary mini-stat-icon"><span class="avatar-title rounded-circle bg-primary"><i class="bx bx-purchase-tag-alt font-size-24"></i></span></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                     <div class="col-md-4">
                        <div class="card mini-stats-wid">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-grow-1">
                                        <p class="text-muted fw-medium">Total Customers</p>
                                        <h4 class="mb-0">${data.total_customers}</h4>
                                    </div>
                                    <div class="flex-shrink-0 align-self-center"><div class="avatar-sm rounded-circle bg-primary mini-stat-icon"><span class="avatar-title rounded-circle bg-primary"><i class="bx bxs-user-detail font-size-24"></i></span></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('#summary-cards').html(cardsHtml);
            })
            .catch(function(error) {
                console.error("Could not load dashboard summary", error);
            });
    });
</script>
@endpush