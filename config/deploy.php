<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Migration hook token
    |--------------------------------------------------------------------------
    |
    | Secret that authorizes the web-triggered migration hook
    | (GET /deploy/migrate). The route is DISABLED (returns 404) whenever this
    | is empty — which is the case locally and by default. Set it only on a host
    | where you cannot run `php artisan migrate` over SSH or cron.
    |
    | Generate a long random value, e.g.:
    |     php -r "echo bin2hex(random_bytes(24)).PHP_EOL;"
    |
    | Read via config (not env() directly) so it keeps working under
    | `php artisan config:cache`.
    |
    */

    'migrate_token' => env('DEPLOY_MIGRATE_TOKEN'),

];
