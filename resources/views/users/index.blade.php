@extends('layouts.app')
@section('title', 'User Management')

@push('styles')
    <link href="{{ asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
{{-- Page Title and "Add New" Button --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">User List</h4>
            <div class="page-title-right">
                <button type="button" class="btn btn-primary" id="addNewUserBtn">
                    <i class="bx bx-plus me-1"></i> Add New User
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
                <table id="users-table" class="table table-bordered dt-responsive nowrap w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role(s)</th>
                            <th>Store</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="user-form" onsubmit="return false;">
                    <input type="hidden" id="user_id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="user_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="user_name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="user_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="user_email" name="email" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="user_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="user_password" name="password">
                            <small class="form-text text-muted">Leave empty if you don't want to change the password.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="user_password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="user_password_confirmation" name="password_confirmation">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="user_roles" class="form-label">Role(s)</label>
                            <select id="user_roles" name="roles[]" class="form-select" multiple required>
                                {{-- Roles will be loaded by JS --}}
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="user_store_id" class="form-label">Store</label>
                            <select id="user_store_id" name="store_id" class="form-select">
                                {{-- Stores will be loaded by JS --}}
                            </select>
                        </div>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="user_is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="user_is_active">User is Active</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveUserBtn">Save User</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables.net-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('js/pages/users.js') }}"></script> 
@endpush