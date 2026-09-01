@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Edit Role: {{ $role->display_name }}
                        </h3>
                        <div>
                            <a href="{{ route('roles.show', $role) }}" class="btn btn-outline-info me-2">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>
                            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Roles
                            </a>
                        </div>
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

                        <form action="{{ route('roles.update', $role) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="display_name" class="form-label">
                                            <i class="fas fa-tag me-1"></i>Display Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text"
                                            class="form-control @error('display_name') is-invalid @enderror"
                                            id="display_name" name="display_name"
                                            value="{{ old('display_name', $role->display_name) }}"
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
                                            id="name" name="name" value="{{ old('name', $role->name) }}"
                                            placeholder="e.g., library_manager" readonly>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">System name cannot be changed after creation.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left me-1"></i>Description
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                    rows="3" placeholder="Describe the responsibilities and permissions of this role...">{{ old('description', $role->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="color" class="form-label">
                                    <i class="fas fa-palette me-1"></i>Role Color <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="color"
                                        class="form-control form-control-color @error('color') is-invalid @enderror"
                                        id="color" name="color" value="{{ old('color', $role->color) }}"
                                        title="Choose role color" required>
                                    <input type="text" class="form-control @error('color') is-invalid @enderror"
                                        id="color_text" value="{{ old('color', $role->color) }}" placeholder="#6c757d"
                                        maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
                                </div>
                                @error('color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">This color will be used for role badges and visual identification.
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                    value="1" {{ old('is_active', $role->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    <i class="fas fa-toggle-on me-1"></i>Active Role
                                </label>
                                <div class="form-text">Inactive roles cannot be assigned to users.</div>
                            </div>

                            <!-- Role Usage Information -->
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Role Usage</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Assigned Users:</strong> {{ $role->users()->count() }}
                                        @if ($role->users()->count() > 0)
                                            <div class="mt-2">
                                                @foreach ($role->users()->limit(3)->get() as $user)
                                                    <span
                                                        class="badge bg-light text-dark me-1">{{ $user->full_name }}</span>
                                                @endforeach
                                                @if ($role->users()->count() > 3)
                                                    <span class="text-muted">and {{ $role->users()->count() - 3 }}
                                                        more...</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Associated Applications:</strong> {{ $role->applications()->count() }}
                                        @if ($role->applications()->count() > 0)
                                            <div class="mt-2">
                                                @foreach ($role->applications()->limit(3)->get() as $application)
                                                    <span
                                                        class="badge bg-light text-dark me-1">{{ $application->name }}</span>
                                                @endforeach
                                                @if ($role->applications()->count() > 3)
                                                    <span class="text-muted">and {{ $role->applications()->count() - 3 }}
                                                        more...</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-between">
                                <div>
                                    @if ($role->users()->count() === 0 && $role->applications()->count() === 0)
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal">
                                            <i class="fas fa-trash me-1"></i>Delete Role
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-outline-secondary"
                                            title="Cannot delete role with assigned users or applications" disabled>
                                            <i class="fas fa-lock me-1"></i>Cannot Delete
                                        </button>
                                    @endif
                                </div>
                                <div>
                                    <a href="{{ route('roles.index') }}" class="btn btn-secondary me-2">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Update Role
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    @if ($role->users()->count() === 0 && $role->applications()->count() === 0)
        <div class="modal fade" id="deleteModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the role <strong>{{ $role->display_name }}</strong>?</p>
                        <p class="text-muted small">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" action="{{ route('roles.destroy', $role) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete Role</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const colorPicker = document.getElementById('color');
            const colorText = document.getElementById('color_text');

            if (colorPicker && colorText) {
                // Update text input when color picker changes
                colorPicker.addEventListener('input', function() {
                    colorText.value = this.value.toUpperCase();
                });

                // Update color picker when text input changes (if valid hex)
                colorText.addEventListener('input', function() {
                    const hexPattern = /^#[0-9A-Fa-f]{6}$/;
                    if (hexPattern.test(this.value)) {
                        colorPicker.value = this.value;
                    }
                });

                // Ensure both inputs start with the same value
                colorText.value = colorPicker.value.toUpperCase();
            }
        });
    </script>
@endpush
