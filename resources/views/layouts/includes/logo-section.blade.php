    <!-- Logo Section Start -->
    <div id="logo-section" class="container-fluid top-logo-container bottom-shadow">
        <div class="container masthead-container">
            <div class="row">
                <div class="col">
                    <a class="masthead-logo-line " href="/home">
                        <span class="show-for-sr ">UC Riverside</span>
                    </a>
                    <h1 class="masthead-title">Library</h1>
                </div>
                <div class="col">
                    <h1 class="masthead-slogan ucr-mr-15">
                        {{-- {{isset($_GET['header-title'])?$_GET['header-title']:Library Application Portal}} --}}
                        @if (isset($_GET['header-title']))
                            {{ $_GET['header-title'] }}
                        @else
                            <i class="fas fa-calendar-check me-2"></i>{{ config('app.name', 'App Template') }}
                        @endif
                    </h1>
                </div>
            </div>
        </div>

    </div>
    <!-- Logo Section End -->
