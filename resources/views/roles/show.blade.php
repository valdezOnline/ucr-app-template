@extends('layouts.app')

@section('title', 'Role Details')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <i class="fas fa-user-tag me-2"></i>
                            {{ $role->display_name }}
                            @if (!$role->is_active)
                                <span class="badge bg-warning ms-2">Inactive</span>
                            @endif
                        </h3>
                        <div>
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-primary me-2">
                                <i class="fas fa-edit me-1"></i>Edit Role
                            </a>
                            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Roles
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Role Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-info-circle me-2"></i>Role Information
                                        </h5>

                                        <div class="mb-3">
                                            <strong>System Name:</strong>
                                            <code class="bg-white px-2 py-1 rounded ms-2">{{ $role->name }}</code>
                                        </div>

                                        @if ($role->description)
                                            <div class="mb-3">
                                                <strong>Description:</strong>
                                                <p class="mt-1 mb-0">{{ $role->description }}</p>
                                            </div>
                                        @endif

                                        @if ($role->level)
                                            <div class="mb-3">
                                                <strong>Permission Level:</strong>
                                                <span class="badge bg-primary ms-2">{{ ucfirst($role->level) }}</span>
                                            </div>
                                        @endif

                                        @if ($role->department)
                                            <div class="mb-3">
                                                <strong>Department:</strong>
                                                <span class="ms-2">{{ $role->department }}</span>
                                            </div>
                                        @endif

                                        <div class="mb-3">
                                            <strong>Status:</strong>
                                            @if ($role->is_active)
                                                <span class="badge bg-success ms-2">
                                                    <i class="fas fa-check me-1"></i>Active
                                                </span>
                                            @else
                                                <span class="badge bg-warning ms-2">
                                                    <i class="fas fa-pause me-1"></i>Inactive
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mb-3">
                                            <strong>Can Manage Users:</strong>
                                            @if ($role->can_manage_users)
                                                <span class="badge bg-success ms-2">
                                                    <i class="fas fa-check me-1"></i>Yes
                                                </span>
                                            @else
                                                <span class="badge bg-secondary ms-2">
                                                    <i class="fas fa-times me-1"></i>No
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mb-0">
                                            <strong>Created:</strong>
                                            <span class="ms-2">{{ $role->created_at->format('M j, Y \a\t g:i A') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-chart-bar me-2"></i>Usage Statistics
                                        </h5>

                                        <div class="row text-center">
                                            <div class="col-6">
                                                <div class="border-end">
                                                    <h3 class="text-primary mb-1">{{ $role->users()->count() }}</h3>
                                                    <small class="text-muted">
                                                        User{{ $role->users()->count() !== 1 ? 's' : '' }} Assigned
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <h3 class="text-info mb-1">{{ $role->applications()->count() }}</h3>
                                                <small class="text-muted">
                                                    Application{{ $role->applications()->count() !== 1 ? 's' : '' }}
                                                    Accessible
                                                </small>
                                            </div>
                                        </div>

                                        @if ($role->created_by)
                                            <hr>
                                            <div class="mb-0">
                                                <strong>Created by:</strong>
                                                <span
                                                    class="ms-2">{{ $role->creator->full_name ?? 'Unknown User' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assigned Users -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <i class="fas fa-users me-2"></i>
                                            Assigned Users ({{ $role->users()->count() }})
                                        </h5>
                                        @if ($role->is_active)
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal" data-bs-target="#assignUserModal">
                                                <i class="fas fa-plus me-1"></i>Assign User
                                            </button>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        @if ($role->users()->count() === 0)
                                            <div class="text-center py-4">
                                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                <h6 class="text-muted">No Users Assigned</h6>
                                                <p class="text-muted">This role has not been assigned to any users yet.</p>
                                            </div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>User</th>
                                                            <th>Email</th>
                                                            <th>Department</th>
                                                            <th>Assigned Date</th>
                                                            <th>Assigned By</th>
                                                            <th width="100">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($role->users as $user)
                                                            <tr>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <div>
                                                                            <strong>{{ $user->full_name }}</strong>
                                                                            <small
                                                                                class="d-block text-muted">{{ $user->net_id }}</small>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>{{ $user->email }}</td>
                                                                <td>
                                                                    <span
                                                                        class="text-muted">{{ $user->department ?? 'N/A' }}</span>
                                                                </td>
                                                                <td>
                                                                    <small>{{ $user->pivot->assigned_at ? $user->pivot->assigned_at->format('M j, Y') : 'N/A' }}</small>
                                                                </td>
                                                                <td>
                                                                    @if ($user->pivot->assigned_by)
                                                                        <small>{{ \App\Models\User::find($user->pivot->assigned_by)?->full_name ?? 'Unknown' }}</small>
                                                                    @else
                                                                        <small class="text-muted">System</small>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <form method="POST"
                                                                        action="{{ route('roles.remove-user', $role) }}"
                                                                        class="d-inline">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <input type="hidden" name="user_id"
                                                                            value="{{ $user->id }}">
                                                                        <button type="submit"
                                                                            class="btn btn-sm btn-outline-danger"
                                                                            title="Remove user from role"
                                                                            onclick="return confirm('Remove {{ $user->full_name }} from this role?')">
                                                                            <i class="fas fa-times"></i>
                                                                        </button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Associated Applications -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-apps me-2"></i>
                                            Associated Applications ({{ $role->applications()->count() }})
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        @if ($role->applications()->count() === 0)
                                            <div class="text-center py-4">
                                                <i class="fas fa-apps fa-3x text-muted mb-3"></i>
                                                <h6 class="text-muted">No Applications Associated</h6>
                                                <p class="text-muted">This role does not provide access to any applications
                                                    yet.</p>
                                            </div>
                                        @else
                                            <div class="row">
                                                @foreach ($role->applications as $application)
                                                    <div class="col-md-6 col-lg-4 mb-3">
                                                        <div
                                                            class="card border-start border-3 {{ $application->is_active ? 'border-success' : 'border-warning' }}">
                                                            <div class="card-body">
                                                                <div class="d-flex align-items-center mb-2">
                                                                    @if ($application->icon)
                                                                        <i
                                                                            class="{{ $application->icon }} fa-2x text-primary me-3"></i>
                                                                    @else
                                                                        <i class="fas fa-cube fa-2x text-muted me-3"></i>
                                                                    @endif
                                                                    <div>
                                                                        <h6 class="mb-0">{{ $application->name }}</h6>
                                                                        @if (!$application->is_active)
                                                                            <small
                                                                                class="badge bg-warning">Inactive</small>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                @if ($application->description)
                                                                    <p class="text-muted small mb-2">
                                                                        {{ Str::limit($application->description, 80) }}</p>
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign User Modal -->
    @if ($role->is_active)
        <div class="modal fade" id="assignUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('roles.assign-user', $role) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Assign User to Role</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="user_id" class="form-label">Select User</label>
                                <select class="form-select" id="user_id" name="user_id" required>
                                    <option value="">Choose a user...</option>
                                    @foreach ($availableUsers as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->full_name }} ({{ $user->net_id }}) - {{ $user->email }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($availableUsers->isEmpty())
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    All active users have already been assigned to this role.
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"
                                {{ $availableUsers->isEmpty() ? 'disabled' : '' }}>
                                <i class="fas fa-plus me-1"></i>Assign User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
