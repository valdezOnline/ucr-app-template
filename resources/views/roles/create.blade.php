@extends('layouts.app')

@section('title', 'Create New Role')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>
                            Create New Role
                        </h3>
                        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Roles
                        </a>
                    </div>

                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('roles.store') }}" method="POST">
                            @csrf

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="display_name" class="form-label">
                                            <i class="fas fa-tag me-1"></i>Display Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text"
                                            class="form-control @error('display_name') is-invalid @enderror"
                                            id="display_name" name="display_name" value="{{ old('display_name') }}"
                                            placeholder="e.g., Library Manager" required>
                                        @error('display_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">This is the human-readable name shown to users.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">
                                            <i class="fas fa-code me-1"></i>System Name
                                        </label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name') }}"
                                            placeholder="e.g., library_manager" readonly>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Auto-generated from display name. Used internally.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left me-1"></i>Description
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                    rows="3" placeholder="Describe the responsibilities and permissions of this role...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="level" class="form-label">
                                            <i class="fas fa-layer-group me-1"></i>Permission Level
                                        </label>
                                        <select class="form-select @error('level') is-invalid @enderror" id="level"
                                            name="level">
                                            <option value="">Select permission level...</option>
                                            <option value="basic" {{ old('level') === 'basic' ? 'selected' : '' }}>
                                                Basic - Limited access to essential features
                                            </option>
                                            <option value="intermediate"
                                                {{ old('level') === 'intermediate' ? 'selected' : '' }}>
                                                Intermediate - Moderate access with some management features
                                            </option>
                                            <option value="advanced" {{ old('level') === 'advanced' ? 'selected' : '' }}>
                                                Advanced - Full access with administrative capabilities
                                            </option>
                                            <option value="supervisor"
                                                {{ old('level') === 'supervisor' ? 'selected' : '' }}>
                                                Supervisor - Can manage users and assignments
                                            </option>
                                            <option value="admin" {{ old('level') === 'admin' ? 'selected' : '' }}>
                                                Administrator - Complete system control
                                            </option>
                                        </select>
                                        @error('level')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="department" class="form-label">
                                            <i class="fas fa-building me-1"></i>Department/Area
                                        </label>
                                        <input type="text" class="form-control @error('department') is-invalid @enderror"
                                            id="department" name="department" value="{{ old('department') }}"
                                            placeholder="e.g., Library Services, IT Support">
                                        @error('department')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Optional: Specify which department this role belongs to.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                            value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            <i class="fas fa-toggle-on me-1"></i>Active Role
                                        </label>
                                        <div class="form-text">Inactive roles cannot be assigned to users.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="can_manage_users"
                                            name="can_manage_users" value="1"
                                            {{ old('can_manage_users') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="can_manage_users">
                                            <i class="fas fa-users-cog me-1"></i>Can Manage Users
                                        </label>
                                        <div class="form-text">Allow users with this role to manage other users.</div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                        <i class="fas fa-undo me-1"></i>Reset Form
                                    </button>
                                </div>
                                <div>
                                    <a href="{{ route('roles.index') }}" class="btn btn-secondary me-2">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Create Role
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const displayNameInput = document.getElementById('display_name');
            const nameInput = document.getElementById('name');

            displayNameInput.addEventListener('input', function() {
                // Auto-generate system name from display name
                let systemName = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s]/g, '') // Remove special characters
                    .replace(/\s+/g, '_') // Replace spaces with underscores
                    .replace(/^_+|_+$/g, ''); // Remove leading/trailing underscores

                nameInput.value = systemName;
            });
        });

        function resetForm() {
            if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
                document.querySelector('form').reset();
                document.getElementById('name').value = '';
            }
        }
    </script>
@endsection
