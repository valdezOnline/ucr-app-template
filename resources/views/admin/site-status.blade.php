@extends('layouts.app')

@section('title', 'Site Status Configuration')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-exclamation-circle me-2"></i>Site Status Configuration
                    </h4>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        When any mode below is enabled, non-admin users and guests are redirected to the public status page.
                    </div>

                    <form method="POST" action="{{ route('site-status-config.update') }}">
                        @csrf
                        @method('PUT')

                        <h5 class="mb-3">Select Mode</h5>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="mode" id="mode_none" value="none"
                                {{ old('mode', $siteStatus->mode) === 'none' ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="mode_none">
                                Site Available (Off)
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="mode" id="mode_maintenance"
                                value="maintenance"
                                {{ old('mode', $siteStatus->mode) === 'maintenance' ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="mode_maintenance">
                                Maintenance Mode
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="mode" id="mode_technical_issue"
                                value="technical_issue"
                                {{ old('mode', $siteStatus->mode) === 'technical_issue' ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="mode_technical_issue">
                                Experiencing Technical Issue
                            </label>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="radio" name="mode" id="mode_custom_message"
                                value="custom_message"
                                {{ old('mode', $siteStatus->mode) === 'custom_message' ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="mode_custom_message">
                                Customizable Message
                            </label>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label fw-bold">Display Message (Optional)</label>
                            <textarea class="form-control @error('message') is-invalid @enderror" name="message" id="message" rows="5"
                                placeholder="Enter a custom message to display. For Maintenance/Technical modes, if left blank, a default message will be used.">{{ old('message', $siteStatus->message) }}</textarea>
                            <div class="form-text">
                                Required only when "Customizable Message" is selected.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small text-muted">
                                Current mode:
                                <strong>{{ $siteStatus->displayTitle() }}</strong>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save Site Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
