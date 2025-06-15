<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Dashboard') | Shopno</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Shopno - Point of Sale & Inventory Management" name="description" />
    <meta content="Themesbrand" name="author" />
    
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    {{-- Client-side Route Protection: This script runs immediately. --}}
    {{-- If no access token is found in localStorage, it redirects to the login page. --}}
    <script>
        if (!localStorage.getItem('access_token')) {
            window.location.href = "{{ route('login') }}";
        }
    </script>

    {{-- Vite directive to load compiled CSS and JS (including configured Axios) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Stack for page-specific CSS (e.g., DataTables) --}}
    @stack('styles')

    <!-- Theme CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
</head>

<body data-sidebar="dark">
    <!-- Begin page -->
    <div id="layout-wrapper">
        
        @include('partials.header')
        
        @include('partials.sidebar')

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
            @include('partials.footer')
        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <div class="rightbar-overlay"></div>

    <!-- Core JavaScript -->
    <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    
    <!-- Main Theme App JS -->
    <script src="{{ asset('assets/js/app.js') }}"></script>

    {{-- Global scripts, like the logout handler --}}
    <script>
        $(document).ready(function() {
            // Universal Logout Button Handler
            $('#logout-button').on('click', function(e) {
                e.preventDefault();
                
                // Call the API's logout endpoint (optional but good practice)
                axios.post('auth/logout')
                    .catch(error => {
                        console.error('API logout call failed, but logging out on client.', error);
                    })
                    .finally(() => {
                        // Always clear localStorage and redirect to login
                        localStorage.removeItem('access_token');
                        localStorage.removeItem('refresh_token');
                        localStorage.removeItem('user');
                        window.location.href = "{{ route('login') }}";
                    });
            });
        });
    </script>
    
    {{-- Stack for page-specific JavaScript --}}
    @stack('scripts')
</body>
</html>