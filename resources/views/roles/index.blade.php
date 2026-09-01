@extends('layouts.app')

@section('title', 'Role Management')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <i class="fas fa-user-tag me-2"></i>
                            Role Management
                        </h3>
                        <a href="{{ route('roles.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Create New Role
                        </a>
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
                                <form method="GET" action="{{ route('roles.index') }}" class="d-flex">
                                    <input type="text" name="search" class="form-control me-2"
                                        placeholder="Search roles..." value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-end align-items-center">
                                    <label class="me-2">Status:</label>
                                    <form method="GET" action="{{ route('roles.index') }}">
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

                        @if ($roles->isEmpty())
                            <div class="text-center py-5">
                                <i class="fas fa-user-tag fa-5x text-muted mb-3"></i>
                                <h4 class="text-muted">No Roles Found</h4>
                                <p class="text-muted">
                                    {{ request('search') ? 'No roles match your search criteria.' : 'Start by creating your first role.' }}
                                </p>
                                <a href="{{ route('roles.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Create First Role
                                </a>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Role Name</th>
                                            <th>Display Name</th>
                                            <th>Description</th>
                                            <th>Users</th>
                                            <th>Applications</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th width="120">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($roles as $role)
                                            <tr>
                                                <td>
                                                    <code class="bg-light px-2 py-1 rounded">{{ $role->name }}</code>
                                                </td>
                                                <td>
                                                    <strong>{{ $role->display_name }}</strong>
                                                </td>
                                                <td>
                                                    <span
                                                        class="text-muted">{{ Str::limit($role->description, 60) }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        {{ $role->users_count }}
                                                        user{{ $role->users_count !== 1 ? 's' : '' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        {{ $role->applications_count }}
                                                        app{{ $role->applications_count !== 1 ? 's' : '' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($role->is_active)
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>Active
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-pause me-1"></i>Inactive
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <small
                                                        class="text-muted">{{ $role->created_at->format('M j, Y') }}</small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('roles.show', $role) }}"
                                                            class="btn btn-outline-info" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('roles.edit', $role) }}"
                                                            class="btn btn-outline-primary" title="Edit Role">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @if ($role->users_count === 0)
                                                            <button type="button" class="btn btn-outline-danger"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteModal{{ $role->id }}"
                                                                title="Delete Role">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        @else
                                                            <button type="button" class="btn btn-outline-secondary"
                                                                title="Cannot delete role with assigned users" disabled>
                                                                <i class="fas fa-lock"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Delete Modal -->
                                            @if ($role->users_count === 0)
                                                <div class="modal fade" id="deleteModal{{ $role->id }}" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Delete Role</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Are you sure you want to delete the role
                                                                    <strong>{{ $role->display_name }}</strong>?</p>
                                                                <p class="text-muted small">This action cannot be undone.
                                                                </p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Cancel</button>
                                                                <form method="POST"
                                                                    action="{{ route('roles.destroy', $role) }}"
                                                                    class="d-inline">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-danger">Delete
                                                                        Role</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            @if ($roles->hasPages())
                                <div class="d-flex justify-content-center">
                                    {{ $roles->appends(request()->query())->links() }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
