<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Setting;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimesheetWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private User $pm;
    private User $programManager;
    private User $admin;
    private Project $project;
    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::factory()->create(['role' => 'employee']);
        $this->pm = User::factory()->projectManager()->create();
        $this->programManager = User::factory()->programManager()->create();
        $this->admin = User::factory()->admin()->create();
        $this->project = Project::create(['code' => 'TEST-01', 'name' => 'Test Project', 'project_type_id' => 1]);
        $this->monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        Setting::create(['key' => 'requireProgramManagerApproval', 'value' => true]);
    }

    public function test_employee_can_create_timesheet(): void
    {
        $this->actingAs($this->employee);

        $response = $this->get('/timesheets/create');
        $response->assertStatus(200);
    }

    public function test_employee_can_submit_timesheet(): void
    {
        $ts = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
        ]);

        $this->assertEquals('draft', $ts->status);
        $this->assertTrue($ts->isDraft());
        $this->assertTrue($ts->isSubmittable());
        $this->assertEquals(40, $ts->totalHours());
    }

    public function test_pm_can_approve_pending_timesheet(): void
    {
        $ts = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'pending_pm',
        ]);

        $this->assertTrue($ts->isPendingPm());
        $this->assertTrue($this->pm->canApproveAsPm());
        $this->assertFalse($ts->isApproved());
    }

    public function test_program_manager_can_approve_pending_program_manager_timesheet(): void
    {
        $ts = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'pending_program_manager',
        ]);

        $this->assertTrue($ts->isPendingProgramManager());
        $this->assertTrue($this->programManager->canApproveAsProgramManager());
    }

    public function test_employee_cannot_see_other_timesheets(): void
    {
        User::factory()->create(['role' => 'employee']);

        $ts1 = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
        ]);

        $ts2 = Timesheet::create([
            'user_id' => $this->pm->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
        ]);

        $this->assertEquals($ts1->user_id, $this->employee->id);
        $this->assertEquals($ts2->user_id, $this->pm->id);
        $this->assertNotEquals($ts1->user_id, $ts2->user_id);
    }

    public function test_rejected_timesheet_is_editable(): void
    {
        $ts = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'rejected',
        ]);

        $this->assertTrue($ts->isRejected());
        $this->assertTrue($ts->isEditable());
        $this->assertTrue($ts->isSubmittable());
    }

    public function test_approval_flow_without_program_manager(): void
    {
        Setting::setValue('requireProgramManagerApproval', false);

        $ts = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'pending_pm',
        ]);

        $this->assertTrue($ts->isPendingPm());
        $this->assertFalse(Setting::getValue('requireProgramManagerApproval', true));
    }

    public function test_models_have_expected_fillable_fields(): void
    {
        $ts = new Timesheet();
        $this->assertEquals([
            'user_id', 'project_id', 'project_role', 'week_start', 'hours', 'overtime_hours', 'tasks', 'status', 'notes',
        ], $ts->getFillable());

        $user = new User();
        $this->assertEquals([
            'name', 'email', 'password', 'role', 'color',
        ], $user->getFillable());
    }
}
