<?php

use App\Jobs\RecordQueueHeartbeat;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('uptime:signal-heartbeat')
    ->everyMinute()
    ->when(fn (): bool => (bool) config('observability.uptime.enabled'));

Schedule::job(new RecordQueueHeartbeat)
    ->everyMinute()
    ->when(fn (): bool => (bool) config('observability.uptime.enabled'));

Schedule::command('activitylog:clean')
    ->daily()
    ->when(fn (): bool => (bool) config('activitylog.enabled'));
