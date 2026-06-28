<?php

namespace Tests\Feature;

use App\Jobs\RecordQueueHeartbeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UptimeHeartbeatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'observability.uptime.enabled' => true,
            'observability.uptime.heartbeat_token' => 'test-heartbeat-token',
            'observability.uptime.scheduler_stale_minutes' => 5,
            'observability.uptime.queue_stale_minutes' => 5,
        ]);
    }

    public function test_scheduler_heartbeat_requires_valid_token(): void
    {
        $this->get('/uptime/heartbeat')
            ->assertForbidden();

        $this->get('/uptime/heartbeat?token=wrong')
            ->assertForbidden();
    }

    public function test_scheduler_heartbeat_returns_service_unavailable_when_stale(): void
    {
        Cache::forget(config('observability.uptime.cache_key_scheduler'));

        $this->get('/uptime/heartbeat?token=test-heartbeat-token')
            ->assertStatus(503)
            ->assertSee('Scheduler heartbeat stale');
    }

    public function test_scheduler_heartbeat_returns_ok_after_signal_command(): void
    {
        Artisan::call('uptime:signal-heartbeat');

        $this->get('/uptime/heartbeat?token=test-heartbeat-token')
            ->assertOk()
            ->assertSee('OK');
    }

    public function test_queue_heartbeat_returns_ok_after_job_runs(): void
    {
        Cache::forget(config('observability.uptime.cache_key_queue'));

        $this->get('/uptime/queue-heartbeat?token=test-heartbeat-token')
            ->assertStatus(503);

        (new RecordQueueHeartbeat)->handle();

        $this->get('/uptime/queue-heartbeat?token=test-heartbeat-token')
            ->assertOk()
            ->assertSee('OK');
    }

    public function test_heartbeats_return_service_unavailable_when_monitoring_disabled(): void
    {
        config(['observability.uptime.enabled' => false]);

        Artisan::call('uptime:signal-heartbeat');

        $this->get('/uptime/heartbeat?token=test-heartbeat-token')
            ->assertStatus(503)
            ->assertSee('Uptime monitoring disabled');
    }
}
