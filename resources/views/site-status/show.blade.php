@extends('layouts.app')

@section('title', $siteStatus->displayTitle())

@section('content')
    <div class="container py-5">
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
    </div>
@endsection
