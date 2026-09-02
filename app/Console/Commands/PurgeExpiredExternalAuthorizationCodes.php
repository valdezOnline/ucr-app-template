<?php

namespace App\Console\Commands;

use App\Models\ExternalAuthorizationCode;
use Illuminate\Console\Command;

class PurgeExpiredExternalAuthorizationCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:purge-external-codes {--dry-run : Show count only without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired external authorization codes used by CAS broker flow';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retentionHours = (int) config('auth.external_broker.cleanup_retention_hours', 24);
        $cutoff = now()->subHours(max(1, $retentionHours));

        $query = ExternalAuthorizationCode::query()
            ->where('expires_at', '<', $cutoff);

        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("Dry run: {$count} expired authorization code(s) would be deleted.");
            return Command::SUCCESS;
        }

        if ($count === 0) {
            $this->info('No expired authorization codes to purge.');
            return Command::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Deleted {$deleted} expired authorization code(s).");

        return Command::SUCCESS;
    }
}
