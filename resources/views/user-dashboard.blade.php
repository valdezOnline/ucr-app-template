@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-tachometer-alt me-2"></i>My Dashboard</h2>
                <div class="text-muted">
                    Welcome back, {{ auth()->user()->full_name }}!
                </div>
            </div>

            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <div class="row">
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-th-large me-2"></i>My Applications
                            </h5>
                            <button type="button" id="save-application-order"
                                class="btn btn-primary btn-sm d-none">
                                <i class="fas fa-save me-1"></i>Save Order
                            </button>
                        </div>
                        <div class="card-body">
                            @if ($userApplications->count() > 0)
                                <p class="text-muted small mb-3">
                                    Drag and drop your application tiles to reorder them.
                                </p>

                                <div class="row" id="application-grid">
                                    @foreach ($userApplications as $application)
                                        <div class="col-lg-4 col-md-6 mb-3 draggable-application"
                                            data-application-id="{{ $application->id }}" draggable="true">
                                            <div class="card application-tile h-100">
                                                <div class="card-body text-center d-flex flex-column">
                                                    <div class="drag-handle mb-2" aria-hidden="true">
                                                        <i class="fas fa-grip-vertical text-muted"></i>
                                                    </div>
                                                    <div class="mb-3">
                                                        @if ($application->logo)
                                                            <img src="{{ $application->logo }}"
                                                                alt="{{ $application->name }}"
                                                                class="application-logo mb-2">
                                                        @else
                                                            <div class="application-icon mb-2">
                                                                <i
                                                                    class="{{ $application->icon ?? 'fas fa-cube' }} fa-3x text-primary"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <h6 class="card-title mb-2">{{ $application->name }}</h6>
                                                    <p class="card-text text-muted small flex-grow-1">
                                                        {{ Str::limit($application->description, 80) }}
                                                    </p>
                                                    <div class="mt-auto">
                                                        <a href="{{ $application->url }}" target="_blank"
                                                            class="btn btn-primary btn-sm">
                                                            <i class="fas fa-external-link-alt me-1"></i>Launch
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="fas fa-th-large fa-3x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted">No Applications Available</h5>
                                    <p class="text-muted">
                                        You don't have access to any applications yet. Contact your administrator if you
                                        need access to specific applications.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-link me-2"></i>My Shortcuts
                            </h5>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#shortcutModal">
                                <i class="fas fa-plus me-1"></i>Add Shortcut
                            </button>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="shortcut-list-wrapper mt-2">
                                @if ($shortcutsByCategory->isEmpty())
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-link fa-2x mb-2"></i>
                                        <p class="mb-0">No shortcuts yet. Add links you use often.</p>
                                    </div>
                                @else
                                    @foreach ($shortcutsByCategory as $category => $shortcuts)
                                        <div class="shortcut-category-group mb-3">
                                            <h6 class="shortcut-category-title">{{ $category }}</h6>
                                            <ul class="list-group list-group-flush shortcut-list">
                                                @foreach ($shortcuts as $shortcut)
                                                    <li
                                                        class="list-group-item px-0 d-flex justify-content-between align-items-start">
                                                        <a href="{{ $shortcut['url'] }}"
                                                            @if ($shortcut['type'] === 'external') target="_blank" rel="noopener noreferrer" @endif
                                                            class="shortcut-link">
                                                            {{ $shortcut['name'] }}
                                                            @if ($shortcut['type'] === 'external')
                                                                <i
                                                                    class="fas fa-external-link-alt ms-1 small text-muted"></i>
                                                            @endif
                                                        </a>
                                                        <div class="shortcut-actions d-flex align-items-center">
                                                            <button type="button"
                                                                class="btn btn-link btn-sm text-primary p-0 me-2 edit-shortcut-btn"
                                                                data-shortcut-id="{{ $shortcut['id'] }}"
                                                                data-shortcut-name="{{ $shortcut['name'] }}"
                                                                data-shortcut-type="{{ $shortcut['type'] }}"
                                                                data-shortcut-url="{{ $shortcut['url'] }}"
                                                                data-shortcut-category="{{ $shortcut['category'] ?? '' }}"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#shortcutModal"
                                                                aria-label="Edit shortcut {{ $shortcut['name'] }}">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <form method="POST"
                                                                action="{{ route('dashboard.shortcuts.destroy', $shortcut['id']) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                    class="btn btn-link btn-sm text-danger p-0"
                                                                    aria-label="Remove shortcut {{ $shortcut['name'] }}">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="shortcutModal" tabindex="-1" aria-labelledby="shortcutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shortcutModalLabel">Create Shortcut</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="shortcutForm" method="POST" action="{{ route('dashboard.shortcuts.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="shortcutMethod" value="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="shortcut-name" class="form-label">Shortcut Name</label>
                            <input id="shortcut-name" type="text" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" maxlength="100" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="shortcut-type" class="form-label">Link Type</label>
                            <select id="shortcut-type" name="type"
                                class="form-select @error('type') is-invalid @enderror" required>
                                <option value="internal" @selected(old('type', 'internal') === 'internal')>Internal</option>
                                <option value="external" @selected(old('type') === 'external')>External</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="shortcut-url" class="form-label">URL or Path</label>
                            <input id="shortcut-url" type="text" name="url"
                                class="form-control @error('url') is-invalid @enderror"
                                value="{{ old('url') }}" placeholder="/profile or https://example.com"
                                maxlength="2048" required>
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="shortcut-category" class="form-label">Category (Optional)</label>
                            <input id="shortcut-category" list="shortcut-category-options" type="text" name="category"
                                class="form-control @error('category') is-invalid @enderror"
                                value="{{ old('category') }}" maxlength="80"
                                placeholder="Examples: Daily, HR, Reports">
                            <datalist id="shortcut-category-options">
                                @foreach ($shortcutCategories as $category)
                                    <option value="{{ $category }}"></option>
                                @endforeach
                            </datalist>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-outline-danger d-none" id="shortcutDeleteButton"
                            form="shortcutDeleteForm">
                            <i class="fas fa-trash-alt me-1"></i>Delete Shortcut
                        </button>

                        <div class="d-flex gap-2 ms-auto">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="shortcutSubmitButton">
                                <i class="fas fa-plus me-1"></i>Create Shortcut
                            </button>
                        </div>
                    </div>
                </form>

                <form id="shortcutDeleteForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="_method" value="DELETE">
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <style>
        .application-tile {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .application-tile:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .application-logo {
            max-width: 48px;
            max-height: 48px;
            object-fit: contain;
        }

        .application-icon {
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .draggable-application {
            cursor: grab;
        }

        .draggable-application.dragging {
            opacity: 0.5;
            cursor: grabbing;
        }

        .drag-handle {
            line-height: 1;
        }

        .shortcut-list-wrapper {
            overflow-y: auto;
        }

        .shortcut-list-wrapper .list-group-item:last-child {
            border-bottom: 0;
        }

        .shortcut-actions {
            gap: 0.35rem;
        }

        .shortcut-category-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .shortcut-link {
            color: #0d6efd;
            text-decoration: none;
        }

        .shortcut-link:hover {
            text-decoration: underline;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
        }

        .card-text {
            line-height: 1.4;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const shortcutForm = document.getElementById('shortcutForm');
            const shortcutMethodInput = document.getElementById('shortcutMethod');
            const shortcutModalLabel = document.getElementById('shortcutModalLabel');
            const shortcutSubmitButton = document.getElementById('shortcutSubmitButton');
            const shortcutDeleteForm = document.getElementById('shortcutDeleteForm');
            const shortcutDeleteButton = document.getElementById('shortcutDeleteButton');
            const shortcutNameInput = document.getElementById('shortcut-name');
            const shortcutTypeInput = document.getElementById('shortcut-type');
            const shortcutUrlInput = document.getElementById('shortcut-url');
            const shortcutCategoryInput = document.getElementById('shortcut-category');
            const createShortcutButton = document.querySelector('[data-bs-target="#shortcutModal"]');

            const setCreateMode = () => {
                if (!shortcutForm || !shortcutMethodInput) {
                    return;
                }

                shortcutForm.action = '{{ route('dashboard.shortcuts.store') }}';
                shortcutMethodInput.value = 'POST';
                shortcutModalLabel.textContent = 'Create Shortcut';
                shortcutSubmitButton.innerHTML = '<i class="fas fa-plus me-1"></i>Create Shortcut';
                shortcutNameInput.value = '';
                shortcutTypeInput.value = 'internal';
                shortcutUrlInput.value = '';
                shortcutCategoryInput.value = '';
                if (shortcutDeleteForm && shortcutDeleteButton) {
                    shortcutDeleteForm.action = '';
                    shortcutDeleteButton.classList.add('d-none');
                }
            };

            if (shortcutDeleteButton) {
                shortcutDeleteButton.addEventListener('click', (event) => {
                    if (!shortcutDeleteForm.action) {
                        event.preventDefault();
                        return;
                    }

                    const confirmed = window.confirm('Are you sure you want to delete this shortcut?');
                    if (!confirmed) {
                        event.preventDefault();
                    }
                });
            }

            if (createShortcutButton) {
                createShortcutButton.addEventListener('click', setCreateMode);
            }

            document.querySelectorAll('.edit-shortcut-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!shortcutForm || !shortcutMethodInput || !shortcutDeleteForm || !shortcutDeleteButton) {
                        return;
                    }

                    const shortcutId = button.dataset.shortcutId;
                    shortcutForm.action = `{{ url('/dashboard/shortcuts') }}/${shortcutId}`;
                    shortcutMethodInput.value = 'PUT';
                    shortcutModalLabel.textContent = 'Edit Shortcut';
                    shortcutSubmitButton.innerHTML = '<i class="fas fa-save me-1"></i>Save Shortcut';
                    shortcutNameInput.value = button.dataset.shortcutName || '';
                    shortcutTypeInput.value = button.dataset.shortcutType || 'internal';
                    shortcutUrlInput.value = button.dataset.shortcutUrl || '';
                    shortcutCategoryInput.value = button.dataset.shortcutCategory || '';
                    shortcutDeleteForm.action = `{{ url('/dashboard/shortcuts') }}/${shortcutId}`;
                    shortcutDeleteButton.classList.remove('d-none');
                });
            });

            setCreateMode();

            const applicationGrid = document.getElementById('application-grid');
            const saveOrderButton = document.getElementById('save-application-order');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!applicationGrid || !saveOrderButton || !csrfToken) {
                return;
            }

            let draggedElement = null;
            let hasOrderChanges = false;

            const markOrderChanged = () => {
                if (!hasOrderChanges) {
                    hasOrderChanges = true;
                    saveOrderButton.classList.remove('d-none');
                }
            };

            const draggableItems = applicationGrid.querySelectorAll('.draggable-application');
            draggableItems.forEach((item) => {
                item.addEventListener('dragstart', (event) => {
                    draggedElement = item;
                    item.classList.add('dragging');
                    event.dataTransfer.effectAllowed = 'move';
                });

                item.addEventListener('dragend', () => {
                    item.classList.remove('dragging');
                    draggedElement = null;
                });
            });

            applicationGrid.addEventListener('dragover', (event) => {
                event.preventDefault();
                if (!draggedElement) {
                    return;
                }

                const dropTarget = event.target.closest('.draggable-application');
                if (!dropTarget || dropTarget === draggedElement) {
                    return;
                }

                const targetRect = dropTarget.getBoundingClientRect();
                const insertAfter = event.clientY > (targetRect.top + (targetRect.height / 2));

                if (insertAfter) {
                    applicationGrid.insertBefore(draggedElement, dropTarget.nextSibling);
                } else {
                    applicationGrid.insertBefore(draggedElement, dropTarget);
                }

                markOrderChanged();
            });

            saveOrderButton.addEventListener('click', async () => {
                const applicationIds = Array.from(applicationGrid.querySelectorAll('.draggable-application'))
                    .map((item) => Number.parseInt(item.dataset.applicationId, 10))
                    .filter((id) => Number.isInteger(id));

                saveOrderButton.disabled = true;

                try {
                    const response = await fetch("{{ route('dashboard.applications.order') }}", {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            application_ids: applicationIds
                        })
                    });

                    if (!response.ok) {
                        throw new Error('Unable to save order');
                    }

                    hasOrderChanges = false;
                    saveOrderButton.classList.add('d-none');
                } catch (error) {
                    alert('Unable to save application order. Please try again.');
                } finally {
                    saveOrderButton.disabled = false;
                }
            });
        });
    </script>
@endsection
