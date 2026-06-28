<?php

namespace Tests\Feature;

use App\Console\Commands\BackfillActivityLogFromApprovalLogs;
use App\Models\ApprovalLog;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class BackfillActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_imports_approval_logs_into_activity_log(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $pm = User::factory()->projectManager()->create();
        $project = Project::create(['code' => 'BF-01', 'name' => 'Backfill Project', 'project_manager_id' => $pm->id]);

        $timesheet = Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => Carbon::now()->startOfWeek(Carbon::MONDAY),
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'status' => 'pending_pm',
        ]);

        $approvalLog = ApprovalLog::create([
            'timesheet_id' => $timesheet->id,
            'user_id' => $pm->id,
            'action' => 'approved_pm',
            'comment' => 'Looks good',
        ]);

        $this->artisan(BackfillActivityLogFromApprovalLogs::class)
            ->assertSuccessful();

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Timesheet approved by PM',
            'subject_type' => Timesheet::class,
            'subject_id' => $timesheet->id,
            'causer_id' => $pm->id,
        ]);

        $activity = Activity::query()
            ->where('description', 'Timesheet approved by PM')
            ->where('subject_id', $timesheet->id)
            ->first();
        $this->assertSame('approval_log:' . $approvalLog->id, $activity?->getProperty('backfill_source'));

        $this->artisan(BackfillActivityLogFromApprovalLogs::class)
            ->expectsOutputToContain('0 audit entries')
            ->assertSuccessful();
    }
}
