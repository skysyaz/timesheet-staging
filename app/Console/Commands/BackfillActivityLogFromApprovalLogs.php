<?php

namespace App\Console\Commands;

use App\Models\ApprovalLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class BackfillActivityLogFromApprovalLogs extends Command
{
    protected $signature = 'activitylog:backfill-approval-logs {--dry-run : Preview rows without writing}';

    protected $description = 'Import historical approval_logs rows into the activity_log audit table';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $imported = 0;
        $skipped = 0;

        ApprovalLog::query()
            ->with(['timesheet', 'user'])
            ->orderBy('id')
            ->chunkById(200, function ($logs) use ($dryRun, &$imported, &$skipped): void {
                foreach ($logs as $log) {
                    $sourceKey = 'approval_log:'.$log->id;

                    $exists = Activity::query()
                        ->where('properties->backfill_source', $sourceKey)
                        ->exists();

                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    if ($dryRun) {
                        $imported++;

                        continue;
                    }

                    DB::transaction(function () use ($log, $sourceKey, &$imported): void {
                        activity($log->user?->role ?? 'system')
                            ->causedBy($log->user)
                            ->performedOn($log->timesheet)
                            ->withProperties([
                                'backfill_source' => $sourceKey,
                                'action' => $log->action ?? null,
                                'comment' => $log->comment,
                            ])
                            ->tap(function (Activity $activity) use ($log): void {
                                $activity->created_at = $log->created_at;
                                $activity->updated_at = $log->updated_at;
                            })
                            ->log($this->descriptionForAction($log->action));
                    });

                    $imported++;
                }
            });

        $this->info(($dryRun ? 'Would import' : 'Imported')." {$imported} audit entries ({$skipped} already present).");

        return self::SUCCESS;
    }

    protected function descriptionForAction(string $action): string
    {
        return match ($action) {
            'submitted' => 'Timesheet submitted for approval',
            'approved_pm' => 'Timesheet approved by PM',
            'rejected_pm' => 'Timesheet rejected by PM',
            'approved_program_manager' => 'Timesheet approved by Program Manager',
            'rejected_program_manager' => 'Timesheet rejected by Program Manager',
            'rejected' => 'Timesheet rejected',
            'reverted' => 'Timesheet reverted to draft',
            default => 'Timesheet workflow action: '.str_replace('_', ' ', $action),
        };
    }
}
