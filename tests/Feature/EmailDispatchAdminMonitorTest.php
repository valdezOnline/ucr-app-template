<?php

namespace Tests\Feature;

use Modules\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Notifications\Models\EmailDispatchLog;
use Modules\Notifications\Jobs\SendEmailJob;
use Tests\TestCase;

class EmailDispatchAdminMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_email_dispatch_monitor(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $log = EmailDispatchLog::create([
            'status' => EmailDispatchLog::STATUS_FAILED,
            'to_recipients' => ['ops@example.com'],
            'cc_recipients' => [],
            'bcc_recipients' => [],
            'reply_to_recipients' => [],
            'subject' => 'Failed email',
            'from_email' => 'no-reply@example.com',
            'from_name' => 'App Template',
            'html_body' => null,
            'text_body' => 'Body',
            'queued_at' => now()->subMinute(),
            'sent_at' => null,
            'failed_at' => now(),
            'attempts' => 2,
            'error_message' => 'SMTP timeout',
        ]);

        $indexResponse = $this->actingAs($admin)->get(route('admin.email-dispatches.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Email Dispatch Monitor');
        $indexResponse->assertSee('Failed email');

        $showResponse = $this->actingAs($admin)->get(route('admin.email-dispatches.show', $log));
        $showResponse->assertOk();
        $showResponse->assertSee('SMTP timeout');
    }

    public function test_non_admin_cannot_view_email_dispatch_monitor(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('admin.email-dispatches.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_retry_failed_email_dispatch(): void
    {
        Queue::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $failedLog = EmailDispatchLog::create([
            'status' => EmailDispatchLog::STATUS_FAILED,
            'to_recipients' => ['ops@example.com'],
            'cc_recipients' => [],
            'bcc_recipients' => [],
            'reply_to_recipients' => [],
            'subject' => 'Retry me',
            'from_email' => 'no-reply@example.com',
            'from_name' => 'App Template',
            'html_body' => null,
            'text_body' => 'Body',
            'queued_at' => now()->subMinute(),
            'sent_at' => null,
            'failed_at' => now(),
            'attempts' => 2,
            'error_message' => 'SMTP timeout',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post(route('admin.email-dispatches.retry', $failedLog), [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect(route('admin.email-dispatches.show', $failedLog));
        $response->assertSessionHas('success');

        Queue::assertPushed(SendEmailJob::class);
        $this->assertDatabaseCount('email_dispatch_logs', 2);
        $this->assertDatabaseHas('email_dispatch_logs', [
            'status' => EmailDispatchLog::STATUS_QUEUED,
            'subject' => 'Retry me',
        ]);
    }

    public function test_admin_cannot_retry_non_failed_email_dispatch(): void
    {
        Queue::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $sentLog = EmailDispatchLog::create([
            'status' => EmailDispatchLog::STATUS_SENT,
            'to_recipients' => ['ops@example.com'],
            'cc_recipients' => [],
            'bcc_recipients' => [],
            'reply_to_recipients' => [],
            'subject' => 'Already sent',
            'from_email' => 'no-reply@example.com',
            'from_name' => 'App Template',
            'html_body' => null,
            'text_body' => 'Body',
            'queued_at' => now()->subMinute(),
            'sent_at' => now(),
            'failed_at' => null,
            'attempts' => 1,
            'error_message' => null,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post(route('admin.email-dispatches.retry', $sentLog), [
                '_token' => 'test-token',
            ]);

        $response->assertSessionHas('warning');
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('email_dispatch_logs', 1);
    }
}
