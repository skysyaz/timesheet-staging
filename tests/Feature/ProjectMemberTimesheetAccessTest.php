<?php

namespace Tests\Feature;

use App\Filament\Resources\TimesheetResource\Pages\CreateTimesheet;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectMemberTimesheetAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_only_sees_assigned_active_projects_in_timesheet_form(): void
    {
        $pm = User::factory()->projectManager()->create();
        $pd = User::factory()->projectDirector()->create();
        $employee = User::factory()->create(['name' => 'Ainie Idris']);

        $assignedProject = Project::create([
            'code' => 'ASSGN',
            'name' => 'Assigned Project',
            'status' => 'active',
            'start_date' => '2026-06-22',
            'end_date' => '2026-06-28',
            'project_manager_id' => $pm->id,
            'project_director_id' => $pd->id,
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
            'project_director_id' => $pd->id,
            'created_by' => $pm->id,
        ]);

        $this->actingAs($employee);

        Livewire::test(CreateTimesheet::class)
            ->assertFormFieldExists('project_id')
            ->assertFormSet([
                'project_id' => null,
            ]);

        $this->assertTrue(
            Project::query()
                ->where('status', 'active')
                ->whereHas('members', fn ($query) => $query->whereKey($employee->id))
                ->whereKey($assignedProject->id)
                ->exists()
        );

        $this->assertFalse($assignedProject->hasMember(User::factory()->create()));
        $this->assertFalse($otherProject->hasMember($employee));
    }
}
