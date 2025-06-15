@extends('layouts.app')
@section('title', 'Role Management')

@push('styles')
    <link href="{{ asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    {{-- Add styles for select2 if you have it, otherwise standard multi-select works fine --}}
@endpush

@section('content')
{{-- Page Title and "Add New" Button --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">Roles & Permissions</h4>
            <div class="page-title-right">
                <button type="button" class="btn btn-primary" id="addNewRoleBtn">
                    <i class="bx bx-plus me-1"></i> Add New Role
                </button>
            </div>
        </div>
    </div>
</div>

{{-- The Card containing the DataTable for roles --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table id="roles-table" class="table table-bordered dt-responsive nowrap w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role Name</th>
                            <th>Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roleModalLabel">Add New Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="role-form" onsubmit="return false;">
                    <input type="hidden" id="role_id" name="id">
                    <div class="mb-3">
                        <label for="role_name" class="form-label">Role Name</label>
                        <input type="text" class="form-control" id="role_name" name="name" required placeholder="e.g., store-admin">
                    </div>
                    <div class="mb-3">
                        <h5 class="font-size-14">Assign Permissions</h5>
                        <div id="permissions-container" class="row p-2 border rounded" style="max-height: 400px; overflow-y: auto;">
                            {{-- Checkboxes for permissions will be loaded here by JavaScript --}}
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveRoleBtn">Save Role</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    
    <script src="{{ asset('js/pages/roles.js') }}"></script> 
@endpush