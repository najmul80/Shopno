<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>@yield('title') | Shopno</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Shopno - Point of Sale & Inventory Management" name="description" />
    <meta content="Themesbrand" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    {{-- This script will redirect users who are already logged in --}}
    @yield('auth-check')

    {{-- Vite directive to load compiled CSS and JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
    <!-- Sweet Alert2 -->
    <link href="{{ asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
</head>
<body>
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            @yield('content')
        </div>
    </div>
    <!-- end account-pages -->

    <!-- Core JavaScript -->
    <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    
    {{-- This stack is for page-specific scripts, like the login form handler --}}
    @stack('scripts')
</body>
</html>