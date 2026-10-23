@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-shield-alt me-2"></i>Admin Dashboard</h2>
                <div class="text-muted">
                    Welcome back, Administrator {{ auth()->user()->full_name }}!
                </div>
            </div>

            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold">{{ $stats['total_users'] }}</div>
                                    <div>Total Users</div>
                                </div>
                                <div class="fs-1 opacity-75">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-primary-dark">
                            <a href="{{ route('users.index') }}" class="text-white text-decoration-none">
                                View all users <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold">{{ $stats['active_users'] }}</div>
                                    <div>Active Users</div>
                                </div>
                                <div class="fs-1 opacity-75">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-success-dark">
                            <a href="{{ route('users.index', ['status' => 'active']) }}"
                                class="text-white text-decoration-none">
                                View active users <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold">{{ $stats['inactive_users'] }}</div>
                                    <div>Inactive Users</div>
                                </div>
                                <div class="fs-1 opacity-75">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-danger-dark">
                            <a href="{{ route('users.index', ['status' => 'inactive']) }}"
                                class="text-white text-decoration-none">
                                View inactive users <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold">{{ $stats['admin_users'] }}</div>
                                    <div>Admin Users</div>
                                </div>
                                <div class="fs-1 opacity-75">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-warning-dark">
                            <a href="{{ route('users.index', ['role' => 'admin']) }}"
                                class="text-white text-decoration-none">
                                View admins <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Management Statistics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold">{{ $stats['total_roles'] }}</div>
                                    <div>Total Roles</div>
                                </div>
                                <div class="fs-1 opacity-75">
                                    <i class="fas fa-user-tag"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-info-dark">
                            <a href="{{ route('roles.index') }}" class="text-white text-decoration-none">
                                Manage roles <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold">{{ $stats['total_applications'] }}</div>
                                    <div>Applications</div>
                                </div>
                                <div class="fs-1 opacity-75">
                                    <i class="fas fa-apps"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-secondary-dark">
                            <a href="{{ route('applications.index') }}" class="text-white text-decoration-none">
                                Manage apps <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-dark text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold">{{ $stats['manager_users'] }}</div>
                                    <div>Managers</div>
                                </div>
                                <div class="fs-1 opacity-75">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-dark-dark">
                            <a href="{{ route('users.index', ['role' => 'manager']) }}"
                                class="text-white text-decoration-none">
                                View managers <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-purple text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fs-4 fw-bold">{{ $stats['supervisor_users'] }}</div>
                                    <div>Supervisors</div>
                                </div>
                                <div class="fs-1 opacity-75">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-purple-dark">
                            <a href="{{ route('users.index', ['role' => 'supervisor']) }}"
                                class="text-white text-decoration-none">
                                View supervisors <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt me-2"></i>Administrative Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <a href="{{ route('users.create') }}" class="btn btn-primary w-100">
                                        <i class="fas fa-user-plus me-2"></i>Add New User
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <button type="button" class="btn btn-info w-100" data-bs-toggle="modal"
                                        data-bs-target="#importUserModal">
                                        <i class="fas fa-download me-2"></i>Import from HR
                                    </button>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="{{ route('users.index') }}" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-users me-2"></i>Manage Users
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="{{ route('roles.index') }}" class="btn btn-outline-info w-100">
                                        <i class="fas fa-user-tag me-2"></i>Manage Roles
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="{{ route('applications.index') }}" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-list-alt me-2"></i>Manage Applications
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="{{ route('users.index', ['status' => 'inactive']) }}"
                                        class="btn btn-outline-danger w-100">
                                        <i class="fas fa-user-slash me-2"></i>Inactive Users
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="{{ route('site-status-config.edit') }}" class="btn btn-outline-warning w-100">
                                        <i class="fas fa-tools me-2"></i>Site Status
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Your Profile
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div
                                    class="avatar-lg bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center">
                                    {{ strtoupper(substr(auth()->user()->first_name, 0, 1) . substr(auth()->user()->last_name, 0, 1)) }}
                                </div>
                            </div>
                            <div class="text-center">
                                <h6 class="mb-1">{{ auth()->user()->full_name }}</h6>
                                <p class="text-muted mb-2">{{ auth()->user()->title ?: 'Staff Member' }}</p>
                                <p class="text-muted mb-3">{{ auth()->user()->department ?: 'No Department' }}</p>

                                @if (count(auth()->user()->getRoles()) > 0)
                                    <div class="mb-3">
                                        @foreach (auth()->user()->getRoles() as $role)
                                            <span class="badge bg-secondary me-1">{{ $role }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <a href="{{ route('users.show', auth()->user()) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import User Modal -->
    <div class="modal fade" id="importUserModal" tabindex="-1" aria-labelledby="importUserModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('users.import-from-hr') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="importUserModalLabel">Import User from HR</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="net_id" class="form-label">Net ID</label>
                            <input type="text" class="form-control" id="net_id" name="net_id" required
                                placeholder="Enter the user's Net ID">
                            <div class="form-text">
                                Enter the Net ID of the user you want to import from HR system.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download me-1"></i>Import User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <style>
        .avatar-lg {
            width: 64px;
            height: 64px;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .bg-primary-dark {
            background-color: rgba(0, 0, 0, 0.1) !important;
        }

        .bg-success-dark {
            background-color: rgba(0, 0, 0, 0.1) !important;
        }

        .bg-warning-dark {
            background-color: rgba(0, 0, 0, 0.1) !important;
        }

        .bg-info-dark {
            background-color: rgba(0, 0, 0, 0.1) !important;
        }

        .bg-danger-dark {
            background-color: rgba(0, 0, 0, 0.1) !important;
        }

        .bg-secondary-dark {
            background-color: rgba(0, 0, 0, 0.1) !important;
        }

        .bg-dark-dark {
            background-color: rgba(0, 0, 0, 0.2) !important;
        }

        .bg-purple {
            background-color: #6f42c1 !important;
        }

        .bg-purple-dark {
            background-color: rgba(0, 0, 0, 0.1) !important;
        }
    </style>
@endsection
