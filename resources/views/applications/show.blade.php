@extends('layouts.app')

@section('title', 'Application Details')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            @if ($application->icon)
                                <i class="{{ $application->icon }} me-2"></i>
                            @else
                                <i class="fas fa-cube me-2"></i>
                            @endif
                            {{ $application->name }}
                            @if (!$application->is_active)
                                <span class="badge bg-warning ms-2">Inactive</span>
                            @endif
                        </h3>
                        <div>
                            @if (auth()->user()->hasRole('admin'))
                                <a href="{{ route('applications.edit', $application) }}" class="btn btn-primary me-2">
                                    <i class="fas fa-edit me-1"></i>Edit Application
                                </a>
                            @endif
                            @if ($application->full_url)
                                <a href="{{ $application->full_url }}" target="_blank" class="btn btn-success me-2">
                                    <i class="fas fa-external-link-alt me-1"></i>Visit Application
                                </a>
                            @endif
                            <a href="{{ route('applications.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Applications
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

                        @if (session('api_credentials'))
                            <div class="alert alert-info alert-dismissible fade show">
                                <h5><i class="fas fa-key me-2"></i>API Credentials Generated</h5>
                                <p><strong>Important:</strong> This is the only time these credentials will be displayed.
                                    Please store them securely.</p>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><strong>API Key:</strong></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control"
                                                    value="{{ session('api_credentials')['api_key'] }}" id="api-key"
                                                    readonly>
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="copyToClipboard('api-key', this)">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label"><strong>API Secret:</strong></label>
                                            <div class="input-group">
                                                <input type="password" class="form-control"
                                                    value="{{ session('api_credentials')['api_secret'] }}" id="api-secret"
                                                    readonly>
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePassword('api-secret', this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="copyToClipboard('api-secret', this)">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-warning mt-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Security Note:</strong> Store these credentials in a secure location. The API
                                    secret will be hashed and cannot be retrieved after you leave this page.
                                </div>

                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Application Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-info-circle me-2"></i>Application Information
                                        </h5>

                                        @if ($application->description)
                                            <div class="mb-3">
                                                <strong>Description:</strong>
                                                <p class="mt-1 mb-0">{{ $application->description }}</p>
                                            </div>
                                        @endif

                                        <div class="mb-3">
                                            <strong>URL Type:</strong>
                                            <span class="ms-2">
                                                @switch($application->url_type)
                                                    @case('external')
                                                        <span class="badge bg-primary">
                                                            <i class="fas fa-external-link-alt me-1"></i>External Website
                                                        </span>
                                                    @break

                                                    @case('sub-application')
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-sitemap me-1"></i>Sub-Application
                                                        </span>
                                                    @break

                                                    @case('folder')
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-folder me-1"></i>Folder View
                                                        </span>
                                                    @break

                                                    @default
                                                        <span class="badge bg-warning">Unknown</span>
                                                @endswitch
                                            </span>
                                        </div>

                                        @if ($application->full_url)
                                            <div class="mb-3">
                                                <strong>Application URL:</strong>
                                                <div class="mt-1">
                                                    <a href="{{ $application->full_url }}" target="_blank"
                                                        class="btn btn-sm btn-outline-success">
                                                        {{ $application->full_url }}
                                                        <i class="fas fa-external-link-alt ms-1"></i>
                                                    </a>
                                                </div>
                                                @if ($application->url_type !== 'external')
                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            <strong>Base Domain:</strong>
                                                            {{ $application->base_domain }}<br>
                                                            <strong>Sub-path:</strong> {{ $application->sub_path }}
                                                        </small>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        <div class="mb-3">
                                            <strong>Status:</strong>
                                            @if ($application->is_active)
                                                <span class="badge bg-success ms-2">
                                                    <i class="fas fa-check me-1"></i>Active
                                                </span>
                                            @else
                                                <span class="badge bg-warning ms-2">
                                                    <i class="fas fa-pause me-1"></i>Inactive
                                                </span>
                                            @endif
                                        </div>

                                        @if ($application->icon)
                                            <div class="mb-3">
                                                <strong>Icon:</strong>
                                                <span class="ms-2">
                                                    <i class="{{ $application->icon }} me-1"></i>
                                                    <code>{{ $application->icon }}</code>
                                                </span>
                                            </div>
                                        @endif

                                        <div class="mb-0">
                                            <strong>Created:</strong>
                                            <span
                                                class="ms-2">{{ $application->created_at->format('M j, Y \\a\\t g:i A') }}</span>
                                        </div>

                                        <hr>

                                        <div class="mb-2">
                                            <strong>External CAS Broker:</strong>
                                            @if ($application->external_auth_enabled)
                                                <span class="badge bg-success ms-2">Enabled</span>
                                            @else
                                                <span class="badge bg-secondary ms-2">Disabled</span>
                                            @endif
                                        </div>

                                        @if ($application->external_auth_enabled)
                                            <div class="mb-0">
                                                <strong>Broker Callback URL:</strong>
                                                <div class="small text-muted mt-1">
                                                    {{ $application->external_auth_callback_url ?: 'Not configured' }}
                                                </div>
                                            </div>
                                        @endif
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
                                                    <h3 class="text-primary mb-1">{{ $application->roles()->count() }}
                                                    </h3>
                                                    <small class="text-muted">
                                                        Role{{ $application->roles()->count() !== 1 ? 's' : '' }} Required
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <h3 class="text-info mb-1">{{ $usersWithAccess }}</h3>
                                                <small class="text-muted">
                                                    User{{ $usersWithAccess !== 1 ? 's' : '' }} with Access
                                                </small>
                                            </div>
                                        </div>

                                        @if ($application->created_by)
                                            <hr>
                                            <div class="mb-0">
                                                <strong>Created by:</strong>
                                                <span
                                                    class="ms-2">{{ $application->creator->full_name ?? 'Unknown User' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Role Requirements -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <i class="fas fa-user-tag me-2"></i>
                                            Role Requirements ({{ $application->roles()->count() }})
                                        </h5>
                                        @if (auth()->user()->hasRole('admin'))
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal" data-bs-target="#assignRoleModal">
                                                <i class="fas fa-plus me-1"></i>Assign Role
                                            </button>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        @if ($application->roles()->count() === 0)
                                            <div class="text-center py-4">
                                                <i class="fas fa-user-tag fa-3x text-muted mb-3"></i>
                                                <h6 class="text-muted">No Role Requirements</h6>
                                                <p class="text-muted">This application has no role requirements. It won't
                                                    be
                                                    visible to any users.</p>
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                    data-bs-target="#assignRoleModal">
                                                    <i class="fas fa-plus me-1"></i>Add First Role Requirement
                                                </button>
                                            </div>
                                        @else
                                            <div class="row">
                                                @foreach ($application->roles as $role)
                                                    <div class="col-md-6 col-lg-4 mb-3">
                                                        <div
                                                            class="card border-start border-3 {{ $role->is_active ? 'border-success' : 'border-warning' }}">
                                                            <div class="card-body">
                                                                <div
                                                                    class="d-flex justify-content-between align-items-start mb-2">
                                                                    <div>
                                                                        <h6 class="mb-0">{{ $role->display_name }}</h6>
                                                                        @if ($role->level)
                                                                            <span
                                                                                class="badge bg-secondary">{{ ucfirst($role->level) }}</span>
                                                                        @endif
                                                                        @if (!$role->is_active)
                                                                            <span class="badge bg-warning">Inactive</span>
                                                                        @endif
                                                                    </div>
                                                                    @if (auth()->user()->hasRole('admin'))
                                                                        <form method="POST"
                                                                            action="{{ route('applications.remove-role', $application) }}"
                                                                            class="d-inline">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <input type="hidden" name="role_id"
                                                                                value="{{ $role->id }}">
                                                                            <button type="submit"
                                                                                class="btn btn-sm btn-outline-danger"
                                                                                title="Remove role requirement"
                                                                                onclick="return confirm('Remove {{ $role->display_name }} from this application?')">
                                                                                <i class="fas fa-times"></i>
                                                                            </button>
                                                                        </form>
                                                                    @endif
                                                                </div>
                                                                @if ($role->description)
                                                                    <p class="text-muted small mb-2">
                                                                        {{ Str::limit($role->description, 80) }}</p>
                                                                @endif
                                                                <div
                                                                    class="d-flex justify-content-between align-items-center">
                                                                    <small class="text-muted">
                                                                        {{ $role->users()->count() }}
                                                                        user{{ $role->users()->count() !== 1 ? 's' : '' }}
                                                                    </small>
                                                                    @if ($role->pivot->assigned_at)
                                                                        <small class="text-muted">
                                                                            Added
                                                                            {{ $role->pivot->assigned_at->format('M j, Y') }}
                                                                        </small>
                                                                    @endif
                                                                </div>
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

                        <!-- Users with Access -->
                        @if ($application->roles()->count() > 0)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                <i class="fas fa-users me-2"></i>
                                                Users with Access ({{ $usersWithAccess }})
                                            </h5>
                                            @if (auth()->user()->hasRole('admin') || auth()->user()->isManager())
                                                <button type="button" class="btn btn-sm btn-outline-success"
                                                    data-bs-toggle="modal" data-bs-target="#assignUserModal">
                                                    <i class="fas fa-user-plus me-1"></i>Assign User
                                                </button>
                                            @endif
                                        </div>
                                        <div class="card-body">
                                            @if ($usersWithAccessDetailed->isEmpty())
                                                <div class="text-center py-4">
                                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                    <h6 class="text-muted">No Users Assigned</h6>
                                                    <p class="text-muted">No users have been assigned roles that provide
                                                        access to this application.</p>
                                                    @if (auth()->user()->hasRole('admin') || auth()->user()->isManager())
                                                        <button type="button" class="btn btn-success"
                                                            data-bs-toggle="modal" data-bs-target="#assignUserModal">
                                                            <i class="fas fa-user-plus me-1"></i>Assign First User
                                                        </button>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="row">
                                                    @foreach ($usersWithAccessDetailed as $userWithAccess)
                                                        <div class="col-md-6 col-lg-4 mb-3">
                                                            <div class="card border-start border-3 border-info">
                                                                <div class="card-body">
                                                                    <div
                                                                        class="d-flex justify-content-between align-items-start mb-2">
                                                                        <div>
                                                                            <h6 class="mb-0">
                                                                                {{ $userWithAccess->full_name }}</h6>
                                                                            <small
                                                                                class="text-muted">{{ $userWithAccess->net_id }}</small>
                                                                            @if (!$userWithAccess->is_active)
                                                                                <span
                                                                                    class="badge bg-warning">Inactive</span>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                    @if ($userWithAccess->department)
                                                                        <p class="text-muted small mb-2">
                                                                            {{ $userWithAccess->department }}</p>
                                                                    @endif
                                                                    <div class="mb-2">
                                                                        @foreach ($userWithAccess->applicableRoles as $role)
                                                                            <span
                                                                                class="badge bg-primary me-1 mb-1">{{ $role->display_name }}</span>
                                                                        @endforeach
                                                                    </div>
                                                                    @if (auth()->user()->hasRole('admin') || auth()->user()->isManager())
                                                                        <div class="dropdown">
                                                                            <button
                                                                                class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                                                type="button" data-bs-toggle="dropdown"
                                                                                aria-expanded="false">
                                                                                <i class="fas fa-cog me-1"></i>Manage
                                                                                Access
                                                                            </button>
                                                                            <ul class="dropdown-menu">
                                                                                @foreach ($userWithAccess->applicableRoles as $role)
                                                                                    <li>
                                                                                        <form method="POST"
                                                                                            action="{{ route('applications.remove-user', $application) }}"
                                                                                            class="d-inline">
                                                                                            @csrf
                                                                                            @method('DELETE')
                                                                                            <input type="hidden"
                                                                                                name="user_id"
                                                                                                value="{{ $userWithAccess->id }}">
                                                                                            <input type="hidden"
                                                                                                name="role_id"
                                                                                                value="{{ $role->id }}">
                                                                                            <button type="submit"
                                                                                                class="dropdown-item text-danger"
                                                                                                onclick="return confirm('Remove {{ $role->display_name }} role from {{ $userWithAccess->full_name }}?')">
                                                                                                <i
                                                                                                    class="fas fa-times me-1"></i>Remove
                                                                                                {{ $role->display_name }}
                                                                                            </button>
                                                                                        </form>
                                                                                    </li>
                                                                                @endforeach
                                                                            </ul>
                                                                        </div>
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
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Role Modal -->
    @if (auth()->user()->hasRole('admin'))
        <div class="modal fade" id="assignRoleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('applications.assign-role', $application) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Assign Role to Application</h5>
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
                            @if ($availableRoles->isEmpty())
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    All active roles have already been assigned to this application.
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"
                                {{ $availableRoles->isEmpty() ? 'disabled' : '' }}>
                                <i class="fas fa-plus me-1"></i>Assign Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Assign User Modal -->
    <div class="modal fade" id="assignUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('applications.assign-user', $application) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Assign User to Application</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Select User</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">Choose a user...</option>
                                @foreach (\App\Models\User::active()->orderBy('last_name')->orderBy('first_name')->get() as $availableUser)
                                    <option value="{{ $availableUser->id }}">
                                        {{ $availableUser->full_name }} ({{ $availableUser->net_id }})
                                        @if ($availableUser->department)
                                            - {{ $availableUser->department }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="role_id" class="form-label">Select Role</label>
                            <select class="form-select" id="assign_role_id" name="role_id" required>
                                <option value="">Choose a role...</option>
                                @foreach ($application->roles as $appRole)
                                    <option value="{{ $appRole->id }}">
                                        {{ $appRole->display_name }}
                                        @if ($appRole->description)
                                            - {{ Str::limit($appRole->description, 60) }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Only roles that are assigned to this application are available.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-user-plus me-1"></i>Assign User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(elementId, button) {
            const element = document.getElementById(elementId);
            element.select();
            element.setSelectionRange(0, 99999); // For mobile devices

            try {
                document.execCommand('copy');
                const originalHtml = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check text-success"></i>';
                setTimeout(() => {
                    button.innerHTML = originalHtml;
                }, 2000);
            } catch (err) {
                console.error('Failed to copy: ', err);
            }
        }

        function togglePassword(elementId, button) {
            const element = document.getElementById(elementId);
            const icon = button.querySelector('i');

            if (element.type === 'password') {
                element.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                element.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
@endsection
