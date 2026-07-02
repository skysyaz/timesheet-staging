<?php

namespace Tests\Feature;

use App\Filament\Resources\TimesheetResource;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Timesheet;
use App\Models\User;
use App\Notifications\TimesheetPendingProgramManagerNotification;
use App\Notifications\TimesheetSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TimesheetNotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private User $pm;

    private User $programManager;

    private Project $project;

    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::factory()->create(['role' => 'employee']);
        $this->pm = User::factory()->projectManager()->create();
        $this->programManager = User::factory()->programManager()->create();
        $this->project = Project::create([
            'code' => 'WF-01',
            'name' => 'Workflow Project',
            'project_manager_id' => $this->pm->id,
            'program_manager_id' => $this->programManager->id,
            'project_type_id' => 1,
        ]);
        $this->monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        Setting::create(['key' => 'emailNotifications', 'value' => true]);
        Setting::create(['key' => 'requireProgramManagerApproval', 'value' => true]);
    }

    private function draftTimesheet(): Timesheet
    {
        return Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'project_role' => 'Engineer',
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
            'overtime_hours' => [0, 0, 0, 0, 0, 0, 0],
            'status' => 'draft',
        ]);
    }

    public function test_submit_timesheet_notifies_project_manager(): void
    {
        $timesheet = $this->draftTimesheet();
        $this->actingAs($this->employee);

        Notification::fake();

        TimesheetResource::submitTimesheet($timesheet);

        Notification::assertSentTo($this->pm, TimesheetSubmittedNotification::class);
        $this->assertSame('pending_pm', $timesheet->fresh()->status);
    }

    public function test_pm_approval_notifies_program_manager(): void
    {
        $timesheet = $this->draftTimesheet();
        $timesheet->update(['status' => 'pending_pm']);
        $timesheet->refresh();
        $this->actingAs($this->pm);

        Notification::fake();

        TimesheetResource::handleApprove($timesheet, 'Approved by PM');

        Notification::assertSentTo($this->programManager, TimesheetPendingProgramManagerNotification::class);
        $this->assertSame('pending_program_manager', $timesheet->fresh()->status);
    }
}
