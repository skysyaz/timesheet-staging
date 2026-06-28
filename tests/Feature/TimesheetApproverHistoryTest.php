<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports;
use App\Models\ApprovalLog;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use App\Support\TimesheetAccess;
use App\Support\TimesheetSummaryBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TimesheetApproverHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_manager_can_view_timesheet_they_approved_after_reassignment(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $pm = User::factory()->projectManager()->create();
        $project = Project::create([
            'code' => 'A',
            'name' => 'Alpha',
            'project_manager_id' => User::factory()->projectManager()->create()->id,
        ]);
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        $timesheet = Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => $monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'status' => 'approved',
        ]);

        ApprovalLog::create([
            'timesheet_id' => $timesheet->id,
            'user_id' => $pm->id,
            'action' => 'approved_pm',
            'comment' => 'Looks good',
        ]);

        $this->assertTrue(TimesheetAccess::userCanViewTimesheet($pm, $timesheet));
        $this->assertTrue(
            TimesheetAccess::scopeTimesheetsForUser(Timesheet::query(), $pm)->whereKey($timesheet->id)->exists(),
        );
    }

    public function test_project_manager_sees_analytics_for_all_assigned_projects(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $pm = User::factory()->projectManager()->create();
        $projectA = Project::create([
            'code' => 'A',
            'name' => 'Alpha',
            'project_manager_id' => $pm->id,
        ]);
        $projectB = Project::create([
            'code' => 'B',
            'name' => 'Beta',
            'project_manager_id' => $pm->id,
        ]);
        $otherProject = Project::create([
            'code' => 'C',
            'name' => 'Gamma',
            'project_manager_id' => User::factory()->projectManager()->create()->id,
        ]);
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        foreach ([$projectA, $projectB, $otherProject] as $project) {
            Timesheet::create([
                'user_id' => $employee->id,
                'project_id' => $project->id,
                'week_start' => $monday,
                'hours' => [8, 8, 8, 8, 8, 0, 0],
                'status' => 'approved',
            ]);
        }

        $this->actingAs($pm);

        $data = TimesheetSummaryBuilder::fromReports(
            'project',
            $monday->copy()->startOfYear()->format('Y-m-d'),
            $monday->copy()->endOfMonth()->format('Y-m-d'),
            null,
        )->groupedData();

        $this->assertCount(2, $data);
        $this->assertEqualsCanonicalizing(['Alpha', 'Beta'], array_column($data, 'label'));
    }

    public function test_reports_page_lists_all_active_projects_for_project_manager(): void
    {
        $pm = User::factory()->projectManager()->create();
        Project::create([
            'code' => 'A',
            'name' => 'Assigned',
            'status' => 'active',
            'project_manager_id' => $pm->id,
            'project_director_id' => User::factory()->projectDirector()->create()->id,
            'created_by' => $pm->id,
        ]);
        Project::create([
            'code' => 'B',
            'name' => 'Other',
            'status' => 'active',
            'project_manager_id' => User::factory()->projectManager()->create()->id,
            'project_director_id' => User::factory()->projectDirector()->create()->id,
            'created_by' => User::factory()->projectManager()->create()->id,
        ]);

        $this->actingAs($pm);

        $projects = Livewire::test(Reports::class)->instance()->getProjects();

        $this->assertCount(2, $projects);
    }
}
