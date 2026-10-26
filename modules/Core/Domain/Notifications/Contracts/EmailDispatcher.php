<?php

namespace Modules\Core\Domain\Notifications\Contracts;

use Modules\Core\Domain\Notifications\Data\EmailMessage;

interface EmailDispatcher
{
    public function enqueue(EmailMessage $message): void;

    public function sendNow(EmailMessage $message): void;
}
