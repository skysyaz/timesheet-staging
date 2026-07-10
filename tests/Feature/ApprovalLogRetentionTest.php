<?php

namespace Tests\Feature;

use App\Filament\Resources\TimesheetResource;
use App\Models\ApprovalLog;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalLogRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_logs_survive_approver_user_deletion(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $pm = User::factory()->projectManager()->create(['name' => 'Former PM']);
        $admin = User::factory()->admin()->create();
        $project = Project::create([
            'code' => 'RET-01',
            'name' => 'Retention Project',
            'project_manager_id' => $pm->id,
            'project_type_id' => 1,
        ]);

        $timesheet = Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => Carbon::now()->startOfWeek(Carbon::MONDAY),
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'approved',
        ]);

        $log = ApprovalLog::create([
            'timesheet_id' => $timesheet->id,
            'user_id' => $pm->id,
            'action' => 'approved_pm',
            'comment' => 'Looks good',
        ]);

        $pmId = $pm->id;
        $pm->delete();

        $this->assertDatabaseMissing('users', ['id' => $pmId]);
        $this->assertDatabaseHas('approval_logs', [
            'id' => $log->id,
            'timesheet_id' => $timesheet->id,
            'user_id' => null,
            'action' => 'approved_pm',
            'comment' => 'Looks good',
        ]);

        $this->assertNull($log->fresh()->user_id);
        $this->assertNull($log->fresh()->user);

        $response = $this->actingAs($admin)
            ->get(TimesheetResource::getUrl('view', ['record' => $timesheet]));

        $response->assertOk();
        $response->assertSee('Looks good', false);
        $response->assertSee('(deleted user)', false);
    }
}
