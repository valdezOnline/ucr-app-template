<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Core\Domain\Notifications\Contracts\EmailDispatcher;
use Modules\Core\Domain\Notifications\Data\EmailMessage;
use Modules\Notifications\Jobs\SendEmailJob;
use Modules\Notifications\Models\EmailDispatchLog;
use Tests\TestCase;

class EmailDispatcherModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_dispatcher_contract_resolves_and_enqueues_job(): void
    {
        Queue::fake();

        $dispatcher = app(EmailDispatcher::class);

        $dispatcher->enqueue(new EmailMessage(
            to: ['user@example.com'],
            subject: 'Module email test',
            textBody: 'Hello from queue',
        ));

        Queue::assertPushed(SendEmailJob::class, function (SendEmailJob $job) {
            return $job->dispatchLogId > 0;
        });

        $this->assertDatabaseHas('email_dispatch_logs', [
            'status' => EmailDispatchLog::STATUS_QUEUED,
            'subject' => 'Module email test',
        ]);
    }
}
