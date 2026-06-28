<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use App\Support\TimesheetSummaryBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimesheetSummaryBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_url_includes_table_filters(): void
    {
        $url = TimesheetSummaryBuilder::fromTableFilters([
            'status' => ['value' => 'approved'],
            'project_id' => ['value' => '5'],
            'user_id' => ['value' => '2'],
        ])->exportUrl();

        $this->assertStringContainsString('status=approved', $url);
        $this->assertStringContainsString('projectId=5', $url);
        $this->assertStringContainsString('userId=2', $url);
    }

    public function test_query_respects_status_and_project_filters(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $projectA = Project::create(['code' => 'A', 'name' => 'Alpha']);
        $projectB = Project::create(['code' => 'B', 'name' => 'Beta']);
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $projectA->id,
            'week_start' => $monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'status' => 'approved',
        ]);

        Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $projectB->id,
            'week_start' => $monday,
            'hours' => [4, 4, 4, 4, 4, 0, 0],
            'status' => 'draft',
        ]);

        $this->actingAs($employee);

        $builder = new TimesheetSummaryBuilder(
            projectId: $projectA->id,
            status: 'approved',
        );

        $data = $builder->groupedData();

        $this->assertCount(1, $data);
        $this->assertSame('Alpha', $data[0]['label']);
        $this->assertSame(40.0, $data[0]['hours']);
    }

    public function test_reports_builder_matches_grouping(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $project = Project::create(['code' => 'A', 'name' => 'Alpha']);
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'week_start' => $monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'status' => 'approved',
        ]);

        $this->actingAs($employee);

        $builder = TimesheetSummaryBuilder::fromReports(
            'project',
            $monday->copy()->startOfMonth()->format('Y-m-d'),
            $monday->copy()->endOfMonth()->format('Y-m-d'),
            null,
        );

        $this->assertSame(40.0, $builder->totalHours());
        $this->assertStringContainsString('dateFrom=', $builder->exportUrl());
    }

    public function test_query_scopes_project_manager_to_assigned_projects(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $pm = User::factory()->projectManager()->create();
        $assignedProject = Project::create([
            'code' => 'A',
            'name' => 'Assigned',
            'project_manager_id' => $pm->id,
        ]);
        $otherProject = Project::create([
            'code' => 'B',
            'name' => 'Other',
            'project_manager_id' => User::factory()->projectManager()->create()->id,
        ]);
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $assignedProject->id,
            'week_start' => $monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'status' => 'approved',
        ]);

        Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $otherProject->id,
            'week_start' => $monday,
            'hours' => [4, 4, 4, 4, 4, 0, 0],
            'status' => 'approved',
        ]);

        $this->actingAs($pm);

        $data = (new TimesheetSummaryBuilder())->groupedData();

        $this->assertCount(1, $data);
        $this->assertSame('Assigned', $data[0]['label']);
        $this->assertSame(40.0, $data[0]['hours']);
    }
}
