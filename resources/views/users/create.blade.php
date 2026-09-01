@extends('layouts.app')

@section('title', 'Create New User')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Create New User
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <!-- First Name -->
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                        id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- Last Name -->
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                        id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <!-- Net ID -->
                                <div class="mb-3">
                                    <label for="net_id" class="form-label">Net ID <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('net_id') is-invalid @enderror"
                                        id="net_id" name="net_id" value="{{ old('net_id') }}" required>
                                    @error('net_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- Email -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        id="email" name="email" value="{{ old('email') }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <!-- Department -->
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" class="form-control @error('department') is-invalid @enderror"
                                        id="department" name="department" value="{{ old('department') }}">
                                    @error('department')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- Title -->
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                                        id="title" name="title" value="{{ old('title') }}">
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <!-- Supervisor Net ID -->
                                <div class="mb-3">
                                    <label for="supervisor_net_id" class="form-label">Supervisor Net ID</label>
                                    <input type="text"
                                        class="form-control @error('supervisor_net_id') is-invalid @enderror"
                                        id="supervisor_net_id" name="supervisor_net_id"
                                        value="{{ old('supervisor_net_id') }}">
                                    @error('supervisor_net_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- Supervisor Name -->
                                <div class="mb-3">
                                    <label for="supervisor_name" class="form-label">Supervisor Name</label>
                                    <input type="text"
                                        class="form-control @error('supervisor_name') is-invalid @enderror"
                                        id="supervisor_name" name="supervisor_name" value="{{ old('supervisor_name') }}">
                                    @error('supervisor_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Password must be at least 8 characters long.
                            </div>
                        </div>

                        <!-- Roles and Status -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Roles and Status</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        @if (auth()->user()->hasRole('admin'))
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="is_admin"
                                                        name="is_admin" value="1"
                                                        {{ old('is_admin') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="is_admin">
                                                        <strong>Administrator</strong>
                                                        <br><small class="text-muted">Full system access</small>
                                                    </label>
                                                </div>
                                            </div>
                                        @else
                                            <div class="mb-3">
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    <strong>Note:</strong> Only administrators can assign administrator
                                                    privileges.
                                                </div>
                                            </div>
                                        @endif
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_manager"
                                                    name="is_manager" value="1"
                                                    {{ old('is_manager') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_manager">
                                                    <strong>Manager</strong>
                                                    <br><small class="text-muted">Management privileges</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_supervisor"
                                                    name="is_supervisor" value="1"
                                                    {{ old('is_supervisor') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_supervisor">
                                                    <strong>Supervisor</strong>
                                                    <br><small class="text-muted">Supervisory access</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_active"
                                                    name="is_active" value="1"
                                                    {{ old('is_active', true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_active">
                                                    <strong>Active User</strong>
                                                    <br><small class="text-muted">User can access the system</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Roles Section -->
                        @if ($roles->isNotEmpty())
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-tag me-2"></i>Dynamic Role Assignment
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">Select which dynamic roles to assign to this user:</p>
                                    <div class="row">
                                        @foreach ($roles as $role)
                                            <div class="col-md-6 col-lg-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="role_{{ $role->id }}" name="roles[]"
                                                        value="{{ $role->id }}"
                                                        {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="role_{{ $role->id }}">
                                                        <strong>{{ $role->display_name }}</strong>
                                                        @if ($role->level)
                                                            <span
                                                                class="badge bg-secondary ms-1">{{ ucfirst($role->level) }}</span>
                                                        @endif
                                                        @if ($role->description)
                                                            <small
                                                                class="d-block text-muted">{{ Str::limit($role->description, 50) }}</small>
                                                        @endif
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Application Access Section -->
                        @if ($applications->isNotEmpty())
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-key me-2"></i>Application Access Management
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">Grant access to applications by selecting the appropriate
                                        roles for each application:</p>

                                    @foreach ($applications as $application)
                                        @if ($application->roles->isNotEmpty())
                                            <div class="border rounded p-3 mb-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="{{ $application->icon }} me-2 text-primary"></i>
                                                    <h6 class="mb-0">{{ $application->name }}</h6>
                                                </div>
                                                @if ($application->description)
                                                    <p class="text-muted small mb-2">{{ $application->description }}</p>
                                                @endif
                                                <div class="row">
                                                    @foreach ($application->roles as $role)
                                                        <div class="col-md-6 col-lg-4 mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="app_{{ $application->id }}_role_{{ $role->id }}"
                                                                    name="application_roles[{{ $application->id }}][]"
                                                                    value="{{ $role->id }}"
                                                                    {{ in_array($role->id, old('application_roles.' . $application->id, [])) ? 'checked' : '' }}>
                                                                <label class="form-check-label"
                                                                    for="app_{{ $application->id }}_role_{{ $role->id }}">
                                                                    <span class="badge"
                                                                        style="background-color: {{ $role->color ?? '#6c757d' }}">
                                                                        {{ $role->display_name }}
                                                                    </span>
                                                                    @if ($role->description)
                                                                        <small
                                                                            class="text-muted d-block">{{ $role->description }}</small>
                                                                    @endif
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Helper Card -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>Tips
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li><strong>Import from HR:</strong> Consider using the "Import from HR" feature instead if the user
                            exists in the HR system.</li>
                        <li><strong>Net ID:</strong> Should be unique and match the user's university Net ID.</li>
                        <li><strong>Password:</strong> Users can change their password after first login.</li>
                        <li><strong>Roles:</strong> Assign appropriate roles based on the user's responsibilities.</li>
                        <li><strong>Application Access:</strong> Select specific roles for each application to grant
                            targeted access. Users will only see applications they have appropriate roles for.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
