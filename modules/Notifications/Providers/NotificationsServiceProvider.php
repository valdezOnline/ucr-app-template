<?php

namespace Modules\Notifications\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Domain\Notifications\Contracts\EmailDispatcher;
use Modules\Notifications\Application\Dispatching\QueuedEmailDispatcher;
use Modules\Notifications\Infrastructure\Email\LaravelEmailSender;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LaravelEmailSender::class);
        $this->app->bind(EmailDispatcher::class, QueuedEmailDispatcher::class);
    }
}
