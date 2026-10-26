<?php

namespace Modules\Notifications\Infrastructure\Email;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Domain\Notifications\Data\EmailMessage;

class LaravelEmailSender
{
    public function send(EmailMessage $message): void
    {
        $callback = function (Message $mailMessage) use ($message): void {
            $mailMessage->to($message->to);
            $mailMessage->subject($message->subject);

            if ($message->cc !== []) {
                $mailMessage->cc($message->cc);
            }

            if ($message->bcc !== []) {
                $mailMessage->bcc($message->bcc);
            }

            if ($message->replyTo !== []) {
                $mailMessage->replyTo($message->replyTo);
            }

            if ($message->fromEmail !== null) {
                $mailMessage->from($message->fromEmail, $message->fromName);
            }
        };

        if ($message->htmlBody !== null) {
            Mail::html($message->htmlBody, $callback);
            return;
        }

        Mail::raw($message->textBody ?? '', $callback);
    }
}
