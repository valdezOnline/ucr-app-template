@extends('layouts.app')

@section('title', 'View User - ' . $user->full_name)

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>User Details
                    </h5>
                    <div class="btn-group">
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-1"></i>Edit User
                        </a>
                        <form method="POST" action="{{ route('users.sync-with-hr', $user) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-info btn-sm">
                                <i class="fas fa-sync me-1"></i>Sync with HR
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Full Name</label>
                                <p class="form-control-plaintext">{{ $user->full_name }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Net ID</label>
                                <p class="form-control-plaintext">
                                    <code>{{ $user->net_id }}</code>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <p class="form-control-plaintext">
                                    <a href="mailto:{{ $user->email }}" class="text-decoration-none">
                                        {{ $user->email }}
                                    </a>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Department</label>
                                <p class="form-control-plaintext">{{ $user->department ?: 'Not specified' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Title</label>
                                <p class="form-control-plaintext">{{ $user->title ?: 'Not specified' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Supervisor</label>
                                <p class="form-control-plaintext">
                                    @if ($user->supervisor_name)
                                        {{ $user->supervisor_name }}
                                        @if ($user->supervisor_net_id)
                                            <br><small class="text-muted">Net ID: {{ $user->supervisor_net_id }}</small>
                                        @endif
                                    @else
                                        Not specified
                                    @endif
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <p class="form-control-plaintext">
                                    @if ($user->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Legacy Roles</label>
                                <p class="form-control-plaintext">
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
                                        @foreach ($legacyRoles as $role)
                                            <span class="badge bg-primary me-1">{{ $role }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">No legacy roles</span>
                                    @endif
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Dynamic Roles</label>
                                <p class="form-control-plaintext">
                                    @if ($user->roles->count() > 0)
                                        @foreach ($user->roles as $role)
                                            <span class="badge bg-success me-1">
                                                {{ $role->display_name }}
                                                @if (!$role->is_active)
                                                    <i class="fas fa-exclamation-triangle ms-1"
                                                        title="Role is inactive"></i>
                                                @endif
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">No dynamic roles assigned</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Created</label>
                                <p class="form-control-plaintext">
                                    {{ $user->created_at->format('M j, Y g:i A') }}
                                    <small class="text-muted">({{ $user->created_at->diffForHumans() }})</small>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Last Updated</label>
                                <p class="form-control-plaintext">
                                    {{ $user->updated_at->format('M j, Y g:i A') }}
                                    <small class="text-muted">({{ $user->updated_at->diffForHumans() }})</small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form method="POST" action="{{ route('users.toggle-status', $user) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-{{ $user->is_active ? 'warning' : 'success' }} me-2"
                                    {{ $user->id === auth()->id() && $user->is_active ? 'disabled' : '' }}>
                                    <i class="fas fa-{{ $user->is_active ? 'user-slash' : 'user-check' }} me-1"></i>
                                    {{ $user->is_active ? 'Deactivate User' : 'Activate User' }}
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6 text-end">
                            @if ($user->id !== auth()->id() && auth()->user()->hasRole('admin'))
                                <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                        <i class="fas fa-trash me-1"></i>Delete User
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dynamic Role Management -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-tag me-2"></i>Dynamic Role Management
                    </h5>
                    @if ($availableRoles->isNotEmpty())
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#assignRoleModal">
                            <i class="fas fa-plus me-1"></i>Assign Role
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    @if ($user->roles->count() === 0)
                        <div class="text-center py-3">
                            <i class="fas fa-user-tag fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-2">No dynamic roles assigned</p>
                            @if ($availableRoles->isNotEmpty())
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#assignRoleModal">
                                    <i class="fas fa-plus me-1"></i>Assign First Role
                                </button>
                            @else
                                <small class="text-muted">No roles available to assign</small>
                            @endif
                        </div>
                    @else
                        <div class="row">
                            @foreach ($user->roles as $role)
                                <div class="col-md-6 mb-3">
                                    <div
                                        class="card border-start border-3 {{ $role->is_active ? 'border-success' : 'border-warning' }}">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1">{{ $role->display_name }}</h6>
                                                    @if ($role->level)
                                                        <span
                                                            class="badge bg-secondary">{{ ucfirst($role->level) }}</span>
                                                    @endif
                                                    @if (!$role->is_active)
                                                        <span class="badge bg-warning">Inactive</span>
                                                    @endif
                                                    @if ($role->description)
                                                        <p class="text-muted small mt-1 mb-0">
                                                            {{ Str::limit($role->description, 60) }}</p>
                                                    @endif
                                                </div>
                                                <form method="POST" action="{{ route('users.remove-role', $user) }}"
                                                    class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="role_id" value="{{ $role->id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="Remove role"
                                                        onclick="return confirm('Remove {{ $role->display_name }} role from this user?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Application Access -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-apps me-2"></i>Application Access & Roles
                    </h5>
                </div>
                <div class="card-body">
                    @if ($accessibleApplications->count() === 0)
                        <div class="text-center py-3">
                            <i class="fas fa-apps fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No applications accessible</p>
                            <small class="text-muted">User needs role assignments to access applications</small>
                        </div>
                    @else
                        <div class="row">
                            @foreach ($accessibleApplications as $application)
                                <div class="col-md-6 mb-3">
                                    <div
                                        class="card border-start border-3 {{ $application->is_active ? 'border-info' : 'border-warning' }}">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center mb-2">
                                                @if ($application->icon)
                                                    <i class="{{ $application->icon }} fa-lg text-primary me-2"></i>
                                                @else
                                                    <i class="fas fa-cube fa-lg text-muted me-2"></i>
                                                @endif
                                                <div>
                                                    <h6 class="mb-0">{{ $application->name }}</h6>
                                                    @if (!$application->is_active)
                                                        <span class="badge bg-warning">Inactive</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if ($application->description)
                                                <p class="text-muted small mb-2">
                                                    {{ Str::limit($application->description, 80) }}
                                                </p>
                                            @endif

                                            <!-- Show user's roles for this application -->
                                            @php
                                                $userAppRoles = $user->roles->filter(function ($role) use (
                                                    $application,
                                                ) {
                                                    return $application->roles->contains('id', $role->id);
                                                });
                                            @endphp

                                            @if ($userAppRoles->count() > 0)
                                                <div class="mb-2">
                                                    <small class="text-muted">Access via roles:</small><br>
                                                    @foreach ($userAppRoles as $role)
                                                        <span class="badge me-1"
                                                            style="background-color: {{ $role->color ?? '#6c757d' }}">
                                                            {{ $role->display_name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if ($application->url)
                                                <a href="{{ $application->url }}" target="_blank"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-external-link-alt me-1"></i>Visit
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- All Applications & Role Requirements -->
            @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('manager'))
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cog me-2"></i>All Applications & Role Requirements
                        </h5>
                    </div>
                    <div class="card-body">
                        @if ($applications->count() === 0)
                            <p class="text-muted">No applications configured</p>
                        @else
                            @foreach ($applications as $application)
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="{{ $application->icon }} me-2"></i>
                                            <h6 class="mb-0">{{ $application->name }}</h6>
                                        </div>
                                        @if ($user->hasAccessToApplication($application))
                                            <span class="badge bg-success">Has Access</span>
                                        @else
                                            <span class="badge bg-secondary">No Access</span>
                                        @endif
                                    </div>

                                    @if ($application->roles->isNotEmpty())
                                        <small class="text-muted">Required roles:</small><br>
                                        @foreach ($application->roles as $role)
                                            @php
                                                $userHasRole = $user->roles->contains('id', $role->id);
                                            @endphp
                                            <span class="badge me-1 {{ $userHasRole ? 'text-light' : '' }}"
                                                style="background-color: {{ $role->color ?? '#6c757d' }}{{ $userHasRole ? '' : '; opacity: 0.5' }}">
                                                {{ $role->display_name }}
                                                @if ($userHasRole)
                                                    <i class="fas fa-check ms-1"></i>
                                                @endif
                                            </span>
                                        @endforeach
                                    @else
                                        <small class="text-muted">No specific roles required</small>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <!-- HR Information Card -->
            @if ($hrInfo)
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-building me-2"></i>Current HR Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Name</label>
                            <p class="form-control-plaintext">
                                {{ $hrInfo['first_name'] }} {{ $hrInfo['last_name'] }}
                                @if ($hrInfo['first_name'] !== $user->first_name || $hrInfo['last_name'] !== $user->last_name)
                                    <span class="badge bg-warning">Different</span>
                                @endif
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <p class="form-control-plaintext">
                                {{ $hrInfo['email'] ?: 'Not available' }}
                                @if ($hrInfo['email'] && $hrInfo['email'] !== $user->email)
                                    <span class="badge bg-warning">Different</span>
                                @endif
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Department</label>
                            <p class="form-control-plaintext">
                                {{ $hrInfo['department'] ?: 'Not available' }}
                                @if ($hrInfo['department'] && $hrInfo['department'] !== $user->department)
                                    <span class="badge bg-warning">Different</span>
                                @endif
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Title</label>
                            <p class="form-control-plaintext">
                                {{ $hrInfo['title'] ?: 'Not available' }}
                                @if ($hrInfo['title'] && $hrInfo['title'] !== $user->title)
                                    <span class="badge bg-warning">Different</span>
                                @endif
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Supervisor</label>
                            <p class="form-control-plaintext">
                                {{ $hrInfo['supervisor_name'] ?: 'Not available' }}
                                @if ($hrInfo['supervisor_name'] && $hrInfo['supervisor_name'] !== $user->supervisor_name)
                                    <span class="badge bg-warning">Different</span>
                                @endif
                            </p>
                        </div>

                        @if (
                            $hrInfo['first_name'] !== $user->first_name ||
                                $hrInfo['last_name'] !== $user->last_name ||
                                $hrInfo['email'] !== $user->email ||
                                $hrInfo['department'] !== $user->department ||
                                $hrInfo['title'] !== $user->title ||
                                $hrInfo['supervisor_name'] !== $user->supervisor_name)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Differences detected!</strong><br>
                                Some HR information differs from stored user data.
                            </div>
                        @else
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                User data is synchronized with HR.
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>HR Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            <i class="fas fa-info-circle me-2"></i>
                            Could not retrieve HR information for this user.
                        </p>
                        <form method="POST" action="{{ route('users.sync-with-hr', $user) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-info btn-sm">
                                <i class="fas fa-sync me-1"></i>Retry HR Sync
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Navigation Card -->
            <div class="card mt-4">
                <div class="card-body">
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary w-100 mb-2">
                        <i class="fas fa-arrow-left me-1"></i>Back to Users List
                    </a>
                    <a href="{{ route('users.edit', $user) }}" class="btn btn-primary w-100">
                        <i class="fas fa-edit me-1"></i>Edit This User
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Role Modal -->
    @if ($availableRoles->isNotEmpty())
        <div class="modal fade" id="assignRoleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('users.assign-role', $user) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Assign Role to {{ $user->full_name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="role_id" class="form-label">Select Role</label>
                                <select class="form-select" id="role_id" name="role_id" required>
                                    <option value="">Choose a role...</option>
                                    @foreach ($availableRoles as $role)
                                        <option value="{{ $role->id }}">
                                            {{ $role->display_name }}
                                            @if ($role->level)
                                                ({{ ucfirst($role->level) }})
                                            @endif
                                            @if ($role->description)
                                                - {{ Str::limit($role->description, 60) }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Assign Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
