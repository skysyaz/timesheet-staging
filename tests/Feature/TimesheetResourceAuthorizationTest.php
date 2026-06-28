<?php

namespace Tests\Feature;

use App\Filament\Resources\TimesheetResource;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimesheetResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_query_scopes_project_manager_to_assigned_projects(): void
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

        $visibleIds = TimesheetResource::getEloquentQuery()->pluck('id');

        $this->assertCount(1, $visibleIds);
        $this->assertTrue(
            Timesheet::where('project_id', $assignedProject->id)->whereKey($visibleIds)->exists(),
        );
    }

    public function test_resource_query_scopes_project_director_to_assigned_projects(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $pd = User::factory()->projectDirector()->create();
        $assignedProject = Project::create([
            'code' => 'A',
            'name' => 'Assigned',
            'project_director_id' => $pd->id,
        ]);
        $otherProject = Project::create([
            'code' => 'B',
            'name' => 'Other',
            'project_director_id' => User::factory()->projectDirector()->create()->id,
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

        $this->actingAs($pd);

        $visibleIds = TimesheetResource::getEloquentQuery()->pluck('id');

        $this->assertCount(1, $visibleIds);
        $this->assertTrue(
            Timesheet::where('project_id', $assignedProject->id)->whereKey($visibleIds)->exists(),
        );
    }

    public function test_resource_query_allows_admin_to_see_all_timesheets(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $admin = User::factory()->admin()->create();
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => Project::create(['code' => 'A', 'name' => 'Alpha'])->id,
            'week_start' => $monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'status' => 'approved',
        ]);

        Timesheet::create([
            'user_id' => User::factory()->create(['role' => 'employee'])->id,
            'project_id' => Project::create(['code' => 'B', 'name' => 'Beta'])->id,
            'week_start' => $monday,
            'hours' => [4, 4, 4, 4, 4, 0, 0],
            'status' => 'approved',
        ]);

        $this->actingAs($admin);

        $this->assertCount(2, TimesheetResource::getEloquentQuery()->get());
    }
}
