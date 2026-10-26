<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ListModulesCommandTest extends TestCase
{
    public function test_it_lists_modules_in_table_output(): void
    {
        $this->artisan('module:list')
            ->expectsOutputToContain('Core')
            ->expectsOutputToContain('Notifications')
            ->expectsOutputToContain('Email templates')
            ->assertExitCode(0);
    }

    public function test_it_lists_modules_in_json_output(): void
    {
        $exitCode = Artisan::call('module:list', ['--json' => true]);
        $this->assertSame(0, $exitCode);

        $payload = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
        $moduleNames = array_column($payload, 'name');

        $this->assertContains('Core', $moduleNames);
        $this->assertContains('Notifications', $moduleNames);
        $this->assertArrayHasKey('email_template_count', $payload[0]);
    }
}
