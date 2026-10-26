<?php

namespace Modules\Notifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use Modules\Core\Domain\Notifications\Data\EmailMessage;
use Modules\Notifications\Infrastructure\Email\LaravelEmailSender;
use Modules\Notifications\Models\EmailDispatchLog;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly EmailMessage $message,
        public readonly int $dispatchLogId,
    ) {
    }

    public function handle(LaravelEmailSender $emailSender): void
    {
        $log = EmailDispatchLog::find($this->dispatchLogId);
        if ($log) {
            $log->update([
                'attempts' => max((int) $log->attempts, $this->attempts()),
            ]);
        }

        $emailSender->send($this->message);

        if ($log) {
            $log->update([
                'status' => EmailDispatchLog::STATUS_SENT,
                'sent_at' => now(),
                'error_message' => null,
                'attempts' => max((int) $log->attempts, $this->attempts()),
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        $log = EmailDispatchLog::find($this->dispatchLogId);
        if (!$log) {
            return;
        }

        $log->update([
            'status' => EmailDispatchLog::STATUS_FAILED,
            'failed_at' => now(),
            'error_message' => $exception->getMessage(),
            'attempts' => max((int) $log->attempts, $this->attempts()),
        ]);
    }
}
