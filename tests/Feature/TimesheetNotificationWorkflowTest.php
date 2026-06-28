<?php

namespace Tests\Feature;

use App\Filament\Resources\TimesheetResource;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Timesheet;
use App\Models\User;
use App\Notifications\TimesheetPendingDirectorNotification;
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

    private User $pd;

    private Project $project;

    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::factory()->create(['role' => 'employee']);
        $this->pm = User::factory()->projectManager()->create();
        $this->pd = User::factory()->projectDirector()->create();
        $this->project = Project::create([
            'code' => 'WF-01',
            'name' => 'Workflow Project',
            'project_manager_id' => $this->pm->id,
            'project_director_id' => $this->pd->id,
        ]);
        $this->monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        Setting::create(['key' => 'emailNotifications', 'value' => true]);
        Setting::create(['key' => 'requireDirectorApproval', 'value' => true]);
    }

    private function draftTimesheet(): Timesheet
    {
        return Timesheet::create([
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'project_role' => 'Engineer',
            'week_start' => $this->monday,
            'hours' => [8, 8, 8, 8, 8, 0, 0],
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

    public function test_pm_approval_notifies_project_director(): void
    {
        $timesheet = $this->draftTimesheet();
        $timesheet->update(['status' => 'pending_pm']);
        $timesheet->refresh();
        $this->actingAs($this->pm);

        Notification::fake();

        TimesheetResource::handleApprove($timesheet, 'Approved by PM');

        Notification::assertSentTo($this->pd, TimesheetPendingDirectorNotification::class);
        $this->assertSame('pending_pd', $timesheet->fresh()->status);
    }
}
