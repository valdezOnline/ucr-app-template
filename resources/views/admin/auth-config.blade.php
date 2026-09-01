@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-cog me-2"></i>Authentication Configuration
                        </h4>
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('info'))
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="text-primary">Current Configuration</h5>
                                <div class="p-3 bg-light rounded">
                                    <strong>Authentication Type:</strong>
                                    <span class="badge bg-{{ $currentType === 'cas' ? 'info' : 'primary' }} ms-2">
                                        {{ \App\Services\AuthenticationService::getTypeDisplayName() }}
                                    </span>

                                    @if ($currentType === 'cas')
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <strong>CAS Status:</strong>
                                                @if ($casAvailable)
                                                    <span class="text-success">✓ Available & Configured</span>
                                                @else
                                                    <span class="text-warning">⚠ Not Available</span>
                                                @endif
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="text-primary">Quick Info</h5>
                                <div class="p-3 bg-light rounded">
                                    <ul class="list-unstyled mb-0 small">
                                        <li><strong>Registration:</strong>
                                            {{ \App\Services\AuthenticationService::registrationEnabled() ? 'Enabled' : 'Disabled' }}
                                        </li>
                                        <li><strong>Password Reset:</strong>
                                            {{ \App\Services\AuthenticationService::passwordResetEnabled() ? 'Enabled' : 'Disabled' }}
                                        </li>
                                        <li><strong>SSO:</strong>
                                            {{ $currentType === 'cas' ? 'Enabled' : 'Disabled' }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <form method="POST" action="{{ route('auth-config.update') }}" onsubmit="return confirmChange()">
                            @csrf
                            @method('PUT')

                            <h5 class="text-primary mb-3">Change Authentication Type</h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card {{ $currentType === 'laravel' ? 'border-primary' : '' }}">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="auth_type"
                                                    id="laravel" value="laravel"
                                                    {{ $currentType === 'laravel' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold" for="laravel">
                                                    <i class="fas fa-user-lock me-2"></i>Laravel Authentication
                                                </label>
                                            </div>
                                            <small class="text-muted">
                                                Traditional email/password authentication with local user management.
                                            </small>
                                            <div class="mt-2">
                                                <small class="text-success">
                                                    ✓ User registration<br>
                                                    ✓ Password reset<br>
                                                    ✓ Local user management
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="card {{ $currentType === 'cas' ? 'border-primary' : '' }}">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="auth_type"
                                                    id="cas" value="cas"
                                                    {{ $currentType === 'cas' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold" for="cas">
                                                    <i class="fas fa-sign-in-alt me-2"></i>CAS Authentication
                                                </label>
                                            </div>
                                            <small class="text-muted">
                                                Central Authentication Service (Single Sign-On) integration.
                                            </small>
                                            <div class="mt-2">
                                                <small class="text-info">
                                                    ✓ Single Sign-On (SSO)<br>
                                                    ✓ Central user management<br>
                                                    ✓ No local passwords
                                                </small>
                                            </div>
                                            @if (!$casAvailable && $currentType !== 'cas')
                                                <div class="mt-2">
                                                    <small class="text-warning">
                                                        ⚠ Requires proper CAS configuration
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Authentication Type
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle me-2"></i>Important Notes
                            </h6>
                            <ul class="mb-0 small">
                                <li>Changing authentication type will clear the configuration cache</li>
                                <li>All users will need to re-authenticate after the change</li>
                                <li>CAS authentication requires proper server configuration</li>
                                <li>Laravel authentication allows local user registration and management</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmChange() {
            const selectedType = document.querySelector('input[name="auth_type"]:checked').value;
            const currentType = '{{ $currentType }}';

            if (selectedType !== currentType) {
                let message = 'Are you sure you want to change the authentication type to ' +
                    (selectedType === 'cas' ? 'CAS' : 'Laravel') + '?\\n\\n';

                if (selectedType === 'cas') {
                    message += 'This will:\\n' +
                        '• Switch to Single Sign-On authentication\\n' +
                        '• Log you out and send you to the login page\\n' +
                        '• Continue by clicking "Sign In with CAS"\\n' +
                        '• Require institutional credentials for login\\n\\n' +
                        'You will need to sign in again with your institutional account.';
                } else {
                    message += 'This will:\\n' +
                        '• Switch to local Laravel authentication\\n' +
                        '• Keep you logged in (if possible)\\n' +
                        '• Enable email/password login\\n\\n' +
                        'Users will use email/password to login.';
                }

                return confirm(message);
            }

            return true;
        }

        // Show loading spinner during form submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = document.querySelector('button[type=\"submit\"]');

            if (form && submitBtn) {
                form.addEventListener('submit', function() {
                    submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin me-2\"></i>Updating...';
                    submitBtn.disabled = true;
                });
            }
        });
    </script>
@endsection
