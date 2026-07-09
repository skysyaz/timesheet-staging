<?php

namespace App\Providers;

use App\Support\WatchtowerReporter;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class WatchtowerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WatchtowerReporter::class);
    }

    public function boot(): void
    {
        Queue::failing(function (JobFailed $event): void {
            app(WatchtowerReporter::class)->report($event->exception, [
                'source' => 'queue',
                'job' => $event->job->resolveName(),
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
            ]);

            if (str_contains($event->job->getName(), 'SendQueuedNotifications')) {
                Log::error('Queued timesheet notification job failed', [
                    'job' => $event->job->getName(),
                    'connection' => $event->connectionName,
                    'queue' => $event->job->getQueue(),
                    'exception' => $event->exception->getMessage(),
                ]);
            }
        });

        Event::listen(ScheduledTaskFailed::class, function (ScheduledTaskFailed $event): void {
            app(WatchtowerReporter::class)->report($event->exception, [
                'source' => 'scheduled',
                'task' => $event->task->getSummaryForDisplay(),
            ]);
        });
    }
}
