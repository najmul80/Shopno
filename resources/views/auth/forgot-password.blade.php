@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="card overflow-hidden">
            <div class="bg-primary bg-soft">
                <div class="row">
                    <div class="col-7">
                        <div class="text-primary p-4">
                            <h5 class="text-primary">Reset Password</h5>
                            <p>Reset Password with Shopno.</p>
                        </div>
                    </div>
                    <div class="col-5 align-self-end">
                        <img src="{{ asset('assets/images/profile-img.png') }}" alt="" class="img-fluid">
                    </div>
                </div>
            </div>
            <div class="card-body pt-0"> 
                <div>
                    <a href="#">
                        <div class="avatar-md profile-user-wid mb-4">
                            <span class="avatar-title rounded-circle bg-light">
                                <img src="{{ asset('assets/images/logo.svg') }}" alt="" class="rounded-circle" height="34">
                            </span>
                        </div>
                    </a>
                </div>
                
                <div class="p-2">
                    <div class="alert alert-success text-center mb-4" role="alert">
                        Enter your Email and instructions will be sent to you!
                    </div>
                    <form class="form-horizontal" id="forgot-password-form">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" placeholder="Enter email" required>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-primary w-md waves-effect waves-light" type="submit">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="mt-5 text-center">
            <p>Remember It ? <a href="{{ route('login') }}" class="fw-medium text-primary"> Sign In here</a> </p>
            <p>© <script>document.write(new Date().getFullYear())</script> Shopno. Crafted with <i class="mdi mdi-heart text-danger"></i> by You</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#forgot-password-form').on('submit', function(e) {
        e.preventDefault();
        let email = $('#email').val();
        
        // Add loading state to button
        let button = $(this).find('button[type="submit"]');
        button.html('Sending...').prop('disabled', true);

        axios.post('/auth/password/forgot', { email: email })
            .then(function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.data.message || 'Password reset link sent to your email!',
                });
                button.html('Reset').prop('disabled', false);
            })
            .catch(function(error) {
                let errorMessage = error.response.data.message || 'Could not send reset link.';
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: errorMessage,
                });
                button.html('Reset').prop('disabled', false);
            });
    });
});
</script>
@endpush