@extends('layouts.app')
@section('title', 'Category Management')

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
            <h4 class="mb-sm-0 font-size-18">Category List</h4>
            <div class="page-title-right">
                <button type="button" class="btn btn-primary" id="addNewCategoryBtn">
                    <i class="bx bx-plus me-1"></i> Add New Category
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
                <table id="categories-table" class="table table-bordered dt-responsive nowrap w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="category-form" onsubmit="return false;" enctype="multipart/form-data">
                    <input type="hidden" id="category_id" name="id">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_description" class="form-label">Description</label>
                        <textarea class="form-control" id="category_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="category_image" class="form-label">Category Image</label>
                        <input type="file" class="form-control" id="category_image" name="image">
                        <small class="form-text text-muted">Leave empty if you don't want to change the existing image.</small>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="category_is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="category_is_active">Is Active</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveCategoryBtn">Save Category</button>
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
    
    {{-- This is the dedicated JavaScript file for the categories page --}}
    <script src="{{ asset('js/pages/categories.js') }}"></script> 
@endpush