<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeployHookTest extends TestCase
{
    use RefreshDatabase;

    public function test_hook_is_disabled_without_a_configured_token(): void
    {
        config(['deploy.migrate_token' => null]);

        $this->get('/deploy/migrate?token=anything')->assertNotFound();
    }

    public function test_hook_rejects_a_wrong_token_without_revealing_itself(): void
    {
        config(['deploy.migrate_token' => 'the-real-secret']);

        // Wrong token and missing token both 404 (not 401/403) — no existence leak.
        $this->get('/deploy/migrate?token=wrong')->assertNotFound();
        $this->get('/deploy/migrate')->assertNotFound();
    }

    public function test_hook_runs_migrations_with_the_correct_token(): void
    {
        config(['deploy.migrate_token' => 'the-real-secret']);

        $response = $this->get('/deploy/migrate?token=the-real-secret');

        $response->assertOk();
        // The schema is already migrated in tests, so the run is a safe no-op.
        $response->assertSee('up to date', false);
    }

    public function test_hook_also_accepts_the_token_via_header(): void
    {
        config(['deploy.migrate_token' => 'the-real-secret']);

        $this->get('/deploy/migrate', ['X-Deploy-Token' => 'the-real-secret'])
            ->assertOk();
    }
}
