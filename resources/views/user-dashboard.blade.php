@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-tachometer-alt me-2"></i>My Dashboard</h2>
                <div class="text-muted">
                    Welcome back, {{ auth()->user()->full_name }}!
                </div>
            </div>

            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Applications Section -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-apps me-2"></i>My Applications
                            </h5>
                        </div>
                        <div class="card-body">
                            @if ($userApplications->count() > 0)
                                <div class="row">
                                    @foreach ($userApplications as $application)
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="card application-tile h-100">
                                                <div class="card-body text-center d-flex flex-column">
                                                    <div class="mb-3">
                                                        @if ($application->logo)
                                                            <img src="{{ $application->logo }}"
                                                                alt="{{ $application->name }}"
                                                                class="application-logo mb-2">
                                                        @else
                                                            <div class="application-icon mb-2">
                                                                <i
                                                                    class="{{ $application->icon ?? 'fas fa-cube' }} fa-3x text-primary"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <h6 class="card-title mb-2">{{ $application->name }}</h6>
                                                    <p class="card-text text-muted small flex-grow-1">
                                                        {{ Str::limit($application->description, 80) }}
                                                    </p>
                                                    <div class="mt-auto">
                                                        <a href="{{ $application->url }}" target="_blank"
                                                            class="btn btn-primary btn-sm">
                                                            <i class="fas fa-external-link-alt me-1"></i>Launch
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="fas fa-apps fa-3x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted">No Applications Available</h5>
                                    <p class="text-muted">
                                        You don't have access to any applications yet. Contact your administrator if you
                                        need access to specific applications.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Profile Section -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2"></i>My Profile
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div
                                    class="avatar-lg bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center">
                                    {{ strtoupper(substr(auth()->user()->first_name, 0, 1) . substr(auth()->user()->last_name, 0, 1)) }}
                                </div>
                            </div>

                            <div class="profile-info">
                                <div class="mb-3">
                                    <strong>Name:</strong>
                                    <div class="text-muted">{{ $profile['name'] }}</div>
                                </div>

                                <div class="mb-3">
                                    <strong>Email:</strong>
                                    <div class="text-muted">{{ $profile['email'] }}</div>
                                </div>

                                @if ($profile['title'])
                                    <div class="mb-3">
                                        <strong>Title:</strong>
                                        <div class="text-muted">{{ $profile['title'] }}</div>
                                    </div>
                                @endif

                                @if ($profile['department'])
                                    <div class="mb-3">
                                        <strong>Department:</strong>
                                        <div class="text-muted">{{ $profile['department'] }}</div>
                                    </div>
                                @endif

                                @if ($profile['supervisor'])
                                    <div class="mb-3">
                                        <strong>Supervisor:</strong>
                                        <div class="text-muted">{{ $profile['supervisor'] }}</div>
                                    </div>
                                @endif

                                @if (count($profile['roles']) > 0)
                                    <div class="mb-3">
                                        <strong>Roles:</strong>
                                        <div>
                                            @foreach ($profile['roles'] as $role)
                                                <span class="badge bg-secondary me-1 mb-1">{{ $role }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="text-center mt-3">
                                <a href="{{ route('profile') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>View Full Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <style>
        .application-tile {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border: 1px solid #dee2e6;
        }

        .application-tile:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .application-logo {
            max-width: 48px;
            max-height: 48px;
            object-fit: contain;
        }

        .application-icon {
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-lg {
            width: 64px;
            height: 64px;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .profile-info strong {
            font-size: 0.9rem;
            color: #495057;
        }

        .profile-info .text-muted {
            font-size: 0.9rem;
            margin-top: 2px;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
        }

        .card-text {
            line-height: 1.4;
        }
    </style>
@endsection
