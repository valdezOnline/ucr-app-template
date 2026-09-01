@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-user me-2"></i>My Profile
                    </h3>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- Avatar and Basic Info -->
                        <div class="col-md-4 text-center">
                            <div class="mb-4">
                                <div
                                    class="avatar-xl bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                                    {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                </div>
                                <h4 class="mb-1">{{ $user->full_name }}</h4>
                                <p class="text-muted mb-2">{{ $user->title ?: 'Staff Member' }}</p>
                                <p class="text-muted">{{ $user->department ?: 'No Department Assigned' }}</p>

                                @if ($user->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </div>
                        </div>

                        <!-- Detailed Information -->
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2 mb-3">Contact Information</h5>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Net ID</label>
                                        <p class="form-control-plaintext">{{ $user->net_id }}</p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Email</label>
                                        <p class="form-control-plaintext">{{ $user->email }}</p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">First Name</label>
                                        <p class="form-control-plaintext">{{ $user->first_name }}</p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Last Name</label>
                                        <p class="form-control-plaintext">{{ $user->last_name }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2 mb-3 mt-4">Work Information</h5>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Title</label>
                                        <p class="form-control-plaintext">{{ $user->title ?: 'Not specified' }}</p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Department</label>
                                        <p class="form-control-plaintext">{{ $user->department ?: 'Not specified' }}</p>
                                    </div>
                                </div>

                                @if ($user->supervisor_name)
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Supervisor</label>
                                            <p class="form-control-plaintext">{{ $user->supervisor_name }}</p>
                                        </div>
                                    </div>
                                @endif

                                @if ($user->supervisor_net_id)
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Supervisor Net ID</label>
                                            <p class="form-control-plaintext">{{ $user->supervisor_net_id }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2 mb-3 mt-4">Roles & Permissions</h5>
                                </div>

                                <div class="col-12">
                                    @php
                                        $legacyRoles = [];
                                        if ($user->is_admin) {
                                            $legacyRoles[] = 'Admin';
                                        }
                                        if ($user->is_manager) {
                                            $legacyRoles[] = 'Manager';
                                        }
                                        if ($user->is_supervisor) {
                                            $legacyRoles[] = 'Supervisor';
                                        }
                                    @endphp

                                    @if (count($legacyRoles) > 0)
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">System Roles</label>
                                            <div>
                                                @foreach ($legacyRoles as $role)
                                                    <span class="badge bg-primary me-1 mb-1">{{ $role }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if ($user->roles->count() > 0)
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Dynamic Roles</label>
                                            <div>
                                                @foreach ($user->roles as $role)
                                                    <span class="badge bg-info me-1 mb-1">{{ $role->display_name }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if (count($legacyRoles) === 0 && $user->roles->count() === 0)
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Roles</label>
                                            <p class="form-control-plaintext text-muted">No roles assigned</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2 mb-3 mt-4">Account Information</h5>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Account Created</label>
                                        <p class="form-control-plaintext">{{ $user->created_at->format('M d, Y') }}</p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Last Updated</label>
                                        <p class="form-control-plaintext">{{ $user->updated_at->format('M d, Y') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light">
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        To update your profile information, please contact your system administrator or HR department.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <style>
        .avatar-xl {
            width: 96px;
            height: 96px;
            font-size: 2rem;
            font-weight: 600;
        }

        .form-control-plaintext {
            margin-bottom: 0;
            font-size: 0.95rem;
        }

        .form-label.fw-bold {
            font-size: 0.9rem;
            color: #495057;
            margin-bottom: 0.25rem;
        }

        .border-bottom {
            border-bottom: 2px solid #dee2e6 !important;
        }
    </style>
@endsection
