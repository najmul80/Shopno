@extends('layouts.guest')
@section('title', 'Login')

@section('auth-check')
<script>
    if (localStorage.getItem('access_token')) {
        window.location.href = "{{ route('dashboard') }}";
    }
</script>
@endsection

@section('content')
    {{-- Your full login form HTML goes here --}}
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card overflow-hidden">
                <div class="bg-primary bg-soft">
                    <div class="row">
                        <div class="col-7">
                            <div class="text-primary p-4">
                                <h5 class="text-primary">Welcome Back!</h5>
                                <p>Sign in to continue to Shopno.</p>
                            </div>
                        </div>
                        <div class="col-5 align-self-end">
                            <img src="{{ asset('assets/images/profile-img.png') }}" alt="" class="img-fluid">
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0"> 
                    <div class="auth-logo">
                        <a href="#" class="auth-logo-dark">
                            <div class="avatar-md profile-user-wid mb-4">
                                <span class="avatar-title rounded-circle bg-light">
                                    <img src="{{ asset('assets/images/logo.svg') }}" alt="" class="rounded-circle" height="34">
                                </span>
                            </div>
                        </a>
                    </div>
                    <div class="p-2">
                        <form class="form-horizontal" id="login-form">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group auth-pass-inputgroup">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                                    <button class="btn btn-light " type="button" id="password-addon"><i class="mdi mdi-eye-outline"></i></button>
                                </div>
                            </div>
                            <div class="mt-3 d-grid">
                                <button class="btn btn-primary waves-effect waves-light" type="submit">Log In</button>
                            </div>
                            <div class="mt-4 text-center">
                                <a href="{{ route('password.request') }}" class="text-muted"><i class="mdi mdi-lock me-1"></i> Forgot your password?</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="mt-5 text-center">
                <div>
                    <p>© <script>document.write(new Date().getFullYear())</script> Shopno. Crafted by You</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        let email = $('#email').val();
        let password = $('#password').val();
        let button = $(this).find('button[type="submit"]');

        button.html('Logging In...').prop('disabled', true);

        // We do NOT use the global Axios instance here, to avoid any interceptor issues.
        // We create a new, clean instance for the login call.
        axios.create({
            baseURL: '/api/v1/'
        }).post('auth/login', {
            email: email,
            password: password
        })
        .then(function(response) {
            if (response.data.success && response.data.data.access_token) {
                // Store the token in localStorage. This is the only place it's written.
                localStorage.setItem('access_token', response.data.data.access_token);
                // Also store refresh token if it exists
                if(response.data.data.refresh_token) {
                    localStorage.setItem('refresh_token', response.data.data.refresh_token);
                }
                
                // Redirect on success
                window.location.href = "{{ route('dashboard') }}";
            } else {
                Swal.fire('Error', 'Login successful, but no token was received.', 'error');
                button.html('Log In').prop('disabled', false);
            }
        })
        .catch(function(error) {
            let msg = error.response?.data?.message || 'Invalid credentials.';
            Swal.fire('Login Failed', msg, 'error');
            button.html('Log In').prop('disabled', false);
        });
    });
});
</script>
@endpush