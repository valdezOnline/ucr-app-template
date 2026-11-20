<div class="col-lg-4 col-md-6 mb-3 draggable-application"
    data-application-id="{{ $application->id }}" draggable="true">
    @if ($displayMode === 'text')
        <div class="card application-tile application-tile-text h-100">
            <div class="card-body d-flex align-items-center px-3 py-2">
                <div class="drag-handle me-2" aria-hidden="true">
                    <i class="fas fa-grip-vertical text-muted"></i>
                </div>
                <div class="flex-grow-1">
                    <a href="{{ $application->url }}" target="_blank" class="text-decoration-none">
                        <strong class="text-dark">{{ $application->name }}</strong>
                    </a>
                </div>
            </div>
        </div>
    @elseif ($displayMode === 'small')
        <div class="card application-tile application-tile-small h-100">
            <div class="card-body text-center d-flex flex-column p-2">
                <div class="drag-handle mb-1" aria-hidden="true">
                    <i class="fas fa-grip-vertical text-muted small"></i>
                </div>
                <div class="mb-2">
                    @if ($application->logo)
                        <img src="{{ $application->logo }}"
                            alt="{{ $application->name }}"
                            class="application-logo-small mb-1"
                            style="max-width: 40px; max-height: 40px;">
                    @else
                        <div class="application-icon-small mb-1">
                            <i
                                class="{{ $application->icon ?? 'fas fa-cube' }} fa-lg text-primary"></i>
                        </div>
                    @endif
                </div>
                <h6 class="card-title mb-0 small">{{ $application->name }}</h6>
                <div class="mt-auto pt-1">
                    <a href="{{ $application->url }}" target="_blank"
                        class="btn btn-primary btn-xs" style="font-size: 0.7rem; padding: 0.25rem 0.5rem;">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="card application-tile application-tile-normal h-100">
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
    @endif
</div>
