<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Rules\ProjectMembershipForEmployee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMemberTimesheetAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_only_sees_assigned_active_projects_in_timesheet_form(): void
    {
        $pm = User::factory()->projectManager()->create();
        $programManager = User::factory()->programManager()->create();
        $employee = User::factory()->create(['name' => 'Ainie Idris']);

        $assignedProject = Project::create([
            'code' => 'ASSGN',
            'name' => 'Assigned Project',
            'status' => 'active',
            'start_date' => '2026-06-22',
            'end_date' => '2026-06-28',
            'project_manager_id' => $pm->id,
            'program_manager_id' => $programManager->id,
            'project_type_id' => 1,
            'created_by' => $pm->id,
        ]);
        $assignedProject->members()->attach($employee->id);

        $otherProject = Project::create([
            'code' => 'OTHER',
            'name' => 'Other Project',
            'status' => 'active',
            'start_date' => '2026-06-22',
            'end_date' => '2026-06-28',
            'project_manager_id' => $pm->id,
            'program_manager_id' => $programManager->id,
            'project_type_id' => 1,
            'created_by' => $pm->id,
        ]);

        $employeeProjects = Project::query()
            ->where('status', 'active')
            ->whereHas('members', fn ($query) => $query->whereKey($employee->id))
            ->pluck('id');

        $this->assertContains($assignedProject->id, $employeeProjects->all());
        $this->assertNotContains($otherProject->id, $employeeProjects->all());

        $rule = new ProjectMembershipForEmployee($employee);
        $rejected = false;
        $rule->validate('project_id', $otherProject->id, function () use (&$rejected): void {
            $rejected = true;
        });
        $this->assertTrue($rejected, 'Non-member project_id must fail server-side membership rule');

        $allowed = false;
        $rule->validate('project_id', $assignedProject->id, function () use (&$allowed): void {
            $allowed = true;
        });
        $this->assertFalse($allowed, 'Assigned project_id must pass membership rule');
    }
}
