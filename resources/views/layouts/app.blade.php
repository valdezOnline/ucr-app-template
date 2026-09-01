<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'App Template') }} - @yield('title', 'App Template')</title>


    <!-- Styles -->
    @vite(['resources/sass/app.scss', 'resources/css/app.css', 'resources/css/custom.css', 'resources/js/app.js'])
</head>

<body>
    <header>
        <div class=" border-bottom">
            @include('layouts.includes.top-nav')
        </div>
        <div class="">
            @include('layouts.includes.logo-section')
        </div>
        <div id="app" class="container-fluid ucr-blue-bg">
            @include('layouts.includes.main-nav')
        </div>
    </header>

    <main class="py-4">
        <div class="container">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show text-center" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer>
        @include('layouts.includes.footer')
    </footer>

    @yield('scripts')
    @stack('scripts')
</body>

</html>
