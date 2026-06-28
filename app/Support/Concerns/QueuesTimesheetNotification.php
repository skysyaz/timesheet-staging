<?php

namespace App\Support\Concerns;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Log;

/**
 * Queued timesheet mail with retries and structured failure logging.
 */
trait QueuesTimesheetNotification
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    public function failed(\Throwable $exception): void
    {
        $timesheetId = property_exists($this, 'timesheet') ? ($this->timesheet->id ?? null) : null;

        Log::error('Timesheet notification delivery failed after retries', [
            'notification' => static::class,
            'timesheet_id' => $timesheetId,
            'error' => $exception->getMessage(),
        ]);
    }
}
