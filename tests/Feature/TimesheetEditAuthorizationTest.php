<?php

namespace Tests\Feature;

use App\Filament\Resources\TimesheetResource;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use App\Support\TimesheetAccess;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimesheetEditAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private User $admin;

    private Project $project;

    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::factory()->create(['role' => 'employee']);
        $this->admin = User::factory()->admin()->create();
        $this->project = Project::create(['code' => 'TST-01', 'name' => 'Test Project']);
        $this->monday = Carbon::now()->startOfWeek(Carbon::MONDAY);
    }

    public function test_employee_can_access_edit_page_for_draft_timesheet(): void
    {
        $timesheet = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
        ]);

        $this->actingAs($this->employee)
            ->get(TimesheetResource::getUrl('edit', ['record' => $timesheet]))
            ->assertOk();
    }

    public function test_employee_cannot_access_edit_page_for_approved_timesheet(): void
    {
        $timesheet = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'approved',
        ]);

        $this->actingAs($this->employee)
            ->get(TimesheetResource::getUrl('edit', ['record' => $timesheet]))
            ->assertForbidden();
    }

    public function test_project_manager_can_access_edit_page_for_own_draft_timesheet(): void
    {
        $pm = User::factory()->projectManager()->create();
        $timesheet = Timesheet::create([
            'user_id' => $pm->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
        ]);

        $this->assertTrue(TimesheetAccess::userCanViewTimesheet($pm, $timesheet));
        $this->assertTrue(
            TimesheetAccess::scopeTimesheetsForUser(Timesheet::query(), $pm)->whereKey($timesheet->id)->exists(),
        );

        $this->actingAs($pm)
            ->get(TimesheetResource::getUrl('edit', ['record' => $timesheet]))
            ->assertOk();
    }

    public function test_project_manager_cannot_access_edit_page_for_other_users_draft_timesheet(): void
    {
        $pm = User::factory()->projectManager()->create();
        $timesheet = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
        ]);

        $this->actingAs($pm)
            ->get(TimesheetResource::getUrl('edit', ['record' => $timesheet]))
            ->assertNotFound();
    }

    public function test_employee_can_access_view_page_for_approved_timesheet(): void
    {
        $timesheet = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'approved',
        ]);

        $this->actingAs($this->employee)
            ->get(TimesheetResource::getUrl('view', ['record' => $timesheet]))
            ->assertOk();
    }

    public function test_admin_revert_makes_timesheet_editable_for_employee(): void
    {
        $timesheet = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'approved',
        ]);

        $this->actingAs($this->admin);

        TimesheetResource::handleRevertToDraft($timesheet, 'Correction requested by HR');

        $timesheet->refresh();

        $this->assertSame('draft', $timesheet->status);
        $this->assertTrue($timesheet->isEditable());

        $this->assertDatabaseHas('approval_logs', [
            'timesheet_id' => $timesheet->id,
            'user_id' => $this->admin->id,
            'action' => 'reverted_to_draft',
            'comment' => 'Correction requested by HR',
        ]);

        $this->actingAs($this->employee)
            ->get(TimesheetResource::getUrl('edit', ['record' => $timesheet]))
            ->assertOk();
    }

    public function test_employee_cannot_revert_approved_timesheet(): void
    {
        $timesheet = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'approved',
        ]);

        $this->actingAs($this->employee);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        TimesheetResource::handleRevertToDraft($timesheet, 'Should fail');
    }

    public function test_print_pdf_remains_available_for_approved_timesheet(): void
    {
        $timesheet = Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'approved',
        ]);

        $this->actingAs($this->employee)
            ->get(route('pdf.weekly', $timesheet))
            ->assertOk();
    }
}
