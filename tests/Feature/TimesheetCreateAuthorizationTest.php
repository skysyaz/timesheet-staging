<?php

namespace Tests\Feature;

use App\Filament\Resources\TimesheetResource\Pages\CreateTimesheet;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use App\Support\TimesheetAccess;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TimesheetCreateAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monday = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $this->project = Project::create([
            'code' => 'CRT-01',
            'name' => 'Create Test Project',
            'status' => 'active',
        ]);
    }

    public function test_employee_does_not_see_user_selector_on_create_form(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee);

        Livewire::test(CreateTimesheet::class)
            ->assertFormFieldIsHidden('user_id');
    }

    public function test_project_manager_does_not_see_user_selector_on_create_form(): void
    {
        $pm = User::factory()->projectManager()->create();

        $this->actingAs($pm);

        Livewire::test(CreateTimesheet::class)
            ->assertFormFieldIsHidden('user_id');
    }

    public function test_admin_sees_populated_user_selector_on_create_form(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create(['role' => 'employee', 'name' => 'Assignable Employee']);

        $this->actingAs($admin);

        Livewire::test(CreateTimesheet::class)
            ->assertFormFieldIsVisible('user_id')
            ->assertFormFieldExists('user_id');

        $options = TimesheetAccess::assignableUserOptionsForAdmin();

        $this->assertArrayHasKey($admin->id, $options);
        $this->assertArrayHasKey($employee->id, $options);
    }

    public function test_project_manager_create_assigns_session_user_automatically(): void
    {
        $pm = User::factory()->projectManager()->create();

        $this->actingAs($pm);

        Livewire::test(CreateTimesheet::class)
            ->fillForm([
                'project_id' => $this->project->id,
                'project_role' => 'Project Manager',
                'work_date' => $this->monday->format('Y-m-d'),
                'week_start' => $this->monday->format('Y-m-d'),
                'hours' => [8, 0, 0, 0, 0, 0, 0],
                'tasks' => ['Planning', '', '', '', '', '', ''],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('timesheets', [
            'user_id' => $pm->id,
            'project_id' => $this->project->id,
            'status' => 'draft',
        ]);
    }

    public function test_admin_can_create_timesheet_for_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($admin);

        Livewire::test(CreateTimesheet::class)
            ->fillForm([
                'user_id' => $employee->id,
                'project_id' => $this->project->id,
                'project_role' => 'Developer',
                'work_date' => $this->monday->format('Y-m-d'),
                'week_start' => $this->monday->format('Y-m-d'),
                'hours' => [8, 0, 0, 0, 0, 0, 0],
                'tasks' => ['Development', '', '', '', '', '', ''],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('timesheets', [
            'user_id' => $employee->id,
            'project_id' => $this->project->id,
        ]);

        $this->assertFalse(
            Timesheet::query()
                ->where('user_id', $admin->id)
                ->where('project_id', $this->project->id)
                ->exists(),
        );
    }

    public function test_project_manager_user_filter_includes_self(): void
    {
        $pm = User::factory()->projectManager()->create();

        $options = TimesheetAccess::userFilterOptionsForViewer($pm);

        $this->assertArrayHasKey($pm->id, $options);
    }
}
