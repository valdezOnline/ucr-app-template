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
                        @auth
                            @if (auth()->user()->hasRole('admin') || auth()->user()->is_admin)
                                <li class="nav-item">
                                    <a class="nav-link ucr-white-text ucr-white-text-hover-gold" href="{{ route('home') }}">
                                        <i class="fas fa-shield-alt me-1"></i>Admin Dashboard
                                    </a>
                                </li>
                            @else
                                <li class="nav-item">
                                    <a class="nav-link ucr-white-text ucr-white-text-hover-gold"
                                        href="{{ route('dashboard') }}">
                                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                    </a>
                                </li>
                            @endif
                            {{-- @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('manager') || auth()->user()->isManager())
                                <li class="nav-item">
                                    <a class="nav-link ucr-white-text ucr-white-text-hover-gold"
                                        href="{{ route('users.index') }}">
                                        <i class="fas fa-users me-1"></i>Manage Users
                                    </a>
                                </li>
                            @endif --}}
                            @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('manager'))
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle ucr-white-text ucr-white-text-hover-gold"
                                        href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <i class="fas fa-cogs me-1"></i>Administration
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                        <li>
                                            <a class="dropdown-item ucr-blue-text ucr-blue-text-hover-blue"
                                                href="{{ route('roles.index') }}">
                                                <i class="fas fa-user-tag me-1"></i>Manage Roles
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item ucr-blue-text ucr-blue-text-hover-blue"
                                                href="{{ route('applications.index') }}">
                                                <i class="fas fa-list-alt me-1"></i>Manage Applications
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link ucr-blue-text ucr-blue-text-hover-blue"
                                                href="{{ route('users.index') }}">
                                                <i class="fas fa-users me-1"></i>Manage Users
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            @endif
                        @endauth
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link ucr-white-text ucr-white-text-hover-gold" href="{{ route('help') }}">
                                <i class="fas fa-question-circle me-1"></i>Help
                            </a>
                        </li>
                        @auth
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle ucr-white-text ucr-white-text-hover-gold" href="#"
                                    id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user me-1"></i>{{ auth()->user()->first_name }}
                                    @if (auth()->user()->getRoles())
                                        <small>
                                            ({{ implode(', ', auth()->user()->getRoles()) }})
                                        </small>
                                    @endif
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    @if (auth()->user() && (auth()->user()->hasRole('admin') || auth()->user()->is_admin))
                                        <li>
                                            <a class="dropdown-item ucr-blue-text ucr-blue-text-hover-blue"
                                                href="{{ route('home') }}">
                                                <i class="fas fa-shield-alt me-1"></i>Admin Dashboard
                                            </a>
                                        </li>
                                    @else
                                        <li>
                                            <a class="dropdown-item ucr-blue-text ucr-blue-text-hover-blue"
                                                href="{{ route('dashboard') }}">
                                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                            </a>
                                        </li>
                                    @endif
                                    <li>
                                        <a class="dropdown-item ucr-blue-text ucr-blue-text-hover-blue"
                                            href="{{ route('profile') }}">
                                            <i class="fas fa-user me-1"></i>My Profile
                                        </a>
                                    </li>
                                    @if (auth()->user() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('manager')))
                                        <li>
                                            <a class="dropdown-item ucr-blue-text ucr-blue-text-hover-blue"
                                                href="{{ route('users.index') }}">
                                                <i class="fas fa-users me-1"></i>Manage Users
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item ucr-blue-text ucr-blue-text-hover-blue"
                                                href="{{ route('roles.index') }}">
                                                <i class="fas fa-user-tag me-1"></i>Manage Roles
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item ucr-blue-text ucr-blue-text-hover-blue"
                                                href="{{ route('applications.index') }}">
                                                <i class="fas fa-list-alt me-1"></i>Manage Applications
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item ucr-blue-text ucr-blue-text-hover-blue"
                                                href="{{ route('auth-config.index') }}">
                                                <i class="fas fa-cog me-1"></i>Authentication Config
                                            </a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                    @endif
                                    <li>
                                        @casavailable
                                            <a class="dropdown-item ucr-blue-text ucr-blue-text-hover-blue"
                                                href="{{ route('cas.logout') }}">
                                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                                            </a>
                                        @else
                                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                                style="display: none;">
                                                @csrf
                                            </form>
                                            <a class="dropdown-item ucr-gold-text ucr-gold-text-hover-blue"
                                                href="{{ route('logout') }}"
                                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                                            </a>
                                        @endcasavailable
                                    </li>
                                </ul>
                            </li>
                        @endauth
                        @guest
                            <li class="nav-item">
                                <a class="nav-link ucr-white-text ucr-white-text-hover-gold"
                                    href="{{ route('login') }}">Login</a>
                            </li>
                        @endguest

                    </ul>
                </div>
            </nav>

        </div>
    </div>

</div>
