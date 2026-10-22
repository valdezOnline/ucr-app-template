@extends('layouts.app')

@section('title', 'User Management')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-users me-2"></i>User Management</h2>
                <div class="btn-group">
                    <a href="{{ route('users.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add New User
                    </a>
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#importUserModal">
                        <i class="fas fa-download me-1"></i>Import from HR
                    </button>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('users.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search"
                                value="{{ request('search') }}" placeholder="Name, Net ID, Email, or Department">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active
                                </option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="">All Roles</option>
                                <optgroup label="Legacy Roles">
                                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin
                                    </option>
                                    <option value="manager" {{ request('role') === 'manager' ? 'selected' : '' }}>Manager
                                    </option>
                                    <option value="supervisor" {{ request('role') === 'supervisor' ? 'selected' : '' }}>
                                        Supervisor</option>
                                </optgroup>
                                @if ($availableRoles->isNotEmpty())
                                    <optgroup label="Dynamic Roles">
                                        @foreach ($availableRoles as $role)
                                            <option value="{{ $role->name }}"
                                                {{ request('role') === $role->name ? 'selected' : '' }}>
                                                {{ $role->display_name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-body">
                    @if ($users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Net ID</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Title</th>
                                        <th>Roles</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $user)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div
                                                        class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                        {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <strong>{{ $user->full_name }}</strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <code>{{ $user->net_id }}</code>
                                            </td>
                                            <td>
                                                <a href="mailto:{{ $user->email }}" class="text-decoration-none">
                                                    {{ $user->email }}
                                                </a>
                                            </td>
                                            <td>{{ $user->department ?: '-' }}</td>
                                            <td>{{ $user->title ?: '-' }}</td>
                                            <td>
                                                @if (count($user->getRoles()) > 0)
                                                    @foreach ($user->getRoles() as $role)
                                                        <span class="badge bg-secondary me-1">{{ $role }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">No roles</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($user->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('users.show', $user) }}"
                                                        class="btn btn-sm btn-outline-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('users.edit', $user) }}"
                                                        class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" action="{{ route('users.toggle-status', $user) }}"
                                                        class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit"
                                                            class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }}"
                                                            title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}"
                                                            {{ $user->id === auth()->id() && $user->is_active ? 'disabled' : '' }}>
                                                            <i
                                                                class="fas fa-{{ $user->is_active ? 'user-slash' : 'user-check' }}"></i>
                                                        </button>
                                                    </form>
                                                    @if (
                                                        $user->id !== auth()->id() &&
                                                            auth()->user()->hasRole('admin') &&
                                                            !$user->hasRole('admin') &&
                                                            !$user->is_admin &&
                                                            $user->is_active &&
                                                            !session('impersonation.active'))
                                                        <form method="POST"
                                                            action="{{ route('admin.impersonation.start', $user) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                                title="Login as this user"
                                                                onclick="return confirm('Start impersonating {{ $user->full_name }}?')">
                                                                <i class="fas fa-user-secret"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if ($user->id !== auth()->id() && auth()->user()->hasRole('admin'))
                                                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                title="Delete"
                                                                onclick="return confirm('Are you sure you want to delete this user?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of
                                {{ $users->total() }} users
                            </div>
                            {{ $users->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No users found</h5>
                            <p class="text-muted">Try adjusting your search criteria or add a new user.</p>
                            <a href="{{ route('users.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Add New User
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Import User Modal -->
    <div class="modal fade" id="importUserModal" tabindex="-1" aria-labelledby="importUserModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('users.import-from-hr') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="importUserModalLabel">Import User from HR</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="net_id" class="form-label">Net ID</label>
                            <input type="text" class="form-control" id="net_id" name="net_id" required
                                placeholder="Enter the user's Net ID">
                            <div class="form-text">
                                Enter the Net ID of the user you want to import from HR system.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download me-1"></i>Import User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <style>
        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 0.75rem;
            font-weight: 500;
        }
    </style>
@endsection
