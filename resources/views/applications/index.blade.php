@extends('layouts.app')

@section('title', 'Application Management')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <i class="fas fa-apps me-2"></i>
                            Application Management
                        </h3>
                        @if (auth()->user()->hasRole('admin'))
                            <a href="{{ route('applications.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Create New Application
                            </a>
                        @endif
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Search and Filter Section -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <form method="GET" action="{{ route('applications.index') }}" class="d-flex">
                                    <input type="text" name="search" class="form-control me-2"
                                        placeholder="Search applications..." value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-end align-items-center">
                                    <label class="me-2">Status:</label>
                                    <form method="GET" action="{{ route('applications.index') }}">
                                        <input type="hidden" name="search" value="{{ request('search') }}">
                                        <select name="status" class="form-select form-select-sm" style="width: auto;"
                                            onchange="this.form.submit()">
                                            <option value="">All Statuses</option>
                                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>
                                                Active</option>
                                            <option value="inactive"
                                                {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>

                        @if ($applications->isEmpty())
                            <div class="text-center py-5">
                                <i class="fas fa-apps fa-5x text-muted mb-3"></i>
                                <h4 class="text-muted">No Applications Found</h4>
                                <p class="text-muted">
                                    {{ request('search') ? 'No applications match your search criteria.' : 'Start by creating your first application.' }}
                                </p>
                                <a href="{{ route('applications.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Create First Application
                                </a>
                            </div>
                        @else
                            <div class="row">
                                @foreach ($applications as $application)
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div
                                            class="card h-100 {{ $application->is_active ? 'border-success' : 'border-warning' }}">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    @if ($application->icon)
                                                        <i class="{{ $application->icon }} fa-lg text-primary me-2"></i>
                                                    @else
                                                        <i class="fas fa-cube fa-lg text-muted me-2"></i>
                                                    @endif
                                                    <h6 class="mb-0">{{ Str::limit($application->name, 20) }}</h6>
                                                </div>
                                                <div>
                                                    @if ($application->is_active)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-warning">Inactive</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="card-body d-flex flex-column">
                                                <p class="text-muted flex-grow-1">
                                                    {{ $application->description ? Str::limit($application->description, 100) : 'No description available.' }}
                                                </p>

                                                <div class="mb-3">
                                                    <div class="row text-center">
                                                        <div class="col-6">
                                                            <h6 class="text-primary mb-0">{{ $application->roles_count }}
                                                            </h6>
                                                            <small
                                                                class="text-muted">Role{{ $application->roles_count !== 1 ? 's' : '' }}</small>
                                                        </div>
                                                        <div class="col-6">
                                                            <h6 class="text-info mb-0">
                                                                {{ $application->users_with_access_count }}</h6>
                                                            <small
                                                                class="text-muted">User{{ $application->users_with_access_count !== 1 ? 's' : '' }}</small>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <small class="text-muted">URL Type:</small>
                                                        @switch($application->url_type)
                                                            @case('external')
                                                                <span class="badge bg-primary bg-opacity-25 text-primary">
                                                                    <i class="fas fa-external-link-alt me-1"></i>External
                                                                </span>
                                                            @break

                                                            @case('sub-application')
                                                                <span class="badge bg-info bg-opacity-25 text-info">
                                                                    <i class="fas fa-sitemap me-1"></i>Sub-App
                                                                </span>
                                                            @break

                                                            @case('folder')
                                                                <span class="badge bg-secondary bg-opacity-25 text-secondary">
                                                                    <i class="fas fa-folder me-1"></i>Folder
                                                                </span>
                                                            @break

                                                            @default
                                                                <span class="badge bg-warning">Unknown</span>
                                                        @endswitch
                                                    </div>
                                                    @if ($application->full_url)
                                                        <a href="{{ $application->full_url }}" target="_blank"
                                                            class="btn btn-outline-primary btn-sm w-100">
                                                            <i class="fas fa-external-link-alt me-1"></i>Visit Application
                                                        </a>
                                                    @endif
                                                </div>

                                                <!-- Role Requirements -->
                                                @if ($application->roles->isNotEmpty())
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block mb-1">Required Roles:</small>
                                                        <div>
                                                            @foreach ($application->roles->take(3) as $role)
                                                                <span
                                                                    class="badge bg-light text-dark me-1 mb-1">{{ $role->display_name }}</span>
                                                            @endforeach
                                                            @if ($application->roles->count() > 3)
                                                                <span
                                                                    class="text-muted small">+{{ $application->roles->count() - 3 }}
                                                                    more</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif

                                                <div class="mt-auto">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-muted">
                                                            Created {{ $application->created_at->format('M j, Y') }}
                                                        </small>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="{{ route('applications.show', $application) }}"
                                                                class="btn btn-outline-info" title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            @if (auth()->user()->hasRole('admin'))
                                                                <a href="{{ route('applications.edit', $application) }}"
                                                                    class="btn btn-outline-primary"
                                                                    title="Edit Application">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                @if ($application->roles_count === 0)
                                                                    <button type="button" class="btn btn-outline-danger"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#deleteModal{{ $application->id }}"
                                                                        title="Delete Application">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                @else
                                                                    <button type="button"
                                                                        class="btn btn-outline-secondary"
                                                                        title="Cannot delete application with assigned roles"
                                                                        disabled>
                                                                        <i class="fas fa-lock"></i>
                                                                    </button>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete Modal -->
                                    @if (auth()->user()->hasRole('admin') && $application->roles_count === 0)
                                        <div class="modal fade" id="deleteModal{{ $application->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Delete Application</h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to delete the application
                                                            <strong>{{ $application->name }}</strong>?
                                                        </p>
                                                        <p class="text-muted small">This action cannot be undone.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">Cancel</button>
                                                        <form method="POST"
                                                            action="{{ route('applications.destroy', $application) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger">Delete
                                                                Application</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            <!-- Pagination -->
                            @if ($applications->hasPages())
                                <div class="d-flex justify-content-center mt-4">
                                    {{ $applications->appends(request()->query())->links() }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
