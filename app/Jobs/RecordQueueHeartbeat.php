<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class RecordQueueHeartbeat implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        if (! config('observability.uptime.enabled')) {
            return;
        }

        Cache::put(
            config('observability.uptime.cache_key_queue'),
            now()->timestamp,
            now()->addMinutes(config('observability.uptime.queue_stale_minutes') * 2),
        );
    }
}
