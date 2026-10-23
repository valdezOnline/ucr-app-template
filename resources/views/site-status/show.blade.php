<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'App Template') }} - {{ $siteStatus->displayTitle() }}</title>
    @vite(['resources/sass/app.scss', 'resources/css/app.css', 'resources/css/custom.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-tools text-warning" style="font-size: 3rem;"></i>
                        </div>
                        <h1 class="h3 mb-3">{{ $siteStatus->displayTitle() }}</h1>
                        <p class="lead mb-4">{{ $siteStatus->displayMessage() }}</p>
                        <div class="text-muted">
                            Please check back later.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
