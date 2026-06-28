<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SignalUptimeHeartbeat extends Command
{
    protected $signature = 'uptime:signal-heartbeat';

    protected $description = 'Record a scheduler heartbeat for Better Uptime monitoring';

    public function handle(): int
    {
        if (! config('observability.uptime.enabled')) {
            $this->components->warn('Uptime monitoring is disabled (BETTER_UPTIME_ENABLED=false).');

            return self::SUCCESS;
        }

        Cache::put(
            config('observability.uptime.cache_key_scheduler'),
            now()->timestamp,
            now()->addMinutes(config('observability.uptime.scheduler_stale_minutes') * 2),
        );

        $this->components->info('Scheduler heartbeat recorded.');

        return self::SUCCESS;
    }
}
