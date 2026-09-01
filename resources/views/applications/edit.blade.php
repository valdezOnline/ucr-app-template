@extends('layouts.app')

@section('title', 'Edit Application')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Edit Application: {{ $application->name }}
                        </h3>
                        <div>
                            <a href="{{ route('applications.show', $application) }}" class="btn btn-outline-info me-2">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>
                            <a href="{{ route('applications.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Applications
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

                        <form action="{{ route('applications.update', $application) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">
                                            <i class="fas fa-tag me-1"></i>Application Name <span
                                                class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', $application->name) }}"
                                            placeholder="e.g., Library Catalog System" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="url_type" class="form-label">
                                            <i class="fas fa-sitemap me-1"></i>URL Type <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('url_type') is-invalid @enderror" id="url_type"
                                            name="url_type" required onchange="toggleUrlFields()">
                                            <option value="">Select URL Type</option>
                                            @foreach (\App\Models\Application::getUrlTypeOptions() as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ old('url_type', $application->url_type) === $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('url_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- External URL Field -->
                            <div class="row" id="external-url-field" style="display: none;">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="url" class="form-label">
                                            <i class="fas fa-external-link-alt me-1"></i>External URL <span
                                                class="text-danger">*</span>
                                        </label>
                                        <input type="url" class="form-control @error('url') is-invalid @enderror"
                                            id="url" name="url" value="{{ old('url', $application->url) }}"
                                            placeholder="https://example.com">
                                        @error('url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Complete URL to the external application</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sub-application/Folder Fields -->
                            <div class="row" id="internal-url-fields" style="display: none;">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="base_domain" class="form-label">
                                            <i class="fas fa-globe me-1"></i>Base Domain <span class="text-danger">*</span>
                                        </label>
                                        <input type="url"
                                            class="form-control @error('base_domain') is-invalid @enderror" id="base_domain"
                                            name="base_domain" value="{{ old('base_domain', $application->base_domain) }}"
                                            placeholder="https://myapp.example.com">
                                        @error('base_domain')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">The main domain (e.g., https://myapp.example.com)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sub_path" class="form-label">
                                            <i class="fas fa-folder me-1"></i>Sub-path <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control @error('sub_path') is-invalid @enderror"
                                            id="sub_path" name="sub_path"
                                            value="{{ old('sub_path', $application->sub_path) }}"
                                            placeholder="admin/library">
                                        @error('sub_path')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Path after the domain (e.g., admin/library, reports)</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Preview:</strong> <span id="url-preview">Select base domain and sub-path to
                                            see preview</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left me-1"></i>Description
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                    rows="3" placeholder="Describe what this application does and who should use it...">{{ old('description', $application->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="icon" class="form-label">
                                            <i class="fas fa-icons me-1"></i>Icon Class
                                        </label>
                                        <input type="text" class="form-control @error('icon') is-invalid @enderror"
                                            id="icon" name="icon" value="{{ old('icon', $application->icon) }}"
                                            placeholder="e.g., fas fa-book">
                                        @error('icon')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">
                                            FontAwesome icon class (e.g., fas fa-book, fas fa-cog)
                                            @if ($application->icon)
                                                - Current: <i
                                                    class="{{ $application->icon }} me-1"></i>{{ $application->icon }}
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="logo" class="form-label">
                                            <i class="fas fa-image me-1"></i>Logo Image
                                        </label>
                                        <input type="file" class="form-control @error('logo') is-invalid @enderror"
                                            id="logo" name="logo" accept="image/*">
                                        @error('logo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">
                                            Upload a new logo image (optional)
                                            @if ($application->logo)
                                                <br><small class="text-success">Current logo:
                                                    {{ basename($application->logo) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                        value="1" {{ old('is_active', $application->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        <i class="fas fa-toggle-on me-1"></i>Active Application
                                    </label>
                                    <div class="form-text">Inactive applications won't be visible to users.</div>
                                </div>
                            </div>

                            <!-- Role Assignment Section -->
                            <div class="card bg-light">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-tag me-2"></i>Role Requirements
                                    </h5>
                                    <small class="text-muted">
                                        {{ $application->roles->count() }} role(s) currently assigned
                                    </small>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">Select which roles should have access to this application:
                                    </p>
                                    @if ($roles->isEmpty())
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            No roles available. <a href="{{ route('roles.create') }}">Create roles</a>
                                            first.
                                        </div>
                                    @else
                                        <div class="row">
                                            @foreach ($roles as $role)
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="role_{{ $role->id }}" name="roles[]"
                                                            value="{{ $role->id }}"
                                                            {{ in_array($role->id, old('roles', $application->roles->pluck('id')->toArray())) ? 'checked' : '' }}>
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
                                    @endif
                                </div>
                            </div>

                            <!-- Usage Warning -->
                            @if ($application->roles->count() > 0)
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Note:</strong> This application is currently accessible to users with
                                    {{ $application->roles->count() }} role(s). Removing roles will affect user access.
                                </div>
                            @endif

                            <!-- API Authentication Section -->
                            <div class="card bg-light mt-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-key me-2"></i>API Authentication Setup
                                    </h5>
                                    <div>
                                        @if ($application->supports_api_auth && $application->api_active)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>API Enabled
                                            </span>
                                        @elseif($application->supports_api_auth)
                                            <span class="badge bg-warning">
                                                <i class="fas fa-pause me-1"></i>API Inactive
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-times me-1"></i>API Disabled
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">Configure external API access for this application.</p>

                                    @if ($application->supports_api_auth && $application->api_key)
                                        <div class="alert alert-success mb-3">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <h6><i class="fas fa-info-circle me-2"></i>Current API Status</h6>
                                                    <p class="mb-1"><strong>API Key:</strong>
                                                        {{ Str::mask($application->api_key, '*', 4, -4) }}</p>
                                                    <p class="mb-1">
                                                        <strong>Status:</strong>
                                                        @if ($application->api_active)
                                                            <span class="text-success">Active</span>
                                                        @else
                                                            <span class="text-warning">Inactive</span>
                                                        @endif
                                                    </p>
                                                    <p class="mb-1">
                                                        <strong>Expires:</strong>
                                                        @if ($application->api_key_expires_at)
                                                            {{ $application->api_key_expires_at->format('M d, Y') }}
                                                            @if ($application->isApiExpired())
                                                                <span class="text-danger">(Expired)</span>
                                                            @else
                                                                <span
                                                                    class="text-success">({{ $application->api_key_expires_at->diffForHumans() }})</span>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">Never</span>
                                                        @endif
                                                    </p>
                                                    @if (!empty($application->allowed_domains))
                                                        <p class="mb-0">
                                                            <strong>Allowed Domains:</strong>
                                                            {{ implode(', ', $application->allowed_domains) }}
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <button type="button" class="btn btn-outline-danger btn-sm mb-2"
                                                        onclick="revokeApiAccess()">
                                                        <i class="fas fa-times me-1"></i>Revoke Access
                                                    </button>
                                                    <br>
                                                    <button type="button" class="btn btn-outline-warning btn-sm"
                                                        onclick="regenerateApiCredentials()">
                                                        <i class="fas fa-sync me-1"></i>Regenerate
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="supports_api_auth"
                                                name="supports_api_auth" value="1"
                                                {{ old('supports_api_auth', $application->supports_api_auth) ? 'checked' : '' }}
                                                onchange="toggleApiFields()">
                                            <label class="form-check-label" for="supports_api_auth">
                                                <i class="fas fa-api me-1"></i>Enable API Authentication
                                            </label>
                                            <div class="form-text">Allow external applications to authenticate users
                                                through this system's API.</div>
                                        </div>
                                    </div>

                                    <div id="api-fields" style="display: none;">
                                        @if (!$application->api_key)
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="generate_credentials" class="form-label">
                                                            <i class="fas fa-cogs me-1"></i>Credential Generation
                                                        </label>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="generate_credentials" name="generate_credentials"
                                                                value="1"
                                                                {{ old('generate_credentials', false) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="generate_credentials">
                                                                Generate API credentials now
                                                            </label>
                                                            <div class="form-text">
                                                                Generate new API key and secret. You can also do this later
                                                                using the artisan command.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="api_key_expires_at" class="form-label">
                                                            <i class="fas fa-calendar-alt me-1"></i>API Key Expiration Date
                                                        </label>
                                                        <input type="date"
                                                            class="form-control @error('api_key_expires_at') is-invalid @enderror"
                                                            id="api_key_expires_at" name="api_key_expires_at"
                                                            value="{{ old('api_key_expires_at', now()->addYear()->format('Y-m-d')) }}"
                                                            min="{{ now()->format('Y-m-d') }}">
                                                        @error('api_key_expires_at')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                        <div class="form-text">Leave blank for no expiration (not
                                                            recommended).</div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="mb-3">
                                            <label for="allowed_domains" class="form-label">
                                                <i class="fas fa-globe me-1"></i>Allowed Domains <span
                                                    class="text-muted">(Optional)</span>
                                            </label>
                                            <textarea class="form-control @error('allowed_domains') is-invalid @enderror" id="allowed_domains"
                                                name="allowed_domains" rows="3" placeholder="example.com&#10;app.example.com&#10;subdomain.example.com">{{ old('allowed_domains', !empty($application->allowed_domains) ? implode("\n", $application->allowed_domains) : '') }}</textarea>
                                            @error('allowed_domains')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <strong>Optional:</strong> One domain per line. Leave empty to allow all
                                                domains (less secure).
                                                <br><strong>Examples:</strong> example.com, app.example.com, localhost:3000
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="api_active"
                                                    name="api_active" value="1"
                                                    {{ old('api_active', $application->api_active) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="api_active">
                                                    <i class="fas fa-power-off me-1"></i>API Access Active
                                                </label>
                                                <div class="form-text">Uncheck to temporarily disable API access without
                                                    removing credentials.</div>
                                            </div>
                                        </div>

                                        @if ($application->api_key)
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>Note:</strong> Changes to domain restrictions will take effect
                                                immediately.
                                                Existing API tokens will remain valid until they expire.
                                            </div>
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-lightbulb me-2"></i>
                                                <strong>Note:</strong> If you generate API credentials now, they'll be
                                                displayed once after saving.
                                                Store them securely as the secret cannot be retrieved later.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                        <i class="fas fa-undo me-1"></i>Reset Changes
                                    </button>
                                </div>
                                <div>
                                    <a href="{{ route('applications.show', $application) }}"
                                        class="btn btn-secondary me-2">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Update Application
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
        function resetForm() {
            if (confirm('Are you sure you want to reset all changes? This will revert to the saved values.')) {
                location.reload();
            }
        }

        function toggleApiFields() {
            const apiAuthEnabled = document.getElementById('supports_api_auth').checked;
            const apiFields = document.getElementById('api-fields');

            if (apiAuthEnabled) {
                apiFields.style.display = 'block';
            } else {
                apiFields.style.display = 'none';
            }
        }

        function revokeApiAccess() {
            if (confirm(
                    'Are you sure you want to revoke API access? This will disable all API authentication and cannot be undone without regenerating new credentials.'
                    )) {
                // Create a form to submit the revoke request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('applications.revoke-api', $application) }}';

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);

                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                form.appendChild(methodField);

                document.body.appendChild(form);
                form.submit();
            }
        }

        function regenerateApiCredentials() {
            if (confirm(
                    'Are you sure you want to regenerate API credentials? This will invalidate all existing API tokens and require updating external applications.'
                    )) {
                // Create a form to submit the regenerate request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('applications.regenerate-api', $application) }}';

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);

                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleUrlFields() {
            const urlType = document.getElementById('url_type').value;
            const externalField = document.getElementById('external-url-field');
            const internalFields = document.getElementById('internal-url-fields');

            // Hide all fields initially
            externalField.style.display = 'none';
            internalFields.style.display = 'none';

            // Clear validation states
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            if (urlType === 'external') {
                externalField.style.display = 'block';
            } else if (urlType === 'sub-application' || urlType === 'folder') {
                internalFields.style.display = 'block';
            }

            updateUrlPreview();
        }

        function updateUrlPreview() {
            const urlType = document.getElementById('url_type').value;
            const preview = document.getElementById('url-preview');

            if (urlType === 'sub-application' || urlType === 'folder') {
                const baseDomain = document.getElementById('base_domain').value;
                const subPath = document.getElementById('sub_path').value;

                if (baseDomain && subPath) {
                    const fullUrl = baseDomain.replace(/\/$/, '') + '/' + subPath.replace(/^\//, '');
                    preview.textContent = fullUrl;
                    preview.className = 'text-success fw-bold';
                } else {
                    preview.textContent = 'Select base domain and sub-path to see preview';
                    preview.className = '';
                }
            }
        }

        // Initialize form on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleUrlFields();
            toggleApiFields();

            // Add event listeners for preview updates
            document.getElementById('base_domain').addEventListener('input', updateUrlPreview);
            document.getElementById('sub_path').addEventListener('input', updateUrlPreview);
        });
    </script>
@endsection
