@extends('layouts.app')

@section('title', 'Email Dispatch Detail')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-envelope-open-text me-2"></i>Email Dispatch #{{ $log->id }}
                        </h4>
                        <div class="d-flex gap-2">
                            @if ($log->status === 'failed')
                                <form method="POST" action="{{ route('admin.email-dispatches.retry', $log) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-warning btn-sm">Retry</button>
                                </form>
                            @endif
                            <a href="{{ route('admin.email-dispatches.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-3">Status</dt>
                            <dd class="col-sm-9">
                                <span class="badge bg-{{ $log->status === 'sent' ? 'success' : ($log->status === 'failed' ? 'danger' : 'secondary') }}">
                                    {{ strtoupper($log->status) }}
                                </span>
                            </dd>

                            <dt class="col-sm-3">Subject</dt>
                            <dd class="col-sm-9">{{ $log->subject }}</dd>

                            <dt class="col-sm-3">To</dt>
                            <dd class="col-sm-9">{{ implode(', ', $log->to_recipients ?? []) }}</dd>

                            <dt class="col-sm-3">CC</dt>
                            <dd class="col-sm-9">{{ implode(', ', $log->cc_recipients ?? []) ?: '—' }}</dd>

                            <dt class="col-sm-3">BCC</dt>
                            <dd class="col-sm-9">{{ implode(', ', $log->bcc_recipients ?? []) ?: '—' }}</dd>

                            <dt class="col-sm-3">Reply-To</dt>
                            <dd class="col-sm-9">{{ implode(', ', $log->reply_to_recipients ?? []) ?: '—' }}</dd>

                            <dt class="col-sm-3">From</dt>
                            <dd class="col-sm-9">
                                @if ($log->from_email)
                                    {{ $log->from_name ? "{$log->from_name} <{$log->from_email}>" : $log->from_email }}
                                @else
                                    —
                                @endif
                            </dd>

                            <dt class="col-sm-3">Attempts</dt>
                            <dd class="col-sm-9">{{ $log->attempts }}</dd>

                            <dt class="col-sm-3">Queued At</dt>
                            <dd class="col-sm-9">{{ optional($log->queued_at)->toDateTimeString() ?? '—' }}</dd>

                            <dt class="col-sm-3">Sent At</dt>
                            <dd class="col-sm-9">{{ optional($log->sent_at)->toDateTimeString() ?? '—' }}</dd>

                            <dt class="col-sm-3">Failed At</dt>
                            <dd class="col-sm-9">{{ optional($log->failed_at)->toDateTimeString() ?? '—' }}</dd>

                            <dt class="col-sm-3">Error</dt>
                            <dd class="col-sm-9">
                                <pre class="mb-0 text-danger bg-light p-2 rounded">{{ $log->error_message ?: '—' }}</pre>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
