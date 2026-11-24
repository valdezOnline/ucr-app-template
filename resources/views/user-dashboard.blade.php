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
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0">
                                <i class="fas fa-th-large me-2"></i>My Applications
                            </h5>
                            <div class="d-flex align-items-center gap-2">
                                <div class="btn-group btn-group-sm" role="group">
                                    <input type="radio" class="btn-check display-mode-toggle" name="displayMode"
                                        id="displayModeNormal" value="normal" {{ $displayMode === 'normal' ? 'checked' : '' }}
                                        data-bs-toggle="tooltip" title="Normal Tile View">
                                    <label class="btn btn-outline-secondary" for="displayModeNormal">
                                        <i class="fas fa-th-large"></i>
                                    </label>

                                    <input type="radio" class="btn-check display-mode-toggle" name="displayMode"
                                        id="displayModeSmall" value="small" {{ $displayMode === 'small' ? 'checked' : '' }}
                                        data-bs-toggle="tooltip" title="Small Tile View">
                                    <label class="btn btn-outline-secondary" for="displayModeSmall">
                                        <i class="fas fa-th"></i>
                                    </label>

                                    <input type="radio" class="btn-check display-mode-toggle" name="displayMode"
                                        id="displayModeText" value="text" {{ $displayMode === 'text' ? 'checked' : '' }}
                                        data-bs-toggle="tooltip" title="Text Only View">
                                    <label class="btn btn-outline-secondary" for="displayModeText">
                                        <i class="fas fa-list"></i>
                                    </label>
                                </div>
                                <div class="btn-group btn-group-sm" role="group"
                                    aria-label="Application layout width">
                                    <input type="radio" class="btn-check dashboard-width-toggle" name="dashboardWidth"
                                        id="dashboardWidthContainer" value="container"
                                        {{ !$dashboardFullWidth ? 'checked' : '' }}
                                        data-bs-toggle="tooltip" title="Use centered container width">
                                    <label class="btn btn-outline-secondary" for="dashboardWidthContainer">
                                        <i class="fas fa-compress-alt"></i>
                                        <span class="visually-hidden">Centered width</span>
                                    </label>

                                    <input type="radio" class="btn-check dashboard-width-toggle" name="dashboardWidth"
                                        id="dashboardWidthFull" value="full"
                                        {{ $dashboardFullWidth ? 'checked' : '' }}
                                        data-bs-toggle="tooltip" title="Use full application width">
                                    <label class="btn btn-outline-secondary" for="dashboardWidthFull">
                                        <i class="fas fa-expand-alt"></i>
                                        <span class="visually-hidden">Full width</span>
                                    </label>
                                </div>
                                <button type="button" id="toggle-dashboard-lock"
                                    class="btn btn-secondary btn-sm"
                                    data-bs-toggle="tooltip"
                                    title="{{ auth()->user()->dashboard_locked ? 'Unlock dashboard to edit' : 'Lock dashboard to prevent accidental changes' }}">
                                    <i class="fas {{ auth()->user()->dashboard_locked ? 'fa-lock' : 'fa-unlock' }} me-1"></i><span class="lock-status-text">{{ auth()->user()->dashboard_locked ? 'Locked' : 'Unlocked' }}</span>
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#groupsModal">
                                    <i class="fas fa-folder me-1"></i>Groups
                                </button>
                                <button type="button" id="save-application-order"
                                    class="btn btn-primary btn-sm d-none">
                                    <i class="fas fa-save me-1"></i>Save Order
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            @if ($userApplications->count() > 0)
                                <div id="applications-container" class="applications-display-{{ $displayMode }}">
                                    @php
                                        $hasMultipleGroups = !empty($groupedApplications) && count($groupedApplications) > 1;
                                    @endphp
                                    @if ($hasMultipleGroups && $groupDisplayMode === 'tabs')
                                        <ul class="nav nav-tabs mb-3" id="application-group-tabs" role="tablist">
                                            @foreach ($groupedApplications as $groupId => $groupData)
                                                @php
                                                    $tabId = 'dashboard-group-tab-' . $loop->index;
                                                    $paneId = 'dashboard-group-pane-' . $loop->index;
                                                @endphp
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                                        id="{{ $tabId }}"
                                                        data-bs-toggle="tab"
                                                        data-bs-target="#{{ $paneId }}"
                                                        type="button"
                                                        role="tab"
                                                        aria-controls="{{ $paneId }}"
                                                        aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                        {{ $groupData['name'] ?? 'Applications' }}
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <div class="tab-content" id="application-group-tabs-content">
                                            @foreach ($groupedApplications as $groupId => $groupData)
                                                @php
                                                    $tabId = 'dashboard-group-tab-' . $loop->index;
                                                    $paneId = 'dashboard-group-pane-' . $loop->index;
                                                @endphp
                                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                                    id="{{ $paneId }}"
                                                    role="tabpanel"
                                                    aria-labelledby="{{ $tabId }}"
                                                    tabindex="0">
                                                    <div class="row application-grid py-3" data-group-id="{{ $groupId }}">
                                                        @foreach ($groupData['applications'] as $application)
                                                            @include('application-tile', ['application' => $application, 'displayMode' => $displayMode])
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif ($hasMultipleGroups)
                                        <div class="accordion" id="application-groups-accordion">
                                            @foreach ($groupedApplications as $groupId => $groupData)
                                                @php
                                                    $headerId = 'dashboard-group-header-' . $loop->index;
                                                    $collapseId = 'dashboard-group-collapse-' . $loop->index;
                                                @endphp
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header" id="{{ $headerId }}">
                                                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}"
                                                            type="button"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#{{ $collapseId }}"
                                                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                                            aria-controls="{{ $collapseId }}">
                                                            {{ $groupData['name'] ?? 'Applications' }}
                                                        </button>
                                                    </h2>
                                                    <div id="{{ $collapseId }}"
                                                        class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                                        aria-labelledby="{{ $headerId }}"
                                                        data-bs-parent="#application-groups-accordion">
                                                        <div class="accordion-body">
                                                            <div class="row application-grid" data-group-id="{{ $groupId }}">
                                                                @foreach ($groupData['applications'] as $application)
                                                                    @include('application-tile', ['application' => $application, 'displayMode' => $displayMode])
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted small mb-3">
                                            Drag and drop your application tiles to reorder them.
                                        </p>
                                        <div class="row application-grid" id="application-grid">
                                            @foreach ($userApplications as $application)
                                                @include('application-tile', ['application' => $application, 'displayMode' => $displayMode])
                                            @endforeach
                                        </div>
                                    @endif
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

    <!-- Application Groups Modal -->
    <div class="modal fade" id="groupsModal" tabindex="-1" aria-labelledby="groupsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="groupsModalLabel">Manage Application Groups</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted d-block">Group display style on dashboard:</label>
                        <div class="btn-group btn-group-sm w-100" role="group" aria-label="Group display style">
                            <input type="radio" class="btn-check" name="groupDisplayMode" id="groupDisplayModeAccordion"
                                value="accordion" {{ $groupDisplayMode !== 'tabs' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary" for="groupDisplayModeAccordion">
                                <i class="fas fa-list-ul me-1"></i>Accordion
                            </label>

                            <input type="radio" class="btn-check" name="groupDisplayMode" id="groupDisplayModeTabs"
                                value="tabs" {{ $groupDisplayMode === 'tabs' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary" for="groupDisplayModeTabs">
                                <i class="fas fa-folder-open me-1"></i>Tabs
                            </label>
                        </div>
                    </div>
                    <div id="groups-container">
                        @foreach ($userGroups as $group)
                            <div class="card mb-3 group-card" data-group-id="{{ $group['id'] }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-2">{{ $group['name'] }}</h6>
                                            <input type="text" class="form-control form-control-sm group-name-input" value="{{ $group['name'] }}" placeholder="Group name">
                                        </div>
                                        <button type="button" class="btn btn-sm btn-danger delete-group-btn" data-group-id="{{ $group['id'] }}">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                    <label class="form-label small text-muted">Select applications for this group:</label>
                                    <div class="group-applications-list" data-group-id="{{ $group['id'] }}">
                                        @foreach ($userApplications as $app)
                                            <div class="form-check">
                                                <input class="form-check-input group-app-checkbox" type="checkbox" 
                                                    id="group{{ $group['id'] }}_app{{ $app->id }}"
                                                    data-app-id="{{ $app->id }}"
                                                    {{ in_array($app->id, $group['application_ids'] ?? []) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="group{{ $group['id'] }}_app{{ $app->id }}">
                                                    {{ $app->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-secondary w-100 mt-3" id="add-group-btn">
                        <i class="fas fa-plus me-2"></i>Add New Group
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save-groups-btn">Save Groups</button>
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

        /* Display Mode Styles */
        .applications-display-normal .col-lg-4 {
            flex: 0 0 33.333%;
        }

        .applications-display-small .col-lg-4 {
            flex: 0 0 20%;
            min-width: 150px;
        }

        .applications-display-small .col-md-6 {
            flex: 0 0 20%;
        }

        .applications-display-text .col-lg-4,
        .applications-display-text .col-md-6 {
            flex: 0 0 100%;
        }

        .application-tile-small {
            padding: 0.5rem !important;
        }

        .application-tile-text {
            border-top: 2px solid #0d6efd;
        }

        .application-tile-text:hover {
            background-color: #f8f9fa;
        }

        .application-logo-small {
            max-width: 40px;
            max-height: 40px;
            object-fit: contain;
        }

        .application-icon-small {
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
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
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const dashboardWidthToggles = document.querySelectorAll('.dashboard-width-toggle');
            dashboardWidthToggles.forEach(toggle => {
                toggle.addEventListener('change', async (e) => {
                    const fullWidth = e.target.value === 'full';
                    const toggleContainer = document.querySelector('input[name="dashboardWidth"]:not(:checked)');

                    try {
                        const response = await fetch("{{ route('dashboard.full-width.update') }}", {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ full_width: fullWidth })
                        });

                        const result = await response.json().catch(() => null);

                        if (!response.ok) {
                            throw new Error(result?.message || 'Failed to update application width.');
                        }

                        window.location.reload();
                    } catch (error) {
                        alert(error instanceof Error ? error.message : 'Failed to save width preference. Please try again.');
                        if (toggleContainer) {
                            toggleContainer.checked = true;
                        }
                    }
                });
            });
            
            // Display Mode Toggle
            const displayModeToggles = document.querySelectorAll('.display-mode-toggle');
            displayModeToggles.forEach(toggle => {
                toggle.addEventListener('change', async (e) => {
                    const displayMode = e.target.value;
                    
                    try {
                        const response = await fetch("{{ route('dashboard.display-mode.update') }}", {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ display_mode: displayMode })
                        });

                        if (!response.ok) {
                            throw new Error('Failed to update display mode');
                        }

                        // Reload the page to reflect the new display mode
                        window.location.reload();
                    } catch (error) {
                        alert('Failed to save display preference. Please try again.');
                        e.target.checked = false;
                    }
                });
            });

            // Groups Management
            const addGroupBtn = document.getElementById('add-group-btn');
            const saveGroupsBtn = document.getElementById('save-groups-btn');
            const groupsContainer = document.getElementById('groups-container');
            let newGroupCounter = 0;

            if (addGroupBtn) {
                addGroupBtn.addEventListener('click', () => {
                    newGroupCounter += 1;
                    const groupId = `group-${Date.now()}-${newGroupCounter}`;
                    const groupCard = document.createElement('div');
                    groupCard.className = 'card mb-3 group-card';
                    groupCard.dataset.groupId = groupId;
                    groupCard.innerHTML = `
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="mb-2">New Group</h6>
                                    <input type="text" class="form-control form-control-sm group-name-input" value="New Group" placeholder="Group name">
                                </div>
                                <button type="button" class="btn btn-sm btn-danger delete-group-btn" data-group-id="${groupId}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            <label class="form-label small text-muted">Select applications for this group:</label>
                            <div class="group-applications-list" data-group-id="${groupId}">
                                @foreach ($userApplications as $app)
                                    <div class="form-check">
                                        <input class="form-check-input group-app-checkbox" type="checkbox" 
                                            id="${groupId}_app{{ $app->id }}"
                                            data-app-id="{{ $app->id }}">
                                        <label class="form-check-label" for="${groupId}_app{{ $app->id }}">
                                            {{ $app->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    `;
                    
                    groupsContainer.appendChild(groupCard);
                    attachGroupCardEventListeners(groupCard);
                });
            }

            const attachGroupCardEventListeners = (groupCard) => {
                const deleteBtn = groupCard.querySelector('.delete-group-btn');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', () => {
                        groupCard.remove();
                    });
                }
            };

            // Attach event listeners to existing group cards
            document.querySelectorAll('.group-card').forEach(card => {
                attachGroupCardEventListeners(card);
            });

            if (saveGroupsBtn) {
                saveGroupsBtn.addEventListener('click', async () => {
                    const groups = [];
                    const selectedGroupDisplayMode = document.querySelector('input[name="groupDisplayMode"]:checked')?.value || 'accordion';
                    let validationError = '';

                    document.querySelectorAll('.group-card').forEach(card => {
                        const groupId = card.dataset.groupId;
                        const nameInput = card.querySelector('.group-name-input');
                        const groupName = nameInput?.value.trim() ?? '';
                        const applicationIds = Array.from(
                            card.querySelectorAll('.group-app-checkbox:checked')
                        ).map(checkbox => parseInt(checkbox.dataset.appId, 10));

                        if (!groupName) {
                            validationError = 'Enter a name for every application group.';
                            nameInput?.classList.add('is-invalid');
                            return;
                        }

                        if (applicationIds.length === 0) {
                            validationError = 'Select at least one application for each group.';
                            card.classList.add('border-danger');
                            return;
                        }

                        nameInput?.classList.remove('is-invalid');
                        card.classList.remove('border-danger');
                        groups.push({
                            id: groupId,
                            name: groupName,
                            application_ids: applicationIds
                        });
                    });

                    if (validationError) {
                        alert(validationError);
                        return;
                    }

                    saveGroupsBtn.disabled = true;

                    try {
                        const response = await fetch("{{ route('dashboard.applications.groups.save') }}", {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                groups,
                                group_display_mode: selectedGroupDisplayMode
                            })
                        });

                        const result = await response.json().catch(() => null);

                        if (!response.ok) {
                            throw new Error(result?.message || 'Failed to save groups.');
                        }

                        const bootstrap = window.bootstrap;
                        if (bootstrap?.Modal) {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('groupsModal'));
                            if (modal) modal.hide();
                        }

                        window.location.reload();
                    } catch (error) {
                        alert(error instanceof Error ? error.message : 'Failed to save groups. Please try again.');
                    } finally {
                        saveGroupsBtn.disabled = false;
                    }
                });
            }


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

            // Auto-prefix external URLs with https:// if missing
            if (shortcutForm) {
                shortcutForm.addEventListener('submit', function(e) {
                    if (shortcutTypeInput && shortcutUrlInput && shortcutTypeInput.value === 'external') {
                        let url = shortcutUrlInput.value.trim();
                        if (url && !/^https?:\/\//i.test(url)) {
                            url = 'https://' + url.replace(/^\/+/, '');
                            shortcutUrlInput.value = url;
                        }
                    }
                });
            }

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
            const applicationGrids = Array.from(document.querySelectorAll('.application-grid'));
            const saveOrderButton = document.getElementById('save-application-order');
            const toggleLockButton = document.getElementById('toggle-dashboard-lock');

            if (!saveOrderButton || !csrfToken) {
                return;
            }

            let draggedElement = null;
            let sourceGrid = null;
            let hasOrderChanges = false;
            let isDashboardLocked = {{ json_encode(auth()->user()->dashboard_locked) }};
            const isGroupedView = applicationGrid === null && applicationGrids.length > 0;

            const markOrderChanged = () => {
                if (!hasOrderChanges) {
                    hasOrderChanges = true;
                    saveOrderButton.classList.remove('d-none');
                }
            };

            const updateLockUI = () => {
                const icon = toggleLockButton.querySelector('i');
                const text = toggleLockButton.querySelector('.lock-status-text');
                if (isDashboardLocked) {
                    icon.classList.remove('fa-unlock');
                    icon.classList.add('fa-lock');
                    text.textContent = 'Locked';
                    toggleLockButton.title = 'Unlock dashboard to edit';
                } else {
                    icon.classList.remove('fa-lock');
                    icon.classList.add('fa-unlock');
                    text.textContent = 'Unlocked';
                    toggleLockButton.title = 'Lock dashboard to prevent accidental changes';
                }
            };

            if (toggleLockButton) {
                toggleLockButton.addEventListener('click', async () => {
                    toggleLockButton.disabled = true;

                    try {
                        const response = await fetch("{{ route('dashboard.lock.toggle') }}", {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Unable to toggle dashboard lock');
                        }

                        const data = await response.json();
                        isDashboardLocked = data.locked;
                        updateLockUI();
                    } catch (error) {
                        alert('Unable to toggle dashboard lock. Please try again.');
                    } finally {
                        toggleLockButton.disabled = false;
                    }
                });
            }

            const syncGroupCheckboxesWithGrid = () => {
                if (!isGroupedView) {
                    return;
                }

                const groupApplicationIds = new Map();
                applicationGrids.forEach((grid) => {
                    const groupId = grid.dataset.groupId;
                    if (!groupId || groupId === 'ungrouped') {
                        return;
                    }

                    const ids = Array.from(grid.querySelectorAll('.draggable-application'))
                        .map((item) => Number.parseInt(item.dataset.applicationId, 10))
                        .filter((id) => Number.isInteger(id));
                    groupApplicationIds.set(groupId, ids);
                });

                document.querySelectorAll('.group-card').forEach((card) => {
                    const groupId = card.dataset.groupId;
                    const selectedIds = groupApplicationIds.get(groupId) ?? [];

                    card.querySelectorAll('.group-app-checkbox').forEach((checkbox) => {
                        const appId = Number.parseInt(checkbox.dataset.appId, 10);
                        checkbox.checked = selectedIds.includes(appId);
                    });
                });
            };

            const draggableItems = document.querySelectorAll('.draggable-application');
            draggableItems.forEach((item) => {
                item.addEventListener('dragstart', (event) => {
                    if (isDashboardLocked) {
                        event.preventDefault();
                        return;
                    }
                    draggedElement = item;
                    sourceGrid = item.closest('.application-grid');
                    item.classList.add('dragging');
                    event.dataTransfer.effectAllowed = 'move';
                });

                item.addEventListener('dragend', () => {
                    item.classList.remove('dragging');
                    draggedElement = null;
                    sourceGrid = null;
                });
            });

            applicationGrids.forEach((grid) => {
                grid.addEventListener('dragover', (event) => {
                    if (isDashboardLocked) {
                        return;
                    }

                    event.preventDefault();
                    if (!draggedElement) {
                        return;
                    }

                    const dropTarget = event.target.closest('.draggable-application');

                    if (!dropTarget) {
                        if (grid !== sourceGrid || grid.lastElementChild !== draggedElement) {
                            grid.appendChild(draggedElement);
                            sourceGrid = grid;
                            markOrderChanged();
                            syncGroupCheckboxesWithGrid();
                        }
                        return;
                    }

                    if (dropTarget === draggedElement) {
                        return;
                    }

                    const targetRect = dropTarget.getBoundingClientRect();
                    const insertAfter = event.clientY > (targetRect.top + (targetRect.height / 2));

                    if (insertAfter) {
                        grid.insertBefore(draggedElement, dropTarget.nextSibling);
                    } else {
                        grid.insertBefore(draggedElement, dropTarget);
                    }

                    sourceGrid = grid;
                    markOrderChanged();
                    syncGroupCheckboxesWithGrid();
                });
            });

            saveOrderButton.addEventListener('click', async () => {
                saveOrderButton.disabled = true;

                try {
                    let response;
                    if (isGroupedView) {
                        const selectedGroupDisplayMode = document.querySelector('input[name="groupDisplayMode"]:checked')?.value || 'accordion';
                        const applicationIdsByGroupId = new Map();

                        applicationGrids.forEach((grid) => {
                            const groupId = grid.dataset.groupId;
                            if (!groupId) {
                                return;
                            }

                            const applicationIds = Array.from(grid.querySelectorAll('.draggable-application'))
                                .map((item) => Number.parseInt(item.dataset.applicationId, 10))
                                .filter((id) => Number.isInteger(id));

                            applicationIdsByGroupId.set(groupId, applicationIds);
                        });

                        const groups = Array.from(document.querySelectorAll('.group-card')).map((card) => {
                            const groupId = card.dataset.groupId;
                            const nameInput = card.querySelector('.group-name-input');
                            const fallbackName = card.querySelector('h6')?.textContent?.trim() || 'New Group';

                            return {
                                id: groupId,
                                name: nameInput?.value.trim() || fallbackName,
                                application_ids: applicationIdsByGroupId.get(groupId) ?? []
                            };
                        });

                        response = await fetch("{{ route('dashboard.applications.groups.save') }}", {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                groups,
                                group_display_mode: selectedGroupDisplayMode
                            })
                        });
                    } else {
                        const applicationIds = Array.from(applicationGrid.querySelectorAll('.draggable-application'))
                            .map((item) => Number.parseInt(item.dataset.applicationId, 10))
                            .filter((id) => Number.isInteger(id));

                        response = await fetch("{{ route('dashboard.applications.order') }}", {
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
                    }

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
