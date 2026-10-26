@extends('layouts.app')

@section('title', 'Email Dispatch Monitor')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-envelope-open-text me-2"></i>Email Dispatch Monitor
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All</option>
                                    @foreach ($statusOptions as $option)
                                        <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Search</label>
                                <input type="text" name="q" value="{{ $search }}" class="form-control"
                                    placeholder="Subject, recipient, or error">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Status</th>
                                        <th>Subject</th>
                                        <th>Recipients</th>
                                        <th>Attempts</th>
                                        <th>Queued</th>
                                        <th>Sent</th>
                                        <th>Failed</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($logs as $log)
                                        <tr>
                                            <td>{{ $log->id }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $log->status === 'sent' ? 'success' : ($log->status === 'failed' ? 'danger' : 'secondary') }}">
                                                    {{ strtoupper($log->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $log->subject }}</td>
                                            <td>{{ implode(', ', $log->to_recipients ?? []) }}</td>
                                            <td>{{ $log->attempts }}</td>
                                            <td>{{ optional($log->queued_at)->toDateTimeString() ?? '—' }}</td>
                                            <td>{{ optional($log->sent_at)->toDateTimeString() ?? '—' }}</td>
                                            <td>{{ optional($log->failed_at)->toDateTimeString() ?? '—' }}</td>
                                            <td class="d-flex gap-2">
                                                <a href="{{ route('admin.email-dispatches.show', $log) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                                @if ($log->status === 'failed')
                                                    <form method="POST"
                                                        action="{{ route('admin.email-dispatches.retry', $log) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                                            Retry
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">No email dispatch logs
                                                found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
