<!-- Nav Menu -->
<div class="clearfix">
    <div class="container-fluid ucr-blue-bg">
        <div class="container">
            <nav class="ucr-navbar-nav navbar navbar-expand-lg ucr-pt-0 ucr-pb-0">
                <button class="navbar-toggler ucr-navbar-toggler-btn" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="ucr-icon-bar"></span>
                    <span class="ucr-icon-bar"></span>
                    <span class="ucr-icon-bar"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a href="{{ route('events.show', $event) }}"
                                class="nav-link ucr-white-text ucr-white-text-hover-gold">
                                <i class="fas fa-arrow-left me-1"></i>Back to Event
                            </a>
                        </li>
                        @authenticated
                        @if (auth()->user() && (auth()->user()->canHostEvents() || auth()->user()->canAdmin()))
                            <li class="nav-item">
                                <a class="nav-link ucr-white-text ucr-white-text-hover-gold"
                                    href="{{ route('participants.index') }}">
                                    <i class="fas fa-users me-1"></i>Participants
                                </a>
                            </li>
                        @endif
                        @if (auth()->user() && (auth()->user()->canRunReports() || auth()->user()->canAdmin()))
                            <li class="nav-item">
                                <a class="nav-link ucr-white-text ucr-white-text-hover-gold"
                                    href="{{ route('reports.index') }}">
                                    <i class="fas fa-chart-bar me-1"></i>Reports
                                </a>
                            </li>
                        @endif

                        @if (auth()->user() && auth()->user()->canAdmin())
                            <li class="nav-item">
                                <a class="nav-link ucr-white-text ucr-white-text-hover-gold"
                                    href="{{ route('admin.dashboard') }}">
                                    <i class="fas fa-cog me-1"></i>Admin
                                </a>
                            </li>
                        @endif
                        @endauthenticated
                        {{-- Help menu: Show to all users --}}
                        <li class="nav-item">
                            <a class="nav-link ucr-white-text ucr-white-text-hover-gold" href="{{ route('help') }}">
                                <i class="fas fa-question-circle me-1"></i>Help
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

        </div>
    </div>
    @authenticated
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
    @endauthenticated

</div>
