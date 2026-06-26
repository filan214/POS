<?php

namespace App\Http\Controllers;

use App\Console\Commands\RunPendingMigrations;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Web hook to run pending migrations on a host with no SSH and no usable cron.
 *
 * This is the fallback trigger for {@see RunPendingMigrations};
 * the preferred trigger is an hPanel Cron Job calling the artisan command
 * directly (no public route at all). See DEPLOY.md.
 *
 * Safe to leave in the repository permanently — which matters, because the
 * `deploy` branch is rebuilt on every push, so a "delete after use" script can't
 * be relied upon:
 *   - It is DISABLED (404) unless DEPLOY_MIGRATE_TOKEN is set on the server.
 *   - The token is compared in constant time; a mismatch 404s without revealing
 *     that the route exists.
 *   - The only action it can ever take is running idempotent migrations — it
 *     exposes no data and performs no destructive operation.
 *
 * The route is registered without session middleware (see routes/web.php) so it
 * works on the very first deploy, before the database has any tables.
 */
class DeployController extends Controller
{
    public function migrate(Request $request): Response
    {
        $expected = (string) config('deploy.migrate_token');

        // Disabled unless a token has been configured on the server.
        abort_if($expected === '', 404);

        $provided = (string) ($request->query('token') ?? $request->header('X-Deploy-Token', ''));

        // Constant-time comparison; do not reveal whether the route exists.
        abort_unless(hash_equals($expected, $provided), 404);

        // Capture into a dedicated buffer: the command makes a nested
        // Artisan::call('migrate'), which would otherwise clobber the global
        // last-output buffer and hide the command's own summary.
        $output = new BufferedOutput;
        Artisan::call('app:run-pending-migrations', [], $output);

        return response($output->fetch(), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
