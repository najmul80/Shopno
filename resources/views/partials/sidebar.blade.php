<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title" key="t-menu">Menu</li>

                <li>
                    <a href="{{ route('dashboard') }}" class="waves-effect">
                        <i class="bx bx-home-circle"></i>
                        <span key="t-dashboards">Dashboard</span>
                    </a>
                </li>
                
                <li>
                     <a href="{{ route('pos.index') }}" class="waves-effect">
                        <i class="bx bx-cart-alt"></i>
                        <span key="t-pos">POS</span>
                    </a>
                </li>

                <li class="menu-title" key="t-apps">Management</li>
                
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-store"></i>
                        <span key="t-products">Products</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('products.index') }}" key="t-product-list">Product List</a></li>
                        <li><a href="#" key="t-add-product">Add Product</a></li>
                        <li><a href="{{ route('categories.index') }}" key="t-categories">Categories</a></li>
                    </ul>
                </li>
                
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-receipt"></i>
                        <span key="t-sales">Sales</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('sales.history') }}" key="t-sales-history">Sales History</a></li>
                        <li><a href="#" key="t-invoices">Invoices</a></li>
                    </ul>
                </li>
                
                <li>
                    <a href="{{ route('customers.index') }}" class="waves-effect">
                        <i class="bx bxs-user-detail"></i>
                        <span key="t-customers">Customers</span>
                    </a>
                </li>
                
                <li class="menu-title">Admin</li>
                
                <li>
                    <a href="{{ route('reports.index') }}" class="waves-effect">
                        <i class="bx bxs-report"></i>
                        <span key="t-reports">Reports</span>
                    </a>
                </li>
                
                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-user-circle"></i>
                        <span key="t-users">User Management</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('users.index') }}" key="t-user-list">Users</a></li>
                          <li><a href="{{ route('admin.roles.index') }}" key="t-roles">Roles & Permissions</a></li>
                    </ul>
                </li>
                
                <li>
                    <a href="#" class="waves-effect">
                        <i class="bx bx-cog"></i>
                        <span key="t-settings">Settings</span>
                    </a>
                </li>
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>