<?php

namespace Tests\Feature;

use App\Filament\Resources\TimesheetResource;
use App\Filament\Resources\TimesheetResource\Pages\ListTimesheets;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Timesheet;
use App\Models\User;
use App\Notifications\TimesheetPendingProgramManagerNotification;
use App\Notifications\TimesheetSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TimesheetPmSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $pm;

    private User $programManager;

    private Project $project;

    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pm = User::factory()->projectManager()->create();
        $this->programManager = User::factory()->programManager()->create();
        $this->project = Project::create([
            'code' => 'PM-01',
            'name' => 'PM Project',
            'project_manager_id' => $this->pm->id,
            'program_manager_id' => $this->programManager->id,
            'project_type_id' => 1,
        ]);
        $this->monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        Setting::create(['key' => 'emailNotifications', 'value' => false]);
        Setting::create(['key' => 'requireProgramManagerApproval', 'value' => true]);
    }

    public function test_pm_submitted_timesheet_goes_to_pending_program_manager(): void
    {
        $timesheet = Timesheet::create([
            'user_id' => $this->pm->id,
            'project_id' => $this->project->id,
            'project_role' => 'Project Manager',
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
        ]);

        $this->actingAs($this->pm);

        TimesheetResource::submitTimesheet($timesheet);

        $timesheet->refresh();

        $this->assertSame('pending_program_manager', $timesheet->status);
        $this->assertDatabaseHas('approval_logs', [
            'timesheet_id' => $timesheet->id,
            'user_id' => $this->pm->id,
            'action' => 'submitted',
        ]);
        $this->assertDatabaseHas('approval_logs', [
            'timesheet_id' => $timesheet->id,
            'user_id' => $this->pm->id,
            'action' => 'approved_pm',
        ]);
    }

    public function test_pm_submission_notifies_program_manager_not_self(): void
    {
        $timesheet = Timesheet::create([
            'user_id' => $this->pm->id,
            'project_id' => $this->project->id,
            'project_role' => 'Project Manager',
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
        ]);

        Setting::where('key', 'emailNotifications')->update(['value' => true]);

        $this->actingAs($this->pm);
        Notification::fake();

        TimesheetResource::submitTimesheet($timesheet);

        Notification::assertSentTo($this->programManager, TimesheetPendingProgramManagerNotification::class);
        Notification::assertNotSentTo($this->pm, TimesheetSubmittedNotification::class);
    }

    public function test_pm_can_submit_multiple_timesheets_for_same_project(): void
    {
        $first = Timesheet::create([
            'user_id' => $this->pm->id,
            'project_id' => $this->project->id,
            'project_role' => 'Project Manager',
            'week_start' => $this->monday,
            'hours' => [8, 0, 0, 0, 0, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'tasks' => ['Activity 1', '', '', '', '', '', ''],
            'status' => 'draft',
        ]);

        $second = Timesheet::create([
            'user_id' => $this->pm->id,
            'project_id' => $this->project->id,
            'project_role' => 'Project Manager',
            'week_start' => $this->monday,
            'hours' => [0, 8, 0, 0, 0, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'tasks' => ['', 'Activity 2', '', '', '', '', ''],
            'status' => 'draft',
        ]);

        $this->actingAs($this->pm);

        TimesheetResource::submitTimesheet($first);
        TimesheetResource::submitTimesheet($second);

        $this->assertSame('pending_program_manager', $first->fresh()->status);
        $this->assertSame('pending_program_manager', $second->fresh()->status);
        $this->assertSame(2, Timesheet::query()->where('user_id', $this->pm->id)->where('project_id', $this->project->id)->count());
    }

    public function test_pm_can_submit_second_timesheet_from_list_action(): void
    {
        Timesheet::create([
            'user_id' => $this->pm->id,
            'project_id' => $this->project->id,
            'project_role' => 'Project Manager',
            'week_start' => $this->monday,
            'hours' => [8, 0, 0, 0, 0, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'pending_program_manager',
        ]);

        $second = Timesheet::create([
            'user_id' => $this->pm->id,
            'project_id' => $this->project->id,
            'week_start' => $this->monday,
            'hours' => [0, 8, 0, 0, 0, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
        ]);

        Livewire::actingAs($this->pm)
            ->test(ListTimesheets::class)
            ->callTableAction('submit', $second)
            ->assertNotified();

        $this->assertSame('pending_program_manager', $second->fresh()->status);
        $this->assertSame('Project Manager', $second->fresh()->project_role);
    }

    public function test_future_week_timesheet_cannot_be_approved_by_pm(): void
    {
        $nextMonday = $this->monday->copy()->addWeek();
        $timesheet = Timesheet::create([
            'user_id' => $this->pm->id,
            'project_id' => $this->project->id,
            'project_role' => 'Project Manager',
            'week_start' => $nextMonday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'pending_pm',
        ]);

        $this->actingAs($this->pm);
        $this->assertFalse($timesheet->canBeApprovedBy($this->pm));

        try {
            TimesheetResource::handleApprove($timesheet, 'go');
            $this->fail('Expected ValidationException for approving a future-week timesheet.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                'has not started yet',
                collect($exception->validator->errors()->get('week_start'))->first(),
            );
        }

        $this->assertSame('pending_pm', $timesheet->fresh()->status);
        $this->assertDatabaseMissing('approval_logs', [
            'timesheet_id' => $timesheet->id,
            'action' => 'approved_pm',
        ]);
    }

    public function test_unauthorized_approve_aborts_forbidden(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $timesheet = Timesheet::create([
            'user_id' => $employee->id,
            'project_id' => $this->project->id,
            'project_role' => 'Developer',
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'pending_pm',
        ]);

        $this->actingAs($employee);

        try {
            TimesheetResource::handleApprove($timesheet, 'nope');
            $this->fail('Expected HttpException for unauthorized approve.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame('pending_pm', $timesheet->fresh()->status);
    }
}
