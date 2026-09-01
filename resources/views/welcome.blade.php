@extends('layouts.app')

@section('content')
    <div class="container mb-0" style="min-height: 500px">
        <div class="row">
            <div class="col-md-12">
                <div class="jumbotron text-center">
                    <h1 class="display-4">Application Template</h1>
                    <p class="lead">Welcome to the UCR Library Application Template</p>
                    <hr class="my-4">
                    <p>This template will be utilized as the base template for any new application created for the Library
                        System.</p>
                </div>
            </div>
        </div>

        @if ($message ?? '' != '')
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="alert alert-warning text-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ $message }}
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header ucr-blue-bg text-white">
                        <i class="fas fa-qrcode me-2"></i>Request Custom Application
                    </div>
                    <div class="card-body">
                        <h5>Meet with Us</h5>
                        <p>Let's get a conversation started:</p>
                        <ul>
                            <li><strong>Purpose:</strong> Let's discuss your need for this application and we'll make the
                                decision together. if a custom
                                application is what you need.</li>
                            <li><strong>Alternate Options:</strong> If a custom application is not necessary, we can explore
                                existing solutions that may meet your needs.</li>
                            <li><strong>Requirements:</strong> If alternate solutions have been explored and are not
                                suitable, we can discuss the specific requirements for a custom application.</li>
                        </ul>
                        <p class="text-muted">
                            <small>Full Software Development Life Cycle (SDLC) process will be followed for all custom
                                applications.</small>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header ucr-gold-bg">
                        <i class="fas fa-users-cog me-2"></i>Technology Stack
                    </div>
                    <div class="card-body">
                        <h5>Technology Stack</h5>
                        <p>We utilize the following technologies:</p>
                        <ul>
                            <li><strong>Software</strong> Laravel, PHP, NodeJS, C#, ReactJS, Express, Python, etc. </li>
                            <li><strong>Infrastructure</strong> AWS, Forge, Docker, etc.</li>
                            <li><strong>Database</strong> MySQL, PostgreSQL, MongoDB, SQL Server,etc.</li>
                            <li><strong>Integration</strong> REST APIs, GraphQL, Webhooks, etc.</li>
                        </ul>

                        <p class="text-muted mt-2">
                            <small>Depending on the specific requirements of the application, additional technologies may be
                                utilized.</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="alert alert-info text-center" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Need More Info?</strong> Contact the library IT staff for assistance.
                </div>
            </div>
        </div>
    </div>
@endsection
