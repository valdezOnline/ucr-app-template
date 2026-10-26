<?php

namespace Modules\Core\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Core\Domain\Notifications\Contracts\EmailDispatcher;
use Modules\Core\Domain\Notifications\Data\EmailMessage;
use Modules\Notifications\Models\EmailDispatchLog;

class AdminEmailDispatchController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'auth.type']);
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user || (!$user->hasRole('admin') && !$user->is_admin)) {
                abort(403, 'Access denied. Admin role required.');
            }

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $search = trim($request->string('q')->toString());

        $logs = EmailDispatchLog::query()
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('subject', 'like', "%{$search}%")
                        ->orWhere('to_recipients', 'like', "%{$search}%")
                        ->orWhere('error_message', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.email-dispatches.index', [
            'logs' => $logs,
            'status' => $status,
            'search' => $search,
            'statusOptions' => [
                EmailDispatchLog::STATUS_QUEUED,
                EmailDispatchLog::STATUS_SENT,
                EmailDispatchLog::STATUS_FAILED,
            ],
        ]);
    }

    public function show(EmailDispatchLog $emailDispatchLog): View
    {
        return view('admin.email-dispatches.show', [
            'log' => $emailDispatchLog,
        ]);
    }

    public function retry(Request $request, EmailDispatchLog $emailDispatchLog, EmailDispatcher $emailDispatcher): RedirectResponse
    {
        if ($emailDispatchLog->status !== EmailDispatchLog::STATUS_FAILED) {
            return redirect()
                ->back()
                ->with('warning', 'Only failed email dispatches can be retried.');
        }

        try {
            $emailDispatcher->enqueue(new EmailMessage(
                to: $emailDispatchLog->to_recipients ?? [],
                subject: $emailDispatchLog->subject,
                htmlBody: $emailDispatchLog->html_body,
                textBody: $emailDispatchLog->text_body,
                cc: $emailDispatchLog->cc_recipients ?? [],
                bcc: $emailDispatchLog->bcc_recipients ?? [],
                replyTo: $emailDispatchLog->reply_to_recipients ?? [],
                fromEmail: $emailDispatchLog->from_email,
                fromName: $emailDispatchLog->from_name,
            ));
        } catch (\Throwable $exception) {
            Log::error('Failed to enqueue retry for email dispatch log.', [
                'email_dispatch_log_id' => $emailDispatchLog->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Unable to retry this email dispatch right now.');
        }

        return redirect()
            ->route('admin.email-dispatches.show', $emailDispatchLog)
            ->with('success', 'Email dispatch has been re-queued.');
    }
}
