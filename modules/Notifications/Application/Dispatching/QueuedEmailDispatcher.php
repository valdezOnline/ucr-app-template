<?php

namespace Modules\Notifications\Application\Dispatching;

use Throwable;
use Modules\Core\Domain\Notifications\Contracts\EmailDispatcher;
use Modules\Core\Domain\Notifications\Data\EmailMessage;
use Modules\Notifications\Infrastructure\Email\LaravelEmailSender;
use Modules\Notifications\Jobs\SendEmailJob;
use Modules\Notifications\Models\EmailDispatchLog;

class QueuedEmailDispatcher implements EmailDispatcher
{
    public function __construct(
        private readonly LaravelEmailSender $emailSender,
    ) {
    }

    public function enqueue(EmailMessage $message): void
    {
        $log = EmailDispatchLog::create([
            'status' => EmailDispatchLog::STATUS_QUEUED,
            'to_recipients' => $message->to,
            'cc_recipients' => $message->cc,
            'bcc_recipients' => $message->bcc,
            'reply_to_recipients' => $message->replyTo,
            'subject' => $message->subject,
            'from_email' => $message->fromEmail,
            'from_name' => $message->fromName,
            'html_body' => $message->htmlBody,
            'text_body' => $message->textBody,
            'queued_at' => now(),
        ]);

        SendEmailJob::dispatch($message, $log->id);
    }

    public function sendNow(EmailMessage $message): void
    {
        $log = EmailDispatchLog::create([
            'status' => EmailDispatchLog::STATUS_QUEUED,
            'to_recipients' => $message->to,
            'cc_recipients' => $message->cc,
            'bcc_recipients' => $message->bcc,
            'reply_to_recipients' => $message->replyTo,
            'subject' => $message->subject,
            'from_email' => $message->fromEmail,
            'from_name' => $message->fromName,
            'html_body' => $message->htmlBody,
            'text_body' => $message->textBody,
            'queued_at' => now(),
        ]);

        try {
            $this->emailSender->send($message);

            $log->update([
                'status' => EmailDispatchLog::STATUS_SENT,
                'attempts' => 1,
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            $log->update([
                'status' => EmailDispatchLog::STATUS_FAILED,
                'attempts' => 1,
                'failed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
