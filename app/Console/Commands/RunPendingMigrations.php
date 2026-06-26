<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Run any pending database migrations.
 *
 * `migrate --force` is already idempotent — it only applies migrations not yet
 * recorded in the `migrations` table — so this command is safe to run on every
 * deploy and as often as a cron schedule likes. It is the single source of
 * truth for the migration step, shared by:
 *   - an hPanel Cron Job (preferred — no public surface), and
 *   - the token-protected GET /deploy/migrate hook (fallback when cron isn't
 *     available; see App\Http\Controllers\DeployController).
 */
class RunPendingMigrations extends Command
{
    protected $signature = 'app:run-pending-migrations';

    protected $description = 'Run pending database migrations (idempotent; safe to run on every deploy).';

    public function handle(): int
    {
        $this->info('Checking for pending migrations…');

        $exit = Artisan::call('migrate', ['--force' => true]);
        $output = trim(Artisan::output());

        if ($output !== '') {
            $this->line($output);
        }

        Log::info('[deploy] run-pending-migrations', ['exit' => $exit, 'output' => $output]);

        if ($exit !== Command::SUCCESS) {
            $this->error('Migration command exited with a non-zero status.');

            return Command::FAILURE;
        }

        $this->info('Database is up to date.');

        return Command::SUCCESS;
    }
}
