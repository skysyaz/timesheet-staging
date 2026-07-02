<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectApproverTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_assigned_project_manager_can_approve_pm_step(): void
    {
        $pm = User::factory()->projectManager()->create();
        $otherPm = User::factory()->projectManager()->create();
        $admin = User::factory()->admin()->create();

        $project = Project::create([
            'code' => 'PRJ-01',
            'name' => 'Demo',
            'project_manager_id' => $pm->id,
            'program_manager_id' => User::factory()->programManager()->create()->id,
            'project_type_id' => 1,
        ]);

        $timesheet = Timesheet::create([
            'user_id' => User::factory()->create()->id,
            'project_id' => $project->id,
            'week_start' => Carbon::now()->startOfWeek(Carbon::MONDAY),
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'pending_pm',
        ]);

        $this->assertTrue($timesheet->canBeApprovedBy($pm));
        $this->assertFalse($timesheet->canBeApprovedBy($otherPm));
        $this->assertTrue($timesheet->canBeApprovedBy($admin));
    }

    public function test_only_assigned_program_manager_can_approve_program_manager_step(): void
    {
        $programManager = User::factory()->programManager()->create();
        $otherProgramManager = User::factory()->programManager()->create();

        $project = Project::create([
            'code' => 'PRJ-02',
            'name' => 'Demo 2',
            'project_manager_id' => User::factory()->projectManager()->create()->id,
            'program_manager_id' => $programManager->id,
            'project_type_id' => 1,
        ]);

        $timesheet = Timesheet::create([
            'user_id' => User::factory()->create()->id,
            'project_id' => $project->id,
            'week_start' => Carbon::now()->startOfWeek(Carbon::MONDAY),
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'pending_program_manager',
        ]);

        $this->assertTrue($timesheet->canBeApprovedBy($programManager));
        $this->assertFalse($timesheet->canBeApprovedBy($otherProgramManager));
    }
}
